<?php
header('Content-Type: application/json');
require 'db.php';

$sql = "
SELECT
    PROD_DATE                AS DATE_,
    BUYER_NAME,
    STYLE_REF                AS STYLE,
    ITEM_NAME                AS ITEM,
    FLOOR_NAME               AS FLOOR,
    LINE_NO                  AS LINE,
    TOTAL_TARGET,
    TOTAL_ACHIEVE,
    HOURLY_TARGET,
    HOURLY_ACHIEVE,
    OPERATOR,
    HELPER,
    TOTAL_MACHINE,
    SMV,
    DHU_PERCENT,
    EFFICIENCY_PERCENT,
    LINE_TOTAL_PRODUCTION,
    LINE_EFFICIENCY
FROM PRODUCTION_DASHBOARD_VIEW
ORDER BY FLOOR_NAME, LINE_NO
";

$stid = oci_parse($conn, $sql);
oci_execute($stid);

$data = [];

while ($row = oci_fetch_assoc($stid)) {
    $data[] = [
        'date'        => $row['DATE_'],
        'buyer'       => $row['BUYER_NAME'],
        'style'       => $row['STYLE'],
        'item'        => $row['ITEM'],
        'floor'       => $row['FLOOR'],
        'line'        => $row['LINE'],
        'total_target' => (int)$row['TOTAL_TARGET'],
        'total_achive' => (int)$row['TOTAL_ACHIEVE'],
        'hourly_target' => (int)$row['HOURLY_TARGET'],
        'hourly_achive' => (int)$row['HOURLY_ACHIEVE'],
        'operator'    => (int)$row['OPERATOR'],
        'helper'      => (int)$row['HELPER'],
        'machine'     => (int)$row['TOTAL_MACHINE'],
        'smv'         => (float)$row['SMV'],
        'dhu'         => (float)$row['DHU_PERCENT'],
        'eff'         => (float)$row['EFFICIENCY_PERCENT'],
        'line_prod'   => (int)$row['LINE_TOTAL_PRODUCTION'],
        'line_eff'    => (float)$row['LINE_EFFICIENCY']
    ];
}

echo json_encode($data);
