<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../libraries/phpExcel/DaichouMultisheetExcelWrapper.php';

/**
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property CI_Zip $zip
 * @property SchShisetsu $SchShisetsu
 * @property SchCheck $SchCheck
 */
class CreateDaichouExcel extends CI_Model {


  const DIR_EXCEL = 'excels';
  const MAP_PATH = __DIR__ . '/../libraries/phpExcel/results/';
  const ICON_PATH = "images/icon/shisetsu_mng/";
  const DIR_BLANK = 'blank';
  const MAPSIZE_X = 500;
  const MAPSIZE_Y = 240;

  protected static $daichou_base_info_arr = [
    'emergency_road_str'=>'',
    'romen_syu_str'=>'',
    'tenkenkou_str'=>'',
    'kanshi_camera_str'=>'',
    'bousuiban_str'=>'',
    'musen_kyoka_dt'=>'',
  ];

  protected $excel_info = array();

  protected $shisetsu_kbn = ['', 'dh', 'jd', 'ss', 'bs', 'yh', 'ka', 'kb', 'kc', 'kd', 'ki', 'jh', 'sd', 'dt', 'tt', 'ck', 'sk', 'bh', 'dy', 'dn', 'ts', 'rh'];

  /**
    * @var DaichouMultisheetExcelWrapper
    */
  protected $xl;

  protected $included_sheets = ['施設台帳様式その1', '施設台帳様式その2','施設台帳様式その3'];
  protected $excel_root;
  protected $file_path;
  protected $file_nm;
  protected $shisetsu_kbn_id;


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
   * @param integer $sno
   */
  public function outputDaichouData($sno) {
    log_message('debug', __METHOD__);
    try {

      // Excel出力の共通処理
      $this->editDaichouData($sno);

      // Excelのダウンロード
      //$this->xl->downloadResult($this->excel_root.$this->file_path, $this->file_nm, DaichouMultisheetExcelWrapper::FORMAT_XLS);

      // Excelの保存
      $this->xl->saveResult($this->excel_root.$this->file_path, $this->file_nm, DaichouMultisheetExcelWrapper::FORMAT_XLS);

      // トランザクション
      $this->rfs->trans_start();
      // Excel管理情報の更新
      $this->addExcelMng();
      // トランザクション確定
      $this->rfs->trans_complete();

    } catch (RecordNotFoundException $e) {
      $this->error($e->getMessage());
    }
  }

  // Excelファイルパス、ファイル名を定義
  protected function setExcelPath($base_info) {

    // Excelファイルパスの設定
    // （ここではパスの設定のみ、保存はExcelWrapper内で行う）
    $this->file_path = self::DIR_EXCEL .'/daichou/' .
      $base_info['syucchoujo_cd'] . '/' .
      $base_info['sno'] . '/';

    $this->file_nm = $base_info['shisetsu_cd']."_".date('Ymd') . '.xls';
    $file_path_nm = $this->excel_root . $this->file_path . $this->file_nm;

    //「$file_path_nm」で指定されたディレクトリが存在するか確認
    if(!is_dir(pathinfo($file_path_nm)["dirname"])) {
      mkdir(pathinfo($file_path_nm)["dirname"], 0755, true);
    }
    // Excel管理テーブル検索パラメータ
    $this->excel_info['sno'] = $base_info['sno'];
    $this->excel_info['syucchoujo_cd'] = $base_info['syucchoujo_cd'];
  }

  // 10進を60進に変換
  protected function chgCoordFrom10($coord10) {
    if (!$coord10) {
      return $coord10;
    }
    $deg=(int)$coord10;
    $min=(int)(($coord10 - $deg) * 60);
    $sec=(int)(((($coord10 - $deg) * 60 - $min) * 60 * 1000000000) / 1000000000);

    return $deg . ":" . $min . ":" . $sec;
  }

  protected function setMap($base_info, &$daichou_info){
    if($base_info['lat'] && $base_info['lon']){
      $map_info = $this->getMapInfo($base_info);  // 地図情報を取得する
      $daichou_info['map'] = $map_info;
    } else {
      $daichou_info['map'] = '';
    }
  }

