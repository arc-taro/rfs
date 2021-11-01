<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**
    コントローラー名：FamMainAjax
    概要：道路施設管理システム検索画面コントローラー
**/
class FamMainAjax extends BaseController {

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
  public function initFamMain(){
    log_message('info', "initFamMain");

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

    // 戻り値に設定
    $result['dogen_syucchoujo']=$dogen_syucchoujo;
    $result['shisetsu_kbn']=$shisetsu_kbn;
    $result['rosen']=$rosen;

    // 現在選択中の出張所
    $dogen_cd=$this->session['mngarea']['dogen_cd']; // 建管コード
    $syucchoujo_cd=$this->session['mngarea']['syucchoujo_cd']; // 出張所コード

/*
    log_message('debug', "dogen_cd=$dogen_cd");
    log_message('debug', "syucchoujo_cd=$syucchoujo_cd");
*/

    // GET引数
    $srch_kbn=$this->get['srch_kbn']; // 検索区分

    // 検索
    // sysTopから
    $shisetsu_info=array();
    if ($srch_kbn==1) {
      // srchを作り出す
      $srch=$this->makeSrchFromSysTop($shisetsu_kbn);
      // session書き込み
      $this->rgstSessionSrch($srch,1,1);
      // 施設検索
      $shisetsu_info=$this->srchShisetsuDetail($dogen_cd, $syucchoujo_cd, $srch);
      // 件数が入っている場合は検索しなかった
      if (isset($shisetsu_info['cnt'])) {
        $result['cnt']=$shisetsu_info['cnt']; // 件数を返却
        $shisetsu_info=[];
      }else{
        $result['cnt']=count($shisetsu_info); // 件数を返却
      }
      // sysTop以外
    }else{
      if (isset($this->session['srch_shisetsu'])){
        // 元検索と同じ検索を行う
        $srch=$this->session['srch_shisetsu'];
/*
        $r = print_r($srch, true);
        log_message('debug', "srch:".$r);
*/
        // 施設検索
        $shisetsu_info=$this->srchShisetsuDetail($dogen_cd, $syucchoujo_cd, $srch);
        // 件数が入っている場合は検索しなかった
        if (isset($shisetsu_info['cnt'])) {
          $result['cnt']=$shisetsu_info['cnt']; // 件数を返却
          $shisetsu_info=[];
        }else{
          $result['cnt']=count($shisetsu_info); // 件数を返却
        }
      }
    }
    $result['shisetsu_info']=$shisetsu_info;
/*
    $r = print_r($result['cnt'], true);
    log_message('debug', "result_cnt・・・・・・・・・・・・・・>".$r."\n");
*/
    // 検索画面での設定が必要なので返却する
    $result['srch']=$srch;
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  /***
   * 検索条件作成 ～sysTopから～
   *
   * sysTopから来た場合はGET引数を元に検索を行うため
   * 検索条件をGET引数から作り出し、検索処理の流れに乗せるために行う処理
   *
   * 引数:施設区分情報
   *
   * 戻り値:$srch
   *
   ***/
   protected function makeSrchFromSysTop($shisetsu_kbn_arr) {
     // GET引数
     $shisetsu_kbn=$this->get['shisetsu_kbn'];
     $secchi_idx=$this->get['secchi_idx'];
     $kyouyou_kbn=$this->get['kyouyou_kbn'];

     // 選択プルダウン情報
     $srch['shisetsu_kbn_dat_model']=array();  // 選択された施設区分
     $srch['shisetsu_kbn_all_cnt']=-1;  // 全施設区分件数
     $tmp=array();
     $tmp['id']=$shisetsu_kbn;
     $tmp['shisetsu_kbn']=$shisetsu_kbn;
     for ($i=0;$i<count($shisetsu_kbn_arr);$i++) {
       if ($shisetsu_kbn_arr[$i]['shisetsu_kbn']==$shisetsu_kbn) {
         $tmp['label']=$shisetsu_kbn_arr[$i]['label'];
         $tmp['sort_no']=$shisetsu_kbn_arr[$i]['sort_no'];
         break;
       }
     }
     array_push($srch['shisetsu_kbn_dat_model'], $tmp);
     $srch['substitute_road_dat_model']=array();  // 選択された代替路
     $srch['substitute_road_all_cnt']=-1;  // 全代替路件数
     $srch['emergency_road_dat_model']=array();  // 選択された緊急輸送道路
     $srch['emergency_road_all_cnt']=-1;  // 全緊急輸送道路件数
     $srch['kyouyou_kbn_dat_model']=array();  // 選択された供用区分
     $srch['kyouyou_kbn_all_cnt']=-1;  // 全供用区分件数
     // 供用区分は-1の場合全てなので条件セットなし
     if ($kyouyou_kbn!=-1){
       $tmp=array();
       $tmp['id']=$kyouyou_kbn;
       if ($kyouyou_kbn==0){
         $tmp['label']="休止";
       }else if ($kyouyou_kbn==1) {
         $tmp['label']="供用";
       // 未入力追加=検索時はIS NULLとして検索
       }else if ($kyouyou_kbn==-2) {
         $tmp['label']="未入力";
       }
       //$tmp['label']=$kyouyou_kbn==1 ? "供用" : "休止";
       array_push($srch['kyouyou_kbn_dat_model'], $tmp);
     }
     $srch['rosen_dat_model']=array();  // 選択された路線
     $srch['rosen_all_cnt']=-1;  // 全路線件数

    /***
     *    設置年度の範囲を表す
     *    1:20年以上
     *    2:10年以上20年未満
     *    3:5年以上10年未満
     *    4:5年未満
     *    5:設置年度不明
     *    6:計 = 設置年度の条件は付けずに検索
     *
     *    設置年度は
     *      $srch['secchi_nendo_from']
     *      $srch['secchi_nendo_to']
     *      $srch['include_secchi_null']
     *    を作り出す
     ***/
     $now_yyyy = (int)date('Y');
     $now_MM = (int)date('m');
     if ($now_MM<=3) {
       $now_yyyy-=1;
     }
     if ($secchi_idx==1) {
       // secchi_toのみ指定
       $srch['secchi_nendo_to']=strval($now_yyyy - 20);
     }else if($secchi_idx==2) {
       $srch['secchi_nendo_from']=strval($now_yyyy - 10);
       $srch['secchi_nendo_to']=strval(($now_yyyy - 20) + 1);
     }else if($secchi_idx==3) {
       $srch['secchi_nendo_from']=strval($now_yyyy - 5);
       $srch['secchi_nendo_to']=strval(($now_yyyy - 10) + 1);
     }else if($secchi_idx==4) {
       $srch['secchi_nendo_from']=strval(($now_yyyy - 5) + 1);
     }else if($secchi_idx==5) {
       $srch['include_secchi_null']=1;
     }else if($secchi_idx==6) {
       // 指定なし
     }

     // mapデフォルト表示
     $srch['default_tab_map']=true;
     $srch['default_tab_list']=false;

     return $srch;
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
    // sessionに追加
    $this->rgstSessionSrch($srch,1,1);
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
    $this->load->model('FamMainModel');
    // 件数を先に取得
    $cnt=$this->FamMainModel->srchShisetsuNum($condition);
    //log_message('debug', "count=$cnt");
    // 700件を超える場合は検索しない
    if ($cnt>=700) {
      $result['cnt']=$cnt;
    }else{
      $result=$this->FamMainModel->srchShisetsu($condition);
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
    $substitute_road_arr = $srch['substitute_road_dat_model'];  // 選択された代替路
    $substitute_road_all_cnt = $srch['substitute_road_all_cnt'];  // 全代替路件数
    $emergency_road_arr = $srch['emergency_road_dat_model'];  // 選択された緊急輸送道路
    $emergency_road_all_cnt = $srch['emergency_road_all_cnt'];  // 全緊急輸送道路件数
    $kyouyou_kbn_arr = $srch['kyouyou_kbn_dat_model'];  // 選択された供用区分
    $kyouyou_kbn_all_cnt = $srch['kyouyou_kbn_all_cnt'];  // 全供用区分件数
    $rosen_dat_arr = $srch['rosen_dat_model'];  // 選択された路線
    $rosen_all_cnt = $srch['rosen_all_cnt'];  // 全路線件数

    // テキスト項目等
    $ret['dogen_cd'] = $dogen_cd; // 建管コード
    $ret['syucchoujo_cd'] = $syucchoujo_cd; // 出張所コード
    if (isset($srch['shisetsu_cd'])) {
      $ret['shisetsu_cd'] = $srch['shisetsu_cd']; // 施設コード
    }
    // CESさんの方では||だったが、条件が間違えているため&&に修正
    if (isset($srch['secchi_nendo_from']) && $srch['secchi_nendo_from']) {
      $ret['secchi_from'] = $srch['secchi_nendo_from']; // 設置年度FROM
    }
    // CESさんの方では||だったが、条件が間違えているため&&に修正
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
    if (count($substitute_road_arr)!==0 && count($substitute_road_arr) !== $substitute_road_all_cnt) {
      $arr = array();
      for ($i=0;$i<count($substitute_road_arr);$i++) {
        array_push($arr, $substitute_road_arr[$i]['id']);
      }
      $ret['substitute_road']=$arr;
    }
    if (count($emergency_road_arr)!==0 && count($emergency_road_arr) !== $emergency_road_all_cnt) {
      $arr = array();
      for ($i=0;$i<count($emergency_road_arr);$i++) {
        array_push($arr, $emergency_road_arr[$i]['id']);
      }
      $ret['emergency_road']=$arr;
    }
    if (count($kyouyou_kbn_arr)!==0 && count($kyouyou_kbn_arr) !== $kyouyou_kbn_all_cnt) {
      $arr = array();
      for ($i=0;$i<count($kyouyou_kbn_arr);$i++) {
        array_push($arr, $kyouyou_kbn_arr[$i]['id']);
      }
      $ret['kyouyou_kbn']=$arr;
    }
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

}
