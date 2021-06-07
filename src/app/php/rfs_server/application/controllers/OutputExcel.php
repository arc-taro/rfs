<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**

    コントローラー名：OutputExcel
    概要：Excel出力のコントローラー

 * @property CI_Loader $load
 * @property CI_Input $input
 * @property CI_Zip $zip
 * @property CreateOutputExcel $CreateOutputExcel
 * @property CreateCheckDataExcel $CreateCheckDataExcel
**/
class OutputExcel extends BaseController {
    public function __construct() {
        parent::__construct();

        ini_set('display_errors', '0');
    }

    /**
     * 施設一覧出力
     */
    public function out_schList() {
        log_message('debug', __METHOD__);

        $this->load->model('CreateOutputExcel');

        $post = $this->input->post();

        $srch = json_decode($post['srch'], true);
        $post['srch'] = $srch;

        $this->CreateOutputExcel->out_schList($post);
    }

    /**
     * 道路施設一覧ダウンロード
     */
    public function dlOutShisetsuList() {
      log_message('debug', __METHOD__);

      $post = $this->input->post();
      $srch = json_decode($post['srch'], true);

      // Excel作成
      $this->load->model('CreateShisetsuListExcel');
      $this->CreateShisetsuListExcel->outputListData($srch);

    }

  /**
     * 道路施設一覧CSVダウンロード
     */
  public function dlOutShisetsuCsvList() {
    log_message('debug', __METHOD__);

    $post = $this->input->post();
    $srch = json_decode($post['srch'], true);

    // Excel作成
    $this->load->model('CreateShisetsuListExcel');
    $this->CreateShisetsuListExcel->outputListCsvData($srch);

  }

  /**
    * 道路附属物施設一覧CSVダウンロード
    */
  public function dlOutShisetsuCsvListHuzokubutsu() {
    log_message('debug', __METHOD__);

    $post = $this->input->post();
    $srch = json_decode($post['srch'], true);

    // Excel作成
    $this->load->model('CreateShisetsuListExcel');
    $this->CreateShisetsuListExcel->outputListCsvDataHuzokubutsu($srch);

  }

    /**
     * 点検票1件出力
     */
    public function out_chkData() {
        log_message('debug', __METHOD__);

        $this->load->model('CreateCheckDataExcel');

        $post = $this->input->post();

        $sno = $post['sno'];
        $chk_mng_no = isset($post['chk_mng_no']) ? $post['chk_mng_no'] : 0;
        $struct_idx = isset($post['struct_idx']) ? $post['struct_idx'] : 0;
        $excel_ver = isset($post['excel_ver']) ? $post['excel_ver'] : CreateCheckDataExcel::OUTPUT_XLS;

        $this->CreateCheckDataExcel->output_check_data($sno, $chk_mng_no, $struct_idx, $excel_ver);
    }

    /**
     * 点検票 Zip 出力
     */
    public function out_chkDataPack() {
        log_message('debug', __METHOD__);

        $this->load->model('CreateCheckDataExcel');

        $post = $this->input->post();

        $mode = $post['mode'];
        $checked_data = json_decode($post['checked_data'], true);
        $excel_ver = isset($post['excel_ver']) ? $post['excel_ver'] : CreateCheckDataExcel::OUTPUT_XLS;

        $this->CreateCheckDataExcel->output_check_data_pack($mode, $checked_data, $excel_ver);
    }

    /**
     * 点検票1件保存
     */
    public function save_chkData() {
        log_message('debug', __METHOD__);

        $this->load->model('CreateCheckDataExcel');

        $get = $this->get;

        $sno = $get['sno'];

        if(isset($get['shisetsuExit'])){
            if ($get['shisetsuExit']==1) {
                $chk_mng_no = $get['chk_mng_no']=-9;
            }else{
                $chk_mng_no = isset($get['chk_mng_no']) ? $get['chk_mng_no'] : 0;
            }
        } else {
            $chk_mng_no = isset($get['chk_mng_no']) ? $get['chk_mng_no'] : 0;
        }

        $struct_idx = isset($get['struct_idx']) ? $get['struct_idx'] : 0;
        $excel_ver = isset($get['excel_ver']) ? $get['excel_ver'] : CreateCheckDataExcel::OUTPUT_XLS;

        $this->CreateCheckDataExcel->save_check_data($sno, $chk_mng_no, $struct_idx, $excel_ver);


        // 施設台帳のExcel生成
        // Excelデータ取得
        $this->load->model('DownloadDaichouExcel');
        $daichou_xls_data = $this->DownloadDaichouExcel->getDaichouXlsData($sno);

        // Excelデータがある場合
        if (count($daichou_xls_data) > 0) {

            $this->load->model('SchCommon');
            $result = $this->SchCommon->getShisetsu($sno);
            $shisetsu_kbn = $result[0]['shisetsu_kbn'];

            // 施設台帳Excel作成
            $this->saveShisetsuDaichou($sno, $shisetsu_kbn);
        }


    }

