<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require("PwaBaseController.php");

class RfsMBousetsusakuShichuAjax extends PwaBaseController {

  function __construct() {
    parent::__construct();
  }

  public function get_rfs_m_bousetsusaku_shichu() {
    $data = $this->post;
    // log_message('debug', print_r($data, true));
    $this->load->model('RfsMBousetsusakuShichuModel');
    $result = $this->RfsMBousetsusakuShichuModel->get_rfs_m_bousetsusaku_shichu($data);
    // log_message('debug', print_r($result, true));
    
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }
}
