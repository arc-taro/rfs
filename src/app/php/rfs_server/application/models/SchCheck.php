<?php

/**
 * 点検表の検索を行う
 * 返却は配列
 *
 * @access public
 * @package Model
 */
class SchCheck extends CI_Model {

    /**
     * コンストラクタ
     *
     * model SchCheckを初期化する。
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * 基本情報の取得（新規追加用）
     *
     * @param $get getクエリ <br>
     *        $get['shisetsu_cd']       施設コード
     *        $get['shisetsu_ver']      施設バージョン
     * @return array
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     */
    public function get_baseinfo_new($get){
        log_message('debug', 'get_baseinfo_new');

        if (($get['shisetsu_cd'] == "") || ($get['shisetsu_ver'] == "")) {
            // 終了
            return null;
        }

        $shisetsu_cd = $get['shisetsu_cd'];
        $shisetsu_ver = $get['shisetsu_ver'];

        $DB_rfs = $this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        $sql= <<<EOF
select
    s.shisetsu_cd
  , s.shisetsu_ver
  , s.shisetsu_kbn
  , msk.shisetsu_kbn_nm
  , s.shisetsu_keishiki_cd
  , mske.shisetsu_keishiki_nm
  , cm.chk_mng_no
  , cm.chk_times
  , tch.rireki_no
  , cm.struct_idx
  , cm.target_dt
  , cm.busyo_cd
  , cm.kumiai_cd
  , s.rosen_cd
  , r.rosen_nm
  , s.shityouson
  , s.azaban
  , s.lat
  , s.lon
  , s.dogen_cd
  , d.dogen_mei
  , s.syucchoujo_cd
  , ms.syucchoujo_mei
  , s.substitute_road
  , case
      when s.substitute_road = 0
      then '有'
      else '無'
      end as substitute_road_str
  , s.emergency_road
  , case
      when s.emergency_road is null
      then ''
      else '第' || s.emergency_road || '次'
      end as emergency_road_str
  , s.motorway
  , case
      when s.motorway = 0
      then '自専道'
      when s.motorway = 1
      then '一般道'
      else ''
      end as motorway_str
  , s.senyou
  , to_char(tch.chk_dt,'YYYY-MM-DD') as chk_dt
  , tch.chk_company
  , tch.chk_person
  , to_char(tch.investigate_dt,'YYYY-MM-DD') as investigate_dt
  , tch.investigate_company
  , tch.investigate_person
  , case
    when tch.surface is null
    then 0
    else tch.surface
    end as surface
  , tch.part_notable_chk
  , tch.reason_notable_chk
  , tch.special_report
  , case
    when tch.phase is null
    then 1
    end as phase
  , tch.syoken
  , tch.update_dt
  , case
    when tch.check_shisetsu_judge is null
    then 0
    else tch.check_shisetsu_judge
    end as check_shisetsu_judge
  , case
    when tch.measures_shisetsu_judge is null
    then 0
    else tch.measures_shisetsu_judge
    end as measures_shisetsu_judge

from
  rfs_m_shisetsu s left join rfs_t_chk_main cm
    on s.sno = cm.sno
  left join rfs_m_shisetsu_kbn msk
    on s.shisetsu_kbn = msk.shisetsu_kbn
  left join rfs_m_shisetsu_keishiki mske
    on s.shisetsu_kbn = mske.shisetsu_kbn
    and s.shisetsu_keishiki_cd = mske.shisetsu_keishiki_cd
  left join rfs_m_rosen r
    on s.rosen_cd = r.rosen_cd
  left join rfs_m_dogen d
    on s.dogen_cd = d.dogen_cd
  left join rfs_m_syucchoujo ms
    on s.syucchoujo_cd = ms.syucchoujo_cd
  left join rfs_t_chk_huzokubutsu tch
    on cm.chk_mng_no = tch.chk_mng_no

where
  s.shisetsu_cd = '$shisetsu_cd'
  and s.shisetsu_ver = $shisetsu_ver

  and cm.chk_times = (
    select
        max(chk_times)
    from
      rfs_t_chk_main
    where
      shisetsu_cd = s.shisetsu_cd
      and shisetsu_ver = s.shisetsu_ver
  )

order by
  struct_idx;
EOF;
        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

//        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
//        log_message('debug', "result=$r");

        return $result;
    }

