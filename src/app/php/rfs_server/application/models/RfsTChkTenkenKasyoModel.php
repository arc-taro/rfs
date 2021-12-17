<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Userのモデル

 * @access public
 * @package Model
 */
class RfsTChkTenkenKasyoModel extends CI_Model {
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

  public function get_rfs_t_chk_tenken_kasyo($data) {
    // $chk_mng_no = $data['chk_mng_no'];
    // $rireki_no = $data['rireki_no'];
    $rfsTChkHuzokubutsuDatas = $data["rfsTChkHuzokubutsuData"];
    $where = '';
    foreach ($rfsTChkHuzokubutsuDatas as $rfsTChkHuzokubutsuData) {
      $where .= '(chk_mng_no=' . $rfsTChkHuzokubutsuData["chk_mng_no"] . ' AND rireki_no=' . $rfsTChkHuzokubutsuData["rireki_no"] . ') OR ';
    }
    if(strlen($where) != 0) {
      $where = substr($where, 0, strlen($where) - 4);
    }
    else {
      return [];
    }
    // log_message('debug', $where);

    $sql = <<<SQL
SELECT
 *
FROM
 rfs_t_chk_tenken_kasyo
WHERE
 {$where}
SQL;

    // log_message('debug', $sql);
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    // log_message('debug', print_r($result, true));
    return $result;
  }

  
  public function set_rfs_t_chk_tenken_kasyo($data) {

//log_message("debug","------------------data-------------->");
//log_message("debug",print_r($data,true));

    $rfsChkTenkenKasyhoData = $data['rfsChkTenkenKasyoData'];

    $chk_mng_no = $rfsChkTenkenKasyhoData['chk_mng_no'];
    $rireki_no = $rfsChkTenkenKasyhoData['rireki_no'];
    $buzai_cd = $rfsChkTenkenKasyhoData['buzai_cd'];
    $buzai_detail_cd = $rfsChkTenkenKasyhoData['buzai_detail_cd'];
    $tenken_kasyo_cd = $rfsChkTenkenKasyhoData['tenken_kasyo_cd'];
    $taisyou_umu = $rfsChkTenkenKasyhoData['taisyou_umu'];
    $check_status = $rfsChkTenkenKasyhoData['check_status'];
    $sonsyou_naiyou_cd = $rfsChkTenkenKasyhoData['sonsyou_naiyou_cd'];
    $check_judge = $rfsChkTenkenKasyhoData['check_judge'];
    $measures_judge = $rfsChkTenkenKasyhoData['measures_judge'];
	$measures_policy='null';
	if ($rfsChkTenkenKasyhoData['measures_policy']) {
 		$measures_policy=$this->DB_rfs->escape($rfsChkTenkenKasyhoData['measures_policy']);
    }
	$measures_dt='null';
	if ($rfsChkTenkenKasyhoData['measures_dt']) {
 		$measures_dt=$this->DB_rfs->escape($rfsChkTenkenKasyhoData['measures_dt']);
    }
	$check_bikou='null';
	if ($rfsChkTenkenKasyhoData['check_bikou']) {
 		$check_bikou=$this->DB_rfs->escape($rfsChkTenkenKasyhoData['check_bikou']);
    }
	$measures_bikou='null';
	if ($rfsChkTenkenKasyhoData['measures_bikou']) {
 		$measures_bikou=$this->DB_rfs->escape($rfsChkTenkenKasyhoData['measures_bikou']);
    }

    $screening = $rfsChkTenkenKasyhoData['screening'];
    $check_policy = $rfsChkTenkenKasyhoData['check_policy'];
    $screening_taisyou = $rfsChkTenkenKasyhoData['screening_taisyou'];

    $this->DB_rfs->trans_start();
  
    $sql = <<<EOF
SELECT
 *
FROM
 rfs_t_chk_tenken_kasyo
WHERE
 chk_mng_no=$chk_mng_no AND rireki_no=$rireki_no AND buzai_cd=$buzai_cd AND buzai_detail_cd=$buzai_detail_cd AND tenken_kasyo_cd=$tenken_kasyo_cd
EOF;
    // log_message('debug', $sql);
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

    if (empty($result)) {
$sql = <<<EOF
INSERT
INTO rfs_t_chk_tenken_kasyo (
  chk_mng_no,
  rireki_no,
  buzai_cd,
  buzai_detail_cd,
  tenken_kasyo_cd,
  taisyou_umu,
  check_status,
  sonsyou_naiyou_cd,
  check_judge,
  measures_judge,
  measures_policy,
  measures_dt,
  check_bikou,
  measures_bikou,
  screening,
  check_policy,
  screening_taisyou
)
VALUES (
 {$chk_mng_no},
 {$rireki_no},
 {$buzai_cd},
 {$buzai_detail_cd},
 {$tenken_kasyo_cd},
 {$taisyou_umu},
 {$check_status},
 {$sonsyou_naiyou_cd},
 {$check_judge},
 {$measures_judge},
 {$measures_policy},
 {$measures_dt},
 {$check_bikou},
 {$measures_bikou},
 {$screening},
 {$check_policy},
 {$screening_taisyou}
)
EOF;
      // log_message('debug', $sql);
      $query = $this->DB_rfs->query($sql);
    }
    else {
$sql = <<<EOF
UPDATE
 rfs_t_chk_tenken_kasyo
SET
  chk_mng_no={$chk_mng_no},
  rireki_no={$rireki_no},
  buzai_cd={$buzai_cd},
  buzai_detail_cd={$buzai_detail_cd},
  tenken_kasyo_cd={$tenken_kasyo_cd},
  taisyou_umu={$taisyou_umu},
  check_status={$check_status},
  sonsyou_naiyou_cd={$sonsyou_naiyou_cd},
  check_judge={$check_judge},
  measures_judge={$measures_judge},
  measures_policy={$measures_policy},
  measures_dt={$measures_dt},
  check_bikou={$check_bikou},
  measures_bikou={$measures_bikou},
  screening={$screening},
  check_policy={$check_policy},
  screening_taisyou={$screening_taisyou}
WHERE
 chk_mng_no={$chk_mng_no} AND buzai_cd={$buzai_cd} AND buzai_detail_cd={$buzai_detail_cd} AND tenken_kasyo_cd={$tenken_kasyo_cd}
EOF;
      // log_message('debug', $sql);
      $query = $this->DB_rfs->query($sql);
    }

    if ($this->DB_rfs->trans_status() === FALSE) {
      $this->DB_rfs->trans_rollback();
      return false;
    }
  
    $this->DB_rfs->trans_complete();
  }


/***
 *
 * 点検箇所内容を保存する
 *   POST DATA
 *     rfsMShisetsuData Array 全件？（未使用）
 *     rfsTChkHuzokubutsuData Array 対象の附属物データ（未使用）
 *     rfsChkTenkenKasyoData Array 対象の点検箇所データっぽい
 *
 ***/
  public function setRfsTChkTenkenKasyo($post) {
	// 送信データを抽出
    $tenken_kasyo_arr = $post['rfsChkTenkenKasyoData'];
    for ($i=0;$i<count($tenken_kasyo_arr);$i++) {
      $tenken_kasyo = $tenken_kasyo_arr[$i]['tenkenKasyoData'];
      $tenken_kasyo = $this->escapeParam($tenken_kasyo, array(
            "chk_mng_no" => "nint",
            "rireki_no" => "nint",
            "buzai_cd" => "nint",
            "buzai_detail_cd" => "nint",
            "tenken_kasyo_cd" => "nint",
            "taisyou_umu" => "nint",
            "check_status" => "nint",
            "sonsyou_naiyou_cd" => "nint",
            "check_judge" => "nint",
            "measures_judge" => "nint",
            "measures_policy" => "ntext",
            "measures_dt" => "ntext",
            "check_bikou" => "ntext",
            "measures_bikou" => "ntext",
            "screening" => "nint",
            "check_policy" => "nint",
            "screening_taisyou" => "nint"
        ));
	  $sql = $sql = $this->upsertHelper(
            "public.rfs_t_chk_tenken_kasyo",
            "rfs_t_chk_tenken_kasyo_pkey",
            $tenken_kasyo,
            [
	            "chk_mng_no",
	            "rireki_no",
	            "buzai_cd",
	            "buzai_detail_cd",
	            "tenken_kasyo_cd",
	            "taisyou_umu",
	            "check_status",
	            "sonsyou_naiyou_cd",
	            "check_judge",
	            "measures_judge",
	            "measures_policy",
	            "measures_dt",
	            "check_bikou",
	            "measures_bikou",
	            "screening",
	            "check_policy",
	            "screening_taisyou"
            ]
        );
 
      // log_message('debug', $sql);
      $query = $this->DB_rfs->query($sql);
    }

  }

