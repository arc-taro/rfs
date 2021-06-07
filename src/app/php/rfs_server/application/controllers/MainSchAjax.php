<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**

    コントローラー名：MainSchAjax
    概要：各施設の検索に関するajax呼び出し口
        Map表示、List表示等、複数の施設を
        取得したい場合に呼ばれる

**/
class MainSchAjax extends BaseController {

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

        if ($this->post['srch']){
            $this->load->model('SchShisetsu');
        }else{
            $this->load->model('SchMst');
        }
    }

    public function get_srchentry(){
        log_message('info', "get_srchentry");
        $result = $this->SchMst->get_srch_entry($this->get);
        $this->load->model('SchCommon');
        // 設置年度の取得
        $yyyy=date("Y");
        $wareki_list = $this->SchCommon->getWarekiList(1975,$yyyy,"desc");
        $result['wareki_list']=$wareki_list;
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }

    // 施設情報の取得(元々get_srch_shisetsu_numとget_srch_shisetsuを呼んでいたが一つにする)
    public function getSearchFuzokubutsu(){
        log_message('info', "getSearchFuzokubutsu");
        // 検索を1回にして、件数とデータを一緒に渡す。
        // 件数が引数の最大件数を超える場合はデータをセットしない
        $tmp = $this->SchShisetsu->get_srch_shisetsu($this->post);
        $arr=json_decode($tmp[0]['sch_result_row'],true)['sch_result'];
        $result['cnt']=0;
        $result['data']=null;
        if ($arr) { // 該当あり
            $result['cnt']=count($arr);
            if ($result['cnt']<=$this->post['max_cnt']) {
                $result['data']=$tmp;
            }
        }
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }
    
    // 施設情報の取得
    public function get_srch_shisetsu(){
        log_message('info', "get_srch_shisetsu");
        $result = $this->SchShisetsu->get_srch_shisetsu($this->post);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }

    // 施設情報件数の取得
    public function get_srch_shisetsu_num(){
        log_message('info', "get_srch_shisetsu_num");
        $result = $this->SchShisetsu->get_srch_shisetsu_num($this->post);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }

    // 支柱インデックスの取得
    public function get_srch_struct(){
        log_message('info', "get_srch_struct");
        $result = $this->SchShisetsu->get_srch_struct($this->post);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }
}
