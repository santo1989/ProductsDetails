<?php
class Database
{
    private $conn;

    public function __construct()
    {
        $db_username = "NtgBi";
        $db_password = "NtgbI@2025";
        $db_host = "49.0.39.85:1521/orcl";

        // Connect with schema specification
        $tns = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=49.0.39.85)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=orcl)(SERVER=DEDICATED)))";

        try {
            $this->conn = oci_connect($db_username, $db_password, $tns);
            if (!$this->conn) {
                $e = oci_error();
                throw new Exception("Database Connection Failed: " . $e['message']);
            }

            // Set current schema
            $sql = "ALTER SESSION SET CURRENT_SCHEMA = NFLERPLIVE";
            $stmt = oci_parse($this->conn, $sql);
            oci_execute($stmt);
        } catch (Exception $e) {
            die("Connection Error: " . $e->getMessage());
        }
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function executeQuery($sql, $params = [])
    {
        $stid = oci_parse($this->conn, $sql);

        if (!$stid) {
            $e = oci_error($this->conn);
            throw new Exception("SQL Parse Error: " . $e['message']);
        }

        // Bind parameters with proper Oracle bind variable syntax
        foreach ($params as $key => &$value) {
            // Use colon prefix for bind variables
            $bindKey = ':' . ltrim($key, ':');
            oci_bind_by_name($stid, $bindKey, $value);
        }

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            throw new Exception("SQL Execute Error: " . $e['message']);
        }

        return $stid;
    }

    public function fetchAll($stid)
    {
        $result = [];
        while ($row = oci_fetch_assoc($stid)) {
            $result[] = array_change_key_case($row, CASE_UPPER); // Standardize column names to uppercase
        }
        return $result;
    }

    public function close()
    {
        if ($this->conn) {
            oci_close($this->conn);
        }
    }
}
