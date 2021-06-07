<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");
require(APPPATH . 'third_party/Flow/autoload.php');

/**

    コントローラー名：UploadController
    概要：アップロードに関するコントローラ
    写真でも添付でも、一度このコントローラでファイルをTempディレクトリに保存する。
**/
class UploadController extends BaseController {

  public function __construct() {
    parent::__construct();
    // メソッド共通の処理をする
    $this->load->model('UploadModel');
  }

  // アップロード
  public function upload(){
    log_message('info', "upload");

    $config = new \Flow\Config();
    $config->setTempDir(APPPATH . 'third_party/Flow/chunks_temp_folder');
    $file = new \Flow\File($config);

    $file_nm = $this->makeRandStr(20);
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      if ($file->checkChunk()) {
        header("HTTP/1.1 200 Ok");
      } else {
        header("HTTP/1.1 204 No Content");
        return ;
      }
    } else {
      if ($file->validateChunk()) {
        $file->saveChunk();
      } else {
        log_message('info', "for IE9");
        // error, invalid chunk upload request, retry
        $temp = isset($_FILES['file']) ? $_FILES['file']['tmp_name'] : $_GET['flowRelativePath'];
        $file_name = APPPATH . 'third_party/Flow/result_tmp/'.$file_nm;
        move_uploaded_file( $temp, $file_name );
        $result = $this->UploadModel->save_file($file_name,$_POST);
        $json = json_encode($result);
        $this->output->set_output($json);
        return ;
      }
    }
    if ($file->validateFile()) {
      // File upload was completed

      $file_name = APPPATH . 'third_party/Flow/result_tmp/'.$file_nm;
      $file->save($file_name);
      $result = $this->UploadModel->saveFile($file_name,$_REQUEST);

      $json = json_encode($result);
      $this->output->set_output($json);

      return;
    } else {
      log_message('info', "image_to_base64 chunk");
    }
  }

  /**
     * URLから（現場写真等から）の写真アップロード
     */
  public function uploadFromUrl(){
    // 仮の名前を作成
    $file_nm = $this->makeRandStr(20);
    $file_name = APPPATH . 'third_party/Flow/result_tmp/'.$file_nm;

    // URLから画像の取得
    $ch = curl_init($this->post['url']);
    $fp = fopen($file_name, "w");
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);

    $param = array();
    $param['flowFilename'] = basename($this->post['url']);

    // t_uploadに登録
    $result = $this->UploadModel->saveFile($file_name,$param);
    $json = json_encode($result);
    $this->output->set_output($json);

  }

  /**
     * URLから（現場写真等から）の写真アップロード
     */
  public function uploadFromUrlList(){
    $result = array();

    for($i=0;$i<count($this->post['url_list']);$i++){
      // 仮の名前を作成
      $file_nm = $this->makeRandStr(20);
      $file_name = APPPATH . 'third_party/Flow/result_tmp/'.$file_nm;

      // URLから画像の取得
      $ch = curl_init($this->post['url_list'][$i]);
      $fp = fopen($file_name, "w");
      curl_setopt($ch, CURLOPT_FILE, $fp);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_exec($ch);
      curl_close($ch);
      fclose($fp);

      $param = array();
      $param['flowFilename'] = basename($this->post['url_list'][$i]);

      // t_uploadに登録
      $result[$i] = $this->UploadModel->saveFile($file_name,$param);
    }


    $json = json_encode($result);
    $this->output->set_output($json);

  }

  public function download(){
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.basename($this->get["file"]));
    readfile($this->config->config['www_path'].$this->get["file"]);
  }

  /**
    * ランダム文字列生成 (英数字)
    * @param $length: 生成する文字数
    */
  function makeRandStr($length) {
    $str = array_merge(range('a', 'z'), range('0', '9'), range('A', 'Z'));
    $r_str = null;
    for ($i = 0; $i < $length; $i++) {
      $r_str .= $str[rand(0, count($str) - 1)];
    }
    return $r_str;
  }
}
