<?php

function getForOutDataDH($db,$cd) {

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
, dh.kousa_tanro_cd
, kousa_tanro.kousa_tanro
, dh.syurui_bangou
, dh.kousa_rosen
, dh.hyoushiki_syu_cd
, hyoushiki_syu.hyoushiki_syu
, dh.ryuui_jikou
, dh.ban_sunpou
, dh.ban_moji_size
, dh.ban_zaishitsu
, dh.ban_hansya_syoumei
, dh.ban_no_hyouki_umu
, CASE dh.ban_no_hyouki_umu 
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END ban_no_hyouki_umu_str
, dh.ban_tagengo_umu
, CASE dh.ban_tagengo_umu 
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END ban_tagengo_umu_str
, dh.ban_kyouka1
, dh.ban_kyouka2
, dh.shichuu_houshiki_cd
, shichuu_houshiki.shichuu_houshiki
, dh.shichuu_houshiki_bikou
, dh.shichuu_kikaku_cd
, shichuu_kikaku.shichuu_kikaku
, dh.shichuu_kikaku_bikou
, dh.shichuu_tosou
, dh.shichuu_kiso_keishiki
, dh.shichuu_sunpou
, dh.hyoushikityuu_no
, dh.d_hokuden_kyakuban
, dh.d_keiyaku_houshiki
, dh.d_kaisen_id
, dh.d_kaisen_kyakuban
, dh.d_hikikomi
, dh.d_denki_dai
, dh.d_denki_ryou
, dh.bikou
, dh.kyoutsuu1
, dh.kyoutsuu2
, dh.kyoutsuu3
, dh.dokuji1
, dh.dokuji2
, dh.dokuji3
, dh.create_dt
, dh.create_account
, dh.update_dt
, dh.update_account
, dh.update_account_nm 
, dh.update_busyo_cd
FROM
s_tmp shisetsu 
LEFT JOIN rfs_t_daichou_dh dh 
  ON shisetsu.sno = dh.sno 
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
LEFT JOIN rfs_m_kousa_tanro kousa_tanro 
  ON dh.kousa_tanro_cd = kousa_tanro.kousa_tanro_cd 
LEFT JOIN rfs_m_hyoushiki_syu hyoushiki_syu 
  ON dh.hyoushiki_syu_cd = hyoushiki_syu.hyoushiki_syu_cd 
LEFT JOIN rfs_m_shichuu_houshiki shichuu_houshiki 
  ON dh.shichuu_houshiki_cd = shichuu_houshiki.shichuu_houshiki_cd 
LEFT JOIN rfs_m_shichuu_kikaku shichuu_kikaku 
  ON dh.shichuu_kikaku_cd = shichuu_kikaku.shichuu_kikaku_cd 
order by syucchoujo_cd
EOF;
//AND shisetsu.shisetsu_kbn = $shisetsu_kbn_cd

  //echo $sql;
  return $db->query($sql);
}

function outputCSVDH($data,$csvDirName,$csv_kihon_header) {
  $csv_daichou_header = [
    'kousa_tanro_cd'=>'交差単路コード'
   ,'kousa_tanro'=>'交差単路'
   ,'syurui_bangou'=>'種類番号'
   ,'kousa_rosen'=>'交差路線'
   ,'hyoushiki_syu_cd'=>'標識種別コード'
   ,'hyoushiki_syu'=>'標識種別'
   ,'ryuui_jikou'=>'標識種別に関する留意事項'
   ,'ban_sunpou'=>'板形状_板寸法'
   ,'ban_moji_size'=>'板形状_文字サイズ'
   ,'ban_zaishitsu'=>'板形状_板の材質'
   ,'ban_hansya_syoumei'=>'板形状_反射・照明'
   ,'ban_no_hyouki_umu'=>'板形状_番号表記有無'
   ,'ban_no_hyouki_umu_str'=>'板形状_番号表記有無'
   ,'ban_tagengo_umu'=>'板形状_多言語有無'
   ,'ban_tagengo_umu_str'=>'板形状_多言語有無'
   ,'ban_kyouka1'=>'板形状_共架標識1'
   ,'ban_kyouka2'=>'板形状_共架標識2'
   ,'shichuu_houshiki_cd'=>'支柱方式コード'
   ,'shichuu_houshiki'=>'支柱形状_支柱方式'
   ,'shichuu_houshiki_bikou'=>'支柱形状_支柱方式備考'
   ,'shichuu_kikaku_cd'=>'支柱規格コード'
   ,'shichuu_kikaku'=>'支柱形状_支柱規格'
   ,'shichuu_kikaku_bikou'=>'支柱形状_支柱規格備考'
   ,'shichuu_tosou'=>'支柱形状_支柱塗装'
   ,'shichuu_kiso_keishiki'=>'支柱形状_基礎形式'
   ,'shichuu_sunpou'=>'支柱形状_基礎寸法'
   ,'hyoushikityuu_no'=>'標識柱NO'
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
  $csvFileName =$csvDirName .'DH_'. date("Ymd_His").'.csv';
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
