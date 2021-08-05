<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**
    コントローラー名：HouteiAttachController
    概要：添付ファイル関連の処理
**/
class HouteiAttachController extends BaseController {

  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
    $this->load->model('HouteiAttachModel');
  }

  /**
   * 添付ファイルを保存する
   */
  function saveAttach(){
    $sno = $this->post['sno'];
    $attach_list = $this->post['attach_list'];

    $data = $this->HouteiAttachModel->saveAttach($sno, $attach_list);
    $this->json = json_encode($data);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }
}