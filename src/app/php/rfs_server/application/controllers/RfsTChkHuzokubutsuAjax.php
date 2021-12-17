<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require("PwaBaseController.php");

class RfsTChkHuzokubutsuAjax extends PwaBaseController {

  function __construct() {
    parent::__construct();
  }

  public function get_rfs_t_chk_huzokubutsu() {
    $data = $this->post;
    log_message('debug', "--------get_rfs_t_chk_huzokubutsu post------------->");
    log_message('debug', print_r($data, true));
    $this->load->model('RfsTChkHuzokubutsuModel');
    $result = $this->RfsTChkHuzokubutsuModel->get_rfs_t_chk_huzokubutsu($data);
    // log_message('debug', print_r($result, true));
    
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  // picture
  public function get_rfs_t_chk_huzokubutsu2() {
    $data = $this->post;
    log_message('debug', "--------get_rfs_t_chk_huzokubutsu2 post------------->");
    log_message('debug', print_r($data, true));
    $this->load->model('RfsTChkHuzokubutsuModel');
    $result = $this->RfsTChkHuzokubutsuModel->get_rfs_t_chk_huzokubutsu2($data);
    // log_message('debug', print_r($result, true));
    
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  public function set_rfs_t_chk_huzokubutsu() {
/*    $_data = $this->post;
    $_rfsMShisetsuData = $_data["rfsMShisetsuData"];
    $_rfsTChkHuzokubutsuData = $_data["rfsTChkHuzokubutsuData"];
    foreach ($_rfsMShisetsuData as $rfsMShisetsuData) {
      foreach ($_rfsTChkHuzokubutsuData as $rfsTChkHuzokubutsuData) {
        if ($rfsMShisetsuData["chk_mng_no"] == $rfsTChkHuzokubutsuData["chk_mng_no"]) {
          // TODO: rireki_no, phase
          $rireki_no = intval($rfsTChkHuzokubutsuData["rireki_no"]);
          $rfsTChkHuzokubutsuData["rireki_no"] = $rireki_no;
          $rfsTChkHuzokubutsuData["phase"] = 1;
          $data["rfsMShisetsuData"] = $rfsTChkHuzokubutsuData;
          $data["rfsTChkHuzokubutsuData"] = $rfsTChkHuzokubutsuData;

          $this->load->model('RfsTChkHuzokubutsuModel');
          $result = $this->RfsTChkHuzokubutsuModel->set_rfs_t_chk_huzokubutsu($data);
        }
      }
    }
*/
//log_message("debug","--------------set_rfs_t_chk_huzokubutsu post---------------->");
//log_message("debug",print_r($this->post,true));

	$this->load->model('RfsTChkHuzokubutsuModel');
	$result = $this->RfsTChkHuzokubutsuModel->setRfsTChkHuzokubutsu($this->post);

//log_message("debug","--------------set_rfs_t_chk_huzokubutsu result---------------->");
//log_message("debug",print_r($result,true));

    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  // picture
  public function set_rfs_t_chk_huzokubutsu2() {
//return;
log_message("debug","--------------set_rfs_t_chk_huzokubutsu2 post---------------->");
log_message("debug",print_r($this->post,true));
/*
    $_data = $this->post;
    $_rfsMShisetsuData = $_data["rfsMShisetsuData"];
    $_rfsTChkHuzokubutsuData = $_data["rfsTChkHuzokubutsuData"];
    $_pictureData = $_data["pictureData"];
    foreach ($_rfsMShisetsuData as $rfsMShisetsuData) {
      foreach ($_rfsTChkHuzokubutsuData as $rfsTChkHuzokubutsuData) {
        if ($rfsMShisetsuData["chk_mng_no"] == $rfsTChkHuzokubutsuData["chk_mng_no"]) {
          foreach ($_pictureData as $pictureData) {
            if ($rfsMShisetsuData["shisetsu_cd"] == $pictureData["shisetsu_cd"]) {
              $data["rfsMShisetsuData"] = $rfsMShisetsuData;
              $data["rfsTChkHuzokubutsuData"] = $rfsTChkHuzokubutsuData;
              $data["pictureData"] = $pictureData;

              $this->load->model('RfsTChkHuzokubutsuModel');
              $result = $this->RfsTChkHuzokubutsuModel->set_rfs_t_chk_huzokubutsu2($data);
            }
          }
        }
      }
    }
*/

	$this->load->model('RfsTChkHuzokubutsuModel');
	$result = $this->RfsTChkHuzokubutsuModel->setRfsTChkPicture($this->post);

    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }
}
