<?php

/**
 * 点検表の履歴取得を行う
 * 返却は配列
 *
 * @access public
 * @package Model
 */
class Rireki extends CI_Model {

    /**
     * コンストラクタ
     *
     * model Rirekiを初期化する。
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * 過去の点検票の取得
     *
     * @param $get getクエリ <br>
     *        $get['shisetsu_cd']       施設コード
     *        $get['shisetsu_ver']      施設バージョン
     *        $get['struct_idx']        支柱インデックス
     * @return array
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     *         array[''] : <br>
     */
    public function get_rireki_data($get){
        log_message('debug', 'get_rireki_data');

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

        $sql= <<<EOF
        select
          jsonb_set(
            '{}'
            ,  '{past_data}'
            , jsonb_agg(
              to_jsonb("past_data")
            )
          ) past_data_row
        from
          (
            select
              rireki_detail.chk_mng_no
              , json_agg(
                    to_jsonb("rireki_detail") - 'chk_mng_no'
              ) rireki_data
            from
              (
                select
                  tch.chk_mng_no
                  , tch.rireki_no
                  , tch.phase
                  , case when tch.update_dt is null
                    then
                      case when tch.investigate_dt is null
                      then
                        case when tch.chk_dt is not null
                        then tch.chk_dt
                        end
                      else tch.investigate_dt
                      end
                    else tch.update_dt
                    end as update_dt                    
                  , mp.phase_str
                  , case
                      when msj.shisetsu_judge_nm is null then
                        '未実施'
                      else
                        msj.shisetsu_judge_nm
                      end as shisetsu_judge_nm
                  , case
                      when tch.check_shisetsu_judge is null then
                        0
                      else
                        tch.check_shisetsu_judge
                      end as check_shisetsu_judge
                  , case
                      when tch.measures_shisetsu_judge is null then
                        0
                      else
                        tch.measures_shisetsu_judge
                      end as measures_shisetsu_judge
                from
                  rfs_t_chk_huzokubutsu tch
                  left join rfs_m_phase mp
                    on tch.phase = mp.phase
                  left join rfs_m_shisetsu_judge msj
                    on msj.shisetsu_judge = tch.measures_shisetsu_judge
                  left join rfs_t_chk_main tcm
                    on tch.chk_mng_no = tcm.chk_mng_no
                where
                  rireki_no is not null
                  and tcm.sno = $sno
                  and tcm.struct_idx = $struct_idx
                order by
                  chk_mng_no desc, rireki_no desc
              ) rireki_detail
            group by
              rireki_detail.chk_mng_no
            order by
              rireki_detail.chk_mng_no desc
          ) past_data;
EOF;
        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

//        $r = print_r($result, true);
//        log_message('debug', "sql=$sql");
//        log_message('debug', "result=$r");

        return $result;
    }

}
