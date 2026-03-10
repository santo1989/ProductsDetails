<?php
// db_config.php
class Database {
    private $conn;
    
    public function __construct() {
        $host = '192.168.100.29:1521/orcl';
        $username = 'NtgBi';
        $password = 'NtgbI@2025';
        $service_name = 'orcl';
        
        $tns = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.100.29)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME={$service_name})))";
        
        try {
            $this->conn = oci_connect($username, $password, $tns);
            if (!$this->conn) {
                $e = oci_error();
                throw new Exception("Connection failed: " . $e['message']);
            }
        } catch (Exception $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function executeQuery($sql, $params = []) {
        $stid = oci_parse($this->conn, $sql);
        
        if (!$stid) {
            $e = oci_error($this->conn);
            throw new Exception("SQL Parse Error: " . $e['message']);
        }
        
        // Bind parameters if any
        foreach ($params as $key => $value) {
            oci_bind_by_name($stid, $key, $value);
        }
        
        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            throw new Exception("SQL Execute Error: " . $e['message']);
        }
        
        $results = [];
        while ($row = oci_fetch_assoc($stid)) {
            $results[] = $row;
        }
        
        oci_free_statement($stid);
        return $results;
    }
    
    public function __destruct() {
        if ($this->conn) {
            oci_close($this->conn);
        }
    }
}
?>