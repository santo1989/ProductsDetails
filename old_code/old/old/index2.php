<?php
// index.php
require_once 'db_config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .card-header {
            background-color: #2c3e50;
            color: white;
        }

        .total-cell {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .table-fixed {
            table-layout: fixed;
        }

        .sticky-header {
            position: sticky;
            top: 0;
            background: white;
            z-index: 100;
        }

        .print-table {
            border-collapse: collapse;
            width: 100%;
        }

        .print-table th,
        .print-table td {
            border: 1px solid #dee2e6;
            padding: 8px;
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-search"></i> Search Orders</h5>
            </div>
            <div class="card-body">
                <form id="searchForm" method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">JOB NO MST</label>
                            <input type="text" class="form-control" name="job_no_mst"
                                value="<?= isset($_POST['job_no_mst']) ? htmlspecialchars($_POST['job_no_mst']) : '' ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">PO BREAKDOWN ID</label>
                            <input type="text" class="form-control" name="po_breakdown_id"
                                value="<?= isset($_POST['po_breakdown_id']) ? htmlspecialchars($_POST['po_breakdown_id']) : '' ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">STYLE REF NO</label>
                            <input type="text" class="form-control" name="style_ref_no"
                                value="<?= isset($_POST['style_ref_no']) ? htmlspecialchars($_POST['style_ref_no']) : '' ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">INSERT DATE Range</label>
                            <input type="date" class="form-control" name="date_from"
                                value="<?= isset($_POST['date_from']) ? $_POST['date_from'] : date('Y-m-d', strtotime('-30 days')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="date_to"
                                value="<?= isset($_POST['date_to']) ? $_POST['date_to'] : date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-12 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = new Database();

            // Build WHERE clause
            $where = [];
            $params = [];

            if (!empty($_POST['job_no_mst'])) {
                $where[] = "w.JOB_NO_MST LIKE '%' || :job_no_mst || '%'";
                $params[':job_no_mst'] = $_POST['job_no_mst'];
            }

            if (!empty($_POST['po_breakdown_id'])) {
                $where[] = "w.PO_BREAK_DOWN_ID = :po_breakdown_id";
                $params[':po_breakdown_id'] = $_POST['po_breakdown_id'];
            }

            if (!empty($_POST['style_ref_no'])) {
                $where[] = "dm.STYLE_REF_NO LIKE '%' || :style_ref_no || '%'";
                $params[':style_ref_no'] = $_POST['style_ref_no'];
            }

            if (!empty($_POST['date_from'])) {
                $where[] = "TRUNC(w.INSERT_DATE) >= TO_DATE(:date_from, 'YYYY-MM-DD')";
                $params[':date_from'] = $_POST['date_from'];
            }

            if (!empty($_POST['date_to'])) {
                $where[] = "TRUNC(w.INSERT_DATE) <= TO_DATE(:date_to, 'YYYY-MM-DD')";
                $params[':date_to'] = $_POST['date_to'];
            }

            // Add default filter for last 180 days if no date range specified
            if (empty($_POST['date_from']) && empty($_POST['date_to'])) {
                $where[] = "w.INSERT_DATE >= TRUNC(SYSDATE) - 180";
            }

            $whereClause = !empty($where) ? "AND " . implode(" AND ", $where) : "";

            $sql = "
                SELECT
                    w.PO_BREAK_DOWN_ID,
                    w.JOB_NO_MST,
                    w.JOB_ID,
                    dm.STYLE_REF_NO,
                    b.BUYER_NAME AS BUYER_NAME,
                    gi.ITEM_NAME AS GARMENT_ITEM,
                    ctry.COUNTRY_NAME AS COUNTRY,
                    sz.SIZE_NAME AS SIZE_NAME,
                    clr.COLOR_NAME AS COLOR_NAME,
                    w.ORDER_QUANTITY,
                    w.ORDER_RATE,
                    w.ORDER_TOTAL,
                    w.COLOR_ORDER,
                    w.SIZE_ORDER,
                    w.COUNTRY_SHIP_DATE,
                    w.INSERT_DATE
                FROM NFLERPLIVE.wo_po_color_size_breakdown w
                LEFT JOIN NFLERPLIVE.WO_PO_DETAILS_MASTER dm
                    ON dm.ID = w.JOB_ID AND dm.IS_DELETED = 0
                LEFT JOIN NFLERPLIVE.LIB_BUYER b
                    ON b.ID = dm.BUYER_NAME AND b.IS_DELETED = 0
                LEFT JOIN NFLERPLIVE.LIB_GARMENT_ITEM gi
                    ON gi.ID = w.ITEM_NUMBER_ID AND gi.IS_DELETED = 0
                LEFT JOIN NFLERPLIVE.LIB_COUNTRY ctry
                    ON ctry.ID = w.COUNTRY_ID AND ctry.IS_DELETED = 0
                LEFT JOIN NFLERPLIVE.LIB_SIZE sz
                    ON sz.ID = w.SIZE_NUMBER_ID AND sz.IS_DELETED = 0
                LEFT JOIN NFLERPLIVE.LIB_COLOR clr
                    ON clr.ID = w.COLOR_NUMBER_ID AND clr.IS_DELETED = 0
                WHERE w.JOB_NO_MST LIKE 'FAL-%'
                {$whereClause}
                ORDER BY w.INSERT_DATE DESC
            ";

            try {
                $results = $db->executeQuery($sql, $params);

                if (count($results) > 0) {
                    // Process data for display
                    $headerData = $results[0];
                    $colorSizeData = [];
                    $sizes = [];
                    $colors = [];

                    foreach ($results as $row) {
                        $size = $row['SIZE_NAME'];
                        $color = $row['COLOR_NAME'];
                        $quantity = (int)$row['ORDER_QUANTITY'];

                        if (!in_array($size, $sizes)) $sizes[] = $size;
                        if (!in_array($color, $colors)) $colors[] = $color;

                        if (!isset($colorSizeData[$size])) {
                            $colorSizeData[$size] = [];
                        }
                        $colorSizeData[$size][$color] = $quantity;
                    }

                    // Calculate totals
                    $sizeTotals = [];
                    $colorTotals = [];
                    $grandTotal = 0;

                    foreach ($sizes as $size) {
                        $sizeTotal = 0;
                        foreach ($colors as $color) {
                            $qty = $colorSizeData[$size][$color] ?? 0;
                            $sizeTotal += $qty;
                            $colorTotals[$color] = ($colorTotals[$color] ?? 0) + $qty;
                        }
                        $sizeTotals[$size] = $sizeTotal;
                        $grandTotal += $sizeTotal;
                    }
        ?>

                    <!-- Top Information Table -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Order Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th style="width: 40%">BUYER NAME</th>
                                            <td><?= htmlspecialchars($headerData['BUYER_NAME']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>STYLE REF NO</th>
                                            <td><?= htmlspecialchars($headerData['STYLE_REF_NO']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>PO BREAKDOWN ID</th>
                                            <td><?= htmlspecialchars($headerData['PO_BREAK_DOWN_ID']) ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th style="width: 40%">ITEM NAME</th>
                                            <td><?= htmlspecialchars($headerData['GARMENT_ITEM']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>JOB NO MST</th>
                                            <td><?= htmlspecialchars($headerData['JOB_NO_MST']) ?></td>
                                        </tr>
                                        <tr>
                                            <th>COUNTRY NAME</th>
                                            <td><?= htmlspecialchars($headerData['COUNTRY']) ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Color-Size Breakdown Table -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-grid-3x3-gap"></i> Color-Size Breakdown</h5>
                            <div>
                                <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel()">
                                    <i class="bi bi-file-earmark-excel"></i> Export
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-fixed" id="colorSizeTable">
                                    <thead class="table-light sticky-header">
                                        <tr>
                                            <th style="width: 150px">Size/Color</th>
                                            <?php foreach ($colors as $color): ?>
                                                <th><?= htmlspecialchars($color) ?></th>
                                            <?php endforeach; ?>
                                            <th class="total-cell">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sizes as $size): ?>
                                            <tr>
                                                <td class="fw-bold"><?= htmlspecialchars($size) ?></td>
                                                <?php foreach ($colors as $color): ?>
                                                    <td class="quantity-cell" data-size="<?= htmlspecialchars($size) ?>"
                                                        data-color="<?= htmlspecialchars($color) ?>">
                                                        <?= $colorSizeData[$size][$color] ?? 0 ?>
                                                    </td>
                                                <?php endforeach; ?>
                                                <td class="total-cell"><?= $sizeTotals[$size] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td class="total-cell fw-bold">Total</td>
                                            <?php foreach ($colors as $color): ?>
                                                <td class="total-cell"><?= $colorTotals[$color] ?? 0 ?></td>
                                            <?php endforeach; ?>
                                            <td class="total-cell fw-bold"><?= $grandTotal ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Multiplier Input -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-calculator"></i> Calculate Multiplied Quantities</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text">Multiplier</span>
                                        <input type="number" class="form-control" id="multiplier" value="1" min="0.1" step="0.1">
                                        <button class="btn btn-primary" onclick="calculateMultiplied()">
                                            <i class="bi bi-calculator"></i> Calculate
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="btn-group">
                                        <button class="btn btn-success" onclick="printMultipliedTable()">
                                            <i class="bi bi-printer"></i> Print
                                        </button>
                                        <button class="btn btn-info" onclick="downloadMultipliedTable()">
                                            <i class="bi bi-download"></i> Download
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Dynamic Multiplied Table -->
                            <div id="multipliedTableContainer" class="mt-4"></div>
                        </div>
                    </div>

                    <!-- Hidden table for printing -->
                    <div id="printArea" style="display: none;"></div>

        <?php
                } else {
                    echo '<div class="alert alert-warning">No records found.</div>';
                }
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        }
        ?>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SheetJS for Excel export -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>

    <script>
        function resetForm() {
            document.getElementById('searchForm').reset();
        }

        function calculateMultiplied() {
            const multiplier = parseFloat(document.getElementById('multiplier').value);
            if (isNaN(multiplier) || multiplier <= 0) {
                alert('Please enter a valid multiplier');
                return;
            }

            const originalTable = document.getElementById('colorSizeTable');
            const rows = originalTable.getElementsByTagName('tr');
            let html = `
                <div class="table-responsive">
                    <table class="table table-bordered table-fixed print-table" id="multipliedTable">
                        <thead class="table-success">
                            <tr>
                                <th style="width: 150px">Size/Color</th>
            `;

            // Get headers (colors)
            const headerRow = rows[0];
            const headers = headerRow.getElementsByTagName('th');
            const colorCount = headers.length - 2; // Excluding Size and Total columns

            for (let i = 1; i <= colorCount; i++) {
                html += `<th>${headers[i].textContent}</th>`;
            }
            html += `<th class="total-cell">Total (×${multiplier})</th></tr></thead><tbody>`;

            // Process data rows
            let grandTotal = 0;
            const sizeTotals = [];

            for (let i = 1; i < rows.length - 1; i++) { // Skip header and footer
                const cells = rows[i].getElementsByTagName('td');
                if (cells.length === 0) continue;

                const sizeName = cells[0].textContent;
                let rowTotal = 0;

                html += `<tr><td class="fw-bold">${sizeName}</td>`;

                for (let j = 1; j <= colorCount; j++) {
                    const originalQty = parseInt(cells[j].textContent) || 0;
                    const multipliedQty = originalQty * multiplier;
                    rowTotal += multipliedQty;

                    html += `<td>${Math.round(multipliedQty)}</td>`;
                }

                html += `<td class="total-cell fw-bold">${Math.round(rowTotal)}</td></tr>`;
                sizeTotals.push(rowTotal);
                grandTotal += rowTotal;
            }

            // Add total row
            html += `</tbody><tfoot><tr><td class="total-cell fw-bold">Total</td>`;

            // Calculate column totals
            for (let col = 1; col <= colorCount; col++) {
                let colTotal = 0;
                for (let i = 1; i < rows.length - 1; i++) {
                    const cells = rows[i].getElementsByTagName('td');
                    if (cells[col]) {
                        const originalQty = parseInt(cells[col].textContent) || 0;
                        colTotal += originalQty * multiplier;
                    }
                }
                html += `<td class="total-cell">${Math.round(colTotal)}</td>`;
            }

            html += `<td class="total-cell fw-bold">${Math.round(grandTotal)}</td></tr></tfoot></table></div>`;

            document.getElementById('multipliedTableContainer').innerHTML = `
                <div class="alert alert-success">
                    <i class="bi bi-info-circle"></i> Quantities multiplied by ${multiplier}
                </div>
                ${html}
            `;

            // Store data for printing/downloading
            window.multipliedData = {
                multiplier: multiplier,
                html: html,
                grandTotal: grandTotal
            };
        }

        function printMultipliedTable() {
            if (!window.multipliedData) {
                alert('Please calculate multiplied table first');
                return;
            }

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Order Sheet - Multiplied by ${window.multipliedData.multiplier}</title>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            .print-table { border-collapse: collapse; width: 100%; margin: 20px 0; }
                            .print-table th, .print-table td { border: 1px solid #000; padding: 8px; text-align: center; }
                            .total-cell { background-color: #f0f0f0; font-weight: bold; }
                            h3 { color: #333; }
                            .header-info { margin-bottom: 20px; padding: 10px; background-color: #f8f9fa; }
                        </style>
                    </head>
                    <body>
                        <h3>Order Sheet</h3>
                        <div class="header-info">
                            <strong>Multiplier:</strong> ${window.multipliedData.multiplier}<br>
                            <strong>Generated:</strong> ${new Date().toLocaleString()}
                        </div>
                        ${window.multipliedData.html}
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        function downloadMultipliedTable() {
            if (!window.multipliedData) {
                alert('Please calculate multiplied table first');
                return;
            }

            const table = document.querySelector('#multipliedTable');
            if (!table) {
                alert('Table not found');
                return;
            }

            // Create workbook
            const wb = XLSX.utils.book_new();

            // Convert table to worksheet
            const ws = XLSX.utils.table_to_sheet(table);

            // Add to workbook
            XLSX.utils.book_append_sheet(wb, ws, "Order Sheet");

            // Generate filename
            const filename = `OrderSheet_Multiplied_${window.multipliedData.multiplier}_${new Date().toISOString().slice(0,10)}.xlsx`;

            // Save file
            XLSX.writeFile(wb, filename);
        }

        function exportToExcel() {
            const table = document.getElementById('colorSizeTable');
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(wb, ws, "Color-Size Breakdown");
            XLSX.writeFile(wb, "Color_Size_Breakdown.xlsx");
        }
    </script>
</body>

</html>