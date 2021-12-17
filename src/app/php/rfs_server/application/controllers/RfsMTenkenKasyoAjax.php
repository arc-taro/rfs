<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require("PwaBaseController.php");

class RfsMTenkenKasyoAjax extends PwaBaseController {

  function __construct() {
    parent::__construct();
  }

  public function get_rfs_m_tenken_kasyo() {
    $data = $this->post;
    // log_message('debug', print_r($data, true));
    $this->load->model('RfsMTenkenKasyoModel');
    $result = $this->RfsMTenkenKasyoModel->get_rfs_m_tenken_kasyo($data);
    // log_message('debug', print_r($result, true));
    
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }
}
