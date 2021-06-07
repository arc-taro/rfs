<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**

    コントローラー名：ReportSchAjax
    概要：点検表の検索に関するajax呼び出し口
    ある施設のある点検の点検表を取得したい場合に呼ばれる






**/
class ReportSchAjax extends BaseController {

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
    }

    public function index()
    {
        $this->load->view('welcome_message');
    }
}