    /**
     * 基本情報の取得（点検管理番号の指定）
     *
     * @param $get getクエリ <br>
     *        $get['chk_mng_no']        点検管理番号
     *        $get['sno']               施設シリアル番号
     *        $get['struct_idx']        支柱インデックス
     * @return array
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     */
    public function get_baseinfo_by_chkmngno($get){
        log_message('debug', 'get_baseinfo_by_chkmngno');

        if (($get['chk_mng_no'] == "") || ($get['sno'] == "") || ($get['struct_idx'] == "")) {
            // 終了
            return null;
        }

        $chk_mng_no = $get['chk_mng_no'];
        $sno = $get['sno'];
        $struct_idx = $get['struct_idx'];

        $DB_rfs = $this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        if($chk_mng_no == 0) {
            $sql= <<<EOF
            select
                s.sno
              , s.shisetsu_cd
              , s.shisetsu_ver
              , s.shisetsu_kbn
              , s.dogen_cd
              , msk.shisetsu_kbn_nm
              , s.shisetsu_keishiki_cd
              , mske.shisetsu_keishiki_nm
              , cm.chk_mng_no
              , cm.chk_times
              , tch.rireki_no
              , cm.struct_idx
              , cm.target_dt
              , cm.busyo_cd
              , cm.kumiai_cd
              , s.rosen_cd
              , r.rosen_nm
              , s.shityouson
              , s.azaban
              , s.lat
              , s.lon
              , s.dogen_cd
              , d.dogen_mei
              , s.syucchoujo_cd
              , ms.syucchoujo_mei
              , s.substitute_road
              , case
                  when s.substitute_road = 0
                  then '有'
                  else '無'
                  end as substitute_road_str
              , s.emergency_road
              , case
                  when s.emergency_road is null
                  then ''
                  else '第' || s.emergency_road || '次'
                  end as emergency_road_str
              , s.motorway
              , case
                  when s.motorway = 0
                  then '自専道'
                  when s.motorway = 1
                  then '一般道'
                  else ''
                  end as motorway_str
              , case
                  when s.senyou is null
                  then ''
                  end as senyou
              , to_char(tch.chk_dt,'YYYY-MM-DD') as chk_dt
              , tch.chk_company
              , tch.chk_person
              , to_char(tch.investigate_dt,'YYYY-MM-DD') as investigate_dt
              , tch.investigate_company
              , tch.investigate_person
              , case
                  when tch.surface is null
                  then 0
                  else tch.surface
                  end as surface
              , tch.part_notable_chk
              , tch.reason_notable_chk
              , tch.special_report
              , case
                  when tch.phase is null
                  then 1
                  else tch.phase
                  end as phase
              , tch.syoken
              , tch.update_dt
              , case
                  when tch.check_shisetsu_judge is null
                  then 0
                  else tch.check_shisetsu_judge
                  end as check_shisetsu_judge
              , case
                  when tch.measures_shisetsu_judge is null
                  then 0
                  else tch.measures_shisetsu_judge
                  end as measures_shisetsu_judge
              , s.sp

--              , case
--                  when s.lr = 0
--                  then '左'
--                  else '右'
--                  end as lr

              , case
                when s.lr = 0
                  then '左'
                when s.lr = 1
                  then '右'
                when s.lr = 2
                  then '中央'
                when s.lr = 3
                  then '左右'
                else '-'
                end lr

            from
              rfs_m_shisetsu s left join rfs_t_chk_main cm
                on s.sno = cm.sno
              left join rfs_m_shisetsu_kbn msk
                on s.shisetsu_kbn = msk.shisetsu_kbn
              left join rfs_m_shisetsu_keishiki mske
                on s.shisetsu_kbn = mske.shisetsu_kbn
                and s.shisetsu_keishiki_cd = mske.shisetsu_keishiki_cd
              left join rfs_m_rosen r
                on s.rosen_cd = r.rosen_cd
              left join rfs_m_dogen d
                on s.dogen_cd = d.dogen_cd
              left join rfs_m_syucchoujo ms
                on s.syucchoujo_cd = ms.syucchoujo_cd
              left join rfs_t_chk_huzokubutsu tch
                on cm.chk_mng_no = tch.chk_mng_no

            where
              s.sno = $sno
              and cm.chk_times = (
                select
                    max(chk_times)
                from
                  rfs_t_chk_main
                where
                  chk_mng_no = $chk_mng_no
              )
              and tch.rireki_no = (
                select
                    max(rireki_no)
                from
                  rfs_t_chk_huzokubutsu
                where
                  chk_mng_no = cm.chk_mng_no
              )
              and cm.chk_mng_no = (
                select
                    max(chk_mng_no)
                from
                  rfs_t_chk_main
                where
                  sno = s.sno
              )

            order by
              struct_idx;
EOF;
        } else {
            $sql= <<<EOF
            select
                s.sno
              , s.shisetsu_cd
              , s.shisetsu_ver
              , s.shisetsu_kbn
              , s.dogen_cd
              , msk.shisetsu_kbn_nm
              , s.shisetsu_keishiki_cd
              , mske.shisetsu_keishiki_nm
              , tcm.chk_mng_no
              , tcm.chk_times
              , case
                when tch.rireki_no is null then
                  0
                else
                  tch.rireki_no
                end as rireki_no
              , tcm.struct_idx
              , tcm.target_dt
              , tcm.busyo_cd
              , tcm.kumiai_cd
              , s.rosen_cd
              , r.rosen_nm
              , s.shityouson
              , s.azaban
              , s.lat
              , s.lon
              , s.dogen_cd
              , d.dogen_mei
              , s.syucchoujo_cd
              , ms.syucchoujo_mei
              , s.substitute_road
              , case
                  when s.substitute_road = 0
                  then '有'
                  else '無'
                  end as substitute_road_str
              , s.emergency_road
              , case
                  when s.emergency_road is null
                  then ''
                  else '第' || s.emergency_road || '次'
                  end as emergency_road_str
              , s.motorway
              , case
                  when s.motorway = 0
                  then '自専道'
                  when s.motorway = 1
                  then '一般道'
                  else ''
                  end as motorway_str
              , s.senyou
              , to_char(tch.chk_dt,'YYYY-MM-DD') as chk_dt
              , tch.chk_company
              , tch.chk_person
              , to_char(tch.investigate_dt,'YYYY-MM-DD') as investigate_dt
              , tch.investigate_company
              , tch.investigate_person
              , case
                  when tch.surface is null
                  then 0
                  else tch.surface
                  end as surface
              , tch.part_notable_chk
              , tch.reason_notable_chk
              , tch.special_report
              , case
                  when tch.phase is null
                  then 1
                  else tch.phase
                  end as phase
              , tch.syoken
              , tch.update_dt
              , case
                  when tch.check_shisetsu_judge is null
                  then 0
                  else tch.check_shisetsu_judge
                  end as check_shisetsu_judge
              , case
                  when tch.measures_shisetsu_judge is null
                  then 0
                  else tch.measures_shisetsu_judge
                  end as measures_shisetsu_judge
              , s.sp

--              , case
--                  when s.lr = 0
--                  then '左'
--                  else '右'
--                  end as lr

              , case
                when s.lr = 0
                  then '左'
                when s.lr = 1
                  then '右'
                when s.lr = 2
                  then '中央'
                when s.lr = 3
                  then '左右'
                else '-'
                end lr

              , case
                when tch.chk_mng_no is null then
                  'true'
                else
                  'false'
                end as is_new_data
              , case when tch.create_account is null then
                  0
                else
                  tch.create_account
                end as create_account
              , case when mp.phase_str_large is null then
                  '点検'
                else
                  mp.phase_str_large
                end as phase_str_large

            from
              rfs_m_shisetsu s
              left join rfs_t_chk_main tcm
                on s.sno = tcm.sno
              left join rfs_m_shisetsu_kbn msk
                on s.shisetsu_kbn = msk.shisetsu_kbn
              left join rfs_m_shisetsu_keishiki mske
                on s.shisetsu_kbn = mske.shisetsu_kbn
                and s.shisetsu_keishiki_cd = mske.shisetsu_keishiki_cd
              left join rfs_m_rosen r
                on s.rosen_cd = r.rosen_cd
              left join rfs_m_dogen d
                on s.dogen_cd = d.dogen_cd
              left join rfs_m_syucchoujo ms
                on s.syucchoujo_cd = ms.syucchoujo_cd
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
              left join rfs_m_phase mp
                on tch.phase = mp.phase

            where
              s.sno = $sno
              and tcm.chk_times = (
                select
                    max(chk_times)
                from
                  rfs_t_chk_main
                where
                  chk_mng_no = $chk_mng_no
              )
              and tcm.chk_mng_no = $chk_mng_no

            order by
              struct_idx;
EOF;
        }

        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

//        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
//        log_message('debug', "result=$r");

        return $result;
    }


    /**
     * 基本情報の取得（過去データ）
     *
     * @param $get getクエリ <br>
     *        $get['chk_mng_no']        点検管理番号
     *        $get['sno']               施設シリアル番号
     *        $get['struct_idx']        支柱インデックス
     * @return array
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     */
    public function get_baseinfo_past($get){
        log_message('debug', 'get_baseinfo_past');

        if (($get['chk_mng_no'] == "") || ($get['sno'] == "") || ($get['struct_idx'] == "")) {
            // 終了
            return null;
        }

        $chk_mng_no = $get['chk_mng_no'];
        $sno = $get['sno'];
        $struct_idx = $get['struct_idx'];

        $DB_rfs = $this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        $sql= <<<EOF
        select
          jsonb_set(
            '{}'
            , '{baseinfo_past}'
            , jsonb_agg(
                to_jsonb("baseinfo_past")
            )
          ) as baseinfo_past_row
        from
          (
            select
                s.shisetsu_cd
              , s.shisetsu_ver
              , s.shisetsu_kbn
              , msk.shisetsu_kbn_nm
              , s.shisetsu_keishiki_cd
              , mske.shisetsu_keishiki_nm
              , cm.chk_mng_no
              , cm.chk_times
              , tch.rireki_no
              , cm.struct_idx
              , cm.target_dt
              , s.senyou
              , to_char(tch.chk_dt,'YYYY-MM-DD') as chk_dt
              , tch.chk_company
              , tch.chk_person
              , to_char(tch.investigate_dt,'YYYY-MM-DD') as investigate_dt
              , tch.investigate_company
              , tch.investigate_person
              , case
                when tch.surface is null
                then 0
                else tch.surface
                end as surface
              , tch.part_notable_chk
              , tch.reason_notable_chk
              , tch.special_report
              , tch.phase
              , tch.syoken
              , tch.update_dt
              , case
                when tch.check_shisetsu_judge is null
                then 0
                else tch.check_shisetsu_judge
                end as check_shisetsu_judge
              , case
                when tch.measures_shisetsu_judge is null
                then 0
                else tch.measures_shisetsu_judge
                end as measures_shisetsu_judge
              , s.sp

--              , case
--                  when s.lr = 0
--                  then '左'
--                  else '右'
--                  end as lr

              , case
                when s.lr = 0
                  then '左'
                when s.lr = 1
                  then '右'
                when s.lr = 2
                  then '中央'
                when s.lr = 3
                  then '左右'
                else '-'
                end lr

            from
              rfs_m_shisetsu s left join rfs_t_chk_main cm
                on s.sno = cm.sno
              left join rfs_m_shisetsu_kbn msk
                on s.shisetsu_kbn = msk.shisetsu_kbn
              left join rfs_m_shisetsu_keishiki mske
                on s.shisetsu_kbn = mske.shisetsu_kbn
                and s.shisetsu_keishiki_cd = mske.shisetsu_keishiki_cd
              left join rfs_m_rosen r
                on s.rosen_cd = r.rosen_cd
              left join rfs_m_dogen d
                on s.dogen_cd = d.dogen_cd
              left join rfs_m_syucchoujo ms
                on s.syucchoujo_cd = ms.syucchoujo_cd
              left join rfs_t_chk_huzokubutsu tch
                on cm.chk_mng_no = tch.chk_mng_no

            where
              s.sno = $sno
              and cm.chk_times = (
                select
                    max(chk_times)
                from
                  rfs_t_chk_main
                where
                  chk_mng_no = $chk_mng_no
                  and struct_idx = $struct_idx
              )
              and tch.rireki_no = (
                select
                    max(rireki_no)
                from
                  rfs_t_chk_huzokubutsu
                where
                  chk_mng_no = cm.chk_mng_no
              )
              and cm.chk_mng_no < $chk_mng_no
            order by
              struct_idx
          ) as baseinfo_past
EOF;
        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

//        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
//        log_message('debug', "result=$r");

        return $result;
    }

