<?php
require_once 'config/db_connection.php';

$db = new Database();
$conn = $db->getConnection();

$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_floor = isset($_GET['floor']) ? $_GET['floor'] : 'All';
$selected_line = isset($_GET['line']) ? $_GET['line'] : 'All';

function getExportData($conn, $date, $floor = null, $line = null)
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
            ROUND(DBMS_RANDOM.VALUE(60, 95), 2) AS EFFICIENCY
        FROM DUAL
        CONNECT BY LEVEL <= 20
    ";

    $stid = oci_parse($conn, $sql);
    oci_execute($stid);

    return $stid;
}

$stid = getExportData($conn, $selected_date, $selected_floor, $selected_line);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=production_data_' . $selected_date . '.csv');

$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Write headers
fputcsv($output, [
    'Date',
    'Buyer',
    'Style',
    'Item',
    'Floor',
    'Line',
    'Total Target',
    'Total Achieve',
    'Hourly Target',
    'Hourly Achieve',
    'Operator',
    'Helper',
    'Machine',
    'SMV',
    'DHU%',
    'Efficiency%'
]);

// Write data
while ($row = oci_fetch_assoc($stid)) {
    fputcsv($output, [
        $row['PRODUCTION_DATE'],
        $row['BUYER_NAME'],
        $row['STYLE'],
        $row['ITEM'],
        $row['FLOOR'],
        $row['LINE'],
        $row['TOTAL_TARGET'],
        $row['TOTAL_ACHIEVE'],
        $row['HOURLY_TARGET'],
        $row['HOURLY_ACHIEVE'],
        $row['OPERATOR'],
        $row['HELPER'],
        $row['TOTAL_MACHINE'],
        $row['SMV'],
        $row['DHU'],
        $row['EFFICIENCY']
    ]);
}

fclose($output);
$db->close();
