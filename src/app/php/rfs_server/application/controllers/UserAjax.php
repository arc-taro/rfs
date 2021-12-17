<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require("PwaBaseController.php");

class UserAjax extends PwaBaseController {

  function __construct() {
    parent::__construct();
  }

  public function login_post() {
    $data = $this->post;
    // log_message('debug', print_r($data, true));
    $this->load->model('UserModel');
    $result = $this->UserModel->userLogin($data);
    // log_message('debug', print_r($result, true));
    
    if (!empty($result)) {
      session_start();
      $_SESSION["ath"] = $result[0];
      // $_SESSION["mngarea"]["syucchoujo_cd"] = $result[0]["syucchoujo_cd"];
      // $_SESSION["mngarea"]["dogen_cd"] = $result[0]["dogen_cd"];
    }

    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  public function patrolin_post() {
    $data = $this->post;
    // log_message('debug', print_r($data, true));
    $this->load->model('UserModel');
    $result = $this->UserModel->userPatrolin($data);
    // log_message('debug', print_r($result, true));
    
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  public function rosen_post() {
    $data = $this->post;
    // log_message('debug', print_r($data, true));
    $this->load->model('RfsMRosenModel');
    $result = $this->RfsMRosenModel->getRfsMRosen($data);
    // log_message('debug', print_r($result, true));
    
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }
}