    /**
     * 施設の健全性の取得
     *
     * @param $get getクエリ <br>
     *        $get['sno']               施設シリアル番号
     *        $get['phase']             フェーズ
     * @return array
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     */
    public function get_shisetsu_judge($get){
        log_message('debug', 'get_shisetsu_judge');

        if (($get['sno'] == "") || ($get['chk_mng_no'] == "")) {
            // 終了
            return null;
        }

        $sno = $get['sno'];
        $chk_mng_no = $get['chk_mng_no'];

        $DB_rfs = $this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        $sql= <<<EOF
        select
          jsonb_set(
            '{}'
            , '{measures_judges}'
            , jsonb_agg(
                to_jsonb("measures_judges")
            )
          ) measures_judge_row
        from
          (
            select
              msj.shisetsu_judge_nm
              , tch.rireki_no
              , tch.phase
              , case
                when tch.check_shisetsu_judge is null
                then 0
                else tch.check_shisetsu_judge
                end as check_shisetsu_judge
              , case
                when tch.measures_shisetsu_judge is null
                then 0
                else tch.measures_shisetsu_judge
                end as measures_shisetsu_judge

            from
              rfs_m_shisetsu s left join rfs_t_chk_main cm
                on s.sno = cm.sno
              left join rfs_t_chk_huzokubutsu tch
                on cm.chk_mng_no = tch.chk_mng_no
              left join
                rfs_m_shisetsu_judge msj
                on msj.shisetsu_judge = tch.measures_shisetsu_judge

            where
              s.sno = $sno
              and cm.chk_times = (
                select
                    max(chk_times)
                from
                  rfs_t_chk_main
                where
                  chk_mng_no = $chk_mng_no
              )
              and tch.chk_mng_no = $chk_mng_no

            order by
              tch.rireki_no
          ) as measures_judges
EOF;
        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

//        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
//        log_message('debug', "result=$r");

        return $result;
    }

    /**
     * 部材以下データの取得
     *
     * @param $get getクエリ <br>
     *        $get['shisetsu_kbn']  施設区分
     *        $get['chk_mng_no']    点検管理番号
     *        $get['rireki_no']     履歴番号
     * @return array
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     */
    public function get_chkdata($get){
        log_message('debug', 'get_chkdata');

        if ($get['shisetsu_kbn']==''){
            // 終了
            return null;
        }
        if ($get['chk_mng_no']==''){
            // 終了
            return null;
        }
        if ($get['rireki_no']==''){
            // 終了
            return null;
        }

        $shisetsu_kbn=$get['shisetsu_kbn'];
        $chk_mng_no=$get['chk_mng_no'];
        $rireki_no=$get['rireki_no'];

        $DB_rfs=$this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        $sql= <<<EOF
select
    buzai.shisetsu_kbn
  , sk.shisetsu_kbn_nm
  , (
      select
        case when count(*) = 0
        then 'true'
        else 'false'
        end as is_new
      from
        rfs_t_chk_buzai tcb
      where
        chk_mng_no = $chk_mng_no
        and rireki_no = 0
    ) is_new
  , jsonb_set(
    '{}'
    , '{buzai}'
    , jsonb_agg(
      to_jsonb("buzai") - 'shisetsu_kbn'
      order by
        buzai_cd
    )
  ) as buzai_row
from
  (
    select
        buzai_detail.shisetsu_kbn
      , buzai_detail.buzai_cd
      , b.buzai_nm
      , case
        when tcb.check_buzai_judge is null
        then 0
        else tcb.check_buzai_judge
        end as check_buzai_judge
      , case
        when tcb.measures_buzai_judge is null
        then 0
        else tcb.measures_buzai_judge
        end as measures_buzai_judge
      , case
        when tcb.hantei1 is null
        then ''
        else tcb.hantei1
        end as hantei1
      , case
        when tcb.hantei2 is null
        then ''
        else tcb.hantei2
        end as hantei2
      , case
        when tcb.hantei3 is null
        then ''
        else tcb.hantei3
        end as hantei3
      , case
        when tcb.hantei4 is null
        then ''
        else tcb.hantei4
        end as hantei4
      , jsonb_agg(
        to_jsonb("buzai_detail") - 'shisetsu_kbn' - 'buzai_cd' - 'check_buzai_judge' - 'measures_buzai_judge'
        - 'hantei1' - 'hantei2' - 'hantei3' - 'hantei4'
        order by
          buzai_detail_cd
      )     as buzai_detail_row
    from
      (
        select
            tenken_kasyo.shisetsu_kbn
          , tenken_kasyo.buzai_cd
          , tenken_kasyo.buzai_detail_cd
          , bd.buzai_detail_nm
          , bd.sample_url
          , jsonb_agg(
--            to_jsonb("tenken_kasyo") - 'shisetsu_kbn' - 'buzai_cd' - 'buzai_detail_cd'
            to_jsonb("tenken_kasyo")
            order by
              tenken_kasyo_cd
          )     as tenken_kasyo_row
        from
          (
            select
                sonsyou_naiyou.shisetsu_kbn
              , sonsyou_naiyou.buzai_cd
              , sonsyou_naiyou.buzai_detail_cd
              , sonsyou_naiyou.tenken_kasyo_cd
              , sonsyou_naiyou.tenken_kasyo_nm
              , sonsyou_naiyou.sign
--              , tctk.chk_mng_no
              , case
                when tctk.chk_mng_no is null
                then $chk_mng_no
                else tctk.chk_mng_no
                end as chk_mng_no
--              , tctk.rireki_no
              , case
                when tctk.rireki_no is null
                then $rireki_no
                else tctk.rireki_no
                end as rireki_no
              , case
                when tctk.sonsyou_naiyou_cd is null
                then 0
                else tctk.sonsyou_naiyou_cd
                end as sonsyou_naiyou_cd
              , case
                when tctk.taisyou_umu is null
                then true
                when tctk.taisyou_umu = 0
                then true
                else false
                end as taisyou_umu
              , tctk.check_status
              , case
                when tctk.check_judge is null
                then 1
                when tctk.check_judge = -1
                then 1
                else tctk.check_judge
                end as check_judge
              , check_judge as check_judge_pre
              , case
                when tctk.measures_judge is null
                then 0
                when tctk.measures_judge = -1
                then 0
                else tctk.measures_judge
                end as measures_judge
              , case
                when tctk.screening is null
                then 0
                when tctk.screening = -1
                then 0
                else tctk.screening
                end as screening
              , case
                when tctk.screening = 1
                then true
                else false
                end as screening_flg
              , case
                when tctk.screening_taisyou is null
                then 0
                when tctk.screening_taisyou = -1
                then 0
                else tctk.screening_taisyou
                end as screening_taisyou
              , case
                when tctk.screening_taisyou is null
                then 0
                when tctk.screening_taisyou = -1
                then 0
                else tctk.screening_taisyou
                end as registered_screening_taisyou
              , case
                when tctk.screening = 1
                then true
                else false
                end as registered_screening_flg
              , case
                when tctk.check_policy is null
                then 0
                else tctk.check_policy
                end as check_policy
              , case
                when tctk.check_policy is null
                then 0
                else tctk.check_policy
                end as registered_check_policy
              , case
                when tctk.check_policy = 0
                then '－'
                when tctk.check_policy = 1
                then 'スクリーニング'
                when tctk.check_policy = 2
                then '詳細調査'
                when tctk.check_policy = 3
                then '詳細調査済'
                when tctk.check_policy = 4
                then 'スクリーニング済'
                else '－'
                end as check_policy_str
              , case
                when tctk.measures_policy is null
                then ''
                else tctk.measures_policy
                end as measures_policy
              , to_char(tctk.measures_dt,'YYYY-MM-DD') as measures_dt
--              , case
--                when tctk.picture_cd_before is null
--                then 0
--                when tctk.picture_cd_before = -1
--                then 0
--                else tctk.picture_cd_before
--                end as picture_cd_before
--              , case
--                when tctk.picture_cd_after is null
--                then 0
--                when tctk.picture_cd_after = -1
--                then 0
--                else tctk.picture_cd_after
--                end as picture_cd_after
              , case
                when tctk.check_bikou is null
                then ''
                else tctk.check_bikou
                end as check_bikou
              , case
                when tctk.measures_bikou is null
                then ''
                else tctk.measures_bikou
                end as measures_bikou
              , jsonb_agg(
                to_jsonb("sonsyou_naiyou") - 'shisetsu_kbn' - 'buzai_cd' - 'buzai_detail_cd' - 'tenken_kasyo_cd' - 'tenken_kasyo_nm'
                 - 'sign' - 'check_policy' - 'measures_policy' - 'measures_dt'
                 - 'check_bikou' - 'measures_bikou' - 'screening'
                order by
                  sonsyou_naiyou.sonsyou_naiyou_cd
              )      as sonsyou_naiyou_row
            from
              (
                select
                    mcs.shisetsu_kbn
                  , mcs.buzai_cd
                  , mcs.buzai_detail_cd
                  , mcs.tenken_kasyo_cd
                  , tk.tenken_kasyo_nm
                  , tk.sign
                  , mcs.sonsyou_naiyou_cd
                  , sn.sonsyou_naiyou_nm
                  , case
                    when dat.check_before is null
                    then 1
                    else dat.check_before
                    end as check_before
                  , case
                    when dat.measures_after is null
                    then 0
                    else dat.measures_after
                    end as measures_after
                from
                  rfs_m_chk_sonsyou mcs
                  left join (
                    select
                        tcs.chk_mng_no
                      , shisetsu.shisetsu_kbn
                      , tcs.buzai_cd
                      , tcs.buzai_detail_cd
                      , tcs.tenken_kasyo_cd
                      , tcs.sonsyou_naiyou_cd
                      , case
                        when tcs.check_before is null
                        then 1
                        when tcs.check_before = -1
                        then 1
                        else tcs.check_before
                        end as check_before
                      , case
                        when tcs.measures_after is null
                        then 0
                        when tcs.measures_after = -1
                        then 0
                        else tcs.measures_after
                        end as measures_after
                    from
                      (
                        select
                            *
                        from
                          rfs_t_chk_sonsyou
                        where
                          chk_mng_no = $chk_mng_no
                          and rireki_no = $rireki_no
                      ) tcs
                      left join (
                        select
                            s.shisetsu_kbn
                          , cm.chk_mng_no
                        from
                          rfs_m_shisetsu s join rfs_t_chk_main cm
                            on s.sno = cm.sno
                        where
                          cm.chk_mng_no = $chk_mng_no
                      ) shisetsu
                        on tcs.chk_mng_no = shisetsu.chk_mng_no
                  ) dat
                    on mcs.shisetsu_kbn = dat.shisetsu_kbn
                    and mcs.buzai_cd = dat.buzai_cd
                    and mcs.buzai_detail_cd = dat.buzai_detail_cd
                    and mcs.tenken_kasyo_cd = dat.tenken_kasyo_cd
                    and mcs.sonsyou_naiyou_cd = dat.sonsyou_naiyou_cd
                  left join rfs_m_tenken_kasyo tk
                    on mcs.shisetsu_kbn = tk.shisetsu_kbn
                    and mcs.buzai_cd = tk.buzai_cd
                    and mcs.buzai_detail_cd = tk.buzai_detail_cd
                    and mcs.tenken_kasyo_cd = tk.tenken_kasyo_cd
                  left join rfs_m_sonsyou_naiyou sn
                    on mcs.sonsyou_naiyou_cd = sn.sonsyou_naiyou_cd
                where
                  mcs.shisetsu_kbn = $shisetsu_kbn
              ) sonsyou_naiyou
              left join (
                select
                    *
                from
                  rfs_t_chk_tenken_kasyo
                where
                  chk_mng_no = $chk_mng_no
                  and rireki_no = $rireki_no
              ) tctk
                on sonsyou_naiyou.buzai_cd = tctk.buzai_cd
                and sonsyou_naiyou.buzai_detail_cd = tctk.buzai_detail_cd
                and sonsyou_naiyou.tenken_kasyo_cd = tctk.tenken_kasyo_cd
            group by
              sonsyou_naiyou.shisetsu_kbn
              , sonsyou_naiyou.buzai_cd
              , sonsyou_naiyou.buzai_detail_cd
              , sonsyou_naiyou.tenken_kasyo_cd
              , sonsyou_naiyou.tenken_kasyo_nm
              , sonsyou_naiyou.sign
              , tctk.chk_mng_no
              , tctk.rireki_no
              , tctk.sonsyou_naiyou_cd
              , tctk.taisyou_umu
              , tctk.check_status
              , tctk.check_judge
              , tctk.measures_judge
              , tctk.screening
              , tctk.screening_taisyou
              , tctk.check_policy
              , tctk.measures_policy
              , tctk.measures_dt
--              , tctk.picture_cd_before
--              , tctk.picture_cd_after
              , tctk.check_bikou
              , tctk.measures_bikou
          ) tenken_kasyo
          left join rfs_m_buzai_detail bd
            on tenken_kasyo.shisetsu_kbn = bd.shisetsu_kbn
            and tenken_kasyo.buzai_cd = bd.buzai_cd
            and tenken_kasyo.buzai_detail_cd = bd.buzai_detail_cd
        group by
          tenken_kasyo.shisetsu_kbn
          , tenken_kasyo.buzai_cd
          , tenken_kasyo.buzai_detail_cd
          , bd.buzai_detail_nm
          , bd.sample_url
      ) buzai_detail
      left join rfs_m_buzai b
        on buzai_detail.shisetsu_kbn = b.shisetsu_kbn
        and buzai_detail.buzai_cd = b.buzai_cd
      left join (
        select
            *
        from
          rfs_t_chk_buzai
        where
          chk_mng_no = $chk_mng_no
          and rireki_no = $rireki_no
      ) tcb
        on buzai_detail.buzai_cd = tcb.buzai_cd
    group by
      buzai_detail.shisetsu_kbn
      , buzai_detail.buzai_cd
      , b.buzai_nm
      , tcb.check_buzai_judge
      , tcb.measures_buzai_judge
      , tcb.hantei1
      , tcb.hantei2
      , tcb.hantei3
      , tcb.hantei4
  ) buzai
  left join rfs_m_shisetsu_kbn sk
    on buzai.shisetsu_kbn = sk.shisetsu_kbn
group by
  buzai.shisetsu_kbn
  , sk.shisetsu_kbn_nm;
EOF;
        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

//        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
//        log_message('debug', "result=$r");

        return $result;
    }

