<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * EditBSParentModelのモデル
 *   防雪柵の親データを更新するモデル
 *
 * @access public
 * @package Model
 */
class EditBSParentModel extends CI_Model {
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
  }

/***
 *
 * 損傷内容を保存する
 *   POST DATA
 *     rfsMShisetsuData Array 全件？（未使用）
 *     rfsTChkHuzokubutsuData Array 対象の附属物データ
 *     rfsChkSonsyouData Array 対象の損傷っぽい（未使用）
 *
 ***/
  public function editBSParent($post) {
	// 対象施設は送信した附属物データ
    $huzokubutsu_arr=$post['rfsTChkHuzokubutsuData'];
	for ($i=0;$i<count($huzokubutsu_arr);$i++) {
		$huzokubutsu = $huzokubutsu_arr[$i];	// 附属物データ
        $shisetsu = $this->getShisetsuData($huzokubutsu['chk_mng_no']);
		if ($shisetsu['shisetsu_kbn']!=4) {
          continue;
        }
		// 親データ取得
		$parent = $this->getParentData($huzokubutsu['chk_mng_no']);
	    // トランザクション開始
	    $this->DB_rfs->trans_begin();
		// 附属物登録
        $this->delParentHuzokubutsu($parent['chk_mng_no']);
        $this->insParentHuzokubutsu($parent['chk_mng_no']);
		// 部材登録
        $this->delParentBuzai($parent['chk_mng_no']);
        $this->insParentBuzai($parent['chk_mng_no']);
		// 点検箇所登録
		if ($this->getCntParentTenkenKasyo($parent['chk_mng_no'])==0) {
	        $this->insParentTenkenKasyo($parent['chk_mng_no'], $huzokubutsu['chk_mng_no']);
        }
        // 損傷登録
		if ($this->getCntParentSonsyou($parent['chk_mng_no'])==0) {
	        $this->insParentSonsyou($parent['chk_mng_no'], $huzokubutsu['chk_mng_no']);
        }
		// 親子テーブル登録
		$this->editParentAndChild($parent['chk_mng_no'],$huzokubutsu['chk_mng_no']);
	    // コネクションの状態チェック
	    if ($this->DB_rfs->trans_status() === false) {
	      $this->DB_rfs->trans_rollback();
	    } else {
	      $this->DB_rfs->trans_commit();
	    }
	}
  }

/***
 *
 * 点検箇所テーブル検索
 * 引数：chk_mng_no 親点検管理番号
 ***/
  private function getShisetsuData($chk_mng_no) {

	$sql = <<<EOF
SELECT
      *
  FROM
    rfs_m_shisetsu
  WHERE
    sno = (SELECT sno FROM rfs_t_chk_main WHERE chk_mng_no = $chk_mng_no);
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result[0];
  }

/***
 *
 * 親附属物データ削除
 * 引数：chk_mng_no 親点検管理番号
 ***/
  private function delParentHuzokubutsu($chk_mng_no) {
	// 削除
    $sql = <<<EOF
DELETE 
  FROM
    rfs_t_chk_huzokubutsu 
  WHERE
    chk_mng_no = $chk_mng_no 
    AND rireki_no = 0
EOF;
     $query = $this->DB_rfs->query($sql);
  }

/***
 *
 * 親附属物データ登録
 * 引数：chk_mng_no 親点検管理番号
 ***/
  private function insParentHuzokubutsu($chk_mng_no) {
	// 削除
    $sql = <<<EOF
INSERT 
  INTO rfs_t_chk_huzokubutsu 
WITH chkmain AS ( 
  SELECT
        * 
    FROM
      rfs_t_chk_main 
    WHERE
      chk_mng_no = $chk_mng_no
) 
, key_set AS ( 
  SELECT
        chk_mng_no
      , max(rireki_no) rireki_no 
    FROM
      rfs_t_chk_huzokubutsu 
    WHERE
      chk_mng_no IN ( 
        SELECT
              c.chk_mng_no 
          FROM
            rfs_t_chk_main c JOIN chkmain 
              ON c.sno = chkmain.sno 
              AND c.chk_times = chkmain.chk_times 
              AND c.struct_idx > 0
      ) 
    GROUP BY
      chk_mng_no
) 
, tmp AS ( 
  SELECT
        * 
    FROM
      rfs_t_chk_huzokubutsu ch 
    WHERE
      (chk_mng_no, rireki_no) IN (SELECT * FROM key_set) 
    ORDER BY
      measures_shisetsu_judge DESC
      , ( 
        SELECT
              struct_idx 
          FROM
            rfs_t_chk_main 
          WHERE
            chk_mng_no = ch.chk_mng_no
      ) 
    LIMIT
      1
) 
SELECT
      $chk_mng_no chk_mng_no
    , 0 rireki_no
    , tmp.chk_dt
    , tmp.chk_company
    , tmp.chk_person
    , tmp.investigate_dt
    , tmp.investigate_company
    , tmp.investigate_person
    , tmp.surface
    , tmp.part_notable_chk
    , tmp.reason_notable_chk
    , tmp.special_report
    , ( 
      SELECT
            min(phase) 
        FROM
          rfs_t_chk_huzokubutsu 
        WHERE
          chk_mng_no IN (SELECT chk_mng_no FROM key_set) 
        GROUP BY
          chk_mng_no 
        LIMIT
          1
    )  phase
    , tmp.check_shisetsu_judge
    , tmp.syoken
    , tmp.update_dt
    , tmp.measures_shisetsu_judge
    , tmp.create_account 
  FROM
    tmp;
EOF;
     $query = $this->DB_rfs->query($sql);
  }

