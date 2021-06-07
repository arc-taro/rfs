<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**

    コントローラー名：SchBsskChildrenAjax
    概要：防雪柵（支柱インデックス）情報の取得

**/
class SchBsskChildrenAjax extends BaseController {

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
        $this->load->model('SchBsskChildren');

    }

    // 防雪柵（支柱インデックス）情報の取得
    public function get_srch_bssk_children(){
        log_message('info', "get_srch_bssk_children");
        $result = $this->SchBsskChildren->get_srch_bssk_children($this->post);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }
}
