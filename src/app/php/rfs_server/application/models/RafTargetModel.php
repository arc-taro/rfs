<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 点検対象登録画面に関するモデル
 * 返却は配列
 *
 * @access public
 * @package Model
 */
class RafTargetModel extends CI_Model {

  protected $DB_rfs;
  protected $DB_imm;

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
    $this->DB_imm = $this->load->database('imm',TRUE);
    if ($this->DB_imm->conn_id === FALSE) {
      log_message('debug', '維持管理システムデータベースに接続されていません');
      return;
    }
  }

  /**
   * 検索対象件数取得
   *  検索項目は整頓されていて、入力されていない条件は引数に入っていない事
   *
   * 引数：画面の入力検索項目
   *  shisetsu_cd 施設コード
   *  secchi_from 設置年度FROM
   *  secchi_to 設置年度TO
   *  secchi_null 設置年度不明
   *  sp_from 測点FROM
   *  sp_to 測点TO
   *  shityouson  市町村
   *  azaban  字番
   *  shisetsu_kbn array 施設区分
   *  substitute_road array 代替路の有無
   *  emergency_road array 緊急輸送道路
   *  kyouyou_kbn array 供用区分
   *  rosen array 路線
   * 戻り値：施設データarray
   */
  public function srchShisetsuNum($condition) {
    log_message('debug', 'srchShisetsuNum');

    $result = $this->srchShisetsu($condition);
    return count($result);
  }

  /**
   * 施設検索
   *  検索項目は整頓されていて、入力されていない条件は引数に入っていない事
   *
   * 引数：画面の入力検索項目
   *  shisetsu_cd 施設コード
   *  secchi_from 設置年度FROM
   *  secchi_to 設置年度TO
   *  secchi_null 設置年度不明
   *  sp_from 測点FROM
   *  sp_to 測点TO
   *  shityouson  市町村
   *  azaban  字番
   *  shisetsu_kbn array 施設区分
   *  substitute_road array 代替路の有無
   *  emergency_road array 緊急輸送道路
   *  kyouyou_kbn array 供用区分
   *  rosen array 路線
   * 戻り値：施設データarray
   */
  public function srchShisetsu($condition) {
    log_message('debug', 'srchShisetsu');

    // 条件を作成
    $where_dogen_cd=""; // 施設条件
    $where_syucchoujo_cd=""; // 施設条件
    $where_shisetsu_cd=""; // 施設条件
    $where_secchi=""; // 施設条件
    $where_sp=""; // 施設条件
    $where_shityouson=""; // 施設条件
    $where_azaban=""; // 施設条件
    $where_shisetsu_kbn=""; // 施設条件
    $where_rosen=""; // 施設条件
    $where_dogen_cd_tmp=""; // 施設条件(防雪柵検索用)
    $where_syucchoujo_cd_tmp=""; // 施設条件(防雪柵検索用)
    $where_shisetsu_cd_tmp=""; // 施設条件(防雪柵検索用)
    $where_secchi_tmp=""; // 施設条件(防雪柵検索用)
    $where_sp_tmp=""; // 施設条件(防雪柵検索用)
    $where_shityouson_tmp=""; // 施設条件(防雪柵検索用)
    $where_azaban_tmp=""; // 施設条件(防雪柵検索用)
    $where_rosen_tmp=""; // 施設条件(防雪柵検索用)
    $where_target_nendo=""; // rfs_t_chk_main条件
    $where_chk_dt=""; // rfs_t_huzokubutsu条件
    $where_investigate_dt=""; // rfs_t_huzokubutsu条件
    $where_phase=""; // rfs_t_huzokubutsu条件
    $where_chk_judge=""; // rfs_t_huzokubutsu条件
    $where_measures_judge=""; // rfs_t_huzokubutsu条件
    $where_chk_company=""; // rfs_t_huzokubutsu条件
    $where_not_chk_year=""; // 過去に点検していない年数

    /*******************/
    /***   条件設定   ***/
    /*******************/

    /***************/
    /*** 施設条件 ***/
    /***************/

    // 建管コード
    $where_dogen_cd = " AND dogen_cd = ".$condition['dogen_cd']." ";
    // 出張所コード
    if ($condition['syucchoujo_cd']!=0) {
      $where_syucchoujo_cd = " AND syucchoujo_cd = ".$condition['syucchoujo_cd']." ";
    }
    // 施設コード
    if (isset($condition['shisetsu_cd'])) {
      $where_shisetsu_cd = " AND shisetsu_cd LIKE '%".$condition['shisetsu_cd']."%' ";
    }
    // 設置年度
    /*** どちらも入っている場合 ***/
    if (isset($condition['secchi_from']) && isset($condition['secchi_to'])) {
      // 大小関係
      if ((int)($condition['secchi_from']) < (int)($condition['secchi_to'])) {
        // fromの方が小さい
        if (isset($condition['secchi_null'])) {
          // 不明を含む
          $where_secchi = " AND ((".$condition['secchi_from']." <= secchi_yyyy AND secchi_yyyy <= ".$condition['secchi_to'].") OR secchi_yyyy IS NULL) ";
        } else {
          $where_secchi = " AND (".$condition['secchi_from']." <= secchi_yyyy AND secchi_yyyy <= ".$condition['secchi_to'].") ";
        }
      } else if ((int)($condition['secchi_from']) === (int)($condition['secchi_to'])) {
        // 同じ場合
        if (isset($condition['secchi_null'])) {
          // 不明を含む
          $where_secchi = " AND (secchi_yyyy = ".$condition['secchi_from']." OR secchi_yyyy IS NULL) ";
        }else{
          $where_secchi = " AND secchi_yyyy = ".$condition['secchi_from']." ";
        }
      } else {
        // fromの方が大きい
        if (isset($condition['secchi_null'])) {
          // 不明を含む
          $where_secchi = " AND ((".$condition['secchi_to']." <= secchi_yyyy AND secchi_yyyy <= ".$condition['secchi_from'].") OR secchi_yyyy IS NULL) ";
        } else {
          $where_secchi = " AND (".$condition['secchi_to']." <= secchi_yyyy AND secchi_yyyy <= ".$condition['secchi_from'].") ";
        }
      }
    /*** どちらも入っていない ***/
    } else if (!isset($condition['secchi_from']) && !isset($condition['secchi_to'])) {
      if (isset($condition['secchi_null'])) {
        // 不明を含む
        $where_secchi = " AND secchi_yyyy IS NULL ";
      }
    /*** FROMのみ入っている場合 ***/
    } else if (!isset($condition['secchi_to'])) {
      if (isset($condition['secchi_null'])) {
        // 不明を含む
        $where_secchi = " AND (".$condition['secchi_from']." <= secchi_yyyy OR secchi_yyyy IS NULL) ";
      } else {
        $where_secchi = " AND ".$condition['secchi_from']." <= secchi_yyyy ";
      }
    /*** TOのみ入っている場合 ***/
    } else {
      if (isset($condition['secchi_null'])) {
        // 不明を含む
        $where_secchi = " AND (secchi_yyyy <= ".$condition['secchi_to']." OR secchi_yyyy IS NULL) ";
      } else {
        $where_secchi = " AND secchi_yyyy <= ".$condition['secchi_to']." ";
      }
    }
    // 測点
    if (isset($condition['sp_from']) && isset($condition['sp_to'])) {
      // 大小関係
      if ((int)($condition['sp_from']) < (int)($condition['sp_to'])) {
        // fromの方が小さい
        $where_sp = " AND (".$condition['sp_from']." <= sp AND sp <= ".$condition['sp_to'].") ";
      } else if ((int)($condition['sp_from']) === (int)($condition['sp_to'])) {
        // 同じ場合
        $where_sp = " AND sp = ".$condition['sp_from']." ";
      } else {
        // fromの方が大きい
        $where_sp = " AND (".$condition['sp_to']." <= sp AND sp <= ".$condition['sp_from'].") ";
      }
    /*** どちらも入っていない ***/
    } else if (!isset($condition['sp_from']) && !isset($condition['sp_to'])) {
      //セットなし
    /*** FROMのみ入っている場合 ***/
    } else if (!isset($condition['sp_to'])) {
      $where_sp = " AND ".$condition['sp_from']." <= sp ";
      /*** TOのみ入っている場合 ***/
    } else {
      $where_sp = " AND sp <= ".$condition['sp_to']." ";
    }
    // 市町村
    if (isset($condition['shityouson'])) {
      $where_shityouson = " AND shityouson LIKE '%".$condition['shityouson']."%' ";
    }
    // 字番
    if (isset($condition['azaban'])) {
      $where_azaban = " AND azaban LIKE '%".$condition['azaban']."%' ";
    }
    // 施設区分
    if (isset($condition['shisetsu_kbn'])) {
      $where_shisetsu_kbn = " AND shisetsu_kbn IN (";
      for ($i=0;$i<count($condition['shisetsu_kbn']);$i++) {
        if ($i>0) {
          $where_shisetsu_kbn .= ", ";
        }
        $where_shisetsu_kbn .= $condition['shisetsu_kbn'][$i];
      }
      $where_shisetsu_kbn .= ")";
    } else {
      $where_shisetsu_kbn = " AND shisetsu_kbn <= 5";
    }
    // 路線コード
    if (isset($condition['rosen'])) {
      $where_rosen = " AND rosen_cd IN (";
      for ($i=0;$i<count($condition['rosen']);$i++) {
        if ($i>0) {
          $where_rosen .= ", ";
          $where_rosen_tmp .= ", ";
        }
        $where_rosen .= $condition['rosen'][$i];
        $where_rosen_tmp .= $condition['rosen'][$i];
      }
      $where_rosen .= ")";
      $where_rosen_tmp .= ")";
    }

    $SC_JOIN = "LEFT JOIN ";

    /** 前年度検索の場合 */
    if(isset($condition['zennen_check']) && $condition['zennen_check']==true){
      /***************/
      /*** 点検条件 ***/
      /***************/
      // 点検の条件がある場合は、点検がぶら下がったデータを検索するためJOINとする。
      // 施設のみ条件がある場合は、点検の有無に関わらず検索するためLEFT JOINとする。
      // フェーズ
      if (isset($condition['phase'])) {
        $SC_JOIN = "JOIN ";
        $where_phase = " AND main.phase IN (";
        for ($i=0;$i<count($condition['phase']);$i++) {
          if ($i>0) {
            $where_phase .= ", ";
          }
          $where_phase .= $condition['phase'][$i];
        }
        $where_phase .= ")";
      }
      // 前年度
      $where_target_nendo="AND CAST((this_nendo_year-1) || '-04-01' AS DATE) <= CAST(target_dt AS DATE) AND CAST(target_dt AS DATE) < CAST(this_nendo_year || '-04-01' AS DATE)";
//      // 健全性(点検時)
//      if (isset($condition['chk_judge'])) {
//        $SC_JOIN = "JOIN ";
//        $where_chk_judge = " AND main.check_shisetsu_judge IN (";
//        for ($i=0;$i<count($condition['chk_judge']);$i++) {
//          if ($i>0) {
//            $where_chk_judge .= ", ";
//          }
//          $where_chk_judge .= $condition['chk_judge'][$i];
//        }
//        $where_chk_judge .= ")";
//      }
//      // 健全性(措置後)
//      if (isset($condition['measures_judge'])) {
//        $SC_JOIN = "JOIN ";
//        $where_measures_judge = " AND main.measures_shisetsu_judge IN (";
//        for ($i=0;$i<count($condition['measures_judge']);$i++) {
//          if ($i>0) {
//            $where_measures_judge .= ", ";
//          }
//          $where_measures_judge .= $condition['measures_judge'][$i];
//        }
//        $where_measures_judge .= ")";
//      }
//      // 業者
//      if (isset($condition['chk_company'])) {
//        $SC_JOIN = "JOIN ";
//        $where_chk_company = " AND main.chk_company IN (";
//        for ($i=0;$i<count($condition['chk_company']);$i++) {
//          if ($i>0) {
//            $where_chk_company .= ", ";
//          }
//          $where_chk_company .= pg_escape_literal($condition['chk_company'][$i]);
//        }
//        $where_chk_company .= ")";
//      }
//
//      // 点検日
//      /*** どちらも入っている場合 ***/
//      if (isset($condition['chk_dt_from']) && isset($condition['chk_dt_to'])) {
//        $SC_JOIN = "JOIN ";
//        // 大小関係
//        if (strtotime($condition['chk_dt_from']) < strtotime($condition['chk_dt_to'])) {
//          // fromの方が小さい
//          $where_chk_dt = " AND (CAST(".pg_escape_literal($condition['chk_dt_from'])." AS DATE) <= CAST(main.chk_dt AS DATE) AND CAST(main.chk_dt AS DATE) <= CAST(".pg_escape_literal($condition['chk_dt_to'])." AS DATE)) ";
//        } else if (strtotime($condition['chk_dt_from']) === strtotime($condition['chk_dt_to'])) {
//          // 同じ場合
//          $where_chk_dt = " AND CAST(main.chk_dt AS DATE) = CAST(".pg_escape_literal($condition['chk_dt_from'])." AS DATE) ";
//        } else {
//          // fromの方が大きい
//          $where_chk_dt = " AND (CAST(".pg_escape_literal($condition['chk_dt_to'])." AS DATE) <= CAST(main.chk_dt AS DATE) AND CAST(main.chk_dt AS DATE) <= CAST(".pg_escape_literal($condition['chk_dt_from'])." AS DATE)) ";
//        }
//        /*** どちらも入っていない ***/
//      } else if (!isset($condition['chk_dt_from']) && !isset($condition['chk_dt_to'])) {
//        /*** FROMのみ入っている場合 ***/
//      } else if (!isset($condition['chk_dt_to'])) {
//        $SC_JOIN = "JOIN ";
//        $where_chk_dt = " AND CAST(".pg_escape_literal($condition['chk_dt_from'])." AS DATE) <= CAST(main.chk_dt AS DATE) ";
//        /*** TOのみ入っている場合 ***/
//      } else {
//        $SC_JOIN = "JOIN ";
//        $where_chk_dt = " AND CAST(main.chk_dt AS DATE) <= CAST(".pg_escape_literal($condition['chk_dt_to'])." AS DATE) ";
//      }
//
//      // 調査日
//      /*** どちらも入っている場合 ***/
//      if (isset($condition['investigate_dt_from']) && isset($condition['investigate_dt_to'])) {
//        $SC_JOIN = "JOIN ";
//        // 大小関係
//        if (strtotime($condition['investigate_dt_from']) < strtotime($condition['investigate_dt_to'])) {
//          // fromの方が小さい
//          $where_investigate_dt = " AND (CAST(".pg_escape_literal($condition['investigate_dt_from'])." AS DATE) <= CAST(main.investigate_dt AS DATE) AND CAST(main.investigate_dt AS DATE) <= CAST(".pg_escape_literal($condition['investigate_dt_to'])." AS DATE)) ";
//        } else if (strtotime($condition['investigate_dt_from']) === strtotime($condition['investigate_dt_to'])) {
//          // 同じ場合
//          $where_investigate_dt = " AND CAST(main.investigate_dt AS DATE) = CAST(".pg_escape_literal($condition['investigate_dt_from'])." AS DATE) ";
//        } else {
//          // fromの方が大きい
//          $where_investigate_dt = " AND (CAST(".pg_escape_literal($condition['investigate_dt_to'])." AS DATE) <= CAST(main.investigate_dt AS DATE) AND CAST(main.investigate_dt AS DATE) <= CAST(".pg_escape_literal($condition['investigate_dt_from'])." AS DATE)) ";
//        }
//        /*** どちらも入っていない ***/
//      } else if (!isset($condition['investigate_dt_from']) && !isset($condition['investigate_dt_to'])) {
//        /*** FROMのみ入っている場合 ***/
//      } else if (!isset($condition['investigate_dt_to'])) {
//        $SC_JOIN = "JOIN ";
//        $where_investigate_dt = " AND CAST(".pg_escape_literal($condition['investigate_dt_from'])." AS DATE) <= CAST(main.investigate_dt AS DATE) ";
//        /*** TOのみ入っている場合 ***/
//      } else {
//        $SC_JOIN = "JOIN ";
//        $where_investigate_dt = " AND CAST(main.investigate_dt AS DATE) <= CAST(".pg_escape_literal($condition['investigate_dt_to'])." AS DATE) ";
//      }
//
//      // 点検対象年度
//      // 年度から該当日付を決定
//      $nendo_where="";
//      $nen_from="";
//      $nen_to="";
//      if (!isset($condition['target_nendo_from']) && !isset($condition['target_nendo_to'])) {
//        // 両方ない場合設定なし
//      }else{
//        $SC_JOIN = "JOIN ";
//        if (!isset($condition['target_nendo_from'])) {
//          // TOのみある場合
//          $nen_to=((int)$condition['target_nendo_to']+1)."-04-01";
//        }else if (!isset($condition['target_nendo_to'])) {
//          $nen_from=$condition['target_nendo_from']."-04-01";
//        }else if ($condition['target_nendo_from']==$condition['target_nendo_to']) {
//          // 同じ場合
//          $nen_from=$condition['target_nendo_from']."-04-01";
//          $nen_to=((int)$condition['target_nendo_from']+1)."-04-01";
//        } else if ((int)$condition['target_nendo_from'] < (int)$condition['target_nendo_to']) {
//          $nen_from=$condition['target_nendo_from']."-04-01";
//          $nen_to=((int)$condition['target_nendo_to']+1)."-04-01";
//        } else {
//          $nen_from=$condition['target_nendo_to']."-04-01";
//          $nen_to=((int)$condition['target_nendo_from']+1)."-04-01";
//        }
//      }
//      if ($nen_from ==="" && $nen_to ==="") {
//        // どっちも条件なし
//      } else if ($nen_from ==="") {
//        $where_target_nendo="AND CAST(main.target_dt AS DATE) < CAST(".pg_escape_literal($nen_to)." AS DATE) ";
//      } else if ($nen_to ==="") {
//        $where_target_nendo="AND CAST(".pg_escape_literal($nen_from)." AS DATE) <= CAST(main.target_dt AS DATE) ";
//      } else {
//        $where_target_nendo="AND (CAST(".pg_escape_literal($nen_from)." AS DATE) <= CAST(main.target_dt AS DATE) AND CAST(main.target_dt AS DATE) < CAST(".pg_escape_literal($nen_to)." AS DATE))";
//      }
//
//      $year = (int)date('Y');
//      $month = (int)date('m');
//      if (1<=$month && $month<=3) {
//        $nendo_now_from = pg_escape_literal(($year-1).'-04-01');
//        $nendo_now_to = pg_escape_literal($year.'-04-01');
//      }else{
//        $nendo_now_from = pg_escape_literal($year.'-04-01');
//        $nendo_now_to = pg_escape_literal(($year+1).'-04-01');
//      }
    }else{
      if (isset($condition['not_chk_year']) && $condition['not_chk_year'] >0) {
        //過去に点検していない年数
        $where_not_chk_year= <<<WHERE
          and (
            chk_mng_no is null
            OR rireki_no is null
            OR CAST((this_nendo_year - {$condition['not_chk_year']}) || '-04-01' AS DATE) > CAST(chk_dt AS DATE)
          )
WHERE;
      }
    }

    /*************************/
    /***   条件設定ここまで   ***/
    /*************************/

    $sql= <<<SQL
WITH this_nendo_table as (
  select
    case                                          --今年度の処理の取得
      when n.month <= 3
      then n.year - 1
      else n.year
      end as this_nendo_year
  from
    (
      select
        EXTRACT(MONTH FROM now()) as month
        , EXTRACT(YEAR FROM now()) as year
    ) n
)
,tmp1 as (
  SELECT
    sno
    , shisetsu_cd
    , shisetsu_ver
    , shisetsu_kbn
    , shisetsu_keishiki_cd
    , rosen_cd
    , shityouson
    , azaban
    , dogen_cd
    , syucchoujo_cd
    , secchi
    , sp
    , lr
    , secchi_yyyy
    , this_nendo_table.this_nendo_year
    FROM
    rfs_m_shisetsu
    CROSS JOIN this_nendo_table
  WHERE
    (shisetsu_keishiki_cd <> 10 OR shisetsu_keishiki_cd is NULL)
    AND
    (haishi is null OR haishi = '')
    $where_dogen_cd
    $where_syucchoujo_cd
    $where_shisetsu_cd
    $where_secchi
    $where_sp
    $where_shityouson
    $where_azaban
    $where_shisetsu_kbn
    $where_rosen
)
, tmp2 as (
  -- shisetsu_verの最大値で絞り込み
  select
    s.*
  from
    tmp1 as s JOIN (
      SELECT
        shisetsu_cd
        , max(shisetsu_ver) shisetsu_ver
      FROM
        tmp1
      GROUP BY
        shisetsu_cd
    ) s2
      ON s.shisetsu_cd = s2.shisetsu_cd
      AND s.shisetsu_ver = s2.shisetsu_ver
)
, tmp3 as (
  -- rfs_m_bousetsusaku_shichuのjoin
  select
    s.*
  ,0 as struct_idx
  from
    tmp2 s
  where
    s.shisetsu_kbn <> 4
  union all
  select
    s.*
    , b.struct_idx
  from
    tmp2 s
    join (
      SELECT
        b_tmp.sno
        , b_tmp.struct_idx
      FROM
        rfs_m_bousetsusaku_shichu b_tmp
    where
      struct_idx <> 0
    ) b
      on s.sno = b.sno
  where
    s.shisetsu_kbn = 4
)
, tmp4 as (
  -- rfs_chk_mainのjoin
  select
    s.*
    , c.chk_mng_no
    , c.chk_times
    , c.target_dt
    , c.busyo_cd
    , c.kumiai_cd
  from
    tmp3 as s
    LEFT JOIN rfs_t_chk_main c
      on s.sno = c.sno
      and s.struct_idx = c.struct_idx
  WHERE
    TRUE
    $where_target_nendo
)
, tmp4_1 as (
  --chk_timesの絞り込みも行う
  SELECT
    tmp4.*
  FROM
    tmp4
    INNER JOIN (
      SELECT
        sno
        , struct_idx
        , max(chk_times) chk_times
      FROM
        tmp4
      GROUP BY
        sno
        , struct_idx
    ) c2
      ON tmp4.sno = c2.sno
      AND tmp4.struct_idx = c2.struct_idx
      AND tmp4.chk_times = c2.chk_times
)
, tmp5 as (
  -- rfs_t_chk_huzokubutsuのjoin , rireki_noの最大値の絞り込みを行う。
  select
    s_c.*
    , h.rireki_no
    , h.chk_dt
    , h.chk_company
    , h.investigate_dt
    , COALESCE(h.phase,-1) phase
    , h.check_shisetsu_judge
    , h.measures_shisetsu_judge
  from
    tmp4_1 as s_c
    LEFT JOIN (
      SELECT
        h1.*
      FROM
        rfs_t_chk_huzokubutsu h1 JOIN (
          SELECT
            chk_mng_no
            , max(rireki_no) rireki_no
          FROM
            rfs_t_chk_huzokubutsu
          GROUP BY
            chk_mng_no
        ) h2
          ON h1.chk_mng_no = h2.chk_mng_no
          AND h1.rireki_no = h2.rireki_no
      WHERE
        TRUE
    ) h
      ON s_c.chk_mng_no = h.chk_mng_no
)
, tmp6 as (
  select
    *
  from
    tmp5
  union all
  select
    -- 点検していないものの抽出
    tmp4.*
    , null rireki_no
    , null chk_dt
    , null chk_company
    , null investigate_dt
    , - 1 phase
    , null check_shisetsu_judge
    , null measures_shisetsu_judge
  from
    tmp4
  where
    chk_mng_no is null
)
--, tmp as (select * from tmp6)
, tmp as (
select
    tmp6.*
  , b.comp_flg
from
  tmp6
  left join rfs_t_chk_bousetsusaku b
    on tmp6.chk_mng_no = b.chk_mng_no_struct
)
SELECT
  *
FROM
  (
    SELECT
      tmp.sno
      , CASE
        WHEN tmp.chk_mng_no IS NULL
        OR tmp.phase = 5
        THEN 0
        ELSE 1
        END target
      , CASE
        WHEN tmp.chk_mng_no IS NULL
        OR tmp.phase = 5
        THEN 0
        ELSE 1
        END input_target
      , CASE
        WHEN rireki_no IS NULL
        THEN 0
        ELSE 1
        END exist_chk
      , tmp.shisetsu_kbn
      , sk.shisetsu_kbn_nm
      , tmp.shisetsu_keishiki_cd
      , skei.shisetsu_keishiki_nm
      , tmp.shisetsu_cd
      , tmp.rosen_cd
      , r.rosen_nm
      , tmp.sp
      , (
        COALESCE(tmp.shityouson, '') || COALESCE(tmp.azaban, '')
      ) as address
      , tmp.secchi
      , tmp.secchi_yyyy
      , tmp.lr
      , CASE
        WHEN tmp.lr = 0
        THEN 'L'
        WHEN tmp.lr = 1
        THEN 'R'
        WHEN tmp.lr = 2
        THEN 'C'
        WHEN tmp.lr = 3
        THEN 'LR'
        END lr_str
      , tmp.dogen_cd
      , tmp.syucchoujo_cd
      , tmp.chk_mng_no
      , tmp.chk_times
      , tmp.struct_idx
      , tmp.target_dt
      , SUBSTR(TO_CHAR(tmp.target_dt, 'YYYY-MM-DD'), 1, 10) target_dt_ymd
      , v_wareki_seireki_nendo.wareki || '度' nendo_wareki
      --, wareki_to_char(tmp.target_dt, 'GGLL年度') nendo_wareki
      -- , CASE
      --   WHEN CAST(
      --     SUBSTR(TO_CHAR(tmp.target_dt, 'YYYY-MM-DD'), 6, 2) as int
      --   ) > 3
      --   THEN CAST(
      --     SUBSTR(TO_CHAR(tmp.target_dt, 'YYYY-MM-DD'), 1, 4) as int
      --   ) - 1988 || '年度'
      --   ELSE CAST(
      --     SUBSTR(TO_CHAR(tmp.target_dt, 'YYYY-MM-DD'), 1, 4) as int
      --   ) - 1989 || '年度'
      --   END nendo_wareki
      , tmp.busyo_cd
      , tmp.kumiai_cd
      , tmp.rireki_no
      , tmp.chk_dt
      , tmp.chk_company
      , tmp.investigate_dt
      , tmp.phase
      , p.phase_str
      , tmp.check_shisetsu_judge
      , tmp.measures_shisetsu_judge
      --, null comp_flg
      -- ストック点検は完了フラグがない可能性があるので1にしておく
      , CASE
        WHEN tmp.chk_times = 0
        THEN 1
        ELSE tmp.comp_flg
        END comp_flg
      , this_nendo_year
    FROM
      tmp
      LEFT JOIN rfs_m_shisetsu_kbn sk
        ON tmp.shisetsu_kbn = sk.shisetsu_kbn
      LEFT JOIN rfs_m_shisetsu_keishiki skei
        ON tmp.shisetsu_kbn = skei.shisetsu_kbn
        AND tmp.shisetsu_keishiki_cd = skei.shisetsu_keishiki_cd
      LEFT JOIN rfs_m_rosen r
        ON tmp.rosen_cd = r.rosen_cd
      LEFT JOIN rfs_m_phase p
        ON tmp.phase = p.phase
      LEFT JOIN v_wareki_seireki AS v_wareki_seireki_nendo 
        ON v_wareki_seireki_nendo.seireki = EXTRACT( 
          YEAR 
          FROM
            tmp.target_dt ::TIMESTAMP - INTERVAL '3 month'
        ) 

  ) main
WHERE
  TRUE
  $where_target_nendo
  $where_not_chk_year
  $where_phase
ORDER BY
  shisetsu_kbn
  , sno
  , struct_idx
  , rosen_cd

SQL;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
//    log_message('debug', "sql=$sql");
//    $r = print_r($result, true);
//    log_message('debug', "result=$r");

    return $result;
  }

  /**
   *  点検施設保存処理
   *
   *  点検施設の登録/削除を行う
   *
   *  元々点検対象じゃない施設
   *    rfs_t_chk_mainに登録を行う
   *  追加処理を行う。
   *
   *  元々点検対象
   *    rfs_t_chk_mainから削除する
   *  点検データがぶら下がっている場合は削除しない！
   *  この画面に来る前にチェックしているが、
   *  登録時にもチェックする
   *
   *  引数:$post
   *          入力内容
   *
   */
  public function rgstRafTarget($post) {
    log_message('debug', 'rgstRafTarget');

    $data=$post['data'];

    // トランザクション
    $this->DB_rfs->trans_start();

    for ($i=0;$i<count($data);$i++) {
      // 登録内容と入力内容をチェック
      if ($data[$i]['target']==$data[$i]['input_target']) {
        continue;
      }
      // 登録内容が点検対象施設
      if ($data[$i]['target'] == 1) {
        // 削除処理
        $this->delChkMain($data[$i]);
        if ($data[$i]['shisetsu_kbn']==4) {
          // 他のデータをチェック
          $this->delParent($data[$i]);
        }
      } else {
        // 登録処理
        $this->insChkMain($data[$i]);
      }
    }

    // 確定
    $this->DB_rfs->trans_complete();
    return;
  }

  // rfs_t_chk_mainから該当レコードを削除する
  protected function delChkMain($deldata) {
    log_message('debug', 'delChkMain');

    // キー取得
    $chk_mng_no = $deldata['chk_mng_no'];
    // 附属物データの取得
    $huzokubutsu_num = $this->getHuzokubutsuNum($chk_mng_no);
    if ($huzokubutsu_num > 0) {
      // 処理しない
      return;
    }
    // 削除
    $this->delChkMainDetail($chk_mng_no);
  }

  /***
   * 同一施設、同一点検回数の子データが無い場合は親も削除
   ***/
  protected function delParent($data) {
    // 子データ数取得
    $cnt = $this->getSameChktimesChildCnt($data);
    if ($cnt>0) {
      return;
    }
    $sno=$data['sno'];
    $chk_times=$data['chk_times'];
    // 親の削除
    $sql= <<<EOF
        DELETE FROM 
          rfs_t_chk_main
        WHERE
        sno = $sno 
        AND chk_times = $chk_times 
        AND struct_idx = 0;
EOF;
    //log_message('debug', "sql=$sql");
    $query = $this->DB_rfs->query($sql);

  }

  /***
   * 同一施設、同一点検回数の子データ数をカウントする
   * 
   ***/
  protected function getSameChktimesChildCnt($data) {
    $sno=$data['sno'];
    $chk_times=$data['chk_times'];
    $sql=<<<SQL
      SELECT
          count(*) cnt 
      FROM
        rfs_t_chk_main 
      WHERE
        sno = $sno 
        AND chk_times = $chk_times 
        AND struct_idx > 0;
SQL;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    /*
    log_message('debug', "sql=$sql");
    log_message('debug', "result=".print_r($result, true));
    */
    return $result[0]['cnt'];
  }

  // rfs_t_chk_mainから該当レコードを追加する
  protected function insChkMain($insdata) {
    log_message('debug', 'insChkMain');

/*
    $r=print_r($insdata, true);
    log_message('debug', "insdata------>".$r."\n");
*/

    $sno=$insdata['sno']; // sno
    $shisetsu_kbn=$insdata['shisetsu_kbn']; // 施設区分
    $chk_times; // 点検回数

    // 防雪柵の場合と防雪柵以外で登録が異なる
    if ($shisetsu_kbn==4) {
      // 防雪柵の場合
      // 防雪柵マスタ取得
      $bs_shichu_num = $this->getBsShichuNum($sno); // マスタ
      $bs_chkMainLatest = $this->getLatestChkMain($sno); // 直近の点検データ
      $struct_idx=$insdata['struct_idx'];
/*
      $r=print_r($bs_shichu_num, true);
      log_message('debug', "マスタの支柱インデックス数------>".$r."\n");
      $r=print_r($bs_chkMainLatest, true);
      log_message('debug', "直近点検対象データ------>".$r."\n");
*/
      // 親の登録可否
      if ($bs_chkMainLatest) {

        // チェックされているんだから、登録は可能と考える。
        // 加算したときの親データが在るか無いかだけを考慮
        $disp_chk_times=$insdata['chk_times'];  // 表示しているデータの点検回数
        $new_chk_times = $insdata['chk_times'] + 1;

        // 20180227 hirano 親データを作成する条件を変更
        // 直近のchk_mainの点検回数と表示データの点検回数を比較し、
        // 同じ場合は、親も作成。
        // 次の点検回数がトランザクション内でも追加されていると加算後の結果となる
        if ($disp_chk_times==(int)$bs_chkMainLatest[0]['chk_times']) {
          // DEL 同じ場合は点検回数をインクリメントし親データも作成
          // if ($bs_shichu_num == count($bs_chkMainLatest)) {
          $chk_times=(int)$bs_chkMainLatest[0]['chk_times']+1;
          // 親の登録
          $this->insChkMainItem($sno, $chk_times, 0);
        }else{
          // 異なる=子データの点検対象登録
          // 自分自身がいるかチェック
          $exist=false;
          // 親除く
          for ($i=1;$i<count($bs_chkMainLatest);$i++) {
            $item=$bs_chkMainLatest[$i];
            if ($item['struct_idx']==$struct_idx) {
              $exist=true;
              break;
            }
          }
          // 全ての子データが完了していない場合どーする？→仕様決定
          if ($exist==true) {
            /***************************************************
             * 自分自身が存在した場合
             * 入力チェックで自分自身はチェックが外せないようにする
             * 制御に決定
             * →ここのロジックは通らない
             ***************************************************/
          }else{
            // 自分自身がいない場合は点検回数を親の点検回数にする
            $chk_times=(int)$bs_chkMainLatest[0]['chk_times'];
            // 親データの登録年度を今に更新する
            $this->updParentTargetDt($sno, $chk_times);
          }
        }
      } else {
        // 初登録=親のみ登録
        $chk_times=1;
        $this->insChkMainItem($sno, 1, 0);
      }
    }else{
      // 防雪柵以外
      $struct_idx=0;
      // 過去最新の点検対象登録データを取得
      $chkMainLatest = $this->getLatestChkMain($sno);
      if ($chkMainLatest) {
        //log_message('debug', '直近あり');
        $chk_times=(int)$chkMainLatest[0]['chk_times']+1;
      }else{
        //log_message('debug', '直近なし');
        // 初登録
        $chk_times=1;
      }
    }
    $this->insChkMainItem($sno, $chk_times, $struct_idx);
  }

  // rfs_t_chk_mainに追加処理を行う
  protected function insChkMainItem($sno, $chk_times, $struct_idx) {
    log_message('debug', 'insChkMainItem');
    $sql= <<<EOF
INSERT
INTO rfs_t_chk_main(
  sno
  , chk_times
  , struct_idx
  , target_dt
  , busyo_cd
  , kumiai_cd
)
VALUES ($sno, $chk_times, $struct_idx, now(), NULL, NULL);
EOF;
    $query = $this->DB_rfs->query($sql);
  }

  // 附属物点検データがあるかチェックを行う。
  // rfs_t_chk_huzokubutsuのカウントを取得
  protected function getHuzokubutsuNum($chk_mng_no) {
    log_message('debug', 'getHuzokubutsuNum');
    $sql= <<<EOF
SELECT
    count(*) cnt
FROM
  rfs_t_chk_huzokubutsu
WHERE
  chk_mng_no = $chk_mng_no
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    //$r = print_r($result, true);
    //        log_message('debug', "sql=$sql");
    //log_message('debug', "result=$r");
    return $result[0]['cnt'];
  }

  // 附属物点検データを削除する
  // rfs_t_chk_mainを削除
  protected function delChkMainDetail($chk_mng_no) {
    log_message('debug', 'delChkMainDetail');
    $sql= <<<EOF
DELETE FROM
  rfs_t_chk_main
WHERE
  chk_mng_no = $chk_mng_no
EOF;
    $query = $this->DB_rfs->query($sql);
  }

  // 直近の附属物点検データを取得する
  protected function getLatestChkMain($sno) {
    log_message('debug', 'getLatestChkMain');
    $sql= <<<EOF
SELECT
    *
FROM
  rfs_t_chk_main
WHERE
  sno = $sno
  AND chk_times = (
    SELECT
        max(chk_times)
    FROM
      rfs_t_chk_main
    WHERE
      sno = $sno
  )
ORDER BY
  struct_idx
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    //log_message('debug', "sql=$sql");
    //$r = print_r($result, true);
    //log_message('debug', "result=$r");
    return $result;
  }

  // 直近の附属物点検データを取得する
  protected function getBsShichuNum($sno) {
    log_message('debug', 'getBsShichuNum');
    $sql= <<<EOF
SELECT
    count(*) cnt
FROM
  rfs_m_bousetsusaku_shichu
WHERE
  sno = $sno
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    //        log_message('debug', "sql=$sql");
    //$r = print_r($result, true);
    //log_message('debug', "result=$r");
    return $result[0]['cnt'];
  }

  /***
   *
   * 防雪柵の親データの年度調整
   *    親データを新しい年度(今)に更新する。
   *    集計で常に防雪柵を新しい年度の点検として集計するため
   *
   * 引数:$sno,$chk_times
   *
   ***/
  protected function updParentTargetDt($sno, $chk_times) {
    log_message('debug', 'updParentTargetDt');
    $sql= <<<EOF
UPDATE rfs_t_chk_main
SET
   target_dt = now()
WHERE
  sno = $sno
  AND
  chk_times = $chk_times
  AND
  struct_idx = 0
EOF;
    $query = $this->DB_rfs->query($sql);
  }

}
