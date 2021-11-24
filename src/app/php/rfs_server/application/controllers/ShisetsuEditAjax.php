<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**
    コントローラー名：ShisetsuEditAjax
    概要：基本情報編集コントローラー
**/
class ShisetsuEditAjax extends BaseController {

  /**
   * コンストラクタ
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * 初期データ取得
   *
   * 共通処理として、建管・出張所情報の取得(sessionの値から)
   * 新規の場合は、施設区分のみ取得する。
   * それ以外の場合は、施設情報に加え
   * 施設区分、路線(該当出張所)、施設形式、形式/区分を取得する。
   *
   */
  public function initShisetsuEdit(){
    log_message('info', "initShisetsuEdit");

    // 建管・出張所情報の取得
    $this->load->model('SchCommon');

    // 施設区分マスタだけを取得
    $shisetsu_kbns=$this->SchCommon->getShisetsuKbns();
    $result['shisetsu_kbn']=$shisetsu_kbns;

    // 設置年度の取得
    $yyyy=date("Y");
    $wareki_list = $this->SchCommon->getWarekiList(1925,$yyyy,"desc");
    $result['wareki_list']=$wareki_list;
    
    // GET引数
    $sno=$this->get['sno'];

    // 新規時は、建管/出張所、施設区分だけで良いので、ここは修正時のみ
    if ($sno == 0) {
      // 新規の場合の出張所は選択されている出張所
      $syucchoujo_cd=$this->session['mngarea']['syucchoujo_cd'];
    }else{
      // 施設情報
      $shisetsu = $this->SchCommon->getShisetsu($sno);
      $result['shisetsu']=$shisetsu;

      // 施設区分/出張所コード
      $shisetsu_kbn=$shisetsu[0]['shisetsu_kbn'];
      $syucchoujo_cd=$shisetsu[0]['syucchoujo_cd'];

      // 防雪柵の場合
      if ($shisetsu_kbn==4) {
        // 子データの取得
        $this->load->model('ShisetsuEditModel');
        $bousetsusaku = $this->ShisetsuEditModel->getBousetsusaku($sno);
        $result['bousetsusaku']=$bousetsusaku;  // 結果に追加
        // 点検データの有無(点検対象に登録されている場合を点検データ有とする)
        $chk_data_exist=$this->ShisetsuEditModel->getChkTargetExist($sno);
        $result['chk_data_exist']=$chk_data_exist;  // 結果に追加
      }

      // マスタを取得
      // 路線
      $rosens=$this->SchCommon->getRosens($syucchoujo_cd);
      $result['rosen']=$rosens;
      // 施設形式マスタ
      $shisetsu_keishikis=$this->SchCommon->getShisetsuKeishikis($shisetsu_kbn);
      $result['shisetsu_keishiki']=$shisetsu_keishikis;
      // 形式区分マスタ
      $keishiki_kubuns=$this->SchCommon->getKeishikiKubuns($shisetsu_kbn);
      $result['keishiki_kubun']=$keishiki_kubuns;
    }

    // 該当の管理者情報取得
    $kanri_info=$this->SchCommon->getKanrisyaInfo($syucchoujo_cd);
    $result['kanri_info']=$kanri_info;

    // 施設区分と各種点検の組み合わせ一覧を取得
    $result['patrol_types'] = $this->getPatrolTypeLists();

    $this->config->load('config');
    $result["ele_url"]=$this->config->config['ele_url'];

    $this->json = json_encode($result,JSON_NUMERIC_CHECK);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  // 施設登録 施設IDの登録
  public function saveShisetsuCd(){
    log_message('info', "saveShisetsuCd");

    $this->load->model('ShisetsuEditModel');
    $result=$this->ShisetsuEditModel->insShisetsuData($this->post);
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  /**
    * 基本情報を保存する。
    */
  public function setShisetsu(){
    log_message('info', "setShisetsu");

    // 施設情報
    $this->load->model('SchCommon');
    $sno=$this->post['data']['sno'];
    $shisetsu = $this->SchCommon->getShisetsu($sno);
    $shisetsu_kbn=$this->post['data']['shisetsu_kbn'];
    $this->load->model('ShisetsuEditModel');
    $result=$this->ShisetsuEditModel->setShisetsu($this->post,$shisetsu);
    $this->json = json_encode($result);
    $this->output->set_content_type('application/json')->set_output($this->json);

    // 施設台帳Excel(再)作成
    $this->saveShisetsuDaichou($sno, $shisetsu_kbn);
    $patrol_types = $this->getPatrolTypeLists();

    // 付属物点検Excel(再)作成
    if (in_array($shisetsu_kbn, $patrol_types['huzokubutsu'])) {
      $this->saveChkData($this->post['data']);
    }

  }

  // 施設台帳保存
  public function saveShisetsuDaichou($sno, $shisetsu_kbn){
    log_message('debug', __METHOD__);

    $model_excel = null;

    // 施設区分によって保存内容が異なる
    if ($shisetsu_kbn==1) {        // 道路標識
      $this->load->model("CreateDaichouExcelDH");
      $model_excel = $this->CreateDaichouExcelDH;
    } else if ($shisetsu_kbn==2) { // 情報板電光
      $this->load->model("CreateDaichouExcelJD");
      $model_excel = $this->CreateDaichouExcelJD;
    } else if ($shisetsu_kbn==3) { // 照明
      $this->load->model("CreateDaichouExcelSS");
      $model_excel = $this->CreateDaichouExcelSS;
    } else if ($shisetsu_kbn==4) { // 防雪柵
      $this->load->model("CreateDaichouExcelBS");
      $model_excel = $this->CreateDaichouExcelBS;
    } else if ($shisetsu_kbn==5) { // スノーポール
      $this->load->model("CreateDaichouExcelYH");
      $model_excel = $this->CreateDaichouExcelYH;
    } else if ($shisetsu_kbn==6) { // 監視局
      $this->load->model("CreateDaichouExcelKA");
      $model_excel = $this->CreateDaichouExcelKA;
    } else if ($shisetsu_kbn==7) { // 受信局
      $this->load->model("CreateDaichouExcelKB");
      $model_excel = $this->CreateDaichouExcelKB;
    } else if ($shisetsu_kbn==8) { // 中継局
      $this->load->model("CreateDaichouExcelKC");
      $model_excel = $this->CreateDaichouExcelKC;
    } else if ($shisetsu_kbn==9) { // 観測局
      $this->load->model("CreateDaichouExcelKD");
      $model_excel = $this->CreateDaichouExcelKD;
    } else if ($shisetsu_kbn==10) { // カメラ
      $this->load->model("CreateDaichouExcelKI");
      $model_excel = $this->CreateDaichouExcelKI;
    } else if ($shisetsu_kbn==11) { // 情報板C型
      $this->load->model("CreateDaichouExcelJH");
      $model_excel = $this->CreateDaichouExcelJH;
    } else if ($shisetsu_kbn==12) { // 遮断機
      $this->load->model("CreateDaichouExcelSD");
      $model_excel = $this->CreateDaichouExcelSD;
    } else if ($shisetsu_kbn==13) { // ドット線
      $this->load->model("CreateDaichouExcelDT");
      $model_excel = $this->CreateDaichouExcelDT;
    } else if ($shisetsu_kbn==14) { // トンネル
      $this->load->model("CreateDaichouExcelTT");
      $model_excel = $this->CreateDaichouExcelTT;
    } else if ($shisetsu_kbn==15) { // 駐車公園
      $this->load->model("CreateDaichouExcelCK");
      $model_excel = $this->CreateDaichouExcelCK;
    } else if ($shisetsu_kbn==16) { // 緑化公園
      $this->load->model("CreateDaichouExcelSK");
      $model_excel = $this->CreateDaichouExcelSK;
    } else if ($shisetsu_kbn==17) { // 立体横断
      $this->load->model("CreateDaichouExcelBH");
      $model_excel = $this->CreateDaichouExcelBH;
    } else if ($shisetsu_kbn==18) { // 擁壁
      $this->load->model("CreateDaichouExcelDY");
      $model_excel = $this->CreateDaichouExcelDY;
    } else if ($shisetsu_kbn==19) { // 法面
      $this->load->model("CreateDaichouExcelDN");
      $model_excel = $this->CreateDaichouExcelDN;
    } else if ($shisetsu_kbn==20) { // 浸出装置
      $this->load->model("CreateDaichouExcelTS");
      $model_excel = $this->CreateDaichouExcelTS;
      $running_cost = $this->getExcelRunningCost($sno);
      $model_excel->setRunningCost($running_cost);
      $repair_cost = $this->getExcelRepairCost($sno);
      $model_excel->setRepairCost($repair_cost);
    } else if ($shisetsu_kbn==21) { // ロードヒーティング
      $this->load->model("CreateDaichouExcelRH");
      $model_excel = $this->CreateDaichouExcelRH;
    } else if ($shisetsu_kbn==24) { // 橋梁
      // TODO: Excel作成時に必要
    } else if ($shisetsu_kbn==25) { // トンネル
      // TODO: Excel作成時に必要
    } else if ($shisetsu_kbn==26) { // 切土
      // TODO: Excel作成時に必要
    } else if ($shisetsu_kbn==27) { // 歩道
      // TODO: Excel作成時に必要
    } else if ($shisetsu_kbn==28) { // 落石崩壊
      // TODO: Excel作成時に必要
    } else if ($shisetsu_kbn==29) { // 横断歩道橋
      // TODO: Excel作成時に必要
    } else if ($shisetsu_kbn==30) { // シェッド等
      // TODO: Excel作成時に必要
    } else if ($shisetsu_kbn==31) { // 大型カルバート
      // TODO: Excel作成時に必要
    } else if ($shisetsu_kbn==32) { // 岩盤崩壊
      // TODO: Excel作成時に必要
    } else if ($shisetsu_kbn==33) { // 急流河川
      // TODO: Excel作成時に必要
    } else if ($shisetsu_kbn==34) { // 盛土
      // TODO: Excel作成時に必要
    } else if ($shisetsu_kbn==35) { // 道路標識（門型）
      // TODO: Excel作成時に必要
    } else if ($shisetsu_kbn==36) { // 36	道路情報提供装置（門型）
      // TODO: Excel作成時に必要
    }

    // Excelを作成
    // 現状、新規追加施設はExcelを作成しない（$model_excelがnull）なので、nullでないときのみ作成を実行
    if (!is_null($model_excel)) {
      $model_excel->outputDaichouData($sno);
    }
  }

  // 付属物点検保存
  public function saveChkData($post){
    log_message('debug', __METHOD__);

    $this->load->model('CreateCheckDataExcel');

    $sno = $post['sno'];
    $chk_mng_no = $post['chk_mng_no']=-9;
    $struct_idx = isset($post['struct_idx']) ? $post['struct_idx'] : 0;
    $excel_ver = isset($post['excel_ver']) ? $post['excel_ver'] : CreateCheckDataExcel::OUTPUT_XLS;

    $this->CreateCheckDataExcel->save_check_data($sno, $chk_mng_no, $struct_idx, $excel_ver);
  }

  /**
   *
   * Excel用ランニングコストの取得
   *  snoにぶら下がるランニングコストを取得(最新の5件)
   *
   * 引数:sno
   * 戻り値:array
   */
  protected function getExcelRunningCost($sno){
    $this->load->model('FamEditModel');

    // 最新のランニングコストをExcelの列数分取得する
    $running_cost = array_slice($this->FamEditModel->getRunningCost($sno), -5, 5);

    for ($i=0;$i<5;$i++) {
      $running_cost["running_cost_id$i"]=isset($running_cost[$i]['running_cost_id'])? $running_cost[$i]['running_cost_id'] : '';
      $running_cost["nendo$i"]=isset($running_cost[$i]['nendo'])? $running_cost[$i]['nendo'] : '-';
      $running_cost["nendo_yyyy$i"]=isset($running_cost[$i]['nendo_yyyy'])? $running_cost[$i]['nendo_yyyy'] : '';
      $running_cost["yakuzai_nm$i"]=isset($running_cost[$i]['yakuzai_nm'])? $running_cost[$i]['yakuzai_nm'] : '';
      $running_cost["sanpu_ryou$i"]=isset($running_cost[$i]['sanpu_ryou'])? $running_cost[$i]['sanpu_ryou'] : '';
      $running_cost["sanpu_cost$i"]=isset($running_cost[$i]['sanpu_cost'])? $running_cost[$i]['sanpu_cost'] : '';
      $running_cost["denki_cost$i"]=isset($running_cost[$i]['denki_cost'])? $running_cost[$i]['denki_cost'] : '';
      $running_cost["keiyaku_denryoku$i"]=isset($running_cost[$i]['keiyaku_denryoku'])? $running_cost[$i]['keiyaku_denryoku'] : '';
      $running_cost["tekiyou$i"]=isset($running_cost[$i]['tekiyou'])? $running_cost[$i]['tekiyou'] : '';
      $running_cost["denryoku_ryou$i"]=isset($running_cost[$i]['denryoku_ryou'])? $running_cost[$i]['denryoku_ryou'] : '';

      // 薬剤単価(散布費/散布量)
      if (isset($running_cost[$i]['sanpu_ryou']) && isset($running_cost[$i]['sanpu_cost'])) {
        $running_cost["yakuzai_tanka$i"]=round($running_cost[$i]['sanpu_cost']/$running_cost[$i]['sanpu_ryou']*100)/100;
      } else {
        $running_cost["yakuzai_tanka$i"]='';
      }

      // ランニングコスト(散布費+電気代)
      if (isset($running_cost[$i]['sanpu_cost']) || isset($running_cost[$i]['denki_cost'])) {
        $s_cost=$running_cost[$i]['sanpu_cost']?(float)$running_cost[$i]['sanpu_cost']:0;
        $d_cost=$running_cost[$i]['denki_cost']?(float)$running_cost[$i]['denki_cost']:0;
        $running_cost["calc_running_cost$i"]=$s_cost+$d_cost;
      } else {
        $running_cost["calc_running_cost$i"]='';
      }

      // ㎡当たり面接コスト(ランニングコスト/効果範囲面積)
      if (isset($running_cost["calc_running_cost$i"]) && isset($running_cost[$i]['kouka_hani_menseki'])) {
        $running_cost["area_per_cost$i"]=round($running_cost["calc_running_cost$i"]/$running_cost[$i]['kouka_hani_menseki']*100)/100;
      } else {
      $running_cost["area_per_cost$i"]='';
      }
    }
    return $running_cost;
  }

  /**
   *
   * Excel用修理費の取得
   *  snoにぶら下がる修理費を取得(最新の5件)
   *
   * 引数:sno
   * 戻り値:array
   */
  protected function getExcelRepairCost($sno){
    $this->load->model('FamEditModel');
    // 最新の修理費をExcelの列数分取得する
    $repair_cost = array_slice($this->FamEditModel->getRepairCost($sno), -5, 5);
    for ($i=0;$i<5;$i++) {
      $repair_cost["s_repair_nendo$i"]=isset($repair_cost[$i]['nendo'])? $repair_cost[$i]['nendo'] : '-';
      $repair_cost["s_repair_cost$i"]=isset($repair_cost[$i]['repair_cost'])? $repair_cost[$i]['repair_cost'] : '';
      $repair_cost["s_repair_naiyou$i"]=isset($repair_cost[$i]['repair_naiyou'])? $repair_cost[$i]['repair_naiyou'] : '';
    }
    return $repair_cost;
  }

}
