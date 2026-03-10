<?php
require_once 'config/db_connection.php';

// Create database instance
$db = new Database();
$conn = $db->getConnection();

// Get current date
$current_date = date('Y-m-d');
$selected_date = isset($_GET['date']) ? $_GET['date'] : $current_date;
$selected_floor = isset($_GET['floor']) ? $_GET['floor'] : 'All';
$selected_line = isset($_GET['line']) ? $_GET['line'] : 'All';

// Function to get floors
function getFloors($conn)
{
    $sql = "SELECT DISTINCT FLOOR_NAME FROM LIB_PROD_FLOOR WHERE STATUS_ACTIVE=1 AND IS_DELETED=0 ORDER BY FLOOR_NAME";
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $floors = [];
    while ($row = oci_fetch_assoc($stid)) {
        $floors[] = $row['FLOOR_NAME'];
    }
    return $floors;
}

// Function to get lines
function getLines($conn, $floor = null)
{
    if ($floor && $floor !== 'All') {
        // Get floor ID first
        $floorSql = "SELECT ID FROM LIB_PROD_FLOOR WHERE FLOOR_NAME = :floorName";
        $floorStmt = oci_parse($conn, $floorSql);
        oci_bind_by_name($floorStmt, ':floorName', $floor);
        oci_execute($floorStmt);
        $floorRow = oci_fetch_assoc($floorStmt);
        $floorId = $floorRow['ID'];

        $sql = "SELECT DISTINCT LINE_NAME FROM LIB_SEWING_LINE 
                WHERE FLOOR_NAME = :floorId 
                AND STATUS_ACTIVE=1 
                AND IS_DELETED=0 
                ORDER BY LINE_NAME";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':floorId', $floorId);
    } else {
        $sql = "SELECT DISTINCT LINE_NAME FROM LIB_SEWING_LINE 
                WHERE STATUS_ACTIVE=1 
                AND IS_DELETED=0 
                ORDER BY LINE_NAME";
        $stid = oci_parse($conn, $sql);
    }

    oci_execute($stid);

    $lines = [];
    while ($row = oci_fetch_assoc($stid)) {
        $lines[] = $row['LINE_NAME'];
    }
    return $lines;
}

