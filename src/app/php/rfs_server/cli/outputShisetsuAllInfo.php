<?php
require_once 'db.php';
require 'outputShisetsuTenkenData.php';
require 'outputShisetsuDaichouData.php';
ini_set('display_errors', "On");
echo "outputShisetsuAllInfo.php  ".date("Y-m-d H:i:s")."\n";

exec("ps x | grep outputShisetsuAllInfo.php | grep -v grep", $output, $result);

// 2個以上見つかれば
if (count($output) > 2) {
   echo "2重起動 \n";
	// 中断する
	exit();
}

// 処理
main($argc,$argv);
exit(1);

// メイン関数
function main($argc,$argv){

  //CSV出力テスト
  // $path = '/home/www/html/rfs_mockup/excels/test.csv';
  // $head = array('施設名','形式','管理番号','No.○/総記録枚数','開始','～','終了','路線番号','路線名','市町村','字番','緯度','経度','測点','横断区分','代替路の有無','緊急輸送道路','自専道or一般道','占用物件(名称)','建設管理部','出張所','点検実施年月日','点検員 会社名','点検員 氏名','調査実施年月日','調査員 会社名','調査員 氏名');
  // $file = fopen($path,'w');
  // fputcsv($file, $head);
  // fclose($file);
  // exit(1);

  // DB初期化
  $DB_rfs = new DB();
  $DB_rfs->init();

  $base_tmp_path = '/home/app/rfs_mockup/cli/tmp/';
  $base_www_path = '/home/www/html/rfs_mockup/csv/';

  // 引数
  // $get['shisetsu_kbn'] = 2;
  // $get['nendo'] = 2021;

  // CSV出力リクエストを取得
  $requests = getRequest($DB_rfs);

  // 台帳取得で使用する施設区分マスタを取得
  $cd_arr = getShisetsuKbn($DB_rfs);

  $request_group = '';
  $nendo = '';
  $shisetsu_kbn = '';
  
  // ZIP保存先
  $csvDirName = '';
  // request_groupでループ
  foreach($requests as $request){
    $csv_file_name = [];
    $request_group = $request['request_group'];
    $csvDirName = $base_tmp_path.$request_group.'/';    // csvファイル格納
    $zip_www_path = $base_www_path.$request_group.'/';  // www
    exec('mkdir '.$csvDirName);
    exec('mkdir '.$zip_www_path);
    $nendo = $request['nendo'];
    $shisetsu_kbn_list = json_decode($request['arr'],true);

    // request_groupごとにshisetsu_kbnでループ
    foreach($shisetsu_kbn_list as $shisetsu_kbn){
      // tmpテーブルの削除
      deleteTenkenTmp($DB_rfs);
      deleteDaichouTmp($DB_rfs);
      
      // 点検データ取得
      $result = getHuzokubutsuAll($nendo,$shisetsu_kbn,$DB_rfs);
      // 点検データ取得結果をjson化
      $json_result = getJsonTenken($result);
      // 点検データ取得結果jsonをtmpテーブルに登録
      insertTmp($json_result,$DB_rfs);
      
      //施設毎にデータを取得する、tmpテーブルに登録
      array_push($csv_file_name,getDataEveryShisetsuKbn($csvDirName,$DB_rfs,$cd_arr,$request_group,$shisetsu_kbn));

      // // tmpテーブルの削除
      // deleteTenkenTmp($DB_rfs);
      // deleteDaichouTmp($DB_rfs);
    }

    print_r("ZIP対象");
    print_r($csv_file_name);

    // csvファイルをzipにする
    $zip = new ZipArchive();
    $now = date("Ymd_His");
    $zip_file = $request_group.'_shisetsuAllInfo_'.$now.'.zip';
    $res = $zip->open($csvDirName.$zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    if($res === true){
      foreach($csv_file_name as $file){
        $zip->addFile($file,basename($file));
      }
      $zip->close();
      // wwwへコピー
      exec('cp '.$csvDirName.$zip_file.' '.$zip_www_path.$zip_file);
      // ZIP情報の保存
      updateZipData($DB_rfs, $request_group, $now, $zip_file);
    }else {
      echo 'Error Code: ' . $res;
    }
  }
  

  // // 点検データ取得
  // $result = getHuzokubutsuAll($get,$DB_rfs);
  // // 点検データ取得結果をjson化
  // $json_result = getJsonTenken($result);
  // // 点検データ取得結果jsonをtmpテーブルに登録
  // insertTmp($json_result,$DB_rfs);

  // // 台帳を取得
  // $cd_arr = getShisetsuKbn($DB_rfs); // 施設区分マスタを取得
  // //施設毎にデータを取得する、tmpテーブルに登録
  // getDataEveryShisetsuKbn($csvDirName,$DB_rfs,$cd_arr,$get);

  // // tmpテーブルの削除
  // deleteTenkenTmp($DB_rfs);
  // deleteDaichouTmp($DB_rfs);

  $DB_rfs->close();
}

// CSV出力リクエストを取得
function getRequest($DB_rfs){
  $sql=<<<EOF
  select 
    request_group
    ,nendo
    ,array_to_json(array_agg(shisetsu_kbn)) as arr
  from rfs_t_management_create_csv
  where file_exec_dt is null
  group by request_group,nendo
  order by request_group
;
EOF;
  return $DB_rfs->query($sql);
}

function getJsonDaichou($data){
$json_data = [];
foreach($data as $row){
  $sno = $row['sno'];
  array_push($json_data , array('sno'=>$sno , 'shisetsu_json'=>json_encode($row)));
}

// $json_data = json_encode($data);
// print_r($json_data);
return $json_data;
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

function getDataEveryShisetsuKbn($csvDirName,$db,$cd_arr,$request_group,$cd){
  // $csvDirName = './';
$shisetsu_kbn_header = [
  1=>'DH',2=>'JD',3=>'SS',4=>'BS',5=>'YH',6=>'KA',7=>'KB',8=>'KC',9=>'KD',10=>'KI'
  ,11=>'JH',12=>'SD',13=>'DT',14=>'TT',15=>'CK',16=>'SK',17=>'BH',18=>'DY',19=>'DN',20=>'TS',21=>'RH'];
//それぞれの施設のデータを取得する
// for($i=0; $i < count($arr_argv);$i++){
//施設区分
  // $cd = $get['shisetsu_kbn'];

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
  // }else{
  //   continue;
  }
  if($cd == 1){
    $data = getForOutDataDH($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVDH($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 2){
    $data = getForOutDataJD($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVJD($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 3){
    $data = getForOutDataSS($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVSS($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 4){
    $data = getForOutDataBS($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVBS($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 5){
    $data = getForOutDataYH($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVYH($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 6){
    $data = getForOutDataKA($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVKA($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 7){
    $data = getForOutDataKB($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVKB($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 8){
    $data = getForOutDataKC($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVKC($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 9){
    $data = getForOutDataKD($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVKD($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 10){
    $data = getForOutDataKI($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVKI($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 11){
    $data = getForOutDataJH($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVJH($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 12){
    $data = getForOutDataSD($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVSD($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 13){
    $data = getForOutDataDT($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVDT($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 14){
    $data = getForOutDataTT($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVTT($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 15){
    $data = getForOutDataCK($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVCK($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 16){
    $data = getForOutDataSK($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVSK($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 17){
    $data = getForOutDataBH($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVBH($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 18){
    $data = getForOutDataDY($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVDY($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 19){
    $data = getForOutDataDN($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVDN($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 20){
    $data = getForOutDataTS($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVTS($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
  if($cd == 21){
    $data = getForOutDataRH($db,$cd);
    $json_data_daichou = getJsonDaichou($data);
    insertTmpDaichou($json_data_daichou,$db);
    // 施設点検のヘッダ、項目を取得
    $tenken_header = getTenkenCsvHead($cd);
    $tenken_Fields = getTenkenCsvFields($cd,$db);
    // CSV出力を呼出
    $csv_file_name = _outputCSVRH($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields);
  }
// }

  // // csvファイルをzipにする
  // $zip = new ZipArchive();
  // $res = $zip->open($csvDirName.'shisetsuAllInfo_'.date("Ymd_His").'.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
  // if($res === true){
  //   foreach($file_arr as $file){
  //     $zip->addFile($file,basename($file));
  //   }
  //   $zip->close();
  // }else {
  //   echo 'Error Code: ' . $res;
  // }

  updateFileExecDt($db,$request_group,$cd);
  return $csv_file_name;
}

function insertTmpDaichou($json_data,$DB_rfs){
  foreach($json_data as $item){
    $sno = $item['sno'];
    $shisetsu_json = $item['shisetsu_json'];
    $sql=<<<EOD
            insert into
              rfs_t_output_csv_daichou_tmp(
                    sno
                    , shisetsu_json
                )
                values(
                    $sno
                    , '$shisetsu_json'
                );
EOD;
    $DB_rfs->query($sql);
  }
}

function getShisetsuTenkenAndDaichou($DB_rfs){
  $sql=<<<EOD
    select
      daichou.sno
      ,tenken.struct_idx
      ,daichou.shisetsu_json daichou_json 
      ,tenken.shisetsu_json tenken_json
    from rfs_t_output_csv_daichou_tmp daichou
    left join rfs_t_output_csv_tenken_tmp tenken
      on daichou.sno = tenken.sno
    ;
EOD;
  return $DB_rfs->query($sql);
}

function deleteTenkenTmp($DB_rfs){
  $sql=<<<EOF
  DELETE
  FROM
    rfs_t_output_csv_tenken_tmp ;
EOF;
    //echo $sql;
  return $DB_rfs->query($sql);
}

function deleteDaichouTmp($DB_rfs){
  $sql=<<<EOF
  DELETE
  FROM
    rfs_t_output_csv_daichou_tmp ;
EOF;
    //echo $sql;
  return $DB_rfs->query($sql);
}

// ファイルのCSV作成日時を保存する
function updateFileExecDt($DB_rfs, $request_group, $shisetsu_kbn){
  $sql=<<<EOD
  UPDATE rfs_t_management_create_csv 
  SET
    file_exec_dt = NOW() 
  WHERE
    request_group = $request_group 
    AND shisetsu_kbn = $shisetsu_kbn;
EOD;
  return $DB_rfs->query($sql);
}

/**
 *  zipファイルの作成日時とファイル名を保存
 *  1回の作業内は全て同じ日時とファイル名が入る
 */
function updateZipData($DB_rfs, $request_group, $zip_dt, $zip_file_nm){
  $zip_dt = pg_escape_literal($zip_dt);
  $zip_file_nm = pg_escape_literal($zip_file_nm);
  $sql=<<<EOD
  UPDATE rfs_t_management_create_csv 
  SET
    zip_dt = $zip_dt
    , zip_file_nm = $zip_file_nm
  WHERE
    request_group = $request_group;
EOD;
  return $DB_rfs->query($sql);
}
