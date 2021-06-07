<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 施設の検索を行う
 * 返却は配列
 *
 * @access public
 * @package Model
 */
class SchMst extends CI_Model {

    /**
    * コンストラクタ
    *
    * model SchCheckを初期化する。
    */
    public function __construct() {
        parent::__construct();
    }

    /**
     *
     * 施設検索項目の取得
     *
     * マスタとして必要なデータを全て取得し、
     * margeして返却する。
     *
     * 引数：$get
     *
     */
     public function get_srch_entry($get) {

        $DB_rfs = $this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', '道路附属物点検システムデータベースに接続されていません');
            return;
        }

        $DB_imm = $this->load->database('imm',TRUE);
        if ($DB_imm->conn_id === FALSE) {
            log_message('debug', '維持管理システムデータベースに接続されていません');
            return;
        }

        /*******************************************************/
        /* 画面でmultiselectで使用したい項目は、コード・名称で取得する */
        /* またコードは[id]、名称は[label]として取得                */
        /*******************************************************/
        // 建管、出張所の取得
        $d_s_arr=$this->get_dogen_syucchoujo($DB_rfs, $get);
        // 施設種別(multiselect)
        $shisetsu_syu_arr=$this->get_shisetsu_kbn_formulti($DB_rfs);
        // 路線(multiselect)
        $rosen_arr=$this->get_rosen_formulti($DB_rfs, $get);
        // 判定区分(multiselect)
        $chk_shisetsu_judge_arr=$this->get_shisetsu_judge_formulti($DB_rfs);
        // フェーズ(multiselect)
        $phase_arr=$this->get_phase_formulti($DB_rfs);
        // 点検業者(multiselect)
        $gyousya_patrolin_arr=$this->get_gyousya_formulti($DB_imm, $get);
        // 点検員(multiselect)
        $patrolin_arr=$this->get_patrolin_formulti($DB_imm, $get);
        // 調査会社(multiselect)
        $investigator_gyousya_arr=$this->get_investigator_gyousya_formulti($DB_imm, $get);
        // 調査員(multiselect)
        $investigator_arr=$this->get_investigator_formulti($DB_imm, $get);
        // 支柱インデックス(multiselect)
        $struct_idx_arr=$this->get_struct_idx_formulti($DB_rfs, $get);

