<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**
    コントローラー名：SumHuzokubutsuController
    概要：H28年度点検実施施設の集計を行う
**/
class SumHuzokubutsuController extends BaseController {

  const TMP_EXCEL_PATH = __DIR__ . '/../libraries/phpExcel/results/';

  /**
     * コンストラクタ
     */
  public function __construct() {
    parent::__construct();
  }

  public function sumHuzokubutsu() {
    log_message("debug","sumHuzokubutsu --Start--");

    // H28年度対象データを取得
    $this->load->model('SumHuzokubutsuModels');
    // 施設毎に全データを取得する
    $result=$this->SumHuzokubutsuModels->getHuzokubutsuAll($this->get);

    // CSVファイルパスの設定
    $file_path = self::TMP_EXCEL_PATH;
    $file_nm = 'sum_huzokubutsu_csv_list_' . date('Ymd') . uniqid() . '.csv';

    $shisetsu="";
    if ($this->get['shisetsu_kbn']==1) {
      $shisetsu="DH";
    }else if($this->get['shisetsu_kbn']==2){
      $shisetsu="JD";
    }else if($this->get['shisetsu_kbn']==3){
      $shisetsu="SS";
    }else if($this->get['shisetsu_kbn']==4){
      $shisetsu="BS";
    }else if($this->get['shisetsu_kbn']==5){
      $shisetsu="YH";
    }

    $downlaod_file_nm="${shisetsu}_huzokubutsu_csv_list_". date('YmdHis') . '.csv';
    $file_path_nm = $file_path . $file_nm;
    $file = fopen($file_path_nm, 'w');

    $fields = $this->SumHuzokubutsuModels->outputListCsvHead($file,$this->get['shisetsu_kbn']);

    // 施設毎にデータを整形しながら、csvを出力する。
    $outarr=[];
    foreach($result as $item){
      $res = $this->SumHuzokubutsuModels->shapePerShisetsu($item);
      $this->SumHuzokubutsuModels->outputListCsvRowData($file,$fields,$res);
    }

    // 台帳のCSVを出力する
    //$this->load->model('SumHuzokubutsuModels');

    // CSVをダウンロードする
    fclose($file);
    $this->load->helper('download');
    force_download($downlaod_file_nm, file_get_contents($file_path_nm));

    // 整形したデータを全てCSV出力する(ファイル配置まで)
//    $this->SumHuzokubutsuModels->createCsvSumHuzokubutsu($outarr);

    // ダウンロードは後で考える
//    log_message("debug", print_r($outarr,true));

    return;
  }

  public function requestCreateCsv() {
    log_message('debug', __METHOD__);
    $this->load->model('SumHuzokubutsuModels');
    $this->SumHuzokubutsuModels->requestCreateCsv($this->post);
  }

}
