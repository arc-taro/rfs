<?php

function getForOutDataDY($db,$cd) {

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
,dy.daityou_no
,dy.genkyou_nendo
,dy.genkyou_nendo_yyyy
,dy.genkyou_no
,dy.bouten_kanri_no
,dy.bikou_kouhou
,dy.hekikou_kou
,dy.hekikou_tei
,dy.hekimen_koubai
,dy.ichi_from_center
,dy.sekou_company
,dy.sekkei_consul
,dy.zumen_umu
,dy.kouzou_sekisansyo_umu
,dy.cad_umu
,dy.tuukoudeme_dt1
,dy.naiyou1
,dy.tuukoudeme_dt2
,dy.naiyou2
,dy.tuukoudeme_dt3
,dy.naiyou3
,dy.tokki_jikou
,dy.haishi_dt_ryuu
,dy.bikou
,dy.kyoutsuu1
,dy.kyoutsuu2
,dy.kyoutsuu3
,dy.dokuji1
,dy.dokuji2
,dy.dokuji3
,dy.create_dt
,dy.create_account
,dy.update_dt
,dy.update_account
,dy.update_account_nm
,dy.update_busyo_cd
FROM
s_tmp shisetsu 
LEFT JOIN rfs_t_daichou_dy dy 
  ON shisetsu.sno = dy.sno 
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

function outputCSVDY($data,$csvDirName,$csv_kihon_header) {
  $csv_daichou_header = [
    'daityou_no'=>'台帳番号'
    ,'genkyou_nendo'=>'現況年度'
    ,'genkyou_nendo_yyyy'=>'現況年度'
    ,'genkyou_no'=>'現況番号'
    ,'bouten_kanri_no'=>'防点管理番号'
    ,'bikou_kouhou'=>'備考'
    ,'hekikou_kou'=>'壁高 高'
    ,'hekikou_tei'=>'壁高 低'
    ,'hekimen_koubai'=>'壁面勾配'
    ,'ichi_from_center'=>'中央からの位置'
    ,'sekou_company'=>'施工会社'
    ,'sekkei_consul'=>'設計コンサル'
    ,'zumen_umu'=>'図面有無'
    ,'kouzou_sekisansyo_umu'=>'後続積算書有無'
    ,'cad_umu'=>'CAD有無'
    ,'tuukoudeme_dt1'=>'通行止めデータ1'
    ,'naiyou1'=>'内容1'
    ,'tuukoudeme_dt2'=>'通行止めデータ2'
    ,'naiyou2'=>'内容2'
    ,'tuukoudeme_dt3'=>'通行止めデータ3'
    ,'naiyou3'=>'内容3'
    ,'tokki_jikou'=>'特記事項'
    ,'haishi_dt_ryuu'=>'廃止理由'
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
  $csvFileName =$csvDirName .'DY_'. date("Ymd_His").'.csv';
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
