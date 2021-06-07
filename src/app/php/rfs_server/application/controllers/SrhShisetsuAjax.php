<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**
    コントローラー名：SrhShisetsuAjax
    概要：基本情報登録の検索で呼ばれるコントローラー

    POST
        kbn:0 マスタ検索
        kbn:1 施設検索

**/
class SrhShisetsuAjax extends BaseController {

    /**
     * コンストラクタ
     */
    public function __construct() {
        parent::__construct();

        if ($this->get['mode']==='mst'){
            // マスタ取得model
            $this->load->model('SchMst');
        }else{
            // 施設取得model
            $this->load->model('SchShisetsu');
        }

    }

    /**
    * 基本情報登録のためのマスタデータを取得
    */
    public function getMst_forBaseInfoEdit(){
        $result=$this->SchMst->getMst_forBaseInfoEdit($this->get);
/*
        $r = print_r($result, true);
        log_message('debug', "result=$r");
*/
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }

    /**
    * 基本情報を取得
    */
    public function getShisetsu_forBaseInfoEdit(){
        $result=$this->SchShisetsu->getShisetsu_forBaseInfoEdit($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }

    /**
    * 最大shisetsu_ver取得
    */
    public function get_shisetsu_max_ver(){
        $result=$this->SchShisetsu->get_shisetsu_max_ver($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }

    // 住所取得
    public function get_addr() {
        $result=$this->SchShisetsu->get_addr($this->get);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }

}
