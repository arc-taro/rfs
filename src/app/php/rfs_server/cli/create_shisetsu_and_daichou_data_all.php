<?php
require_once 'db.php';
ini_set('memory_limit', '5000M');
main();
exit(1);

function main(){
  $db = new DB();
  // 初期化
  dbInit($db);

  //コマンドラインの引数を受け取る
  //$_SERVER['argv'][1]からが値の入る場所なので、for文内で調整
  $arr_argv = array();
  for($i = 0; $i < $_SERVER['argc'] - 1;$i++ ){
    $arr_argv[] = $_SERVER['argv'][$i + 1];
  }
//  print_r($arr_argv,false);
//  return;

  $cd_arr=getShisetsuKbn($db);

  $file_arr = [];
  $file_arr_kihonjouhou = [];
  $csvDirName = './tmp/'.date("Ymd_His").'/';
  $zipFileName = $csvDirName;
  if(!file_exists($csvDirName)){
    mkdir($csvDirName, 0777,TRUE);
  }
    //施設毎にデータを取得する
    getDataEveryShisetsuKbn($db,$cd_arr,$csvDirName,$arr_argv);
}


function dbInit($db){
  $db->init();
}

function getShisetsuKbn($db){
  $sql=<<<EOF
SELECT
  shisetsu_kbn
, daityou_tbl
FROM
  rfs_m_shisetsu_kbn 
ORDER BY
  shisetsu_kbn
EOF;
  //echo $sql;
  return $db->query($sql);
}