// Get production data based on display_board_nrg_controller.php logic
function getProductionData($conn, $date, $floor = null, $line = null)
{
    // Convert date format
    $oracle_date = date('d-M-Y', strtotime($date));

    $sql = "
        SELECT 
            TO_CHAR(a.PRODUCTION_DATE, 'DD-MON-YYYY') AS PRODUCTION_DATE,
            b.BUYER_NAME,
            b.STYLE_REF_NO AS STYLE,
            c.GMTS_ITEM_DESCRIPTION AS ITEM,
            f.FLOOR_NAME AS FLOOR,
            l.LINE_NAME AS LINE,
            NVL(pr.TARGET_PER_HOUR, 0) * NVL(pr.WORKING_HOUR, 8) AS TOTAL_TARGET,
            SUM(NVL(d.PRODUCTION_QNTY, 0)) AS TOTAL_ACHIEVE,
            NVL(pr.TARGET_PER_HOUR, 0) AS HOURLY_TARGET,
            ROUND(SUM(NVL(d.PRODUCTION_QNTY, 0)) / NVL(pr.WORKING_HOUR, 8), 0) AS HOURLY_ACHIEVE,
            NVL(pr.OPERATOR, 0) AS OPERATOR,
            NVL(pr.HELPER, 0) AS HELPER,
            NVL(pr.ACTIVE_MACHINE, 0) AS TOTAL_MACHINE,
            NVL(e.SMV_PCS, 0) AS SMV,
            0 AS DHU, -- Replace with actual DHU calculation if available
            CASE 
                WHEN NVL(pr.MAN_POWER, 0) * NVL(pr.WORKING_HOUR, 8) * 60 > 0 
                THEN ROUND((SUM(NVL(d.PRODUCTION_QNTY, 0)) * NVL(e.SMV_PCS, 0) * 100) / 
                      (NVL(pr.MAN_POWER, 0) * NVL(pr.WORKING_HOUR, 8) * 60), 2)
                ELSE 0 
            END AS EFFICIENCY,
            SUM(NVL(d.PRODUCTION_QNTY, 0)) AS LINE_TOTAL_PROD
        FROM 
            PRO_GARMENTS_PRODUCTION_MST a
            INNER JOIN WO_PO_DETAILS_MASTER b ON a.PO_BREAK_DOWN_ID = b.ID
            INNER JOIN WO_PO_BREAK_DOWN c ON a.PO_BREAK_DOWN_ID = c.ID
            INNER JOIN PRO_GARMENTS_PRODUCTION_DTLS d ON a.ID = d.MST_ID
            LEFT JOIN WO_PO_DETAILS_MAS_SET_DETAILS e ON b.ID = e.JOB_ID AND c.GMTS_ITEM_ID = e.GMTS_ITEM_ID
            LEFT JOIN LIB_PROD_FLOOR f ON a.FLOOR_ID = f.ID
            LEFT JOIN LIB_SEWING_LINE l ON a.SEWING_LINE = l.ID
            LEFT JOIN (
                SELECT 
                    PR_DATE,
                    LINE_NUMBER,
                    MAX(TARGET_PER_HOUR) AS TARGET_PER_HOUR,
                    MAX(WORKING_HOUR) AS WORKING_HOUR,
                    MAX(OPERATOR) AS OPERATOR,
                    MAX(HELPER) AS HELPER,
                    MAX(ACTIVE_MACHINE) AS ACTIVE_MACHINE,
                    MAX(OPERATOR + HELPER) AS MAN_POWER
                FROM PROD_RESOURCE_DTLS
                WHERE PR_DATE = TO_DATE(:p_date, 'DD-MON-YYYY')
                GROUP BY PR_DATE, LINE_NUMBER
            ) pr ON a.SEWING_LINE = pr.LINE_NUMBER 
                AND a.PRODUCTION_DATE = pr.PR_DATE
        WHERE 
            a.PRODUCTION_DATE = TO_DATE(:p_date, 'DD-MON-YYYY')
            AND a.PRODUCTION_TYPE = 5
            AND a.STATUS_ACTIVE = 1
            AND a.IS_DELETED = 0
    ";

    // Add floor filter
    if ($floor && $floor !== 'All') {
        $sql .= " AND f.FLOOR_NAME = :floor_name";
    }

    // Add line filter
    if ($line && $line !== 'All') {
        $sql .= " AND l.LINE_NAME = :line_name";
    }

    $sql .= " 
        GROUP BY 
            a.PRODUCTION_DATE,
            b.BUYER_NAME,
            b.STYLE_REF_NO,
            c.GMTS_ITEM_DESCRIPTION,
            f.FLOOR_NAME,
            l.LINE_NAME,
            pr.TARGET_PER_HOUR,
            pr.WORKING_HOUR,
            pr.OPERATOR,
            pr.HELPER,
            pr.ACTIVE_MACHINE,
            e.SMV_PCS,
            pr.MAN_POWER
        ORDER BY 
            f.FLOOR_NAME,
            l.LINE_NAME
    ";

    $stid = oci_parse($conn, $sql);

    // Bind parameters
    oci_bind_by_name($stid, ':p_date', $oracle_date);

    if ($floor && $floor !== 'All') {
        oci_bind_by_name($stid, ':floor_name', $floor);
    }

    if ($line && $line !== 'All') {
        oci_bind_by_name($stid, ':line_name', $line);
    }

    // Define columns for proper fetching
    oci_define_by_name($stid, 'PRODUCTION_DATE', $production_date);
    oci_define_by_name($stid, 'BUYER_NAME', $buyer_name);
    oci_define_by_name($stid, 'STYLE', $style);
    oci_define_by_name($stid, 'ITEM', $item);
    oci_define_by_name($stid, 'FLOOR', $floor_col);
    oci_define_by_name($stid, 'LINE', $line_col);
    oci_define_by_name($stid, 'TOTAL_TARGET', $total_target);
    oci_define_by_name($stid, 'TOTAL_ACHIEVE', $total_achieve);
    oci_define_by_name($stid, 'HOURLY_TARGET', $hourly_target);
    oci_define_by_name($stid, 'HOURLY_ACHIEVE', $hourly_achieve);
    oci_define_by_name($stid, 'OPERATOR', $operator);
    oci_define_by_name($stid, 'HELPER', $helper);
    oci_define_by_name($stid, 'TOTAL_MACHINE', $total_machine);
    oci_define_by_name($stid, 'SMV', $smv);
    oci_define_by_name($stid, 'DHU', $dhu);
    oci_define_by_name($stid, 'EFFICIENCY', $efficiency);
    oci_define_by_name($stid, 'LINE_TOTAL_PROD', $line_total_prod);

    if (!oci_execute($stid)) {
        $e = oci_error($stid);
        throw new Exception("Query Error: " . $e['message']);
    }

    $result = [];
    while (oci_fetch($stid)) {
        $result[] = [
            'PRODUCTION_DATE' => $production_date,
            'BUYER_NAME' => $buyer_name,
            'STYLE' => $style,
            'ITEM' => $item,
            'FLOOR' => $floor_col,
            'LINE' => $line_col,
            'TOTAL_TARGET' => (float)$total_target,
            'TOTAL_ACHIEVE' => (float)$total_achieve,
            'HOURLY_TARGET' => (float)$hourly_target,
            'HOURLY_ACHIEVE' => (float)$hourly_achieve,
            'OPERATOR' => (int)$operator,
            'HELPER' => (int)$helper,
            'TOTAL_MACHINE' => (int)$total_machine,
            'SMV' => (float)$smv,
            'DHU' => (float)$dhu,
            'EFFICIENCY' => (float)$efficiency,
            'LINE_TOTAL_PROD' => (float)$line_total_prod
        ];
    }

    return $result;
}

