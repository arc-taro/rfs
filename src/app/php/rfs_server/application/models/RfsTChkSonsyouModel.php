<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Userのモデル

 * @access public
 * @package Model
 */
class RfsTChkSonsyouModel extends CI_Model {
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

  public function get_rfs_t_chk_sonsyou($data) {
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
 rfs_t_chk_sonsyou
WHERE
 {$where}
SQL;

    // log_message('debug', $sql);
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    // log_message('debug', print_r($result, true));
    return $result;
  }
  
  public function set_rfs_t_chk_sonsyou($data) {
    $rfsChkSonsyouData = $data['rfsChkSonsyouData'];

    $chk_mng_no = $rfsChkSonsyouData['chk_mng_no'];
    $rireki_no = $rfsChkSonsyouData['rireki_no'];
    $buzai_cd = $rfsChkSonsyouData['buzai_cd'];
    $buzai_detail_cd = $rfsChkSonsyouData['buzai_detail_cd'];
    $tenken_kasyo_cd = $rfsChkSonsyouData['tenken_kasyo_cd'];
    $sonsyou_naiyou_cd = $rfsChkSonsyouData['sonsyou_naiyou_cd'];
    $check_before = $rfsChkSonsyouData['check_before'];
    $measures_after = $rfsChkSonsyouData['measures_after'];

    $this->DB_rfs->trans_start();
  
    $sql = <<<EOF
SELECT
 *
FROM
 rfs_t_chk_sonsyou
WHERE
 chk_mng_no={$chk_mng_no} AND buzai_cd={$buzai_cd} AND buzai_detail_cd={$buzai_detail_cd} AND tenken_kasyo_cd={$tenken_kasyo_cd} AND sonsyou_naiyou_cd={$sonsyou_naiyou_cd}
EOF;
    // log_message('debug', $sql);
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

    if (empty($result)) {
$sql = <<<EOF
INSERT
INTO rfs_t_chk_sonsyou (
  chk_mng_no,
  rireki_no,
  buzai_cd,
  buzai_detail_cd,
  tenken_kasyo_cd,
  sonsyou_naiyou_cd,
  check_before,
  measures_after
)
VALUES (
 {$chk_mng_no},
 {$rireki_no},
 {$buzai_cd},
 {$buzai_detail_cd},
 {$tenken_kasyo_cd},
 {$sonsyou_naiyou_cd},
 {$check_before},
 {$measures_after}
)
EOF;
      // log_message('debug', $sql);
      $query = $this->DB_rfs->query($sql);
    }
    else {
$sql = <<<EOF
UPDATE
 rfs_t_chk_sonsyou
SET
-- chk_mng_no={$chk_mng_no},
-- rireki_no={$rireki_no},
-- buzai_cd={$buzai_cd},
-- buzai_detail_cd={$buzai_detail_cd},
-- tenken_kasyo_cd={$tenken_kasyo_cd},
-- sonsyou_naiyou_cd={$sonsyou_naiyou_cd},
 check_before={$check_before},
 measures_after={$measures_after}
WHERE
 chk_mng_no={$chk_mng_no} AND buzai_cd={$buzai_cd} AND buzai_detail_cd={$buzai_detail_cd} AND tenken_kasyo_cd={$tenken_kasyo_cd} AND sonsyou_naiyou_cd={$sonsyou_naiyou_cd}
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
 * 損傷内容を保存する
 *   POST DATA
 *     rfsMShisetsuData Array 全件？（未使用）
 *     rfsTChkHuzokubutsuData Array 対象の附属物データ（未使用）
 *     rfsChkSonsyouData Array 対象の損傷っぽい
 *
 *
 ***/
  public function setRfsTChkSonsyou($post) {
	// 送信データを抽出
    $sonsyou_arr = $post['rfsChkSonsyouData'];
    for ($i=0;$i<count($sonsyou_arr);$i++) {
      $sonsyou = $sonsyou_arr[$i]['sonsyouData'];
      $sonsyou = $this->escapeParam($sonsyou, array(
            "chk_mng_no" => "nint",
            "rireki_no" => "nint",
            "buzai_cd" => "nint",
            "buzai_detail_cd" => "nint",
            "tenken_kasyo_cd" => "nint",
            "sonsyou_naiyou_cd" => "nint",
            "check_before" => "nint",
            "measures_after" => "nint"
        ));
	  $sql = $this->upsertHelper(
            "public.rfs_t_chk_sonsyou",
            "rfs_t_chk_sonsyou_pkey",
            $sonsyou,
            [
	            "chk_mng_no",
    	        "rireki_no",
        	    "buzai_cd",
            	"buzai_detail_cd",
	            "tenken_kasyo_cd",
    	        "sonsyou_naiyou_cd",
        	    "check_before",
            	"measures_after"
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

