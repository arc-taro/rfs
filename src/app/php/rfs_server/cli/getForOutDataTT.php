<?php

function getForOutDataTT($db,$cd) {

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
,tt.toukyuu_cd
,toukyuu.toukyuu
,tt.shisetsu_renzoku_cd
,shisetsu_renzoku.shisetsu_renzoku
,tt.kussaku
,tt.hekimen_kbn_cd
,hekimen_kbn.hekimen_kbn
,tt.romen_syu
, CASE tt.romen_syu 
  WHEN 0 THEN '不明' 
  WHEN 1 THEN 'アスファルト' 
  WHEN 2 THEN 'コンクリート' 
  ELSE '' 
  END romen_syu_str
,tt.tenkenkou
, CASE tt.tenkenkou
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END tenkenkou_umu
,tt.kanshi_camera
, CASE tt.kanshi_camera
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END kanshi_camera_umu
,tt.kanki_shisetsu_cd
,kanki_shisetsu.kanki_shisetsu
,tt.bousuiban
, CASE tt.bousuiban
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END bousuiban_umu
,tt.secchi_kasyo_juudan_cd
,secchi_kasyo_kbn1.secchi_kasyo as secchi_kasyo_juudan
,tt.secchi_kasyo_oudan_cd
,secchi_kasyo_kbn2.secchi_kasyo as secchi_kasyo_oudan
,tt.syoumei_shisetsu_cd
,syoumei_shisetsu.syoumei_shisetsu
,tt.syoumei_kisuu
,tt.tuuhou_souchi_cd
,tuuhou_souchi.tuuhou_souchi
,tt.push_button_num
,tt.hijou_tel_num
,tt.hijou_keihou_souchi_cd
,hijou_keihou_souchi.hijou_keihou_souchi
,tt.keihou_hyoujiban_num
,tt.tenmetsutou_num
,tt.syouka_setsubi_cd
,syouka_setsubi.syouka_setsubi
,tt.syoukaki_num
,tt.syoukasen
, CASE tt.syoukasen 
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END syoukasen_umu
,tt.sonota_setsubi_cd
,sonota_setsubi.sonota_setsubi
,tt.yuudou_hyoujiban_num
,tt.kasai_kenchiki
, CASE tt.kasai_kenchiki 
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END kasai_kenchiki_umu
,tt.musen_tsuushin_setsubi
, CASE tt.musen_tsuushin_setsubi 
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END musen_tsuushin_setsubi_umu
,tt.radio_re_housou_setsubi
, CASE tt.radio_re_housou_setsubi
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END radio_re_housou_setsubi_umu
,tt.warikomi_housou
, CASE tt.warikomi_housou
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END warikomi_housou_umu
,tt.radio_re_musenkyoka_num
,tt.musen_kyoka_dt
,tt.humei
,tt.jieisen_denki
, CASE tt.jieisen_denki 
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END jieisen_denki_umu
,tt.jieisen_tsuushin
, CASE tt.jieisen_tsuushin 
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END jieisen_tsuushin_umu
,tt.tk_kaisen_syu
,tt.tk_kaisen_kyori
,tt.tk_kaisen_id
,tt.tk_kaisen_kyakuban
,tt.tk_setsuzoku_moto
,tt.tk_setsuzoku_saki
,tt.tk_getsugaku
,tt.tk_waribiki
,tt.d_hokuden_kyakuban
,tt.d_keiyaku_houshiki
,tt.d_kaisen_id
,tt.d_kaisen_kyakuban
,tt.d_hikikomi
,tt.d_denki_dai
,tt.d_denki_ryou
,tt.bikou
,tt.kyoutsuu1
,tt.kyoutsuu2
,tt.kyoutsuu3
,tt.dokuji1
,tt.dokuji2
,tt.dokuji3
,tt.create_dt
,tt.create_account
,tt.update_dt
,tt.update_account
,tt.update_account_nm
,tt.update_busyo_cd
FROM
s_tmp shisetsu 
LEFT JOIN rfs_t_daichou_tt tt 
  ON shisetsu.sno = tt.sno 
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
LEFT JOIN rfs_m_toukyuu toukyuu 
  ON tt.toukyuu_cd = toukyuu.toukyuu_cd
LEFT JOIN rfs_m_shisetsu_renzoku shisetsu_renzoku 
  ON tt.shisetsu_renzoku_cd = shisetsu_renzoku.shisetsu_renzoku_cd
LEFT JOIN rfs_m_hekimen_kbn hekimen_kbn 
  ON tt.hekimen_kbn_cd = hekimen_kbn.hekimen_kbn_cd
LEFT JOIN rfs_m_kanki_shisetsu kanki_shisetsu 
  ON tt.kanki_shisetsu_cd = kanki_shisetsu.kanki_shisetsu_cd
LEFT JOIN  ( 
  SELECT
      * 
  FROM
    rfs_m_secchi_kasyo
  WHERE
    secchi_kasyo_kbn = 1
) secchi_kasyo_kbn1
  ON tt.secchi_kasyo_juudan_cd = secchi_kasyo_kbn1.secchi_kasyo_cd
LEFT JOIN  ( 
  SELECT
      * 
  FROM
    rfs_m_secchi_kasyo
  WHERE
    secchi_kasyo_kbn = 2
) secchi_kasyo_kbn2
  ON tt.secchi_kasyo_oudan_cd = secchi_kasyo_kbn2.secchi_kasyo_cd
LEFT JOIN rfs_m_syoumei_shisetsu syoumei_shisetsu 
  ON tt.syoumei_shisetsu_cd = syoumei_shisetsu.syoumei_shisetsu_cd
LEFT JOIN rfs_m_sonota_setsubi sonota_setsubi 
  ON tt.sonota_setsubi_cd = sonota_setsubi.sonota_setsubi_cd
LEFT JOIN rfs_m_syouka_setsubi syouka_setsubi 
  ON tt.syouka_setsubi_cd = syouka_setsubi.syouka_setsubi_cd
LEFT JOIN rfs_m_hijou_keihou_souchi hijou_keihou_souchi 
  ON tt.hijou_keihou_souchi_cd = hijou_keihou_souchi.hijou_keihou_souchi_cd
LEFT JOIN rfs_m_tuuhou_souchi tuuhou_souchi 
  ON tt.tuuhou_souchi_cd = tuuhou_souchi.tuuhou_souchi_cd
order by syucchoujo_cd
EOF;
//AND shisetsu.shisetsu_kbn = $shisetsu_kbn_cd

  //echo $sql;
  return $db->query($sql);
}

