<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require("SchMst.php");

/**
 * 施設の検索を行う
 * 返却は配列
 *
 * @access public
 * @package Model
 */
class SchShisetsu extends CI_Model {

    /**
    * コンストラクタ
    *
    * model SchCheckを初期化する。
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
     * 施設情報の取得
     *
     * @param $post setクエリ <br>
     *        $post['srch']             検索条件JSON
     * @return array
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     */
    public function get_srch_shisetsu($post) {
        log_message('debug', 'get_srch_shisetsu');

        $DB_rfs=$this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        // 建管コードと出張所コード
        $where_syucchoujo="";
        $dogen_cd=pg_escape_string($post['dogen_cd']);
        if(isset($post['syucchoujo_cd']) && $post['syucchoujo_cd'] !=0){
          $syucchoujo_cd=pg_escape_string($post['syucchoujo_cd']);
          $where_syucchoujo = "AND syucchoujo_cd = ".$syucchoujo_cd." ";
        }

        // 検索条件の有無
//        $is_search_condition = false;
        $and = " and ";

        // 施設コード
        if(isset($post['srch']['shisetsu_cd'])) {
            $shisetsu_cd = pg_escape_literal('%'.$post['srch']['shisetsu_cd'].'%');
        }

        // 施設バージョン
        if(isset($post['srch']['shisetsu_ver'])) {
            $shisetsu_ver = $post['srch']['shisetsu_ver'];
        }

        // 路線番号
        $rosen_cd = (isset($post['srch']['rosen_cd']))
          ? $post['srch']['rosen_cd']
          : '';
        log_message('info', __CLASS__ . '::' .__FUNCTION__ . '/rosen_cd: ' . $rosen_cd);

        // 建管
        // 建管については、選択されている建管のため、
        // このメソッド上部でセットしている建管でOK
        // また入っていないことがあり得ない
/*
        if(isset($post['srch']['dogen_cd'])) {
            $dogen_cd = pg_escape_literal($post['srch']['dogen_cd']);
        }
*/
      // 出張所コード
      // 出張所については、選択されている出張所のため、
      // このメソッド上部でセットしている出張所でOK
      // また入っていないことがあり得ないが
      // 全ての場合は０がセットされている。
      // SQLセット時にその判定を行っている
      /*
        if(isset($post['srch']['syucchoujo_cd'])) {
            $syucchoujo_cd = $post['srch']['syucchoujo_cd'];
        }
*/
        // 市町村
        $sicyouson = '';
        if(isset($post['srch']['sicyouson'])) {
            if($post['srch']['sicyouson']) {
                $sicyouson = pg_escape_literal('%'.$post['srch']['sicyouson'].'%');
            }
        }

        // 字番
        $azaban = '';
        if(isset($post['srch']['azaban'])) {
            if($post['srch']['azaban']) {
                $azaban = pg_escape_literal('%'.$post['srch']['azaban'].'%');
            }
        }

        // 設置年度
        $setti_nendo_front_sel;
        if(isset($post['srch']['setti_nendo_front_sel'])) {
            $setti_nendo_front_sel = intval($post['srch']['setti_nendo_front_sel']);
        }
        $setti_nendo_back_sel;
        if(isset($post['srch']['setti_nendo_back_sel'])) {
            $setti_nendo_back_sel = intval($post['srch']['setti_nendo_back_sel']);
        }

        // 設置年度に不明(null)を含める
        $include_secchi_null = 0;
        if(isset($post['srch']['include_secchi_null'])) {
            $include_secchi_null = boolval($post['srch']['include_secchi_null']);
        }

        // 点検実施年月日
        $chk_dt_front;
        if(isset($post['srch']['chk_dt_front'])) {
            $chk_dt_front = date('Y-m-d', strtotime($post['srch']['chk_dt_front']));
            $chk_dt_front = pg_escape_literal($chk_dt_front);
        }
        $chk_dt_back;
        if(isset($post['srch']['chk_dt_back'])) {
            $chk_dt_back = date('Y-m-d', strtotime($post['srch']['chk_dt_back']));
            $chk_dt_back = pg_escape_literal($chk_dt_back);
        }

        // 調査実施年月日
        $measures_dt_front;
        if(isset($post['srch']['measures_dt_front'])) {
            $measures_dt_front = date('Y-m-d', strtotime($post['srch']['measures_dt_front']));
            $measures_dt_front = pg_escape_literal($measures_dt_front);
        }
        $measures_dt_back;
        if(isset($post['srch']['measures_dt_back'])) {
            $measures_dt_back = date('Y-m-d', strtotime($post['srch']['measures_dt_back']));
            $measures_dt_back = pg_escape_literal($measures_dt_back);
        }

        // 測点
        $sp_start;
        if(isset($post['srch']['sp_start'])) {
            $sp_start = intval($post['srch']['sp_start']);
        }
        $sp_end;
        if(isset($post['srch']['sp_end'])) {
            $sp_end = intval($post['srch']['sp_end']);
        }

        $shisetsu_kbn_dat_model = $post['srch']['shisetsu_kbn_dat_model'];
        $rosen_dat_model = $post['srch']['rosen_dat_model'];
        $phase_dat_model = $post['srch']['phase_dat_model'];
        $chk_judge_dat_model = $post['srch']['chk_judge_dat_model'];
        $measures_judge_dat_model = $post['srch']['measures_judge_dat_model'];
        $patrolin_gyousya_dat_model = $post['srch']['patrolin_gyousya_dat_model'];
        $patrolin_dat_model = $post['srch']['patrolin_dat_model'];
        $investigator_gyousya_dat_model = $post['srch']['investigator_gyousya_dat_model'];
        $investigator_dat_model = $post['srch']['investigator_dat_model'];
        $struct_idx_dat_model = $post['srch']['struct_idx_dat_model'];

        // 支柱インデックス
        $struct_idx_in = '';
        foreach($struct_idx_dat_model as $key => $struct_idx_dat) {
            $struct_idx_in .= $struct_idx_dat['struct_idx'] . ',';
            //log_message('debug', "★struct_idx_in : " . $struct_idx_in);
        }
        //log_message('debug', "★struct_idx_in : " . $struct_idx_in);
        if(strlen($struct_idx_in) != 0) {
            $struct_idx_in = substr($struct_idx_in, 0, strlen($struct_idx_in) - 1);
        }

        // 施設名
        $shisetsu_kbn_in = '';
        if ($rosen_cd === '') {
            // 路線別集計では区分による絞り込みをしない
            foreach ($shisetsu_kbn_dat_model as $key => $shisetsu_kbn_dat) {
                $shisetsu_kbn_in .= $shisetsu_kbn_dat['shisetsu_kbn'] . ',';
            }
            if (strlen($shisetsu_kbn_in) != 0) {
                $shisetsu_kbn_in = substr($shisetsu_kbn_in, 0, strlen($shisetsu_kbn_in) - 1);
            }
        }

        if(strlen($shisetsu_kbn_in) !=0) {
            $where_shisetsu_kbn= " AND s.shisetsu_kbn in ($shisetsu_kbn_in)";
        }else{
          $where_shisetsu_kbn="";
        }

        // 路線選択
        $rosen_cd_in = '';
        foreach($rosen_dat_model as $key => $rosen_dat) {
            $rosen_cd_in .= $rosen_dat['rosen_cd'] . ',';
        }
        if(strlen($rosen_cd_in) != 0) {
            $rosen_cd_in = substr($rosen_cd_in, 0, strlen($rosen_cd_in) - 1);
        }

        // 点検の実施状況
        $phase_in = '';
        if ($rosen_cd === '') {
            // 路線別集計では区分による絞り込みをしない
            foreach ($phase_dat_model as $key => $phase_dat) {
                $phase_in .= $phase_dat['phase'] . ',';
            }
            if (strlen($phase_in) != 0) {
                $phase_in = substr($phase_in, 0, strlen($phase_in) - 1);
            }
        } elseif ($rosen_cd > 0) {
            $phase_in = '-1, 5';
        }

        // phase条件を先につくる
        if($phase_in!=""){
          $phase_where = "AND phase in ($phase_in)";
        }else{
          $phase_where="";
        }

        // 健全性（点検時）の検索条件
        $mijisshi=false;
        $chk_shisetsu_judge_in = '';
        foreach($chk_judge_dat_model as $key => $chk_judge_dat) {
          if ($chk_judge_dat['shisetsu_judge']==0) {
            $mijisshi=true;
          } else {
            $chk_shisetsu_judge_in .= $chk_judge_dat['shisetsu_judge'] . ',';
          }
        }
        if(strlen($chk_shisetsu_judge_in) != 0) {
            $chk_shisetsu_judge_in = substr($chk_shisetsu_judge_in, 0, strlen($chk_shisetsu_judge_in) - 1);
        }

        // 健全性（措置後）の検索条件
        $measures_shisetsu_judge_in = '';
        foreach($measures_judge_dat_model as $key => $measures_judge_dat) {
            $measures_shisetsu_judge_in .= $measures_judge_dat['shisetsu_judge'] . ',';
        }
        if(strlen($measures_shisetsu_judge_in) != 0) {
            $measures_shisetsu_judge_in = substr($measures_shisetsu_judge_in, 0, strlen($measures_shisetsu_judge_in) - 1);
        }

        // 点検会社
        $patrolin_gyousya_label_in = '';
        foreach($patrolin_gyousya_dat_model as $key => $patrolin_gyousya_dat) {
            $patrolin_gyousya_label_in .= pg_escape_literal($patrolin_gyousya_dat['label']) . ',';
        }
        if(strlen($patrolin_gyousya_label_in) != 0) {
            $patrolin_gyousya_label_in = substr($patrolin_gyousya_label_in, 0, strlen($patrolin_gyousya_label_in) - 1);
        }

        // 点検員
        $patrolin_label_in = '';
        foreach($patrolin_dat_model as $key => $patrolin_dat) {
            $patrolin_label_in .= pg_escape_literal($patrolin_dat['label']) . ',';
        }
        if(strlen($patrolin_label_in) != 0) {
            $patrolin_label_in = substr($patrolin_label_in, 0, strlen($patrolin_label_in) - 1);
        }

        // 調査会社
        $investigator_gyousya_label_in = '';
        foreach($investigator_gyousya_dat_model as $key => $investigator_gyousya_dat) {
            $investigator_gyousya_label_in .= pg_escape_literal($investigator_gyousya_dat['label']) . ',';
        }
        if(strlen($investigator_gyousya_label_in) != 0) {
            $investigator_gyousya_label_in = substr($investigator_gyousya_label_in, 0, strlen($investigator_gyousya_label_in) - 1);
        }

        // 調査員
        $investigator_label_in = '';
        foreach($investigator_dat_model as $key => $investigator_dat) {
            $investigator_label_in .= pg_escape_literal($investigator_dat['label']) . ',';
        }
        if(strlen($investigator_label_in) != 0) {
            $investigator_label_in = substr($investigator_label_in, 0, strlen($investigator_label_in) - 1);
        }

        // 対象年度の条件を追加
        $target_nendo_from=isset($post['srch']['target_nendo_from'])?$post['srch']['target_nendo_from']:"";
        $target_nendo_to=isset($post['srch']['target_nendo_to'])?$post['srch']['target_nendo_to']:"";

        $nendo_where="";
        $nendo_where_tcm1="";
        if ($target_nendo_from === "" && $target_nendo_to === "") {
          // 両方ない場合設定なし
        }else{
          if ($target_nendo_from==$target_nendo_to || $target_nendo_to == "") {
            // 同じ場合またはTOが無い場合
            $nen_from=$target_nendo_from."-04-01";
            $nen_to=($target_nendo_from+1)."-04-01";
          } else if ($target_nendo_from=="") {
            // TOのみある場合
            $nen_from=$target_nendo_to."-04-01";
            $nen_to=($target_nendo_to+1)."-04-01";
          } else if ($target_nendo_from < $target_nendo_to) {
            // TOの方が大きい(正常)
            $nen_from=$target_nendo_from."-04-01";
            $nen_to=($target_nendo_to+1)."-04-01";
          } else {
            // TOの方が小さい
            $nen_from=$target_nendo_to."-04-01";
            $nen_to=($target_nendo_from+1)."-04-01";
          }
          $nendo_where="AND (".pg_escape_literal($nen_from)." <= c.target_dt AND c.target_dt < ".pg_escape_literal($nen_to).")";
          $nendo_where_tcm1="AND (".pg_escape_literal($nen_from)." <= tcm1.target_dt AND tcm1.target_dt < ".pg_escape_literal($nen_to).")";
        }

/*
      if(isset($syucchoujo_cd)) {
        if ($syucchoujo_cd==0) {
          if(isset($dogen_cd)) {
            $sql .= "    $and tmp.dogen_cd = $dogen_cd";
            $and = " and ";
          }
        }else{
          $sql .= "    $and tmp.syucchoujo_cd = $syucchoujo_cd";
          $and = " and ";
        }
      }
*/


      $sql = <<<SQL
with tmp1 as (
  select
    s.*
    , c.chk_mng_no
    , c.chk_times
    , c.target_dt
    , c.struct_idx
    , h.chk_company
    , h.chk_person
    , h.investigate_dt
    , h.investigate_company
    , h.investigate_person
    , h.check_shisetsu_judge
    , h.measures_shisetsu_judge
    , h.chk_dt
    , h.syoken
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
    AND s.dogen_cd = $dogen_cd
    $where_shisetsu_kbn
    $where_syucchoujo
    $nendo_where
)
, tmp2 as (
  -- max_measures_dtの結合
  select
    tmp1.*
    , union_tmp.max_measures_dt
  from
    tmp1
    LEFT JOIN (
      SELECT
        tk.chk_mng_no
        , max(measures_dt) as max_measures_dt
      FROM
        rfs_t_chk_tenken_kasyo tk JOIN rfs_t_chk_main c1
          ON tk.chk_mng_no = c1.chk_mng_no JOIN rfs_m_shisetsu s1
          ON s1.sno = c1.sno
      GROUP BY
        tk.chk_mng_no
    ) union_tmp
      ON tmp1.chk_mng_no = union_tmp.chk_mng_no
)
, tmp3 as(
 -- chk_timesの最大値で絞り込み
  select
    *
  from
    tmp2
    natural join (
      select
        sno
        , max(chk_times) chk_times
      from
        tmp2
      group by
        sno
    ) t2
)
, tmp4 as (
  -- rireki_noの最大値で絞り込み
  select
    *
  from
    tmp3
    natural join (
      select
        sno
        , max(rireki_no) rireki_no
      from
        tmp3
      group by
        sno
    ) t2
)
, tmp5 as (
  -- phaseの絞り込み
  SELECT
    *
  FROM
    tmp4
  WHERE
    true
    $phase_where
)
, tmp6 as (
  -- 防雪柵の点検phaseの取得
  select
    tmp5.*
    , rfs_t_chk_main.chk_mng_no as child_chk_mng_no
    , COALESCE(rfs_t_chk_huzokubutsu.phase, - 1) as child_phase
  from
    tmp5
    inner join rfs_m_bousetsusaku_shichu
      on tmp5.sno = rfs_m_bousetsusaku_shichu.sno
    inner join rfs_t_chk_main
      on tmp5.sno = rfs_t_chk_main.sno
      and tmp5.chk_times = rfs_t_chk_main.chk_times
      and rfs_m_bousetsusaku_shichu.struct_idx = rfs_t_chk_main.struct_idx
    left join rfs_t_chk_huzokubutsu
      on rfs_t_chk_main.chk_mng_no = rfs_t_chk_huzokubutsu.chk_mng_no
  where
    rfs_m_bousetsusaku_shichu.struct_idx <>  0

)
, tmp7 as (
  -- 各防雪柵の最大phaseを取る
  select
    tmp6.*
  from
    tmp6
    natural join (
      select
        child_chk_mng_no
        , max(child_phase) child_phase
      from
        tmp6
      group by
        child_chk_mng_no
    ) t
)
, tmp8 as (
  -- 防雪柵の最大最小phaseを取る
  select
    tmp5.*
    , t1.child_phase max_phase
    , t2.child_phase min_phase
  from
    tmp5
    left join (
      select
        sno
        , max(child_phase) child_phase
      from
        tmp7
      group by
        sno
    ) t1
      on tmp5.sno = t1.sno
    left join (
      select
        sno
        , min(child_phase) child_phase
      from
        tmp7
      group by
        sno
    ) t2
      on tmp5.sno = t2.sno
)
, tmp as (select * from tmp8)
, tmp_phase as (
  -- phase用tmp
  select
    phase
    , case
      when phase = 1
      then '点検中'
      else phase_str_large
      end phase_str_large
  from
    rfs_m_phase
  group by
    phase
    , phase_str_large
)
select
  jsonb_set(
    '{}'
    , '{sch_result}'
    , jsonb_agg(to_jsonb("sch_result"))
  ) as sch_result_row
from
  (
    select
      row_number() over (
        order by
          sk.sort_no
          , tmp.chk_mng_no is null asc
          , tmp.chk_mng_no desc
          , tmp.sno
      ) as seq_no
      , true as chkexcel
      , tmp.sno
      , tmp.chk_mng_no
      , TO_CHAR(tmp.target_dt, 'YYYY-MM-DD') target_dt
      , v_wareki_seireki_nendo.wareki_ryaku || '年' w_target_nendo
      , '' target_nendo
      , tmp.shisetsu_cd
      , tmp.shisetsu_ver
      , tmp.dogen_cd
      , tmp.lat
      , tmp.lon
      , d.dogen_mei
      , tmp.syucchoujo_cd
      , s.syucchoujo_mei
      , tmp.shisetsu_kbn
      , sk.shisetsu_kbn_nm
      , tmp.shisetsu_keishiki_cd
      , skei.shisetsu_keishiki_nm
      , tmp.chk_times
      , tmp.struct_idx
      , tmp.rosen_cd
      , COALESCE(r.rosen_nm, '') as rosen_nm      -- rosen_nmがnullなら空文字
      , tmp.sp
      , tmp.shityouson || tmp.azaban syozaichi
      , COALESCE(tmp.secchi, '') as secchi        -- secchiがnullなら空文字
      , tmp.secchi_yyyy
      , case
        when tmp.substitute_road = 0
        then '有'
        else '無'
        end substitute_road_str
      , case
        when tmp.emergency_road is null
        then '-'
        else '第' || tmp.emergency_road || '次'
        end emergency_road_str
      , case
        when tmp.motorway = 1
        then '一般道'
        else '自専道'
        end motorway_str
      , COALESCE(tmp.senyou, '-') as senyou       -- secchiがnullなら'-'
      , case
        when tmp.fukuin is null
        then '-'
        when tmp.fukuin = ''
        then '-'
        else tmp.fukuin || 'm'
        end fukuin
      , tmp.rireki_no
      , to_char(tmp.chk_dt, 'yyyy年MM月dd日') as chk_dt
      , COALESCE(tmp.chk_company, '-') as chk_company -- tch.chk_companyがnullなら'-'
      , COALESCE(tmp.chk_person, '-') as chk_person -- tch.chk_personがnullなら'-'
      , to_char(tmp.investigate_dt, 'yyyy年MM月dd日') as investigate_dt
      , COALESCE(tmp.investigate_company, '-') as investigate_company -- tch.investigate_companyがnullなら'-'
      , COALESCE(tmp.investigate_person, '-') as investigate_person -- tch.investigate_personがnullなら'-'
      , tmp.phase
      , COALESCE(p.phase_str_large, '-') phase_str_large
      , COALESCE(tmp.check_shisetsu_judge, 0) as check_shisetsu_judge -- tch.check_shisetsu_judgeがnullなら0
      , COALESCE(sj_check_shisetsu.shisetsu_judge_nm, '未実施') as chk_shisetsu_judge_nm -- chk_shisetsu_judge_nmがnullなら未実施
      , COALESCE(tmp.measures_shisetsu_judge, 0) as measures_shisetsu_judge -- measures_shisetsu_judgeがnullなら0
      , COALESCE(sj_measeures_shisetsu.shisetsu_judge_nm, '未実施') as measures_shisetsu_judge_nm
      , COALESCE(tmp.syoken, '-') as syoken
      , bscnt.shichu_cnt
      , to_char(max_measures_dt, 'yyyy年MM月dd日') as re_measures_dt
      , lr
      , case
        when lr = 0
        then 'L'
        when lr = 1
        then 'R'
        when lr = 2
        then 'C'
        when lr = 3
        then 'LR'
        else '-'
        end lr_str
      , tmp.min_phase
      , tmp.max_phase
      , COALESCE(min_p.phase_str_large, '-') as min_phase_str
      , COALESCE(max_p.phase_str_large, '-') as max_phase_str
      from
      tmp
      left join rfs_m_shisetsu_kbn sk
        on tmp.shisetsu_kbn = sk.shisetsu_kbn
      left join rfs_m_shisetsu_keishiki skei
        on tmp.shisetsu_kbn = skei.shisetsu_kbn
        and tmp.shisetsu_keishiki_cd = skei.shisetsu_keishiki_cd
      left join rfs_m_dogen d
        on tmp.dogen_cd = d.dogen_cd
      left join rfs_m_syucchoujo s
        on tmp.syucchoujo_cd = s.syucchoujo_cd
      left join rfs_m_rosen r
        on tmp.rosen_cd = r.rosen_cd
      left join rfs_m_shisetsu_judge sj_check_shisetsu
        on tmp.check_shisetsu_judge = sj_check_shisetsu.shisetsu_judge
      left join rfs_m_shisetsu_judge sj_measeures_shisetsu
        on tmp.measures_shisetsu_judge = sj_measeures_shisetsu.shisetsu_judge
      left join tmp_phase p
        on tmp.phase = p.phase
      left join tmp_phase min_p
        on tmp.min_phase = min_p.phase
      left join tmp_phase max_p
        on tmp.max_phase = max_p.phase
      LEFT JOIN v_wareki_seireki AS v_wareki_seireki_nendo 
        ON v_wareki_seireki_nendo.seireki = EXTRACT( 
          YEAR 
          FROM
            tmp.target_dt ::TIMESTAMP - INTERVAL '3 month'
        ) 
      left join (
        select
          sno
          , count(sno) as shichu_cnt
        from
          rfs_m_bousetsusaku_shichu
        group by
          sno
      ) bscnt
        on tmp.sno = bscnt.sno
      where true
SQL;

      if(isset($syucchoujo_cd)) {
        if ($syucchoujo_cd==0) {
          if(isset($dogen_cd)) {
            $sql .= "    $and tmp.dogen_cd = $dogen_cd";
            $and = " and ";
          }
        }else{
          $sql .= "    $and tmp.syucchoujo_cd = $syucchoujo_cd";
          $and = " and ";
        }
      }

      if (isset($rosen_cd) and $rosen_cd !== '') {
        $sql .= sprintf(" and tmp.rosen_cd = %s ", $rosen_cd);
      }

      if(isset($shisetsu_cd)) {
            $sql.= "     $and tmp.shisetsu_cd like $shisetsu_cd";
            $and = " and ";
        }

        if(isset($shisetsu_ver)) {
            $sql .= "    $and tmp.shisetsu_ver = $shisetsu_ver";
            $and = " and ";
        }

/*
        if(strlen($struct_idx_in) != 0) {
            $sql .= "    $and struct_idx in ($struct_idx_in)";
            $and = " and ";
        }
*/
//
//        if(strlen($shisetsu_kbn_in) !=0) {
//            $sql .= "    $and tmp.shisetsu_kbn in ($shisetsu_kbn_in)";
//            $and = " and ";
//        }

        if(isset($setti_nendo_front_sel) || isset($setti_nendo_back_sel)) {
            if($include_secchi_null) {
                $and = " and (";
            }

            if(isset($setti_nendo_front_sel) && isset($setti_nendo_back_sel)) {
                $sql .= "$and ((secchi_yyyy between $setti_nendo_front_sel and $setti_nendo_back_sel) or (secchi_yyyy between $setti_nendo_back_sel and $setti_nendo_front_sel))";
            } else if(isset($setti_nendo_front_sel)) {
                $sql .= "$and secchi_yyyy >= $setti_nendo_front_sel";
            } else {
                $sql .= "$and secchi_yyyy <= $setti_nendo_back_sel";
            }

            if($include_secchi_null) {
                $sql .= "or (secchi_yyyy is null))";
            }

            $and = " and ";
        }

        if(strlen($rosen_cd_in) !=0) {
            $sql .= "    $and tmp.rosen_cd in ($rosen_cd_in)";
            $and = " and ";
        }

        if(isset($sp_start) || isset($sp_end)) {
            if(isset($sp_start) && isset($sp_end)) {
                $sql .= "$and sp between $sp_start and $sp_end";
            } else if(isset($sp_start)) {
                $sql .= "$and sp >= $sp_start";
            } else {
                $sql .= "$and sp <= $sp_end";
            }
            $and = " and ";
        }

        if(isset($sicyouson)) {
            if($sicyouson != '') {
                $sql .= "    $and shityouson like $sicyouson";
                $and = " and ";
            }
        }

        if(isset($azaban)) {
            if($azaban != '') {
                $sql .= "    $and azaban like $azaban";
                $and = " and ";
            }
        }

/*
        if(strlen($phase_in) !=0) {
            $sql .= "    $and tch.phase in ($phase_in)";
            $and = " and ";
        }
*/

        // 未実施が含まれている時
        if ($mijisshi==true) {
          if(strlen($chk_shisetsu_judge_in) !=0) {
            $sql .= "    $and (tmp.check_shisetsu_judge in ($chk_shisetsu_judge_in) or tmp.check_shisetsu_judge is null)";
            $and = " and ";
          }else{
            $sql .= "    $and tmp.check_shisetsu_judge is null ";
            $and = " and ";
          }
        } else {
          if(strlen($chk_shisetsu_judge_in) !=0) {
            $sql .= "    $and tmp.check_shisetsu_judge in ($chk_shisetsu_judge_in)";
            $and = " and ";
          }
        }

        if(strlen($measures_shisetsu_judge_in) !=0) {
            $sql .= "    $and tmp.measures_shisetsu_judge in ($measures_shisetsu_judge_in)";
            $and = " and ";
        }

        if(isset($chk_dt_front) || isset($chk_dt_back)) {
            if(isset($chk_dt_front) && isset($chk_dt_back)) {
                $sql .= "$and cast(tmp.chk_dt as date) between $chk_dt_front and $chk_dt_back";
            } else if(isset($chk_dt_front)) {
              $sql .= "$and cast(tmp.chk_dt as date) >= $chk_dt_front";
            } else {
              $sql .= "$and cast(tmp.chk_dt as date) <= $chk_dt_back";
            }
            $and = " and ";
        }

        if(strlen($patrolin_gyousya_label_in) !=0) {
          $sql.="      $and tmp.chk_company in ($patrolin_gyousya_label_in)";
          $and = " and ";
        }

        if(strlen($patrolin_label_in) !=0) {
          $sql .="     $and tmp.chk_person in ($patrolin_label_in)";
          $and = " and ";
        }

        if(isset($measures_dt_front) || isset($measures_dt_back)) {
            if(isset($measures_dt_front) && isset($measures_dt_back)) {
              $sql .= "$and cast(tmp.investigate_dt as date) between $measures_dt_front and $measures_dt_back";
            } else if(isset($measures_dt_front)) {
              $sql .= "$and cast(tmp.investigate_dt as date) >= $measures_dt_front";
            } else {
              $sql .= "$and cast(tmp.investigate_dt as date) <= $measures_dt_back";
            }
            $and = " and ";
        }

        if(strlen($investigator_gyousya_label_in) !=0) {
          $sql .= "    $and tmp.investigate_company in ($investigator_gyousya_label_in)";
          $and = " and ";
        }

        if(strlen($investigator_label_in) !=0) {
          $sql .= "    $and tmp.investigate_person in ($investigator_label_in)";
            $and = " and ";
        }


      $sql .= "      order by";
      $sql .= "        sk.sort_no";
      $sql .= "        , tmp.chk_mng_no is null asc";
      $sql .= "        , tmp.chk_mng_no desc";
      $sql .= "        , tmp.sno";

      $sql .= "  ) sch_result;";

      log_message('INFO', __CLASS__ . '::' . __FUNCTION__ . '/sql=' . $sql);

//      log_message('debug', "sql=$sql");
        $query = $this->DB_rfs->query($sql);
        $result = $query->result('array');

//        log_message('debug', "sql=$sql");
//        $r = print_r($result, true);
//        log_message('debug', "result=$r");

        return $result;
    }

    /**
     * 施設情報件数の取得
     *
     * @param $post setクエリ <br>
     *        $post['srch']             検索条件JSON
     * @return array
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     */
    public function get_srch_shisetsu_num($post) {
        log_message('debug', 'get_srch_shisetsu_num');

        $DB_rfs=$this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        // 建管コードと出張所コード
        $dogen_cd=$post['dogen_cd'];
        $syucchoujo_cd=$post['syucchoujo_cd'];


        // 検索条件の有無
//        $is_search_condition = false;
        $and = " and ";

        // 施設コード
        if(isset($post['srch']['shisetsu_cd'])) {
            $shisetsu_cd = pg_escape_literal('%'.$post['srch']['shisetsu_cd'].'%');
        }

        // 施設バージョン
        if(isset($post['srch']['shisetsu_ver'])) {
            $shisetsu_ver = $post['srch']['shisetsu_ver'];
        }

        // 建管
        if(isset($post['srch']['dogen_cd'])) {
            $dogen_cd = pg_escape_literal($post['srch']['dogen_cd']);
        }

        // 出張所コード
        if(isset($post['srch']['syucchoujo_cd'])) {
            $syucchoujo_cd = $post['srch']['syucchoujo_cd'];
        }

        // 路線番号
        $rosen_cd = (isset($post['srch']['rosen_cd']))
          ? $post['srch']['rosen_cd']
          : '';
        log_message('info', __CLASS__ . '::' .__FUNCTION__ . '/rosen_cd: ' . $rosen_cd);

        // 市町村
        $sicyouson = '';
        if(isset($post['srch']['sicyouson'])) {
            if($post['srch']['sicyouson']) {
                $sicyouson = pg_escape_literal('%'.$post['srch']['sicyouson'].'%');
            }
        }

        // 字番
        $azaban = '';
        if(isset($post['srch']['azaban'])) {
            if($post['srch']['azaban']) {
                $azaban = pg_escape_literal('%'.$post['srch']['azaban'].'%');
            }
        }

        // 設置年度
        $setti_nendo_front_sel;
        if(isset($post['srch']['setti_nendo_front_sel'])) {
            $setti_nendo_front_sel = intval($post['srch']['setti_nendo_front_sel']);
        }
        $setti_nendo_back_sel;
        if(isset($post['srch']['setti_nendo_back_sel'])) {
            $setti_nendo_back_sel = intval($post['srch']['setti_nendo_back_sel']);
        }

        // 設置年度に不明(null)を含める
        $include_secchi_null = 0;
        if(isset($post['srch']['include_secchi_null'])) {
            $include_secchi_null = boolval($post['srch']['include_secchi_null']);
        }

        // 点検実施年月日
        $chk_dt_front;
        if(isset($post['srch']['chk_dt_front'])) {
            $chk_dt_front = date('Y-m-d', strtotime($post['srch']['chk_dt_front']));
            $chk_dt_front = pg_escape_literal($chk_dt_front);
        }
        $chk_dt_back;
        if(isset($post['srch']['chk_dt_back'])) {
            $chk_dt_back = date('Y-m-d', strtotime($post['srch']['chk_dt_back']));
            $chk_dt_back = pg_escape_literal($chk_dt_back);
        }

        // 調査実施年月日
        $measures_dt_front;
        if(isset($post['srch']['measures_dt_front'])) {
            $measures_dt_front = date('Y-m-d', strtotime($post['srch']['measures_dt_front']));
            $measures_dt_front = pg_escape_literal($measures_dt_front);
        }
        $measures_dt_back;
        if(isset($post['srch']['measures_dt_back'])) {
            $measures_dt_back = date('Y-m-d', strtotime($post['srch']['measures_dt_back']));
            $measures_dt_back = pg_escape_literal($measures_dt_back);
        }

        // 測点
        $sp_start;
        if(isset($post['srch']['sp_start'])) {
            $sp_start = intval($post['srch']['sp_start']);
        }
        $sp_end;
        if(isset($post['srch']['sp_end'])) {
            $sp_end = intval($post['srch']['sp_end']);
        }

        $shisetsu_kbn_dat_model = $post['srch']['shisetsu_kbn_dat_model'];
        $rosen_dat_model = $post['srch']['rosen_dat_model'];
        $phase_dat_model = $post['srch']['phase_dat_model'];
        $chk_judge_dat_model = $post['srch']['chk_judge_dat_model'];
        $measures_judge_dat_model = $post['srch']['measures_judge_dat_model'];
        $patrolin_gyousya_dat_model = $post['srch']['patrolin_gyousya_dat_model'];
        $patrolin_dat_model = $post['srch']['patrolin_dat_model'];
        $investigator_gyousya_dat_model = $post['srch']['investigator_gyousya_dat_model'];
        $investigator_dat_model = $post['srch']['investigator_dat_model'];
        $struct_idx_dat_model = $post['srch']['struct_idx_dat_model'];

        // 支柱インデックス
        $struct_idx_in = '';
        foreach($struct_idx_dat_model as $key => $struct_idx_dat) {
            $struct_idx_in .= $struct_idx_dat['struct_idx'] . ',';
            //log_message('debug', "★struct_idx_in : " . $struct_idx_in);
        }
        //log_message('debug', "★struct_idx_in : " . $struct_idx_in);
        if(strlen($struct_idx_in) != 0) {
            $struct_idx_in = substr($struct_idx_in, 0, strlen($struct_idx_in) - 1);
        }

        // 施設名
        $shisetsu_kbn_in = '';
        if ($rosen_cd === '') {
            // 路線別集計では区分による絞り込みをしない
            foreach ($shisetsu_kbn_dat_model as $key => $shisetsu_kbn_dat) {
                $shisetsu_kbn_in .= $shisetsu_kbn_dat['shisetsu_kbn'] . ',';
            }
            if (strlen($shisetsu_kbn_in) != 0) {
                $shisetsu_kbn_in = substr($shisetsu_kbn_in, 0, strlen($shisetsu_kbn_in) - 1);
            }
        }

        // 路線選択
        $rosen_cd_in = '';
        foreach($rosen_dat_model as $key => $rosen_dat) {
            $rosen_cd_in .= $rosen_dat['rosen_cd'] . ',';
        }
        if(strlen($rosen_cd_in) != 0) {
            $rosen_cd_in = substr($rosen_cd_in, 0, strlen($rosen_cd_in) - 1);
        }

        // 点検の実施状況
        $phase_in = '';
        if ($rosen_cd === '') {
          // 路線別集計では区分による絞り込みをしない
            foreach ($phase_dat_model as $key => $phase_dat) {
                $phase_in .= $phase_dat['phase'] . ',';
            }
            if (strlen($phase_in) != 0) {
                $phase_in = substr($phase_in, 0, strlen($phase_in) - 1);
            }
        } elseif ($rosen_cd > 0) {
          $phase_in = '-1, 5';
        }

        // 健全性（点検時）の検索条件
        $mijisshi=false;
        $chk_shisetsu_judge_in = '';
        foreach($chk_judge_dat_model as $key => $chk_judge_dat) {
          if ($chk_judge_dat['shisetsu_judge']==0) {
            $mijisshi=true;
          } else {
            $chk_shisetsu_judge_in .= $chk_judge_dat['shisetsu_judge'] . ',';
          }
        }
        if(strlen($chk_shisetsu_judge_in) != 0) {
            $chk_shisetsu_judge_in = substr($chk_shisetsu_judge_in, 0, strlen($chk_shisetsu_judge_in) - 1);
        }

        // 健全性（措置後）の検索条件
        $measures_shisetsu_judge_in = '';
        foreach($measures_judge_dat_model as $key => $measures_judge_dat) {
            $measures_shisetsu_judge_in .= $measures_judge_dat['shisetsu_judge'] . ',';
        }
        if(strlen($measures_shisetsu_judge_in) != 0) {
            $measures_shisetsu_judge_in = substr($measures_shisetsu_judge_in, 0, strlen($measures_shisetsu_judge_in) - 1);
        }

        // 点検会社
        $patrolin_gyousya_label_in = '';
        foreach($patrolin_gyousya_dat_model as $key => $patrolin_gyousya_dat) {
            $patrolin_gyousya_label_in .= pg_escape_literal($patrolin_gyousya_dat['label']) . ',';
        }
        if(strlen($patrolin_gyousya_label_in) != 0) {
            $patrolin_gyousya_label_in = substr($patrolin_gyousya_label_in, 0, strlen($patrolin_gyousya_label_in) - 1);
        }

        // 点検員
        $patrolin_label_in = '';
        foreach($patrolin_dat_model as $key => $patrolin_dat) {
            $patrolin_label_in .= pg_escape_literal($patrolin_dat['label']) . ',';
        }
        if(strlen($patrolin_label_in) != 0) {
            $patrolin_label_in = substr($patrolin_label_in, 0, strlen($patrolin_label_in) - 1);
        }

        // 調査会社
        $investigator_gyousya_label_in = '';
        foreach($investigator_gyousya_dat_model as $key => $investigator_gyousya_dat) {
            $investigator_gyousya_label_in .= pg_escape_literal($investigator_gyousya_dat['label']) . ',';
        }
        if(strlen($investigator_gyousya_label_in) != 0) {
            $investigator_gyousya_label_in = substr($investigator_gyousya_label_in, 0, strlen($investigator_gyousya_label_in) - 1);
        }

        // 調査員
        $investigator_label_in = '';
        foreach($investigator_dat_model as $key => $investigator_dat) {
            $investigator_label_in .= pg_escape_literal($investigator_dat['label']) . ',';
        }
        if(strlen($investigator_label_in) != 0) {
            $investigator_label_in = substr($investigator_label_in, 0, strlen($investigator_label_in) - 1);
        }

      // 対象年度の条件を追加
      $target_nendo_from=isset($post['srch']['target_nendo_from'])?$post['srch']['target_nendo_from']:"";
      $target_nendo_to=isset($post['srch']['target_nendo_to'])?$post['srch']['target_nendo_to']:"";

      $nendo_where="";
      if ($target_nendo_from === "" && $target_nendo_to === "") {
        // 両方ない場合設定なし
      }else{
        if ($target_nendo_from==$target_nendo_to || $target_nendo_to == "") {
          // 同じ場合またはTOが無い場合
          $nen_from=$target_nendo_from."-04-01";
          $nen_to=($target_nendo_from+1)."-04-01";
        } else if ($target_nendo_from=="") {
          // TOのみある場合
          $nen_from=$target_nendo_to."-04-01";
          $nen_to=($target_nendo_to+1)."-04-01";
        } else if ($target_nendo_from < $target_nendo_to) {
          // TOの方が大きい(正常)
          $nen_from=$target_nendo_from."-04-01";
          $nen_to=($target_nendo_to+1)."-04-01";
        } else {
          // TOの方が小さい
          $nen_from=$target_nendo_to."-04-01";
          $nen_to=($target_nendo_from+1)."-04-01";
        }
        $nendo_where="AND (".pg_escape_literal($nen_from)." <= tcm1.target_dt AND tcm1.target_dt < ".pg_escape_literal($nen_to).")";
      }

//        $sql= <<<SQL
        $sql =  "      select";
        $sql .= "        count(*) as sch_result_num";
        $sql .= "      from";
        $sql .= "        (";
        $sql .= "          select ms1.*";
        $sql .= "          from rfs_m_shisetsu ms1, ";
        $sql .= "          (";
        $sql .= "            select ";
        $sql .= "              shisetsu_cd ";
        $sql .= "              ,max(shisetsu_ver) as shisetsu_ver";
        $sql .= "            from rfs_m_shisetsu";
        $sql .= "            group by";
        $sql .= "              shisetsu_cd";
        $sql .= "          ) ms2";
        $sql .= "          where";
        $sql .= "            ms1.shisetsu_cd = ms2.shisetsu_cd";
        $sql .= "            and ms1.shisetsu_ver = ms2.shisetsu_ver";
        $sql .= "        ) ms";
      // 点検が1回も行われていないものは拾わない
      $sql .= "        join";
      //$sql .= "        left join";
      $sql .= "          (";
        $sql .= "            select";
        $sql .= "              distinct on (tcm1.chk_mng_no) *";
        $sql .= "            from";
        $sql .= "              rfs_t_chk_main tcm1";
        $sql .= "            where not exists";
        $sql .= "              (";
        $sql .= "                select 1";
        $sql .= "                from rfs_t_chk_main tcm2";
        $sql .= "                where";
        $sql .= "                  tcm1.sno = tcm2.sno";
        $sql .= "                  and tcm1.struct_idx = tcm2.struct_idx";
        $sql .= "                  and tcm1.chk_mng_no < tcm2.chk_mng_no";
        $sql .= "              ) $nendo_where";
        $sql .= "          ) tcm";
        $sql .= "          on ms.sno = tcm.sno";
        $sql .= "        left join";
        $sql .= "          (";
        $sql .= "            select";
        $sql .= "              distinct on (chk_mng_no) *";
        $sql .= "            from";
        $sql .= "              rfs_t_chk_huzokubutsu tch1";
        $sql .= "            where not exists";
        $sql .= "              (";
        $sql .= "                select 1";
        $sql .= "                from rfs_t_chk_huzokubutsu tch2";
        $sql .= "                where";
        $sql .= "                  tch1.chk_mng_no = tch2.chk_mng_no";
        $sql .= "                  and tch1.rireki_no < tch2.rireki_no";
        $sql .= "              )";
        $sql .= "          ) tch";
        $sql .= "          on tcm.chk_mng_no = tch.chk_mng_no";
        $sql .= "        left join rfs_m_shisetsu_kbn sk";
        $sql .= "          on ms.shisetsu_kbn = sk.shisetsu_kbn";
        $sql .= "        left join rfs_m_shisetsu_keishiki skei";
        $sql .= "          on ms.shisetsu_kbn = skei.shisetsu_kbn";
        $sql .= "          and ms.shisetsu_keishiki_cd = skei.shisetsu_keishiki_cd";
        $sql .= "        left join rfs_m_dogen d";
        $sql .= "          on ms.dogen_cd = d.dogen_cd";
        $sql .= "        left join rfs_m_syucchoujo s";
        $sql .= "          on ms.syucchoujo_cd = s.syucchoujo_cd";
        $sql .= "        left join rfs_m_rosen r";
        $sql .= "          on ms.rosen_cd = r.rosen_cd  ";
        $sql .= "        left join rfs_m_shisetsu_judge sj_check_shisetsu";
        $sql .= "          on tch.check_shisetsu_judge = sj_check_shisetsu.shisetsu_judge";
        $sql .= "        left join rfs_m_shisetsu_judge sj_measeures_shisetsu";
        $sql .= "          on tch.measures_shisetsu_judge = sj_measeures_shisetsu.shisetsu_judge";
        $sql .= "        left join";
        $sql .= "          (";
        $sql .= "            select";
        $sql .= "              phase, phase_str_large";
        $sql .= "            from";
        $sql .= "              rfs_m_phase";
        $sql .= "            group by";
        $sql .= "              phase, phase_str_large";
        $sql .= "          ) p";
        $sql .= "          on tch.phase = p.phase";
        $sql .= "        left join";
        $sql .= "          (select sno, count(sno) as shichu_cnt from rfs_m_bousetsusaku_shichu group by sno) bscnt ";
        $sql .= "          on ms.sno = bscnt.sno ";

        $sql .= "      where true";
        $sql .= "        and (struct_idx = 0 OR struct_idx is null) ";

        // 廃止年が入っていないもののみ
        $sql .= "        and ((trim(ms.haishi) = '' or ms.haishi is null) and ms.haishi_yyyy is null) ";

        if(isset($shisetsu_cd)) {
            $sql.= "     $and ms.shisetsu_cd like $shisetsu_cd";
            $and = " and ";
        }

        if(isset($shisetsu_ver)) {
            $sql .= "    $and ms.shisetsu_ver = $shisetsu_ver";
            $and = " and ";
        }

        if(strlen($struct_idx_in) != 0) {
            $sql .= "    $and struct_idx in ($struct_idx_in)";
            $and = " and ";
        }

        if (isset($rosen_cd) and $rosen_cd !== '') {
          $sql .= sprintf(" and tmp.rosen_cd = %s ", $rosen_cd);
        }

        if(strlen($shisetsu_kbn_in) !=0) {
            $sql .= "    $and ms.shisetsu_kbn in ($shisetsu_kbn_in)";
            $and = " and ";
        }

        if(isset($setti_nendo_front_sel) || isset($setti_nendo_back_sel)) {
            if($include_secchi_null) {
                $and = " and (";
            }

            if(isset($setti_nendo_front_sel) && isset($setti_nendo_back_sel)) {
                $sql .= "$and ((secchi_yyyy between $setti_nendo_front_sel and $setti_nendo_back_sel) or (secchi_yyyy between $setti_nendo_back_sel and $setti_nendo_front_sel))";
            } else if(isset($setti_nendo_front_sel)) {
                $sql .= "$and secchi_yyyy >= $setti_nendo_front_sel";
            } else {
                $sql .= "$and secchi_yyyy <= $setti_nendo_back_sel";
            }

            if($include_secchi_null) {
                $sql .= "or (secchi_yyyy is null))";
            }

            $and = " and ";
        }

        if(strlen($rosen_cd_in) !=0) {
            $sql .= "    $and ms.rosen_cd in ($rosen_cd_in)";
            $and = " and ";
        }

        if(isset($sp_start) || isset($sp_end)) {
            if(isset($sp_start) && isset($sp_end)) {
                $sql .= "$and sp between $sp_start and $sp_end";
            } else if(isset($sp_start)) {
                $sql .= "$and sp >= $sp_start";
            } else {
                $sql .= "$and sp <= $sp_end";
            }
            $and = " and ";
        }

        if(isset($sicyouson)) {
            if($sicyouson != '') {
                $sql .= "    $and shityouson like $sicyouson";
                $and = " and ";
            }
        }

        if(isset($azaban)) {
            if($azaban != '') {
                $sql .= "    $and azaban like $azaban";
                $and = " and ";
            }
        }

        if(strlen($phase_in) !=0) {
          $sql .= "    $and COALESCE(tch.phase , - 1) in ($phase_in)";
            $and = " and ";
        }

        // 未実施が含まれている時
        if ($mijisshi==true) {
          if(strlen($chk_shisetsu_judge_in) !=0) {
            $sql .= "    $and (tch.check_shisetsu_judge in ($chk_shisetsu_judge_in) or tch.check_shisetsu_judge is null)";
            $and = " and ";
          }else{
            $sql .= "    $and tch.check_shisetsu_judge is null ";
            $and = " and ";
          }
        } else {
          if(strlen($chk_shisetsu_judge_in) !=0) {
            $sql .= "    $and tch.check_shisetsu_judge in ($chk_shisetsu_judge_in)";
            $and = " and ";
          }
        }

        if(strlen($measures_shisetsu_judge_in) !=0) {
            $sql .= "    $and tch.measures_shisetsu_judge in ($measures_shisetsu_judge_in)";
            $and = " and ";
        }

        if(isset($chk_dt_front) || isset($chk_dt_back)) {
            if(isset($chk_dt_front) && isset($chk_dt_back)) {
                $sql .= "$and tch.chk_dt between $chk_dt_front and $chk_dt_back";
            } else if(isset($chk_dt_front)) {
                $sql .= "$and tch.chk_dt >= $chk_dt_front";
            } else {
                $sql .= "$and tch.chk_dt <= $chk_dt_back";
            }
            $and = " and ";
        }

        if(strlen($patrolin_gyousya_label_in) !=0) {
            $sql.="      $and tch.chk_company in ($patrolin_gyousya_label_in)";
            $and = " and ";
        }

        if(strlen($patrolin_label_in) !=0) {
            $sql .="     $and tch.chk_person in ($patrolin_label_in)";
            $and = " and ";
        }

        if(isset($measures_dt_front) || isset($measures_dt_back)) {
            if(isset($measures_dt_front) && isset($measures_dt_back)) {
                $sql .= "$and tch.investigate_dt between $measures_dt_front and $measures_dt_back";
            } else if(isset($measures_dt_front)) {
                $sql .= "$and tch.investigate_dt >= $measures_dt_front";
            } else {
                $sql .= "$and tch.investigate_dt <= $measures_dt_back";
            }
            $and = " and ";
        }

        if(strlen($investigator_gyousya_label_in) !=0) {
            $sql .= "    $and tch.investigate_company in ($investigator_gyousya_label_in)";
            $and = " and ";
        }

        if(strlen($investigator_label_in) !=0) {
            $sql .= "    $and tch.investigate_person in ($investigator_label_in)";
            $and = " and ";
        }

        if(isset($syucchoujo_cd)) {
            if ($syucchoujo_cd==0) {
                if(isset($dogen_cd)) {
                    $sql .= "    $and ms.dogen_cd = $dogen_cd";
                    $and = " and ";
                }
            }else{
                $sql .= "    $and ms.syucchoujo_cd = $syucchoujo_cd";
                $and = " and ";
            }
        }

        $query = $this->DB_rfs->query($sql);
        $result = $query->result('array');

//      log_message('debug', "sql=$sql");
//        $r = print_r($result, true);
//        log_message('debug', "result=$r");

        return $result;
    }

    /**
     *  支柱インデックス取得
     *
     * @param $post setクエリ <br>
     *        $post['shisetsu_cd']      施設コード
     *
     */
    public function get_srch_struct($post) {
        log_message('debug', 'get_srch_struct');

        $DB_rfs=$this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        $sql = <<<EOF
        select
          jsonb_set(
            '{}'
            , '{struct_idx_info}'
            , jsonb_agg(
                to_jsonb("sch_result")
            )
          ) as struct_idx_row
        from
          (
            select
              distinct(struct_idx) as id
              , struct_idx as label
              , struct_idx as struct_idx
            from
              rfs_t_chk_main
EOF;

        if(isset($post['srch']['shisetsu_cd'])) {

            $where_clause = " where ";

            if(isset($post['srch']['shisetsu_cd'])) {
                $shisetsu_cd = pg_escape_literal($post['srch']['shisetsu_cd']);
                $where_clause .= "shisetsu_cd = $shisetsu_cd";
            }
            $sql .= $where_clause;
        }

        $sql .=  <<<EOF
            order by
              struct_idx
          ) sch_result
EOF;

        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

//        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
//        log_message('debug', "result=$r");

        return $result;
    }

    /**
     *  基本情報取得処理
     *  GETにある、施設を特定するキーから、
     *  1件の基本情報を取得し、返却する。
     *
     *  引数：$get
     *
     */
    public function getShisetsu_forBaseInfoEdit($get) {

        if ($get['sno']=="") {
            return null;
        }

        $sno=$get['sno'];

        $sql= <<<EOF
select
    jsonb_set('{}', '{s_info}', jsonb_agg(to_jsonb(s))) AS s_row
from
  (
    select
        s.*
      , 0 as struct_idx
      , bs_data.bs_row
    from
      rfs_m_shisetsu s
      left join (
        select
            shisetsu_cd
          , shisetsu_ver
          , jsonb_set(
            '{}'
            , '{bs_info}'
            , jsonb_agg(
              to_jsonb(rfs_m_bousetsusaku_shichu) - 'shisetsu_cd' - 'shisetsu_ver' order by struct_idx
            )
          ) AS bs_row
        from
          rfs_m_bousetsusaku_shichu
        group by
          shisetsu_cd
          , shisetsu_ver
        order by
          shisetsu_cd
          , shisetsu_ver
      ) bs_data
        on s.shisetsu_cd = bs_data.shisetsu_cd
        and s.shisetsu_ver = bs_data.shisetsu_ver
    where
      s.sno = $sno
  ) as s
EOF;

        $this->DB_rfs = $this->load->database('rfs',TRUE);
        if ($this->DB_rfs->conn_id === FALSE) {
            log_message('debug', '道路附属物点検システムデータベースに接続されていません');
            return;
        }

        $query = $this->DB_rfs->query($sql);
        $result = $query->result('array');

        //$r = print_r($result, true);
        //log_message('debug', "sql=$sql");
        //log_message('debug', "result=$r");

        return $result;

    }

    /**
     * 現在のshisetsu_verが最大の施設を返却する
     *
     * 与えられたキーの施設で最大のshisetsu_verのレコードを返却する。
     *
     * @param   道路附属物点検DBコネクション
     *          $get getクエリ <br>
     *          使用するのは、shisetsu_cd
     *
     * @return row
     *
     */
    public function get_shisetsu_max_ver($get) {

        log_message('debug', 'chk_shisetsu_duplication');

        // 施設コードが無い場合はチェックしない
        if ($get['shisetsu_cd']=="") {
            return -1;
        }

        $shisetsu_cd=$get['shisetsu_cd'];

        $sql= <<<EOF
select
*
from
rfs_m_shisetsu
where
shisetsu_cd='$shisetsu_cd'
order by
shisetsu_ver desc
EOF;

        $query = $this->DB_rfs->query($sql);
        $result = $query->result('array');

        //$r = print_r($result, true);
        //        log_message('debug', "sql=$sql");
        //log_message('debug', "return:".$r);

        return $result;
    }

    /**
     * 緯度経度から住所を取得する
     *
     * GET引数の緯度経度から住所を取得する（維持管理の住所マスタから）
     *
     * @param   $get getクエリ <br>
     *          使用するのは、lat、lon
     *
     * @return row（twon、streat)
     *
     */
    public function get_addr($get) {

        $DB_imm = $this->load->database('imm',TRUE);
        if ($DB_imm->conn_id === FALSE) {
            log_message('debug', '維持管理システムデータベースに接続されていません');
            return;
        }

        $lat=$get['lat'];
        $lon=$get['lon'];

        $sql= <<<EOF
SELECT
    twon
  , streat
FROM
  m_jyusyo
WHERE
  st_distance(ST_GeomFromText('POINT($lon $lat)', 4612), geom) = (
    SELECT
        min(
        st_distance(ST_GeomFromText('POINT($lon $lat)', 4612), geom)
      )
    FROM
      m_jyusyo
  );
EOF;

        $query = $DB_imm->query($sql);
        $result = $query->result('array');

        //$r = print_r($result, true);
        //log_message('debug', "sql=$sql");
        //log_message('debug', "return:".$r);

        return $result;
    }

}