  // 緯度経度の変換
  protected function chgCoord(&$base_info) {
    $lat_d="";
    $lat_m="";
    $lat_s="";
    $lon_d="";
    $lon_m="";
    $lon_s="";
    if ($base_info['lat']) {
      $lat=$this->chgCoordFrom10($base_info['lat']);
      $lat_arr=explode(":", $lat);
      $lat_d=$lat_arr[0];
      $lat_m=$lat_arr[1];
      $lat_s=$lat_arr[2];
    }
    if ($base_info['lon']) {
      $lon=$this->chgCoordFrom10($base_info['lon']);
      $lon_arr=explode(":", $lon);
      $lon_d=$lon_arr[0];
      $lon_m=$lon_arr[1];
      $lon_s=$lon_arr[2];
    }

    $base_info['lat_d']=$lat_d;
    $base_info['lat_m']=$lat_m;
    $base_info['lat_s']=$lat_s;
    $base_info['lon_d']=$lon_d;
    $base_info['lon_m']=$lon_m;
    $base_info['lon_s']=$lon_s;
  }

  // ==========================================
  //   データ取得部
  // ==========================================
  /**
    * 施設の検索
    *   引数のsnoから施設基本情報を取得する。
    *
    * @param  sno
    * @return array 施設情報
  */
  protected function getShisetsuInfo($sno){
    $sql= <<<EOF
SELECT
    s.sno
    ,s.shisetsu_cd shisetsu_cd
    ,s.shisetsu_ver
    ,s.shisetsu_kbn
    ,k.shisetsu_kbn_nm
    ,s.shisetsu_keishiki_cd
    ,s.rosen_cd
    ,ro.rosen_nm
    ,shityouson
    ,azaban
    ,s.lat
    ,s.lon
    ,s.dogen_cd
    ,s.syucchoujo_cd
    ,substitute_road
    , CASE
      WHEN substitute_road = 0 THEN '有'
      ELSE '無' END substitute_road_str
    , emergency_road
    , CASE WHEN emergency_road = 1 THEN '第1次'
      WHEN emergency_road = 2 THEN '第2次'
      WHEN emergency_road = 3 THEN '第3次'
      ELSE '-' END emergency_road_str
    ,motorway
    , CASE
      WHEN motorway = 1 THEN '一般道'
      ELSE '自専道' END motorway_str
    ,senyou
    ,secchi
    ,haishi haishi
    ,fukuin
    ,sp
    ,kp
    ,lr
    , CASE WHEN lr = 0 THEN 'L'
      WHEN lr = 1 THEN 'R'
      WHEN lr = 2 THEN 'C'
      WHEN lr = 3 THEN 'LR'
      ELSE '-' END lr_str
    ,secchi_yyyy
    ,haishi_yyyy
    ,shisetsu_cd_daichou
    ,kyouyou_kbn
    , CASE WHEN kyouyou_kbn = 0 THEN '休止'
      WHEN kyouyou_kbn = 1 THEN '供用'
      ELSE '-' END kyouyou_kbn_str
    ,sp_to
    ,ud
    , CASE WHEN ud = 0 THEN '上'
      WHEN ud = 1 THEN '下'
      WHEN ud = 2 THEN '上下'
      ELSE '-' END ud_str
    ,koutsuuryou_day
    ,koutsuuryou_oogata
    ,koutsuuryou_hutuu
    ,koutsuuryou_12
    ,name
    ,s.keishiki_kubun_cd1
    ,s.keishiki_kubun_cd2
    ,encho
    ,seiri_no
    ,k.daityou_tbl
    ,d.dogen_mei
    ,syu.syucchoujo_mei
    ,kk1.syubetsu_title syubetsu_title1
    ,kk1.keishiki_kubun keishiki_kubun1
    ,kk2.syubetsu_title syubetsu_title2
    ,kk2.keishiki_kubun keishiki_kubun2
    ,'$this->excel_root' || zen1.path zenkei_picture_0
    ,'$this->excel_root' || zen2.path zenkei_picture_1
    ,zen1.exif_dt exif_dt1
    ,zen2.exif_dt exif_dt2
    ,zen1.description description1
    ,zen2.description description2
    ,'$this->excel_root' || zu.path zumen
    ,shisetsu_keishiki_nm
  FROM
    rfs_m_shisetsu s
  JOIN
    rfs_m_shisetsu_kbn k
  ON
    s.shisetsu_kbn = k.shisetsu_kbn
  LEFT JOIN
    rfs_m_dogen d
  ON
    s.dogen_cd = d.dogen_cd
  LEFT JOIN
    rfs_m_syucchoujo syu
  ON
    s.syucchoujo_cd = syu.syucchoujo_cd
  LEFT JOIN
    (SELECT * FROM rfs_m_keishiki_kubun WHERE syubetsu=1) kk1
  ON
    s.shisetsu_kbn = kk1.shisetsu_kbn
    AND
    s.keishiki_kubun_cd1 = kk1.keishiki_kubun_cd
  LEFT JOIN
    (SELECT * FROM rfs_m_keishiki_kubun WHERE syubetsu=2) kk2
  ON
    s.shisetsu_kbn = kk2.shisetsu_kbn
    AND
    s.keishiki_kubun_cd2 = kk2.keishiki_kubun_cd
  LEFT JOIN
    rfs_m_rosen ro
  ON
    s.rosen_cd = ro.rosen_cd
  LEFT JOIN
    (SELECT * FROM rfs_t_zenkei_picture WHERE sno = $sno AND use_flg = 1 ORDER BY zenkei_picture_cd DESC LIMIT 1) zen1
  ON
    s.sno = zen1.sno
  LEFT JOIN
    (SELECT * FROM rfs_t_zenkei_picture WHERE sno = $sno AND use_flg = 2 ORDER BY zenkei_picture_cd DESC LIMIT 1) zen2
  ON
    s.sno = zen2.sno
  LEFT JOIN
    (SELECT * FROM rfs_t_zumen WHERE sno = $sno ORDER BY zumen_id DESC LIMIT 1) zu
  ON
    s.sno = zu.sno
  LEFT JOIN
    rfs_m_shisetsu_keishiki sk
  ON
    s.shisetsu_keishiki_cd = sk.shisetsu_keishiki_cd AND s.shisetsu_kbn = sk.shisetsu_kbn
  WHERE
    s.sno = $sno
EOF;

    $query = $this->rfs->query($sql);
    $result = $query->result('array');

    if (!$result[0]['syubetsu_title1']) {
      $result[0]['syubetsu_title1'] = '-';
    }
    if (!$result[0]['syubetsu_title2']) {
      $result[0]['syubetsu_title2'] = '-';
    }

    return $result;
  }

