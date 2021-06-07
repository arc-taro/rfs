<?php
// DB接続用php

// DB接続情報
define('DB_HOST', '192.168.3.57');

define('DB_USER', "postgres");
define('DB_PASSWD', "postgres");
define('DB_DBNAME', "rfs");

class DB{
  public function init(){
    $this->db = pg_connect("host=".DB_HOST." dbname=".DB_DBNAME." user=".DB_USER." password=".DB_PASSWD);
  }

  public function query($sql){
    $ret = array();
    $result = pg_query($sql);
    if (!$result) {
      return $ret;
    }

    for ($i = 0 ; $i < pg_num_rows($result) ; $i++){
      $rows = pg_fetch_array($result, NULL, PGSQL_ASSOC);
      $ret[$i] = $rows;
    }
    return $ret;
  }

  public function version(){
    $v =  pg_version($this->db);
    return $v['client'];
  }

  public function close(){
    pg_close($this->db);
  }

  public function beginTran(){
    pg_query("BEGIN");
  }

  public function tranCommit(){
    pg_query("COMMIT");
  }

  public function sqlExec($sql){
    $result_flag = pg_query($sql);
    if (!$result_flag) {
      echo "SQLの実行に失敗しました。$sql\n";
      return 1;
    }
    return 0;
  }
}
