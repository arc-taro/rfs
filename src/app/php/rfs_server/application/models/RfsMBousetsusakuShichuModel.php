<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Userのモデル

 * @access public
 * @package Model
 */
class RfsMBousetsusakuShichuModel extends CI_Model {
  protected $DB_rfs;
  // protected $DB_imm;

  public function __construct() {
    parent::__construct();
    // rfs
    $this->DB_rfs = $this->load->database('rfs',TRUE);
    if ($this->DB_rfs->conn_id === FALSE) {
      log_message('debug', 'データベースに接続されていません');
      return;
    }
    // imm
    // $this->DB_imm = $this->load->database('imm',TRUE);
    // if ($this->DB_imm->conn_id === FALSE) {
    //   log_message('debug', '維持管理システムデータベースに接続されていません');
    //   return;
    // }
  }

  public function get_rfs_m_bousetsusaku_shichu($data) {
    $sno = $data['sno'];
    $shisetsu_cd = $data['shisetsu_cd'];

    $sql = <<<SQL
SELECT
 *
FROM
 rfs_m_bousetsusaku_shichu
WHERE
 sno = {$sno} AND
 shisetsu_cd =  {$shisetsu_cd}
SQL;
    // log_message('debug', $sql);
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    // log_message('debug', print_r($result, true));

    return $result;
  }
}
