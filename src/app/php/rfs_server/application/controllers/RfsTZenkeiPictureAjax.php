<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require("PwaBaseController.php");

class RfsTZenkeiPictureAjax extends PwaBaseController {

  function __construct() {
    parent::__construct();
  }

  public function get_rfs_t_zenkei_picture() {
    $data = $this->post;
    // log_message('debug', print_r($data, true));
    $this->load->model('RfsTZenkeiPictureModel');
    $result = $this->RfsTZenkeiPictureModel->get_rfs_t_zenkei_picture($data);
    // log_message('debug', 'get_rfs_t_zenkei_picture');
    // log_message('debug', print_r($result, true));
    
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }
}