    /**
     * 防雪柵親データの部材情報作成
     */
    public function create_bssk_buzai_data() {
        log_message('debug', __METHOD__);

        $this->load->model('CreateCheckDataExcel');

        $this->CreateCheckDataExcel->create_stock_data();
    }

    /**
     * 施設台帳保存
     */
  public function outDaichouData() {
    log_message('debug', __METHOD__);

    $post = $this->input->post();
    $sno = $post['sno'];
    $this->load->model('CreateDaichouExcel');

    $this->CreateDaichouExcel->outputDaichouData($sno);
  }

  /**
     * 施設台帳ダウンロード
     */
  public function dlDaichouData() {
    log_message('debug', __METHOD__);

    $post = $this->input->post();
    $sno = $post['sno'];
    $shisetsu_kbn = $post['shisetsu_kbn'];
    $this->load->model('DownloadDaichouExcel');

    // Excelデータ取得
    $daichou_xls_data = $this->DownloadDaichouExcel->getDaichouXlsData($sno);

    // データの有無
    if (count($daichou_xls_data)==0) {

      // 施設台帳Excel作成
      $this->saveShisetsuDaichou($sno, $shisetsu_kbn);

      // Excelデータ取得
      $daichou_xls_data = $this->DownloadDaichouExcel->getDaichouXlsData($sno);
    }

    $this->DownloadDaichouExcel->dlDaichouData($daichou_xls_data);
  }

  /**
     * 施設台帳 Zip 出力
     */
  public function outShisetsuDataPack() {
    log_message('debug', __METHOD__);

    $this->load->model('CreateShisetsuDataExcel');
    $this->load->model('CreateCheckDataExcel');

    $post = $this->input->post();
    $checked_data = json_decode($post['checked_data'], true);
    $excel_ver = isset($post['excel_ver']) ? $post['excel_ver'] : CreateCheckDataExcel::OUTPUT_XLS;
    $zip_data_array = array();
    foreach ($checked_data["data_array"] as $shisetsu) {
      $excel_info = $this->CreateShisetsuDataExcel->getExcelInfo($shisetsu, $excel_ver);
      if ($excel_info[0]['esno']) {
        array_push($zip_data_array, $excel_info[0]);
      }else{

        // 最大実行時間を設定
        ini_set("max_execution_time", 300);

        // 施設台帳Excel作成
        $this->saveShisetsuDaichou($shisetsu['sno'], $excel_info[0]['shisetsu_kbn']);
        $tmp_excel_info = $this->CreateShisetsuDataExcel->getExcelInfo($shisetsu, $excel_ver);
        array_push($zip_data_array, $tmp_excel_info[0]);
      }
    }
    // 施設台帳をzipファイルでダウンロードする
    $this->load->model("CreateShisetsuZip");
    $this->CreateShisetsuZip->outputShisetsuDataPack($zip_data_array);
  }



  // 施設台帳Excel生成
  public function saveShisetsuDaichou($sno, $shisetsu_kbn){
      log_message('debug', __METHOD__);

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
      $running_cost=$this->getExcelRunningCost($sno);
      $model_excel->setRunningCost($running_cost);
      $repair_cost = $this->getExcelRepairCost($sno);
      $model_excel->setRepairCost($repair_cost);
    } else if ($shisetsu_kbn==21) { // ロードヒーティング
      $this->load->model("CreateDaichouExcelRH");
      $model_excel = $this->CreateDaichouExcelRH;
    }
    // Excelを作成
    $model_excel->outputDaichouData($sno);
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
