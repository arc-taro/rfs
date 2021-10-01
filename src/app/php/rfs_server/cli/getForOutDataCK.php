<?php

function getForOutDataCK($db,$cd) {

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
,ck.genkyou_ichi_no
,ck.yosan_himoku
,ck.jigyouhi
,ck.syadou_hosou_kousei
,ck.syadou_hosou_menseki
,ck.hodou_hosou_kousei
,ck.hodou_hosou_menseki
,ck.norimen_ryokuchi_menseki
,ck.tyuusyadaisuu_oogata
,ck.tyuusyadaisuu_hutsuu
,ck.toire_katashiki_cd
,toire_katashiki.toire_katashiki
,ck.toire_suigen
,ck.kenjousya_dai
,ck.kenjousya_syou
,ck.shinsyousya_dai
,ck.shinsyousya_syou
,ck.riyou_kanou_kikan
,ck.syoumeitou_pole_kikaku
,ck.ramp_syu
,ck.syoumei_dengen_cd
,syoumei_dengen.syoumei_dengen
,ck.syoumei_hashira_num
,ck.syoumei_kyuu_num
,ck.azumaya
, CASE ck.azumaya 
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END azumaya_umu
,ck.kousyuu_tel
, CASE ck.kousyuu_tel
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END kousyuu_tel_umu
,ck.bench
, CASE ck.bench
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END bench_umu
,ck.tbl
, CASE ck.tbl
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END tbl_umu
,ck.clock
, CASE ck.clock
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END clock_umu
,ck.syokuju_kouboku
,ck.syokuju_tyuuteiboku
,ck.annai_hyoushiki
, CASE ck.annai_hyoushiki
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END annai_hyoushiki_umu
,ck.kankou_annaiban
, CASE ck.kankou_annaiban
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END kankou_annaiban_umu
,ck.keikan_kankoushisetsu
,ck.barrierfree_seibi
,ck.haishi_riyuu
,ck.tk_kaisen_syu
,ck.tk_kaisen_kyori
,ck.tk_kaisen_id
,ck.tk_kaisen_kyakuban
,ck.tk_setsuzoku_moto
,ck.tk_setsuzoku_saki
,ck.tk_getsugaku
,ck.tk_waribiki
,ck.d_hokuden_kyakuban
,ck.d_keiyaku_houshiki
,ck.d_kaisen_id
,ck.d_kaisen_kyakuban
,ck.d_hikikomi
,ck.d_denki_dai
,ck.d_denki_ryou
,ck.bikou
,ck.kyoutsuu1
,ck.kyoutsuu2
,ck.kyoutsuu3
,ck.dokuji1
,ck.dokuji2
,ck.dokuji3
,ck.create_dt
,ck.create_account
,ck.update_dt
,ck.update_account
,ck.update_account_nm
,ck.update_busyo_cd
FROM
s_tmp shisetsu 
LEFT JOIN rfs_t_daichou_ck ck 
  ON shisetsu.sno = ck.sno 
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
LEFT JOIN rfs_m_toire_katashiki toire_katashiki
  ON ck.toire_katashiki_cd = toire_katashiki.toire_katashiki_cd
LEFT JOIN rfs_m_syoumei_dengen syoumei_dengen
  ON ck.syoumei_dengen_cd = syoumei_dengen.syoumei_dengen_cd
order by syucchoujo_cd
EOF;
//AND shisetsu.shisetsu_kbn = $shisetsu_kbn_cd

  //echo $sql;
  return $db->query($sql);
}

function outputCSVCK($data,$csvDirName,$csv_kihon_header) {
  $csv_daichou_header = [
    'genkyou_ichi_no'=>'現況位置番号'
   ,'yosan_himoku'=>'予算費目'
   ,'jigyouhi'=>'事業費'
   ,'syadou_hosou_kousei'=>'車道舗装構成'
   ,'syadou_hosou_menseki'=>'車道舗装面積'
   ,'hodou_hosou_kousei'=>'歩道舗装構成'
   ,'hodou_hosou_menseki'=>'歩道舗装面積'
   ,'norimen_ryokuchi_menseki'=>'法面・緑地面積'
   ,'tyuusyadaisuu_oogata'=>'駐車台数大型車'
   ,'tyuusyadaisuu_hutsuu'=>'駐車台数普通車'
   ,'toire_katashiki_cd'=>'トイレ型式'
   ,'toire_katashiki'=>'トイレ型式'
   ,'toire_suigen'=>'トイレ水源'
   ,'kenjousya_dai'=>'健常者大'
   ,'kenjousya_syou'=>'健常者小'
   ,'shinsyousya_dai'=>'身障者大'
   ,'shinsyousya_syou'=>'身障者小'
   ,'riyou_kanou_kikan'=>'利用可能期間'
   ,'syoumeitou_pole_kikaku'=>'照明等ポール規格'
   ,'ramp_syu'=>'ランプ種類'
   ,'syoumei_dengen_cd'=>'照明電源コード'
   ,'syoumei_dengen'=>'照明電源'
   ,'syoumei_hashira_num'=>'照明柱数'
   ,'syoumei_kyuu_num'=>'照明球数'
   ,'azumaya'=>'あずまや'
   ,'azumaya_umu'=>'あずまや有無'
   ,'kousyuu_tel'=>'公衆電話'
   ,'kousyuu_tel_umu'=>'公衆電話有無'
   ,'bench'=>'ベンチ'
   ,'bench_umu'=>'ベンチ有無'
   ,'tbl'=>'テーブル'
   ,'tbl_umu'=>'テーブル有無'
   ,'clock'=>'時計'
   ,'clock_umu'=>'時計有無'
   ,'syokuju_kouboku'=>'樹木 高木'
   ,'syokuju_tyuuteiboku'=>'樹木 中低木'
   ,'annai_hyoushiki'=>'案内標識'
   ,'annai_hyoushiki_umu'=>'案内標識有無'
   ,'kankou_annaiban'=>'観光案内版'
   ,'kankou_annaiban_umu'=>'観光案内版有無'
   ,'keikan_kankoushisetsu'=>'景観観光施設'
   ,'barrierfree_seibi'=>'バリアフリー設備'
   ,'haishi_riyuu'=>'廃止理由'
   ,'tk_kaisen_syu'=>'回線種別'
   ,'tk_kaisen_kyori'=>'距離'
   ,'tk_kaisen_id'=>'回線ID(電話番号)'
   ,'tk_kaisen_kyakuban'=>'回線客番号'
   ,'tk_setsuzoku_moto'=>'接続元'
   ,'tk_setsuzoku_saki'=>'接続先'
   ,'tk_getsugaku'=>'月額料金'
   ,'tk_waribiki'=>'割引料金'
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
  $csvFileName =$csvDirName .'CK_'. date("Ymd_His").'.csv';
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
