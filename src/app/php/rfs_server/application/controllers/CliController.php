<?php
defined('BASEPATH') OR exit('No direct script access allowed');


/**

    コントローラー名：CliController
    概要：curlコマンドで直接呼ばれるコントローラ

**/
class CliController extends CI_Controller {

  // 防雪柵の健全性のマージ
  // getメソッドで動くように変更してある。
  public function merge_to_bssk_parent(){
    log_message('info', "merge_to_bssk_parent");
    $this->load->model('CreateCheckDataExcel');
    $this->get = $this->input->get();
    $this->CreateCheckDataExcel->merge_to_bssk_parent($this->get);
  }

  public function create_excel(){
    log_message('info', "create_excel");
    $this->load->model('CreateCheckDataExcel');
    $this->get = $this->input->get();
    $sno = $this->get["sno"];
    $chk_mng_no = $this->get["chk_mng_no"];
    log_message('debug', "sno = $sno , chk_mng_no = $chk_mng_no");
    $this->CreateCheckDataExcel->outexcel_chk_mng_no=$chk_mng_no;
    $this->CreateCheckDataExcel->save_check_data($sno, -1,1,0);
    $this->CreateCheckDataExcel->outexcel_chk_mng_no=null;
    $this->CreateCheckDataExcel->save_check_data($sno, $chk_mng_no,1,0);
  }
}