  // ==========================================
  //   データ取得部
  // ==========================================
  /**
    * 施設区分IDの取得
    *   引数のbase_infoから施設区分IDを取得する。
    *
    * @param  array   base_info
    * @return String  施設区分ID
  */
  protected function getShisetsuKbnId($base_info){
    $this->shisetsu_kbn_id = $this->shisetsu_kbn[$base_info['shisetsu_kbn']];
  }

  /**
    *
    * 引数のsnoから附属物点検履歴を取得
    *
    * @param integer sno
    * @return array 附属物点検履歴
  */
  protected function getHuzokubutsuRireki($base_info){
    $this->load->model('FamEditModel');
    $huzokubutsu_rireki = $this->FamEditModel->getHuzokubutsu($base_info['sno'],$base_info['shisetsu_kbn']);
    $huzokubutsu_rireki_info=array();

    for($i = 0; $i < 6; $i++){
      $huzokubutsu_rireki_info["chk_dt$i"]="";
      $huzokubutsu_rireki_info["check_shisetsu_judge_nm$i"]="";
      $huzokubutsu_rireki_info["buzai_nm$i"]="";
      $huzokubutsu_rireki_info["syoken$i"]="";
      $huzokubutsu_rireki_info["sochi_dt$i"]="";
      $huzokubutsu_rireki_info["measures_shisetsu_judge_nm$i"]="";
    }
    if($huzokubutsu_rireki){
      for($i = 0; $i < count($huzokubutsu_rireki); $i++){
        $huzokubutsu_rireki_info["chk_dt$i"]=$huzokubutsu_rireki[$i]['chk_dt'];
        $huzokubutsu_rireki_info["check_shisetsu_judge_nm$i"]=$huzokubutsu_rireki[$i]['check_shisetsu_judge_nm'];
        $huzokubutsu_rireki_info["buzai_nm$i"]=$huzokubutsu_rireki[$i]['buzai_nm'];
        $huzokubutsu_rireki_info["syoken$i"]=$huzokubutsu_rireki[$i]['syoken'];
        $huzokubutsu_rireki_info["sochi_dt$i"]=$huzokubutsu_rireki[$i]['sochi_dt'];
        $huzokubutsu_rireki_info["measures_shisetsu_judge_nm$i"]=$huzokubutsu_rireki[$i]['measures_shisetsu_judge_nm'];
      }
    }
    return $huzokubutsu_rireki_info;
  }

