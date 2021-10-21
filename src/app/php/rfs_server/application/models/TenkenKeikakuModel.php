<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 点検計画の検索に関するモデル
 * 返却は配列
 *
 * @access public
 * @package Model
 */
class TenkenKeikakuModel extends CI_Model {

  protected $DB_rfs;  // rfsコネクション

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
   * 点検施設検索
   *  先に件数を求める
   *
   *  廃止の扱い
   *   ここでは、廃止の施設は検索しない
   *
   * 引数：画面の入力検索項目
   *  shisetsu_cd 施設コード
   *  sp_from 測点FROM
   *  sp_to 測点TO
   *  shisetsu_kbn array 施設区分
   *  rosen array 路線
   * 戻り値：施設データarray
   */
  public function srchTenkenShisetsuNum($condition) {
    log_message('debug', 'srchTenkenShisetsuNum');

    // 条件を作成
    $where_dogen_cd="";
    $where_syucchoujo_cd="";
    $where_shisetsu_cd="";
    $where_sp="";
    $where_shisetsu_kbn="";
    $where_rosen="";

    /*******************/
    /***   条件設定   ***/
    /*******************/
    /***
     * 建管コード
     ***/
    $where_dogen_cd = " AND s1.dogen_cd = ".$condition['dogen_cd']." ";

    /***
     * 出張所コード
     ***/
    if ($condition['syucchoujo_cd']!=0) {
      $where_syucchoujo_cd = " AND s1.syucchoujo_cd = ".$condition['syucchoujo_cd']." ";
    }
    /***
     * 施設コード
     ***/
    if (isset($condition['shisetsu_cd'])) {
      $where_shisetsu_cd = " AND s1.shisetsu_cd LIKE '%".$condition['shisetsu_cd']."%' ";
    }

    /***
     * 測点
     ***/
    /*** どちらも入っている場合 ***/
    if (isset($condition['sp_from']) && isset($condition['sp_to'])) {
      // 大小関係
      if ((int)($condition['sp_from']) < (int)($condition['sp_to'])) {
        // fromの方が小さい
        $where_sp = " AND ((".$condition['sp_from']." <= s1.sp AND s1.sp <= ".$condition['sp_to'].") OR (".$condition['sp_from']." <= s1.sp_to AND s1.sp_to <= ".$condition['sp_to'].")) ";
      } else if ((int)($condition['sp_from']) === (int)($condition['sp_to'])) {
        // 同じ場合
        $where_sp = " AND (s1.sp = ".$condition['sp_from']." OR s1.sp_to = ".$condition['sp_from'].") ";
      } else {
        // fromの方が大きい
        $where_sp = " AND ((".$condition['sp_to']." <= s1.sp AND s1.sp <= ".$condition['sp_from'].") OR (".$condition['sp_to']." <= s1.sp_to AND s1.sp_to <= ".$condition['sp_from'].")) ";
      }
    /*** どちらも入っていない ***/
    } else if (!isset($condition['sp_from']) && !isset($condition['sp_to'])) {
      //セットなし
    /*** FROMのみ入っている場合 ***/
    } else if (!isset($condition['sp_to'])) {
      $where_sp = " AND (".$condition['sp_from']." <= s1.sp OR ".$condition['sp_from']." <= s1.sp_to) ";
    /*** TOのみ入っている場合 ***/
    } else {
      $where_sp = " AND (s1.sp <= ".$condition['sp_to']." OR s1.sp_to <= ".$condition['sp_to'].") ";
    }

    /***
     * 施設区分
     ***/
    if (isset($condition['shisetsu_kbn'])) {
      $where_shisetsu_kbn = " AND s1.shisetsu_kbn IN (";
      for ($i=0;$i<count($condition['shisetsu_kbn']);$i++) {
        if ($i>0) {
          $where_shisetsu_kbn .= ", ";
        }
        $where_shisetsu_kbn .= $condition['shisetsu_kbn'][$i];
      }
      $where_shisetsu_kbn .= ")";
    }

    /***
     * 路線コード
     ***/
    if (isset($condition['rosen'])) {
      $where_rosen = " AND s1.rosen_cd IN (";
      for ($i=0;$i<count($condition['rosen']);$i++) {
        if ($i>0) {
          $where_rosen .= ", ";
        }
        $where_rosen .= $condition['rosen'][$i];
      }
      $where_rosen .= ")";
    }

    /*************************/
    /***   条件設定ここまで   ***/
    /*************************/

$sql= <<<EOF
WITH shisetsu AS (
  SELECT
      s1.*
  FROM
    rfs_m_shisetsu s1 JOIN (
      SELECT
          shisetsu_cd
        , max(shisetsu_ver) shisetsu_ver
      FROM
        rfs_m_shisetsu
      GROUP BY
        shisetsu_cd
    ) s2
      ON s1.shisetsu_cd = s2.shisetsu_cd
      AND s1.shisetsu_ver = s2.shisetsu_ver
  WHERE
  -- 廃止施設を読み込まないようにする
  ((TRIM(s1.haishi) = '' OR s1.haishi IS NULL) AND s1.haishi_yyyy IS NULL)
  $where_dogen_cd
  $where_syucchoujo_cd
  $where_shisetsu_cd
  $where_sp
  $where_rosen
)
SELECT 
  count(*) cnt
FROM 
shisetsu
EOF;
//    log_message('debug', "sql=$sql");
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
//    $r = print_r($result, true);
//    log_message('debug', "result=$r");
    return $result[0]['cnt'];
  }

