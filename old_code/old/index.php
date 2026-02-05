<?php
$conn = oci_connect('NtgBi', 'NtgbI@2025', '192.168.100.29:1521/orcl');
if ($conn) {
    echo "Connected!";
    oci_close($conn);
} else {
    echo "Failed: " . print_r(oci_error(), true);
}
?>