function outputCSVTT($data,$csvDirName,$csv_kihon_header) {
  $csv_daichou_header = [
    'toukyuu_cd'=>'トンネル等級コード'
   ,'toukyuu'=>'トンネル等級'
   ,'shisetsu_renzoku_cd'=>'施設の連続コード'
   ,'shisetsu_renzoku'=>'施設の連続'
   ,'kussaku'=>'掘削方法'
   ,'hekimen_kbn_cd'=>'壁面区分コード'
   ,'hekimen_kbn'=>'壁面区分'
   ,'romen_syu'=>'路面種別'
   ,'romen_syu_str'=>'路面種別'
   ,'tenkenkou'=>'点検口'
   ,'tenkenkou_umu'=>'点検口有無'
   ,'kanshi_camera'=>'監視カメラ'
   ,'kanshi_camera_umu'=>'監視カメラ有無'
   ,'kanki_shisetsu_cd'=>'換気施設コード'
   ,'kanki_shisetsu'=>'換気施設'
   ,'bousuiban'=>'防水板'
   ,'bousuiban_umu'=>'防水板有無'
   ,'secchi_kasyo_juudan_cd'=>'設置個所縦断コード'
   ,'secchi_kasyo_juudan'=>'設置個所縦断'
   ,'secchi_kasyo_oudan_cd'=>'設置個所横断コード'
   ,'secchi_kasyo_oudan'=>'設置個所横断'
   ,'syoumei_shisetsu_cd'=>'照明施設コード'
   ,'syoumei_shisetsu'=>'照明施設'
   ,'syoumei_kisuu'=>'照明基数'
   ,'tuuhou_souchi_cd'=>'通報装置コード'
   ,'tuuhou_souchi'=>'通報装置'
   ,'push_button_num'=>'押ボタン数'
   ,'hijou_tel_num'=>'非常電話数'
   ,'hijou_keihou_souchi_cd'=>'非常警報装置コード'
   ,'hijou_keihou_souchi'=>'非常警報装置'
   ,'keihou_hyoujiban_num'=>'警報表示数'
   ,'tenmetsutou_num'=>'点滅灯数'
   ,'syouka_setsubi_cd'=>'消化設備コード'
   ,'syouka_setsubi'=>'消化設備'
   ,'syoukaki_num'=>'消化器数'
   ,'syoukasen'=>'消火栓'
   ,'syoukasen_umu'=>'消火栓有無'
   ,'sonota_setsubi_cd'=>'その他設備コード'
   ,'sonota_setsubi'=>'その他設備'
   ,'yuudou_hyoujiban_num'=>'誘導表示板数'
   ,'kasai_kenchiki'=>'火災検知器'
   ,'kasai_kenchiki_umu'=>'火災検知器有無'
   ,'musen_tsuushin_setsubi'=>'無線通信設備'
   ,'musen_tsuushin_setsubi_umu'=>'無線通信設備有無'
   ,'radio_re_housou_setsubi'=>'ラジオ再放送ラジオ設備'
   ,'radio_re_housou_setsubi_umu'=>'ラジオ再放送ラジオ設備有無'
   ,'warikomi_housou'=>'割り込み放送機能'
   ,'warikomi_housou_umu'=>'割り込み放送機能有無'
   ,'radio_re_musenkyoka_num'=>'ラジオ再無線許可番号'
   ,'musen_kyoka_dt'=>'無線許可年月日'
   ,'humei'=>'不明'
   ,'jieisen_denki'=>'自営線電気'
   ,'jieisen_denki_umu'=>'自営線電気有無'
   ,'jieisen_tsuushin'=>'自営線通信'
   ,'jieisen_tsuushin_umu'=>'自営線通信有無'
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
  $csvFileName =$csvDirName .'TT_'. date("Ymd_His").'.csv';
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