       return array_merge($d_s_arr, $shisetsu_syu_arr, $rosen_arr, $chk_shisetsu_judge_arr, $phase_arr, $gyousya_patrolin_arr, $investigator_gyousya_arr, $investigator_arr, $struct_idx_arr, $patrolin_arr);
    }

    /**
     *
     * 基本情報登録に必要なマスタデータの取得
     *
     * マスタとして必要なデータを全て取得し、
     * margeして返却する。
     *
     * 引数：$get
     *
     */
    public function getMst_forBaseInfoEdit($get) {

        $DB_rfs = $this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', '道路附属物点検システムデータベースに接続されていません');
            return;
        }

        // 建管、出張所の取得
        $d_s_arr=$this->get_kanrisya($DB_rfs, $get);
        // 施設種別_形式
        $skbn_skei_arr=$this->get_skbn_skei($DB_rfs);
        // 路線
        $rosen_arr=$this->get_rosen($DB_rfs, $get);

        return array_merge($d_s_arr, $skbn_skei_arr, $rosen_arr);
    }

    /**
     * 建管・出張所取得
     *
     * @param   道路附属物点検DBコネクション
     *          $get getクエリ <br>
     *          $get['dogen_cd']          建管コード（セッションから）
     * @return array
     *         array['dogen_row'] : <br>
     */
    protected function get_dogen_syucchoujo($DB_rfs, $get) {

        log_message('debug', 'get_dogen_syucchoujo');

        $sql="";
        $sql.="select ";
        $sql.="jsonb_set('{}', '{dogen_info}', jsonb_agg(to_jsonb(all_info))) AS dogen_row ";
        $sql.="from ";
        $sql.="(select ";
        $sql.="d.*";
        $sql.=", syucchoujo_row ";
        $sql.="from ";
        $sql.="(select ";
        $sql.="* ";
        $sql.="from ";
        $sql.="rfs_m_dogen ";
        // 建管コードが0以外の場合は建管で絞る
        if ($get['dogen_cd'] != "0") {
            $sql.="where dogen_cd=".$get['dogen_cd'];
        }
        $sql.=") d ";
        $sql.="join ";
        $sql.="(select ";
        $sql.="s.dogen_cd ";
        $sql.=", jsonb_set('{}', '{syucchoujo_info}', jsonb_agg(to_jsonb(s)-'dogen_cd')) AS syucchoujo_row ";
        $sql.="from ";
        $sql.="(select syucchoujo_cd, syucchoujo_mei, dogen_cd, lt_lon, lt_lat, rb_lon, rb_lat from rfs_m_syucchoujo order by syucchoujo_cd) as s ";
        $sql.="group by s.dogen_cd order by s.dogen_cd) as s_row ";
        $sql.="on ";
        $sql.="d.dogen_cd=s_row.dogen_cd) all_info ";

        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

        //$r = print_r($result, true);
        //log_message('debug', "sql=$sql");
        //log_message('debug', "result=$r");

        return $result;

    }

    /**
     * 建管・出張所取得
     *
     * @param   道路附属物点検DBコネクション
     *          $get getクエリ <br>
     * @return array
     *         array['kanrisya_row'] : <br>
     */
    protected function get_kanrisya($DB_rfs, $get) {

        log_message('debug', 'get_kanrisya');

        // 建管コード、出張所コードは必須
        if ($get['dogen_cd']=="") {
            return null;
        }
        if ($get['syucchoujo_cd']=="") {
            return null;
        }

        $dogen_cd=$get['dogen_cd'];
        $syucchoujo_cd=$get['syucchoujo_cd'];

        $sql= <<<EOF
select
    d.dogen_cd
  , d.dogen_mei
  , s.syucchoujo_cd
  , s.syucchoujo_mei
--  , s.lat
--  , s.lon
  , s.lt_lat
  , s.rb_lat
  , s.lt_lon
  , s.rb_lon
from
  rfs_m_dogen d join rfs_m_syucchoujo s
    on d.dogen_cd = s.dogen_cd
where
  d.dogen_cd = $dogen_cd
  and s.syucchoujo_cd = $syucchoujo_cd
EOF;


        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

//        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
//        log_message('debug', "result=$r");

        return $result;

    }

    /**
     * 施設区分取得
     *
     * @param   道路附属物点検DBコネクション
     * @return array
     *         array['shisetsu_kbn_row'] : <br>
     */
    protected function get_shisetsu_kbn_formulti($DB_rfs) {

        log_message('debug', 'get_shisetsu_kbn_formulti');

        $sql= <<<EOF
select
    jsonb_set(
    '{}'
    , '{shisetsu_kbn_info}'
    , jsonb_agg(to_jsonb(s) - 'sort_no' )
  ) AS shisetsu_kbn_row
from
  (select shisetsu_kbn as id, shisetsu_kbn_nm as label, shisetsu_kbn, sort_no from rfs_m_shisetsu_kbn order by sort_no) s
where
  id <= 5
EOF;

        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

        //$r = print_r($result, true);
        //        log_message('debug', "sql=$sql");
        //log_message('debug', "result=$r");

        return $result;

    }

    /**
     * 施設区分＆形式取得
     *
     * @param   道路附属物点検DBコネクション
     * @return array
     *         array['s_row'] : <br>
     */
    protected function get_skbn_skei($DB_rfs) {

        log_message('debug', 'get_shisetsu_kbn');

        $sql= <<<EOF
select
    jsonb_set('{}', '{s_info}', jsonb_agg(to_jsonb(s))) AS s_row
from
  (
    select
        skbn.shisetsu_kbn
      , skbn.shisetsu_kbn_nm
      , keishiki_row
    from
      rfs_m_shisetsu_kbn skbn join (
        select
            shisetsu_kbn
          , jsonb_set(
            '{}'
            , '{keishiki_info}'
            , jsonb_agg(
              to_jsonb(rfs_m_shisetsu_keishiki) - 'shisetsu_kbn'
            )
          ) AS keishiki_row
        from
          rfs_m_shisetsu_keishiki
        group by
          shisetsu_kbn
      ) skei
        on skbn.shisetsu_kbn = skei.shisetsu_kbn
    where
      skbn.shisetsu_kbn <= 5
    order by
      sort_no
  ) s
EOF;

        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

        //$r = print_r($result, true);
        //        log_message('debug', "sql=$sql");
        //log_message('debug', "result=$r");

        return $result;

    }

    /**
     * 判定区分取得
     *
     * @param   道路附属物点検DBコネクション
     * @return array
     *         array['shisetsu_judge_row'] : <br>
     */
    protected function get_shisetsu_judge_formulti($DB_rfs) {

        log_message('debug', 'get_shisetsu_judge_formulti');

        $sql= <<<EOF
select
    jsonb_set(
    '{}'
    , '{judge_info}'
    , jsonb_agg(to_jsonb(sj))
  ) AS shisetsu_judge_row
from
 (select shisetsu_judge as id, shisetsu_judge_nm as label, shisetsu_judge from rfs_m_shisetsu_judge order by id) as sj
EOF;

        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

        //$r = print_r($result, true);
        //        log_message('debug', "sql=$sql");
        //log_message('debug', "result=$r");

        return $result;

    }

    /**
     * 路線取得
     *
     * @param   道路附属物点検DBコネクション
     *          $get getクエリ <br>
     *          $get['syucchoujo_cd']          出張所コード（セッションから）
     * @return array
     *         array['rosen_row'] : <br>
     */
    protected function get_rosen_formulti($DB_rfs, $get) {

      log_message('debug', 'get_rosen_formulti');

      // 所属コード判定
      if ($get['syozoku_cd']<=2 || $get['syozoku_cd']==10001) {
        $where_cd="TRUE";
      }else{
        $where_cd="dogen_cd=".$get['dogen_cd'];
      }

      $sql= <<<EOF
select
    jsonb_set('{}', '{rosen_info}', jsonb_agg(to_jsonb(r))) AS rosen_row
from
  (
    select
        vr.rosen_cd                          as id
      , vr.rosen_cd || ' ： ' || vr.rosen_nm as label
      , vr.rosen_cd
      , s.dogen_cd
      , vr.syucchoujo_cd
    from
      v_rosen vr
      left join rfs_m_syucchoujo s
        on vr.syucchoujo_cd = s.syucchoujo_cd
    where
      $where_cd
    ORDER BY
      dogen_cd
      , syucchoujo_cd
      , rosen_cd
  ) as r;
EOF;

        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

        //$r = print_r($result, true);
        //        log_message('debug', "sql=$sql");
        //log_message('debug', "result=$r");

        return $result;

    }

    /**
     * 路線取得
     *
     * @param   道路附属物点検DBコネクション
     *          $get getクエリ <br>
     *          $get['syucchoujo_cd']          出張所コード（セッションから）
     * @return array
     *         array['rosen_row'] : <br>
     */
    protected function get_rosen($DB_rfs, $get) {

        log_message('debug', 'get_rosen');

        if ($get['syucchoujo_cd'] == "") {
            // 終了
            return null;
        }

        $syucchoujo_cd=$get['syucchoujo_cd'];

        $sql= <<<EOF
select
    jsonb_set('{}', '{rosen_info}', jsonb_agg(to_jsonb(r))) AS rosen_row
from
  (
    select
      distinct(rosen_cd) rosen_cd
      , rosen_cd || ' ： ' || rosen_nm as rosen_nm
      , cntx
      , cnty
      , ext1x
      , ext1y
      , ext2x
      , ext2y
    from
      v_rosen1_site
    where
      syucchoujo_cd = $syucchoujo_cd
    ORDER BY
      rosen_cd
  ) as r;
EOF;

        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

//        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
//        log_message('debug', "result=$r");

        return $result;

    }

    /**
     * フェーズ取得
     *
     * @param   道路附属物点検DBコネクション
     * @return array
     *         array['phase_row'] : <br>
     */
    protected function get_phase_formulti($DB_rfs) {

        log_message('debug', 'get_phase_formulti');

        $sql= <<<EOF
select
    jsonb_set(
    '{}'
    , '{phase_info}'
    , jsonb_agg(to_jsonb(p))
  ) AS phase_row
from
  (select
    phase as id
    , phase_str as label
    , phase
    from rfs_m_phase_sum where phase != 4 and phase != 999 order by id) as p
EOF;

        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

        //$r = print_r($result, true);
        //        log_message('debug', "sql=$sql");
        //log_message('debug', "result=$r");

        return $result;

    }

    /**
     * 調査会社取得
     *
     * @param   道路附属物点検DBコネクション
     *          $get getクエリ <br>
     *          $get['syucchoujo_cd']          出張所コード（セッションから）
     * @return array
     *         array['gyousya_row'] : <br>
     */
    protected function get_gyousya_formulti($DB_imm, $get) {

      log_message('debug', 'get_gyousya_formulti');

      $dogen_cd=$get['dogen_cd'];

      // 所属コード判定
      // 管理者が何故か10001なので特別に
      if ($get['syozoku_cd']==1 || $get['syozoku_cd']==10001) {
        // 本庁
        $where_cd="TRUE";
      }else{
        // 建管、出張所、業者
        $where_cd="s.dogen_cd = $dogen_cd";
      }

      $sql= <<<EOF
select
    jsonb_set('{}', '{gyousya_info}', jsonb_agg(to_jsonb(b))) AS gyousya_row
from
  (
    select
        bs.busyo_cd as id
      , bs.busyo_ryaku as label
      , bs.busyo_cd
    , bs.syucchoujo_cd
    from
      ac_m_busyo bs
  left join
    m_syucchoujo s
  on
    bs.syucchoujo_cd = s.syucchoujo_cd
    where
      $where_cd
    ORDER BY
      bs.syucchoujo_cd, bs.busyo_cd
  ) as b;
EOF;

        $query = $DB_imm->query($sql);
        $result = $query->result('array');

        //$r = print_r($result, true);
        //        log_message('debug', "sql=$sql");
        //log_message('debug', "result=$r");

        return $result;

    }

    /**
     * パトロール員取得
     *
     * @param   道路附属物点検DBコネクション
     *          $get getクエリ <br>
     *          $get['syucchoujo_cd']          出張所コード（セッションから）
     * @return array
     *         array['patrolin_row'] : <br>
     */
    protected function get_patrolin_formulti($DB_imm, $get) {

      log_message('debug', 'get_patrolin_formulti');

      // 所属コード判定
      if ($get['syozoku_cd']<=2) {
        return array();
      }
      // 管理者が何故か所属コード10001なので特別に
      if ($get['syozoku_cd']==10001) {
        return array();
      }

      $dogen_cd=$get['dogen_cd'];

      $sql= <<<EOF
select
    jsonb_set('{}', '{patrolin_info}', jsonb_agg(to_jsonb(p) - 'sort_no')) AS patrolin_row
from
  (
    select
        patrolin.patrolin_cd as id
      , patrolin.simei as label
      , patrolin.syucchoujo_cd
      , patrolin.patrolin_cd
      , b.busyo_ryaku as busyo_mei
      , patrolin.busyo_cd
      , patrolin.sort_no
    from
      pt_m_patrolin patrolin
    join
      ac_m_busyo b
    on
    patrolin.busyo_cd=b.busyo_cd
    and
    patrolin.syucchoujo_cd=b.syucchoujo_cd
    left join
    m_syucchoujo s
    on
    patrolin.syucchoujo_cd=s.syucchoujo_cd
    left join
    m_dogen d
    on
    s.dogen_cd=d.dogen_cd
    where
      s.dogen_cd = $dogen_cd
    ORDER BY
      patrolin.syucchoujo_cd, patrolin.busyo_cd, patrolin.sort_no
  ) as p;
EOF;

        $query = $DB_imm->query($sql);
        $result = $query->result('array');

        //$r = print_r($result, true);
        //        log_message('debug', "sql=$sql");
        //log_message('debug', "result=$r");

        return $result;

    }

    /**
     * 調査業者取得
     *
     * @param   道路附属物点検DBコネクション
     * @return array
     *         array['investigator_gyousya_row'] : <br>
     */
    protected function get_investigator_gyousya_formulti($DB_imm, $get) {

        log_message('debug', 'get_investigator_gyousya_formulti');

        $sql= <<<EOF
select
    jsonb_set('{}', '{investigator_gyousya_info}', jsonb_agg(to_jsonb(i) - 'sort_no')) AS investigator_gyousya_row
from
  (
    select
      investigator.busyo_cd as id
      , b.busyo_mei as label
      , investigator.busyo_cd
    from
      rfs_m_investigator investigator
    join
      ac_m_busyo b
    on
    investigator.busyo_cd=b.busyo_cd
    GROUP BY
      investigator.busyo_cd,b.busyo_mei
    ORDER BY
      investigator.busyo_cd
  ) as i;
EOF;

        $query = $DB_imm->query($sql);
        $result = $query->result('array');

        //$r = print_r($result, true);
        //        log_message('debug', "sql=$sql");
        //log_message('debug', "result=$r");

        return $result;

    }

    /**
     * 調査員取得
     *
     * @param   道路附属物点検DBコネクション
     * @return array
     *         array['investigator_row'] : <br>
     */
    protected function get_investigator_formulti($DB_imm, $get) {

        log_message('debug', 'get_investigator_formulti');

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
        investigator.investigator_cd as id
      , investigator.simei as label
      , investigator.investigator_cd
      , b.busyo_mei
      , investigator.busyo_cd
      , investigator.sort_no
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

        //$r = print_r($result, true);
        //        log_message('debug', "sql=$sql");
        //log_message('debug', "result=$r");

        return $result;

    }

    /**
     * 支柱インデックス取得
     *
     * @param   道路附属物点検DBコネクション
     * @return array
     *         array['struct_idx_row'] : <br>
     */
    protected function get_struct_idx_formulti($DB_rfs) {

        log_message('debug', 'get_struct_idx_formulti');

        $sql= <<<EOF
select
    jsonb_set(
    '{}'
    , '{struct_idx_info}'
    , jsonb_agg(to_jsonb(s))
  ) AS struct_idx_row
from
  (select distinct(struct_idx) as id , struct_idx as label , struct_idx as struct_idx from rfs_t_chk_main where struct_idx > 0 order by struct_idx) as s;
EOF;

        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

        //$r = print_r($result, true);
        //        log_message('debug', "sql=$sql");
        //log_message('debug', "result=$r");

        return $result;

    }

}
