<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require("PwaBaseController.php");

class RfsMShisetsuKeishikiAjax extends PwaBaseController {

  function __construct() {
    parent::__construct();
  }

  public function get_rfs_m_shisetsu_keishiki() {
    $data = $this->post;
    log_message('debug', '-------------------get_rfs_m_buzai post ------------------------>');
    log_message('debug', print_r($data, true));
    $this->load->model('RfsMShisetsuKeishikiModel');
    $result = $this->RfsMShisetsuKeishikiModel->get_rfs_m_shisetsu_keishiki($data);
    // log_message('debug', print_r($result, true));
    
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }
}