  /**
   * $paramで与えられた連想配列をすべてエスケープする。
   * @param $escapeParam 連想配列 key:要素名 , value:text,like,int,float,array,ntext,nint,nfloatのいずれか
   */
  public function escapeParam($param, $escapeParam) {
    $result = array();
    foreach ($escapeParam as $param_name => $param_type) {
      if (!isset($param[$param_name])) {
        // 定義されなかった時のデフォルト値
        if ($param_type == "text" || $param_type == "like") {
          $result[$param_name] = '';
        }
        if ($param_type == "int") {
          $result[$param_name] = -1;
        }
        if ($param_type == "ntext" || $param_type == "nfloat" || $param_type == "nint") {
          // ntextなどはnullに変換する
          $result[$param_name] = 'null';
        }
      } else {
        // エスケープ
        if ($param_type == "text" || $param_type == "ntext") {
          $result[$param_name] = $this->DB_rfs->escape($param[$param_name]);
        } else if ($param_type == "int" || $param_type == "nint") {
          $result[$param_name] = $this->DB_rfs->escape($param[$param_name]);
        } else if ($param_type == "float" || $param_type == "nfloat") {
          $result[$param_name] = $this->DB_rfs->escape($param[$param_name]);
        } else if ($param_type == "like") {
          $result[$param_name] = $this->DB_rfs->escape_like_str($param[$param_name]);
        } else if ($param_type == "array") {
          for ($i = 0; $i < count($param[$param_name]); $i++) {
            $result[$param_name][$i] = $this->DB_rfs->escape($param[$param_name][$i]);
          }
          $result[$param_name] = implode(",", $result[$param_name]);
        } else if ($param_type == "object") {
          $result[$param_name] = $param[$param_name];
        }
      }

    }
    return $result;
  }

  /**
   * Upsert用のSQLを生成する。
   */
  protected function upsertHelper($table_nm, $conflict_key, $data, $param_arr) {

    $insert_val_arr = [];
    $update_set_arr = [];

    for ($i = 0; $i < count($param_arr); $i++) {
      $insert_val_arr[$i] = $data[$param_arr[$i]];
      $update_set_arr[$i] = "{$param_arr[$i]} = {$data[$param_arr[$i]]}";
    }

    $insert_col = implode(",", $param_arr);
    $insert_val = implode(",", $insert_val_arr);
    $update_set = implode(",", $update_set_arr);

    $sql = <<<EOF
INSERT
INTO $table_nm (
  $insert_col
)
VALUES (
  $insert_val
)
  ON CONFLICT
    ON CONSTRAINT $conflict_key DO UPDATE
SET
  $update_set
EOF;

    return $sql;
  }

}
