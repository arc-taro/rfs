<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Userのモデル

 * @access public
 * @package Model
 */
class UserModel extends CI_Model {
  // protected $DB_rfs;
  protected $DB_imm;

  public function __construct() {
    parent::__construct();
    // rfs
    // $this->DB_rfs = $this->load->database('rfs',TRUE);
    // if ($this->DB_rfs->conn_id === FALSE) {
    //   log_message('debug', 'データベースに接続されていません');
    //   return;
    // }
    // imm
    $this->DB_imm = $this->load->database('imm',TRUE);
    if ($this->DB_imm->conn_id === FALSE) {
      log_message('debug', '維持管理システムデータベースに接続されていません');
      return;
    }
  }

  public function userLogin($data) {
    // log_message('debug', print_r($data, true));
    $user_id = $data['user_id'];
    $password = $data['password'];

    $sql = <<<SQL
SELECT
 ac_m_account.account_cd,
 ac_m_account.user_id,
 ac_m_account.password ,
 ac_m_busyo.busyo_cd ,
 ac_m_busyo.dogen_cd ,
 ac_m_busyo.syucchoujo_cd ,
 ac_m_busyo.syozoku_cd ,
 ac_m_busyo.syozoku_sub_cd ,
 ac_m_syozoku.syozoku_mei
FROM
 ac_m_account
 NATURAL JOIN ac_m_busyo
 NATURAL JOIN ac_m_syozoku
where
 user_id = '{$user_id}'
 AND password = '{$password}'
SQL;
    // log_message('debug', $sql);
    $query = $this->DB_imm->query($sql);
    $result = $query->result('array');
    if (!empty($result)) {
      // log_message('debug', print_r($result, true));
      // busyo_mei
      $busyo_mei = $this->getBusyoMei($result[0]['busyo_cd']);
      $result[0]['busyo_mei'] = $busyo_mei[0]['busyo_mei'];
      // dogen_mei
      $dogen_mei = $this->getDogenMei($result[0]['dogen_cd']);
      $result[0]['dogen_mei'] = $dogen_mei[0]['dogen_mei'];
      // syucchoujo_mei
      $syucchoujo_mei = $this->getSyucchoujoMei($result[0]['dogen_cd'], $result[0]['syucchoujo_cd']);
      $result[0]['syucchoujo_mei'] = $syucchoujo_mei[0]['syucchoujo_mei'];
    }
    return $result;
  }

  private function getBusyoMei($busyo_cd) {
    // busyo_mei
    $sql = <<<SQL
SELECT
 busyo_mei
FROM
 ac_m_busyo
where
 busyo_cd = '{$busyo_cd }'
SQL;

    $query = $this->DB_imm->query($sql);
    return $query->result('array');
  }

  private function getDogenMei($dogen_cd) {
    // dogen_mei
    $sql = <<<SQL
SELECT
 dogen_mei
FROM
 m_dogen
where
 dogen_cd = '{$dogen_cd }'
SQL;

    $query = $this->DB_imm->query($sql);
    return $query->result('array');
  }

  private function getSyucchoujoMei($dogen_cd, $syucchoujo_cd) {
    // syucchoujo_mei
    $sql = <<<SQL
SELECT
 syucchoujo_mei
FROM
 m_syucchoujo
where
 dogen_cd = '{$dogen_cd }'
 AND syucchoujo_cd = '{$syucchoujo_cd }'
SQL;

    $query = $this->DB_imm->query($sql);
    return $query->result('array');
  }

  // pt_m_patrolin
  public function userPatrolin($data) {
    $syucchoujo_cd = $data['syucchoujo_cd'];
    $busyo_cd = $data['busyo_cd'];
    $sql = <<<SQL
SELECT
 *
FROM
 pt_m_patrolin
where
 syucchoujo_cd = '{$syucchoujo_cd }'
 AND busyo_cd = '{$busyo_cd }'
SQL;
    // log_message('debug', $sql);
    $query = $this->DB_imm->query($sql);
    $result = $query->result('array');
    // log_message('debug', print_r($result, true));
   
    return $result;
  }
}
