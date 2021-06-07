<?php
function getForOutDataJD($db,$cd) {

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
  ,jd.kousa_tanro_cd
  ,kousa_tanro.kousa_tanro
  ,jd.kousa_rosen
  ,jd.rosen_genkyou
  ,jd.keishiki_cd
  ,keishiki.keishiki
  ,jd.kiki_syu_cd
  ,kiki_syu.kiki_syu
  ,jd.koukyou_tandoku_cd
  ,koukyou_tandoku.koukyou_tandoku
  ,jd.hyouji_shiyou_cd
  ,hyouji_shiyou.hyouji_shiyou
  ,jd.maker_nm
  ,jd.secchi_gyousya
  ,jd.tk_kaisen_syu
  ,jd.tk_kaisen_kyori
  ,jd.tk_kaisen_id
  ,jd.tk_kaisen_kyakuban
  ,jd.tk_setsuzoku_moto
  ,jd.tk_setsuzoku_saki
  ,jd.tk_getsugaku
  ,jd.tk_waribiki
  ,jd.d_hokuden_kyakuban
  ,jd.d_keiyaku_houshiki
  ,jd.d_kaisen_id
  ,jd.d_kaisen_kyakuban
  ,jd.d_hikikomi
  ,jd.d_denki_dai
  ,jd.d_denki_ryou
  ,jd.bikou
  ,jd.kyoutsuu1
  ,jd.kyoutsuu2
  ,jd.kyoutsuu3
  ,jd.dokuji1
  ,jd.dokuji2
  ,jd.dokuji3
  ,jd.create_dt
  ,jd.create_account
  ,jd.update_dt
  ,jd.update_account
  ,jd.update_account_nm
  ,jd.update_busyo_cd
  FROM
  s_tmp shisetsu 
  LEFT JOIN rfs_t_daichou_jd jd 
    ON shisetsu.sno = jd.sno 
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
    ON jd.kousa_tanro_cd = kousa_tanro.kousa_tanro_cd 
  LEFT JOIN rfs_m_keishiki keishiki 
    ON jd.keishiki_cd = keishiki.keishiki_cd
  LEFT JOIN rfs_m_kiki_syu kiki_syu  
    ON jd.kiki_syu_cd = kiki_syu.kiki_syu_cd
  LEFT JOIN rfs_m_koukyou_tandoku koukyou_tandoku  
    ON jd.koukyou_tandoku_cd = koukyou_tandoku.koukyou_tandoku_cd
  LEFT JOIN rfs_m_hyouji_shiyou hyouji_shiyou  
    ON jd.hyouji_shiyou_cd = hyouji_shiyou.hyouji_shiyou_cd
  ORDER BY syucchoujo_cd
EOF;
  //echo $sql;
  return $db->query($sql);
}

function outputCSVJD($data,$csvDirName,$csv_kihon_header) {
  $csv_daichou_header = [
    'kousa_tanro_cd'=>'交差単路コード'
    ,'kousa_tanro'=>'交差単路'
    ,'kousa_rosen'=>'交差路線'
    ,'rosen_genkyou'=>'路線現況'
    ,'keishiki_cd'=>'形式コード'
    ,'keishiki'=>'形式'
    ,'kiki_syu_cd'=>'機器種コード'
    ,'kiki_syu'=>'機器種'
    ,'koukyou_tandoku_cd'=>'公共単独コード'
    ,'koukyou_tandoku'=>'公共単独'
    ,'hyouji_shiyou_cd'=>'表示仕様コード'
    ,'hyouji_shiyou'=>'表示仕様'
    ,'maker_nm'=>'メーカー名'
    ,'secchi_gyousya'=>'設置業者'
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
  // 建管
  //$tmp="daichou_dogen_${cd}_";
  $csvFileName =$csvDirName.'JD_'. date("Ymd_His").'.csv';
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
