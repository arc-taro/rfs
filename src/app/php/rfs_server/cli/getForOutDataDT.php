<?php

function getForOutDataDT($db,$cd) {

  $sql=<<<EOF
  WITH s_tmp AS (
    SELECT * FROM rfs_m_shisetsu WHERE shisetsu_kbn=$cd
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
, keishiki_kbn1.keishiki_kubun as keishiki_kubun1
, shisetsu.keishiki_kubun_cd2
, keishiki_kbn2.keishiki_kubun as keishiki_kubun2
, shisetsu.encho
, shisetsu.seiri_no 
,dt.kousa_rosen_nm
,dt.kankatsu_keisatsusyo
,dt.secchi_honsuu
,dt.bikou
,dt.kyoutsuu1
,dt.kyoutsuu2
,dt.kyoutsuu3
,dt.dokuji1
,dt.dokuji2
,dt.dokuji3
,dt.create_dt
,dt.create_account
,dt.update_dt
,dt.update_account
,dt.update_account_nm
,dt.update_busyo_cd
FROM
s_tmp shisetsu 
LEFT JOIN rfs_t_daichou_dt dt 
  ON shisetsu.sno = dt.sno 
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
order by syucchoujo_cd
EOF;
//AND shisetsu.shisetsu_kbn = $shisetsu_kbn_cd

  //echo $sql;
  return $db->query($sql);
}

function outputCSVDT($data,$csvDirName,$csv_kihon_header) {
  $csv_daichou_header = [
    'kousa_rosen_nm'=>'交差路線名'
    ,'kankatsu_keisatsusyo'=>'管轄警察署'
    ,'secchi_honsuu'=>'設置本数'
    ,'bikou'=>'備考'
    ,'kyoutsuu1'=>'共通1'
    ,'kyoutsuu2'=>'共通2'
    ,'kyoutsuu3'=>'共通3'
    ,'dokuji1'=>'独自1'
    ,'dokuji2'=>'独自2'
    ,'dokuji3'=>'独自3'
    ,'create_dt'=>'作成データ'
    ,'create_account'=>'作成アカウント'
    ,'update_dt'=>'更新データ'
    ,'update_account'=>'更新アカウント'
    ,'update_account_nm'=>'更新アカウント名'
    ,'update_busyo_cd'=>'更新部署コード'
    ];


$csv_header = array_merge($csv_kihon_header,$csv_daichou_header);

  //建管
  //$tmp = "daichou_dogen_${cd}_";
  $csvFileName =$csvDirName .'DT_'. date("Ymd_His").'.csv';
  $fp = fopen($csvFileName,'w');
  mb_convert_variables('SJIS', 'UTF-8', $csv_header);
  fputcsv($fp,(array)$csv_header);
  for ($i=0;$i<count($data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      $tmp[] = $data[$i][$key];
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}
