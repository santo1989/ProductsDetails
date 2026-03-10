<?php
require_once 'config/db_connection.php';

// Check if OCI8 is installed
if (!function_exists('oci_connect')) {
    die("<h3 style='color:red;'>Error: Oracle OCI8 extension is not installed on this server.</h3>");
}

// Initialize database
$db = new Database();
$conn = $db->getConnection();

// Check connection
if (!$conn) {
    die("<h3 style='color:red;'>Error: Could not connect to Oracle database.</h3>");
}

// Get current date
$current_date = date('Y-m-d');
$selected_date = isset($_GET['date']) ? $_GET['date'] : $current_date;

// Test query to check connection
function testConnection($conn)
{
    $sql = "SELECT SYSDATE as CURRENT_DATE FROM DUAL";
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_assoc($stid);
    return $row;
}

// Get production data
function getProductionData($conn, $date)
{
    // Convert date format for Oracle
    $oracle_date = date('d-M-Y', strtotime($date));

    // Using NFLERPLIVE schema prefix as in your working code
    $sql = "
        SELECT 
            TO_CHAR(p.PRODUCTION_DATE, 'DD-MON-YYYY') AS PRODUCTION_DATE,
            b.BUYER_NAME,
            dm.STYLE_REF_NO,
            c.GMTS_ITEM_DESCRIPTION AS ITEM,
            f.FLOOR_NAME,
            l.LINE_NAME,
            pr.TARGET_PER_HOUR * pr.WORKING_HOUR AS TOTAL_TARGET,
            SUM(pd.PRODUCTION_QNTY) AS TOTAL_ACHIEVE,
            pr.TARGET_PER_HOUR AS HOURLY_TARGET,
            ROUND(SUM(pd.PRODUCTION_QNTY) / pr.WORKING_HOUR, 0) AS HOURLY_ACHIEVE,
            pr.OPERATOR,
            pr.HELPER,
            pr.ACTIVE_MACHINE AS TOTAL_MACHINE,
            w.SMV_PCS AS SMV,
            0.5 AS DHU,
            CASE 
                WHEN pr.OPERATOR + pr.HELPER > 0 
                THEN ROUND((SUM(pd.PRODUCTION_QNTY) * w.SMV_PCS * 100) / 
                      ((pr.OPERATOR + pr.HELPER) * pr.WORKING_HOUR * 60), 2)
                ELSE 0 
            END AS EFFICIENCY
        FROM 
            NFLERPLIVE.PRO_GARMENTS_PRODUCTION_MST p
            JOIN NFLERPLIVE.PRO_GARMENTS_PRODUCTION_DTLS pd ON p.ID = pd.MST_ID
            JOIN NFLERPLIVE.WO_PO_BREAK_DOWN c ON p.PO_BREAK_DOWN_ID = c.ID
            JOIN NFLERPLIVE.WO_PO_DETAILS_MASTER dm ON c.JOB_ID = dm.ID
            JOIN NFLERPLIVE.LIB_BUYER b ON dm.BUYER_NAME = b.ID
            JOIN NFLERPLIVE.LIB_PROD_FLOOR f ON p.FLOOR_ID = f.ID
            JOIN NFLERPLIVE.LIB_SEWING_LINE l ON p.SEWING_LINE = l.ID
            LEFT JOIN NFLERPLIVE.PROD_RESOURCE_DTLS pr ON p.SEWING_LINE = pr.LINE_NUMBER 
                AND p.PRODUCTION_DATE = pr.PR_DATE
            LEFT JOIN NFLERPLIVE.WO_PO_DETAILS_MAS_SET_DETAILS w ON dm.ID = w.JOB_ID 
                AND c.GMTS_ITEM_ID = w.GMTS_ITEM_ID
        WHERE 
            p.PRODUCTION_DATE = TO_DATE(:prod_date, 'DD-MON-YYYY')
            AND p.PRODUCTION_TYPE = 5
            AND p.IS_DELETED = 0
            AND p.STATUS_ACTIVE = 1
        GROUP BY 
            p.PRODUCTION_DATE,
            b.BUYER_NAME,
            dm.STYLE_REF_NO,
            c.GMTS_ITEM_DESCRIPTION,
            f.FLOOR_NAME,
            l.LINE_NAME,
            pr.TARGET_PER_HOUR,
            pr.WORKING_HOUR,
            pr.OPERATOR,
            pr.HELPER,
            pr.ACTIVE_MACHINE,
            w.SMV_PCS
        ORDER BY 
            f.FLOOR_NAME,
            l.LINE_NAME
    ";

    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ':prod_date', $oracle_date);

    if (!oci_execute($stid)) {
        $e = oci_error($stid);
        throw new Exception("Query Error: " . $e['message']);
    }

    $result = [];
    while ($row = oci_fetch_assoc($stid)) {
        $result[] = array_change_key_case($row, CASE_UPPER);
    }

    return $result;
}

