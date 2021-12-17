<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require("PwaBaseController.php");

class RfsTChkTenkenKasyoAjax extends PwaBaseController {

  function __construct() {
    parent::__construct();
  }

  public function get_rfs_t_chk_tenken_kasyo() {
    $data = $this->post;
    // log_message('debug', print_r($data, true));
    $this->load->model('RfsTChkTenkenKasyoModel');
    $result = $this->RfsTChkTenkenKasyoModel->get_rfs_t_chk_tenken_kasyo($data);
    // log_message('debug', print_r($result, true));
    
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  public function set_rfs_t_chk_tenken_kasyo() {
	log_message('debug', __METHOD__);
//	log_message('debug', print_r($this->post,true));
/*    $_data = $this->post;
    $_rfsMShisetsuData = $_data["rfsMShisetsuData"];
    $_rfsTChkHuzokubutsuData = $_data["rfsTChkHuzokubutsuData"];
    $_rfsChkTenkenKasyoData = $_data["rfsChkTenkenKasyoData"];
    foreach ($_rfsMShisetsuData as $rfsMShisetsuData) {
      foreach ($_rfsTChkHuzokubutsuData as $rfsTChkHuzokubutsuData) {
        if ($rfsMShisetsuData["chk_mng_no"] == $rfsTChkHuzokubutsuData["chk_mng_no"]) {
          foreach ($_rfsChkTenkenKasyoData as $rfsChkTenkenKasyoData) {
            if ($rfsMShisetsuData["chk_mng_no"] == $rfsChkTenkenKasyoData["tenkenKasyoData"]["chk_mng_no"]) {
//              $data["rfsMShisetsuData"] = $rfsTChkHuzokubutsuData;
              $data["rfsMShisetsuData"] = $rfsMShisetsuData;
              $data["rfsTChkHuzokubutsuData"] = $rfsTChkHuzokubutsuData;
              $data["rfsChkTenkenKasyoData"] = $rfsChkTenkenKasyoData["tenkenKasyoData"];

              $this->load->model('RfsTChkTenkenKasyoModel');
              $result = $this->RfsTChkTenkenKasyoModel->set_rfs_t_chk_tenken_kasyo($data);
            }
          }
        }
      }
    }
*/

	$this->load->model('RfsTChkTenkenKasyoModel');
	$result = $this->RfsTChkTenkenKasyoModel->setRfsTChkTenkenKasyo($this->post);

    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }
}
