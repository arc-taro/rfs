<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 道路施設管理システムトップに関するモデル
 * 返却は配列
 *
 * @access public
 * @package Model
 */
class RafTopModel extends CI_Model {

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

  public function refreshSumChkHuzokubutsu(){
    $sql = <<<SQL
      refresh materialized view rfs_mv_phase_sum;
SQL;
    // $this->DB_rfs->query($sql);
  }
  /**
     *
     * 附属物点検システム集計の取得
     *
     *  附属物点検の集計【点検未実施・点検中・スクリーニング・詳細調査・点検完了】を
     *  取得する関数
     *
     * 引数：$dogen_cd 建管コード
     *      $syucchoujo_cd 出張所コード
     *      $from 年度From
     *      $to 年度To
     *      $shisetsu_kbn 施設区分
     */
  public function getSumChkHuzokubutsu($dogen_cd, $syucchoujo_cd, $from, $to, $shisetsu_kbn) {
    log_message('debug', 'getSumChkHuzokubutsu');

    // 建管は必ずある
    // 出張所は0の場合があるので0の場合はセットしない
    $where_syucchoujo = "";
    if ($syucchoujo_cd!=0) {
      $where_syucchoujo = "AND syucchoujo_cd = ".$syucchoujo_cd." ";
    }
    // 年度から該当日付を決定
    $nendo_where="";
    $nen_from="";
    $nen_to="";
    if ($from === "" && $to === "") {
      // 両方ない場合設定なし
    }else{
      if ($from=="") {
        // TOのみある場合
        $nen_to=($to+1)."-04-01";
      }else if ($to=="") {
        $nen_from=$from."-04-01";
      }else if ($from==$to) {
        // 同じ場合
        $nen_from=$from."-04-01";
        $nen_to=($from+1)."-04-01";
      } else if ($from < $to) {
        $nen_from=$from."-04-01";
        $nen_to=($to+1)."-04-01";
      } else {
        $nen_from=$to."-04-01";
        $nen_to=($from+1)."-04-01";
      }
    }
    if ($nen_from ==="" && $nen_to ==="") {
      // どっちも条件なし
    } else if ($nen_from ==="") {
      //$nendo_where="AND CAST(target_yyyymm||'-01' AS DATE) < CAST(".pg_escape_literal($nen_to)." AS DATE) ";
      $nendo_where="AND CAST(target_dt AS DATE) < CAST(".pg_escape_literal($nen_to)." AS DATE) ";
    } else if ($nen_to ==="") {
      //$nendo_where="AND CAST(".pg_escape_literal($nen_from)." AS DATE) <= CAST(target_yyyymm||'-01' AS DATE) ";
      $nendo_where="AND CAST(".pg_escape_literal($nen_from)." AS DATE) <= CAST(target_dt AS DATE) ";
    } else {
//      $nendo_where="AND (CAST(".pg_escape_literal($nen_from)." AS DATE) <= CAST(target_yyyymm||'-01' AS DATE) AND CAST(target_yyyymm||'-01' AS DATE) < CAST(".pg_escape_literal($nen_to)." AS DATE))";
      $nendo_where="AND (CAST(".pg_escape_literal($nen_from)." AS DATE) <= CAST(target_dt AS DATE) AND CAST(target_dt AS DATE) < CAST(".pg_escape_literal($nen_to)." AS DATE))";
    }

    $sql = <<<SQL
with tmp1 as (
  select
    s.sno
    , s.shisetsu_kbn
    , c.chk_mng_no
    , c.target_dt
    , c.chk_times
    , COALESCE(h.rireki_no, - 1) rireki_no
    , COALESCE(h.phase, - 1) phase
  from
    rfs_m_shisetsu s
    INNER JOIN rfs_t_chk_main c
      ON s.sno = c.sno
    LEFT JOIN rfs_t_chk_huzokubutsu h
      ON c.chk_mng_no = h.chk_mng_no
  where
    (
      (trim(s.haishi) = '' or s.haishi is null)
      and s.haishi_yyyy is null
    )
    AND c.struct_idx = 0
    AND shisetsu_kbn = $shisetsu_kbn
    AND dogen_cd = $dogen_cd
    $where_syucchoujo
    $nendo_where
)
, tmp2 as( -- chk_timesの最大値で絞り込み
  select
    sno
    , shisetsu_kbn
    , chk_mng_no
    , rireki_no
    , phase
    , chk_times
  from
    tmp1
    natural join (
      select
        sno
        , max(chk_times) chk_times
      from
        tmp1
      group by
        sno
    ) t2
)
, tmp3 as( -- rireki_noの最大値で絞り込み
  select
    *
  from
    tmp2
    natural join (
      select
        sno
        , max(rireki_no) rireki_no
      from
        tmp2
      group by
        sno
    ) t2
)

select
  $shisetsu_kbn as shisetsu_kbn
  , rfs_m_phase_sum.phase
  , rfs_m_phase_sum.phase_str
  , COALESCE(cnt, 0) as cnt
from
  rfs_m_phase_sum
  LEFT JOIN (
    select --phaseの個別集計
      shisetsu_kbn
      , phase
      , count(*) cnt
    from
      tmp3
    group by
      shisetsu_kbn
      , phase
    UNION ALL
    select --合計の集計
      shisetsu_kbn
      , 999 as phase
      , count(*) cnt
    from
      tmp3
    group by
      shisetsu_kbn
  ) goukei
    on rfs_m_phase_sum.phase = goukei.phase;
SQL;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

/*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/

    return $result;
  }


  public function refreshSumSochi(){
    $sql = <<<SQL
      refresh materialized view rfs_mv_sochi_sum;
SQL;
    $this->DB_rfs->query($sql);
  }
  /**
     *
     * 附属物点検システム集計の取得
     *
     *  措置の集計【措置不要・措置・措置完了】を
     *  取得する関数
     *
     *  集計の対象は、点検入力を確定保存した後の点検とする。
     *  （点検中はカウントしない）
     *
     * 引数：$dogen_cd 建管コード
     *      $syucchoujo_cd 出張所コード
     *      $from 年度From
     *      $to 年度To
     *      $shisetsu_kbn 施設区分
     */
  public function getSumSochi($dogen_cd, $syucchoujo_cd, $from, $to, $shisetsu_kbn) {
    log_message('debug', 'getSumChkHuzokubutsu');

    // 建管は必ずある
    // 出張所は0の場合があるので0の場合はセットしない
    $where_syucchoujo = "";
    if ($syucchoujo_cd!=0) {
      $where_syucchoujo = "AND syucchoujo_cd = ".$syucchoujo_cd." ";
    }
    // 年度から該当日付を決定
    $nendo_where="";
    if ($from === "" && $to === "") {
      // 両方ない場合設定なし
    }else{
      if ($from==$to || $to == "") {
        // 同じ場合またはTOが無い場合
        $nen_from=$from."-04-01";
        $nen_to=($from+1)."-04-01";
      } else if ($from=="") {
        // TOのみある場合
        $nen_from=$to."-04-01";
        $nen_to=($to+1)."-04-01";
      } else if ($from < $to) {
        $nen_from=$from."-04-01";
        $nen_to=($to+1)."-04-01";
      } else {
        $nen_from=$to."-04-01";
        $nen_to=($from+1)."-04-01";
      }
      $nendo_where="AND ".pg_escape_literal($nen_from)." <= c.target_dt AND c.target_dt < ".pg_escape_literal($nen_to)." ";
//      $nendo_where="AND (CAST(".pg_escape_literal($nen_from)." AS DATE) <= CAST(target_yyyymm||'-01' AS DATE) AND CAST(target_yyyymm||'-01' AS DATE) < CAST(".pg_escape_literal($nen_to)." AS DATE))";
    }
    $sql= <<<EOF
with tmp1 as (
  select
    s.sno
    , s.shisetsu_kbn
    , c.chk_mng_no
    , c.target_dt
    , c.chk_times
    , COALESCE(h.rireki_no, - 1) rireki_no
    , COALESCE(h.phase, - 1) phase
  , check_shisetsu_judge
  , measures_shisetsu_judge
  from
    rfs_m_shisetsu s
    INNER JOIN rfs_t_chk_main c
      ON s.sno = c.sno
    LEFT JOIN rfs_t_chk_huzokubutsu h
      ON c.chk_mng_no = h.chk_mng_no
  where
    (
      (trim(s.haishi) = '' or s.haishi is null)
      and s.haishi_yyyy is null
    )
    AND c.struct_idx = 0
    AND shisetsu_kbn = $shisetsu_kbn
    AND dogen_cd = $dogen_cd
    $where_syucchoujo
    $nendo_where
)
, tmp2 as (
  -- chk_timesの最大値で絞り込み
  select
  *
  from
    tmp1
    natural join (
      select
        sno
        , max(chk_times) chk_times
      from
        tmp1
      group by
        sno
    ) t2
)
, tmp3 as (
  -- rireki_noの最大値で絞り込み
  select
    *
  from
    tmp2
    natural join (
      select
        sno
        , max(rireki_no) rireki_no
      from
        tmp2
      group by
        sno
    ) t2
)
, tmp4 as (
  -- idxをつける
  SELECT
    --idxを作成する
    case
      when check_shisetsu_judge <= 2
      then 1
      when (
        check_shisetsu_judge >= 3
        and measures_shisetsu_judge >= 3
      )
      then 2
      when (
        check_shisetsu_judge >= 3
        AND measures_shisetsu_judge <= 2
      )
      then 3
      end idx
    , tmp3.*
  FROM
    tmp3
  WHERE
    phase >= 1
)
, judge4_not_sochi AS ( -- 健全性Ⅳで措置していないものの件数
  SELECT
    *
  FROM
    tmp4
  WHERE
    idx = 2
    AND check_shisetsu_judge = 4
)
select
  $shisetsu_kbn as shisetsu_kbn
  , rfs_m_sochi_sum_idx.idx
  , rfs_m_sochi_sum_idx.sochi_str
  , COALESCE(sochi_cnt, 0) as sochi_cnt
  , (SELECT count(*) FROM judge4_not_sochi) judge4_cnt
from
  rfs_m_sochi_sum_idx
  LEFT JOIN (
    select --phaseの個別集計
      shisetsu_kbn
      , idx
      , count(*) sochi_cnt
    from
      tmp4
    group by
      shisetsu_kbn
      , idx
    UNION ALL
    select --合計の集計
      shisetsu_kbn
      , 4 as idx
      , count(*) sochi_cnt
    from
      tmp4
    group by
      shisetsu_kbn
  ) goukei
    on rfs_m_sochi_sum_idx.idx = goukei.idx
order by idx
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
/*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result;

  }

    /**
     * 施設区分取得（選択プルダウン用）
     *
     * @param   道路附属物点検DBコネクション
     * @return array
     *         array['shisetsu_kbn_row'] : <br>
     */
    public function getShisetsuKbnFormulti() {
      log_message('debug', __METHOD__);
      $sql= <<<EOF
select
  jsonb_set(
  '{}'
  , '{shisetsu_kbn_info}'
  , jsonb_agg(to_jsonb(s) - 'sort_no' )
) AS shisetsu_kbn_row
from
(select shisetsu_kbn as id, shisetsu_kbn_nm as label, shisetsu_kbn, sort_no from rfs_m_shisetsu_kbn order by sort_no) s
EOF;
      $query = $this->DB_rfs->query($sql);
      $result = $query->result('array');
      return $result;
  }
}
