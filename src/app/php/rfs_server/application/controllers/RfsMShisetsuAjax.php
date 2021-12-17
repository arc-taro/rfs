<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require("PwaBaseController.php");

class RfsMShisetsuAjax extends PwaBaseController {

  function __construct() {
    parent::__construct();
  }

  public function get_rfs_m_shisetsu() {
    $data = $this->post;
    // log_message('debug', print_r($data, true));
    $this->load->model('RfsMShisetsuModel');
    $result = $this->RfsMShisetsuModel->get_rfs_m_shisetsu($data);
    // log_message('debug', print_r($result, true));
    
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }
  
  public function set_rfs_m_shisetsu() {
    $data = $this->post;
    // log_message('debug', print_r($data, true));
    $this->load->model('RfsMShisetsuModel');
    $result = $this->RfsMShisetsuModel->set_rfs_m_shisetsu($data);
    // log_message('debug', print_r($result, true));
    
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }
}
