<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**

    コントローラー名：ShisetsuDetailJudgeAjax
    概要：施設の健全性を全て検索し、返却する。
        Map表示、List表示で施設の健全性を表示するときに使用

**/
class SchDetailJudgeAjax extends BaseController {

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
     * So any other public methods not prefixed with an underscore will * map to /index.php/welcome/
<method_name>
     * @see https://codeigniter.com/user_guide/general/urls.html
     */

    public function __construct() {
        parent::__construct();
        // メソッド共通の処理をする

        $this->load->model('SchDetailJudge');
    }

    public function get_srch_detail_judge(){
        log_message('info', "get_srch_detail_judge");
        $result = $this->SchDetailJudge->get_srch_detail_judge($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }



}
