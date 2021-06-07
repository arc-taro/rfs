<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**

    コントローラー名：TenkenRirekiAjax
    概要：施設の点検一覧検索に関するajax呼び出し口
    ある施設の点検の履歴（一覧）を取得したい場合に呼ばれる




**/
class TenkenRirekiAjax extends BaseController {

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
        $this->load->model('Rireki');

    }

    // 過去の点検票の取得
    public function get_rireki_data(){
        log_message('info', "get_rireki_data");

        $result = $this->Rireki->get_rireki_data($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);

    }

}
