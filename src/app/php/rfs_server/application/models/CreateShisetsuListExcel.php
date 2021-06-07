<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Daichouの修正がいまいち分からない。実機にもあがっていないようなので保留。
require_once __DIR__ . '/../libraries/phpExcel/MultisheetExcelWrapper.php';
//require_once __DIR__ . '/../libraries/phpExcel/DaichouMultisheetExcelWrapper.php';


/**
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property CI_Zip $zip
 * @property SchShisetsu $SchShisetsu
 * @property SchCheck $SchCheck
 */
class CreateShisetsuListExcel extends CI_Model {

  const TMP_EXCEL_PATH = __DIR__ . '/../libraries/phpExcel/results/';
  /**
    * @var MultisheetExcelWrapper
    */
  protected $xl;

  protected $excel_root;
  protected $file_path;
  protected $file_nm;
  protected $out_list = array();
  protected $template = "daichou/shisetsu_daichou_list.xls";

  public function __construct() {
    parent::__construct();

    $this->excel_root = $this->config->config['www_path'];
    $this->rfs = $this->load->database('rfs', true);

    $this->load->model('SchCheck');
  }

  // ==========================================
  //   呼び出し部
  // ==========================================

  /**
   * 台帳の Excel ブックを出力する
   *
   * @param array $srch
   */
  public function outputListData($srch) {
    log_message('debug', __METHOD__);

    try {
      // Excel作成
      $this->createSheet();
      // メインのシートを作る
      $this->editSheet($srch);
      // Excelパスを作成
      $path = $this->setExcelPath($srch);
      // Excelの保存
      $this->saveExcel($path);
      $this->downloadExcel($path);
    } catch (RecordNotFoundException $e) {
      $this->error($e->getMessage());
    }
  }

  /**
   * 台帳のCSVを出力する
   *
   * @param array $data
   */
  public function outputListCsvData($data) {
    log_message('debug', __METHOD__);

    try {
      // CSVファイルパスの設定
      $file_path = self::TMP_EXCEL_PATH;
      $file_nm = 'shisetsu_daichou_csv_list_' . date('Ymd') . uniqid() . '.csv';
      $file_path_nm = $file_path . $file_nm;

      // 201900208 灯柱番号を追加
      $other_title = "未使用項目";
      for($i = 0; $i < count($data); $i++) {
        if ($data[$i]['shisetsu_kbn']==3) {
          $other_title = "灯柱番号";
          break;
        }
      }
      $file = fopen($file_path_nm, 'w');

      // ヘッダー
      $export_csv_title = array('施設シリアル番号','施設区分','施設名','施設形式コード','施設形式名','名称','施設ID', $other_title, '路線番号','路線名','現況SP(自)','現況SP(至)','延長','幅員','所在地','供用区分コード','供用区分','設置年度','西暦設置年','廃止年度','西暦廃止年','横断区分コード','横断区分','上下区分コード','上下区分','一般交通量(台／24h)','一般交通量(台／12h)','普通車交通量(台／12h)','大型車交通量(台／12h)','代替路の有無コード','代替路の有無','緊急輸送道路コード','緊急輸送道路','自専道or一般道コード','自専道or一般道','占用物件','建設管理部コード','建設管理部名','出張所コード','出張所名','形式/区分コード1','形式/区分名1','形式/区分コード2','形式/区分名2','緯度','経度');
      foreach( $export_csv_title as $key => $val ){
        $export_csv_title_arr[] = mb_convert_encoding($val, 'SJIS', 'UTF-8');
      }
      fputcsv($file, $export_csv_title_arr);

      // syucchoujo_cd,rosen_cd,spをキーに昇順ソート
      foreach ($data as $key => $value) {
        $tmp_shisetsu_kbn_cd_arr[$key] = $value['shisetsu_kbn'];
        $tmp_syucchoujo_cd_arr[$key] = $value['syucchoujo_cd'];
        $tmp_rosen_cd_arr[$key] = $value['rosen_cd'];
        $tmp_sp_arr[$key] = $value['sp'];
      }
      array_multisort($tmp_shisetsu_kbn_cd_arr,
                      $tmp_syucchoujo_cd_arr,
                      $tmp_rosen_cd_arr, SORT_NUMERIC,
                      $tmp_sp_arr, SORT_NUMERIC, $data
                     );

                     log_message("debug",print_r($data,true));

      // リスト
      foreach( $data as $key => $val ){
        $export_arr = array();
        foreach( $val as $key2 => $val2){
          if ($key2 == 'chkexcel' || $key2 == '$$hashKey') {
            $val2='';
          }
          $export_arr[] = mb_convert_encoding($val2, 'SJIS', 'UTF-8');
        }
        fputcsv($file, $export_arr);
      }

      fclose($file);

      // 台帳CSVをダウンロードする
      $this->load->helper('download');
      force_download($file_path_nm, NULL);

    } catch (RecordNotFoundException $e) {
      $this->error($e->getMessage());
    }
  }

