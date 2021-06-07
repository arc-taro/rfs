<?php
/*******************************************************/
/*** インポートデータには全景写真データが無いため、         ***/
/*** インポートした施設に全景写真、図面が無いかを検索し、    ***/
/*** ある場合は、写真を全景写真テーブルに追加する。         ***/
/*** さらに、図面がある場合は図面データに追加する。         ***/
/*** また、基本情報に座標が無い場合は写真から取得し更新する。 ***/
/***                                                 ***/
/*** コマンド:                                        ***/
/***    cd /home/app/rfs/cli                         ***/
/***    php ImportPicturePath.php 1(from) 2(to)      ***/
/***                                                 ***/
/*******************************************************/

require_once 'db.php';
$url="/home/www/html/rfs_mockup/";
main($argc,$argv);
exit(0);

// メイン関数
// 引数:snoの範囲
function main($argc,$argv) {
  if ($argc!=3){
    echo "引数はsnoのFrom、Toです\n";
    return;
  }

  // rfsコネクション
  $db = new DB();
  $db->init();
  $from=$argv[1];
  $to=$argv[2];

  // 防雪柵の場合は子データがあるため別SQLとする
  $shisetsu_arr=getShisetsu($db,$from,$to);  // 施設データ取得

  $db->beginTran();

  for ($i=0;$i<count($shisetsu_arr);$i++){
    /******************************/
    /*** 全景写真と基本情報緯度経度 ***/
    /******************************/
    $pic_exist=searchPicture($shisetsu_arr[$i]);  // 写真検索
    // 無い場合はその時点で終了
    if ($pic_exist=="false") {
      echo "写真がありませんでした".$shisetsu_arr[$i]['shisetsu_cd']."\n";
      continue;
    }
    $cooords_upd=false;
    // 基本情報の座標更新有無
    if (!$shisetsu_arr[$i]['lat'] || !$shisetsu_arr[$i]['lon']) {
      // 緯度経度どちらか入っていない
      $cooords_upd=true;
    } else if ($shisetsu_arr[$i]['lat']==0 || $shisetsu_arr[$i]['lon']==0) {
      // 緯度経度どちらか0
      $cooords_upd=true;
    }
    // 全景写真処理
    $ret=execZenkei($db, $shisetsu_arr[$i]);
    if ($ret==1) {
      // INSERTで失敗
      return;
    }
    // 座標更新判定
    if ($cooords_upd==true && $shisetsu_arr[$i]['extension']=='jpg') {
      $ret=updShisetsu($db, $shisetsu_arr[$i]);
    }
    if ($ret==1) {
      // UPDATEで失敗
      return;
    }

    /***********/
    /*** 図面 ***/
    /***********/
    // 図面検索
    $zumen_exist=searchZumen($shisetsu_arr[$i]);  // 図面検索
    if ($zumen_exist=="false") {
      echo "図面がありませんでした".$shisetsu_arr[$i]['shisetsu_cd']."\n";
      continue;
    }

    // 基本情報にも写真にも無い場合は、図面にあれば基本情報の座標にセット
    $cooords_upd=false;
    // 基本情報/全景写真の座標更新有無
    if (!$shisetsu_arr[$i]['lat'] || !$shisetsu_arr[$i]['lon']) {
      // 緯度経度どちらか入っていない
      $cooords_upd=true;
    } else if ($shisetsu_arr[$i]['lat']==0 || $shisetsu_arr[$i]['lon']==0) {
      // 緯度経度どちらか0
      $cooords_upd=true;
    }

    // 図面処理
    $ret=execZumen($db, $shisetsu_arr[$i]);
    if ($ret==1) {
      // INSERTで失敗
      return;
    }

    // 座標更新判定
    if ($cooords_upd==true && $shisetsu_arr[$i]['zumen_extension']=='jpg') {
      $ret=updShisetsu($db, $shisetsu_arr[$i]);
    }
    if ($ret==1) {
      // UPDATEで失敗
      return;
    }
  }

  $db->tranCommit();
}

