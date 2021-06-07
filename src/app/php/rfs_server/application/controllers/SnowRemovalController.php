<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**

    コントローラー名：SnowRemovalController
    概要：除雪用CSV出力用のコントローラー

 * @property CI_Loader $load
 * @property CI_Input $input
 * @property CI_Zip $zip
**/
class SnowRemovalController extends BaseController {
/*
    protected static $csv_title=[4=>["shisetsu_cd"=>"id", "dogen_cd"=>"土現コード"
                                    , "syucchoujo_cd"=>"出張所コード", "rosen_cd"=>"路線コード"
                                    , "snow_removal_keishiki_cd"=>"形式コード"
                                    , "encho"=>"延長", "haishi_snow_removal"=>"廃止"
                                    , "haishinen_snow_removal"=>"廃止年"]
                                ,21=>["shisetsu_cd"=>"箇所id", "dogen_cd"=>"建設管理部コード"
                                    , "syahodou_cd"=>"車歩道コード", "syucchoujo_cd"=>"出張所コード"
                                    , "rosen_cd"=>"路線コード" , "shityouson"=>"市町村", "azaban"=>"字番"
                                    , "menseki_syadou"=>"車道面積", "menseki_hodou"=>"歩道面積"
                                    , "netsugen" => "熱源", "nendo" => "年度"
                                    , "haishi_snow_removal"=>"廃止"
                                    , "haishinen_snow_removal"=>"廃止年"]
                                ];
*/

    protected static $csv_title=[4=>["shisetsu_cd"=>"施設コード","rosen_cd"=>"路線コード",
                                    "dogen_cd"=>"建設管理部コード","syucchoujo_cd"=>"出張所コード",
                                    "haishi"=>"廃止年度（入力値）","haishi_yyyy"=>"廃止年度（西暦）",
                                    "encho"=>"延長","sakusyu_cd"=>"柵種","sakusyu"=>"柵種内容",
                                    "saku_kbn_cd"=>"柵区分","saku_kbn"=>"柵区分内容","saku_keishiki_cd"=>"柵形式",
                                    "saku_keishiki"=>"柵形式内容","snow_removal_keishiki_cd"=>"除雪用形式コード",
                                    "snow_removal_keishiki_nm"=>"除雪用形式内容",
                                    ]
                                ,21=>["shisetsu_cd"=>"施設コード","rosen_cd"=>"路線コード",
                                    "shityouson"=>"市町村","azaban"=>"字番",
                                    "dogen_cd"=>"建設管理部コード","syucchoujo_cd"=>"出張所コード",
                                    "haishi"=>"廃止年度（入力値）","haishi_yyyy"=>"廃止年度（西暦）",
                                    "keishiki_kubun_cd1"=>"熱源コード","keishiki_kubun1"=>"熱源内容",
                                    "keishiki_kubun_cd2"=>"動力コード","keishiki_kubun2"=>"動力内容",
                                    "menseki_syadou"=>"車道面積","menseki_hodou"=>"歩道面積",
                                    ]];

    protected static $shisetsu_kbn_ryaku=[4=>'BO', 21=>'RH'];

    const TMP_EXCEL_TMP_PATH = __DIR__ . '/../libraries/phpExcel/results/';

    public function __construct() {
        parent::__construct();
    }

    public function init() {
      
      // 所属コードと建管は渡さなきゃいけない
      $dogen_cd = $this->session['ath']['dogen_cd']; // 出張所コード
      $syozoku_cd = $this->session['ath']['syozoku_cd']; // 所属コード
      $tmp=array();
      $tmp['dogen_cd']=$dogen_cd;
      $tmp['syozoku_cd']=$syozoku_cd;
      // マスタの取得
      $this->load->model('SchCommon');
      $dogen_syucchoujo=$this->SchCommon->getDogenSyucchoujo($tmp);
      $result["dogen_syucchoujo"] = $dogen_syucchoujo;
      $this->json = json_encode($result);
      $this->output->set_content_type('application/json')->set_output($this->json);
    }