/***
 *
 * 親部材データ削除
 * 引数：chk_mng_no 親点検管理番号
 ***/
  private function delParentBuzai($chk_mng_no) {
	// 削除
    $sql = <<<EOF
DELETE 
  FROM
    rfs_t_chk_buzai 
  WHERE
    chk_mng_no = $chk_mng_no 
    AND rireki_no = 0
EOF;
     $query = $this->DB_rfs->query($sql);
  }

/***
 *
 * 親附属物データ登録
 * 引数：chk_mng_no 親点検管理番号
 ***/
  private function insParentBuzai($chk_mng_no) {
	// 削除
    $sql = <<<EOF
INSERT 
  INTO rfs_t_chk_buzai 
WITH chkmain AS ( 
  SELECT
        * 
    FROM
      rfs_t_chk_main 
    WHERE
      chk_mng_no = $chk_mng_no
) 
, key_set AS ( 
  SELECT
        chk_mng_no
      , max(rireki_no) rireki_no 
    FROM
      rfs_t_chk_huzokubutsu 
    WHERE
      chk_mng_no IN ( 
        SELECT
              c.chk_mng_no 
          FROM
            rfs_t_chk_main c JOIN chkmain 
              ON c.sno = chkmain.sno 
              AND c.chk_times = chkmain.chk_times 
              AND c.struct_idx > 0
      ) 
    GROUP BY
      chk_mng_no
) 
, target_data AS ( 
  SELECT
        chk_mng_no
      , ( 
        SELECT
              struct_idx 
          FROM
            rfs_t_chk_main 
          WHERE
            chk_mng_no = b.chk_mng_no
      ) struct_idx
      , rireki_no
      , b.buzai_cd
      , b.necessity_measures
      , b.check_buzai_judge
      , b.hantei1
      , b.hantei2
      , b.hantei3
      , b.hantei4
      , b.measures_buzai_judge 
    FROM
      rfs_t_chk_buzai b 
    WHERE
      (chk_mng_no, rireki_no) IN (SELECT * FROM key_set)
) 
, target_data_judge AS ( 
  SELECT
        tmp1.* 
    FROM
      target_data tmp1 JOIN ( 
        SELECT
              buzai_cd
            , max(measures_buzai_judge) measures_buzai_judge 
          FROM
            target_data 
          GROUP BY
            buzai_cd
      ) tmp2 
        ON tmp1.buzai_cd = tmp2.buzai_cd 
        AND tmp1.measures_buzai_judge = tmp2.measures_buzai_judge
) 
, tmp AS ( 
  SELECT
        tmp1.* 
    FROM
      target_data_judge tmp1 JOIN ( 
        SELECT
              min(struct_idx) struct_idx
            , buzai_cd 
          FROM
            target_data_judge 
          GROUP BY
            buzai_cd
      ) tmp2 
        ON tmp1.struct_idx = tmp2.struct_idx 
        AND tmp1.buzai_cd = tmp2.buzai_cd
) 
SELECT
      $chk_mng_no chk_mng_no
    , 0 rireki_no
    , buzai_cd
    , necessity_measures
    , check_buzai_judge
    , hantei1
    , hantei2
    , hantei3
    , hantei4
    , measures_buzai_judge 
  FROM
    tmp 
  ORDER BY
    buzai_cd;
EOF;
     $query = $this->DB_rfs->query($sql);
  }

/***
 *
 * 点検箇所テーブル検索
 * 引数：chk_mng_no 親点検管理番号
 ***/
  private function getCntParentTenkenKasyo($chk_mng_no) {

	$sql = <<<EOF
SELECT
      count(*) cnt 
  FROM
    rfs_t_chk_tenken_kasyo
  WHERE
    chk_mng_no = $chk_mng_no;
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result[0]['cnt'];
  }

