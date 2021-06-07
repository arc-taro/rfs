<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**

    コントローラー名：InquirySession
    概要：session問い合わせ
    返却：session
**/
class InquirySession extends BaseController {

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     *         http://example.com/index.php/welcome
     *    - or -
     *         http://example.com/index.php/welcome/index
     *    - or -
     * Since this controller is set as the default controller in
     * config/routes.php, it's displayed at http://example.com/
     *
     * So any other public methods not prefixed with an underscore will
     * map to /index.php/welcome/<method_name>
     * @see https://codeigniter.com/user_guide/general/urls.html
     */

    public function __construct() {
        parent::__construct();
        // メソッド共通の処理をする
    }

    public function get_session(){
        log_message('info', "get_session");
        $result = $this->session;
/*
        $r = print_r($result, true);
        log_message('debug', "session=$r");
*/
      $this->json = json_encode($result);
      $this->output->set_content_type('application/json')->set_output($this->json);
    }

  /**
   * Manageareaの更新
   *
   *  Manageareaが変更した際に呼ばれ、
   *  建管、出張所のSessionの書き換えを行う
   */
  public function updMngarea(){
    log_message('info', "updMngarea");
    $dogen_cd=$this->get['dogen_cd'];
    $syucchoujo_cd=$this->get['syucchoujo_cd'];

    // SESSIONのmngareaの書き換え
    $this->updMngareaBase($dogen_cd, $syucchoujo_cd);
    $result = $this->session;

    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  /**
   * Map、Listの選択状態更新
   *
   *  Map、Listの選択状態をSessionに保持する
   */
  public function updSrchDisp(){
    log_message('info', "updSrchDisp");

    $map_list=$this->get['map_list'];

    // サーチの書き換え
    $this->rgstSrchDisp($map_list);
    $result = $this->session;

    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  /**
   * RafTopからの条件セット
   *
   *  RafTopからの検索条件を解析しsessionにセットする。
   */
  public function updSrchFuzokubutsuFromTop(){
    log_message('info', "updSrchFuzokubutsuFromTop");

    // 引数から検索条件を整形してsessionへセット
    $obj=json_decode($this->get['raf_params']);
    // 各々
    $dogen_cd=$obj->dogen_cd;
    $syucchoujo_cd=$obj->syucchoujo_cd;
    $kbn=$obj->kbn;
    $nendo_from=$obj->nendo_from;
    $nendo_to=$obj->nendo_to;
    $shisetsu_kbn=$obj->shisetsu_kbn;
    $val=$obj->val;

    // 配列を作成
    $setSess;
    $srch = array();
    $shisetsu_kbn_arr = array();
    $shisetsu_kbn_arr_tmp = array();

    $setSess['raf_top']=true;
    $setSess['dogen_cd']=$dogen_cd;
    $setSess['syucchoujo_cd']=$syucchoujo_cd;
    $setSess['default_tab']['map']=true;
    $setSess['default_tab']['list']=false;
    $setSess['srch_done']['dogen_cd']=$dogen_cd;
    $setSess['srch_done']['syucchoujo_cd']=$syucchoujo_cd;
    $setSess['srch_done']['srch'] = array();
    $setSess['srch_done']['srch']['target_nendo_from']=isset($nendo_from) ? $nendo_from : "";
    $setSess['srch_done']['srch']['target_nendo_to']=isset($nendo_to) ? $nendo_to : "";
    $setSess['srch_done']['srch']['include_secchi_null']=1; // 不明は常に含める
    $setSess['srch_done']['srch']['shisetsu_kbn_dat_model'] = array();
    $setSess['srch_done']['srch']['phase_dat_model'] = array();
    $setSess['srch_done']['srch']['chk_judge_dat_model'] = array();
    $setSess['srch_done']['srch']['measures_judge_dat_model'] = array();
    $setSess['srch_done']['srch']['rosen_dat_model'] = array();
    $setSess['srch_done']['srch']['patrolin_gyousya_dat_model'] = array();
    $setSess['srch_done']['srch']['patrolin_dat_model'] = array();
    $setSess['srch_done']['srch']['investigator_gyousya_dat_model'] = array();
    $setSess['srch_done']['srch']['investigator_dat_model'] = array();
    $setSess['srch_done']['srch']['struct_idx_dat_model'] = array();

    // 各マスタ情報取得
    $this->load->model('SchCommon');
    $shisetsu_kbn_mst=$this->SchCommon->getShisetsuKbnMst();
    $phase_mst=$this->SchCommon->getPhaseMstSum();
    $judge_mst=$this->SchCommon->getJudgeMst();

    /*
    $r = print_r($shisetsu_kbn_mst, true);
    log_message('debug', "shisetsu_kbn_mst=$r");
*/

    $tmp = array();
    $tmp['id'] = (int)$shisetsu_kbn;
    $tmp['label'] = $this->getNmFromArr($shisetsu_kbn_mst, $shisetsu_kbn, 'cd', 'nm');
    $tmp['shisetsu_kbn'] = $shisetsu_kbn;
    array_push($setSess['srch_done']['srch']['shisetsu_kbn_dat_model'], $tmp);

    /********************************************************/
    /*** 引数によって、フェーズと点検時健全性と措置後健全性をセット ***/
    /********************************************************/

    /****************************************/
    /*** kbn 0:施設選択時、1:フェーズ、2:措置 ***/
    /****************************************/
    /*** 施設選択時 ***/
    if ($kbn == 0) {
      // 施設全部なので、条件なし

      /*** フェーズ ***/
      /****************************************************************************/
      /*** val -1:点検未実施、1:点検中、2:スクリーニング、3:詳細調査、4:点検完了、999:全て ***/
      /****************************************************************************/
    } else if ($kbn == 1) {

      // -1:未実施を追加したため、999以外は同じ制御
      if ($val != 999) {
        // 点検、スクリーニング、詳細調査、点検完了
        $tmp = array();
        $tmp['id'] = (int)$this->getNmFromArr($phase_mst, $val, 'cd', 'id');
        $tmp['label'] = $this->getNmFromArr($phase_mst, $val, 'cd', 'nm');
        $tmp['phase'] = $val;
        array_push($setSess['srch_done']['srch']['phase_dat_model'], $tmp);
      }
/*
      if ($val == -1) {
        // 未点検
        $tmp = array();
        $tmp['id'] = 0;
        $tmp['label'] = '未実施';
        $tmp['shisetsu_judge'] = 0;
        array_push($setSess['srch_done']['srch']['chk_judge_dat_model'], $tmp);
      } else if ($val == 1 || $val == 2 || $val == 3 || $val == 5) {
        // 点検、スクリーニング、詳細調査、点検完了
        $tmp = array();
        $tmp['id'] = $this->getNmFromArr($phase_mst, $val, 'cd', 'id');
        $tmp['label'] = $this->getNmFromArr($phase_mst, $val, 'cd', 'nm');
        $tmp['phase'] = $val;
        array_push($setSess['srch_done']['srch']['phase_dat_model'], $tmp);
      } else if ($val == 999) {
        // 全て
      }
*/
      // 健全性についてはセットなし

      /*** 措置 ***/
      /*****************************************/
      /*** 1:措置不要、2:措置、3:措置完了、4:全て ***/
      /*****************************************/
    } else if ($kbn == 2) {
      if ($val == 1) {
        // 措置不要
        // 点検以上、点検時健全性Ⅱ以下
        $tmp = array();
        $tmp['id'] = 1;
        $tmp['label'] = $this->getNmFromArr($judge_mst, 1, 'cd', 'nm');
        $tmp['shisetsu_judge'] = 1;
        array_push($setSess['srch_done']['srch']['chk_judge_dat_model'], $tmp);
        $tmp = array();
        $tmp['id'] = 2;
        $tmp['label'] = $this->getNmFromArr($judge_mst, 2, 'cd', 'nm');
        $tmp['shisetsu_judge'] = 2;
        array_push($setSess['srch_done']['srch']['chk_judge_dat_model'], $tmp);
      } else if ($val == 2) {
        // 措置
        // 点検以上、点検時健全性Ⅲ以上、措置後健全性Ⅲ以上
        $tmp = array();
        $tmp['id'] = 3;
        $tmp['label'] = $this->getNmFromArr($judge_mst, 3, 'cd', 'nm');
        $tmp['shisetsu_judge'] = 3;
        array_push($setSess['srch_done']['srch']['chk_judge_dat_model'], $tmp);
        $tmp = array();
        $tmp['id'] = 4;
        $tmp['label'] = $this->getNmFromArr($judge_mst, 4, 'cd', 'nm');
        $tmp['shisetsu_judge'] = 4;
        array_push($setSess['srch_done']['srch']['chk_judge_dat_model'], $tmp);
        $tmp = array();
        $tmp['id'] = 3;
        $tmp['label'] = $this->getNmFromArr($judge_mst, 3, 'cd', 'nm');
        $tmp['shisetsu_judge'] = 3;
        array_push($setSess['srch_done']['srch']['measures_judge_dat_model'], $tmp);
        $tmp = array();
        $tmp['id'] = 4;
        $tmp['label'] = $this->getNmFromArr($judge_mst, 4, 'cd', 'nm');
        $tmp['shisetsu_judge'] = 4;
        array_push($setSess['srch_done']['srch']['measures_judge_dat_model'], $tmp);
      } else if ($val == 3) {
        // 措置完了
        // 点検以上、点検時健全性Ⅲ以上、措置後健全性Ⅱ以下
        $tmp = array();
        $tmp['id'] = 3;
        $tmp['label'] = $this->getNmFromArr($judge_mst, 3, 'cd', 'nm');
        $tmp['shisetsu_judge'] = 3;
        array_push($setSess['srch_done']['srch']['chk_judge_dat_model'], $tmp);
        $tmp = array();
        $tmp['id'] = 4;
        $tmp['label'] = $this->getNmFromArr($judge_mst, 4, 'cd', 'nm');
        $tmp['shisetsu_judge'] = 4;
        array_push($setSess['srch_done']['srch']['chk_judge_dat_model'], $tmp);
        $tmp = array();
        $tmp['id'] = 1;
        $tmp['label'] = $this->getNmFromArr($judge_mst, 1, 'cd', 'nm');
        $tmp['shisetsu_judge'] = 1;
        array_push($setSess['srch_done']['srch']['measures_judge_dat_model'], $tmp);
        $tmp = array();
        $tmp['id'] = 2;
        $tmp['label'] = $this->getNmFromArr($judge_mst, 2, 'cd', 'nm');
        $tmp['shisetsu_judge'] = 2;
        array_push($setSess['srch_done']['srch']['measures_judge_dat_model'], $tmp);
      } else if ($val == 4) {
        // 全部(措置対象のもの)
      }
    }

    // SESSIONのsrch_fuzokubutsuの書き換え
    $this->rgstSessionSrch($setSess, 2,1);
    $result = $this->session;

    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  /**
   * 点検票からの条件セット
   *
   *  点検票からの検索条件をsessionにセットする。
   */
  public function updSrchFuzokubutsu(){
    log_message('info', "updSrchFuzokubutsu");
    $kbn=$this->post['kbn'];
    $upd=$this->post['upd'];
    // 引数から検索条件を整形してsessionへセット
    //$arr=json_decode($this->post['tenken_params']);
    $arr=$this->post['tenken_params'];
    // オブジェクトの部分を配列化
    $arr=$this->obj2arr($arr);
    if ($arr['srch_done']) {
      foreach ($arr['srch_done']['srch'] as $key => $value) {
        if (is_array($arr['srch_done']['srch'][$key])) {
          foreach ($arr['srch_done']['srch'][$key] as &$item) {
            $item=$this->obj2arr($item);
          }
        }
      }
    }

    $r = print_r($arr, true);
    log_message('debug', "arr=$r");



    // SESSIONのsrch_fuzokubutsuの書き換え
    $this->rgstSessionSrch($arr, $kbn,$upd);
    $result = $this->session;
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  // 配列からキーのValueを取得する
  // 引数:$arr 検索対象配列
  //      $srch 検索値
  //      $keynm 検索する項目
  //      $valnm 検索結果として返す値
  protected function getNmFromArr($arr, $srch, $keynm, $valnm) {
    $ret="";
    foreach ( $arr as $item ) {
      if ( $item[$keynm] == $srch ) {
        $ret=$item[$valnm];
        break;
      }
    }
    return $ret;
  }

  // オブジェクトの配列化
  function obj2arr($obj) {
    if ( !is_object($obj) ) return $obj;
    $arr = (array) $obj;
    foreach ( $arr as &$a ) {
      $a = $this->obj2arr($a);
    }
    return $arr;
  }

}