    public function CreateSnowRemovalCsv(){
      $post = $this->input->post();
      // データ取得
      $this->load->model('SnowRemovalModal');
      $csvdata=$this->SnowRemovalModal->getSnowRemovalData($post);
      $download_path=[];
      if (isset($csvdata['bs'])) {
        //log_message("debug",print_r($csvdata,true));
        $download_path[$csvdata['bs']['shisetsu_kbn']]=$this->createCsv($csvdata['bs']['shisetsu_kbn'],$csvdata['bs']['data']);
      }
      if (isset($csvdata['rh'])) {
        //log_message("debug",print_r($csvdata,true));
        $download_path[$csvdata['rh']['shisetsu_kbn']]=$this->createCsv($csvdata['rh']['shisetsu_kbn'],$csvdata['rh']['data']);
      }
      $this->csvDownload($download_path);
    }

    protected function createCsv($shisetsu_kbn, $data){
      // CSVファイルパスの設定
      $file_path = self::TMP_EXCEL_TMP_PATH;
      $file_nm = 'snow_removal_' . $shisetsu_kbn . "_" . date('YmdHis') . uniqid() . '.csv';
      $file_path_nm = $file_path . $file_nm;

      $file = fopen($file_path_nm, 'w');
      $title_arr=self::$csv_title[$shisetsu_kbn];
      $out_title_arr=[];
      foreach( $title_arr as $key => $val ){
        $out_title_arr[] = mb_convert_encoding($val, 'SJIS-WIN', 'UTF-8');
      }
      fputcsv($file, $out_title_arr);
      // リスト
      for ($i=0;$i<count($data);$i++) {
        $tmp=[];
        foreach( $title_arr as $key => $val ){
          array_push($tmp,mb_convert_encoding($data[$i][$key], 'SJIS-WIN', 'UTF-8'));
        }
        fputcsv($file, $tmp);
      }
      return $file_path_nm;
    }

    // 引数の配列にあるExcelをダウンロードする。
    // 一つの場合はファイルひとつをダウンロード
    // 複数の場合はZipダウンロード
    protected function csvDownload($path_arr) {

      // 施設区分情報を取得する（ファイル名に使用する）
      $this->load->model('SchCommon');
      $shisetsu_kbns=$this->SchCommon->getShisetsuKbns();

      // 一つかどうか
      if (count($path_arr)==1) {
        // ファイル一つをダウンロード
        foreach($path_arr as $key => $val){
          $shisetsu_kbn_nm = $this->getShisetsuKbnNm($key,$shisetsu_kbns);
          $download_nm=$shisetsu_kbn_nm."_".date('YmdHis').'.csv';
          $file_path=$val;
        }
      } else {
        // ZIP作成
        // ZIP用DIR
        $zip_path=self::TMP_EXCEL_TMP_PATH."snow_removal_".date('YmdHis').uniqid()."/csv_".date('YmdHis')."/";
        mkdir($zip_path, 0777, true);
        $download_nm="除雪システム用CSV_".date('YmdHis').".zip";
        foreach($path_arr as $key => $val){
          $shisetsu_kbn_nm = $this->getShisetsuKbnNm($key,$shisetsu_kbns);
          $csv_file_nm=$shisetsu_kbn_nm."_".date('YmdHis').'.csv';
          copy($val, $zip_path.$csv_file_nm);
          chmod($zip_path.$csv_file_nm, 0777);
        }
        // zip対象フォルダを圧縮
        // zip : zipファイル保存パス 対象フォルダ
        $command = "cd ".$zip_path."; "."zip -r ".$zip_path.$download_nm." .";
        exec($command);
        chmod($zip_path.$download_nm, 0777);
        $file_path=$zip_path.$download_nm;
      }
      try {
        // Excelのダウンロード
        header('Content-Type: application/octet-stream');
        //header('Content-Disposition: attachment; filename='.$download_nm);
        header('Content-Disposition: attachment; filename*=UTF-8\'\'' .rawurlencode($download_nm));
        readfile($file_path);
      } catch (RecordNotFoundException $e) {
        $this->error($e->getMessage());
      }
    }

    // 施設区分コードから施設区分名を取得
    protected function getShisetsuKbnNm($key,$shisetsu_kbns) {
      foreach ($shisetsu_kbns as $item) {
        if ($item['shisetsu_kbn']==$key) {
          return self::$shisetsu_kbn_ryaku[$key];
        }
      }
    }

}