// 施設データ取得
function getShisetsu($db,$from,$to){
  $sql = <<<EOF
WITH shisetsu AS (
  SELECT
      tmp1.sno
    , tmp1.shisetsu_cd
    , tmp1.shisetsu_ver
    , tmp1.shisetsu_kbn
    , tmp1.syucchoujo_cd
    , tmp1.lat
    , tmp1.lon
  FROM
    rfs_m_shisetsu tmp1 JOIN (
      SELECT
          shisetsu_cd
        , MAX(shisetsu_ver) shisetsu_ver
      FROM
        rfs_m_shisetsu
      GROUP BY
        shisetsu_cd
    ) tmp2
      ON tmp1.shisetsu_cd = tmp2.shisetsu_cd
      AND tmp1.shisetsu_ver = tmp2.shisetsu_ver
  WHERE
    $from <= tmp1.sno
    AND tmp1.sno <= $to
)
, shisetsu_bousetsusaku AS (
  SELECT
      s.*
    , b.struct_idx
  FROM
    shisetsu s JOIN rfs_m_bousetsusaku_shichu b
      ON s.sno = b.sno
)
, bousetsusaku AS (
  SELECT
      sno
    , MAX(struct_idx) struct_idx
  FROM
    shisetsu_bousetsusaku
  GROUP BY
    sno
)
SELECT
    s.sno
  , s.shisetsu_cd
  , s.shisetsu_ver
  , s.shisetsu_kbn
  , s.syucchoujo_cd
  , s.lat
  , s.lon
  , COALESCE(b.struct_idx, 0) struct_idx
FROM
  shisetsu s
  LEFT JOIN bousetsusaku b
    ON s.sno = b.sno
order by
  s.sno
EOF;
  $result = $db->query($sql);
  return $result;
}

// 写真検索
function searchPicture(&$arr){

  global $url;
  $path="images/photos/zenkei/${arr['syucchoujo_cd']}/${arr['shisetsu_cd']}/";

  // 施設のディレクトリがあるか
  if (file_exists($url.$path)) {
  }else{
    return "false";
  }

  // ファイルがあるか
  $file_nm_jpg=$arr['shisetsu_cd']."A.jpg";
  $file_nm_jpg2=$arr['shisetsu_cd']."A.JPG";
  $file_nm_bmp=$arr['shisetsu_cd']."A.bmp";
  $file_nm_bmp2=$arr['shisetsu_cd']."A.BMP";

  $full_path=$url.$path.$file_nm_jpg;
  if (file_exists($full_path)) {
    //$arr['full_path']=$full_path;
    //$arr['path']=$path."/".$file_nm_jpg;
    $arr['file_nm']=$file_nm_jpg;
    $arr['extension']='jpg';
  } else {
    $full_path=$url.$path.$file_nm_jpg2;
    if (file_exists($full_path)) {
      //$arr['full_path']=$full_path;
      //$arr['path']=$path."/".$file_nm_jpg2;
      $arr['file_nm']=$file_nm_jpg2;
      $arr['extension']='jpg';
    }else{
      $full_path=$url.$path.$file_nm_bmp;
      if (file_exists($full_path)) {
        //$arr['full_path']=$full_path;
        // $arr['path']=$path."/".$file_nm_bmp;
        $arr['file_nm']=$file_nm_bmp;
        $arr['extension']='bmp';
      }else{
        $full_path=$url.$path.$file_nm_bmp2;
        if (file_exists($full_path)) {
          //$arr['full_path']=$full_path;
          //$arr['path']=$path."/".$file_nm_bmp2;
          $arr['file_nm']=$file_nm_bmp2;
          $arr['extension']='bmp';
        }else{
          return "false";
        }
      }
    }
  }
  return "true";
}

