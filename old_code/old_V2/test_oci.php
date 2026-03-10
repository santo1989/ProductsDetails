<?php
// Hide notices/warnings for clean output
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$username = 'NtgBi';
$password = 'NtgbI@2025';
$connection_string = '192.168.100.29:1521/orcl';

$conn = oci_connect($username, $password, $connection_string);

if ($conn) {
    echo "Connected to Oracle Database successfully!";
    oci_close($conn);
} else {
    $err = oci_error();
    echo "Connection failed: " . htmlentities($err['message']);
}
