<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 施設の検索を行う
 * 返却は配列
 *
 * @access public
 * @package Model
 */
class SchDetailJudge extends CI_Model {

    /**
    * コンストラクタ
    *
    * model SchDetailJudgeを初期化する。
    */
    public function __construct() {
        parent::__construct();
    }

    public function get_srch_detail_judge($get) {

        // GET引数の確認
        if ($get['shisetsu_cd']==""){
            return null; // 終了
        }
        if ($get['shisetsu_ver']==""){
            return null; // 終了
        }
        if ($get['shisetsu_kbn']==""){
            return null; // 終了
        }
        if ($get['chk_mng_no']==""){
            return null; // 終了
        }
        if ($get['rireki_no']==""){
            return null; // 終了
        }

        $shisetsu_cd= $get['shisetsu_cd'];
        $shisetsu_ver = $get['shisetsu_ver'];
        $shisetsu_kbn = $get['shisetsu_kbn'];
        $chk_mng_no = $get['chk_mng_no'];
        $rireki_no = $get['rireki_no'];

        $DB_rfs = $this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', '道路附属物点検システムデータベースに接続されていません');
            return;
        }

        $sql= <<<EOF
select
    ms.dogen_cd
  , d.dogen_mei
  , ms.syucchoujo_cd
  , s.syucchoujo_mei
  , ms.shisetsu_kbn
  , sk.shisetsu_kbn_nm
  , ms.shisetsu_keishiki_cd
  , skei.shisetsu_keishiki_nm
  , ms.shisetsu_cd
  , ms.rosen_cd
  , r.rosen_nm
  , ms.sp
  , ms.shityouson || ms.azaban syozaichi
  , ms.secchi
  , ch.rireki_no
  , ch.check_shisetsu_judge
  , sj_chk_shisetsu.shisetsu_judge_nm check_shisetsu_judge_str
  , ch.measures_shisetsu_judge
  , sj_measeures_shisetsu.shisetsu_judge_nm measures_shisetsu_judge_str
  , cb.buzai_cd
  , mb.buzai_nm
  , cb.check_buzai_judge
  , sj_chk_buzai.shisetsu_judge_nm check_buzai_judge_str
  , cb.measures_buzai_judge
  , sj_measures_buzai.shisetsu_judge_nm measures_buzai_judge_str
  , mbd.buzai_detail_cd
  , mbd.buzai_detail_nm
  , mtk.tenken_kasyo_cd
  , mtk.tenken_kasyo_nm
  , ctk.sonsyou_naiyou_cd
  , sn.sonsyou_naiyou_nm
--  , ctk.check_judge
  , case when ctk.taisyou_umu != 0 then
      0
    else
      ctk.check_judge
    end as check_judge
--  , sj_chk_tenken_kasyo.shisetsu_judge_nm check_judge_str
  , case when ctk.taisyou_umu != 0 then
      '対象外'
    else
      sj_chk_tenken_kasyo.shisetsu_judge_nm
    end as check_judge_str
  , ctk.check_bikou
--  , ctk.measures_judge
  , case when ctk.taisyou_umu != 0 then
      0
    else
      ctk.measures_judge
    end as measures_judge
--  , sj_measures_tenken_kasyo.shisetsu_judge_nm measures_judge_str
  , case when ctk.taisyou_umu != 0 then
      '対象外'
    else
      sj_measures_tenken_kasyo.shisetsu_judge_nm 
    end as measures_judge_str
  , ctk.measures_bikou
  , (
    select
        count(*) as cnt_shisetsu
    from
      rfs_m_tenken_kasyo
    where
      shisetsu_kbn = $shisetsu_kbn
  ) cnt_shisetsu
  , mb_cnt.cnt_buzai
  , mbd_cnt.cnt_buzai_detail
from
  (
    select
        *
    from
      rfs_m_shisetsu
    where
      shisetsu_cd = '$shisetsu_cd'
      and shisetsu_ver = $shisetsu_ver
  ) ms
  left join (
    select
        *
    from
      rfs_t_chk_main
    where
      chk_mng_no = $chk_mng_no
  ) cm
    on ms.sno = cm.sno
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
  left join (
    select
        *
    from
      rfs_t_chk_huzokubutsu
    where
      chk_mng_no = $chk_mng_no
      and rireki_no = $rireki_no
  ) ch
    on cm.chk_mng_no = ch.chk_mng_no
  left join rfs_m_shisetsu_judge sj_chk_shisetsu
    on ch.check_shisetsu_judge = sj_chk_shisetsu.shisetsu_judge
  left join rfs_m_shisetsu_judge sj_measeures_shisetsu
    on ch.measures_shisetsu_judge = sj_measeures_shisetsu.shisetsu_judge
  left join (
    select
        phase
      , phase_str_large
    from
      rfs_m_phase
    group by
      phase
      , phase_str_large
  ) p
    on ch.phase = p.phase
  left join (
    select
        *
    from
      rfs_m_buzai
    where
      shisetsu_kbn = $shisetsu_kbn
  ) mb
    on ms.shisetsu_kbn = mb.shisetsu_kbn
  left join (
    select
        *
    from
      rfs_m_buzai_detail
    where
      shisetsu_kbn = $shisetsu_kbn
  ) mbd
    on mb.buzai_cd = mbd.buzai_cd
  left join (
    select
        *
    from
      rfs_m_tenken_kasyo
    where
      shisetsu_kbn = $shisetsu_kbn
  ) mtk
    on mbd.buzai_cd = mtk.buzai_cd
    and mbd.buzai_detail_cd = mtk.buzai_detail_cd
  left join (
    select
        *
    from
      rfs_t_chk_buzai
    where
      chk_mng_no = $chk_mng_no
      and rireki_no = $rireki_no
  ) cb
    on mb.buzai_cd = cb.buzai_cd
  left join rfs_m_shisetsu_judge sj_chk_buzai
    on cb.check_buzai_judge = sj_chk_buzai.shisetsu_judge
  left join rfs_m_shisetsu_judge sj_measures_buzai
    on cb.measures_buzai_judge = sj_measures_buzai.shisetsu_judge
  left join (
    select
        *
    from
      rfs_t_chk_tenken_kasyo
    where
      chk_mng_no = $chk_mng_no
      and rireki_no = $rireki_no
  ) ctk
    on mtk.buzai_cd = ctk.buzai_cd
    and mtk.buzai_detail_cd = ctk.buzai_detail_cd
    and mtk.tenken_kasyo_cd = ctk.tenken_kasyo_cd
  left join rfs_m_shisetsu_judge sj_chk_tenken_kasyo
    on ctk.check_judge = sj_chk_tenken_kasyo.shisetsu_judge
  left join rfs_m_shisetsu_judge sj_measures_tenken_kasyo
    on ctk.measures_judge = sj_measures_tenken_kasyo.shisetsu_judge
  left join rfs_m_sonsyou_naiyou sn
    on ctk.sonsyou_naiyou_cd = sn.sonsyou_naiyou_cd
  left join (
    select
        buzai_cd
      , count(*) as cnt_buzai
    from
      rfs_m_tenken_kasyo
    where
      shisetsu_kbn = $shisetsu_kbn
    group by
      buzai_cd
  ) mb_cnt
    on mbd.buzai_cd = mb_cnt.buzai_cd
  left join (
    select
        buzai_cd
      , buzai_detail_cd
      , count(*) as cnt_buzai_detail
    from
      rfs_m_tenken_kasyo
    where
      shisetsu_kbn = $shisetsu_kbn
    group by
      buzai_cd
      , buzai_detail_cd
  ) mbd_cnt
    on mbd.buzai_cd = mbd_cnt.buzai_cd
    and mbd.buzai_detail_cd = mbd_cnt.buzai_detail_cd
order by
  cb.buzai_cd
  , mbd.buzai_detail_cd
  , mtk.tenken_kasyo_cd

EOF;

        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

        /*$r = print_r($result, true);
        log_message('debug', "sql=$sql");
        log_message('debug', "result=$r");*/

        return $result;

    }

}
