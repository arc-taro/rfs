<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**
    コントローラー名：RafTopAjax
    概要：道路附属物点検システムTOPコントローラー
**/
class RafTopAjax extends BaseController {

  /**
     * コンストラクタ
     */
  public function __construct() {
    parent::__construct();
  }

  /**
    * 初期データ取得
    *   建管、出張所、セッション情報の建管、出張所でデータ取得
    */
  public function initRafTop(){
    log_message('info', __CLASS__ . '::' . __FUNCTION__);

    // 附属物トップなので、この時点で附属物の検索条件がSESSIONに入っている場合は
    // SESSIONから[srch_fuzokubutsu]を削除する
    // また附属物点検登録についても同様
    $this->rgstSessionSrch("",2,2);
    $this->rgstSessionSrch("",3,2);

    // マスタの取得
    $this->load->model('SchCommon');
    $dogen_syucchoujo=$this->SchCommon->getDogenSyucchoujo($this->get);

    // 設置年度の取得
    //$yyyy=date("Y");
/*
    if(date("m")=="01" || date("m")=="02" || date("m")=="03"){
	    $yyyy=date("Y", strtotime('-1 year'));
    }else{
      $yyyy=date("Y");
    }
*/
    $yyyy = date('Y', strtotime('-3 month'));

    $wareki_list = $this->SchCommon->getWarekiList(2016,$yyyy,"desc");

    // 建管未選択はありえない（前の画面で選択されているはず）
    $dogen_cd=$this->get['dogen_cd'];
    $syucchoujo_cd=$this->get['syucchoujo_cd'];

    // from 初期値 (昨年度)
    $target_nendo_from = "";
    $target_nendo_to = "";

    // sessionにある場合はsessionから
    if (isset($this->session['srch_raftop'])){
      $nendo=$this->session['srch_raftop'];
      $target_nendo_from=$nendo['from'];
      $target_nendo_to=$nendo['to'];
    }else{
      // 年度が無い場合もある
      if ($this->get['target_nendo_from']) {
        $target_nendo_from=$this->get['target_nendo_from'];
      }
      if ($this->get['target_nendo_to']) {
        $target_nendo_to=$this->get['target_nendo_to'];
      }
    }

    $result = $this->getSumChkHuzokubutsu($dogen_cd, $syucchoujo_cd,$target_nendo_from,$target_nendo_to);
    $result["dogen_syucchoujo"] = $dogen_syucchoujo;

    // 検索時の年度を返却する
    $nendo=array('nendo_from' => $target_nendo_from, 'nendo_to' => $target_nendo_to);
    $result['nendo'] = $nendo;

    // 施設区分取得
    $result["shisetsu_kbns"]=$this->SchCommon->getShisetsuKbnsHuzokubutsu();

    //$this->load->model('RafTopModel');
    // 施設区分取得(選択プルダウン用)
    $result["shisetsu_kbns_multi"]=$this->RafTopModel->getShisetsuKbnFormulti()[0];
    // 和暦年度リスト
    $result['wareki_list'] = $wareki_list;

    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  /**
    * 集計取得
    *   引数の建管、出張所の集計結果を返却
    */
  public function getSumAll(){
    log_message('info', "getSumAll");
    // 建管未選択はありえない（前の画面で選択されているはず）
    $dogen_cd=$this->get['dogen_cd'];
    $syucchoujo_cd=$this->get['syucchoujo_cd'];
    // 年度が無い場合もある
    $target_nendo_from="";
    if (isset($this->get['target_nendo_from'])) {
      $target_nendo_from=$this->get['target_nendo_from'];
    }
    $target_nendo_to="";
        if (isset($this->get['target_nendo_to'])) {
      $target_nendo_to=$this->get['target_nendo_to'];
    }
    $result = $this->getSumChkHuzokubutsu($dogen_cd, $syucchoujo_cd,$target_nendo_from,$target_nendo_to);
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  /**
    * 基本情報を取得
    */
  public function getSumChkHuzokubutsu($dogen_cd, $syucchoujo_cd, $from, $to)
  {
    // 範囲内の附属物集計を行う
    $this->load->model('SchCommon');
    $this->load->model('RafTopModel');
    $this->RafTopModel->refreshSumChkHuzokubutsu();
    $this->RafTopModel->refreshSumSochi();

    // 道路標識
    $ret_dh=$this->RafTopModel->getSumChkHuzokubutsu($dogen_cd, $syucchoujo_cd, $from, $to, 1);
    $ret_sochi_dh=$this->RafTopModel->getSumSochi($dogen_cd, $syucchoujo_cd, $from, $to, 1);
    // 道路情報提供装置
    $ret_jd=$this->RafTopModel->getSumChkHuzokubutsu($dogen_cd, $syucchoujo_cd, $from, $to, 2);
    $ret_sochi_jd=$this->RafTopModel->getSumSochi($dogen_cd, $syucchoujo_cd, $from, $to, 2);
    // 道路照明施設
    $ret_ss=$this->RafTopModel->getSumChkHuzokubutsu($dogen_cd, $syucchoujo_cd, $from, $to, 3);
    $ret_sochi_ss=$this->RafTopModel->getSumSochi($dogen_cd, $syucchoujo_cd, $from, $to, 3);
    // 防雪柵
    $ret_bs=$this->RafTopModel->getSumChkHuzokubutsu($dogen_cd, $syucchoujo_cd, $from, $to, 4);
    $ret_sochi_bs=$this->RafTopModel->getSumSochi($dogen_cd, $syucchoujo_cd, $from, $to, 4);
    // 大型スノーポール
    $ret_yh=$this->RafTopModel->getSumChkHuzokubutsu($dogen_cd, $syucchoujo_cd, $from, $to, 5);
    $ret_sochi_yh=$this->RafTopModel->getSumSochi($dogen_cd, $syucchoujo_cd, $from, $to, 5);

    $result_group_rosen = $this->RafTopModel->getSumChkHuzokubutsuGroupRosen($dogen_cd, $syucchoujo_cd, $from, $to);

    // 返却$result作成
    $result["dh"] = $ret_dh;
    $result["dh_sochi"] = $ret_sochi_dh;

    $result["jd"] = $ret_jd;
    $result["jd_sochi"] = $ret_sochi_jd;

    $result["ss"] = $ret_ss;
    $result["ss_sochi"] = $ret_sochi_ss;

    $result["bs"] = $ret_bs;
    $result["bs_sochi"] = $ret_sochi_bs;

    $result["yh"] = $ret_yh;
    $result["yh_sochi"] = $ret_sochi_yh;

    // 施設区分取得
    $shisetsu_kbns = $this->SchCommon->getShisetsuKbnsHuzokubutsu();
    // [1: 道路標識, 3: 道路照明施設, 2: 道路情報提供装置, 4: 防雪柵, 5: 大型スノーポール]
    $shisetsu_kbn_list = array_column($shisetsu_kbns, 'shisetsu_kbn');
    // [5: 完了, -1: 未実施]
    $phase_list = ['5', '-1'];

    // 路線別集計
    // rosen_cd, shisetsu_kbn, phase ごとで集計
    $result_rosen = [];
    $rosen_cd_list = array_unique(array_column($result_group_rosen, 'rosen_cd'));
    $rosen_nm_list = array_unique(array_column($result_group_rosen, 'rosen_nm'));
    $rosen_list = array_combine($rosen_cd_list, $rosen_nm_list);

    $result_rosen = [];
    foreach ($rosen_list as $rosen_cd => $rosen_nm) {
      $rosen_filter = array_filter($result_group_rosen, function($group_rosen) use ($rosen_cd) {
        return $group_rosen['rosen_cd'] == $rosen_cd;
      });
      $count_list = [];

      foreach ($shisetsu_kbn_list as $shisetsu_kbn) {
        foreach ($phase_list as $phase) {
          $target = reset(array_filter($rosen_filter, function($rosen) use ($shisetsu_kbn, $phase) {
            return $rosen['shisetsu_kbn'] == $shisetsu_kbn and $rosen['phase'] == $phase;
          }));
          $count = ($target) ? intval($target['cnt']) : 0;
          $count_list[] = [
            'shisetsu_kbn' => $shisetsu_kbn,
            'phase' => $phase,
            'count' => $count,
          ];
        }
      }

      // 日付変換 (timestamp -> Rx.xx.xx)
      $last_target_dt = max(array_values(array_column($rosen_filter, 'target_dt')));
      $year_diff_reiwa = 2018;
      $year_base = mb_substr($last_target_dt, 0, 4);
      $year = intval($year_base) - $year_diff_reiwa;
      $month = intval(mb_substr($last_target_dt, 5, 2));
      $day = intval(mb_substr($last_target_dt, 8, 2));
      $gengou = ($year_base >= 2018) ? 'R' : 'H';
      $last_target_dt_format = sprintf('%s%s.%s.%s', $gengou, $year, $month, $day);

      $target_dt_list = array_unique(array_column($rosen_filter, 'target_dt'));
      $has_edit = (count($target_dt_list) > 1);

      $result_rosen[] = [
        'rosen_cd' => $rosen_cd,
        'rosen_nm' => $rosen_nm,
        'target_dt' => $last_target_dt_format,
        'has_edit' -> $has_edit,
        'rosen' => $count_list,
      ];
    }

    // 現地点検状況
    // shisetsu_kbn, phase ごとで集計
    $result_rosen_total = [];
    foreach ($shisetsu_kbn_list as $shisetsu_kbn) {
      $tmp_shisetsu_kbn[$shisetsu_kbn] = array_values(array_filter($result_group_rosen, function ($rosen) use ($shisetsu_kbn) {
        return $shisetsu_kbn == $rosen['shisetsu_kbn'];
      }));

      foreach ($phase_list as $phase) {
        $tmp_phase[$shisetsu_kbn][$phase] = array_filter($tmp_shisetsu_kbn[$shisetsu_kbn], function ($rosen) use ($shisetsu_kbn, $phase) {
          return $shisetsu_kbn == $rosen['shisetsu_kbn'] and $phase == $rosen['phase'];
        });

        $result_rosen_total[] = [
          'shisetsu_kbn' => $shisetsu_kbn,
          'phase' => $phase,
          'count' => array_sum(array_values(array_column($tmp_phase[$shisetsu_kbn][$phase], 'cnt'))),
        ];
      }
    }

    $result["rosen_list"] = $rosen_list;
    $result["rosen"] = $result_rosen;
    $result['rosen_total'] = $result_rosen_total;

/*
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/

    return $result;
  }

}