// 全景写真の処理
function execZenkei($db, &$arr) {

  global $url;
  $loop=$arr['struct_idx']; // デフォルト0で防雪柵の場合0以外が入っているはず

  // arrのファイル名ではなく、写真保存用のファイル名に書き換えのため保持
  $path="images/photos/zenkei/${arr['syucchoujo_cd']}/${arr['shisetsu_cd']}/";
  $file_nm=$arr['file_nm'];

  // 緯度経度を取得
  $exif['date']="";
  $exif['lat']=0;
  $exif['lon']=0;
  if ($arr['extension']=="jpg") {
    $exif = get_exif($url.$path.$file_nm);
  }


  // 全支柱インデックスに同じ写真を適用
  for($i=0;$i<=$loop;$i++){
    // 登録データを作成
    $ins=array();
    $ins['sno']=$arr['sno'];
    $ins['shisetsu_cd']=chkItem($arr, "shisetsu_cd", 2);
    $ins['shisetsu_ver']=chkItem($arr, "shisetsu_ver", 1);
    $ins['struct_idx']=$i;
    $ins['zenkei_picture_cd']=1;
    $arr['picture_nm']="全景$i";
    $ins['picture_nm']=chkItem($arr, "picture_nm", 2);
    // ファイル名を変え、ファイルをコピーする
    $ins_file_nm=$i."-1.".$arr['extension'];  // struct_idx-picture_cd
    copy($url.$path.$file_nm, $url.$path.$ins_file_nm);
    $arr['file_nm']=$ins_file_nm;
    $ins['file_nm']=chkItem($arr, "file_nm", 2);
    // ファイルが変わったのでパスを書き換え
    $arr['path']=$path.$ins_file_nm;
    $ins['path']=chkItem($arr, "path", 2);
    //$ins['up_dt']=""; now()
    $arr['lat']=$exif['lat'];
    $arr['lon']=$exif['lon'];
    $ins['lat']=chkItem($arr, "lat", 1);
    $ins['lon']=chkItem($arr, "lon", 1);
    $ins['use_flg']=1;
    $ins['del']=0;
    $arr['exif_dt']=$exif['date'];
    $ins['exif_dt']=chkItem($arr, "exif_dt", 2);
    $ins['description']="";

    $sql = <<<EOF
INSERT
INTO rfs_t_zenkei_picture(
  sno
  , shisetsu_cd
  , shisetsu_ver
  , struct_idx
  , zenkei_picture_cd
  , picture_nm
  , file_nm
  , path
  , up_dt
  , lat
  , lon
  , use_flg
  , del
  , exif_dt
  , description
)
VALUES (
  ${ins['sno']}
  , ${ins['shisetsu_cd']}
  , ${ins['shisetsu_ver']}
  , ${ins['struct_idx']}
  , ${ins['zenkei_picture_cd']}
  , ${ins['picture_nm']}
  , ${ins['file_nm']}
  , ${ins['path']}
  , now()
  , ${ins['lat']}
  , ${ins['lon']}
  , ${ins['use_flg']}
  , ${ins['del']}
  , ${ins['exif_dt']}
  , ''
)
EOF;
    $ret=$db->sqlExec($sql);
    if ($ret==1) {
      return $ret;
    }
  }
  return 0;
}

// 図面検索
function searchZumen(&$arr){

  global $url;
  $path="images/photos/zumen/${arr['syucchoujo_cd']}/${arr['sno']}/";

  // 施設のディレクトリがあるか
  if (file_exists($url.$path)) {
  }else{
    return "false";
  }

  // ファイルがあるか
  $file_nm_jpg=$arr['shisetsu_cd']."Z.jpg";
  $file_nm_jpg2=$arr['shisetsu_cd']."Z.JPG";
  $file_nm_bmp=$arr['shisetsu_cd']."Z.bmp";
  $file_nm_bmp2=$arr['shisetsu_cd']."Z.BMP";

  $full_path=$url.$path.$file_nm_jpg;
  if (file_exists($full_path)) {
    $arr['zumen_file_nm']=$file_nm_jpg;
    $arr['zumen_extension']='jpg';
  } else {
    $full_path=$url.$path.$file_nm_jpg2;
    if (file_exists($full_path)) {
      $arr['zumen_file_nm']=$file_nm_jpg2;
      $arr['zumen_extension']='jpg';
    }else{
      $full_path=$url.$path.$file_nm_bmp;
      if (file_exists($full_path)) {
        $arr['zumen_file_nm']=$file_nm_bmp;
        $arr['zumen_extension']='bmp';
      }else{
        $full_path=$url.$path.$file_nm_bmp2;
        if (file_exists($full_path)) {
          $arr['zumen_file_nm']=$file_nm_bmp2;
          $arr['zumen_extension']='bmp';
        }else{
          return "false";
        }
      }
    }
  }
  return "true";
}

