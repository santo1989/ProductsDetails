<?php
// =================================================================================
// 1. DATABASE CONNECTION & LOGIC
// =================================================================================

// Database Credentials
$db_username = "NtgBi";
$db_password = "NtgbI@2025";
$db_host     = "49.0.39.85:1521/orcl";

// Initialize variables
$conn = null;
$rows = [];
$headerInfo = null;
$error_msg = "";
$search_performed = false;

// CHECK IF OCI8 IS INSTALLED
$oci_available = function_exists('oci_connect');

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['search'])) {
    $search_performed = true;

    if ($oci_available) {
        // --- REAL DATABASE CONNECTION ---
        $conn = @oci_connect($db_username, $db_password, $db_host);

        if (!$conn) {
            $e = oci_error();
            $error_msg = "Connection failed: " . $e['message'];
        } else {
            // inputs
            $job_no     = $_POST['job_no'] ?? '';
            $po_id      = $_POST['po_id'] ?? '';
            $style_ref  = $_POST['style_ref'] ?? '';
            $date_from  = $_POST['date_from'] ?? '';
            $date_to    = $_POST['date_to'] ?? '';

            // Base SQL
            $sql = "SELECT
                        w.PO_BREAK_DOWN_ID,
                        w.JOB_NO_MST,
                        dm.STYLE_REF_NO,
                        b.BUYER_NAME               AS BUYER_NAME,
                        gi.ITEM_NAME               AS GARMENT_ITEM,
                        ctry.COUNTRY_NAME          AS COUNTRY,
                        sz.SIZE_NAME               AS SIZE_NAME,
                        clr.COLOR_NAME             AS COLOR_NAME,
                        w.ORDER_QUANTITY,
                        w.COLOR_ORDER,
                        w.SIZE_ORDER
                    FROM NFLERPLIVE.wo_po_color_size_breakdown w
                    LEFT JOIN NFLERPLIVE.WO_PO_DETAILS_MASTER dm ON dm.ID = w.JOB_ID AND dm.IS_DELETED = 0
                    LEFT JOIN NFLERPLIVE.LIB_BUYER b ON b.ID = dm.BUYER_NAME AND b.IS_DELETED = 0
                    LEFT JOIN NFLERPLIVE.LIB_GARMENT_ITEM gi ON gi.ID = w.ITEM_NUMBER_ID AND gi.IS_DELETED = 0
                    LEFT JOIN NFLERPLIVE.LIB_COUNTRY ctry ON ctry.ID = w.COUNTRY_ID AND ctry.IS_DELETED = 0
                    LEFT JOIN NFLERPLIVE.LIB_SIZE sz ON sz.ID = w.SIZE_NUMBER_ID AND sz.IS_DELETED = 0
                    LEFT JOIN NFLERPLIVE.LIB_COLOR clr ON clr.ID = w.COLOR_NUMBER_ID AND clr.IS_DELETED = 0
                    WHERE w.IS_DELETED = 0 ";

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
                $sql .= " AND w.INSERT_DATE >= TRUNC(SYSDATE) - 180 ";
            }

            $sql .= " ORDER BY w.SIZE_ORDER ASC, w.COLOR_ORDER ASC";

            $stid = oci_parse($conn, $sql);
            foreach ($bindings as $key => $val) {
                oci_bind_by_name($stid, $key, $bindings[$key]);
            }
            oci_execute($stid);

            while ($row = oci_fetch_assoc($stid)) {
                $rows[] = $row;
            }
            oci_free_statement($stid);
            oci_close($conn);
        }
    } else {
        // --- DUMMY DATA FOR TESTING (Because OCI8 is missing) ---
        $error_msg = "<strong>Notice:</strong> Oracle Driver not installed. Showing DUMMY data for testing.";

        // Generate some fake rows
        $sizes = ['S', 'M', 'L', 'XL', 'XXL'];
        $colors = ['BLACK', 'NAVY', 'RED', 'WHITE'];

        foreach ($sizes as $sKey => $s) {
            foreach ($colors as $cKey => $c) {
                $rows[] = [
                    'PO_BREAK_DOWN_ID' => 'PO-998877',
                    'JOB_NO_MST' => 'FAL-2023-DEMO',
                    'STYLE_REF_NO' => 'ST-554433',
                    'BUYER_NAME' => 'H&M (DEMO)',
                    'GARMENT_ITEM' => 'T-SHIRT',
                    'COUNTRY' => 'BANGLADESH',
                    'SIZE_NAME' => $s,
                    'COLOR_NAME' => $c,
                    'ORDER_QUANTITY' => rand(100, 5000),
                    'SIZE_ORDER' => $sKey,
                    'COLOR_ORDER' => $cKey
                ];
            }
        }
    }

    // --- PROCESS ROWS INTO MATRIX (Common Logic) ---
    $matrix = [];
    $unique_sizes = [];
    $unique_colors = [];

    if (!empty($rows)) {
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

            $unique_sizes[$s] = $item['SIZE_ORDER'];
            $unique_colors[$c] = $item['COLOR_ORDER'];

            if (!isset($matrix[$s][$c])) {
                $matrix[$s][$c] = 0;
            }
            $matrix[$s][$c] += $q;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Color Size Breakdown</title>
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

        .matrix-table .total-col,
        .matrix-table .total-row {
            background-color: #e2e3e5;
            font-weight: bold;
        }

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
                    <div class="col-md-2"><label class="form-label">Job No</label><input type="text" name="job_no" class="form-control" placeholder="FAL-..." value="<?php echo $_POST['job_no'] ?? ''; ?>"></div>
                    <div class="col-md-2"><label class="form-label">PO ID</label><input type="text" name="po_id" class="form-control" placeholder="PO ID" value="<?php echo $_POST['po_id'] ?? ''; ?>"></div>
                    <div class="col-md-2"><label class="form-label">Style Ref</label><input type="text" name="style_ref" class="form-control" placeholder="Style Ref" value="<?php echo $_POST['style_ref'] ?? ''; ?>"></div>
                    <div class="col-md-2"><label class="form-label">Date From</label><input type="date" name="date_from" class="form-control" value="<?php echo $_POST['date_from'] ?? ''; ?>"></div>
                    <div class="col-md-2"><label class="form-label">Date To</label><input type="date" name="date_to" class="form-control" value="<?php echo $_POST['date_to'] ?? ''; ?>"></div>
                    <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100">Search / Test</button></div>
                    <!--reset button-->
                    <div class="col-md-2 d-flex align-items-end"><button type="reset" class="btn btn-secondary w-100">Reset</button></div>
                </div>
            </form>
            <?php if ($error_msg): ?>
                <div class="alert alert-warning mt-3"><?php echo $error_msg; ?></div>
            <?php endif; ?>
        </div>

        <?php if ($search_performed && !empty($matrix)): ?>

            <!-- ORIGINAL ORDER SHEET -->
            <div class="order-sheet" id="originalOrderSheet">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="text-center">Orginal ORDER QUANTITY SHEET</h3>
                </div>

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

            <!-- CALCULATOR -->
            <div class="search-box no-print mt-4">
                <h5 class="text-center">Generated ORDER QUANTITY SHEET</h5>
                <div class="row g-3 align-items-center">
                    <div class="col-auto"><label class="col-form-label">Multiplier:</label></div>
                    <div class="col-auto"><input type="number" id="multiplierInput" step="0.0001" class="form-control" placeholder="Ex: 1.05"></div>
                    <div class="col-auto"><button type="button" class="btn btn-success" id="btnCalculate">Generate Table</button></div>
                </div>
                <button class="btn btn-secondary mt-3" onclick="window.print();">Print Page</button>
            </div>

            <div id="dynamicContainer"></div>

        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#btnCalculate').click(function() {
                var multiplier = parseFloat($('#multiplierInput').val());
                if (isNaN(multiplier) || multiplier <= 0) {
                    alert("Enter valid number");
                    return;
                }

                var $clonedSheet = $('#originalOrderSheet').clone();
                $clonedSheet.attr('id', 'calculatedSheet_' + Date.now());
                $clonedSheet.find('h3').text('CALCULATED REQUIREMENT (x' + multiplier + ')');

                var $table = $clonedSheet.find('.matrix-table');
                var colTotals = {};
                var grandTotal = 0;

                $table.find('thead th').each(function(index) {
                    if (index > 0) colTotals[index] = 0;
                });

                $table.find('tbody tr').each(function() {
                    var rowTotal = 0;
                    $(this).find('td.qty-cell').each(function() {
                        var baseVal = parseFloat($(this).attr('data-val'));
                        var newVal = (baseVal * multiplier);
                        $(this).text(Math.ceil(newVal));
                        rowTotal += newVal;
                    });
                    //Showing rounded celi value in total column
                    $(this).find('.row-total').text(Math.ceil(rowTotal));
                    // Accumulate column totals
                    $(this).find('td').each(function(index) {
                        if (index > 0) {
                            var val = parseFloat($(this).text());
                            if (!isNaN(val)) colTotals[index] += val;
                        }
                    });
                    grandTotal += rowTotal;
                });

                // Recalculate Column Totals by summing vertical cells
                $table.find('tfoot tr td.col-total').each(function() {
                    var colIndex = $(this).index(); // Visual index in tfoot
                    var vertSum = 0;
                    $table.find('tbody tr').each(function() {
                        // Find corresponding TD in tbody. Note: tbody has <th> at index 0.
                        // tfoot has <td> at index 0. So index matches perfectly if we traverse children.
                        // But wait: tbody row: <th>Size</th> <td>Val</td> ...
                        // tfoot row: <td>Grand</td> <td>Val</td> ...
                        // So index X in tfoot corresponds to index X in tbody row.
                        var val = parseFloat($(this).children().eq(colIndex).text());
                        if (!isNaN(val)) vertSum += val;
                    });
                    $(this).text(Math.ceil(vertSum));
                });

                $table.find('.grand-total-cell').text(Math.ceil(grandTotal));
                $('#dynamicContainer').html('').append('<hr class="no-print">').append($clonedSheet);
                $('html, body').animate({
                    scrollTop: $("#dynamicContainer").offset().top
                }, 1000);
            });
        });

        //printing script for pdf
        function printDiv(divName) {
            var printContents = document.getElementById(divName).innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }

        //reset button script
        document.querySelector('button[type="reset"]').addEventListener('click', function() {
            window.location.href = window.location.pathname;
        });
    </script>
</body>

</html>