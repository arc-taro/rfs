<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**
    コントローラー名：TenkenKeikakuAjax
    概要：点検計画入力画面コントローラー
**/
class TenkenKeikakuAjax extends BaseController {

  protected $DB_rfs;  // rfsコネクション

  /**
     * コンストラクタ
     */
  public function __construct() {
    parent::__construct();
    $this->load->model('TenkenKeikakuModel');
    $this->DB_rfs = $this->load->database('rfs',TRUE);
    if ($this->DB_rfs->conn_id === FALSE) {
      log_message('debug', 'データベースに接続されていません');
      return;
    }
  }

  /**
    * 初期データ取得
    *   建管、出張所、セッション情報の建管、出張所でデータ取得
    */
  public function initTenkenKeikaku(){
    log_message('info', "initTenkenKeikaku");

    // マスタの取得
    $this->load->model('SchCommon');
    // 建管・出張所取得
    $dogen_syucchoujo=$this->SchCommon->getDogenSyucchoujo($this->get);
    // 施設区分（選択プルダウン）取得
    $shisetsu_kbn=$this->SchCommon->getShisetsuKbnFormulti(1);
    // 路線（選択プルダウン）取得
    $rosen=$this->SchCommon->getRosenFormulti($this->get);
    // 和暦リスト取得
    // 設置年度の取得
    $yyyy=date("Y");
    $wareki_list = $this->SchCommon->getWarekiList(1975,$yyyy,"desc");
    $result['wareki_list']=$wareki_list;
    $wareki_list_future = $this->SchCommon->getWarekiListFuture(1975,$yyyy + $this->config->config['tenken_keikaku_year_span'],"desc");
    $result['wareki_list_future']=$wareki_list_future;
    $this->config->load('config');
    $result['tenken_keikaku_year_span'] = $this->config->config['tenken_keikaku_year_span'];

    // 戻り値に設定
    $result['dogen_syucchoujo']=$dogen_syucchoujo;
    $result['shisetsu_kbn']=$shisetsu_kbn;
    $result['rosen']=$rosen;

    // 電気通信URL（電気通信施設画面へのリンクに使用する）
    $this->config->load('config');
    $result["ele_url"]=$this->config->config['www_ele_path'];

    // 検索画面での設定が必要なので返却する
    // $result['srch']=$srch;
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  /**
    * 施設検索
    *   条件を元に施設を検索
    */
  public function srchTenkenShisetsu(){
    log_message('info', "srchTenkenShisetsu");
    // 建管・出張所コード
    $dogen_cd=$this->post['dogen_cd'];
    $syucchoujo_cd=$this->post['syucchoujo_cd'];
    // 検索条件
    $srch = $this->post['srch'];
    // sessionに追加
    // $this->rgstSessionSrch($srch,1,1);
    // 施設検索
    $shisetsu_info=$this->srchShisetsuDetail($dogen_cd, $syucchoujo_cd, $srch);
    // 件数が入っている場合は検索しなかった
    if (isset($shisetsu_info['cnt'])) {
      $result['cnt']=$shisetsu_info['cnt']; // 件数を返却
      $shisetsu_info=[];
    }else{
      $result['cnt']=count($shisetsu_info); // 件数を返却
    }
    $result['shisetsu_info']=$shisetsu_info;

/*
    $r = print_r($result, true);
    log_message('debug', "result・・・・・・・・・・・・・・>".$r."\n");
*/

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

/*
    $r = print_r($srch, true);
    log_message('debug', "srch--------------------->".$r."\n");
*/

    // 検索条件を整頓
    $condition = $this->arrangementCondition($dogen_cd, $syucchoujo_cd, $srch);
/*
    $r = print_r($condition, true);
    log_message('debug', "condition=$r");
*/
    // 件数を先に取得
    $cnt=$this->TenkenKeikakuModel->srchTenkenShisetsuNum($condition);
    //log_message('debug', "count=$cnt");
    // 100件を超える場合は検索しない
    if ($cnt>=100) {
      $result['cnt']=$cnt;
    }else{
      $result=$this->TenkenKeikakuModel->srchTenkenShisetsu($condition);
    }
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
    // $substitute_road_arr = $srch['substitute_road_dat_model'];  // 選択された代替路
    // $substitute_road_all_cnt = $srch['substitute_road_all_cnt'];  // 全代替路件数
    // $emergency_road_arr = $srch['emergency_road_dat_model'];  // 選択された緊急輸送道路
    // $emergency_road_all_cnt = $srch['emergency_road_all_cnt'];  // 全緊急輸送道路件数
    // $kyouyou_kbn_arr = $srch['kyouyou_kbn_dat_model'];  // 選択された供用区分
    // $kyouyou_kbn_all_cnt = $srch['kyouyou_kbn_all_cnt'];  // 全供用区分件数
    $rosen_dat_arr = $srch['rosen_dat_model'];  // 選択された路線
    $rosen_all_cnt = $srch['rosen_all_cnt'];  // 全路線件数

    // テキスト項目等
    $ret['dogen_cd'] = $dogen_cd; // 建管コード
    $ret['syucchoujo_cd'] = $syucchoujo_cd; // 出張所コード
    if (isset($srch['shisetsu_cd'])) {
      $ret['shisetsu_cd'] = $srch['shisetsu_cd']; // 施設コード
    }
    if (isset($srch['secchi_nendo_from']) && $srch['secchi_nendo_from']) {
      $ret['secchi_from'] = $srch['secchi_nendo_from']; // 設置年度FROM
    }
    if (isset($srch['secchi_nendo_to']) && $srch['secchi_nendo_to']) {
      $ret['secchi_to'] = $srch['secchi_nendo_to']; // 設置年度TO
    }
    // 路線が複数ある場合は測点は無効
    if (count($rosen_dat_arr)===1) {
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
      if ($srch['include_secchi_null']) {
        $ret['secchi_null'] = $srch['include_secchi_null']; // 設置年度NULL
      }
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
    // if (count($substitute_road_arr)!==0 && count($substitute_road_arr) !== $substitute_road_all_cnt) {
    //   $arr = array();
    //   for ($i=0;$i<count($substitute_road_arr);$i++) {
    //     array_push($arr, $substitute_road_arr[$i]['id']);
    //   }
    //   $ret['substitute_road']=$arr;
    // }
    // if (count($emergency_road_arr)!==0 && count($emergency_road_arr) !== $emergency_road_all_cnt) {
    //   $arr = array();
    //   for ($i=0;$i<count($emergency_road_arr);$i++) {
    //     array_push($arr, $emergency_road_arr[$i]['id']);
    //   }
    //   $ret['emergency_road']=$arr;
    // }
    // if (count($kyouyou_kbn_arr)!==0 && count($kyouyou_kbn_arr) !== $kyouyou_kbn_all_cnt) {
    //   $arr = array();
    //   for ($i=0;$i<count($kyouyou_kbn_arr);$i++) {
    //     array_push($arr, $kyouyou_kbn_arr[$i]['id']);
    //   }
    //   $ret['kyouyou_kbn']=$arr;
    // }
    if (count($rosen_dat_arr)!==0 && count($rosen_dat_arr) !== $rosen_all_cnt) {
      $arr = array();
      for ($i=0;$i<count($rosen_dat_arr);$i++) {
        array_push($arr, $rosen_dat_arr[$i]['rosen_cd']);
      }
      $ret['rosen']=$arr;
    }

    /* 灯柱番号追加 */
    if (isset($srch['touchuu_no'])) {
      $ret['touchuu_no'] = $srch['touchuu_no']; // 市町村
    }


    log_message("debug",print_r($srch,true));
    log_message("debug",print_r($ret,true));


    return $ret;

  }

  public function saveTenkenKeikaku() {
    log_message('info', __METHOD__);
    $houtei_plans = $this->post['houtei_plans'];
    $huzokubutsu_plans = $this->post['huzokubutsu_plans'];
    $teiki_pat_plans = $this->post['teiki_pat_plans'];
    // 対象となる年度の範囲（DELETE->INSERTするのに必要）
    $target_year_start = $this->post['target_year_start'];
    $target_year_end = $this->post['target_year_end'];
    $shisetsu_list = $this->post['shisetsu_list'];
    
    // トランザクション
    $this->DB_rfs->trans_start();

    $this->TenkenKeikakuModel->deleteOldTenkenKeikaku($shisetsu_list, $target_year_start, $target_year_end);

    foreach($houtei_plans as $plan) {
      $this->TenkenKeikakuModel->insertHouteiTenkenKeikaku($plan['sno'], $plan['shisetsu_kbn'], $plan['struct_idx'], $plan['year']);
    }

    foreach($huzokubutsu_plans as $plan) {
      $this->TenkenKeikakuModel->insertHuzokubutsuTenkenKeikaku($plan['sno'], $plan['shisetsu_kbn'], $plan['struct_idx'], $plan['year']);
    }

    foreach($teiki_pat_plans as $plan) {
      $this->TenkenKeikakuModel->insertTeikiPatTenkenKeikaku($plan['sno'], $plan['shisetsu_kbn'], $plan['struct_idx'], $plan['year']);
    }

    // トランザクション処理
    if ($this->DB_rfs->trans_status() === FALSE) {
      $result['result_cd'] = 400;
      $this->DB_rfs->trans_rollback();
    } else {
      $result['result_cd'] = 200;
      $this->DB_rfs->trans_commit();
    }
    // 返却
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

}