// Get floors
function getFloors($conn)
{
    $sql = "SELECT DISTINCT FLOOR_NAME FROM NFLERPLIVE.LIB_PROD_FLOOR 
            WHERE STATUS_ACTIVE = 1 AND IS_DELETED = 0 
            ORDER BY FLOOR_NAME";

    $stid = oci_parse($conn, $sql);
    oci_execute($stid);

    $floors = [];
    while ($row = oci_fetch_assoc($stid)) {
        $floors[] = $row['FLOOR_NAME'];
    }

    return $floors;
}

// Get lines
function getLines($conn)
{
    $sql = "SELECT DISTINCT LINE_NAME FROM NFLERPLIVE.LIB_SEWING_LINE 
            WHERE STATUS_ACTIVE = 1 AND IS_DELETED = 0 
            ORDER BY LINE_NAME";

    $stid = oci_parse($conn, $sql);
    oci_execute($stid);

    $lines = [];
    while ($row = oci_fetch_assoc($stid)) {
        $lines[] = $row['LINE_NAME'];
    }

    return $lines;
}

// Test connection first
$connection_test = testConnection($conn);
$production_data = [];
$error = null;

try {
    $production_data = getProductionData($conn, $selected_date);
} catch (Exception $e) {
    $error = $e->getMessage();
}

$floors = getFloors($conn);
$lines = getLines($conn);

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
    $total_target += (float)($row['TOTAL_TARGET'] ?? 0);
    $total_achieve += (float)($row['TOTAL_ACHIEVE'] ?? 0);
    $total_operators += (int)($row['OPERATOR'] ?? 0);
    $total_helpers += (int)($row['HELPER'] ?? 0);
    $total_machines += (int)($row['TOTAL_MACHINE'] ?? 0);
    $avg_efficiency += (float)($row['EFFICIENCY'] ?? 0);
    $avg_smv += (float)($row['SMV'] ?? 0);
    $avg_dhu += (float)($row['DHU'] ?? 0);
}

