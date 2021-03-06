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

  // rfs_t_patrol_planのcategoryの値
  const CATEGORY_HUZOKUBUTSU = 1;
  const CATEGORY_HOUTEI = 2;
  const CATEGORY_TEIKI_PAT = 3;


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
    log_message('info', __METHOD__);

    // 条件を作成
    $where_dogen_cd="";
    $where_syucchoujo_cd="";
    $where_shisetsu_cd="";
    $where_secchi="";
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
     * 設置年度
     ***/
    /*** どちらも入っている場合 ***/
    if (isset($condition['secchi_from']) && isset($condition['secchi_to'])) {
      // 大小関係
      if ((int)($condition['secchi_from']) < (int)($condition['secchi_to'])) {
        // fromの方が小さい
        if (isset($condition['secchi_null'])) {
          // 不明を含む
          $where_secchi = " AND ((".$condition['secchi_from']." <= s1.secchi_yyyy AND s1.secchi_yyyy <= ".$condition['secchi_to'].") OR s1.secchi_yyyy IS NULL) ";
        } else {
          $where_secchi = " AND (".$condition['secchi_from']." <= s1.secchi_yyyy AND s1.secchi_yyyy <= ".$condition['secchi_to'].") ";
        }
      } else if ((int)($condition['secchi_from']) === (int)($condition['secchi_to'])) {
        // 同じ場合
        if (isset($condition['secchi_null'])) {
          // 不明を含む
          $where_secchi = " AND (s1.secchi_yyyy = ".$condition['secchi_from']." OR s1.secchi_yyyy IS NULL) ";
        }else{
          $where_secchi = " AND s1.secchi_yyyy = ".$condition['secchi_from']." ";
        }
      } else {
        // fromの方が大きい
        if (isset($condition['secchi_null'])) {
          // 不明を含む
          $where_secchi = " AND ((".$condition['secchi_to']." <= s1.secchi_yyyy AND s1.secchi_yyyy <= ".$condition['secchi_from'].") OR s1.secchi_yyyy IS NULL) ";
        } else {
          $where_secchi = " AND (".$condition['secchi_to']." <= s1.secchi_yyyy AND s1.secchi_yyyy <= ".$condition['secchi_from'].") ";
        }
      }

    /*** どちらも入っていない ***/
    } else if (!isset($condition['secchi_from']) && !isset($condition['secchi_to'])) {
      if (isset($condition['secchi_null'])) {
        // 不明を含む
        $where_secchi = " AND s1.secchi_yyyy IS NULL ";
      }
    /*** FROMのみ入っている場合 ***/
    } else if (!isset($condition['secchi_to'])) {
      if (isset($condition['secchi_null'])) {
        // 不明を含む
        $where_secchi = " AND (".$condition['secchi_from']." <= s1.secchi_yyyy OR s1.secchi_yyyy IS NULL) ";
      } else {
        $where_secchi = " AND ".$condition['secchi_from']." <= s1.secchi_yyyy ";
      }
    /*** TOのみ入っている場合 ***/
    } else {
      if (isset($condition['secchi_null'])) {
        // 不明を含む
        $where_secchi = " AND (s1.secchi_yyyy <= ".$condition['secchi_to']." OR s1.secchi_yyyy IS NULL) ";
      } else {
        $where_secchi = " AND s1.secchi_yyyy <= ".$condition['secchi_to']." ";
      }
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
-- srchTenkenShisetsuMain内SQLのpatrol_planはshisetsuの
-- 1レコードに対して常に1件のため件数に影響を与えないので、ここでは含めない
WITH shisetsu AS (
  -- 防雪柵は支柱インデックスごとに表示するので、防雪柵とそれ以外で分けて取得する
  -- 防雪柵以外
  SELECT
    s1.*
    ,-1 as struct_idx
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
  -- AND s1.shisetsu_kbn <> 4
  $where_dogen_cd
  $where_syucchoujo_cd
  $where_shisetsu_cd
  $where_secchi
  $where_sp
  $where_shisetsu_kbn
  $where_rosen
  UNION
  -- 防雪柵
  SELECT
      s1.*
      ,rmbs.struct_idx as struct_idx
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
  LEFT JOIN
    rfs_m_bousetsusaku_shichu rmbs
    ON rmbs.sno = s1.sno
  WHERE
  -- 廃止施設を読み込まないようにする
  ((TRIM(s1.haishi) = '' OR s1.haishi IS NULL) AND s1.haishi_yyyy IS NULL)
  AND s1.shisetsu_kbn = 4
  $where_dogen_cd
  $where_syucchoujo_cd
  $where_shisetsu_cd
  $where_secchi
  $where_sp
  $where_shisetsu_kbn
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
    log_message('info', __METHOD__);
    // 施設データを検索
    $result = $this->srchTenkenShisetsuMain($condition);
    
    for($i_row = 0; $i_row < count($result); $i_row++) {
      // // 直近の附属物点検のデータを結合（定期パトと法定点検はMainで取得済み）
      // $sno = $result[$i_row]['sno'];
      // $shisetsu_kbn = $result[$i_row]['shisetsu_kbn'];
      // $struct_idx = $result[$i_row]['struct_idx'];

      // if ($result[$i_row]['huzokubutsu_flag']) {
      //   $result[$i_row]['latest_huzokubutsu'] = [];
      //   // 附属物点検
      //   $latest_huzokubutsu_result = $this->getChkMainMaxData($sno, $shisetsu_kbn, $struct_idx);
      //   if (count($latest_huzokubutsu_result) > 0) {
      //     $result[$i_row]['latest_huzokubutsu'] = $latest_huzokubutsu_result[0];
      //   }
      // }

      // チェックボックスに対応するデータを作成する
      $result[$i_row]['teiki_pat_plans'] = [];
      $result[$i_row]['houtei_plans'] = [];
      $result[$i_row]['huzokubutsu_plans'] = [];

      // 今年度の西暦を取得
      $this_business_year = date('Y', strtotime('-3 month'));
      $this_year_obj = (new DateTime())->setDate($this_business_year, 4, 1);
      $next_year_obj = (new DateTime())->setDate($this_business_year + 1, 4, 1);

      // 今年度点検を実施済みかどうかをチェックして保持しておく
      // HACK: もう少しまとめられないか?
      if (isset($result[$i_row]['latest_houtei']['target_dt_year']) && $result[$i_row]['latest_houtei']['target_dt_year']) {
        $target_dt_year = $result[$i_row]['latest_houtei']['target_dt_year'];
        $target_dt_month = $result[$i_row]['latest_houtei']['target_dt_month'];
        $target_dt_day = $result[$i_row]['latest_houtei']['target_dt_day'];
        $latest_patrol_obj = (new DateTime())->setDate($target_dt_year, $target_dt_month, $target_dt_day);
        if ($this_year_obj <= $latest_patrol_obj && $latest_patrol_obj < $next_year_obj) {
          // 今年度は配列の1個目に入る
          $result[$i_row]['houtei_plans'][0]['patrol_done'] = true;
        }
      }
      if (isset($result[$i_row]['latest_huzokubutsu']['target_dt_year']) && $result[$i_row]['latest_huzokubutsu']['target_dt_year']) {
        $target_dt_year = $result[$i_row]['latest_huzokubutsu']['target_dt_year'];
        $target_dt_month = $result[$i_row]['latest_huzokubutsu']['target_dt_month'];
        $target_dt_day = $result[$i_row]['latest_huzokubutsu']['target_dt_day'];
        $latest_patrol_obj = (new DateTime())->setDate($target_dt_year, $target_dt_month, $target_dt_day);
        if ($this_year_obj <= $latest_patrol_obj && $latest_patrol_obj < $next_year_obj) {
          // 今年度は配列の1個目に入る
          $result[$i_row]['huzokubutsu_plans'][0]['patrol_done'] = true;
        }
      }
      if (isset($result[$i_row]['latest_teiki_pat']['target_dt_year']) && $result[$i_row]['latest_teiki_pat']['target_dt_year']) {
        $target_dt_year = $result[$i_row]['latest_teiki_pat']['target_dt_year'];
        $target_dt_month = $result[$i_row]['latest_teiki_pat']['target_dt_month'];
        $target_dt_day = $result[$i_row]['latest_teiki_pat']['target_dt_day'];
        $latest_patrol_obj = (new DateTime())->setDate($target_dt_year, $target_dt_month, $target_dt_day);
        if ($this_year_obj <= $latest_patrol_obj && $latest_patrol_obj < $next_year_obj) {
          // 今年度は配列の1個目に入る
          $result[$i_row]['teiki_pat_plans'][0]['patrol_done'] = true;
        }
      }

      $this->config->load('config');
      $year_span = $this->config->config['tenken_keikaku_year_span'];
      
      for ($i_year = 0; $i_year < $year_span; $i_year++) {
        // 1年毎の処理
        $target_year = $this_business_year + $i_year;
        $result[$i_row]['teiki_pat_plans'][$i_year]['year'] = $target_year;
        $result[$i_row]['teiki_pat_plans'][$i_year]['planned'] = false;
        if (
          isset($result[$i_row]['teiki_pat_plans'][$i_year]['patrol_done'])
          && $result[$i_row]['teiki_pat_plans'][$i_year]['patrol_done']) {
            // パトロール実施済みの場合はtrueにする
            $result[$i_row]['teiki_pat_plans'][$i_year]['planned'] = true;
        }

        $result[$i_row]['houtei_plans'][$i_year]['year'] = $target_year;
        $result[$i_row]['houtei_plans'][$i_year]['planned'] = false;
        if (
          isset($result[$i_row]['houtei_plans'][$i_year]['patrol_done'])
          && $result[$i_row]['houtei_plans'][$i_year]['patrol_done']) {
            // パトロール実施済みの場合はtrueにする
            $result[$i_row]['houtei_plans'][$i_year]['planned'] = true;
        }
        
        $result[$i_row]['huzokubutsu_plans'][$i_year]['year'] = $target_year;
        $result[$i_row]['huzokubutsu_plans'][$i_year]['planned'] = false;
        if (
          isset($result[$i_row]['huzokubutsu_plans'][$i_year]['patrol_done'])
          && $result[$i_row]['huzokubutsu_plans'][$i_year]['patrol_done']) {
            // パトロール実施済みの場合はtrueにする
            $result[$i_row]['huzokubutsu_plans'][$i_year]['planned'] = true;
        }
        

        // teiki_pat_plansにこの年のデータがあるかどうか探す。
        foreach ($result[$i_row]['patrol_plans'] as $plan) {
          $planned_year = (new DateTime($plan['target_dt']))->format('Y');
          $category = $plan['category'];
          if ($planned_year == $target_year) {
            if ($category == self::CATEGORY_HUZOKUBUTSU) {
              $result[$i_row]['huzokubutsu_plans'][$i_year]['planned'] = true;
            } else if ($category == self::CATEGORY_HOUTEI) {
              $result[$i_row]['houtei_plans'][$i_year]['planned'] = true;
            } else if ($category == self::CATEGORY_TEIKI_PAT) {
              $result[$i_row]['teiki_pat_plans'][$i_year]['planned'] = true;
            }
          }
        }
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
    log_message('info', __METHOD__);

    // 条件を作成
    $where_dogen_cd="";
    $where_syucchoujo_cd="";
    $where_shisetsu_cd="";
    $where_secchi="";
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
     * 設置年度
     ***/
    /*** どちらも入っている場合 ***/
    if (isset($condition['secchi_from']) && isset($condition['secchi_to'])) {
      // 大小関係
      if ((int)($condition['secchi_from']) < (int)($condition['secchi_to'])) {
        // fromの方が小さい
        if (isset($condition['secchi_null'])) {
          // 不明を含む
          $where_secchi = " AND ((".$condition['secchi_from']." <= s1.secchi_yyyy AND s1.secchi_yyyy <= ".$condition['secchi_to'].") OR s1.secchi_yyyy IS NULL) ";
        } else {
          $where_secchi = " AND (".$condition['secchi_from']." <= s1.secchi_yyyy AND s1.secchi_yyyy <= ".$condition['secchi_to'].") ";
        }
      } else if ((int)($condition['secchi_from']) === (int)($condition['secchi_to'])) {
        // 同じ場合
        if (isset($condition['secchi_null'])) {
          // 不明を含む
          $where_secchi = " AND (s1.secchi_yyyy = ".$condition['secchi_from']." OR s1.secchi_yyyy IS NULL) ";
        }else{
          $where_secchi = " AND s1.secchi_yyyy = ".$condition['secchi_from']." ";
        }
      } else {
        // fromの方が大きい
        if (isset($condition['secchi_null'])) {
          // 不明を含む
          $where_secchi = " AND ((".$condition['secchi_to']." <= s1.secchi_yyyy AND s1.secchi_yyyy <= ".$condition['secchi_from'].") OR s1.secchi_yyyy IS NULL) ";
        } else {
          $where_secchi = " AND (".$condition['secchi_to']." <= s1.secchi_yyyy AND s1.secchi_yyyy <= ".$condition['secchi_from'].") ";
        }
      }

    /*** どちらも入っていない ***/
    } else if (!isset($condition['secchi_from']) && !isset($condition['secchi_to'])) {
      if (isset($condition['secchi_null'])) {
        // 不明を含む
        $where_secchi = " AND s1.secchi_yyyy IS NULL ";
      }
    /*** FROMのみ入っている場合 ***/
    } else if (!isset($condition['secchi_to'])) {
      if (isset($condition['secchi_null'])) {
        // 不明を含む
        $where_secchi = " AND (".$condition['secchi_from']." <= s1.secchi_yyyy OR s1.secchi_yyyy IS NULL) ";
      } else {
        $where_secchi = " AND ".$condition['secchi_from']." <= s1.secchi_yyyy ";
      }
    /*** TOのみ入っている場合 ***/
    } else {
      if (isset($condition['secchi_null'])) {
        // 不明を含む
        $where_secchi = " AND (s1.secchi_yyyy <= ".$condition['secchi_to']." OR s1.secchi_yyyy IS NULL) ";
      } else {
        $where_secchi = " AND s1.secchi_yyyy <= ".$condition['secchi_to']." ";
      }
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
     * 点検計画の取得期間（今年から10年後まで）
     */
    // 今年度
    $this_business_year = date('Y', strtotime('-3 month'));
    $this->config->load('config');
    $year_span = $this->config->config['tenken_keikaku_year_span'];
    $start_date = (new DateTime())->setDate($this_business_year, 4, 1)->setTime(0,0,0);
    $end_date = (new DateTime())->setDate($this_business_year + $year_span - 1, 4, 1)->setTime(0,0,0);
    $start_date_str = date_format($start_date, 'Y-m-d');
    $end_date_str = date_format($end_date, 'Y-m-d');

    /*************************/
    /***   条件設定ここまで   ***/
    /*************************/
$sql= <<<EOF
WITH patrol_plan AS (
  SELECT
    rtpp.sno
    , rtpp.struct_idx 
    , json_agg(rtpp) json
  FROM
  rfs_t_patrol_plan rtpp
  WHERE
    rtpp.target_dt BETWEEN '$start_date_str' AND '$end_date_str'
  GROUP BY
    rtpp.sno
    , rtpp.struct_idx
)
, max_tenken_detail AS (
  SELECT 
    sno
    ,max(zenkei_image_at) max_zenkei_image_at
  FROM
    teiki_patrol.tenken_list_details tld 
  WHERE 
    tld.zenkei_image_1 IS NOT NULL 
  GROUP BY 
    sno
)
, ijou_list AS (
  -- tenken_listごとに、tenken_list_detailsに異常有無フラグが1のレコードを集計する
  -- ここにヒットしないtenken_list_cdは異常有無フラグが全て0かnull
  SELECT
    tenken_list_cd
    , count(tenken_list_cd) AS ijou_list_count
  FROM 
    teiki_patrol.tenken_list_details tld
  WHERE
    tld.ijyou_umu_flg = 1
  GROUP BY
    tenken_list_cd 
)
, latest_teiki_pat AS (
  -- snoごとに直近の定期パトロールを取得する
  SELECT
    tld.sno
    ,tl.deliveried_at
    ,wareki_to_char(tl.deliveried_at, 'ggLL')  wareki_ryaku -- 直近年度
    ,il.ijou_list_count
    ,CASE
      WHEN il.ijou_list_count IS NULL
        THEN '無'
      ELSE '有'
    END umu_str -- 直近異常有無
    -- PHP側で日付を正確に指定するために年月日を分けて取得
    ,to_char(tld.zenkei_image_at, 'YYYY') as target_dt_year
    ,to_char(tld.zenkei_image_at, 'MM') as target_dt_month
    ,to_char(tld.zenkei_image_at, 'MM') as target_dt_day
  FROM
    teiki_patrol.tenken_lists tl
  INNER JOIN
    teiki_patrol.tenken_list_details tld
    ON tl.tenken_list_cd = tld.tenken_list_cd
  INNER JOIN 
    max_tenken_detail mtd
    ON tld.sno = mtd.sno
    AND tld.zenkei_image_at = mtd.max_zenkei_image_at
  LEFT JOIN
    ijou_list il
    ON tl.tenken_list_cd = il.tenken_list_cd
)
, latest_teiki_pat_json AS (
  -- 直近の定期パトロールは1オブジェクトとしてまとまっていたほうが扱いやすいのでJSONに変換しておく
  SELECT
    ltp.sno
    , json_agg(ltp) latest_teiki_pat_json
  FROM
    latest_teiki_pat ltp
  GROUP BY 
    ltp.sno
)
, max_houtei_chk_times AS (
  SELECT
    sno
    ,max(chk_times) AS max_chk_times
  FROM
    rfs_t_chk_houtei 
  GROUP BY
    sno
)
, latest_houtei AS (
  -- snoごとに直近の法定点検を取得する
  SELECT
    rtch.*
    -- PHP側で日付を正確に指定するために年月日を分けて取得
    ,to_char(rtch.target_dt, 'YYYY') as target_dt_year
    ,to_char(rtch.target_dt, 'MM') as target_dt_month
    ,to_char(rtch.target_dt, 'MM') as target_dt_day
  FROM
    rfs_t_chk_houtei rtch 
  INNER JOIN
    max_houtei_chk_times mhct
  ON rtch.sno = mhct.sno AND rtch.chk_times = mhct.max_chk_times
)
, latest_houtei_json AS (
  -- 直近の法定点検は1オブジェクトとしてまとまっていたほうが扱いやすいのでJSONに変換しておく
  SELECT
    lho.sno
    , json_agg(lho) latest_houtei_json
  FROM
    latest_houtei lho
  GROUP BY 
    lho.sno
)
, chk_main_huzokubutsu as
(
  select
    rtcm.*,
    rtch.rireki_no
  from
    rfs_t_chk_main rtcm
  INNER JOIN
    rfs_t_chk_huzokubutsu rtch
    on rtcm.chk_mng_no = rtch.chk_mng_no
), max_chk_times_tbl as (
  select
    sno,
    struct_idx,
    max(chk_times) as max_chk_times
  from
    chk_main_huzokubutsu
  group by
    sno,struct_idx
), max_rireki_tbl as (
select
  chk_mng_no
  ,max(rireki_no) as max_rireki_no
from
  rfs_t_chk_huzokubutsu
group by chk_mng_no
)
, latest_huzokubutsu AS (
  -- snoごとに直近の法定点検を取得する
select
  mctt.sno,
  mctt.struct_idx,
  mctt.max_chk_times,
  rtcm.chk_mng_no,
  mrt.max_rireki_no
  , rtch.check_shisetsu_judge
  , wareki_to_char(rtch.chk_dt, 'ggLL') w_chk_dt  -- 直近年度
  , rtch.check_shisetsu_judge
  , rmsj.shisetsu_judge_nm check_shisetsu_judge_nm  -- 直近の健全性
  -- PHP側で日付を正確に指定するために年月日を分けて取得
  , to_char(rtcm.target_dt, 'YYYY') as target_dt_year
  , to_char(rtcm.target_dt, 'MM') as target_dt_month
  , to_char(rtcm.target_dt, 'MM') as target_dt_day
from
  max_chk_times_tbl mctt
INNER JOIN
  rfs_t_chk_main rtcm
  ON mctt.sno = rtcm.sno
     AND mctt.struct_idx = rtcm.struct_idx
     AND mctt.max_chk_times = rtcm.chk_times
INNER JOIN
  max_rireki_tbl mrt
  ON rtcm.chk_mng_no = mrt.chk_mng_no
LEFT JOIN
  rfs_t_chk_huzokubutsu rtch
  ON rtch.chk_mng_no = rtcm.chk_mng_no
LEFT JOIN rfs_m_shisetsu_judge rmsj
  ON rtch.check_shisetsu_judge = rmsj.shisetsu_judge
)
, latest_huzokubutsu_json AS (
  -- 直近の点検は1オブジェクトとしてまとまっていたほうが扱いやすいのでJSONに変換しておく
  SELECT
    lhu.sno
    , lhu.struct_idx
    , json_agg(lhu) latest_huzokubutsu_json
  FROM
    latest_huzokubutsu lhu
  GROUP BY 
    lhu.sno
    ,lhu.struct_idx
)
, shisetsu AS (
  -- ※この後ろのUNIONで防雪柵のレコードを別途取得しているが、
  -- 定期パトの点検計画は防雪柵自体に紐づくので、ここでは防雪柵の施設自体のレコードも取得する
  SELECT
    s1.*
    ,-1 as struct_idx 
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
  $where_secchi
  $where_sp
  $where_shisetsu_kbn
  $where_rosen
  UNION
  -- 防雪柵は支柱インデックスごとに表示するので、防雪柵は支柱インデックスごとのレコードを別途取得する
  SELECT
      s1.*
      ,rmbs.struct_idx as struct_idx
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
  LEFT JOIN
    rfs_m_bousetsusaku_shichu rmbs
    ON rmbs.sno = s1.sno
  WHERE
  -- 廃止施設を読み込まないようにする
  ((TRIM(s1.haishi) = '' OR s1.haishi IS NULL) AND s1.haishi_yyyy IS NULL)
  AND s1.shisetsu_kbn = 4
  $where_dogen_cd
  $where_syucchoujo_cd
  $where_shisetsu_cd
  $where_secchi
  $where_sp
  $where_shisetsu_kbn
  $where_rosen
)
SELECT
    s.sno
  , s.shisetsu_kbn
  , sk.shisetsu_kbn_nm
  , s.struct_idx
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
  , pp.json patrol_plans
  , rmpt.houtei_flag
  , rmpt.huzokubutsu_flag
  , rmpt.teiki_pat_flag
  , ltpj.latest_teiki_pat_json
  , lhj.latest_houtei_json
  , lhuj.latest_huzokubutsu_json
FROM
  shisetsu s
  INNER JOIN
    rfs_m_patrol_type rmpt
    ON rmpt.shisetsu_kbn = s.shisetsu_kbn
  LEFT JOIN
    latest_teiki_pat_json ltpj
    ON ltpj.sno = s.sno AND rmpt.teiki_pat_flag = TRUE
  LEFT JOIN
    latest_houtei_json lhj
    ON lhj.sno = s.sno AND rmpt.houtei_flag = TRUE
  LEFT JOIN
    latest_huzokubutsu_json lhuj
    ON lhuj.sno = s.sno 
    AND
    CASE WHEN s.shisetsu_kbn = 4
      THEN lhuj.struct_idx = s.struct_idx
      ELSE TRUE
    END
    AND rmpt.huzokubutsu_flag = TRUE
  LEFT JOIN rfs_m_shisetsu_kbn sk
    ON s.shisetsu_kbn = sk.shisetsu_kbn
  LEFT JOIN rfs_m_rosen r
    ON s.rosen_cd = r.rosen_cd
  LEFT JOIN patrol_plan pp
    ON s.sno = pp.sno 
    -- 防雪柵の場合は結合条件に支柱インデックスを追加する
    AND CASE WHEN s.shisetsu_kbn <> 4
        THEN TRUE
        ELSE s.struct_idx = pp.struct_idx
    END
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
    ,s.struct_idx

EOF;
    log_message('debug', "sql=$sql");
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

    $result = array_map(function($row) use($year_span, $this_business_year) {
      // Postgresのbool型はt/fで取得されてしまうので変換する
      $row['houtei_flag'] = $row['houtei_flag'] == 't' ? true : false;
      $row['huzokubutsu_flag'] = $row['huzokubutsu_flag'] == 't' ? true : false;
      $row['teiki_pat_flag'] = $row['teiki_pat_flag'] == 't' ? true : false;

      // patrol_plansと直近の点検はJSON文字列なので連想配列に戻す
      $row['patrol_plans'] = is_null($row['patrol_plans']) ? [] : json_decode($row['patrol_plans'], true);
      if ($row['teiki_pat_flag']) {
        $latest_teiki_pat = is_null($row['latest_teiki_pat_json']) ? [] : json_decode($row['latest_teiki_pat_json'], true);
        $row['latest_teiki_pat'] = [];
        if (count($latest_teiki_pat) > 0) {
          $row['latest_teiki_pat'] = $latest_teiki_pat[0];
        }
      }
      // JSONデータは変換して不要になるので削除
      unset($row['latest_teiki_pat_json']);

      if ($row['houtei_flag']) {
        $latest_houtei = is_null($row['latest_houtei_json']) ? [] : json_decode($row['latest_houtei_json'], true);
        $row['latest_houtei'] = [];
        if (count($latest_houtei) > 0) {
          $row['latest_houtei'] = $latest_houtei[0];
        }
      }
      // JSONデータは変換して不要になるので削除
      unset($row['latest_houtei_json']);

      if ($row['huzokubutsu_flag']) {
        $latest_huzokubutsu = is_null($row['latest_huzokubutsu_json']) ? [] : json_decode($row['latest_huzokubutsu_json'], true);
        $row['latest_huzokubutsu'] = [];
        if (count($latest_huzokubutsu) > 0) {
          $row['latest_huzokubutsu'] = $latest_huzokubutsu[0];
        }
      }
      // JSONデータは変換して不要になるので削除
      unset($row['latest_huzokubutsu_json']);
      
      return $row;
    }, $result);

    //    log_message('debug', "sql=$sql");
    //    $r = print_r($result, true);
    //    log_message('debug', "result=$r");

    return $result;
  }

  /***
   * 附属物点検の直近データを取得する。
   *
   * 引数:$sno sno
   * 　　:$shisetsu_kbn 施設区分
   *
   ***/
  protected function getChkMainMaxData($sno, $shisetsu_kbn, $struct_idx){
    // 検索結果1レコードごとに読みだされるため、関数の呼び出しログが大量に出るので削除
    // log_message('info', __METHOD__);

    // 防雪柵の場合は親はいらない
    // それ以外はstruct_idx=0
    $where_struct_idx="";
    if ($shisetsu_kbn == 4) {
      // 防雪柵
      $where_struct_idx = "AND rtcm.struct_idx = $struct_idx";
    } else {
      $where_struct_idx = "AND rtcm.struct_idx = 0";
    }

    $sql= <<<EOF
SELECT
  rtcm.chk_mng_no
  , rtcm.sno
  , rtch.chk_dt
  , rtcm.struct_idx
  , rtch.check_shisetsu_judge
  , rtcm.chk_times
  , rtch.rireki_no
  , wareki_to_char(rtch.chk_dt, 'ggLL') w_chk_dt  -- 直近年度
  , rtch.check_shisetsu_judge
  , rmsj.shisetsu_judge_nm check_shisetsu_judge_nm  -- 直近の健全性
  -- PHP側で日付を正確に指定するために年月日を分けて取得
  , to_char(rtcm.target_dt, 'YYYY') as target_dt_year
  , to_char(rtcm.target_dt, 'MM') as target_dt_month
  , to_char(rtcm.target_dt, 'MM') as target_dt_day
FROM
  rfs_m_shisetsu rms
LEFT JOIN
  rfs_t_chk_main rtcm
  ON rms.sno = rtcm.sno
LEFT JOIN
  rfs_t_chk_huzokubutsu rtch
  ON rtch.chk_mng_no = rtcm.chk_mng_no
LEFT JOIN rfs_m_shisetsu_judge rmsj
  ON rtch.check_shisetsu_judge = rmsj.shisetsu_judge
WHERE
  rtcm.sno = $sno
  AND rtch.chk_mng_no IS NOT NULL
  $where_struct_idx
ORDER BY
-- chk_timesとrireki_noが最大のもののみを取得するためにソートして最初の1件を取得する
  chk_times DESC
  , rireki_no DESC
LIMIT 1
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  public function deleteOldTenkenKeikaku($shisetsu_list, $start_year, $end_year) {
    log_message('info', __METHOD__);
    if (count($shisetsu_list) == 0) {
      return;
    }

    $start_date_obj = (new DateTime())->setDate($start_year, 4, 1);
    $start_date = $start_date_obj->format('Y-m-d');
    $end_date_obj = (new DateTime())->setDate($end_year, 4, 1);
    $end_date = $end_date_obj->format('Y-m-d');

    // snoとstuct_idxが一致するものを全て削除
    // shisetsu_kbnが4以外のものはstruct_idxがnullなので分けて削除する（nullの場合in句が使えないため）
    $shisetsu_kbn_4 = [];
    $shisetsu_kbn_others = [];
    foreach($shisetsu_list as $shisetsu) {
      if ($shisetsu['shisetsu_kbn'] == 4) {
        array_push($shisetsu_kbn_4, $shisetsu);
      } else {
        array_push($shisetsu_kbn_others, $shisetsu);
      }
    }


    $in_kbn_4 = 'AND (sno,struct_idx) in (';
    if (count($shisetsu_kbn_4) > 0) {
      for ($i = 0; $i < count($shisetsu_kbn_4); $i++) {
        $sno = $shisetsu_kbn_4[$i]['sno'];
        $struct_idx = $shisetsu_kbn_4[$i]['struct_idx'];
        $in_kbn_4 .= "($sno, $struct_idx)";
        if ($i < count($shisetsu_kbn_4) - 1) {
          $in_kbn_4 .= ', ';
        } else {
          $in_kbn_4 .= ')';
        }
      }
      $sql = <<<EOF
DELETE
FROM
  public.rfs_t_patrol_plan
WHERE
  target_dt BETWEEN '${start_date}' AND '${end_date}'
  ${in_kbn_4}
EOF;

      log_message('debug', $sql);
      $this->DB_rfs->query($sql);
    }

    $in_kbn_others = 'AND sno in (';
    if (count($shisetsu_kbn_others) > 0) {
      for ($i = 0; $i < count($shisetsu_kbn_others); $i++) {
        $sno = $shisetsu_kbn_others[$i]['sno'];
        $in_kbn_others .= $sno;
        if ($i < count($shisetsu_kbn_others) - 1) {
          $in_kbn_others .= ', ';
        } else {
          $in_kbn_others .= ')';
        }
      }
      $sql = <<<EOF
DELETE
FROM
  public.rfs_t_patrol_plan
WHERE
  target_dt BETWEEN '${start_date}' AND '${end_date}'
  ${in_kbn_others}
EOF;
      log_message('debug', $sql);
      $this->DB_rfs->query($sql);
    }
    return;
  }

  private function insertTenkenKeikaku($category, $sno, $shisetsu_kbn, $struct_idx, $year) {
    log_message('info', __METHOD__);
    $target_dt_obj = (new DateTime())->setDate($year, 4, 1);
    $target_dt = $target_dt_obj->format('Y-m-d');
    $struct_idx_nullable = $shisetsu_kbn == 4 ? $struct_idx : 'null';

    $sql = <<<EOF
INSERT INTO
public.rfs_t_patrol_plan (
  sno
  ,shisetsu_kbn
  ,target_dt
  ,category
  ,struct_idx
) VALUES (
  $sno
  ,$shisetsu_kbn
  ,'$target_dt'
  ,$category
  ,$struct_idx_nullable
);
EOF;

    log_message('debug', $sql);
    $this->DB_rfs->query($sql);
    return;
  }

  public function insertHouteiTenkenKeikaku($sno, $shisetsu_kbn, $struct_idx, $year) {
    $this->insertTenkenKeikaku(self::CATEGORY_HOUTEI, $sno, $shisetsu_kbn, $struct_idx, $year);
  }

  public function insertHuzokubutsuTenkenKeikaku($sno, $shisetsu_kbn, $struct_idx, $year) {
    $this->insertTenkenKeikaku(self::CATEGORY_HUZOKUBUTSU, $sno, $shisetsu_kbn, $struct_idx, $year);
  }

  public function insertTeikiPatTenkenKeikaku($sno, $shisetsu_kbn, $struct_idx, $year) {
    $this->insertTenkenKeikaku(self::CATEGORY_TEIKI_PAT, $sno, $shisetsu_kbn, $struct_idx, $year);
  }

}
