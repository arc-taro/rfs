<?php
define('DB_HOST', 'rfs_postgres_server');
define('DB_PORT', '5432');
define('DB_USER', "postgres");
define('DB_PASSWD', "postgres");
define('DB_DBNAME', "imm");

class DB
{
    public function __construct()
    {
        $this->db = pg_connect("host=" . DB_HOST . " port=" . DB_PORT . " dbname=" . DB_DBNAME . " user=" . DB_USER . " password=" . DB_PASSWD);
    }

    public function query($query, $params = null)
    {
        $ret = array();
        if (isset($params) && is_array($params)) {
            $result = pg_query_params($this->db, $query, $params);
        } else {
            $result = pg_query($this->db, $query);
        }
        if (!$result) {
            return $ret;
        }
        for ($i = 0; $i < pg_num_rows($result); $i++) {
            $ret[$i] = pg_fetch_array($result, null, PGSQL_ASSOC);
        }
        return $ret;
    }

    public function non_query($query, $params = null)
    {
        if (isset($params) && is_array($params)) {
            $result = pg_query_params($this->db, $query, $params);
        } else {
            $result = pg_query($this->db, $query);
        }
        if (!$result) {
            return false;
        }
        return true;
    }

    public function version()
    {
        return pg_version($this->db)['client'];
    }

    public function close()
    {
        pg_close($this->db);
        unset($this->db);
    }

    public function beginTransaction()
    {
        pg_query($this->db, "BEGIN");
    }

    public function commit()
    {
        pg_query($this->db, "COMMIT");
    }

    public function rollback()
    {
        pg_query($this->db, "ROLLBACK");
    }

}
