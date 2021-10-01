<?php

function getForOutDataTS($db,$cd) {

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
  WHEN 2 THEN '一部休止' 
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
,ts.dj_old_id
,ts.dj_syadou_haba
,ts.dj_rokata_haba
,ts.dj_hodou_haba
,ts.dj_juudan_koubai
,ts.dj_kyokusen_hankei
,ts.dj_kouzou
,ts.dj_dourojoukyou_bikou
,ts.endou_syu_cd
,ts.e_chimoku
,ts.e_syokusei_sta
,ts.e_haisui_syori
,ts.j_maker_nm
,ts.j_enkaku_sousa
,ts.jigyou_nm_cd
,ts.j_jigyou_cost
,ts.j_bikou
,ts.c_kousetsu
,ts.c_gaikion
,ts.c_roon
,ts.c_romen_suibun
,ts.c_sonota
,ts.c_sonota_input
,ts.t_youryou_chijou
,ts.t_youryou_chika
,ts.k_all_kadou_hour
,ts.k_naiyou
,ts.y_kouka_entyou
,ts.y_kouka_fukuin
,ts.d_hokuden_kyakuban
,ts.d_keiyaku_houshiki
,ts.d_kaisen_id
,ts.d_kaisen_kyakuban
,ts.d_hikikomi
,ts.d_denki_dai
,ts.d_denki_ryou
,ts.bikou
,ts.kyoutsuu1
,ts.kyoutsuu2
,ts.kyoutsuu3
,ts.dokuji1
,ts.dokuji2
,ts.dokuji3
,ts.create_dt
,ts.create_account
,ts.update_dt
,ts.update_account
,ts.update_account_nm
,ts.update_busyo_cd
FROM
s_tmp shisetsu 
LEFT JOIN rfs_t_daichou_ts ts 
  ON shisetsu.sno = ts.sno 
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

function outputCSVTS($data,$csvDirName,$csv_kihon_header) {
  $csv_daichou_header = [
    'dj_old_id'=>'旧電算処理ID'
    ,'dj_syadou_haba'=>'車道幅'
    ,'dj_rokata_haba'=>'路肩幅'
    ,'dj_hodou_haba'=>'歩道幅'
    ,'dj_juudan_koubai'=>'縦断勾配'
    ,'dj_kyokusen_hankei'=>'曲線半径'
    ,'dj_kouzou'=>'後続'
    ,'dj_dourojoukyou_bikou'=>'道路状況備考'
    ,'endou_syu_cd'=>'沿道種コード'
    ,'e_chimoku'=>'地目'
    ,'e_syokusei_sta'=>'植生'
    ,'e_haisui_syori'=>'排水処理'
    ,'j_maker_nm'=>'メーカー名'
    ,'j_enkaku_sousa'=>'遠隔操作'
    ,'jigyou_nm_cd'=>'事業名コード'
    ,'j_jigyou_cost'=>'事業コスト'
    ,'j_bikou'=>'備考'
    ,'c_kousetsu'=>'降雪'
    ,'c_gaikion'=>'外気温'
    ,'c_roon'=>'路温'
    ,'c_romen_suibun'=>'路面水分'
    ,'c_sonota'=>'その他'
    ,'c_sonota_input'=>'その他'
    ,'t_youryou_chijou'=>'地上容量'
    ,'t_youryou_chika'=>'地下容量'
    ,'k_all_kadou_hour'=>'全稼働時間'
    ,'k_naiyou'=>'内容'
    ,'y_kouka_entyou'=>'高架延長'
    ,'y_kouka_fukuin'=>'高架幅員'
    ,'d_hokuden_kyakuban'=>'電気_ほくでん客番'
    ,'d_keiyaku_houshiki'=>'電気_契約方式'
    ,'d_kaisen_id'=>'電気_回線ID(電話番号)'
    ,'d_kaisen_kyakuban'=>'電気_回線客番号'
    ,'d_hikikomi'=>'電気_北電電柱引込'
    ,'d_denki_dai'=>'電気_電気代'
    ,'d_denki_ryou'=>'電気_電気量'
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
  $csvFileName =$csvDirName .'TS_'. date("Ymd_His").'.csv';
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
