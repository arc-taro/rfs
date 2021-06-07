<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**

    コントローラー名：CheckListEdtAjax
    概要：施設の点検一覧更新に関するajax呼び出し口
    ある施設の点検の履歴（一覧）を更新したい場合に呼ばれる




**/
class CheckListEdtAjax extends BaseController {

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     *         http://example.com/index.php/welcome
     *    - or -
     *         http://example.com/index.php/welcome/index
     *    - or -
     * Since this controller is set as the default controller in
     * config/routes.php, it's displayed at http://example.com/
     *
     * So any other public methods not prefixed with an underscore will
     * map to /index.php/welcome/<method_name>
     * @see https://codeigniter.com/user_guide/general/urls.html
     */

    public function __construct() {
        parent::__construct();

        // メソッド共通の処理をする
        $this->load->model('EdtCheck');

    }

    // 基本情報の更新
    public function set_baseinfo(){
        log_message('info', "set_baseinfo");

        $this->EdtCheck->set_baseinfo($this->post);
    }

    // 部材以下情報の登録
    public function set_chkdata(){
        log_message('info', "set_chkdata");

      // shisetsu_keishiki_cdが無く、input_keishiki_cd(形式)がある場合は
      // 基本情報を保存させる
      if (!$this->post['baseinfo']['shisetsu_keishiki_cd'] && $this->post['baseinfo']['input_keishiki_cd']) {
        $this->EdtCheck->set_keishiki($this->post);
      }
      $this->EdtCheck->set_baseinfo($this->post);
      $this->EdtCheck->set_chkdata($this->post);
    }

    // パトロール員の追加
    public function add_patrolin(){
        log_message('info', "add_patrolin");

        $this->EdtCheck->add_patrolin($this->post);
    }

    // 調査員の追加
    public function add_investigator(){
        log_message('info', "add_investigator");

        $this->EdtCheck->add_investigator($this->post);
    }

    // 点検データメインへの新規登録
    public function add_chkdatamain(){
        log_message('info', "add_chkdatamain");

        $result = $this->EdtCheck->add_chkdatamain($this->post);
        $this->json = json_encode($result);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }

    // 附属物データの追加
    public function add_huzokubutsu(){
        log_message('info', "add_huzokubutsu");

        $this->EdtCheck->add_huzokubutsu($this->post);
    }

    // 防雪柵管理情報の追加
    public function add_bousetsusaku_mng_info(){
        log_message('info', "add_bousetsusaku_mng_info");

        $this->EdtCheck->add_bousetsusaku_mng_info($this->post);
    }

    // 防雪柵管理情報の更新
    public function set_bousetsusaku_mng_info_complete(){
        log_message('info', "set_bousetsusaku_mng_info_complete");

        $this->EdtCheck->set_bousetsusaku_mng_info_complete($this->post);
    }

    // 点検票の削除
    public function del_chkdata(){
        log_message('info', "del_chkdata");

        $this->EdtCheck->del_chkdata($this->post);
    }

    // 防雪柵の健全性のマージ
    public function merge_to_bssk_parent(){
        log_message('info', "merge_to_bssk_parent");

        $this->load->model('CreateCheckDataExcel');

        $this->CreateCheckDataExcel->merge_to_bssk_parent($this->post);
    }

  /***
   * 差戻し
   ***/
  public function tenkenRemand(){
    log_message('info', "tenkenRemand");
    // フェーズを書き換え、履歴を上げる
    $ret=$this->EdtCheck->tenkenRemand($this->post);
    // Excelの更新
    $this->load->model('CreateCheckDataExcel');
    log_message("info",print_r($ret,true));
    $this->CreateCheckDataExcel->save_check_data($ret['sno'], $ret['chk_mng_no'], $ret['struct_idx'], $ret['excel_ver']);

  }

}
