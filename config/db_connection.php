<?php
class Database
{
    private $conn;

    public function __construct()
    {
        // Database Credentials
        $db_username = "NtgBi";
        $db_password = "NtgbI@2025";
        $db_host = "49.0.39.85:1521/orcl";

        try {
            // Use the same connection method as your working code
            $this->conn = oci_connect($db_username, $db_password, $db_host);

            if (!$this->conn) {
                $e = oci_error();
                throw new Exception("Database Connection Failed: " . $e['message']);
            }
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

        // Bind parameters
        foreach ($params as $key => $value) {
            oci_bind_by_name($stid, $key, $params[$key]);
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
            $result[] = array_change_key_case($row, CASE_UPPER);
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
