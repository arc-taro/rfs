<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**
    コントローラー名：SysTopAjax
    概要：道路施設管理システムTOPコントローラー
**/
class CsvListController extends BaseController {

  /**
     * コンストラクタ
     */
  public function __construct() {
    parent::__construct();
  }

  /**
    * 初期データ取得
    *   建管、出張所、セッション情報の建管、出張所データ取得
    */
  public function init(){
    log_message('info', __METHOD__);

    // マスタの取得
    $this->load->model('CsvListModel');
    $result["csv_list"]=$this->CsvListModel->getCsvList();
    

    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  /**
    * ファイルダウンロード
    *   該当のCSVZIPをダウンロード
    */
  public function zipDownload(){
    log_message('info', __METHOD__);
    $post = $this->input->post();
    log_message('info', print_r($post,true));
    // マスタの取得
    $this->load->model('CsvListModel');
    $request_group=$post['request_group'];
    $zip_data=$this->CsvListModel->getZipData($request_group);
    // ダウンロードファイル
    $zip_file_nm = $zip_data[0]['zip_file_nm'];
    $base_path=$this->config->config['www_path'];
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename*=UTF-8\'\'' .rawurlencode($zip_file_nm));
    readfile($base_path.'csv/'.$request_group.'/'.$zip_file_nm);  
  }
}