function getDataEveryShisetsuKbn($db,$cd_arr,$csvDirName,$arr_argv){
  $shisetsu_kbn_header = [
    1=>'DH',2=>'JD',3=>'SS',4=>'BS',5=>'YH',6=>'KA',7=>'KB',8=>'KC',9=>'KD',10=>'KI'
    ,11=>'JH',12=>'SD',13=>'DT',14=>'TT',15=>'CK',16=>'SK',17=>'BH',18=>'DY',19=>'DN',20=>'TS',21=>'RH'];
  //それぞれの施設のデータを取得する
  for($i=0; $i < count($arr_argv);$i++){
  //コマンドライン引数からの施設区分の場合
    $cd = $arr_argv[$i];
  //getShisetsuKbnから取った施設区分の場合
  //$cd = $cd_arr[$i]['shisetsu_kbn'];

  //基本情報部分の項目は共通なので、まとめておく
    $csv_kihon_header = [
    'sno'=>'シリアル番号'
    ,'shisetsu_cd'=>'施設管理番号'
    ,'shisetsu_kbn'=>'施設区分'
    ,'shisetsu_kbn_nm'=>'施設名'
    ,'shisetsu_keishiki_cd'=>'施設形式'
    ,'shisetsu_keishiki_nm'=>'施設形式名'
    ,'rosen_cd'=>'路線コード'
    ,'rosen_nm'=>'路線名'
    ,'shityouson'=>'市町村'
    ,'azaban'=>'字番'
    ,'lat'=>'緯度'
    ,'lon'=>'経度'
    ,'dogen_cd'=>'建設管理部コード'
    ,'dogen_mei'=>'建設管理部名'
    ,'syucchoujo_cd'=>'出張所コード'
    ,'syucchoujo_mei'=>'出張所名'
    ,'substitute_road'=>'代替路番号'
    ,'substitute_road_str'=>'代替路の有無'
    ,'emergency_road'=>'緊急輸送道路番号'
    ,'emergency_road_str'=>'緊急輸送道路'
    ,'motorway'=>'自専道/一般道番号'
    ,'motorway_str'=>'自専道/一般道'
    ,'senyou'=>'占用物件'
    ,'secchi'=>'設置年度'
    ,'haishi'=>'廃止年度'
    ,'fukuin'=>'幅員'
    ,'sp'=>'測点(自)(ｍ)'
    ,'sp_to'=>'測点(至)(ｍ)'
    ,'kp'=>'kp'
    ,'lr'=>'横断区分番号'
    ,'lr_str'=>'横断区分'
    ,'secchi_yyyy'=>'設置年度(西暦)'
    ,'haishi_yyyy'=>'廃止年度(西暦)'
    ,'shisetsu_cd_daichou'=>'旧データ施設コード'
    ,'kyouyou_kbn'=>'供用区分'
    ,'kyouyou_kbn_str'=>'供用区分'
    ,'ud'=>'上下区分(番号)'
    ,'ud_str'=>'上下区分'
    ,'koutsuuryou_day'=>'日交通量'
    ,'koutsuuryou_oogata'=>'交通量大型車'
    ,'koutsuuryou_hutuu'=>'交通量普通車'
    ,'koutsuuryou_12'=>'12時間交通量'
    ,'name'=>'名称'
    ,'keishiki_kubun_cd1'=>'形式/区分1コード'
    ,'keishiki_kubun1'=>'形式/区分1内容'
    ,'keishiki_kubun_cd2'=>'形式/区分2コード'
    ,'keishiki_kubun2'=>'形式/区分2内容'
    ,'encho'=>'延長'
    ,'seiri_no'=>'整理番号'];

    //対応した施設のデータ取得とcsv出力をするphpファイルを呼び出す
    if($cd >= 1 && $cd <=21){
      require 'getForOutData'.$shisetsu_kbn_header[$cd].'.php';
    }else{
      continue;
    }
    if($cd == 1){
      $data = getForOutDataDH($db,$cd);
      $file_arr[] = outputCSVDH($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 2){
      $data = getForOutDataJD($db,$cd);
      $file_arr[] = outputCSVJD($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 3){
      $data = getForOutDataSS($db,$cd);
      $file_arr[] = outputCSVSS($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 4){
      $data = getForOutDataBS($db,$cd);
      $file_arr[] = outputCSVBS($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 5){
      $data = getForOutDataYH($db,$cd);
      $file_arr[] = outputCSVYH($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 6){
      $data = getForOutDataKA($db,$cd);
      $file_arr[] = outputCSVKA($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 7){
      $data = getForOutDataKB($db,$cd);
      $file_arr[] = outputCSVKB($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 8){
      $data = getForOutDataKC($db,$cd);
      $file_arr[] = outputCSVKC($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 9){
      $data = getForOutDataKD($db,$cd);
      $file_arr[] = outputCSVKD($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 10){
      $data = getForOutDataKI($db,$cd);
      $file_arr[] = outputCSVKI($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 11){
      $data = getForOutDataJH($db,$cd);
      $file_arr[] = outputCSVJH($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 12){
      $data = getForOutDataSD($db,$cd);
      $file_arr[] = outputCSVSD($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 13){
      $data = getForOutDataDT($db,$cd);
      $file_arr[] = outputCSVDT($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 14){
      $data = getForOutDataTT($db,$cd);
      $file_arr[] = outputCSVTT($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 15){
      $data = getForOutDataCK($db,$cd);
      $file_arr[] = outputCSVCK($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 16){
      $data = getForOutDataSK($db,$cd);
      $file_arr[] = outputCSVSK($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 17){
      $data = getForOutDataBH($db,$cd);
      $file_arr[] = outputCSVBH($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 18){
      $data = getForOutDataDY($db,$cd);
      $file_arr[] = outputCSVDY($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 19){
      $data = getForOutDataDN($db,$cd);
      $file_arr[] = outputCSVDN($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 20){
      $data = getForOutDataTS($db,$cd);
      $file_arr[] = outputCSVTS($data,$csvDirName,$csv_kihon_header);
    }
    if($cd == 21){
      $data = getForOutDataRH($db,$cd);
      $file_arr[] = outputCSVRH($data,$csvDirName,$csv_kihon_header);
    }
  }

  //基本情報4種と台帳データを紐づけたcsvファイルをzipにする
  $zip = new ZipArchive();
  $res = $zip->open('./shisetsu_and_daichou_data.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
  if($res === true){
    foreach($file_arr as $file){
      $zip->addFile($file,basename($file));
    }
    $zip->close();
  }else {
    echo 'Error Code: ' . $res;
  }
}
