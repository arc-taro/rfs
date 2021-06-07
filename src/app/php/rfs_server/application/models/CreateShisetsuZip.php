<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property CI_Zip $zip
 * @property SchShisetsu $SchShisetsu
 * @property SchCheck $SchCheck
 */
class CreateShisetsuZip extends CI_Model {

  const TMP_PATH = __DIR__ . '/../libraries/phpExcel/results/';
  const DIR_BLANK = 'blank';

  protected $excel_root;

  public function __construct() {
    parent::__construct();

    $this->excel_root = $this->config->config['www_path'];
    $this->rfs = $this->load->database('rfs', true);

    $this->load->model('SchCheck');
  }


  /**
   * zipファイルを作成後、ダウンロードする
   *
   * @param array $zip_data_array
   */
  public function outputShisetsuDataPack($zip_data_array) {
    log_message('debug', __METHOD__);
    try {
      $this->load->library('zip');

      // zip保存パス
      $zip_path_base = self::TMP_PATH;
      $zip_path = $zip_path_base . date('Ymd') . '/' . uniqid() . '/';
      $zip_nm = date('YmdHis') . '.zip';

      // zip対象ファイルをzipフォルダへコピーする
      foreach ($zip_data_array as $shisetsu) {

        // zip保存パスに出張所コードを追加
        $zip_path_tmp = $zip_path;

        if ($shisetsu["syucchoujo_cd"] == 1) {
          $zip_path_tmp .= 'sapporo';
        } else if ($shisetsu["syucchoujo_cd"] == 2) {
          $zip_path_tmp .= 'iwamizawa';
        } else if ($shisetsu["syucchoujo_cd"] == 3) {
          $zip_path_tmp .= 'takikawa';
        } else if ($shisetsu["syucchoujo_cd"] == 4) {
          $zip_path_tmp .= 'fukagawa';
        } else if ($shisetsu["syucchoujo_cd"] == 5) {
          $zip_path_tmp .= 'toubetsu';
        } else if ($shisetsu["syucchoujo_cd"] == 6) {
          $zip_path_tmp .= 'naganuma';
        } else if ($shisetsu["syucchoujo_cd"] == 8) {
          $zip_path_tmp .= 'chitose';
        } else if ($shisetsu["syucchoujo_cd"] == 11) {
          $zip_path_tmp .= 'rankoshi';
        } else if ($shisetsu["syucchoujo_cd"] == 12) {
          $zip_path_tmp .= 'yoichi';
        } else if ($shisetsu["syucchoujo_cd"] == 13) {
          $zip_path_tmp .= 'kyouwa';
        } else if ($shisetsu["syucchoujo_cd"] == 14) {
          $zip_path_tmp .= 'makkari';
        } else if ($shisetsu["syucchoujo_cd"] == 18) {
          $zip_path_tmp .= 'otaru';
        } else if ($shisetsu["syucchoujo_cd"] == 21) {
          $zip_path_tmp .= 'matsumae';
        } else if ($shisetsu["syucchoujo_cd"] == 22) {
          $zip_path_tmp .= 'hakodate';
        } else if ($shisetsu["syucchoujo_cd"] == 23) {
          $zip_path_tmp .= 'yakumo';
        } else if ($shisetsu["syucchoujo_cd"] == 24) {
          $zip_path_tmp .= 'esashi';
        } else if ($shisetsu["syucchoujo_cd"] == 25) {
          $zip_path_tmp .= 'imakane';
        } else if ($shisetsu["syucchoujo_cd"] == 30) {
          $zip_path_tmp .= 'okushiri';
        } else if ($shisetsu["syucchoujo_cd"] == 31) {
          $zip_path_tmp .= 'tomakomai';
        } else if ($shisetsu["syucchoujo_cd"] == 32) {
          $zip_path_tmp .= 'touya';
        } else if ($shisetsu["syucchoujo_cd"] == 33) {
          $zip_path_tmp .= 'noboribetsu';
        } else if ($shisetsu["syucchoujo_cd"] == 34) {
          $zip_path_tmp .= 'monbetsu';
        } else if ($shisetsu["syucchoujo_cd"] == 35) {
          $zip_path_tmp .= 'urakawa';
        } else if ($shisetsu["syucchoujo_cd"] == 41) {
          $zip_path_tmp .= 'asahikawa';
        } else if ($shisetsu["syucchoujo_cd"] == 42) {
          $zip_path_tmp .= 'shibetsu';
        } else if ($shisetsu["syucchoujo_cd"] == 43) {
          $zip_path_tmp .= 'furano';
        } else if ($shisetsu["syucchoujo_cd"] == 44) {
          $zip_path_tmp .= 'bifuka';
        } else if ($shisetsu["syucchoujo_cd"] == 51) {
          $zip_path_tmp .= 'rumoi';
        } else if ($shisetsu["syucchoujo_cd"] == 52) {
          $zip_path_tmp .= 'haboro';
        } else if ($shisetsu["syucchoujo_cd"] == 53) {
          $zip_path_tmp .= 'enbetsu';
        } else if ($shisetsu["syucchoujo_cd"] == 61) {
          $zip_path_tmp .= 'wakkanai';
        } else if ($shisetsu["syucchoujo_cd"] == 62) {
          $zip_path_tmp .= 'utanobori';
        } else if ($shisetsu["syucchoujo_cd"] == 63) {
          $zip_path_tmp .= 'rishiri';
        } else if ($shisetsu["syucchoujo_cd"] == 64) {
          $zip_path_tmp .= 'rebun';
        } else if ($shisetsu["syucchoujo_cd"] == 71) {
          $zip_path_tmp .= 'kitami';
        } else if ($shisetsu["syucchoujo_cd"] == 72) {
          $zip_path_tmp .= 'abashiri';
        } else if ($shisetsu["syucchoujo_cd"] == 73) {
          $zip_path_tmp .= 'monbetsu';
        } else if ($shisetsu["syucchoujo_cd"] == 74) {
          $zip_path_tmp .= 'shari';
        } else if ($shisetsu["syucchoujo_cd"] == 75) {
          $zip_path_tmp .= 'engaru';
        } else if ($shisetsu["syucchoujo_cd"] == 76) {
          $zip_path_tmp .= 'okoppe';
        } else if ($shisetsu["syucchoujo_cd"] == 81) {
          $zip_path_tmp .= 'obihiro';
        } else if ($shisetsu["syucchoujo_cd"] == 83) {
          $zip_path_tmp .= 'shikaoi';
        } else if ($shisetsu["syucchoujo_cd"] == 84) {
          $zip_path_tmp .= 'taiki';
        } else if ($shisetsu["syucchoujo_cd"] == 85) {
          $zip_path_tmp .= 'ashoro';
        } else if ($shisetsu["syucchoujo_cd"] == 86) {
          $zip_path_tmp .= 'urahoro';
        } else if ($shisetsu["syucchoujo_cd"] == 91) {
          $zip_path_tmp .= 'kushiro';
        } else if ($shisetsu["syucchoujo_cd"] == 92) {
          $zip_path_tmp .= 'nemuro';
        } else if ($shisetsu["syucchoujo_cd"] == 93) {
          $zip_path_tmp .= 'teshikaga';
        } else if ($shisetsu["syucchoujo_cd"] == 94) {
          $zip_path_tmp .= 'nakashibetsu';
        } else if ($shisetsu["syucchoujo_cd"] == 96) {
          $zip_path_tmp .= 'akkeshi';
        } else {
          $zip_path_tmp .= 'miteigi';
        }
        $zip_path_tmp .= '/';

        // zipフォルダの作成
        if(!is_dir($zip_path_tmp)) {
          mkdir($zip_path_tmp, 0777, true);
        }

        // Excelファイルパスを取得
        $file_path_nm = $this->excel_root . $shisetsu["file_path"];

        // Excelファイル名を取得
        $file_nm = basename($file_path_nm);

        // zip対象フォルダへコピー
        copy($file_path_nm, $zip_path_tmp . $file_nm);
        chmod($zip_path_tmp . $file_nm, 0777);
      }

      // zip対象フォルダを圧縮
      // zip : zipファイル保存パス 対象フォルダ
      $command = "cd ". $zip_path ."; " . "zip -r ". $zip_path . $zip_nm ." .";
      exec($command);

      chmod($zip_path . $zip_nm, 0777);

      // 圧縮したファイルをダウンロードさせる
      //        header('Pragma: public');
      header("Content-Type: application/octet-stream");
      header("Content-Disposition: attachment; filename=".$zip_nm);
      readfile($zip_path . $zip_nm);

    } catch (RecordNotFoundException $e) {
      $this->error($e->getMessage());
    }
  }
}
