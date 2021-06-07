<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**
    コントローラー名：SysTopAjax
    概要：道路施設管理システムTOPコントローラー
**/
class SysTopAjax extends BaseController {

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
  public function initSysTop(){
    log_message('info', "initSysTop");

    // システムトップなので、この時点で検索条件がSESSIONに入っている場合は
    // SESSIONから[srch_shisetsu]を削除する
    $this->rgstSessionSrch("",1,2);

    // マスタの取得
    $this->load->model('SchCommon');
    $dogen_syucchoujo=$this->SchCommon->getDogenSyucchoujo($this->get);

    // 建管未選択時は、先頭の建管コードが対象になるので、その建管で取得する
    $dogen_cd=$this->get['dogen_cd'];
    $syucchoujo_cd=$this->get['syucchoujo_cd'];
    if ($dogen_cd==0) {
      $dogen_cd=json_decode($dogen_syucchoujo[0]['dogen_row'])->dogen_info[0]->dogen_cd;
      $syucchoujo_cd=0;
    }
    $result = $this->getSumShisetsu($dogen_cd, $syucchoujo_cd);
    $result["dogen_syucchoujo"] = $dogen_syucchoujo;

    // 電気通信URL
    $this->config->load('config');
    $result["ele_url"]=$this->config->config['www_ele_path'];

    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  /**
    * 集計取得
    *   引数の建管、出張所の集計結果を返却
    */
  public function getSumAll(){
    log_message('info', "getSumAll");
    $dogen_cd=$this->get['dogen_cd'];
    $syucchoujo_cd=$this->get['syucchoujo_cd'];
    $result = $this->getSumShisetsu($dogen_cd, $syucchoujo_cd);
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  /**
    * 基本情報を取得
    */
  public function getSumShisetsu($dogen_cd, $syucchoujo_cd){
    log_message('info', "getSumShisetsu");
    // 全施設の集計を行う
    $this->load->model('SysTopModel');
    $this->SysTopModel->refreshSumShisetsu();
    // 道路標識
    $ret_dh=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 1);
    // 道路情報提供装置
    $ret_jd=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 2);
    // 道路照明施設
    $ret_ss=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 3);
    // 防雪柵
    $ret_bs=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 4);
    // 大型スノーポール
    $ret_yh=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 5);
    // 気象情報収集装置（監視局）
    $ret_ka=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 6);
    // 気象情報収集装置（中継局）
    $ret_kb=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 7);
    // 気象情報収集装置（観測局）
    $ret_kc=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 8);
    // 気象情報収集装置（ITVカメラ）
    $ret_kd=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 9);
    // 気象情報収集装置（受信局）
    $ret_ki=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 10);
    // 道路情報板（C型）
    $ret_jh=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 11);
    // 遮断機
    $ret_sd=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 12);
    // ドット線
    $ret_dt=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 13);
    // トンネル
    $ret_tt=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 14);
    // 駐車公園
    $ret_ck=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 15);
    // 緑化樹木
    $ret_sk=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 16);
    // 立体横断
    $ret_bh=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 17);
    // 擁壁
    $ret_dy=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 18);
    // 法面
    $ret_dn=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 19);
    // 浸出装置
    $ret_ts=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 20);
    // ロードヒーティング(未決定だったので仮にrhにした)
    $ret_rh=$this->SysTopModel->getSumShisetsu($dogen_cd, $syucchoujo_cd, 21);

    // 返却$result作成
    $result["dh"] = $ret_dh;
    $result["jd"] = $ret_jd;
    $result["ss"] = $ret_ss;
    $result["bs"] = $ret_bs;
    $result["yh"] = $ret_yh;
    $result["ka"] = $ret_ka;
    $result["kb"] = $ret_kb;
    $result["kc"] = $ret_kc;
    $result["kd"] = $ret_kd;
    $result["ki"] = $ret_ki;
    $result["jh"] = $ret_jh;
    $result["sd"] = $ret_sd;
    $result["dt"] = $ret_dt;
    $result["tt"] = $ret_tt;
    $result["ck"] = $ret_ck;
    $result["sk"] = $ret_sk;
    $result["bh"] = $ret_bh;
    $result["dy"] = $ret_dy;
    $result["dn"] = $ret_dn;
    $result["ts"] = $ret_ts;
    $result["rh"] = $ret_rh;
    return $result;
  }

}
