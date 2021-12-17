<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require("PwaBaseController.php");

class RfsTChkBuzaiAjax extends PwaBaseController {

  function __construct() {
    parent::__construct();
  }

  public function get_rfs_t_chk_buzai() {
    $data = $this->post;
    // log_message('debug', print_r($data, true));
    $this->load->model('RfsTChkBuzaiModel');
    $result = $this->RfsTChkBuzaiModel->get_rfs_t_chk_buzai($data);
    // log_message('debug', print_r($result, true));
    
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }
  
  public function set_rfs_t_chk_buzai() {
    //$data = $this->post;
    //log_message('debug', print_r($data, true));
    //$result = $this->RfsTChkBuzaiModel->set_rfs_t_chk_buzai($data);
    // log_message('debug', print_r($result, true));
    $this->load->model('RfsTChkBuzaiModel');
	$result = $this->RfsTChkBuzaiModel->setRfsTChkBuzai($this->post);
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }
}