  /**
    *
    * 引数のsnoから補修履歴を生成
    *
    * @param integer sno
    * @return array 補修履歴
  */
  protected function getHosyuuRireki($sno){
    // その他点検データを取得する
    $hosyuu_rireki = $this->getHosyuuRirekiInfo($sno);
    $hosyuu_rireki_info=array();
    for($i = 0; $i < 6; $i++){
      $hosyuu_rireki_info["check_nendo$i"]="";
      $hosyuu_rireki_info["check_naiyou$i"]="";
      $hosyuu_rireki_info["repair_nendo$i"]="";
      $hosyuu_rireki_info["repair_naiyou$i"]="";
    }
    if($hosyuu_rireki){
      for($i = 0; $i < count($hosyuu_rireki); $i++){
        $hosyuu_rireki_info["check_nendo$i"]=$hosyuu_rireki[$i]['check_nendo'];
        $hosyuu_rireki_info["check_naiyou$i"]=$hosyuu_rireki[$i]['check_naiyou'];
        $hosyuu_rireki_info["repair_nendo$i"]=$hosyuu_rireki[$i]['repair_nendo'];
        $hosyuu_rireki_info["repair_naiyou$i"]=$hosyuu_rireki[$i]['repair_naiyou'];
      }
    }
    return $hosyuu_rireki_info;
  }

  /**
    *
    * 引数のsnoから点検データを取得する。
    *
    * @param integer sno
    * @return array 点検データ
  */
  protected function getHosyuuRirekiInfo($sno){
    log_message('debug', 'getHosyuuRirekiInfo');

    $sql= <<<EOF
SELECT *
FROM (
SELECT
  *
FROM
  rfs_t_chk_hosyuu_rireki
WHERE
  sno = $sno
ORDER BY
  hosyuu_rireki_id DESC
LIMIT
  6
) ri
WHERE
  ri.sno = $sno
ORDER BY
  hosyuu_rireki_id ASC
EOF;

    $query = $this->rfs->query($sql);
    $result = $query->result('array');

/*    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");*/
//}
    return $result;

  }

  /**
    * 地図情報の取得
    *   引数のbase_infoから地図情報を取得する。
    *
    * @param  Array   base_info
    * @return String  地図情報保存ファイルパス名
  */
  protected function getMapInfo($base_info){
    $this->file_path = self::MAP_PATH;
    $this->file_nm = date('YmdHis') . '_temp_shisetsu_map_' . uniqid() . '.gif';
    $file_path_nm = $this->file_path . $this->file_nm;

    // 画像の保存
    $this->makeMapImage($base_info,$file_path_nm);

    return $file_path_nm;
  }

  // ==========================================
  //   編集部
  // ==========================================

  /**
   * シートの作成
   *
   */
  protected function createSheet(){
    $template = "daichou/daichou_" . $this->shisetsu_kbn_id . ".xls";
    $this->xl = new DaichouMultisheetExcelWrapper($template);
  }

  /**
   * シートの編集
   *
   * @param string $sheetname
   * @param array $params
   */
  protected function editSheet(string $sheetname, array $params) {
    log_message('debug', __METHOD__);

    $this->xl->setTemplateSheet($sheetname);
    $this->xl->renderSheet($params, $sheetname);
  }

  /**
   * シートの編集（シートコピー用）
   *
   * @param string|int $src_sheet テンプレートのシート名
   * @param string|int $dest_sheet 出力先シート名
   * @param array $params
   */
  protected function editSheetForCopy(string $src_sheet, string $dest_sheet, array $params) {
    log_message('debug', __METHOD__);
    $this->xl->setTemplateSheet($src_sheet);
    $this->xl->renderSheet($params, $dest_sheet);
  }

  /**
   * 地図上アイコンを作成し、保存する。
   * @param unknown $pos
   * @param unknown $filename
   */
  public function makeMapImage($pos,$filename){
    log_message('debug', __METHOD__);
    $img = $this->makeMap((float)$pos['lon'],(float)$pos['lat'],self::MAPSIZE_X,self::MAPSIZE_Y, 16);

    // 色作成
    $red = imagecolorallocate($img, 0xff, 0, 0);

    // 円描画
    $url = $this->excel_root . self::ICON_PATH . $this->shisetsu_kbn_id . '_1.gif';
    $icon =  imagecreatefromgif($url);
    // アイコン元画像をリサイズ
    $canvas=imagecreatetruecolor(22,22);
    imagecopyresampled($canvas,$icon,0,0,0,0,22,22,88,88);
    imagecopy($img,$canvas,self::MAPSIZE_X/2-11,self::MAPSIZE_Y/2-11,0,0,22,22);

    // 再保存
    //imagepng($img2,$image_file);
    imagegif($img,$filename);
  }