/***
 *
 * 損傷テーブル検索
 * 引数：chk_mng_no 親点検管理番号
 ***/
  private function getCntParentSonsyou($chk_mng_no) {

	$sql = <<<EOF
SELECT
      count(*) cnt 
  FROM
    rfs_t_chk_sonsyou
  WHERE
    chk_mng_no = $chk_mng_no;
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result[0]['cnt'];
  }

/***
 *
 * 親点検箇所データ登録
 * 引数：chk_mng_no 親点検管理番号
 *       chk_mng_no_child 子点検番号
 ***/
  private function insParentTenkenKasyo($chk_mng_no, $chk_mng_no_child) {
	// 登録
    $sql = <<<EOF
INSERT 
  INTO rfs_t_chk_tenken_kasyo 
SELECT
      $chk_mng_no chk_mng_no
    , 0 rireki_no
    , buzai_cd
    , buzai_detail_cd
    , tenken_kasyo_cd
    , 0 taisyou_umu
    , 0 check_status
    , 0 sonsyou_naiyou_cd
    , 1 check_judge
    , 0 measures_judge
    , '' measures_policy
    , NULL measures_dt
    , '' check_bikou
    , '' measures_bikou
    , 0 screening
    , 0 check_policy
    , 0 screening_taisyou 
  FROM
    rfs_t_chk_tenken_kasyo 
  WHERE
    chk_mng_no = $chk_mng_no_child; 
EOF;
     $query = $this->DB_rfs->query($sql);
  }

/***
 *
 * 親損傷データ登録
 * 引数：chk_mng_no 親点検管理番号
 *       chk_mng_no_child 子点検番号
 ***/
  private function insParentSonsyou($chk_mng_no, $chk_mng_no_child) {
	// 登録
    $sql = <<<EOF
INSERT 
  INTO rfs_t_chk_Sonsyou 
SELECT
      $chk_mng_no chk_mng_no
    , 0 rireki_no
    , buzai_cd
    , buzai_detail_cd
    , tenken_kasyo_cd
    , sonsyou_naiyou_cd
    , 1 check_before
    , 0 measures_after 
  FROM
    rfs_t_chk_sonsyou 
  WHERE
    chk_mng_no = $chk_mng_no_child; 
EOF;
     $query = $this->DB_rfs->query($sql);
  }

/***
 *
 * 親データ取得
 * 引数：chk_mng_no
 ***/
  private function getParentData($chk_mng_no) {

      $sql = <<<EOF
WITH chkmain AS ( 
  SELECT
        * 
    FROM
      rfs_t_chk_main 
    WHERE
      chk_mng_no = $chk_mng_no
) 
SELECT
      c.* 
  FROM
    rfs_t_chk_main c JOIN chkmain 
      ON c.sno = chkmain.sno 
      AND c.chk_times = chkmain.chk_times 
      AND c.struct_idx = 0;
EOF;
     $query = $this->DB_rfs->query($sql);
     $result = $query->result('array');
     return $result[0];
  }

/***
 *
 * 親子テーブル作成
 * 引数：chk_mng_no 親点検管理番号
 *       chk_mng_no_child 子点検管理番号
 ***/
  private function editParentAndChild($chk_mng_no,$chk_mng_no_child) {
	$cnt = $this->getParentAndChildCnt($chk_mng_no,$chk_mng_no_child);
    // 存在しない場合のみINSERT
	if ($cnt==0) {
		$this->insParentAndChild($chk_mng_no,$chk_mng_no_child);
    } // 存在する場合は、端末送信で完了になることは無いため更新しない
  }

/***
 *
 * 親子テーブル検索
 * 引数：chk_mng_no 親点検管理番号
 ***/
  private function getParentAndChildCnt($chk_mng_no,$chk_mng_no_child) {

	$sql = <<<EOF
SELECT
      count(*) cnt 
  FROM
    rfs_t_chk_bousetsusaku 
  WHERE
    chk_mng_no = $chk_mng_no 
    AND chk_mng_no_struct = $chk_mng_no_child;
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result[0]['cnt'];
  }

/***
 *
 * 親子テーブル登録
 * 引数：chk_mng_no 親点検管理番号
 ***/
  private function insParentAndChild($chk_mng_no,$chk_mng_no_child) {
	$sql = <<<EOF
INSERT 
  INTO rfs_t_chk_bousetsusaku 
  VALUES ($chk_mng_no, $chk_mng_no_child, 0);
EOF;
    $query = $this->DB_rfs->query($sql);
  }





  private function aaa () {

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
