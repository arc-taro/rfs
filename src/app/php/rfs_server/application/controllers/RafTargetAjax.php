<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**
    コントローラー名：RafTargetAjax
    概要：点検対象登録画面コントローラー
**/
class RafTargetAjax extends BaseController {

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
  public function initRafTarget(){
    log_message('info', "initRafTarget");

    // マスタの取得
    $this->load->model('SchCommon');
    // 建管・出張所取得
    $dogen_syucchoujo=$this->SchCommon->getDogenSyucchoujo($this->get);
    // 施設区分（選択プルダウン）取得
    $shisetsu_kbn=$this->SchCommon->getShisetsuKbnFormulti(2);
    // 路線（選択プルダウン）取得
    $rosen=$this->SchCommon->getRosenFormulti($this->get);
    // 点検の実施状況
    $phase=$this->SchCommon->getPhaseFormulti();
    // 健全性
    $judge=$this->SchCommon->getShisetsuJudgeFormulti();
    // 点検業者
    $chk_gyousya=$this->SchCommon->getGyousyaFormulti($this->get);

    // 設置年度の取得
    $yyyy=date("Y");
    $wareki_list = $this->SchCommon->getWarekiList(1975,$yyyy,"desc");

    // 検索条件がSESSIONにある場合は検索を行う
    $shisetsu_info=array();
    if (isset($this->session['srch_target'])){
      // 元検索と同じ検索を行う
      $srch=$this->session['srch_target'];
      // 施設検索
      $shisetsu_info=$this->srchShisetsuDetail($this->get['dogen_cd'], $this->get['syucchoujo_cd'], $srch);
      // 件数が入っている場合は検索しなかった
      if (isset($shisetsu_info['cnt'])) {
        $result['cnt']=$shisetsu_info['cnt']; // 件数を返却
        $shisetsu_info=[];
      }else{
        $result['cnt']=count($shisetsu_info); // 件数を返却
      }
      $result['srch']=$srch;
    }

    // 戻り値に設定
    $result['dogen_syucchoujo']=$dogen_syucchoujo;
    $result['shisetsu_kbn']=$shisetsu_kbn;
    $result['rosen']=$rosen;
    $result['phase']=$phase;
    $result['judge']=$judge;
    $result['chk_gyousya']=$chk_gyousya;
    $result['shisetsu_info']=$shisetsu_info;
    $result['wareki_list']=$wareki_list;

    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  /**
    * 施設検索
    *   条件を元に施設を検索
    */
  public function srchShisetsu(){
    log_message('info', "srchShisetsu");
    // 建管・出張所コード
    $dogen_cd=$this->post['dogen_cd'];
    $syucchoujo_cd=$this->post['syucchoujo_cd'];
    // 検索条件
    $srch = $this->post['srch'];
    // session書き込み
    $this->rgstSessionSrch($srch,3,1);
    // 施設検索
    $shisetsu_info=$this->srchShisetsuDetail($dogen_cd, $syucchoujo_cd, $srch);
    // 件数が入っている場合は検索しなかった
    if (isset($shisetsu_info['cnt'])) {
      $result['cnt']=$shisetsu_info['cnt']; // 件数を返却
      $shisetsu_info=[];
    }else{
      $result['cnt']=count($shisetsu_info); // 件数を返却
    }
/*
    $r = print_r($result['cnt'], true);
    log_message('debug', "result_cnt・・・・・・・・・・・・・・>".$r."\n");
*/
    $result['shisetsu_info']=$shisetsu_info;
    // 返却
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  /***
   * 施設検索 実際にModelにAccessを行う
   *
   * 引数: $dogen_cd 建管コード
   *      $syucchoujo_cd 出張所コード
   *      $srch 検索項目
   ***/
  protected function srchShisetsuDetail($dogen_cd, $syucchoujo_cd, $srch) {
    // 検索条件を整頓
    $condition = $this->arrangementCondition($dogen_cd, $syucchoujo_cd, $srch);
    $this->load->model('RafTargetModel');

    // 20180606 点検対象登録は件数制限を外すことにする
    /*
    // 件数を先に取得
    $cnt=$this->RafTargetModel->srchShisetsuNum($condition);
    //log_message('debug', "count=$cnt");

    // 700件を超える場合は検索しない
    if ($cnt>=700) {
      $result['cnt']=$cnt;
    }else{
      $result=$this->RafTargetModel->srchShisetsu($condition);
    }
*/
    $result=$this->RafTargetModel->srchShisetsu($condition);
    return $result;
  }

  /**
   * 検索条件をSQLにセットする値として整頓する
   *
   *  POSTされる値が、配列だったり、入っていたり、入っていなかったり
   *  するので、SQLにセットできる状態にし、配列で返却
   *
   * 引数：$dogen_cd 建管コード
   *      $syucchoujo_cd 出張所コード
   *      $srch 検索項目
   *
   * 戻り値：array
   *  dogen_cd 建管コード
   *  syucchoujo_cd 出張所コード
   *  shisetsu_cd 施設コード
   *  secchi_from 設置年度FROM
   *  secchi_to 設置年度TO
   *  sp_from 測点FROM
   *  sp_to 測点TO
   *  shityouson  市町村
   *  azaban  字番
   *  shisetsu_kbn array 施設区分
   *  substitute_road array 代替路の有無
   *  emergency_road array 緊急輸送道路
   *  kyouyou_kbn array 供用区分
   *  rosen array 路線
   */
  protected function arrangementCondition($dogen_cd, $syucchoujo_cd, $srch) {

    $ret = array();

    // 選択プルダウン項目
    $shisetsu_kbn_arr = $srch['shisetsu_kbn_dat_model'];  // 選択された施設区分
    $shisetsu_kbn_all_cnt = $srch['shisetsu_kbn_all_cnt'];  // 全施設区分件数
    $phase_arr = $srch['phase_dat_model'];  // 選択された点検状況
    $phase_all_cnt = $srch['phase_all_cnt'];  // 点検状況件数
    $chk_judge_arr = $srch['chk_judge_dat_model'];  // 選択された点検時健全性
    $chk_judge_all_cnt = $srch['chk_judge_all_cnt'];  // 点検時健全性件数
    $measures_judge_arr = $srch['measures_judge_dat_model'];  // 選択された措置後健全性
    $measures_judge_all_cnt = $srch['measures_judge_all_cnt'];  // 措置後健全性件数
    $rosen_arr = $srch['rosen_dat_model'];  // 選択された路線
    $rosen_all_cnt = $srch['rosen_all_cnt'];  // 全路線件数
    $gyousya_arr = $srch['gyousya_dat_model'];  // 選択された点検業者
    $gyousya_all_cnt = $srch['gyousya_all_cnt'];  // 点検業者件数

    // テキスト項目等
    $ret['dogen_cd'] = $dogen_cd; // 建管コード
    $ret['syucchoujo_cd'] = $syucchoujo_cd; // 出張所コード
    if (isset($srch['shisetsu_cd'])) {
      $ret['shisetsu_cd'] = $srch['shisetsu_cd']; // 施設コード
    }
    if (isset($srch['secchi_nendo_from'])) {
      $ret['secchi_from'] = $srch['secchi_nendo_from']; // 設置年度FROM
    }
    if (isset($srch['secchi_nendo_to'])) {
      $ret['secchi_to'] = $srch['secchi_nendo_to']; // 設置年度TO
    }
    // 路線が複数ある場合は測点は無効
    if (count($rosen_arr)===1) {
      if (isset($srch['sp_from'])) {
        $ret['sp_from'] = $srch['sp_from']; // 測点FROM
      }
      if (isset($srch['sp_to'])) {
        $ret['sp_to'] = $srch['sp_to']; // 測点TO
      }
    }
    if (isset($srch['shityouson'])) {
      $ret['shityouson'] = $srch['shityouson']; // 市町村
    }
    if (isset($srch['azaban'])) {
      $ret['azaban'] = $srch['azaban']; // 字番
    }
    if (isset($srch['include_secchi_null'])) {
      $ret['secchi_null'] = $srch['include_secchi_null']; // 設置年度NULL
    }
    if (isset($srch['zennen_check'])) {
      $ret['zennen_check'] = $srch['zennen_check']; // 前年度検査チェック
    }
    if (isset($srch['not_chk_year'])) {
      $ret['not_chk_year'] = $srch['not_chk_year']; // 過去に点検していない年数
    }
    if (isset($srch['target_nendo_from'])) {
      $ret['target_nendo_from'] = $srch['target_nendo_from']; // 点検対象年度FROM
    }
    if (isset($srch['target_nendo_to'])) {
      $ret['target_nendo_to'] = $srch['target_nendo_to']; // 点検対象年度TO
    }

    if (isset($srch['chk_dt_from'])) {
      $ret['chk_dt_from'] = date('Y-m-d', strtotime($srch['chk_dt_from'])); // 点検日FROM
    }
    if (isset($srch['chk_dt_to'])) {
      $ret['chk_dt_to'] = date('Y-m-d', strtotime($srch['chk_dt_to'])); // 点検日TO
    }
    if (isset($srch['investigate_dt_from'])) {
      $ret['investigate_dt_from'] = date('Y-m-d', strtotime($srch['investigate_dt_from'])); // // 調査日FROM
    }
    if (isset($srch['investigate_dt_to'])) {
      $ret['investigate_dt_to'] = date('Y-m-d', strtotime($srch['investigate_dt_to'])); // // 調査日TO
    }

    // syucchoujo_cd=0の場合（全て）の場合、建管内で被っている路線があるので改めてカウントを取得する
    if ($this->post['syucchoujo_cd']===0) {
      $this->load->model('SchCommon');
      $rosen_all_cnt=(int)$this->SchCommon->getDogenRosenCnt($dogen_cd);
    }

    // 件数が0または全件数と同じ場合はセットしない
    if (count($shisetsu_kbn_arr)!==0 && count($shisetsu_kbn_arr) !== $shisetsu_kbn_all_cnt) {
      $arr = array();
      for ($i=0;$i<count($shisetsu_kbn_arr);$i++) {
        array_push($arr, $shisetsu_kbn_arr[$i]['shisetsu_kbn']);
      }
      $ret['shisetsu_kbn']=$arr;
    }
    // 件数が0または全件数と同じ場合はセットしない
    if (count($phase_arr)!==0 && count($phase_arr) !== $phase_all_cnt) {
      $arr = array();
      for ($i=0;$i<count($phase_arr);$i++) {
        array_push($arr, $phase_arr[$i]['phase']);
      }
      $ret['phase']=$arr;
    }
    // 件数が0または全件数と同じ場合はセットしない
    if (count($chk_judge_arr)!==0 && count($chk_judge_arr) !== $chk_judge_all_cnt) {
      $arr = array();
      for ($i=0;$i<count($chk_judge_arr);$i++) {
        array_push($arr, $chk_judge_arr[$i]['shisetsu_judge']);
      }
      $ret['chk_judge']=$arr;
    }
    // 件数が0または全件数と同じ場合はセットしない
    if (count($measures_judge_arr)!==0 && count($measures_judge_arr) !== $measures_judge_all_cnt) {
      $arr = array();
      for ($i=0;$i<count($measures_judge_arr);$i++) {
        array_push($arr, $measures_judge_arr[$i]['shisetsu_judge']);
      }
      $ret['measures_judge']=$arr;
    }
    // 件数が0または全件数と同じ場合はセットしない
    if (count($rosen_arr)!==0 && count($rosen_arr) !== $rosen_all_cnt) {
      $arr = array();
      for ($i=0;$i<count($rosen_arr);$i++) {
        array_push($arr, $rosen_arr[$i]['rosen_cd']);
      }
      $ret['rosen']=$arr;
    }
    // 件数が0または全件数と同じ場合はセットしない
    if (count($gyousya_arr)!==0 && count($gyousya_arr) !== $gyousya_all_cnt) {
      $arr = array();
      for ($i=0;$i<count($gyousya_arr);$i++) {
        array_push($arr, $gyousya_arr[$i]['busyo_mei']);
      }
      $ret['chk_company']=$arr;
    }
    return $ret;
  }

  // 点検対象施設の
  public function rgstRafTarget(){
    log_message('info', "rgstRafTarget");
/*
    $r = print_r($this->post, true);
    log_message('debug', "POST・・・・・・・・・・・・・・>".$r."\n");
*/
    $this->load->model('RafTargetModel');
    $result=$this->RafTargetModel->rgstRafTarget($this->post);
  }
}