  /**
   * 道路附属物点検施設一覧のCSVを出力する
   *
   * @param array $data
   */
  public function outputListCsvDataHuzokubutsu($data) {
    log_message('debug', __METHOD__);

    try {
      // CSVファイルパスの設定
      $file_path = self::TMP_EXCEL_PATH;
      $file_nm = 'huzokubutsu_csv_list_' . date('Ymd') . uniqid() . '.csv';
      $file_path_nm = $file_path . $file_nm;

      $file = fopen($file_path_nm, 'w');

      $export_csv_title = array('施設名','形式','点検年度','管理番号','点検実施状況','健全性（点検時）','所見','健全性（措置後）','路線コード','路線名','測点（m）','横断区分','所在地','設置年度','点検実施年月日','調査実施年月日','再判定年月日','点検会社','点検員','調査会社','調査員','代替路の有無','緊急輸送道路','自専道 or 一般道','占用物件（名称）','道路幅員','建管','出張所');

      foreach( $export_csv_title as $key => $val ){
        $export_csv_title_arr[] = mb_convert_encoding($val, 'SJIS', 'UTF-8');
      }
      fputcsv($file, $export_csv_title_arr);



      // syucchoujo_cd,rosen_cd,spをキーに昇順ソート
      foreach ($data as $key => $value) {
        $tmp_shisetsu_kbn_cd_arr[$key] = $value['shisetsu_kbn'];
        $tmp_syucchoujo_cd_arr[$key] = $value['syucchoujo_cd'];
        $tmp_rosen_cd_arr[$key] = $value['rosen_cd'];
        $tmp_sp_arr[$key] = $value['sp'];
      }
      array_multisort($tmp_shisetsu_kbn_cd_arr,
                      $tmp_syucchoujo_cd_arr,
                      $tmp_rosen_cd_arr, SORT_NUMERIC,
                      $tmp_sp_arr, SORT_NUMERIC, $data
                     );

      // リスト
      foreach( $data as $key => $val ){
        $export_arr = array();
        $export_arr[]= mb_convert_encoding($val['shisetsu_kbn_nm'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['shisetsu_keishiki_nm'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['target_nendo'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['shisetsu_cd'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['phase_str_large'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['chk_shisetsu_judge_nm'], 'SJIS-win', 'UTF-8'); // ローマ数字に対応させるためSJIS-win
        $export_arr[]= mb_convert_encoding($val['syoken'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['measures_shisetsu_judge_nm'], 'SJIS-win', 'UTF-8'); // ローマ数字に対応させるためSJIS-win
        $export_arr[]= mb_convert_encoding($val['rosen_cd'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['rosen_nm'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['sp'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['lr_str'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['syozaichi'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['secchi'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['chk_dt'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['investigate_dt'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['re_measures_dt'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['chk_company'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['chk_person'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['investigate_company'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['investigate_person'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['substitute_road_str'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['emergency_road_str'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['motorway_str'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['senyou'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['fukuin'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['dogen_mei'], 'SJIS', 'UTF-8');
        $export_arr[]= mb_convert_encoding($val['syucchoujo_mei'], 'SJIS', 'UTF-8');
        fputcsv($file, $export_arr);
      }

      fclose($file);

      // 施設一覧CSVをダウンロードする
      $this->load->helper('download');
      force_download($file_path_nm, NULL);

    } catch (RecordNotFoundException $e) {
      $this->error($e->getMessage());
    }
  }

  // ==========================================
  //   編集部
  // ==========================================

  /**
   * シートの作成
   *
   */
  protected function createSheet(){
    $this->xl = new MultisheetExcelWrapper($this->template);
    //$this->xl = new DaichouMultisheetExcelWrapper($this->template);

  }

  /**
   * シートの編集
   *
   * @param string $sheetname
   * @param array $params
   */
  protected function editSheet(array $params) {
    log_message('debug', __METHOD__);
    $this->out_list = $params;
    $max = 89;
    // シート枚数
    $sheet_cnt= ceil(count($this->out_list)/$max);
    log_message('debug', "シート枚数->$sheet_cnt");
    // 201900208 灯柱番号を追加
    $other_title = "未使用項目";
    for($i = 0; $i < count($params); $i++) {
      if ($params[$i]['shisetsu_kbn']==3) {
        $other_title = "灯柱番号";
        break;
      }
    }
    // シート枚数分ループ
    for ($s=1; $s<=$sheet_cnt; $s++) {
      $sheetname = "sheet".$s;
      log_message('debug', "タイトル->$other_title");
      // Excel出力配列
      $arr = array();
      // 201900208 灯柱番号を追加
      $arr['other_title'] = $other_title;
      //$arr['row_cnt'] = count($this->out_list);
      $arr['created'] = date('n月j日');
      // 件数
      if ($s==$sheet_cnt) {
        // 最後は端数の件数
        $line_cnt= count($this->out_list)-$max*($s-1);
      }else{
        // 途中の場合は最大行数になる
        $line_cnt=$max;
      }
      $this->xl->setTemplateSheet('list');
      // リスト行データ
      for($i = 0; $i < $line_cnt; $i++) {
        $arr['koutsuuryou_halfday_row'.$i]='';
          foreach ($params[$max*($s-1)+$i] as $k => $v) {
            $arr[$k.'_row'.$i] = $v;
            // koutsuuryou_12_row(数字の前後に'_')の場合のみExcel出力に失敗するため
            if(isset($arr['koutsuuryou_12_row'.$i])){
              $arr['koutsuuryou_halfday_row'.$i]=$arr['koutsuuryou_12_row'.$i];
            }
            /** secchi,haishiの値にしようよ。CSVと値変わっちゃってるし。 */
            // secchi_yyyy_row(西暦)を和暦に変換
            // if(isset($arr['secchi_yyyy_row'.$i])){
            //   $arr['secchi_row'.$i]=$this->getWareki($arr['secchi_yyyy_row'.$i]);
            // }
            // if(isset($arr['haishi_yyyy_row'.$i])){
            //   $arr['haishi_row'.$i]=$this->getWareki($arr['haishi_yyyy_row'.$i]);
            // }
          }
          $arr['no_row'.$i] = $i+1;
      }

      $arr['row_cnt'] = $line_cnt;
      $this->xl->renderSheet($arr, '検索結果リスト'.$s);
    }    // シートループ
  }

  // Excelファイルパス、ファイル名を定義
  protected function setExcelPath($srch) {
    // Excelファイルパスの設定
    // （ここではパスの設定のみ、保存はExcelWrapper内で行う）
    //$this->file_path = 'api/application/libraries/phpExcel/results/';
    $this->file_path =self::TMP_EXCEL_PATH;
    $this->file_nm = 'shisetsu_daichou_list_' . date('Ymd') . uniqid() . '.xls';
    $file_path_nm = $this->file_path . $this->file_nm;
    //「$file_path_nm」で指定されたディレクトリが存在するか確認
    if(!is_dir(pathinfo($file_path_nm)["dirname"])) {
      mkdir(pathinfo($file_path_nm)["dirname"], 0755, true);
    }
    return $file_path_nm;
  }

  protected function saveExcel($path) {
    //dirname();
    //pathinfo();
    $this->xl->saveResult(dirname($path)."/",pathinfo($path)['basename']);
  }

  protected function downloadExcel($path) {
    $this->xl->downloadResult(dirname($path)."/",pathinfo($path)['basename']);
  }

  // 西暦を和暦に変換
  protected function getWareki($year) {
    if($year == 1989) { // 平成元年
      return $year_name . $year . 'H元年';
    } else if($year > 1989) { // 平成
      $year_name = "H";
      $year -= 1988;
      return $year_name . $year . '年';
    } else if($year >= 1925) { // 昭和
      $year_name = "S";
      $year -= 1925;
      return $year_name . $year . '年';
    } else {
      return '';
    }
  }

}
