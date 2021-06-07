<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**

    コントローラー名：CheckListSchAjax
    概要：施設の点検一覧検索に関するajax呼び出し口
    ある施設の点検の履歴（一覧）を取得したい場合に呼ばれる




**/
class CheckListSchAjax extends BaseController {

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
        $this->load->model('SchCheck');

    }

    // 基本情報の取得（新規追加用）
    public function get_baseinfo_new(){
        log_message('info', "get_baseinfo_new");

        $result = $this->SchCheck->get_baseinfo_new($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);

    }

    // 基本情報の取得（点検管理番号の指定）
    public function get_baseinfo_by_chkmngno(){
        log_message('info', "get_baseinfo_by_chkmngno");

        $result = $this->SchCheck->get_baseinfo_by_chkmngno($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);

    }

    // 基本情報の取得（過去データ）
    public function get_baseinfo_past(){
        log_message('info', "get_baseinfo_past");

        $result = $this->SchCheck->get_baseinfo_past($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);

    }

    // 施設の健全性の取得
    public function get_shisetsu_judge(){
        log_message('info', "get_shisetsu_judge");

        $result = $this->SchCheck->get_shisetsu_judge($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);

    }

    // 部材以下情報の取得
    public function get_chkdata(){
        log_message('info', "get_chkdata");

        $result = $this->SchCheck->get_chkdata($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }

    // 部材以下情報の取得
    public function get_chkdata_exist_only(){
        log_message('info', "get_chkdata_exist_only");

        $result = $this->SchCheck->get_chkdata_exist_only($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }

    // 部材以下情報の取得
    public function get_chkdata_new(){
        log_message('info', "get_chkdata_new");

        $result = $this->SchCheck->get_chkdata_new($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }

    // パトロール員と調査員一覧の取得
    public function get_patrolin_investigator() {
        log_message('info', "get_patrolin_investigator");

        $result = $this->SchCheck->get_patrolin_investigator($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);

    }

/*
    // パトロール員一覧の取得
    public function get_patrolins() {
        log_message('info', "get_patrolins");

        $result = $this->SchCheck->get_patrolins($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);

    }
*/

    // 点検管理番号の取得
    public function get_chkmngnos() {
        log_message('info', "get_chkmngnos");

        $result = $this->SchCheck->get_chkmngnos($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);

    }

    // 防雪柵支柱インデックス数の取得
    public function get_struct_idx_num() {
        log_message('info', "get_struct_idx_num");

        $result = $this->SchCheck->get_struct_idx_num($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);

    }

    // 防雪柵管理情報の取得
    public function get_bousetsusaku_mng_info() {
        log_message('info', "get_bousetsusaku_mng_info");

        $result = $this->SchCheck->get_bousetsusaku_mng_info($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);

    }

    // 防雪柵(親)情報の取得
    public function get_chkmngno_bssk_parent() {
        log_message('info', "get_chkmngno_bssk_parent");

        $result = $this->SchCheck->get_chkmngno_bssk_parent($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);


    }
    // ストック対象データの取得
    public function get_stock_data(){
        log_message('info', "get_stock_data");

        $result = $this->SchCheck->get_stock_data($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }

    // 形式の取得
    public function get_keishiki() {
      log_message('info', "get_keishiki");
      $result = $this->SchCheck->get_keishiki($this->get);
      $this->json = json_encode($result);
      $this->output->set_content_type('application/json')->set_output($this->json);
    }

    // 基本情報とその先の部材以下の情報も取得しちゃう
    public function get_kihon_chkmng_buzai(){
        log_message('info', "get_kihon_chkmng_buzai");
        $result["kihon_chkmng"] = $this->SchCheck->get_baseinfo_by_chkmngno($this->get);
        
        // 引数が変わるので作成
        // chk_mng_no,rireki_no,shisetsu_kbn
        $param_chkdata["chk_mng_no"]=$result["kihon_chkmng"][0]["chk_mng_no"];
        $param_chkdata["rireki_no"]=$result["kihon_chkmng"][0]["rireki_no"];
        $param_chkdata["shisetsu_kbn"]=$result["kihon_chkmng"][0]["shisetsu_kbn"];

        if ($result["kihon_chkmng"][0]["chk_times"]==0){
            $result["buzai_row"] = $this->SchCheck->get_chkdata_exist_only($param_chkdata);
        }else{
            $result["buzai_row"] = $this->SchCheck->get_chkdata($param_chkdata);
        }

        // 引数が変わるので作成
        // syozoku_cd,syucchoujo_cd,busyo_cd
        $param_patrolin_investigator["syozoku_cd"]=$this->session['ath']['syozoku_cd'];
        $param_patrolin_investigator["syucchoujo_cd"]=$this->session['mngarea']['syucchoujo_cd'];
        $param_patrolin_investigator["busyo_cd"]=$this->session['ath']['busyo_cd'];
        // パトロール員、調査員の取得
        $result["patrolin_investigator"] = $this->SchCheck->get_patrolin_investigator($param_patrolin_investigator);

        // 形式(param_chkdataに施設区分が入っているのでそれを渡す)
        $result["keishiki"] = $this->SchCheck->get_keishiki($param_chkdata);

        // 引数が変わるので作成
        $param_shisetsu_judge["sno"]=$this->get["sno"];
        $param_shisetsu_judge["chk_mng_no"]=$param_chkdata["chk_mng_no"];
        // 施設健全性
        $result["shisetsu_judge"] = $this->SchCheck->get_shisetsu_judge($param_shisetsu_judge);

        // 点検管理番号の取得(最初の引数にsnoと支柱インデックスがあるのでそれを渡す)
        $result["chkmngnos"] = $this->SchCheck->get_chkmngnos($this->get);

        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }

}
