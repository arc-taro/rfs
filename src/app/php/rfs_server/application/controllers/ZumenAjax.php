<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**
    コントローラー名：ZumenAjax
    概要：図面の処理
**/
class ZumenAjax extends BaseController {

  /**
   * コンストラクタ
   */
  public function __construct() {
    parent::__construct();
    // メソッド共通の処理をする
    $this->load->model('ZumenModel');

  }

  public function getZumen(){
    $param = $this->post['zumen'];
    $data = $this->ZumenModel->getZumen($param);
    $this->json = json_encode($data);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  public function saveZumen(){
    $param = $this->post['zumen'];
    $data = $this->ZumenModel->saveZumen($param);
    $this->json = json_encode($data);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }
}