  public function srchTenkenShisetsu($condition) {
    // 施設データを検索
    $result = $this->srchTenkenShisetsuMain($condition);
    for($i = 0; $i < count($result); $i++) {
      // 直近の定期パトと法定点検/附属物点検のデータを結合
      $sno = $result[$i]['sno'];
      $shisetsu_kbn = $result[$i]['shisetsu_kbn'];

      // 定期パト
      $result[$i]['latest_teiki_pat'] = [];
      $latest_teiki_pat_result = $this->getTeikiPatrolLatestData($sno);
      if (count($latest_teiki_pat_result) > 0) {
        $result[$i]['latest_teiki_pat'] = $latest_teiki_pat_result[0];
      }

      if (1 <= $shisetsu_kbn && $shisetsu_kbn <= 5) {
        $result[$i]['latest_huzokubutsu'] = [];
        // 附属物点検
        $latest_huzokubutsu_result = $this->getChkMainMaxData($sno, $shisetsu_kbn);
        if (count($latest_huzokubutsu_result) > 0) {
          $result[$i]['latest_huzokubutsu'] = $latest_huzokubutsu_result[0];
        }
      } else {
        // 法定点検
      }
    }
    return $result;
  }

  /**
   * 施設検索
   *  検索項目は整頓されていて、入力されていない条件は引数に入っていない事
   *
   *  廃止の扱い
   *   ここでは、廃止の施設は検索しない
   *
   * 引数：画面の入力検索項目
   *  shisetsu_cd 施設コード
   *  sp_from 測点FROM
   *  sp_to 測点TO
   *  shisetsu_kbn array 施設区分
   *  rosen array 路線
   * 戻り値：施設データarray
   */
  private function srchTenkenShisetsuMain($condition) {
    log_message('debug', 'srchTenkenShisetsuMain');

    // 条件を作成
    $where_dogen_cd="";
    $where_syucchoujo_cd="";
    $where_shisetsu_cd="";
    $where_sp="";
    $where_shityouson="";
    $where_rosen="";
    $where_shisetsu_kbn="";

    /*******************/
    /***   条件設定   ***/
    /*******************/
    /***
     * 建管コード
     ***/
    $where_dogen_cd = " AND s1.dogen_cd = ".$condition['dogen_cd']." ";

    /***
     * 出張所コード
     ***/
    if ($condition['syucchoujo_cd']!=0) {
      $where_syucchoujo_cd = " AND s1.syucchoujo_cd = ".$condition['syucchoujo_cd']." ";
    }
    /***
     * 施設コード
     ***/
    if (isset($condition['shisetsu_cd'])) {
      $where_shisetsu_cd = " AND s1.shisetsu_cd LIKE '%".$condition['shisetsu_cd']."%' ";
    }

    /***
     * 測点
     ***/
    /*** どちらも入っている場合 ***/
    if (isset($condition['sp_from']) && isset($condition['sp_to'])) {
      // 大小関係
      if ((int)($condition['sp_from']) < (int)($condition['sp_to'])) {
        // fromの方が小さい
        $where_sp = " AND ((".$condition['sp_from']." <= s1.sp AND s1.sp <= ".$condition['sp_to'].") OR (".$condition['sp_from']." <= s1.sp_to AND s1.sp_to <= ".$condition['sp_to'].")) ";
      } else if ((int)($condition['sp_from']) === (int)($condition['sp_to'])) {
        // 同じ場合
        $where_sp = " AND (s1.sp = ".$condition['sp_from']." OR s1.sp_to = ".$condition['sp_from'].") ";
      } else {
        // fromの方が大きい
        $where_sp = " AND ((".$condition['sp_to']." <= s1.sp AND s1.sp <= ".$condition['sp_from'].") OR (".$condition['sp_to']." <= s1.sp_to AND s1.sp_to <= ".$condition['sp_from'].")) ";
      }
      /*** どちらも入っていない ***/
    } else if (!isset($condition['sp_from']) && !isset($condition['sp_to'])) {
      //セットなし
      /*** FROMのみ入っている場合 ***/
    } else if (!isset($condition['sp_to'])) {
      $where_sp = " AND (".$condition['sp_from']." <= s1.sp OR ".$condition['sp_from']." <= s1.sp_to) ";
      /*** TOのみ入っている場合 ***/
    } else {
      $where_sp = " AND (s1.sp <= ".$condition['sp_to']." OR s1.sp_to <= ".$condition['sp_to'].") ";
    }

    /***
     * 施設区分
     ***/
    if (isset($condition['shisetsu_kbn'])) {
      $where_shisetsu_kbn = " AND s1.shisetsu_kbn IN (";
      for ($i=0;$i<count($condition['shisetsu_kbn']);$i++) {
        if ($i>0) {
          $where_shisetsu_kbn .= ", ";
        }
        $where_shisetsu_kbn .= $condition['shisetsu_kbn'][$i];
      }
      $where_shisetsu_kbn .= ")";
    }

    /***
     * 路線コード
     ***/
    if (isset($condition['rosen'])) {
      $where_rosen = " AND s1.rosen_cd IN (";
      for ($i=0;$i<count($condition['rosen']);$i++) {
        if ($i>0) {
          $where_rosen .= ", ";
        }
        $where_rosen .= $condition['rosen'][$i];
      }
      $where_rosen .= ")";
    }

    /***
     * 点検計画の取得期間（翌年から10年後まで）
     * 法定点検か附属物点検かによって5年か10年かが変わるので、一旦10年分取得して法定点検のものは6年後以降を削除する
     */
    // 今年度
    $this_business_year = date('Y', strtotime('-3 month'));
    $start_date = (new DateTime())->setDate($this_business_year + 1, 4, 1)->setTime(0,0,0);
    $end_date = (new DateTime())->setDate($this_business_year + 10, 4, 1)->setTime(0,0,0);
    $houtei_end_date = (new DateTime())->setDate($this_business_year + 5, 4, 1)->setTime(0,0,0);
    $start_date_str = date_format($start_date, 'Y-m-d');
    $end_date_str = date_format($end_date, 'Y-m-d');

    /*************************/
    /***   条件設定ここまで   ***/
    /*************************/
$sql= <<<EOF
WITH patrol_plan AS (
  SELECT
    rtpp.sno
    , json_agg(rtpp) json
  FROM
  rfs_t_patrol_plan rtpp
  WHERE
    rtpp.target_dt BETWEEN '$start_date_str' AND '$end_date_str'
  GROUP BY
    rtpp.sno
)
, shisetsu AS (
  SELECT
      s1.*
  FROM
    rfs_m_shisetsu s1 JOIN (
      SELECT
          shisetsu_cd
        , max(shisetsu_ver) shisetsu_ver
      FROM
        rfs_m_shisetsu
      GROUP BY
        shisetsu_cd
    ) s2
      ON s1.shisetsu_cd = s2.shisetsu_cd
      AND s1.shisetsu_ver = s2.shisetsu_ver
  WHERE
  -- 廃止施設を読み込まないようにする
  ((TRIM(s1.haishi) = '' OR s1.haishi IS NULL) AND s1.haishi_yyyy IS NULL)
  $where_dogen_cd
  $where_syucchoujo_cd
  $where_shisetsu_cd
  $where_sp
  $where_shisetsu_kbn
  $where_rosen
)
SELECT
    s.sno
  , s.shisetsu_kbn
  , sk.shisetsu_kbn_nm
  , s.shisetsu_keishiki_cd
  , s.name
  , s.shisetsu_cd
  , s.rosen_cd
  , r.rosen_nm
  , s.sp
  , s.lr
  , CASE
    WHEN s.lr = 0
    THEN 'L'
    WHEN s.lr = 1
    THEN 'R'
    WHEN s.lr = 2
    THEN 'C'
    WHEN s.lr = 3
    THEN 'LR'
    ELSE '-'
    END lr_str
  , s.dogen_cd
  , s.syucchoujo_cd
  , s.keishiki_kubun_cd1
  , s.keishiki_kubun_cd2
  , pp.json teiki_pat_plans
FROM
  shisetsu s
  LEFT JOIN rfs_m_shisetsu_kbn sk
    ON s.shisetsu_kbn = sk.shisetsu_kbn
  LEFT JOIN rfs_m_rosen r
    ON s.rosen_cd = r.rosen_cd
  LEFT JOIN patrol_plan pp
    ON s.sno = pp.sno
WHERE TRUE
--  s.shisetsu_ver = (
--    SELECT
--        shisetsu_ver
--    FROM
--      rfs_m_shisetsu
--    WHERE
--      shisetsu_cd = s.shisetsu_cd
--    ORDER BY
--      shisetsu_ver DESC
--    LIMIT
--      1
--  )
ORDER BY 
    s.rosen_cd
    ,s.sp

EOF;
log_message('debug', "sql=$sql");
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

    $result = array_map(function($row) use($houtei_end_date, $this_business_year) {
      // teiki_pat_plansはJSON文字列なので連想配列に戻す
      $row['teiki_pat_plans'] = is_null($row['teiki_pat_plans']) ? [] : json_decode($row['teiki_pat_plans'], true);
      
      if ($row['shisetsu_kbn'] > 5) {
        // 法定点検は5年分なので5年分のみに絞り込む
        log_message('debug', print_r($row['teiki_pat_plans'], true));
        $row['teiki_pat_plans'] = array_filter($row['teiki_pat_plans'], function($plan) use($houtei_end_date) {
          return (new DateTime($plan['target_dt']) <= $houtei_end_date);
        });
      }

      // チェックボックスに対応するデータを作成する
      $row['plan_list'] = [];
      // 施設区分が5以下のものは附属物点検を行うので10年分、それ以外は法定点検なので5年分データを作成する
      $patrol_years = $row['shisetsu_kbn'] > 5 ? 5 : 10;
      for ($i = 0; $i < $patrol_years; $i++) {
        // 1年毎の処理
        $target_year = $this_business_year + $i + 1;
        $row['plan_list'][$i]['year'] = $target_year;
        $row['plan_list'][$i]['teiki_pat_planned'] = false;
        // teiki_pat_plansにこの年のデータがあるかどうか探す。あれば定期パトを実施する。無ければ法定/附属物点検を行う。
        foreach ($row['teiki_pat_plans'] as $plan) {
          $planned_year = (new DateTime($plan['target_dt']))->format('Y');
          if ($planned_year == $target_year) {
            $row['plan_list'][$i]['teiki_pat_planned'] = true;
          }
        }
      }

      return $row;
    }, $result);


    //    log_message('debug', "sql=$sql");
    //    $r = print_r($result, true);
    //    log_message('debug', "result=$r");

    return $result;
  }

