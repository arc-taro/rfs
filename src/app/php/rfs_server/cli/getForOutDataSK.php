<?php

function getForOutDataSK($db,$cd) {

  $sql=<<<EOF
  WITH s_tmp AS (
    SELECT * FROM rfs_m_shisetsu WHERE shisetsu_kbn=$cd
  ), kouboku as (
    SELECT * FROM rfs_m_tree where jumoku_syu = 1
  ), tyuuteiboku as (
    SELECT * FROM rfs_m_tree where jumoku_syu = 2
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
,sk.bangou
,sk.kbn_c_cd
,kbn_c.kbn_c
,sk.ichi_c_cd
,ichi_c.ichi_c
,sk.tyuusya_kouen_tou
,sk.akimasu_menseki
,sk.kouboku_jumoku_cd1
,t1.jumoku_nm as kouboku1
,sk.kouboku_num1
,sk.kouboku_jumoku_cd2
,t2.jumoku_nm as kouboku2
,sk.kouboku_num2
,sk.kouboku_jumoku_cd3
,t3.jumoku_nm as kouboku3
,sk.kouboku_num3
,sk.kouboku_jumoku_cd4
,t4.jumoku_nm as kouboku4
,sk.kouboku_num4
,sk.kouboku_jumoku_cd5
,t5.jumoku_nm as kouboku5
,sk.kouboku_num5
,sk.kouboku_jumoku_cd6
,t6.jumoku_nm as kouboku6
,sk.kouboku_num6
,sk.kouboku_jumoku_cd7
,t7.jumoku_nm as kouboku7
,sk.kouboku_num7
,sk.kouboku_jumoku_cd8
,t8.jumoku_nm as kouboku8
,sk.kouboku_num8
,sk.kouboku_jumoku_cd9
,t9.jumoku_nm as kouboku9
,sk.kouboku_num9
,sk.kouboku_jumoku_cd10
,t10.jumoku_nm as kouboku10
,sk.kouboku_num10
,sk.tyuuteiboku_jumoku_cd1
,s1.jumoku_nm as tyuuteiboku1
,sk.tyuuteiboku_num1
,sk.tyuuteiboku_jumoku_cd2
,s2.jumoku_nm as tyuuteiboku2
,sk.tyuuteiboku_num2
,sk.tyuuteiboku_jumoku_cd3
,s3.jumoku_nm as tyuuteiboku3
,sk.tyuuteiboku_num3
,sk.tyuuteiboku_jumoku_cd4
,s4.jumoku_nm as tyuuteiboku4
,sk.tyuuteiboku_num4
,sk.tyuuteiboku_jumoku_cd5
,s5.jumoku_nm as tyuuteiboku5
,sk.tyuuteiboku_num5
,sk.tyuuteiboku_jumoku_cd6
,s6.jumoku_nm as tyuuteiboku6
,sk.tyuuteiboku_num6
,sk.tyuuteiboku_jumoku_cd7
,s7.jumoku_nm as tyuuteiboku7
,sk.tyuuteiboku_num7
,sk.tyuuteiboku_jumoku_cd8
,s8.jumoku_nm as tyuuteiboku8
,sk.tyuuteiboku_num8
,sk.tyuuteiboku_jumoku_cd9
,s9.jumoku_nm as tyuuteiboku9
,sk.tyuuteiboku_num9
,sk.tyuuteiboku_jumoku_cd10
,s10.jumoku_nm as tyuuteiboku10
,sk.tyuuteiboku_num10
,sk.bikou
,sk.kyoutsuu1
,sk.kyoutsuu2
,sk.kyoutsuu3
,sk.dokuji1
,sk.dokuji2
,sk.dokuji3
,sk.create_dt
,sk.create_account
,sk.update_dt
,sk.update_account
,sk.update_account_nm
,sk.update_busyo_cd
FROM
s_tmp shisetsu 
LEFT JOIN rfs_t_daichou_sk sk 
  ON shisetsu.sno = sk.sno 
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
LEFT JOIN rfs_m_ichi_c ichi_c
  ON sk.ichi_c_cd = ichi_c.ichi_c_cd
LEFT JOIN rfs_m_kbn_c kbn_c
  ON sk.kbn_c_cd = kbn_c.kbn_c_cd
LEFT JOIN kouboku t1
  ON t1.jumoku_cd = sk.kouboku_jumoku_cd1
LEFT JOIN kouboku t2
  ON t2.jumoku_cd = sk.kouboku_jumoku_cd2
LEFT JOIN kouboku t3
  ON t3.jumoku_cd = sk.kouboku_jumoku_cd3
LEFT JOIN kouboku t4
  ON t4.jumoku_cd = sk.kouboku_jumoku_cd4
LEFT JOIN kouboku t5
  ON t5.jumoku_cd = sk.kouboku_jumoku_cd5
LEFT JOIN kouboku t6
  ON t6.jumoku_cd = sk.kouboku_jumoku_cd6
LEFT JOIN kouboku t7
  ON t7.jumoku_cd = sk.kouboku_jumoku_cd7
LEFT JOIN kouboku t8
  ON t8.jumoku_cd = sk.kouboku_jumoku_cd8
LEFT JOIN kouboku t9
  ON t9.jumoku_cd = sk.kouboku_jumoku_cd9
LEFT JOIN kouboku t10
  ON t10.jumoku_cd = sk.kouboku_jumoku_cd10
LEFT JOIN tyuuteiboku s1
  ON s1.jumoku_cd = sk.tyuuteiboku_jumoku_cd1
LEFT JOIN tyuuteiboku s2
  ON s2.jumoku_cd = sk.tyuuteiboku_jumoku_cd2
LEFT JOIN tyuuteiboku s3
  ON s3.jumoku_cd = sk.tyuuteiboku_jumoku_cd3
LEFT JOIN tyuuteiboku s4
  ON s4.jumoku_cd = sk.tyuuteiboku_jumoku_cd4
LEFT JOIN tyuuteiboku s5
  ON s5.jumoku_cd = sk.tyuuteiboku_jumoku_cd5
LEFT JOIN tyuuteiboku s6
  ON s6.jumoku_cd = sk.tyuuteiboku_jumoku_cd6
LEFT JOIN tyuuteiboku s7
  ON s7.jumoku_cd = sk.tyuuteiboku_jumoku_cd7
LEFT JOIN tyuuteiboku s8
  ON s8.jumoku_cd = sk.tyuuteiboku_jumoku_cd8
LEFT JOIN tyuuteiboku s9
  ON s9.jumoku_cd = sk.tyuuteiboku_jumoku_cd9
LEFT JOIN tyuuteiboku s10
  ON s10.jumoku_cd = sk.tyuuteiboku_jumoku_cd10
order by syucchoujo_cd
EOF;
//AND shisetsu.shisetsu_kbn = $shisetsu_kbn_cd

  //echo $sql;
  return $db->query($sql);
}

function outputCSVSK($data,$csvDirName,$csv_kihon_header) {
  $csv_daichou_header = [
    'bangou'=>'番号'
   ,'kbn_c_cd'=>'区分Cコード'
   ,'kbn_c'=>'区分C'
   ,'ichi_c_cd'=>'位置Cコード'
   ,'ichi_c'=>'位置C'
   ,'tyuusya_kouen_tou'=>'駐車公園等'
   ,'akimasu_menseki'=>'空桝面積'
   ,'kouboku_jumoku_cd1'=>'高木 樹種コード'
   ,'kouboku1'=>'高木 樹種名称'
   ,'kouboku_num1'=>'本数'
   ,'kouboku_jumoku_cd2'=>'高木 樹種コード'
   ,'kouboku2'=>'高木 樹種名称'
   ,'kouboku_num2'=>'本数'
   ,'kouboku_jumoku_cd3'=>'高木 樹種コード'
   ,'kouboku3'=>'高木 樹種名称'
   ,'kouboku_num3'=>'本数'
   ,'kouboku_jumoku_cd4'=>'高木 樹種コード'
   ,'kouboku4'=>'高木 樹種名称'
   ,'kouboku_num4'=>'本数'
   ,'kouboku_jumoku_cd5'=>'高木 樹種コード'
   ,'kouboku5'=>'高木 樹種名称'
   ,'kouboku_num5'=>'本数'
   ,'kouboku_jumoku_cd6'=>'高木 樹種コード'
   ,'kouboku6'=>'高木 樹種名称'
   ,'kouboku_num6'=>'本数'
   ,'kouboku_jumoku_cd7'=>'高木 樹種コード'
   ,'kouboku7'=>'高木 樹種名称'
   ,'kouboku_num7'=>'本数'
   ,'kouboku_jumoku_cd8'=>'高木 樹種コード'
   ,'kouboku8'=>'高木 樹種名称'
   ,'kouboku_num8'=>'本数'
   ,'kouboku_jumoku_cd9'=>'高木 樹種コード'
   ,'kouboku9'=>'高木 樹種名称'
   ,'kouboku_num9'=>'本数'
   ,'kouboku_jumoku_cd10'=>'高木 樹種コード'
   ,'kouboku10'=>'高木 樹種名称'
   ,'kouboku_num10'=>'本数'
   ,'tyuuteiboku_jumoku_cd1'=>'中低木 樹種コード'
   ,'tyuuteiboku1'=>'中低木 樹種名称'
   ,'tyuuteiboku_num1'=>'本数'
   ,'tyuuteiboku_jumoku_cd2'=>'中低木 樹種コード'
   ,'tyuuteiboku2'=>'中低木 樹種名称'
   ,'tyuuteiboku_num2'=>'本数'
   ,'tyuuteiboku_jumoku_cd3'=>'中低木 樹種コード'
   ,'tyuuteiboku3'=>'中低木 樹種名称'
   ,'tyuuteiboku_num3'=>'本数'
   ,'tyuuteiboku_jumoku_cd4'=>'中低木 樹種コード'
   ,'tyuuteiboku4'=>'中低木 樹種名称'
   ,'tyuuteiboku_num4'=>'本数'
   ,'tyuuteiboku_jumoku_cd5'=>'中低木 樹種コード'
   ,'tyuuteiboku5'=>'中低木 樹種名称'
   ,'tyuuteiboku_num5'=>'本数'
   ,'tyuuteiboku_jumoku_cd6'=>'中低木 樹種コード'
   ,'tyuuteiboku6'=>'中低木 樹種名称'
   ,'tyuuteiboku_num6'=>'本数'
   ,'tyuuteiboku_jumoku_cd7'=>'中低木 樹種コード'
   ,'tyuuteiboku7'=>'中低木 樹種名称'
   ,'tyuuteiboku_num7'=>'本数'
   ,'tyuuteiboku_jumoku_cd8'=>'中低木 樹種コード'
   ,'tyuuteiboku8'=>'中低木 樹種名称'
   ,'tyuuteiboku_num8'=>'本数'
   ,'tyuuteiboku_jumoku_cd9'=>'中低木 樹種コード'
   ,'tyuuteiboku9'=>'中低木 樹種名称'
   ,'tyuuteiboku_num9'=>'本数'
   ,'tyuuteiboku_jumoku_cd10'=>'中低木 樹種コード'
   ,'tyuuteiboku10'=>'中低木 樹種名称'
   ,'tyuuteiboku_num10'=>'本数'
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
  $csvFileName =$csvDirName .'SK_'. date("Ymd_His").'.csv';
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
