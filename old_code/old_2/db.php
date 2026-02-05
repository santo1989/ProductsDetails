<?php
$host = '192.168.100.29';
$port = '1521';
$service_name = 'orcl';
$username = 'NtgBi';
$password = 'NtgbI@2025';

$tns = "(DESCRIPTION =
    (ADDRESS = (PROTOCOL = TCP)(HOST = $host)(PORT = $port))
    (CONNECT_DATA =
        (SERVICE_NAME = $service_name)
    )
)";

$conn = oci_connect($username, $password, $tns);

if (!$conn) {
    $e = oci_error();
    die("Oracle Connection Failed: " . $e['message']);
}
