<?php
ini_set('memory_limit', '5000M');
function getForOutDataSS($db,$cd) {

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

function outputCSVSS($data,$csvDirName,$csv_kihon_header) {
  $csv_daichou_header = [
  'toutyuu_no'=>'灯柱番号'
  ,'secchi_bui'=>'設置部位'
  ,'kousa_rosen'=>'交差路線'
  ,'shichuu_kou'=>'支柱高'
  ,'haba'=>'幅'
  ,'pole_kikaku_cd'=>'ポール規格コード'
  ,'pole_kikaku'=>'規格'
  ,'pole_kikaku_bikou'=>'備考'
  ,'syadou_ramp1'=>'車道①／ランプ形式'
  ,'syadou_syoutou1'=>'消灯'
  ,'syadou_ramp2'=>'車道②／ランプ形式'
  ,'syadou_syoutou2'=>'消灯'
  ,'hodou_ramp1'=>'歩道①／ランプ形式'
  ,'hodou_syoutou1'=>'消灯'
  ,'hodou_ramp2'=>'歩道②／ランプ形式'
  ,'hodou_syoutou2'=>'消灯'
  ,'tougu_secchi'=>'灯具設置年度'
  ,'tougu_secchi_yyyy'=>'灯具設置年度（西暦）'
  ,'ramp_num'=>'ランプ数'
  ,'tyoukou_umu_cd'=>'調光コード'
  ,'tyoukou_umu'=>'調光'
  ,'timer_umu_cd'=>'タイマーコード'
  ,'timer_umu'=>'タイマー'
  ,'syoutou_cd'=>'消灯コード'
  ,'syoutou'=>'消灯'
  ,'secchi_gyousya'=>'設置業者'
  ,'hodou_syoumei_payer'=>'歩道照明支払者'
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
  ,'create_dt'=>'作成日'
  ,'create_account'=>'作成アカウント'
  ,'update_dt'=>'更新日'
  ,'update_account'=>'更新アカウント'
  ,'update_account_nm'=>'更新アカウント名'
  ,'update_busyo_cd'=>'更新部署コード'
  ];

  $csv_header = array_merge($csv_kihon_header,$csv_daichou_header);
  // 建管
  //$tmp="daichou_dogen_${cd}_";
  $csvFileName =$csvDirName.'SS_'. date("Ymd_His").'.csv';
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
