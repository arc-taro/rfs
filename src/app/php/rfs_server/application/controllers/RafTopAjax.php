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
    log_message('info', "initRafTop");

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
    if(date("m")=="01" || date("m")=="02" || date("m")=="03"){
	    $yyyy=date("Y", strtotime('-1 year'));
    }else{
      $yyyy=date("Y");
    }

    $wareki_list = $this->SchCommon->getWarekiList(2016,$yyyy,"desc");

    // 建管未選択はありえない（前の画面で選択されているはず）
    $dogen_cd=$this->get['dogen_cd'];
    $syucchoujo_cd=$this->get['syucchoujo_cd'];

    // sessionにある場合はsessionから
    if (isset($this->session['srch_raftop'])){
      $nendo=$this->session['srch_raftop'];
      $target_nendo_from="";
      $target_nendo_from=$nendo['from'];
      $target_nendo_to="";
      $target_nendo_to=$nendo['to'];
    }else{
      // 年度が無い場合もある
      $target_nendo_from="";
      if ($this->get['target_nendo_from']) {
        $target_nendo_from=$this->get['target_nendo_from'];
      }
      $target_nendo_to="";
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

    // 和暦年度リスト
    $result['wareki_list']=$wareki_list;

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
  public function getSumChkHuzokubutsu($dogen_cd, $syucchoujo_cd, $from, $to){
    log_message('info', "getSumChkHuzokubutsu");
    // 範囲内の附属物集計を行う
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

/*
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/

    return $result;
  }

}
