<?php
$conn = oci_connect('NtgBi', 'NtgbI@2025', '192.168.100.29:1521/orcl');
if ($conn) {
    echo "Connected!";
    oci_close($conn);
} else {
    echo "Failed: " . print_r(oci_error(), true);
}
?>


<?php
// =================================================================================
// 1. DATABASE CONNECTION & LOGIC
// =================================================================================

// Database Credentials
$db_username = "NtgBi";
$db_password = "NtgbI@2025";
$db_host     = "192.168.100.29:1521/orcl"; // Connection string

// Initialize variables
$conn = null;
$rows = [];
$headerInfo = null;
$error_msg = "";
$search_performed = false;

// Process Search
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['search'])) {
    $conn = oci_connect($db_username, $db_password, $db_host);

    if (!$conn) {
        $e = oci_error();
        $error_msg = "Connection failed: " . $e['message'];
    } else {
        $search_performed = true;

        // inputs
        $job_no     = $_POST['job_no'] ?? '';
        $po_id      = $_POST['po_id'] ?? '';
        $style_ref  = $_POST['style_ref'] ?? '';
        $date_from  = $_POST['date_from'] ?? '';
        $date_to    = $_POST['date_to'] ?? '';

        // Base SQL (From your snippet)
        $sql = "SELECT
                    w.PO_BREAK_DOWN_ID,
                    w.JOB_NO_MST,
                    w.JOB_ID,
                    dm.STYLE_REF_NO,
                    b.BUYER_NAME               AS BUYER_NAME,
                    gi.ITEM_NAME               AS GARMENT_ITEM,
                    ctry.COUNTRY_NAME          AS COUNTRY,
                    sz.SIZE_NAME               AS SIZE_NAME,
                    clr.COLOR_NAME             AS COLOR_NAME,
                    w.ORDER_QUANTITY,
                    w.ORDER_RATE,
                    w.ORDER_TOTAL,
                    w.COLOR_ORDER,
                    w.SIZE_ORDER,
                    w.COUNTRY_SHIP_DATE,
                    w.INSERT_DATE
                FROM NFLERPLIVE.wo_po_color_size_breakdown w
                LEFT JOIN NFLERPLIVE.WO_PO_DETAILS_MASTER dm ON dm.ID = w.JOB_ID AND dm.IS_DELETED = 0
                LEFT JOIN NFLERPLIVE.LIB_BUYER b ON b.ID = dm.BUYER_NAME AND b.IS_DELETED = 0
                LEFT JOIN NFLERPLIVE.LIB_GARMENT_ITEM gi ON gi.ID = w.ITEM_NUMBER_ID AND gi.IS_DELETED = 0
                LEFT JOIN NFLERPLIVE.LIB_COUNTRY ctry ON ctry.ID = w.COUNTRY_ID AND ctry.IS_DELETED = 0
                LEFT JOIN NFLERPLIVE.LIB_SIZE sz ON sz.ID = w.SIZE_NUMBER_ID AND sz.IS_DELETED = 0
                LEFT JOIN NFLERPLIVE.LIB_COLOR clr ON clr.ID = w.COLOR_NUMBER_ID AND clr.IS_DELETED = 0
                WHERE w.IS_DELETED = 0 "; // Basic safety check

        // Dynamic Filtering
        $bindings = [];

        if (!empty($job_no)) {
            $sql .= " AND w.JOB_NO_MST LIKE :job_no ";
            $bindings[':job_no'] = "%" . $job_no . "%";
        }
        if (!empty($po_id)) {
            $sql .= " AND w.PO_BREAK_DOWN_ID = :po_id ";
            $bindings[':po_id'] = $po_id;
        }
        if (!empty($style_ref)) {
            $sql .= " AND dm.STYLE_REF_NO LIKE :style_ref ";
            $bindings[':style_ref'] = "%" . $style_ref . "%";
        }
        if (!empty($date_from) && !empty($date_to)) {
            $sql .= " AND w.INSERT_DATE BETWEEN TO_DATE(:date_from, 'YYYY-MM-DD') AND TO_DATE(:date_to, 'YYYY-MM-DD') + 1 ";
            $bindings[':date_from'] = $date_from;
            $bindings[':date_to'] = $date_to;
        } else {
            // Default condition from your query if no date selected
            $sql .= " AND w.INSERT_DATE >= TRUNC(SYSDATE) - 180 ";
        }

        // Add Order By
        $sql .= " ORDER BY w.SIZE_ORDER ASC, w.COLOR_ORDER ASC";

        $stid = oci_parse($conn, $sql);

        // Bind parameters
        foreach ($bindings as $key => $val) {
            oci_bind_by_name($stid, $key, $bindings[$key]);
        }

        oci_execute($stid);

        // Fetch Data
        while ($row = oci_fetch_assoc($stid)) {
            $rows[] = $row;
        }

        // Process Data for Matrix (Pivot Table)
        // Structure: $matrix[Size][Color] = Quantity
        $matrix = [];
        $unique_sizes = [];
        $unique_colors = [];

        if (!empty($rows)) {
            // Extract Header Info from the first row found
            $headerInfo = [
                'BUYER_NAME'   => $rows[0]['BUYER_NAME'],
                'STYLE_REF_NO' => $rows[0]['STYLE_REF_NO'],
                'PO_BREAK_DOWN_ID' => $rows[0]['PO_BREAK_DOWN_ID'],
                'GARMENT_ITEM' => $rows[0]['GARMENT_ITEM'],
                'JOB_NO_MST'   => $rows[0]['JOB_NO_MST'],
                'COUNTRY'      => $rows[0]['COUNTRY'],
            ];

            foreach ($rows as $item) {
                $s = $item['SIZE_NAME'];
                $c = $item['COLOR_NAME'];
                $q = $item['ORDER_QUANTITY'];

                $unique_sizes[$s] = $item['SIZE_ORDER']; // Store order for sorting if needed
                $unique_colors[$c] = $item['COLOR_ORDER'];

                if (!isset($matrix[$s][$c])) {
                    $matrix[$s][$c] = 0;
                }
                $matrix[$s][$c] += $q;
            }
        }

        oci_free_statement($stid);
        oci_close($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Color Size Breakdown</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
        }

        .search-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .order-sheet {
            background: white;
            padding: 30px;
            border: 1px solid #ddd;
            margin-bottom: 30px;
        }

        .header-table td {
            font-weight: bold;
            color: #555;
            padding: 5px 10px;
        }

        .header-table td span {
            font-weight: normal;
            color: #000;
        }

        /* Matrix Table Styles */
        .matrix-table th,
        .matrix-table td {
            text-align: center;
            vertical-align: middle;
            border: 1px solid #000;
            padding: 5px;
            font-size: 13px;
        }

        .matrix-table thead th {
            background-color: #e9ecef;
        }

        .matrix-table .total-col {
            background-color: #e2e3e5;
            font-weight: bold;
        }

        .matrix-table .total-row {
            background-color: #e2e3e5;
            font-weight: bold;
        }

        /* Print Styles */
        @media print {
            .no-print {
                display: none !important;
            }

            .order-sheet {
                border: none;
                padding: 0;
                margin: 0;
            }

            body {
                background-color: white;
            }
        }
    </style>
</head>

<body>

    <div class="container-fluid mt-4">

        <!-- SEARCH FORM -->
        <div class="search-box no-print">
            <h4 class="mb-3">Search Order Breakdown</h4>
            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Job No</label>
                        <input type="text" name="job_no" class="form-control" placeholder="FAL-..." value="<?php echo $_POST['job_no'] ?? ''; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">PO ID</label>
                        <input type="text" name="po_id" class="form-control" placeholder="PO ID" value="<?php echo $_POST['po_id'] ?? ''; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Style Ref</label>
                        <input type="text" name="style_ref" class="form-control" placeholder="Style Ref" value="<?php echo $_POST['style_ref'] ?? ''; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $_POST['date_from'] ?? ''; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo $_POST['date_to'] ?? ''; ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </div>
            </form>
            <?php if ($error_msg): ?>
                <div class="alert alert-danger mt-3"><?php echo $error_msg; ?></div>
            <?php endif; ?>
        </div>

        <?php if ($search_performed && !empty($matrix)): ?>

            <!-- ORIGINAL ORDER SHEET -->
            <div class="order-sheet" id="originalOrderSheet">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>ORDER QUANTITY SHEET</h3>
                    <button onclick="window.print()" class="btn btn-secondary btn-sm no-print">Print / PDF</button>
                </div>

                <!-- UPPER TABLE (Split View) -->
                <div class="row mb-4">
                    <div class="col-6">
                        <table class="table table-bordered table-sm header-table">
                            <tr>
                                <td>BUYER NAME: <span><?php echo $headerInfo['BUYER_NAME']; ?></span></td>
                            </tr>
                            <tr>
                                <td>STYLE REF: <span><?php echo $headerInfo['STYLE_REF_NO']; ?></span></td>
                            </tr>
                            <tr>
                                <td>PO ID: <span><?php echo $headerInfo['PO_BREAK_DOWN_ID']; ?></span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-6">
                        <table class="table table-bordered table-sm header-table">
                            <tr>
                                <td>ITEM NAME: <span><?php echo $headerInfo['GARMENT_ITEM']; ?></span></td>
                            </tr>
                            <tr>
                                <td>JOB NO: <span><?php echo $headerInfo['JOB_NO_MST']; ?></span></td>
                            </tr>
                            <tr>
                                <td>COUNTRY: <span><?php echo $headerInfo['COUNTRY']; ?></span></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- MIDDLE TABLE (Size-Color Breakdown) -->
                <div class="table-responsive">
                    <table class="table table-sm matrix-table" id="baseTable">
                        <thead>
                            <tr>
                                <th>Size \ Color</th>
                                <?php foreach ($unique_colors as $colorName => $order): ?>
                                    <th><?php echo $colorName; ?></th>
                                <?php endforeach; ?>
                                <th class="total-col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $grandTotal = 0;
                            $colTotals = array_fill_keys(array_keys($unique_colors), 0);

                            foreach ($unique_sizes as $sizeName => $order):
                                $rowTotal = 0;
                            ?>
                                <tr>
                                    <th><?php echo $sizeName; ?></th>
                                    <?php foreach ($unique_colors as $colorName => $cOrder):
                                        $qty = $matrix[$sizeName][$colorName] ?? 0;
                                        $rowTotal += $qty;
                                        $colTotals[$colorName] += $qty;
                                    ?>
                                        <td class="qty-cell" data-val="<?php echo $qty; ?>"><?php echo $qty; ?></td>
                                    <?php endforeach; ?>
                                    <td class="total-col row-total"><?php echo $rowTotal; ?></td>
                                </tr>
                            <?php
                                $grandTotal += $rowTotal;
                            endforeach;
                            ?>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td>GRAND TOTAL</td>
                                <?php foreach ($unique_colors as $colorName => $cOrder): ?>
                                    <td class="col-total"><?php echo $colTotals[$colorName]; ?></td>
                                <?php endforeach; ?>
                                <td class="grand-total-cell"><?php echo $grandTotal; ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- MULTIPLIER SECTION (No Print) -->
            <div class="search-box no-print mt-4">
                <h5>Generate Calculation Sheet</h5>
                <div class="row g-3 align-items-center">
                    <div class="col-auto">
                        <label class="col-form-label">Consumption / Multiplier:</label>
                    </div>
                    <div class="col-auto">
                        <input type="number" id="multiplierInput" step="0.0001" class="form-control" placeholder="Ex: 1.05">
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-success" id="btnCalculate">Generate Table</button>
                    </div>
                </div>
            </div>

            <!-- DYNAMIC CALCULATED TABLE -->
            <div id="dynamicContainer"></div>

        <?php elseif ($search_performed && empty($matrix)): ?>
            <div class="alert alert-warning text-center">No records found for the selected criteria.</div>
        <?php endif; ?>

    </div>

    <!-- JavaScript (jQuery + Logic) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {

            $('#btnCalculate').click(function() {
                var multiplier = parseFloat($('#multiplierInput').val());

                if (isNaN(multiplier) || multiplier <= 0) {
                    alert("Please enter a valid number");
                    return;
                }

                // 1. Clone the entire Order Sheet structure
                var $clonedSheet = $('#originalOrderSheet').clone();
                $clonedSheet.attr('id', 'calculatedSheet_' + Date.now()); // Unique ID
                $clonedSheet.find('h3').text('CALCULATED REQUIREMENT (x' + multiplier + ')');

                // Remove existing print button in clone (optional, or keep it)
                $clonedSheet.find('.btn-secondary').remove();

                var $table = $clonedSheet.find('.matrix-table');
                var colTotals = {}; // Reset col totals logic
                var grandTotal = 0;

                // Initialize column totals accumulator
                $table.find('thead th').each(function(index) {
                    if (index > 0) colTotals[index] = 0; // Skip first col (Size label)
                });

                // 2. Iterate rows and update values
                $table.find('tbody tr').each(function() {
                    var rowTotal = 0;

                    $(this).find('td.qty-cell').each(function(index) {
                        // Get original base integer value stored in data-val attribute
                        var baseVal = parseFloat($(this).attr('data-val'));

                        // Calculate new value
                        var newVal = (baseVal * multiplier);

                        // Format (2 decimal places usually)
                        var displayVal = newVal.toFixed(2);

                        // Update text
                        $(this).text(displayVal);

                        // Add to Row Total
                        rowTotal += newVal;

                        // Add to Col Total (Index + 1 because of TH offset)
                        var colIndex = $(this).index() + 1; // Correction based on structure
                        if (!colTotals[colIndex]) colTotals[colIndex] = 0;
                        colTotals[colIndex] += newVal;
                    });

                    // Update Row Total Cell
                    $(this).find('.row-total').text(rowTotal.toFixed(2));
                    grandTotal += rowTotal;
                });

                // 3. Update Footer (Col Totals)
                $table.find('tfoot tr td.col-total').each(function(index) {
                    // Find the visual index
                    var colIndex = index + 2; // +1 for Size TH, +1 for 0-based
                    // Note: Simplest way is strictly matching index logic used in loop
                    // Let's rely on stored totals
                    var key = index + 1; // logic alignment
                    // actually easier:
                });

                // Re-calculate footer accurately based on DOM position
                var $footerCells = $table.find('tfoot tr td');
                var footerIndex = 0;

                // Skip the "GRAND TOTAL" label cell
                // Iterate only value cells
                $table.find('tfoot tr td.col-total').each(function(i) {
                    // The column index in the body matches i+1 (if we ignore the 'Size' header)
                    // Let's just sum the column vertically from the new body values to be 100% sure
                    var vertSum = 0;
                    var targetColIndex = $(this).index(); // This is the index in the TR

                    $table.find('tbody tr').each(function() {
                        var val = parseFloat($(this).children('td').eq(targetColIndex - 1).text()); // -1 because tbody has th at start
                        if (!isNaN(val)) vertSum += val;
                    });

                    $(this).text(vertSum.toFixed(2));
                });

                // Update Grand Total
                $table.find('.grand-total-cell').text(grandTotal.toFixed(2));

                // 4. Append to container
                $('#dynamicContainer').html(''); // Clear previous or append? User said "down that div new", implying replacement usually.
                $('#dynamicContainer').append('<hr class="no-print">');
                $('#dynamicContainer').append($clonedSheet);

                // Scroll to new table
                $('html, body').animate({
                    scrollTop: $("#dynamicContainer").offset().top
                }, 1000);
            });

        });
    </script>

</body>

</html>