    /**
     * 部材以下データの取得
     * （ストック点検データは存在しない項目があるため、存在するデータのみ取得する）
     *
     * @param $get getクエリ <br>
     *        $get['shisetsu_kbn']  施設区分
     *        $get['chk_mng_no']    点検管理番号
     *        $get['rireki_no']     履歴番号
     * @return array
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     */
    public function get_chkdata_exist_only($get){
        log_message('debug', 'get_chkdata');

        if ($get['shisetsu_kbn']==''){
            // 終了
            return null;
        }
        if ($get['chk_mng_no']==''){
            // 終了
            return null;
        }
        if ($get['rireki_no']==''){
            // 終了
            return null;
        }

        $shisetsu_kbn=$get['shisetsu_kbn'];
        $chk_mng_no=$get['chk_mng_no'];
        $rireki_no=$get['rireki_no'];

        $DB_rfs=$this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        $sql= <<<EOF
select
    buzai.shisetsu_kbn
  , sk.shisetsu_kbn_nm
  , (
      select
        case when count(*) = 0
        then 'true'
        else 'false'
        end as is_new
      from
        rfs_t_chk_buzai tcb
      where
        chk_mng_no = $chk_mng_no
        and rireki_no = 0
    ) is_new
  , jsonb_set(
    '{}'
    , '{buzai}'
    , jsonb_agg(
      to_jsonb("buzai") - 'shisetsu_kbn'
      order by
        buzai_cd
    )
  ) as buzai_row
from
  (
    select
        buzai_detail.shisetsu_kbn
      , buzai_detail.buzai_cd
      , b.buzai_nm
      , case
        when tcb.check_buzai_judge is null
        then 0
        else tcb.check_buzai_judge
        end as check_buzai_judge
      , case
        when tcb.measures_buzai_judge is null
        then 0
        else tcb.measures_buzai_judge
        end as measures_buzai_judge
      , case
        when tcb.hantei1 is null
        then ''
        else tcb.hantei1
        end as hantei1
      , case
        when tcb.hantei2 is null
        then ''
        else tcb.hantei2
        end as hantei2
      , case
        when tcb.hantei3 is null
        then ''
        else tcb.hantei3
        end as hantei3
      , case
        when tcb.hantei4 is null
        then ''
        else tcb.hantei4
        end as hantei4
      , jsonb_agg(
        to_jsonb("buzai_detail") - 'shisetsu_kbn' - 'buzai_cd' - 'check_buzai_judge' - 'measures_buzai_judge'
        - 'hantei1' - 'hantei2' - 'hantei3' - 'hantei4'
        order by
          buzai_detail_cd
      )     as buzai_detail_row
    from
      (
        select
            tenken_kasyo.shisetsu_kbn
          , tenken_kasyo.buzai_cd
          , tenken_kasyo.buzai_detail_cd
          , bd.buzai_detail_nm
          , bd.sample_url
          , jsonb_agg(
--            to_jsonb("tenken_kasyo") - 'shisetsu_kbn' - 'buzai_cd' - 'buzai_detail_cd'
            to_jsonb("tenken_kasyo")
            order by
              tenken_kasyo_cd
          )     as tenken_kasyo_row
        from
          (
            select
                sonsyou_naiyou.shisetsu_kbn
              , sonsyou_naiyou.buzai_cd
              , sonsyou_naiyou.buzai_detail_cd
              , sonsyou_naiyou.tenken_kasyo_cd
              , sonsyou_naiyou.tenken_kasyo_nm
              , sonsyou_naiyou.sign
--              , tctk.chk_mng_no
              , case
                when tctk.chk_mng_no is null
                then $chk_mng_no
                else tctk.chk_mng_no
                end as chk_mng_no
--              , tctk.rireki_no
              , case
                when tctk.rireki_no is null
                then $rireki_no
                else tctk.rireki_no
                end as rireki_no
              , case
                when tctk.sonsyou_naiyou_cd is null
                then 0
                else tctk.sonsyou_naiyou_cd
                end as sonsyou_naiyou_cd
              , case
                when tctk.taisyou_umu is null
                then false
                when tctk.taisyou_umu = 0
                then true
                else false
                end as taisyou_umu
              , tctk.check_status
              , case
                when tctk.check_judge is null
                then 0
                when tctk.check_judge = -1
                then 0
                else tctk.check_judge
                end as check_judge
              , check_judge as check_judge_pre
              , case
                when tctk.measures_judge is null
                then 0
                when tctk.measures_judge = -1
                then 0
                else tctk.measures_judge
                end as measures_judge
              , case
                when tctk.screening is null
                then 0
                when tctk.screening = -1
                then 0
                else tctk.screening
                end as screening
              , case
                when tctk.screening = 1
                then true
                else false
                end as screening_flg
              , case
                when tctk.screening_taisyou is null
                then 0
                when tctk.screening_taisyou = -1
                then 0
                else tctk.screening_taisyou
                end as screening_taisyou
              , case
                when tctk.check_policy is null
                then 0
                else tctk.check_policy
                end as check_policy
              , case
                when tctk.check_policy = 0
                then '－'
                when tctk.check_policy = 1
                then 'スクリーニング'
                when tctk.check_policy = 2
                then '詳細調査'
                when tctk.check_policy = 3
                then '詳細調査済'
                when tctk.check_policy = 4
                then 'スクリーニング済'
                else '－'
                end as check_policy_str
              , case
                when tctk.measures_policy is null
                then ''
                else tctk.measures_policy
                end as measures_policy
              , to_char(tctk.measures_dt,'YYYY-MM-DD') as measures_dt
--              , case
--                when tctk.picture_cd_before is null
--                then 0
--                when tctk.picture_cd_before = -1
--                then 0
--                else tctk.picture_cd_before
--                end as picture_cd_before
--              , case
--                when tctk.picture_cd_after is null
--                then 0
--                when tctk.picture_cd_after = -1
--                then 0
--                else tctk.picture_cd_after
--                end as picture_cd_after
              , case
                when tctk.check_bikou is null
                then ''
                else tctk.check_bikou
                end as check_bikou
              , case
                when tctk.measures_bikou is null
                then ''
                else tctk.measures_bikou
                end as measures_bikou
              , jsonb_agg(
                to_jsonb("sonsyou_naiyou") - 'shisetsu_kbn' - 'buzai_cd' - 'buzai_detail_cd' - 'tenken_kasyo_cd' - 'tenken_kasyo_nm'
                 - 'sign' - 'check_policy' - 'measures_policy' - 'measures_dt'
                 - 'check_bikou' - 'measures_bikou' - 'screening'
                order by
                  sonsyou_naiyou.sonsyou_naiyou_cd
              )      as sonsyou_naiyou_row
            from
              (
                select
                    mcs.shisetsu_kbn
                  , mcs.buzai_cd
                  , mcs.buzai_detail_cd
                  , mcs.tenken_kasyo_cd
                  , tk.tenken_kasyo_nm
                  , tk.sign
                  , mcs.sonsyou_naiyou_cd
                  , sn.sonsyou_naiyou_nm
                  , case
                    when dat.check_before is null
                    then 0
                    else dat.check_before
                    end as check_before
                  , case
                    when dat.measures_after is null
                    then 0
                    else dat.measures_after
                    end as measures_after
                from
                  rfs_m_chk_sonsyou mcs
                  left join (
                    select
                        tcs.chk_mng_no
                      , shisetsu.shisetsu_kbn
                      , tcs.buzai_cd
                      , tcs.buzai_detail_cd
                      , tcs.tenken_kasyo_cd
                      , tcs.sonsyou_naiyou_cd
                      , case
                        when tcs.check_before is null
                        then 0
                        when tcs.check_before = -1
                        then 0
                        else tcs.check_before
                        end as check_before
                      , case
                        when tcs.measures_after is null
                        then 0
                        when tcs.measures_after = -1
                        then 0
                        else tcs.measures_after
                        end as measures_after
                    from
                      (
                        select
                            *
                        from
                          rfs_t_chk_sonsyou
                        where
                          chk_mng_no = $chk_mng_no
                          and rireki_no = $rireki_no
                      ) tcs
                      left join (
                        select
                            s.shisetsu_kbn
                          , cm.chk_mng_no
                        from
                          rfs_m_shisetsu s join rfs_t_chk_main cm
                            on s.sno = cm.sno
                        where
                          cm.chk_mng_no = $chk_mng_no
                      ) shisetsu
                        on tcs.chk_mng_no = shisetsu.chk_mng_no
                  ) dat
                    on mcs.shisetsu_kbn = dat.shisetsu_kbn
                    and mcs.buzai_cd = dat.buzai_cd
                    and mcs.buzai_detail_cd = dat.buzai_detail_cd
                    and mcs.tenken_kasyo_cd = dat.tenken_kasyo_cd
                    and mcs.sonsyou_naiyou_cd = dat.sonsyou_naiyou_cd
                  left join rfs_m_tenken_kasyo tk
                    on mcs.shisetsu_kbn = tk.shisetsu_kbn
                    and mcs.buzai_cd = tk.buzai_cd
                    and mcs.buzai_detail_cd = tk.buzai_detail_cd
                    and mcs.tenken_kasyo_cd = tk.tenken_kasyo_cd
                  left join rfs_m_sonsyou_naiyou sn
                    on mcs.sonsyou_naiyou_cd = sn.sonsyou_naiyou_cd
                where
                  mcs.shisetsu_kbn = $shisetsu_kbn
              ) sonsyou_naiyou
              left join (
                select
                    *
                from
                  rfs_t_chk_tenken_kasyo
                where
                  chk_mng_no = $chk_mng_no
                  and rireki_no = $rireki_no
              ) tctk
                on sonsyou_naiyou.buzai_cd = tctk.buzai_cd
                and sonsyou_naiyou.buzai_detail_cd = tctk.buzai_detail_cd
                and sonsyou_naiyou.tenken_kasyo_cd = tctk.tenken_kasyo_cd
            group by
              sonsyou_naiyou.shisetsu_kbn
              , sonsyou_naiyou.buzai_cd
              , sonsyou_naiyou.buzai_detail_cd
              , sonsyou_naiyou.tenken_kasyo_cd
              , sonsyou_naiyou.tenken_kasyo_nm
              , sonsyou_naiyou.sign
              , tctk.chk_mng_no
              , tctk.rireki_no
              , tctk.sonsyou_naiyou_cd
              , tctk.taisyou_umu
              , tctk.check_status
              , tctk.check_judge
              , tctk.measures_judge
              , tctk.screening
              , tctk.screening_taisyou
              , tctk.check_policy
              , tctk.measures_policy
              , tctk.measures_dt
--              , tctk.picture_cd_before
--              , tctk.picture_cd_after
              , tctk.check_bikou
              , tctk.measures_bikou
          ) tenken_kasyo
          left join rfs_m_buzai_detail bd
            on tenken_kasyo.shisetsu_kbn = bd.shisetsu_kbn
            and tenken_kasyo.buzai_cd = bd.buzai_cd
            and tenken_kasyo.buzai_detail_cd = bd.buzai_detail_cd
        group by
          tenken_kasyo.shisetsu_kbn
          , tenken_kasyo.buzai_cd
          , tenken_kasyo.buzai_detail_cd
          , bd.buzai_detail_nm
          , bd.sample_url
      ) buzai_detail
      left join rfs_m_buzai b
        on buzai_detail.shisetsu_kbn = b.shisetsu_kbn
        and buzai_detail.buzai_cd = b.buzai_cd
      left join (
        select
            *
        from
          rfs_t_chk_buzai
        where
          chk_mng_no = $chk_mng_no
          and rireki_no = $rireki_no
      ) tcb
        on buzai_detail.buzai_cd = tcb.buzai_cd
    group by
      buzai_detail.shisetsu_kbn
      , buzai_detail.buzai_cd
      , b.buzai_nm
      , tcb.check_buzai_judge
      , tcb.measures_buzai_judge
      , tcb.hantei1
      , tcb.hantei2
      , tcb.hantei3
      , tcb.hantei4
  ) buzai
  left join rfs_m_shisetsu_kbn sk
    on buzai.shisetsu_kbn = sk.shisetsu_kbn
group by
  buzai.shisetsu_kbn
  , sk.shisetsu_kbn_nm;
EOF;
        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

//        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
//        log_message('debug', "result=$r");

        return $result;
    }