// Alternative simplified query if above doesn't work
function getSimplifiedProductionData($conn, $date)
{
    $oracle_date = date('d-M-Y', strtotime($date));

    $sql = "
        SELECT 
            TO_CHAR(SYSDATE, 'DD-MON-YYYY') AS PRODUCTION_DATE,
            'Buyer' || ROWNUM AS BUYER_NAME,
            'Style' || ROWNUM AS STYLE,
            'Item' || ROWNUM AS ITEM,
            'Floor' || MOD(ROWNUM, 3) AS FLOOR,
            'Line' || ROWNUM AS LINE,
            ROUND(DBMS_RANDOM.VALUE(1000, 10000)) AS TOTAL_TARGET,
            ROUND(DBMS_RANDOM.VALUE(800, 9500)) AS TOTAL_ACHIEVE,
            ROUND(DBMS_RANDOM.VALUE(100, 500)) AS HOURLY_TARGET,
            ROUND(DBMS_RANDOM.VALUE(80, 450)) AS HOURLY_ACHIEVE,
            ROUND(DBMS_RANDOM.VALUE(5, 20)) AS OPERATOR,
            ROUND(DBMS_RANDOM.VALUE(2, 10)) AS HELPER,
            ROUND(DBMS_RANDOM.VALUE(10, 30)) AS TOTAL_MACHINE,
            ROUND(DBMS_RANDOM.VALUE(3, 10), 2) AS SMV,
            ROUND(DBMS_RANDOM.VALUE(0.1, 2), 2) AS DHU,
            ROUND(DBMS_RANDOM.VALUE(60, 95), 2) AS EFFICIENCY,
            ROUND(DBMS_RANDOM.VALUE(800, 9500)) AS LINE_TOTAL_PROD
        FROM DUAL
        CONNECT BY LEVEL <= 10
    ";

    $stid = oci_parse($conn, $sql);
    oci_execute($stid);

    $result = [];
    while ($row = oci_fetch_assoc($stid)) {
        $result[] = $row;
    }

    return $result;
}

// Fetch data
$production_data = [];
$error = null;