  /**
   * 国土地理院から指定された座標の近辺のタイルを取得し、画像を生成する。
   * @param unknown $lon 緯度経度
   * @param unknown $lat
   * @param unknown $out_width　出力画像サイズ
   * @param unknown $out_height
   * @param int $z 国土地理院のズームレベル
   */
  public function makeMap($lon,$lat,$out_width,$out_height,$z=11){
    // 緯度経度から、国土地理院のタイルを取得するプログラム

    // タイルの取得数
    // tile_width : ｘ座標方向のタイルの枚数
    // tile_height : ｘ座標方向のタイルの枚数
    $tile_width = ceil($out_width / 256) *2 +1;
    $tile_height = ceil($out_height / 256) *2 +1;

    // 中央のタイル番号
    $x = ( int ) (($lon / 180.0 + 1) * pow ( 2, $z ) / 2);
    $y = ( int ) ((- log ( tan ( (45.0 + $lat / 2) * M_PI / 180.0 ) ) + M_PI) * pow ( 2, $z ) / (2 * M_PI));

    // 全タイルが収まる画像を生成
    $image = imagecreatetruecolor ( 256 * $tile_width, 256 * $tile_width );

    // タイルの取得
    for($x1 = 0; $x1 < $tile_width; $x1 ++) {
        for($y1 = 0; $y1 < $tile_height; $y1 ++) {
            $x2 = $x +$x1 -($tile_width-1)/2;
            $y2 = $y +$y1 -($tile_height-1)/2;
            $url = "http://cyberjapandata.gsi.go.jp/xyz/std/{$z}/{$x2}/{$y2}.png";
            //echo "$url \n";
            //        $data = file_get_contents ( $url );
            $src = imagecreatefrompng( $url );
            imagecopy ($image ,$src , $x1*256 , $y1*256 , 0 , 0, 256, 256);
            imagedestroy($src);
        }
    }

    // 中央のタイルの上下の緯度を計算
    $lon1 = ($x/pow(2,$z))*360-180;
    $lon2 = (($x+1)/pow(2,$z))*360-180;

    // 中央のタイルの左右の経度を計算
    $lat1 =2.0 * atan(exp(-(($y / pow(2,$z)) * 2 * M_PI- M_PI))) * 180.0 / M_PI - 90.0;
    $lat2 =2.0 * atan(exp(-((($y+1) / pow(2,$z)) * 2 * M_PI- M_PI))) * 180.0 / M_PI - 90.0;

    // 出力画像の生成
    $image2 = imagecreatetruecolor ( $out_width, $out_height );
    $src_x = ($lon - $lon1) / ($lon2 - $lon1) * 256+($tile_width-1)/2*256-$out_width/2;
    $src_y = ($lat - $lat1) / ($lat2 - $lat1) * 256+($tile_height-1)/2*256-$out_height/2;
    //echo "$src_x,$src_y\n";
    imagecopy ($image2 ,$image , 0 , 0 , $src_x , $src_y, $out_width, $out_height);

    imagedestroy($image);

    return $image2;

  }

  // Excel管理テーブルの対象レコードを更新
  protected function addExcelMng() {

    $sno = $this->excel_info['sno'];
    $sql = <<<EOF
        SELECT
          count(sno)
        FROM
          rfs_t_daichou_excel
        WHERE
          sno = $sno
EOF;

    $query = $this->rfs->query($sql);
    $result = $query->row_array();

/*
$r = print_r($result, true);
log_message('debug', "sql=$sql");
log_message('debug', "result=$r");
*/

    $create_dt = date('Y-m-d H:i:s');

    $full_path=$this->file_path.$this->file_nm;
    // レコードがある場合
    if($result['count'] != 0) {
      $sql = <<<EOF
            UPDATE rfs_t_daichou_excel tde
            SET
              file_path = '$full_path'
              , update_dt = '$create_dt'
            WHERE
              sno = $sno
EOF;
    } else {
      $sql = <<<EOF
            INSERT INTO rfs_t_daichou_excel
              (
                sno
                , file_path
                , create_dt
              )
            VALUES
              (
                $sno
                , '$full_path'
                , '$create_dt'
              )
EOF;
    }
    $query = $this->rfs->query($sql);
  }
}
