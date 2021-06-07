<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");
require(APPPATH . 'third_party/Flow/autoload.php');

/**
    コントローラー名：ImageAjax
    概要：写真関連の処理
**/
class PictureAjax extends BaseController {

    /**
     * コンストラクタ
     * flow.jsのライブラリ（サーバ側）を読みだす。
     */
    public function __construct() {
        parent::__construct();
        $this->load->model('PictureModel');
    }

    // 基本情報の更新
    public function image_upload(){
        log_message('info', "image_to_base64");

        $config = new \Flow\Config();
        $config->setTempDir(APPPATH . 'third_party/Flow/chunks_temp_folder');
        $file = new \Flow\File($config);

        $file_nm = $this->makeRandStr(10).".jpg";
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
                $this->save_picture($file_name,$_POST);

//                header("HTTP/1.1 400 Bad Request");
                return ;
            }
        }
        if ($file->validateFile()) {
            // File upload was completed
            $file_name = APPPATH . 'third_party/Flow/result_tmp/'.$file_nm;
            $file->save($file_name);
            $this->save_picture($file_name,$_REQUEST);
            log_message('info', "image_to_base64 finished");

        } else {
            log_message('info', "image_to_base64 chunk");
            // This is not a final chunk, continue to upload
        }
    }

    /**
     * 現場写真から（URLから）の写真アップロード
     */
    public function image_from_url(){
        $file_nm = $this->makeRandStr(10).".jpg";
        $file_name = APPPATH . 'third_party/Flow/result_tmp/'.$file_nm;
        // URLから画像の取得
        $ch = curl_init($this->post['url']);
        $fp = fopen($file_name, "w");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        $this->save_picture($file_name,$this->post);

    }
    /**
     * 画像をDBへ登録する
     */
    public function save_picture($file_name,$param){
        if($param['mode']=='chk_picture'){
            $this->PictureModel->save_picture($file_name,$param);
        }else{
            $this->PictureModel->save_picture_zenkei($file_name,$param);
        }
    }

    /**
    * 画像データを取得する
    */
    public function get_picture(){
        if($this->post['mode']=='chk_picture'){
            $data = $this->PictureModel->get_picture($this->post);
        }else{
            $data = $this->PictureModel->get_picture_zenkei($this->post);
        }
        $this->json = json_encode($data);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }

    /**
    * 画像データを本保存する。
    */
    public function save_fix_picture(){
        if(isset($this->post['chk_mng_no'])){
            $this->PictureModel->save_fix_picture($this->post);
        }
        if(isset($this->post['zenkei_picture_data'])){
            $this->PictureModel->save_fix_picture_zenkei($this->post);
        }
        $this->json = json_encode(0);
        $this->output->set_content_type('application/json')->set_output($this->json);

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

    /**
     * 現場写真のアルバムリストを取得する。
     */
    function get_gs_title_list(){
        $data = $this->PictureModel->get_gs_title_list($this->post);
        $this->json = json_encode($data);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }

    /**
     * 現場写真の写真リストを取得する。
     */
    function get_gs_picture_list(){
        $data = $this->PictureModel->get_gs_picture_list($this->post);
        $this->json = json_encode($data);
        $this->output->set_content_type('application/json')->set_output($this->json);
    }
}