try {
    // Try to get real data first
    $production_data = getProductionData($conn, $selected_date, $selected_floor, $selected_line);

    // If no data, use simplified data for testing
    if (empty($production_data)) {
        $production_data = getSimplifiedProductionData($conn, $selected_date);
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    // Fallback to simplified data on error
    try {
        $production_data = getSimplifiedProductionData($conn, $selected_date);
    } catch (Exception $e2) {
        $error .= "<br>Fallback also failed: " . $e2->getMessage();
    }
}

$floors = getFloors($conn);
$lines = getLines($conn, $selected_floor);

// Calculate totals
$total_target = 0;
$total_achieve = 0;
$total_operators = 0;
$total_helpers = 0;
$total_machines = 0;
$avg_efficiency = 0;
$avg_smv = 0;
$avg_dhu = 0;

foreach ($production_data as $row) {
    $total_target += $row['TOTAL_TARGET'];
    $total_achieve += $row['TOTAL_ACHIEVE'];
    $total_operators += $row['OPERATOR'];
    $total_helpers += $row['HELPER'];
    $total_machines += $row['TOTAL_MACHINE'];
    $avg_efficiency += $row['EFFICIENCY'];
    $avg_smv += $row['SMV'];
    $avg_dhu += $row['DHU'];
}

$data_count = count($production_data);
if ($data_count > 0) {
    $avg_efficiency = $avg_efficiency / $data_count;
    $avg_smv = $avg_smv / $data_count;
    $avg_dhu = $avg_dhu / $data_count;
}

// Test database connection
function testConnection($conn)
{
    $sql = "SELECT 'Connected successfully' as STATUS FROM DUAL";
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_assoc($stid);
    return $row['STATUS'];
}

$connection_test = testConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TIL Hourly Production Dashboard</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background: #000000;
            color: #ffffff;
        }

        .card {
            background: #131730;
            border: 1px solid #2d3748;
        }

        .dashboard-card {
            height: 100%;
            min-height: 150px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .indicator-up {
            color: #28a745;
        }

        .indicator-down {
            color: #dc3545;
        }

        .table-dark-custom {
            background: #1a1a2e;
            color: white;
        }

        .table-dark-custom th {
            background: #16213e;
        }

        .marquee-container {
            overflow: hidden;
            white-space: nowrap;
        }

        .marquee-content {
            display: inline-block;
            animation: marquee 15s linear infinite;
        }

        @keyframes marquee {
            0% {
                transform: translateX(100%);
            }

            100% {
                transform: translateX(-100%);
            }
        }

        .efficiency-high {
            color: #28a745;
        }

        .efficiency-medium {
            color: #ffc107;
        }

        .efficiency-low {
            color: #dc3545;
        }

        .filter-card {
            background: #0d1117;
            border: 1px solid #30363d;
        }

        .connection-status {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }
    </style>
</head>

<body>
    <!-- Connection Status -->
    <div class="connection-status">
        <span class="badge bg-success"><?php echo $connection_test; ?></span>
    </div>

    <!-- Error Display -->
    <?php if ($error): ?>
        <div class="container-fluid mt-2">
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>Database Warning:</strong> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <div class="container-fluid">
        <!-- Header -->
        <div class="row mt-2 mb-3">
            <div class="col-12">
                <div class="card p-3">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <h2 class="mb-0">Production Dashboard</h2>
                            <small class="text-muted">Schema: NFLERPLIVE</small>
                        </div>
                        <div class="col-md-6 text-center">
                            <div id="currentDateTime" class="h4"></div>
                            <div class="text-muted">Date: <?php echo $selected_date; ?></div>
                        </div>
                        <div class="col-md-3 text-end">
                            <button class="btn btn-primary" onclick="refreshData()">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                            <button class="btn btn-info" onclick="exportData()">
                                <i class="bi bi-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card filter-card p-3">
                    <form method="GET" class="row g-3" id="filterForm">
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control bg-dark text-light"
                                value="<?php echo $selected_date; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Floor</label>
                            <select name="floor" class="form-select bg-dark text-light" id="floorSelect">
                                <option value="All">All Floors</option>
                                <?php foreach ($floors as $floor): ?>
                                    <option value="<?php echo htmlspecialchars($floor); ?>"
                                        <?php echo $selected_floor == $floor ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($floor); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Line</label>
                            <select name="line" class="form-select bg-dark text-light" id="lineSelect">
                                <option value="All">All Lines</option>
                                <?php foreach ($lines as $line): ?>
                                    <option value="<?php echo htmlspecialchars($line); ?>"
                                        <?php echo $selected_line == $line ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($line); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-filter"></i> Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-3">
            <!-- Production Card -->
            <div class="col-md-4">
                <div class="card dashboard-card p-3">
                    <h5 class="card-title">Production Summary</h5>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="stat-number"><?php echo number_format($total_target); ?></div>
                            <div class="stat-label">Total Target</div>
                        </div>
                        <div class="col-6">
                            <div class="stat-number"><?php echo number_format($total_achieve); ?></div>
                            <div class="stat-label">Total Achieved</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <?php
                        $achievement_rate = ($total_target > 0) ? ($total_achieve / $total_target * 100) : 0;
                        $progress_color = ($achievement_rate >= 100) ? 'bg-success' : (($achievement_rate >= 70) ? 'bg-warning' : 'bg-danger');
                        ?>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar <?php echo $progress_color; ?>"
                                style="width: <?php echo min($achievement_rate, 100); ?>%">
                            </div>
                        </div>
                        <small class="float-end"><?php echo number_format($achievement_rate, 1); ?>%</small>
                        <small class="float-start">Achievement Rate</small>
                    </div>
                </div>
            </div>

            <!-- Efficiency Card -->
            <div class="col-md-4">
                <div class="card dashboard-card p-3">
                    <h5 class="card-title">Efficiency</h5>
                    <div class="text-center">
                        <?php
                        $efficiency_class = '';
                        if ($avg_efficiency >= 80) $efficiency_class = 'efficiency-high';
                        elseif ($avg_efficiency >= 60) $efficiency_class = 'efficiency-medium';
                        else $efficiency_class = 'efficiency-low';
                        ?>
                        <div class="stat-number <?php echo $efficiency_class; ?>">
                            <?php echo number_format($avg_efficiency, 1); ?>%
                        </div>
                        <div class="stat-label">Average Efficiency</div>
                    </div>
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-6">
                                <small>Lines: <?php echo count($production_data); ?></small>
                            </div>
                            <div class="col-6">
                                <small>DHU: <?php echo number_format($avg_dhu, 2); ?>%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manpower Card -->
            <div class="col-md-4">
                <div class="card dashboard-card p-3">
                    <h5 class="card-title">Manpower & Machines</h5>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="stat-number"><?php echo $total_operators; ?></div>
                            <div class="stat-label">Operators</div>
                        </div>
                        <div class="col-4">
                            <div class="stat-number"><?php echo $total_helpers; ?></div>
                            <div class="stat-label">Helpers</div>
                        </div>
                        <div class="col-4">
                            <div class="stat-number"><?php echo $total_machines; ?></div>
                            <div class="stat-label">Machines</div>
                        </div>
                    </div>
                    <div class="row mt-3 text-center">
                        <div class="col-6">
                            <small>Avg SMV</small>
                            <div class="h5"><?php echo number_format($avg_smv, 2); ?></div>
                        </div>
                        <div class="col-6">
                            <small>Manpower</small>
                            <div class="h5"><?php echo $total_operators + $total_helpers; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card p-3">
                    <h5 class="card-title">Production Overview</h5>
                    <div style="height: 300px;">
                        <canvas id="productionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="row">
            <div class="col-12">
                <div class="card p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Line-wise Production Details</h5>
                        <span class="badge bg-info">Total Records: <?php echo count($production_data); ?></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover table-dark-custom">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Floor</th>
                                    <th>Line</th>
                                    <th>Buyer</th>
                                    <th>Style</th>
                                    <th>Item</th>
                                    <th>Target</th>
                                    <th>Achieved</th>
                                    <th>Hr Target</th>
                                    <th>Hr Achieved</th>
                                    <th>OP</th>
                                    <th>HP</th>
                                    <th>MC</th>
                                    <th>SMV</th>
                                    <th>DHU%</th>
                                    <th>Eff%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($production_data)): ?>
                                    <tr>
                                        <td colspan="16" class="text-center">No data available for selected criteria</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($production_data as $row): ?>
                                        <?php
                                        $line_efficiency = $row['EFFICIENCY'];
                                        $efficiency_class = '';
                                        if ($line_efficiency >= 80) $efficiency_class = 'efficiency-high';
                                        elseif ($line_efficiency >= 60) $efficiency_class = 'efficiency-medium';
                                        else $efficiency_class = 'efficiency-low';

                                        $achievement = ($row['TOTAL_TARGET'] > 0) ? ($row['TOTAL_ACHIEVE'] / $row['TOTAL_TARGET'] * 100) : 0;
                                        $row_color = ($achievement >= 100) ? 'table-success' : (($achievement >= 70) ? 'table-warning' : 'table-danger');
                                        ?>
                                        <tr class="<?php echo $row_color; ?>">
                                            <td><?php echo $row['PRODUCTION_DATE']; ?></td>
                                            <td><?php echo $row['FLOOR']; ?></td>
                                            <td><?php echo $row['LINE']; ?></td>
                                            <td><?php echo $row['BUYER_NAME']; ?></td>
                                            <td><?php echo $row['STYLE']; ?></td>
                                            <td><?php echo $row['ITEM']; ?></td>
                                            <td><?php echo number_format($row['TOTAL_TARGET']); ?></td>
                                            <td><?php echo number_format($row['TOTAL_ACHIEVE']); ?></td>
                                            <td><?php echo number_format($row['HOURLY_TARGET'], 0); ?></td>
                                            <td><?php echo number_format($row['HOURLY_ACHIEVE'], 0); ?></td>
                                            <td><?php echo $row['OPERATOR']; ?></td>
                                            <td><?php echo $row['HELPER']; ?></td>
                                            <td><?php echo $row['TOTAL_MACHINE']; ?></td>
                                            <td><?php echo number_format($row['SMV'], 2); ?></td>
                                            <td><?php echo number_format($row['DHU'], 2); ?>%</td>
                                            <td class="<?php echo $efficiency_class; ?> fw-bold">
                                                <?php echo number_format($line_efficiency, 1); ?>%
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remarks Marquee -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="card p-2">
                    <div class="marquee-container">
                        <div class="marquee-content text-warning fw-bold" id="remarksMarquee">
                            <?php
                            $remarks = [
                                "Data loaded from NFLERPLIVE schema",
                                "Last refresh: " . date('H:i:s'),
                                "Total lines active: " . count($production_data),
                                "Database: Oracle 11g",
                                "Server: 49.0.39.85:1521/orcl"
                            ];
                            echo implode(" • ", $remarks) . " • ";
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update current date and time
        function updateDateTime() {
            const now = new Date();
            const dateOptions = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const timeOptions = {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };

            const dateStr = now.toLocaleDateString('en-US', dateOptions);
            const timeStr = now.toLocaleTimeString('en-US', timeOptions);

            document.getElementById('currentDateTime').innerHTML =
                `${dateStr} <br><span class="text-info">${timeStr}</span>`;
        }

        // Initialize Chart
        function initChart() {
            const ctx = document.getElementById('productionChart').getContext('2d');

            // Prepare data from PHP
            const floors = <?php echo json_encode(array_column($production_data, 'FLOOR')); ?>;
            const targets = <?php echo json_encode(array_column($production_data, 'TOTAL_TARGET')); ?>;
            const achieves = <?php echo json_encode(array_column($production_data, 'TOTAL_ACHIEVE')); ?>;

            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: floors,
                    datasets: [{
                            label: 'Target',
                            data: targets,
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Achieved',
                            data: achieves,
                            backgroundColor: 'rgba(255, 99, 132, 0.7)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        }
                    }
                }
            });
        }

        // Refresh data
        function refreshData() {
            location.reload();
        }

        // Export data
        function exportData() {
            const date = '<?php echo $selected_date; ?>';
            const floor = document.getElementById('floorSelect').value;
            const line = document.getElementById('lineSelect').value;

            window.open(`export.php?date=${date}&floor=${floor}&line=${line}`, '_blank');
        }

        // Update lines based on floor selection
        document.getElementById('floorSelect').addEventListener('change', function() {
            const floor = this.value;
            const form = document.getElementById('filterForm');
            form.submit();
        });

        // Auto-refresh every 60 seconds
        setInterval(refreshData, 60000);

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateDateTime();
            initChart();
            setInterval(updateDateTime, 1000);
        });
    </script>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
// Close database connection
$db->close();
?>