    /***
   * 定期パトロールの直近データを取得する。
   *
   * 引数:$sno sno
   *
   ***/
  protected function getTeikiPatrolLatestData($sno){
    log_message('info', 'getTeikiPatrolLatestData');

    $sql= <<<EOF
WITH teiki_patlol_result AS (
  SELECT 
    tld.sno,
    tls.deliveried_at,
    wareki_to_char(tls.deliveried_at, 'ggLL')  wareki_ryaku, -- 直近年度
    tld.ijyou_umu_flg,
    CASE
      WHEN tld.ijyou_umu_flg = 0
      THEN '無'
      WHEN tld.ijyou_umu_flg = 1
      THEN '有'
      ELSE ''
    END umu_str -- 直近異常有無
  FROM
    teiki_patrol.tenken_lists tls
  INNER JOIN teiki_patrol.tenken_list_details  tld
    ON tls.tenken_list_cd = tld.tenken_list_cd
  LEFT JOIN v_wareki_seireki_future wf
    ON EXTRACT(YEAR FROM tls.deliveried_at) = wf.seireki
  WHERE
    sno = $sno
)
SELECT *
FROM teiki_patlol_result
WHERE deliveried_at = (
  SELECT max(deliveried_at) 
  FROM teiki_patlol_result
)
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

    /***
   * 附属物点検の直近データを取得する。
   *
   * 引数:$sno sno
   * 　　:$shisetsu_kbn 施設区分
   *
   ***/
  protected function getChkMainMaxData($sno, $shisetsu_kbn){
    log_message('info', 'getChkMainMaxData');

    // 防雪柵の場合は親はいらない
    // それ以外はstruct_idx=0
    $where_struct_idx="";
    if ($shisetsu_kbn == 4) {
      // 防雪柵
      $where_struct_idx = "AND struct_idx > 0";
    } else {
      $where_struct_idx = "AND struct_idx = 0";
    }

    $sql= <<<EOF
WITH huzoku_tenken_result AS (
  SELECT
      c.chk_mng_no
    , c.sno
    , h.chk_dt
    , c.struct_idx
    , wareki_to_char(h.chk_dt, 'ggLL') w_chk_dt  -- 直近年度
    , h.check_shisetsu_judge
    , sj1.shisetsu_judge_nm check_shisetsu_judge_nm  -- 直近の健全性
  FROM
    (SELECT * FROM rfs_t_chk_main WHERE sno = $sno $where_struct_idx) c JOIN (
      SELECT
        h1.*
      FROM
        rfs_t_chk_huzokubutsu h1 JOIN (
          SELECT
            chk_mng_no
            , MAX(rireki_no) rireki_no
          FROM
            rfs_t_chk_huzokubutsu
          GROUP BY
            chk_mng_no
        ) h2
        ON h1.chk_mng_no = h2.chk_mng_no
        AND h1.rireki_no = h2.rireki_no
    ) h
    ON c.chk_mng_no = h.chk_mng_no
    LEFT JOIN rfs_m_shisetsu_judge sj1
    ON h.check_shisetsu_judge = sj1.shisetsu_judge
)
SELECT *
FROM huzoku_tenken_result
WHERE chk_dt = (
  SELECT max(chk_dt) 
  FROM huzoku_tenken_result
)
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

}