    /**
     * 部材以下データの取得（新規追加用）
     *
     * @param $get getクエリ <br>
     *        $get['shisetsu_kbn']  施設区分
     *        $get['chk_mng_no']    点検管理番号
     *        $get['rireki_no']     履歴番号
     * @return array
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     */
    public function get_chkdata_new($get){
        log_message('debug', 'get_chkdata_new');

        if ($get['shisetsu_kbn']==""){
            // 終了
            return null;
        }
        if ($get['chk_mng_no']==""){
            // 終了
            return null;
        }
        if ($get['rireki_no']==""){
            // 終了
            return null;
        }

        $shisetsu_kbn=$get['shisetsu_kbn'];
        $chk_mng_no=$get['chk_mng_no'];
        $rireki_no=$get['rireki_no'];

        $DB_rfs=$this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        $sql= <<<EOF
select
    buzai.shisetsu_kbn
  , sk.shisetsu_kbn_nm
  , jsonb_set(
    '{}'
    , '{buzai}'
    , jsonb_agg(
      to_jsonb("buzai") - 'shisetsu_kbn'
      order by
        buzai_cd
    )
  ) as buzai_row
from
  (
    select
        buzai_detail.shisetsu_kbn
      , buzai_detail.buzai_cd
      , b.buzai_nm
      , case
        when tcb.check_buzai_judge is null
        then 0
        else tcb.check_buzai_judge
        end as check_buzai_judge
      , case
        when tcb.measures_buzai_judge is null
        then 0
        else tcb.measures_buzai_judge
        end as measures_buzai_judge
      , case
        when tcb.hantei1 is null
        then ''
        else tcb.hantei1
        end as hantei1
      , case
        when tcb.hantei2 is null
        then ''
        else tcb.hantei2
        end as hantei2
      , case
        when tcb.hantei3 is null
        then ''
        else tcb.hantei3
        end as hantei3
      , case
        when tcb.hantei4 is null
        then ''
        else tcb.hantei4
        end as hantei4
      , jsonb_agg(
        to_jsonb("buzai_detail") - 'shisetsu_kbn' - 'buzai_cd' - 'check_buzai_judge' - 'measures_buzai_judge'
        - 'hantei1' - 'hantei2' - 'hantei3' - 'hantei4'
        order by
          buzai_detail_cd
      )     as buzai_detail_row
    from
      (
        select
            tenken_kasyo.shisetsu_kbn
          , tenken_kasyo.buzai_cd
          , tenken_kasyo.buzai_detail_cd
          , bd.buzai_detail_nm
          , bd.sample_url
          , jsonb_agg(
            to_jsonb("tenken_kasyo") - 'shisetsu_kbn' - 'buzai_cd' - 'buzai_detail_cd'
            order by
              tenken_kasyo_cd
          )     as tenken_kasyo_row
        from
          (
            select
                sonsyou_naiyou.shisetsu_kbn
              , sonsyou_naiyou.buzai_cd
              , sonsyou_naiyou.buzai_detail_cd
              , sonsyou_naiyou.tenken_kasyo_cd
              , sonsyou_naiyou.tenken_kasyo_nm
              , sonsyou_naiyou.sign
              , case
                when tctk.sonsyou_naiyou_cd is null
                then 0
                else tctk.sonsyou_naiyou_cd
                end as sonsyou_naiyou_cd
              , true as taisyou_umu
              , tctk.check_status
              , case
                when tctk.check_judge is null
                then 1
                when tctk.check_judge = -1
                then 1
                else tctk.check_judge
                end as check_judge
              , case
                when tctk.measures_judge is null
                then 0
                when tctk.measures_judge = -1
                then 0
                else tctk.measures_judge
                end as measures_judge
              , case
                when tctk.screening is null
                then 0
                when tctk.screening = -1
                then 0
                else tctk.screening
                end as screening
              , case
                when tctk.screening = 1
                then true
                else false
                end as screening_flg
              , case
                when tctk.screening_taisyou is null
                then 0
                when tctk.screening_taisyou = -1
                then 0
                else tctk.screening_taisyou
                end as screening_taisyou
              , case
                when tctk.check_policy is null
                then 0
                else tctk.check_policy
                end as check_policy
              , case
                when tctk.measures_policy is null
                then ''
                else tctk.measures_policy
                end as measures_policy
              , to_char(tctk.measures_dt,'YYYY-MM-DD') as measures_dt
--              , case
--                when tctk.picture_cd_before is null
--                then 0
--                when tctk.picture_cd_before = -1
--                then 0
--                else tctk.picture_cd_before
--                end as picture_cd_before
--              , case
--                when tctk.picture_cd_after is null
--                then 0
--                when tctk.picture_cd_after = -1
--                then 0
--                else tctk.picture_cd_after
--                end as picture_cd_after
              , case
                when tctk.check_bikou is null
                then ''
                else tctk.check_bikou
                end as check_bikou
              , case
                when tctk.measures_bikou is null
                then ''
                else tctk.measures_bikou
                end as measures_bikou
              , jsonb_agg(
                to_jsonb("sonsyou_naiyou") - 'shisetsu_kbn' - 'buzai_cd' - 'buzai_detail_cd' - 'tenken_kasyo_cd' - 'tenken_kasyo_nm'
                 - 'sign' - 'check_policy' - 'measures_policy' - 'measures_dt'
                 - 'check_bikou' - 'measures_bikou' - 'screening'
                order by
                  sonsyou_naiyou.sonsyou_naiyou_cd
              )      as sonsyou_naiyou_row
            from
              (
                select
                    mcs.shisetsu_kbn
                  , mcs.buzai_cd
                  , mcs.buzai_detail_cd
                  , mcs.tenken_kasyo_cd
                  , tk.tenken_kasyo_nm
                  , tk.sign
                  , mcs.sonsyou_naiyou_cd
                  , sn.sonsyou_naiyou_nm
                  , case
                    when dat.check_before is null
                    then 1
                    else dat.check_before
                    end as check_before
                  , case
                    when dat.measures_after is null
                    then 0
                    else dat.measures_after
                    end as measures_after
                from
                  rfs_m_chk_sonsyou mcs
                  left join (
                    select
                        tcs.chk_mng_no
                      , shisetsu.shisetsu_kbn
                      , tcs.buzai_cd
                      , tcs.buzai_detail_cd
                      , tcs.tenken_kasyo_cd
                      , tcs.sonsyou_naiyou_cd
                      , case
                        when tcs.check_before is null
                        then 1
                        when tcs.check_before = -1
                        then 1
                        else tcs.check_before
                        end as check_before
                      , case
                        when tcs.measures_after is null
                        then 0
                        when tcs.measures_after = -1
                        then 0
                        else tcs.measures_after
                        end as measures_after
                    from
                      (
                        select
                            *
                        from
                          rfs_t_chk_sonsyou
                        where
                          chk_mng_no = $chk_mng_no
                          and rireki_no = $rireki_no
                      ) tcs
                      left join (
                        select
                            s.shisetsu_kbn
                          , cm.chk_mng_no
                        from
                          rfs_m_shisetsu s join rfs_t_chk_main cm
                            on s.sno = cm.sno
                        where
                          cm.chk_mng_no = $chk_mng_no
                      ) shisetsu
                        on tcs.chk_mng_no = shisetsu.chk_mng_no
                  ) dat
                    on mcs.shisetsu_kbn = dat.shisetsu_kbn
                    and mcs.buzai_cd = dat.buzai_cd
                    and mcs.buzai_detail_cd = dat.buzai_detail_cd
                    and mcs.tenken_kasyo_cd = dat.tenken_kasyo_cd
                    and mcs.sonsyou_naiyou_cd = dat.sonsyou_naiyou_cd
                  left join rfs_m_tenken_kasyo tk
                    on mcs.shisetsu_kbn = tk.shisetsu_kbn
                    and mcs.buzai_cd = tk.buzai_cd
                    and mcs.buzai_detail_cd = tk.buzai_detail_cd
                    and mcs.tenken_kasyo_cd = tk.tenken_kasyo_cd
                  left join rfs_m_sonsyou_naiyou sn
                    on mcs.sonsyou_naiyou_cd = sn.sonsyou_naiyou_cd
                where
                  mcs.shisetsu_kbn = $shisetsu_kbn
              ) sonsyou_naiyou
              left join (
                select
                    *
                from
                  rfs_t_chk_tenken_kasyo
                where
                  chk_mng_no = $chk_mng_no
                  and rireki_no = $rireki_no
              ) tctk
                on sonsyou_naiyou.buzai_cd = tctk.buzai_cd
                and sonsyou_naiyou.buzai_detail_cd = tctk.buzai_detail_cd
                and sonsyou_naiyou.tenken_kasyo_cd = tctk.tenken_kasyo_cd
            group by
              sonsyou_naiyou.shisetsu_kbn
              , sonsyou_naiyou.buzai_cd
              , sonsyou_naiyou.buzai_detail_cd
              , sonsyou_naiyou.tenken_kasyo_cd
              , sonsyou_naiyou.tenken_kasyo_nm
              , sonsyou_naiyou.sign
              , tctk.sonsyou_naiyou_cd
              , tctk.taisyou_umu
              , tctk.check_status
              , tctk.check_judge
              , tctk.measures_judge
              , tctk.screening
              , tctk.screening_taisyou
              , tctk.check_policy
              , tctk.measures_policy
              , tctk.measures_dt
--              , tctk.picture_cd_before
--              , tctk.picture_cd_after
              , tctk.check_bikou
              , tctk.measures_bikou
          ) tenken_kasyo
          left join rfs_m_buzai_detail bd
            on tenken_kasyo.shisetsu_kbn = bd.shisetsu_kbn
            and tenken_kasyo.buzai_cd = bd.buzai_cd
            and tenken_kasyo.buzai_detail_cd = bd.buzai_detail_cd
        group by
          tenken_kasyo.shisetsu_kbn
          , tenken_kasyo.buzai_cd
          , tenken_kasyo.buzai_detail_cd
          , bd.buzai_detail_nm
          , bd.sample_url
      ) buzai_detail
      left join rfs_m_buzai b
        on buzai_detail.shisetsu_kbn = b.shisetsu_kbn
        and buzai_detail.buzai_cd = b.buzai_cd
      left join (
        select
            *
        from
          rfs_t_chk_buzai
        where
          chk_mng_no = $chk_mng_no
          and rireki_no = $rireki_no
      ) tcb
        on buzai_detail.buzai_cd = tcb.buzai_cd
    group by
      buzai_detail.shisetsu_kbn
      , buzai_detail.buzai_cd
      , b.buzai_nm
      , tcb.check_buzai_judge
      , tcb.measures_buzai_judge
      , tcb.hantei1
      , tcb.hantei2
      , tcb.hantei3
      , tcb.hantei4
  ) buzai
  left join rfs_m_shisetsu_kbn sk
    on buzai.shisetsu_kbn = sk.shisetsu_kbn
group by
  buzai.shisetsu_kbn
  , sk.shisetsu_kbn_nm;
EOF;
        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

//        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
//        log_message('debug', "result=$r");

        return $result;
    }

