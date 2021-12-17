<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require("PwaBaseController.php");

class RfsTChkSonsyouAjax extends PwaBaseController {

  function __construct() {
    parent::__construct();
  }

  public function get_rfs_t_chk_sonsyou() {
    $data = $this->post;
    // log_message('debug', print_r($data, true));
    $this->load->model('RfsTChkSonsyouModel');
    $result = $this->RfsTChkSonsyouModel->get_rfs_t_chk_sonsyou($data);
    // log_message('debug', print_r($result, true));
    
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  public function set_rfs_t_chk_sonsyou() {
	log_message("debug",__METHOD__);
/*
    $_data = $this->post;
    $_rfsMShisetsuData = $_data["rfsMShisetsuData"];
    $_rfsTChkHuzokubutsuData = $_data["rfsTChkHuzokubutsuData"];
    $_rfsChkSonsyouData = $_data["rfsChkSonsyouData"];
    foreach ($_rfsMShisetsuData as $rfsMShisetsuData) {
      foreach ($_rfsTChkHuzokubutsuData as $rfsTChkHuzokubutsuData) {
        if ($rfsMShisetsuData["chk_mng_no"] == $rfsTChkHuzokubutsuData["chk_mng_no"]) {
          foreach ($_rfsChkSonsyouData as $rfsChkSonsyouData) {
            if ($_rfsChkSonsyouData["chk_mng_no"] == $rfsChkSonsyouData["sonsyouData"]["chk_mng_no"]) {
              $data["rfsMShisetsuData"] = $rfsMShisetsuData;
              $data["rfsTChkHuzokubutsuData"] = $rfsTChkHuzokubutsuData;
              $data["rfsChkSonsyouData"] = $rfsChkSonsyouData["sonsyouData"];

              $this->load->model('RfsTChkSonsyouModel');
              $result = $this->RfsTChkSonsyouModel->set_rfs_t_chk_sonsyou($data);
            }
          }
        }
      }
    }
*/

	log_message("debug","-----------------set_rfs_t_chk_sonsyou post------------------");
	log_message("debug",print_r($this->post,true));
	$this->load->model('RfsTChkSonsyouModel');
	$result = $this->RfsTChkSonsyouModel->setRfsTChkSonsyou($this->post);

	$this->load->model('EditBSParentModel');
	$result = $this->EditBSParentModel->editBSParent($this->post);

    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }
}
