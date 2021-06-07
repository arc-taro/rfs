<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");




/******************************************
********** ShisetsuEdit.phpへ移行 **********
**********  二次リリース後削除予定  **********
******************************************/









/**
    コントローラー名：EditShisetuAjax
    概要：基本情報の編集

**/
class EditShisetsuAjax extends BaseController {

    /**
     * コンストラクタ
     */
    public function __construct() {
        parent::__construct();
        $this->load->model('EditShisetsu');
    }

    /**
    * 基本情報キー保存（写真UPLOADにはどうしても必要なため）
    */
    public function set_shisetsu_id(){
        log_message('info', "set_shisetsu_id");
        $result=$this->EditShisetsu->set_shisetsu_id($this->post);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }

    /**
    * 基本情報を保存する。
    */
    public function set_shisetsu(){
        log_message('info', "set_shisetsu");
        $result=$this->EditShisetsu->set_shisetsu($this->post);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }
}
