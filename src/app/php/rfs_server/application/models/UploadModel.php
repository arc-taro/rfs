<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * アップロードのモデル
 * 一度tempディレクトリに保存するために使う。

 * @access public
 * @package Model
 */
class UploadModel extends CI_Model {
  protected $DB_rfs;

  /**
    * コンストラクタ
    *
    * model GetForm8
    */
  public function __construct() {
    parent::__construct();
    $this->DB_rfs = $this->load->database('rfs',TRUE);
    if ($this->DB_rfs->conn_id === FALSE) {
      log_message('debug', 'データベースに接続されていません');
      return;
    }
  }

  /**
     * 画像を本保存する。
     * @param $tmp_file_name 仮のファイル名（パス込）
     * @param $param クライアントからのパラメータ
     *        $param['flowFilename'] 本当のファイル名
     */
  public function saveFile($tmp_file_name,$param){
    $file_name =$param['flowFilename'];
    $extension = pathinfo($file_name, PATHINFO_EXTENSION);

    // 画像ファイルの移動
    $tmp_file_name_info = pathinfo($tmp_file_name);
    $path = "upload/temp/".$tmp_file_name_info['basename'].".".$extension;
    $server_path = $this->config->config['www_path'].$path;
    //「$directory_path」で指定されたディレクトリが存在するか確認
    if(!is_dir( pathinfo( $server_path )["dirname"])){
      //存在しないときの処理（「$directory_path」で指定されたディレクトリを作成する）
      mkdir(pathinfo( $server_path )["dirname"], 0755,true);
    }
    log_message("info","\n $file_name \n $server_path \n");
    rename( $tmp_file_name ,$server_path);
    // アップロードされたものが、画像だったら、リサイズとEXIFを取得する。
    if($extension=="jpg" || $extension=="JPG"){
      $exif = $this->getExif($server_path);
      //$this->resizeImage($server_path,$server_path);
    }else{
      $exif['lat']=0;
      $exif['lon']=0;
      $exif['date']="";
    }


    // DBへ登録
    $sql= <<<SQL
            insert into t_upload (
              file_name
              , path
              , upload_dt
              , lat
              , lon
              , exif_dt
            ) values(
              '$file_name'
              ,'$path'
              ,now()
              ,{$exif['lat']}
              ,{$exif['lon']}
              ,'{$exif['date']}'
            )
SQL;
    log_message("debug","sql = $sql \n");
    $query = $this->DB_rfs->query($sql);

    $result = array();
    $result["path"]=$path;
    $result["lon"]=$exif['lon'];
    $result["lat"]=$exif['lat'];
    $result["date"]=$exif['date'];


    return $result;
  }

  /**
     * オリジナル画像を別ファイルに保存してから、画像を圧縮する
     @param dst_file_name : ソースファイル　圧縮されて保存される
     @param src_file_name : オリジナル画像を保存するファイル名
     */
  public function resizeImage($dst_filename,$src_filename){

    require_once(APPPATH . 'third_party/pel/autoload.php');
    $input_jpeg = new \lsolesen\pel\PelJpeg($src_filename);
    /* PelJpeg objectの内部のイメージバイトでイメージリソースを生成 */
    $src_img = ImageCreateFromString($input_jpeg->getBytes());
    $width = ImagesX($src_img);
    $height = ImagesY($src_img);

    /* リサイズの計算 */
    $min_width = 1024; // 幅の最低サイズ
    $min_height = 1024; // 高さの最低サイズ

    if($width >= $min_width|$height >= $min_height){
      if($width == $height) {
        $new_width = $min_width;
        $new_height = $min_height;
      } else if($width > $height) {//横長の場合
        $new_width = $min_width;
        $new_height = $height*($min_width/$width);
      } else if($width < $height) {//縦長の場合
        $new_width = $width*($min_height/$height);
        $new_height = $min_height;
      }

      /* イメージのリサイズ */
      $dst_img = ImageCreateTrueColor($new_width, $new_height);
      ImageCopyResampled($dst_img, $src_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

      /* JPEG data から PelJpeg object の生成 */
      $output_jpeg = new \lsolesen\pel\PelJpeg($dst_img);

      /* 元のファイルからEXIFデータを取得 */
      $exif = $input_jpeg->getExif();

      /* EXIFデータが存在すれば、リサイズされるデータに設定 */
      if ($exif != null){
        $output_jpeg->setExif($exif);
      }
      /* リサイズ先ファイルに書き込む */
      file_put_contents($dst_filename, $output_jpeg->getBytes());
    }
  }

  /**
     * Exif情報の取得
     */
  public function getExif($file){

    // 日付の取得
    $exif=@exif_read_data($file);

    $date=false;//date("Y/m/d H:i:s", filemtime($file));
    $dat=array();
    $dat['date']="";

    if (isset($exif) && isset($exif['DateTimeOriginal']) ) {
      $date2=$exif['DateTimeOriginal'];
      $r1=explode(':',$date2);
      if( is_array($r1) ){
        if( count($r1) != 2){
          $r2=explode(' ',$date2);
          if( is_array($r2) && count($r2) == 2){
            $r2[0]=str_replace(':','/',$r2[0]);
            $date=implode(' ',$r2);
          }
        }else{
          $date=$date2;
        }
      }
    }
    if($date){
      $dat['date']=$date;
    }

    // 緯度経度の取得
    $dat['lat']=0;
    $dat['lon']=0;

    $exif=@exif_read_data($file,0,true);

    if (isset($exif['GPS']) && isset($exif['GPS']['GPSLatitude']) && isset($exif['GPS']['GPSLongitude'])) {
      $lat = $this->gpsDegree($exif['GPS']['GPSLatitude'], $exif['GPS']['GPSLatitudeRef']);
      $lng = $this->gpsDegree($exif['GPS']['GPSLongitude'], $exif['GPS']['GPSLongitudeRef']);

      if ($lat && $lng) {
        $dat['lat']=$lat;
        $dat['lon']=$lng;
      }
    }
    return $dat;
  }

  public function gpsDegree($gps, $ref) {
    if (count($gps) != 3) return false;
    if ($ref == 'S' or $ref == 'W') {
      $pm = -1;
    } else {
      $pm = 1;
    }
    $degree = $this->fraction2number($gps[0]);
    $minute = $this->fraction2number($gps[1]);
    $second = $this->fraction2number($gps[2]);
    return ($degree + $minute/60.0 + $second/3600.0) * $pm;
  }

  public function fraction2number($value) {
    try{
      $fraction = explode('/', $value);
      if (count($fraction) != 2) return 0;
      if ((double)$fraction[1] == 0) $fraction[1]=1;
      return (double)$fraction[0] / (double)$fraction[1];
    }catch(Exception $ex){
    }
    return 0;
  }


}
