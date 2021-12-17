<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PwaBaseController extends CI_Controller {

  protected $get;         //GETパラメータ
  protected $post;        //POSTパラメータ
  protected $post_json;   //POSTパラメータ
  protected $json;        //戻り値

  /* テスト環境 */
  var $session=array();

  /**
   * コンストラクタ
   *
   */
  public function __construct() {
    // log_message('debug', "__construct-------------------");
    parent::__construct();

    // GET取得
    $this->get_getParams();
    // POST(JSON)取得
    $this->get_postParams();

  }

  // $_GETの配列を返す
  protected function get_getParams() {
    $this->get = $this->input->get();
    $r = print_r($this->get, true);
    //        log_message('debug', "get=$r");
    return;
  }

  // $_POSTの配列を返す
  /*
    protected function get_postParams() {
        $this->post = $this->input->post();
        $r = print_r($this->post, true);
        log_message('info', "post=$r");
        return;
    }
*/

  // $_POSTの配列を返す
  protected function get_postParams() {
    $this->post = json_decode(file_get_contents('php://input'),true);
    $r = print_r($this->post, true);
    //        log_message('info', "post=$r");
    return;
  }

  /************************/
  /*** $_SESSION書き換え ***/
  /************************/
  // mngareaの更新
  protected function updMngareaBase($dogen_cd, $syucchoujo_cd) {
    log_message('debug', "updMngareaBase");
    // mngarea書き換え
    $_SESSION['mngarea']['dogen_cd']=$dogen_cd;
    $_SESSION['mngarea']['syucchoujo_cd']=$syucchoujo_cd;
    $this->session=$_SESSION;
    return;
  }

  /***
   * srchの書き換え
   *
   * 引数:$srch 保存する検索条件(object)
   *      $kbn 1:施設管理、2:附属物点検、3:附属物点検対象登録、4:附属物トップ画面
   *      $upd 1:登録、2:削除
   * [srch_○○]を削除し、引数の$srchをセットする
   *      ※別場面での検索項目があれば、kbnを追加してください
   */
  protected function rgstSessionSrch($srch, $kbn, $upd) {
    log_message('debug', "rgstSessionSrch");
    // srch削除
    if ($kbn===1) {
      if (isset($_SESSION['srch_shisetsu'])){
        unset($_SESSION['srch_shisetsu']);
      }
    }else if ($kbn===2) {
      if (isset($_SESSION['srch_fuzokubutsu'])){
        unset($_SESSION['srch_fuzokubutsu']);
      }
    }else if ($kbn===3) {
      if (isset($_SESSION['srch_target'])){
        unset($_SESSION['srch_target']);
      }
    }else if ($kbn===4) {
      if (isset($_SESSION['srch_raftop'])){
        unset($_SESSION['srch_raftop']);
      }
    }
    // 削除の場合はここで終了
    if ($upd===2) {
      $this->session=$_SESSION;
      /*
      $r = print_r($this->session, true);
      log_message('debug', "this->session=$r");
*/
      return;
    }
    // 検索項目のセット
    // 施設管理の検索条件
    if ($kbn===1) {
      $_SESSION['srch_shisetsu']=$srch;
    }else if ($kbn===2) {
      $_SESSION['srch_fuzokubutsu']=$srch;
    }else if ($kbn===3) {
      $_SESSION['srch_target']=$srch;
    }else if ($kbn===4) {
      $_SESSION['srch_raftop']=$srch;
    }
    /*
    $r = print_r($this->session, true);
    log_message('debug', "this->session=$r");
*/
    $this->session=$_SESSION;
    return;
  }

  /***
   * srchのMap、List選択を書き換え
   *
   * 引数：map_list マップ表示時'map'、
   *               リスト表示時'list'
   *
   * mapの場合srch.default_tab_map=true
   *         srch.default_tab_list=false
   * listの場合srch.default_tab_map=false
   *         srch.default_tab_list=true
   */
  protected function rgstSrchDisp($map_list) {
    log_message('debug', "rgstSrchDisp");
    // マップ
    if ($map_list==='map') {
      $_SESSION['srch_shisetsu']['default_tab_map']=true;
      $_SESSION['srch_shisetsu']['default_tab_list']=false;
      // リスト
    }else{
      $_SESSION['srch_shisetsu']['default_tab_map']=false;
      $_SESSION['srch_shisetsu']['default_tab_list']=true;
    }
    $this->session=$_SESSION;

    return;
  }

}
