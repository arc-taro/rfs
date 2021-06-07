<?php
require_once 'db.php';

ini_set('memory_limit', '512M');
main();
exit(1);

function main(){
  $db = new DB();
  $dogen_cd="";
  $syucchoujo_cd="";
  $shisetsu_kbn_cd="";
  $daichou_tbl_nm="";
  // 初期化
  dbInit($db);
  // 施設区分データ呼び出し　台帳テーブル名も取る
  //$shisetsu_kbn_arr=getShisetsuKbn($db);
  $file_arr = [];
  $file_arr_kihonjouhou = [];
  $csvDirName = './tmp/'.date("Ymd_His").'/';
  $zipFileName = $csvDirName;
  if(!file_exists($csvDirName)){
    mkdir($csvDirName, 0777,TRUE);
  }
  $cd_arr=getDogen($db);

  //建管でループする
  loopCd($db,$cd_arr,$csvDirName);
}


function dbInit($db){
  $db->init();
}

function getSyucchojo($db){
  $sql=<<<EOF
SELECT
  syucchoujo_cd cd
FROM
  rfs_m_syucchoujo 
ORDER BY
  syucchoujo_cd
EOF;
  //echo $sql;
  return $db->query($sql);
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

function getDogen($db){
  $sql=<<<EOF
SELECT
  dogen_cd cd
FROM
  rfs_m_dogen
ORDER BY
  dogen_cd
EOF;
  //echo $sql;
  return $db->query($sql);
}

function loopCd($db,$cd_arr,$csvDirName){
  // 出張所ループ
  for ($i=0;$i<count($cd_arr);$i++){ 
    // SQL実行（引数に出張所コード、施設区分コード）
    $cd = $cd_arr[$i]['cd'];
    $data=getForOutData($db, $cd);

    // 出張所ループ内に施設区分ループ
//    for ($j=0;$j<count($shisetsu_kbn_arr);$j++){    
      //$shisetsu_kbn_cd = $shisetsu_kbn_arr[$j]['shisetsu_kbn'];
      $file_arr[] = outputCSV($data,$cd,$csvDirName);
//      $file_arr_kihonjouhou[] = outputCSV_kihonjouhou($data,$cd,$csvDirName,$number);
//    }
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

// 引数の出張所コードと施設区分コード、台帳テーブル名をSQLにセットし
// 必要な基本情報、台帳情報を紐付けて取得する。
function getForOutData($db, $cd,$kbn) {

  $sql=<<<EOF
  WITH s_tmp AS (
    SELECT * FROM rfs_m_shisetsu WHERE shisetsu_kbn=3 AND dogen_cd=$cd
  )
    SELECT
    shisetsu.sno 
  , shisetsu.shisetsu_cd 
  , shisetsu.syucchoujo_cd
  , syucchoujo.syucchoujo_mei
  , shisetsu.shisetsu_ver 
  , shisetsu.shisetsu_kbn
  , s_kbn.shisetsu_kbn_nm
  , shisetsu.shisetsu_keishiki_cd
  , s_kei.shisetsu_keishiki_nm
  , shisetsu.rosen_cd
  , rosen.rosen_nm
  , shisetsu.shityouson
  , shisetsu.azaban
  , shisetsu.lat
  , shisetsu.lon
  , shisetsu.dogen_cd
  , dogen.dogen_mei
  , shisetsu.substitute_road
  , CASE shisetsu.substitute_road 
    WHEN 0 THEN '有' 
    WHEN 1 THEN '無' 
    ELSE '' 
    END substitute_road_str
  , shisetsu.emergency_road
  , CASE shisetsu.emergency_road 
   WHEN NULL THEN '' 
    ELSE '第' || shisetsu.emergency_road || '次' 
    END emergency_road_str
  , shisetsu.motorway
  , CASE shisetsu.motorway 
    WHEN 0 THEN '自専道' 
    WHEN 1 THEN '一般道' 
    ELSE '' 
    END motorway_str
  , shisetsu.senyou
  , shisetsu.secchi
  , shisetsu.haishi
  , shisetsu.fukuin
  , shisetsu.sp
  , shisetsu.sp_to
  , shisetsu.kp
  , shisetsu.lr
  , CASE shisetsu.lr 
    WHEN 1 THEN '右' 
    WHEN 0 THEN '左' 
    WHEN 2 THEN '中央' 
    WHEN 3 THEN '左右' 
    ELSE '' 
    END lr_str
  , shisetsu.secchi_yyyy
  , shisetsu.haishi_yyyy
  , shisetsu.shisetsu_cd_daichou
  , shisetsu.kyouyou_kbn
  , CASE shisetsu.kyouyou_kbn 
    WHEN 1 THEN '供用' 
    WHEN 0 THEN '休止' 
    ELSE '' 
    END kyouyou_kbn_str
  , shisetsu.ud
  , CASE shisetsu.ud 
    WHEN 1 THEN '下り' 
    WHEN 0 THEN '上り' 
    WHEN 2 THEN '上下' 
    ELSE '' 
    END ud_str
  , shisetsu.koutsuuryou_day
  , shisetsu.koutsuuryou_oogata
  , shisetsu.koutsuuryou_hutuu
  , shisetsu.koutsuuryou_12
  , shisetsu.name
  , shisetsu.keishiki_kubun_cd1
  , keishiki_kbn1.keishiki_kubun
  , shisetsu.keishiki_kubun_cd2
  , keishiki_kbn2.keishiki_kubun
  , shisetsu.encho
  , shisetsu.seiri_no 
  , ss.toutyuu_no
  , ss.secchi_bui
  , ss.kousa_rosen
  , ss.shichuu_kou
  , ss.haba
  , ss.pole_kikaku_cd
  , pk.pole_kikaku
  , ss.pole_kikaku_bikou
  , ss.syadou_ramp1
  , ss.syadou_syoutou1
  , ss.syadou_ramp2
  , ss.syadou_syoutou2
  , ss.hodou_ramp1
  , ss.hodou_syoutou1
  , ss.hodou_ramp2
  , ss.hodou_syoutou2
  , ss.ramp_num
  , ss.tyoukou_umu_cd
  , tu.tyoukou_umu
  , ss.timer_umu_cd
  , timer.timer_umu
  , ss.syoutou syoutou_cd
  , CASE ss.syoutou 
    WHEN 1 THEN '有' 
    WHEN 2 THEN '無' 
    ELSE '不明' 
    END syoutou
  , ss.secchi_gyousya
  , ss.hodou_syoumei_payer
  , ss.d_hokuden_kyakuban
  , ss.d_keiyaku_houshiki
  , ss.d_kaisen_id
  , ss.d_kaisen_kyakuban
  , ss.d_hikikomi
  , ss.d_denki_dai
  , ss.d_denki_ryou
  , ss.bikou
  , ss.kyoutsuu1
  , ss.kyoutsuu2
  , ss.kyoutsuu3
  , ss.dokuji1
  , ss.dokuji2
  , ss.dokuji3
  , ss.create_dt
  , ss.create_account
  , ss.update_dt
  , ss.update_account
  , ss.tougu_secchi
  , ss.tougu_secchi_yyyy
  , ss.update_account_nm
  , ss.update_busyo_cd
  FROM
  s_tmp shisetsu 
  LEFT JOIN rfs_t_daichou_ss ss 
    ON shisetsu.sno = ss.sno 
  LEFT JOIN rfs_m_shisetsu_kbn s_kbn 
    ON shisetsu.shisetsu_kbn = s_kbn.shisetsu_kbn 
  LEFT JOIN rfs_m_shisetsu_keishiki s_kei 
    ON shisetsu.shisetsu_kbn = s_kei.shisetsu_kbn 
    AND shisetsu.shisetsu_keishiki_cd = s_kei.shisetsu_keishiki_cd 
  LEFT JOIN rfs_m_rosen rosen 
    ON shisetsu.rosen_cd = rosen.rosen_cd 
  LEFT JOIN rfs_m_dogen dogen 
    ON shisetsu.dogen_cd = dogen.dogen_cd 
  LEFT JOIN rfs_m_syucchoujo syucchoujo 
    ON shisetsu.syucchoujo_cd = syucchoujo.syucchoujo_cd 
  LEFT JOIN ( 
    SELECT
        * 
    FROM
      rfs_m_keishiki_kubun 
    WHERE
      syubetsu = 1
  ) keishiki_kbn1 
    ON shisetsu.shisetsu_kbn = keishiki_kbn1.shisetsu_kbn 
    AND shisetsu.keishiki_kubun_cd1 = keishiki_kbn1.keishiki_kubun_cd 
  LEFT JOIN ( 
    SELECT
        * 
    FROM
      rfs_m_keishiki_kubun 
    WHERE
      syubetsu = 2
  ) keishiki_kbn2 
    ON shisetsu.shisetsu_kbn = keishiki_kbn2.shisetsu_kbn 
    AND shisetsu.keishiki_kubun_cd2 = keishiki_kbn2.keishiki_kubun_cd 
  LEFT JOIN rfs_m_pole_kikaku pk
    ON ss.pole_kikaku_cd=pk.pole_kikaku_cd
  LEFT JOIN rfs_m_tyoukou_umu tu
    ON ss.tyoukou_umu_cd=tu.tyoukou_umu_cd
  LEFT JOIN rfs_m_timer_umu timer
    ON ss.timer_umu_cd=timer.timer_umu_cd
  ORDER BY syucchoujo_cd
EOF;
  //echo $sql;
  return $db->query($sql);
}

function outputCSV($data,$cd,$csvDirName) {
  $csv_header = ['sno'=>'シリアル番号','shisetsu_cd'=>'施設管理番号'
  ,'shisetsu_kbn'=>'施設区分'
  ,'shisetsu_kbn_nm'=>'施設名','shisetsu_keishiki_cd'=>'施設形式','shisetsu_keishiki_nm'=>'施設形式名','rosen_cd'=>'路線コード'
  ,'rosen_nm'=>'路線名','shityouson'=>'市町村','azaban'=>'字番','lat'=>'緯度','lon'=>'経度'
  ,'dogen_cd'=>'建設管理部コード','dogen_mei'=>'建設管理部名','syucchoujo_cd'=>'出張所コード','syucchoujo_mei'=>'出張所名','substitute_road'=>'代替路番号'
  ,'substitute_road_str'=>'代替路の有無','emergency_road'=>'緊急輸送道路番号','emergency_road_str'=>'緊急輸送道路'
  ,'motorway'=>'自専道/一般道番号','motorway_str'=>'自専道/一般道','senyou'=>'占用物件','secchi'=>'設置年度','haishi'=>'廃止年度'
  ,'fukuin'=>'幅員','sp'=>'測点(自)(ｍ)','sp_to'=>'測点(至)(ｍ)','kp'=>'kp','lr'=>'横断区分番号','lr_str'=>'横断区分'
  ,'secchi_yyyy'=>'設置年度(西暦)','haishi_yyyy'=>'廃止年度(西暦)','shisetsu_cd_daichou'=>'旧データ施設コード','kyouyou_kbn'=>'供用区分','kyouyou_kbn_str'=>'供用区分'
  ,'ud'=>'上下区分(番号)','ud_str'=>'上下区分','koutsuuryou_day'=>'日交通量','koutsuuryou_oogata'=>'交通量大型車','koutsuuryou_hutuu'=>'交通量普通車'
  ,'koutsuuryou_12'=>'12時間交通量','name'=>'名称','keishiki_kubun_cd1'=>'形式/区分1コード','keishiki_kubun1'=>'形式/区分1内容'
  ,'keishiki_kubun_cd2'=>'形式/区分2コード','keishiki_kubun2'=>'形式/区分2内容','encho'=>'延長','seiri_no'=>'整理番号'
  ,'toutyuu_no'=>'灯柱番号','secchi_bui'=>'設置部位','kousa_rosen'=>'交差路線','shichuu_kou'=>'支柱高'
  ,'haba'=>'幅','pole_kikaku_cd'=>'ポール規格コード'
  ,'pole_kikaku'=>'規格','pole_kikaku_bikou'=>'備考','syadou_ramp1'=>'車道①／ランプ形式','syadou_syoutou1'=>'消灯'
  ,'syadou_ramp2'=>'車道②／ランプ形式','syadou_syoutou2'=>'消灯'
  ,'hodou_ramp1'=>'歩道①／ランプ形式','hodou_syoutou1'=>'消灯','hodou_ramp2'=>'歩道②／ランプ形式','hodou_syoutou2'=>'消灯'
  ,'tougu_secchi'=>'灯具設置年度','tougu_secchi_yyyy'=>'灯具設置年度（西暦）'
  ,'ramp_num'=>'ランプ数'
  ,'tyoukou_umu_cd'=>'調光コード','tyoukou_umu'=>'調光'
  ,'timer_umu_cd'=>'タイマーコード','timer_umu'=>'タイマー'
  ,'syoutou_cd'=>'消灯コード','syoutou'=>'消灯','secchi_gyousya'=>'設置業者'
  ,'hodou_syoumei_payer'=>'歩道照明支払者'
  ,'d_hokuden_kyakuban'=>'電気_ほくでん客番','d_keiyaku_houshiki'=>'電気_契約方式'
  ,'d_kaisen_id'=>'電気_回線ID(電話番号)','d_kaisen_kyakuban'=>'電気_回線客番号','d_hikikomi'=>'電気_北電電柱引込','d_denki_dai'=>'電気_電気代'
  ,'d_denki_ryou'=>'電気_電気量','bikou'=>'備考','kyoutsuu1'=>'共通1','kyoutsuu2'=>'共通2','kyoutsuu3'=>'共通3','dokuji1'=>'独自1'
  ,'dokuji2'=>'独自2','dokuji3'=>'独自3','create_dt'=>'作成日','create_account'=>'作成アカウント','update_dt'=>'更新日'
  ,'update_account'=>'更新アカウント','update_account_nm'=>'更新アカウント名'];

  // 建管
  $tmp="daichou_dogen_${cd}_";
  $csvFileName =$csvDirName. $tmp .'SS_'. date("Ymd_His").'.csv';
  $fp = fopen($csvFileName,'w');
  mb_convert_variables('SJIS', 'UTF-8', $csv_header);
  fputcsv($fp,(array)$csv_header);
  for ($i=0;$i<count($data);$i++) {
    $tmp=array();
    foreach($csv_header as $key=>$value){
      $tmp[] = $data[$i][$key];
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

