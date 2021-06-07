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
class SchBsskChildren extends CI_Model {

    /**
    * コンストラクタ
    *
    * model SchBsskChildrenを初期化する。
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
     * 防雪柵（支柱インデックス）情報の取得
     *
     * @param $post setクエリ <br>
     *        $get['sno']               施設シリアル番号
     * @return array
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     */
    public function get_srch_bssk_children($post) {
        log_message('debug', 'get_srch_bssk_children');

        $DB_rfs=$this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        $sno = $post['sno'];

        $sql = <<<EOF
        select
          jsonb_set(
            '{}'
            , '{sch_result}'
            , jsonb_agg(
                to_jsonb("sch_result")
            )
          ) as sch_result_row
        from
          (
            select
              row_number() over(
                order by
                  mbs.struct_idx) as seq_no
              ,mbs.sno
              ,tcm.chk_mng_no
              ,mbs.shisetsu_cd
              ,mbs.shisetsu_ver
              ,mbs.struct_idx
              ,ms.dogen_cd
              ,ms.lat
              ,ms.lon
              ,d.dogen_mei
              ,ms.syucchoujo_cd
              ,s.syucchoujo_mei
              ,ms.shisetsu_kbn
              ,sk.shisetsu_kbn_nm
              ,ms.shisetsu_keishiki_cd
              ,skei.shisetsu_keishiki_nm
              ,ms.rosen_cd
              ,case
                when r.rosen_nm is null then
                  ''
                else
                  ms.rosen_cd || ':' || r.rosen_nm
                end rosen_nm
              ,ms.sp
              ,ms.shityouson || ms.azaban syozaichi
              ,case
                when ms.secchi is null then
                  '-'
                when ms.secchi = '' then
                  '-'
                else
                  ms.secchi
                end secchi
              ,ms.secchi_yyyy
              ,case
                when ms.substitute_road = 1 then
                  '有'
                else
                  '無'
                end substitute_road_str
              ,case
                when ms.emergency_road is null then
                 '-'
                else
                  '第' || ms.emergency_road || '次'
                end emergency_road_str
              ,case
                when ms.motorway = 1 then
                  '一般道'
                else
                  '自専道'
                end motorway_str
              ,case
                when ms.senyou is null then
                 '-'
                else
                 ms.senyou
                end senyou
              ,case
                when ms.fukuin is null then
                 '-'
                when ms.fukuin = '' then
                 '-'
                else
                  ms.fukuin || 'm'
                end fukuin
              ,tch.rireki_no
              , to_char(tch.chk_dt,'YYYY年MM月DD日') as chk_dt
              ,case
                when tch.chk_company is null then
                 '-'
                else
                 tch.chk_company
                end chk_company
              ,case
                when tch.chk_person is null then
                 '-'
                else
                 tch.chk_person
                end chk_person
              , to_char(tch.investigate_dt,'YYYY年MM月DD日') as investigate_dt
              ,case
                when tch.investigate_company is null then
                 '-'
                else
                 tch.investigate_company
                end investigate_company
              ,case
                when tch.investigate_person is null then
                 '-'
                else
                 tch.investigate_person
                end investigate_person
              ,tch.phase
              ,case
                when p.phase_str_large is null then
                 '-'
                when tch.phase = 1 then
                 '点検中'
                else
                 p.phase_str_large
                end phase_str_large
              ,case
                when tch.check_shisetsu_judge is null then
                 0
                else
                 tch.check_shisetsu_judge
                end check_shisetsu_judge
              ,case
                when sj_check_shisetsu.shisetsu_judge_nm is null then
                 '未実施'
                else
                 sj_check_shisetsu.shisetsu_judge_nm
                end chk_shisetsu_judge_nm
              ,case
                when tch.measures_shisetsu_judge is null then
                 0
                else
                 tch.measures_shisetsu_judge
                end measures_shisetsu_judge
              ,case
                when sj_measeures_shisetsu.shisetsu_judge_nm is null then
                 '未実施'
                else
                 sj_measeures_shisetsu.shisetsu_judge_nm
                end measures_shisetsu_judge_nm
              ,case
                when tch.syoken is null then
                 '-'
                else
                 tch.syoken
                end syoken
              , mbs.struct_no_s
              , mbs.struct_no_e
              , to_char(max_measures_dt_tmp.max_measures_dt,'YYYY年MM月DD日') as re_measures_dt
            from
              (
                select ms1.*
                from rfs_m_shisetsu ms1,
                (
                  select
                    shisetsu_cd
                    , max(shisetsu_ver) as shisetsu_ver
                  from rfs_m_shisetsu
                  group by
                    shisetsu_cd
                ) ms2
                where
                  ms1.shisetsu_cd = ms2.shisetsu_cd
                  and ms1.shisetsu_ver = ms2.shisetsu_ver
                  and ms1.shisetsu_kbn = 4
                  and ms1.sno = $sno
              ) ms
              left join
                (
                  select *
                  from rfs_m_bousetsusaku_shichu
                  where
                    struct_idx != 0
                    and sno = $sno
                ) mbs
              on ms.sno = mbs.sno
              left join
                (

                  SELECT
                    tcm1.chk_mng_no
                    , tcm1.sno
                    , tcm1.struct_idx
                  FROM
                    rfs_t_chk_main tcm1
                  JOIN
                  (
                    SELECT
                      sno,
                      MAX(chk_times) chk_times
                    FROM
                      rfs_t_chk_main
                    WHERE sno = $sno
                    GROUP BY sno
                  ) tcm2
                  ON
                    tcm1.sno=tcm2.sno
                    AND
                    tcm1.chk_times=tcm2.chk_times
                  WHERE
                    tcm1.struct_idx != 0

--                  select
--                    chk_mng_no
--                    , sno
--                    , struct_idx
--                  from
--                    rfs_t_chk_main tcm1
--                  where not exists
--                    (
--                      select 1
--                      from rfs_t_chk_main tcm2
--                      where
--                        tcm1.sno = tcm2.sno
--                        and tcm1.chk_times < tcm2.chk_times
--                        and tcm1.struct_idx = tcm2.struct_idx
--                        and tcm1.chk_mng_no < tcm2.chk_mng_no
--                    )
--                    and struct_idx != 0

                ) tcm
                on mbs.sno = tcm.sno
                and mbs.struct_idx = tcm.struct_idx
              left join
                (
                  select
                    distinct on (chk_mng_no) *
                  from
                    rfs_t_chk_huzokubutsu tch1
                  where not exists
                    (
                      select 1
                      from rfs_t_chk_huzokubutsu tch2
                      where
                        tch1.chk_mng_no = tch2.chk_mng_no
                        and tch1.rireki_no < tch2.rireki_no
                    )
                ) tch
                on tcm.chk_mng_no = tch.chk_mng_no
              left join rfs_m_shisetsu_kbn sk
                on ms.shisetsu_kbn = sk.shisetsu_kbn
              left join rfs_m_shisetsu_keishiki skei
                on ms.shisetsu_kbn = skei.shisetsu_kbn
                and ms.shisetsu_keishiki_cd = skei.shisetsu_keishiki_cd
              left join rfs_m_dogen d
                on ms.dogen_cd = d.dogen_cd
              left join rfs_m_syucchoujo s
                on ms.syucchoujo_cd = s.syucchoujo_cd
              left join rfs_m_rosen r
                on ms.rosen_cd = r.rosen_cd
              left join rfs_m_shisetsu_judge sj_check_shisetsu
                on tch.check_shisetsu_judge = sj_check_shisetsu.shisetsu_judge
              left join rfs_m_shisetsu_judge sj_measeures_shisetsu
                on tch.measures_shisetsu_judge = sj_measeures_shisetsu.shisetsu_judge
              left join
                (
                  select
                    phase, phase_str_large
                  from
                    rfs_m_phase
                  group by
                    phase, phase_str_large
                ) p
                on tch.phase = p.phase
              left join
                (
                  select
                    chk_mng_no,
                    max(measures_dt) max_measures_dt
                  from rfs_t_chk_tenken_kasyo
                  group by
                    chk_mng_no
                ) max_measures_dt_tmp
                on tcm.chk_mng_no=max_measures_dt_tmp.chk_mng_no
              where
                mbs.struct_idx != 0
                and ms.sno = $sno
                and ms.shisetsu_kbn = 4

              order by
                shisetsu_cd
          ) sch_result;
EOF;
        $query = $this->DB_rfs->query($sql);
        $result = $query->result('array');

//        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
//        log_message('debug', "result=$r");

        return $result;
    }

}
