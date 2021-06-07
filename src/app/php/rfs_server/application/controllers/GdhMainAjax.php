<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**
    コントローラー名：GdhMainAjax
    概要：案内標識登録画面のコントローラー
**/
class GdhMainAjax extends BaseController {

  /**
     * コンストラクタ
     */
  public function __construct() {
    parent::__construct();
  }

  /**
    * 初期データ取得
    *   建管、出張所、セッション情報の建管、出張所でデータ取得
    */
  public function init(){
    log_message('info', "init");
    // モデルの取得
    $this->load->model('SchCommon');
    $this->load->model('GdhMainModel');

    // 現在選択中の出張所
    $dogen_cd=$this->session['mngarea']['dogen_cd']; // 建管コード
    $syucchoujo_cd=$this->session['mngarea']['syucchoujo_cd']; // 出張所コード
    $syozoku_cd=$this->session['ath']['syozoku_cd']; // 所属コード
    $arr['dogen_cd']=$dogen_cd;
    $arr['syucchoujo_cd']=$syucchoujo_cd;
    $arr['syozoku_cd']=$syozoku_cd;

    // マスタの取得
    $result['mst']['gdh_syubetsu']=$this->SchCommon->getMstSimple("gdh_m_gdh_syubetsu");
    $result['mst']['kousa']=$this->SchCommon->getMstSimple("gdh_m_kousa");
    $result['mst']['brd_color']=$this->SchCommon->getMstSimple("gdh_m_brd_color");
    $result['mst']['taisaku_status']=$this->SchCommon->getMstSimple("gdh_sm_taisaku_status","taisaku_status_cd != 3");
    $result['mst']['taisaku_status_min']=$this->SchCommon->getMstSimple("gdh_sm_taisaku_status","taisaku_status_cd < 3");
    $result['mst']['taisaku_kouhou']=$this->SchCommon->getMstSimple("gdh_m_taisaku_kouhou");
    $result['mst']['gaitou_higaitou']=$this->SchCommon->getMstSimple("gdh_sm_gaitou_higaitou");
    $result['mst']['yotei_nendo']=$this->GdhMainModel->getYoteiNendo();

    // 案内標識基本情報取得
    $shisetsuSub=$this->GdhMainModel->getTShisetsuSub($this->get['sno']);
    $result['data']['shisetsu_sub']=$shisetsuSub;

    // 案内標識板一枚目のgdh_idxを取得
    $min_gdh_idx = $this->GdhMainModel->getMinGdhIdx($this->get['sno']);
    $result['data']['min_gdh_idx']=$min_gdh_idx;

    // 対応状況データ取得
    if ($this->get['gdh_idx'] < 0) {
      $responseStatus=$this->GdhMainModel->getTResponseStatus($this->get['sno'], $min_gdh_idx);
      for ($i = 0; $i < count($responseStatus); $i++) {
        $responseStatus[$i]['rireki_no'] = "";
        $responseStatus[$i]['taisaku_status_cd'] = "";
        $responseStatus[$i]['taisaku_kouhou_cd'] = "";
        $responseStatus[$i]['dououdou']="";
      }
      $result['data']['response_status']=$responseStatus;
    } else {
      $responseStatus=$this->GdhMainModel->getTResponseStatus($this->get['sno'], $this->get['gdh_idx']);
      $result['data']['response_status']=$responseStatus;
    }

    // 案内標識写真データ取得
    $pic_data =$this->GdhMainModel->getTPicture($this->get['sno'], $this->get['gdh_idx']);
    $result['data']['pic_data']=$pic_data;

    // 案内標識板色取得
    $brd_color = $this->GdhMainModel->getTBrd($this->get['sno'], $this->get['gdh_idx']);
    $result['data']['brd_color']=$brd_color;

    // 台帳取得
    $result['data']['daichou']=$this->getDaichou($this->get['sno']);
    $result['data']['daichou']['shisetsu_kbn']=1;
    $result['data']['daichou']['sno']=$this->get['sno'];

    // 返却
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  /**
    * DB登録処理
    */
  public function save(){
    log_message('info', "save");

    $this->load->model('GdhMainModel');

    // DB登録Transaction処理
    $result['gdh_idx'] = $this->GdhMainModel->saveMain($this->post);

    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  /**
    * DB削除処理
    */
  public function gdhDbDelete(){
    log_message('info', "gdhDbDelete");

    $this->load->model('GdhMainModel');

    // DB登録Transaction処理
    $gdhIdx = $this->GdhMainModel->delMain($this->post);

    return $gdhIdx;
  }

  /**
    * 台帳データ取得処理（台帳更新処理に使用）
    * 引数：$sno
    */
  private function getDaichou($sno) {
    // 施設区分マスタ-台帳テーブル名取得
    $shisetsu_kbn_arr = array();
    $shisetsu_kbns=$this->SchCommon->getMstSimple('rfs_m_shisetsu_kbn');
    $tbl_nm="";
    for ($i=0;$i<count($shisetsu_kbns);$i++) {
      $item = $shisetsu_kbns[$i];
      if ($item['shisetsu_kbn']==1) {
        // 台帳テーブル名
        $tbl_nm=$item['daityou_tbl'];
        break;
      }
    }

    // 台帳取得
    $this->load->model('FamEditModel');
    return $this->FamEditModel->getDaityou($sno, $tbl_nm);
  }

    /**
     * 現場写真から（URLから）の写真アップロード
     */
    public function imageFromUrl(){
      $file_nm = $this->makeRandStr(15).".jpg";
      $file_path = APPPATH . 'third_party/Flow/result_tmp/'.$file_nm;
      $server_path = $this->config->config['www_path'].'upload/temp/'.$file_nm;

      // URLから画像の取得
      $ch = curl_init($this->post['url']);
      $fp = fopen($file_path, "w");
      curl_setopt($ch, CURLOPT_FILE, $fp);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_exec($ch);
      curl_close($ch);
      fclose($fp);

      log_message('DEBUG', $file_nm);
      log_message('DEBUG', $file_path);

      rename($file_path ,$server_path);

      $result['copy_path'] = 'upload/temp/'.$file_nm;

      $this->json = json_encode($result);
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
}
