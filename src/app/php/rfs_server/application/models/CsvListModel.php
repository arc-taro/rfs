<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 返却は配列
 *
 * @access public
 * @package Model
 */
class CsvListModel extends CI_Model {

    /**
    * コンストラクタ
    */
    public function __construct() {
        parent::__construct();
        $this->DB_rfs = $this->load->database('rfs',TRUE);
        if ($this->DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }
    }

    /**
     * CSV作成リストを全件表示する
     * ZIP化されたもののみ取得する
     */
    public function getCsvList() {
      log_message('info', __METHOD__);
        $sql= <<<EOF
        WITH request AS ( 
            SELECT
                  request_group
                , syurui_kbn
                , nendo
                , zip_dt
                , zip_file_nm 
              FROM
                rfs_t_management_create_csv 
              WHERE
                syurui_kbn = 1 
              GROUP BY
                request_group
                , syurui_kbn
                , nendo
                , zip_dt
                , zip_file_nm
          ) 
          , row_request AS ( 
            SELECT
                  r.request_group
                , c.shisetsu_kbn
                , s.shisetsu_kbn_nm 
              FROM
                rfs_t_management_create_csv c JOIN request r 
                  ON c.request_group = r.request_group JOIN rfs_m_shisetsu_kbn s 
                  ON c.shisetsu_kbn = s.shisetsu_kbn
              ORDER BY c.shisetsu_kbn
          ) 
          , shisetsu_kbn AS ( 
            SELECT
                  rr.request_group
                , json_agg(rr) shisetsu_kbn_json 
              FROM
                row_request rr 
              GROUP BY
                rr.request_group
          ) 
          SELECT
                r.request_group
              , r.syurui_kbn
              , r.nendo
              , ( 
                SELECT
                      TO_CHAR(request_dt, 'YYYY年MM月DD日 HH24時MI分SS秒') 
                  FROM
                    rfs_t_management_create_csv 
                  WHERE
                    request_group = r.request_group 
                  ORDER BY
                    id 
                  LIMIT
                    1
              ) request_dt
              , CASE 
                WHEN r.zip_dt IS NULL 
                  THEN '' 
                ELSE TO_CHAR(r.zip_dt, 'YYYY年MM月DD日 HH24時MI分SS秒') 
                END zip_dt
              , COALESCE(r.zip_file_nm, '') zip_file_nm
              , s.shisetsu_kbn_json 
            FROM
              request r JOIN shisetsu_kbn s 
                ON r.request_group = s.request_group 
            ORDER BY
              --zip_dt DESC, 
              request_group DESC
EOF;

        $query = $this->DB_rfs->query($sql);
        $result = $query->result('array');
        return $result;

    }

    public function getZipData($request_group) {
      log_message('info', __METHOD__);
      $sql= <<<EOF
      SELECT
      * 
  FROM
    rfs_t_management_create_csv 
  WHERE
    request_group = $request_group
    AND syurui_kbn = 1 
  ORDER BY
    id 
  LIMIT
    1;
EOF;

      $query = $this->DB_rfs->query($sql);
      $result = $query->result('array');
      return $result;

  }

}