$data_count = count($production_data);
if ($data_count > 0) {
    $avg_efficiency = $avg_efficiency / $data_count;
    $avg_smv = $avg_smv / $data_count;
    $avg_dhu = $avg_dhu / $data_count;
}
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

        .table-dark-custom {
            background: #1a1a2e;
            color: white;
        }

        .table-dark-custom th {
            background: #16213e;
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
        <span class="badge bg-success">Connected: <?php echo $connection_test['CURRENT_DATE']; ?></span>
    </div>

    <?php if ($error): ?>
        <div class="container-fluid mt-2">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Database Error:</strong> <?php echo htmlspecialchars($error); ?>
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
                        <div class="col-md-4">
                            <h2 class="mb-0">Production Dashboard</h2>
                            <small class="text-muted">Oracle Database: NFLERPLIVE</small>
                        </div>
                        <div class="col-md-4 text-center">
                            <div id="currentDateTime" class="h4"></div>
                        </div>
                        <div class="col-md-4 text-end">
                            <form method="GET" class="d-inline">
                                <div class="input-group">
                                    <input type="date" name="date" class="form-control bg-dark text-light"
                                        value="<?php echo $selected_date; ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-arrow-clockwise"></i> Load
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card dashboard-card p-3">
                    <h5 class="card-title">Production</h5>
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
                    </div>
                </div>
            </div>

            <div class="col-md-3">
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
                </div>
            </div>

            <div class="col-md-3">
                <div class="card dashboard-card p-3">
                    <h5 class="card-title">Manpower</h5>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="stat-number"><?php echo $total_operators; ?></div>
                            <div class="stat-label">Operators</div>
                        </div>
                        <div class="col-6">
                            <div class="stat-number"><?php echo $total_helpers; ?></div>
                            <div class="stat-label">Helpers</div>
                        </div>
                    </div>
                    <div class="row mt-3 text-center">
                        <div class="col-6">
                            <small>Total</small>
                            <div class="h5"><?php echo $total_operators + $total_helpers; ?></div>
                        </div>
                        <div class="col-6">
                            <small>Machines</small>
                            <div class="h5"><?php echo $total_machines; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card dashboard-card p-3">
                    <h5 class="card-title">Metrics</h5>
                    <div class="row text-center">
                        <div class="col-6">
                            <small>Avg SMV</small>
                            <div class="h4"><?php echo number_format($avg_smv, 2); ?></div>
                        </div>
                        <div class="col-6">
                            <small>DHU %</small>
                            <div class="h4"><?php echo number_format($avg_dhu, 2); ?>%</div>
                        </div>
                    </div>
                    <div class="row mt-3 text-center">
                        <div class="col-12">
                            <small>Total Lines: <?php echo count($production_data); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="row">
            <div class="col-12">
                <div class="card p-3">
                    <h5 class="card-title">Production Details for <?php echo $selected_date; ?></h5>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover table-dark-custom">
                            <thead>
                                <tr>
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
                                        <td colspan="15" class="text-center">No production data found for <?php echo $selected_date; ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($production_data as $row): ?>
                                        <?php
                                        $line_efficiency = (float)($row['EFFICIENCY'] ?? 0);
                                        $efficiency_class = '';
                                        if ($line_efficiency >= 80) $efficiency_class = 'efficiency-high';
                                        elseif ($line_efficiency >= 60) $efficiency_class = 'efficiency-medium';
                                        else $efficiency_class = 'efficiency-low';

                                        $achievement = ((float)($row['TOTAL_TARGET'] ?? 0) > 0) ?
                                            ((float)($row['TOTAL_ACHIEVE'] ?? 0) / (float)($row['TOTAL_TARGET'] ?? 0) * 100) : 0;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['FLOOR_NAME'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['LINE_NAME'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['BUYER_NAME'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['STYLE_REF_NO'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['ITEM'] ?? ''); ?></td>
                                            <td><?php echo number_format((float)($row['TOTAL_TARGET'] ?? 0)); ?></td>
                                            <td><?php echo number_format((float)($row['TOTAL_ACHIEVE'] ?? 0)); ?></td>
                                            <td><?php echo number_format((float)($row['HOURLY_TARGET'] ?? 0)); ?></td>
                                            <td><?php echo number_format((float)($row['HOURLY_ACHIEVE'] ?? 0)); ?></td>
                                            <td><?php echo $row['OPERATOR'] ?? 0; ?></td>
                                            <td><?php echo $row['HELPER'] ?? 0; ?></td>
                                            <td><?php echo $row['TOTAL_MACHINE'] ?? 0; ?></td>
                                            <td><?php echo number_format((float)($row['SMV'] ?? 0), 2); ?></td>
                                            <td><?php echo number_format((float)($row['DHU'] ?? 0), 2); ?>%</td>
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
                `${dateStr} <br><small>${timeStr}</small>`;
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateDateTime();
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