    /**
     * パトロール員と調査員の一覧の取得
     *
     * @param $get getクエリ <br>
     *        $get['syozoku_cd']        所属コード（セッションから）
     *        $get['syucchoujo_cd']     出張所コード（セッションから）
     *        $get['busyo_cd']          部署コード（セッションから）
     * @return array
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     */
    public function get_patrolin_investigator($get) {
        log_message('debug', 'get_patrolin_investigator');

        $DB_imm = $this->load->database('imm',TRUE);
        if ($DB_imm->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        // パトロール員取得
        $patrolin_arr=$this->get_patrolins($DB_imm, $get);

        // 調査員取得
        $investigator_arr=$this->get_investigator($DB_imm, $get);

        return array_merge($patrolin_arr, $investigator_arr);

    }

    /**
     * パトロール員一覧の取得
     *
     * @param $get getクエリ <br>
     *        $get['syozoku_cd']        所属コード（セッションから）
     *        $get['syucchoujo_cd']     出張所コード（セッションから）
     *        $get['busyo_cd']          部署コード（セッションから）
     * @return array
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     */
    protected function get_patrolins($DB_imm, $get) {
        log_message('debug', 'get_patrolins');

        if (($get['syozoku_cd'] == "") || ($get['syucchoujo_cd'] == "") || ($get['busyo_cd'] == "")) {
            // 終了
            return null;
        }

        $syozoku_cd = $get['syozoku_cd'];
        $syucchoujo_cd = $get['syucchoujo_cd'];
        $busyo_cd = $get['busyo_cd'];

        // 所属コードで取得SQLを分岐
        $sql = '';
        if($syozoku_cd == 3) {
//        log_message('debug', 'syozoku_cd : ' . $get['syozoku_cd']);

            // 事業課(3)の場合
            $sql = <<<EOF
select
  jsonb_set(
    '{}'
    , '{patrolins}'
    , jsonb_agg(
        to_jsonb("patrolins")
    )
  )
from
  (
    select
      pt.syucchoujo_cd
      , pt.patrolin_cd
      , (ac.busyo_ryaku || '：'::text) || pt.simei as simei_ryaku
      , ac.busyo_ryaku as company
      , pt.simei
    from
      pt_m_patrolin pt
    left join
      ac_m_busyo ac
      on pt.busyo_cd = ac.busyo_cd
    where
      pt.syucchoujo_cd = $syucchoujo_cd
    order by
      pt.busyo_cd
      , pt.sort_no
  ) as patrolins
EOF;

        // 業者ログイン時は業者のみの点検員を表示
        }else if ($syozoku_cd == 4) {

          $sql = <<<EOF
select
  jsonb_set(
    '{}'
    , '{patrolins}'
    , jsonb_agg(
        to_jsonb("patrolins")
    )
  )
from
  (
    select
      pt.syucchoujo_cd
      , pt.patrolin_cd
      , pt.simei as simei_ryaku
      , ac.busyo_ryaku as company
      , pt.simei
    from
      pt_m_patrolin pt
    left join
      ac_m_busyo ac
      on pt.busyo_cd = ac.busyo_cd
    where
      pt.busyo_cd = $busyo_cd
    order by
      pt.busyo_cd
      , pt.sort_no
  ) as patrolins
EOF;

      } else {
//        log_message('debug', 'syozoku_cd : ' . $get['syozoku_cd']);
            // 事業課(3)以外の場合
            $sql = <<<EOF
select
  jsonb_set(
    '{}'
    , '{patrolins}'
    , jsonb_agg(
        to_jsonb("patrolins")
    )
  )
from
  (
    select
      mp.syucchoujo_cd
      , mp.patrolin_cd
--      , simei as simei_ryaku
      , (ac.busyo_ryaku || '：'::text) || mp.simei as simei_ryaku
--      , '' as company
      , ac.busyo_ryaku as company
      , simei
    from
      pt_m_patrolin mp
    left join ac_m_busyo ac
      on mp.busyo_cd = ac.busyo_cd
    where
      mp.busyo_cd = $busyo_cd
    order by
      sort_no
  ) as patrolins
EOF;
        }

        $query = $DB_imm->query($sql);
        $result = $query->result('array');

        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
        log_message('debug', "result=$r");

        return $result;
    }

    /**
     * 調査員取得
     *
     * @param   道路附属物点検DBコネクション
     * @return array
     *         array['investigator_row'] : <br>
     */
    protected function get_investigator($DB_imm, $get) {

        log_message('debug', 'get_investigator');

        if ($get['syucchoujo_cd'] == "") {
            // 終了
            return null;
        }

        $syucchoujo_cd=$get['syucchoujo_cd'];

        $sql= <<<EOF
select
    jsonb_set('{}', '{investigator_info}', jsonb_agg(to_jsonb(i) - 'sort_no')) AS investigator_row
from
  (
    select
      investigator.busyo_cd
      , b.busyo_ryaku || '：' || investigator.simei as busyo_simei
      , investigator.investigator_cd
      , investigator.simei
      , investigator.sort_no
      , b.busyo_ryaku as company
    from
      rfs_m_investigator investigator
    join
      ac_m_busyo b
    on
    investigator.busyo_cd=b.busyo_cd
    ORDER BY
      investigator.busyo_cd, investigator.sort_no
  ) as i;
EOF;

        $query = $DB_imm->query($sql);
        $result = $query->result('array');

//        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
//        log_message('debug', "result=$r");

        return $result;

    }

    /**
     * 点検管理番号の取得
     *
     * @param $get getクエリ <br>
     *        $get['sno']               施設シリアル番号
     *        $get['struct_idx']        支柱インデックス
     * @return array
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     */
    public function get_chkmngnos($get) {
        log_message('debug', 'get_chkmngnos');

        if (($get['sno'] == "") || ($get['struct_idx'] == "")) {
            // 終了
            return null;
        }

        $sno = $get['sno'];
        $struct_idx = $get['struct_idx'];

        $DB_rfs = $this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        // 点検管理番号の取得
        $sql = <<<EOF
        select
          jsonb_set(
            '{}'
            , '{chkmngnos}'
            , jsonb_agg(
                to_jsonb("chkmngnos")
            )
          ) as chkmngnos
        from
          (
            select
              chk_mng_no
            from
              rfs_t_chk_main tcm
              left join rfs_m_shisetsu ms
                on tcm.sno = ms.sno
            where
              ms.sno = $sno
              and tcm.struct_idx = $struct_idx
            order by
              chk_mng_no
          ) as chkmngnos
EOF;
        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

//        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
//        log_message('debug', "result=$r");

        return $result;
    }

    /**
     * 防雪柵支柱インデックス数の取得
     *
     * @param $get getクエリ <br>
     *        $get['sno']               施設シリアル番号
     * @return array
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     */
    public function get_struct_idx_num($get) {
        log_message('debug', 'get_struct_idx_num');

        if ($get['sno'] == "") {
            // 終了
            return null;
        }

        $sno = $get['sno'];

        $DB_rfs = $this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        // 点検管理番号の取得(親データの分を減算する)
        $sql = <<<EOF
        select
          case when count(struct_idx) > 0 then
            count(struct_idx) - 1
          else
            0
          end as struct_idx_num
        from
          rfs_m_bousetsusaku_shichu
        where
          sno = $sno
EOF;
        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

//        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
//        log_message('debug', "result=$r");

        return $result;
    }

    /**
     * 防雪柵管理情報の取得
     *
     * @param $get getクエリ <br>
     *        $get['sno']               施設シリアル番号
     * @return array
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     */
    public function get_bousetsusaku_mng_info($get) {
        log_message('debug', 'get_bousetsusaku_mng_info');

        if ($get['sno'] == "") {
            // 終了
            return null;
        }

        $sno = $get['sno'];

        $DB_rfs = $this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        // 防雪柵情報の取得
        $sql = <<<EOF
        select
          jsonb_set(
            '{}'
            , '{bssk_parent}'
            , jsonb_agg(
                to_jsonb("bssk_parent")
            )
          ) as bssk_parent_row
        from
          (
            select
              tcb.chk_mng_no
              , tcb.chk_mng_no_struct
              , tcb.comp_flg
              , tch.check_shisetsu_judge
              , tch.measures_shisetsu_judge
            from
              rfs_t_chk_bousetsusaku tcb
              left join rfs_t_chk_main tcm
                on tcb.chk_mng_no = tcm.chk_mng_no
              left join rfs_m_bousetsusaku_shichu mbs
                on tcm.sno = mbs.sno
                and tcm.struct_idx = mbs.struct_idx
              left join rfs_t_chk_huzokubutsu tch
                on tch.chk_mng_no = tcb.chk_mng_no_struct
                and tch.rireki_no = (
                  select
                      max(rireki_no)
                  from
                    rfs_t_chk_huzokubutsu
                  where
                    chk_mng_no = tcb.chk_mng_no_struct
                )
            where
              mbs.sno = $sno
              and mbs.struct_idx = 0
              and tcb.comp_flg = 0
          ) as bssk_parent
EOF;
        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

//        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
//        log_message('debug', "result=$r");

        return $result;
    }

    /**
     * 防雪柵（親）情報の取得
     *
     * @param $get getクエリ <br>
     *        $get['sno']               施設シリアル番号
     * @return array
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     */
    public function get_chkmngno_bssk_parent($get) {
        log_message('debug', 'get_chkmngno_bssk_parent');

        $sno = $get['sno'];

        $DB_rfs=$this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        $sno = $get['sno'];

        $sql = <<<EOF
        select
          mbs.sno
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
          ,tch.chk_dt
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
          ,tch.investigate_dt
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
                struct_idx = 0
                and sno = $sno
            ) mbs
          on ms.sno = mbs.sno
          left join
            (
              select
                chk_mng_no
                , sno
                , struct_idx
              from
                rfs_t_chk_main tcm1
              where not exists
                (
                  select 1
                  from rfs_t_chk_main tcm2
                  where
                    tcm1.sno = tcm2.sno
--                    and tcm1.chk_times = tcm2.chk_times
                    and tcm1.struct_idx = tcm2.struct_idx
                    and tcm1.chk_mng_no < tcm2.chk_mng_no
                )
                and struct_idx = 0
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
          where
            mbs.struct_idx = 0
            and ms.sno = $sno
            and ms.shisetsu_kbn = 4
          order by
            shisetsu_cd
EOF;

        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

//        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
//        log_message('debug', "result=$r");

        return $result;
    }

    /**
     * 防雪柵（親）情報の取得
     *
     * @param $get getクエリ <br>
     *        $get['sno']               施設シリアル番号
     * @return array
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     */
    public function get_chkmngno_bssk_parent_stock($get) {
      log_message('debug', 'get_chkmngno_bssk_parent_stock');

        $sno = $get['sno'];

        $DB_rfs=$this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        $sno = $get['sno'];

        $sql = <<<EOF
        select
          mbs.sno
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
          ,tch.chk_dt
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
          ,tch.investigate_dt
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
                struct_idx = 0
                and sno = $sno
            ) mbs
          on ms.sno = mbs.sno
          left join rfs_t_chk_main tcm
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
                    and tch1.rireki_no > tch2.rireki_no
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
          where
            mbs.struct_idx = 0
            and ms.sno = $sno
            and ms.shisetsu_kbn = 4
            and tcm.chk_times = 0
          order by
            shisetsu_cd
EOF;

        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

//        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
//        log_message('debug', "result=$r");

        return $result;
    }

  /**
     * 形式の取得
     *
     * @param   道路附属物点検DBコネクション
     * @return array
     *         array['investigator_row'] : <br>
     */
  public function get_keishiki($get) {

    log_message('debug', 'get_keishiki');

    $DB_rfs = $this->load->database('rfs',TRUE);
    if ($DB_rfs->conn_id === FALSE) {
      log_message('debug', 'データベースに接続されていません');
      return;
    }

    $shisetsu_kbn = $get['shisetsu_kbn'];

    $sql= <<<EOF
select
    jsonb_set('{}', '{keishiki_info}', jsonb_agg(to_jsonb(k))) AS keishiki_row
from
  (
    select
      *
    from
      rfs_m_shisetsu_keishiki
    where
      shisetsu_kbn=$shisetsu_kbn
    ORDER BY
      shisetsu_keishiki_cd
  ) as k;
EOF;

    $query = $DB_rfs->query($sql);
    $result = $query->result('array');

    //        $r = print_r($result, true);
    //        log_message('debug', "sql=$sql");
    //        log_message('debug', "result=$r");

    return $result;

  }

}