function execZumen($db, &$arr){

  // arrのファイル名ではなく、写真保存用のファイル名に書き換えのため保持
  global $url;
  $path="images/photos/zumen/${arr['syucchoujo_cd']}/${arr['sno']}/";
  $file_nm=$arr['zumen_file_nm'];

  // 緯度経度を取得
  $exif['date']="";
  $exif['lat']=0;
  $exif['lon']=0;
  if ($arr['zumen_extension']=="jpg") {
    $exif = get_exif($url.$path.$file_nm);
  }

  // 登録データを作成
  $ins=array();
  $ins['sno']=$arr['sno'];
  $ins['zumen_id']=0;
  $ins_file_nm=$arr['sno']."-0.".$arr['zumen_extension']; // sno-zumen_id
  copy($url.$path.$file_nm, $url.$path.$ins_file_nm);
  // ファイルが変わったのでパスを書き換え
  $arr['zumen_path']=$path.$ins_file_nm;
  $ins['zumen_path']=chkItem($arr, "zumen_path", 2);
  $arr['exif_dt']=$exif['date'];
  $ins['exif_dt']=chkItem($arr, "exif_dt", 2);
  //$ins['update_dt']=""; now()
  $ins['description']="";

  if ($arr['lat']==null || $arr['lon']==null) {
    // 座標の登録は無いが戻すためにセット
    $arr['lat']=$exif['lat'];
    $arr['lon']=$exif['lon'];
  }else{
    if ($arr['lat']==0 || $arr['lon']==0) {
      // 座標の登録は無いが戻すためにセット
      $arr['lat']=$exif['lat'];
      $arr['lon']=$exif['lon'];
    }
  }

    $sql = <<<EOF
INSERT
INTO rfs_t_zumen(
  sno
  , zumen_id
  , path
  , exif_dt
  , update_dt
  , description
)
VALUES (
  ${ins['sno']}
  , ${ins['zumen_id']}
  , ${ins['zumen_path']}
  , ${ins['exif_dt']}
  , now()
  , ''
)
EOF;
  $ret=$db->sqlExec($sql);
  if ($ret==1) {
    return $ret;
  }
  return 0;
}


/***
 * Exif情報の取得
 ***/
function get_exif($file){

  // 日付の取得
  $exif=@exif_read_data($file);

  $date=false;//date("Y/m/d H:i:s", filemtime($file));
  $dat=array();

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
  if($date && $date > "2000/01/01"){
    $dat['date']=$date;
  }else{
    $dat['date']="";
  }

  // 緯度経度の取得
  $dat['lat']=0;
  $dat['lon']=0;

  $exif=@exif_read_data($file,0,true);
  /*
      log_message("info","EXIF TEST2-----------------------------------------------");
      log_message("info",$file);
      log_message("info",var_export($exif,true));
*/

  if (isset($exif['GPS']) && isset($exif['GPS']['GPSLatitude']) && isset($exif['GPS']['GPSLongitude'])) {
    $lat = gps_degree($exif['GPS']['GPSLatitude'], $exif['GPS']['GPSLatitudeRef']);
    $lng = gps_degree($exif['GPS']['GPSLongitude'], $exif['GPS']['GPSLongitudeRef']);

    if ($lat && $lng) {
      $dat['lat']=$lat;
      $dat['lon']=$lng;
    }
  }
  return $dat;
}

function gps_degree($gps, $ref) {
  if (count($gps) != 3) return false;
  if ($ref == 'S' or $ref == 'W') {
    $pm = -1;
  } else {
    $pm = 1;
  }
  $degree = fraction2number($gps[0]);
  $minute = fraction2number($gps[1]);
  $second = fraction2number($gps[2]);
  return ($degree + $minute/60.0 + $second/3600.0) * $pm;
}

function fraction2number($value) {
  try{
    $fraction = explode('/', $value);
    if (count($fraction) != 2) return 0;
    if ((double)$fraction[1] == 0) $fraction[1]=1;
    return (double)$fraction[0] / (double)$fraction[1];
  }catch(Exception $ex){
  }
  return 0;
}

/***
   *  登録用項目チェック
   *    登録項目がある場合その値(文字列の場合は登録用文字列)、
   *    無い場合は数値項目はNULLを、文字列項目は空文字を返却
   *
   *  引数
   *    $obj 項目格納オブジェクト
   *    $key 項目キー
   *    $kbn 1:数値項目、2:文字列項目
   ***/
function chkItem($obj, $key, $kbn){
  if ($kbn==1) {
    // 数値項目
    $obj[$key]=isset($obj[$key])?$obj[$key]:"null";
  }else if ($kbn==2) {
    $obj[$key]=isset($obj[$key])?pg_escape_literal($obj[$key]):"null";
  }else if ($kbn==3) {
    $obj[$key]=isset($obj[$key])?pg_escape_literal($obj[$key]):"'f'";
  }
  return $obj[$key];
}

/***
 * 基本情報の登録
 *   基本情報の座標を更新する
 ***/
function updShisetsu($db, $arr){
  $sql = <<<EOF
UPDATE rfs_m_shisetsu
SET
  lat = ${arr['lat']}
  , lon = ${arr['lon']}
WHERE
  sno = ${arr['sno']}
EOF;
  $ret=$db->sqlExec($sql);
  return $ret;
}
