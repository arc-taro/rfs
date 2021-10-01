<?php

function getForOutDataRH($db,$cd) {

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
,rh.gno
,rh.endou_joukyou_cd
,rh_endou_joukyou.endou_joukyou_nm
,rh.did_syubetsu_cd
,rh_did_syubetsu.did_syubetsu_nm
,rh.endou_kuiki_cd
,rh_endou_kuiki.endou_kuiki_nm
,rh.endou_chiiki_cd
,rh_endou_chiiki.endou_chiiki_nm
,rh.bus_rosen
, CASE rh.bus_rosen 
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END bus_rosen_umu
,rh.tsuugaku_ro
, CASE rh.tsuugaku_ro 
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END tsuugaku_ro_umu
,rh.yukimichi
, CASE rh.yukimichi 
  WHEN 0 THEN '不明' 
  WHEN 1 THEN '有' 
  WHEN 2 THEN '無' 
  ELSE '' 
  END yukimichi_umu
,rh.josetsu_kbn
, CASE rh.josetsu_kbn 
  WHEN 1 THEN '1種' 
  WHEN 2 THEN '2種' 
  WHEN 3 THEN '3種' 
  ELSE '' 
  END josetsu_kbn_nm
,rh.genkyou_num1
,rh.genkyou_num2
,rh.haba_syadou
,rh.haba_hodou
,rh.koubai_syadou
,rh.koubai_hodou
,rh.hankei_r
,rh.chuusin_enchou_syadou
,rh.nobe_enchyou_syadou
,rh.fukuin_syadou
,rh.menseki_syadou
,rh.hosou_syubetsu_syadou
,rh.chuusin_enchou_hodou
,rh.nobe_enchyou_hodou
,rh.fukuin_hodou
,rh.menseki_hodou
,rh.hosou_syubetsu_hodou
,rh.koutsuuryou_syadou
,rh.koutsuuryou_hodou
,rh.morido
,rh.kirido
,rh.kyuu_curve
,rh.under_syadou
,rh.under_hodou
,rh.kyuukoubai_syadou
,rh.kyuukoubai_hodou
,rh.fumikiri_syadou
,rh.fumikiri_houdou
,rh.kousaten_syadou
,rh.kousaten_hodou
,rh.hodoukyou
,rh.tunnel_syadou
,rh.tunnel_hodou
,rh.heimen_syadou
,rh.heimen_hodou
,rh.kyouryou_syadou
,rh.kyouryou_hodou
,rh.kosen_syadou
,rh.kosen_hodou
,rh.etc_syadou
,rh.etc_hodou
,rh.etc_comment_syadou
,rh.etc_comment_hodou
,rh.netsugen_etc
,rh.netsugen_etc
,rh.douryoku_etc
,rh.syuunetsu_cd
,rh_syuunetsu.syuunetsu_nm
,rh.syuunetsu_etc
,rh.hounetsu_cd
,rh_hounetsu.hounetsu_nm
,rh.hounetsu_etc
,rh.denryoku_keiyaku_syubetsu_cd
,rh_denryoku_keiyaku_syubetsu.denryoku_keiyaku_syubetsu_nm
,rh.seibi_keii_syadou
,rh.sentei_riyuu_syadou
,rh.seibi_keii_hodou
,rh.sentei_riyuu_hodou
,rh.haishi_jikou
,rh.unit_shiyou
, CASE rh.unit_shiyou
  WHEN '0' THEN '不明' 
  WHEN '1' THEN '有' 
  WHEN '2' THEN '無' 
  ELSE '' 
  END unit_shiyou_umu
,rh.unit_ichi
, CASE rh.unit_ichi
  WHEN '0' THEN '不明' 
  WHEN '1' THEN '有' 
  WHEN '2' THEN '無' 
  ELSE '' 
  END unit_ichi_umu
,rh.sencor_shiyou
, CASE rh.sencor_shiyou
  WHEN '0' THEN '不明' 
  WHEN '1' THEN '有' 
  WHEN '2' THEN '無' 
  ELSE '' 
  END sencor_shiyou_umu
,rh.sensor_ichi
, CASE rh.sensor_ichi
  WHEN '0' THEN '不明' 
  WHEN '1' THEN '有' 
  WHEN '2' THEN '無' 
  ELSE '' 
  END sensor_ichi_umu
,rh.seigyoban_shiyou
, CASE rh.seigyoban_shiyou
  WHEN '0' THEN '不明' 
  WHEN '1' THEN '有' 
  WHEN '2' THEN '無' 
  ELSE '' 
  END seigyoban_shiyou_umu
,rh.seigyoban_ichi
, CASE rh.seigyoban_ichi
  WHEN '0' THEN '不明' 
  WHEN '1' THEN '有' 
  WHEN '2' THEN '無' 
  ELSE '' 
  END seigyoban_ichi_umu
,rh.haisen_shiyou
, CASE rh.haisen_shiyou
  WHEN '0' THEN '不明' 
  WHEN '1' THEN '有' 
  WHEN '2' THEN '無' 
  ELSE '' 
  END haisen_shiyou_umu
,rh.haisen_ichi
, CASE rh.haisen_ichi
  WHEN '0' THEN '不明' 
  WHEN '1' THEN '有' 
  WHEN '2' THEN '無' 
  ELSE '' 
  END haisen_ichi_umu
,rh.hokuden_ichi
, CASE rh.hokuden_ichi
  WHEN '0' THEN '不明' 
  WHEN '1' THEN '有' 
  WHEN '2' THEN '無' 
  ELSE '' 
  END hokuden_ichi_umu
,rh.check1
,rh.check2
,rh.old_id
,rh.comment
,rh.hs_check
,rh.comment_douroka
,rh.comment_dogen
,rh.dhs
,rh.dctr
,rh.bundenban
,rh.boira
,rh.update_dt
,rh.update_account
,rh.bikou
,rh.kyoutsuu1
,rh.kyoutsuu2
,rh.kyoutsuu3
,rh.dokuji1
,rh.dokuji2
,rh.dokuji3
,rh.create_dt
,rh.create_account
,rh.update_account_nm
,rh.update_busyo_cd
FROM
s_tmp shisetsu 
LEFT JOIN rfs_t_daichou_rh rh 
  ON shisetsu.sno = rh.sno 
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
LEFT JOIN rfs_m_rh_endou_joukyou rh_endou_joukyou 
  ON rh.endou_joukyou_cd = rh_endou_joukyou.endou_joukyou_cd
LEFT JOIN rfs_m_rh_did_syubetsu rh_did_syubetsu
  ON rh.did_syubetsu_cd = rh_did_syubetsu.did_syubetsu_cd
LEFT JOIN rfs_m_rh_endou_kuiki rh_endou_kuiki
  ON rh.endou_kuiki_cd = rh_endou_kuiki.endou_kuiki_cd
LEFT JOIN rfs_m_rh_endou_chiiki rh_endou_chiiki
  ON rh.endou_chiiki_cd = rh_endou_chiiki.endou_chiiki_cd
LEFT JOIN rfs_m_rh_syuunetsu rh_syuunetsu
  ON rh.syuunetsu_cd = rh_syuunetsu.syuunetsu_cd
LEFT JOIN rfs_m_rh_hounetsu rh_hounetsu
  ON rh.hounetsu_cd = rh_hounetsu.hounetsu_cd
LEFT JOIN rfs_m_rh_denryoku_keiyaku_syubetsu rh_denryoku_keiyaku_syubetsu
  ON rh.denryoku_keiyaku_syubetsu_cd = rh_denryoku_keiyaku_syubetsu.denryoku_keiyaku_syubetsu_cd
order by syucchoujo_cd
EOF;
//AND shisetsu.shisetsu_kbn = $shisetsu_kbn_cd

  //echo $sql;
  return $db->query($sql);
}

function outputCSVRH($data,$csvDirName,$csv_kihon_header) {
  $csv_daichou_header = [
    'gno'=>'グループNo'
   ,'endou_joukyou_cd'=>'沿道状況'
   ,'endou_joukyou_nm'=>'沿道状況'
   ,'did_syubetsu_cd'=>'DID種別'
   ,'did_syubetsu_nm'=>'DID種別'
   ,'endou_kuiki_cd'=>'沿道区域'
   ,'endou_kuiki_nm'=>'沿道区域'
   ,'endou_chiiki_cd'=>'沿道地域'
   ,'endou_chiiki_nm'=>'沿道地域'
   ,'bus_rosen'=>'バス路線有無'
   ,'bus_rosen_umu'=>'バス路線有無'
   ,'tsuugaku_ro'=>'通学路有無'
   ,'tsuugaku_ro_umu'=>'通学路有無'
   ,'yukimichi'=>'雪道計画有無'
   ,'yukimichi_umu'=>'雪道計画有無'
   ,'josetsu_kbn'=>'除雪区分'
   ,'josetsu_kbn_nm'=>'除雪区分'
   ,'genkyou_num1'=>'現況'
   ,'genkyou_num2'=>'現況'
   ,'haba_syadou'=>'車道幅'
   ,'haba_hodou'=>'歩道幅'
   ,'koubai_syadou'=>'車道勾配'
   ,'koubai_hodou'=>'歩道勾配'
   ,'hankei_r'=>'曲線半径'
   ,'chuusin_enchou_syadou'=>'車道中心延長'
   ,'nobe_enchyou_syadou'=>'車道延べ延長'
   ,'fukuin_syadou'=>'車道幅員'
   ,'menseki_syadou'=>'車道面積'
   ,'hosou_syubetsu_syadou'=>'車道舗装種別'
   ,'chuusin_enchou_hodou'=>'歩道中心延長'
   ,'nobe_enchyou_hodou'=>'歩道延べ延長'
   ,'fukuin_hodou'=>'歩道幅員'
   ,'menseki_hodou'=>'歩道面積'
   ,'hosou_syubetsu_hodou'=>'歩道舗装種別'
   ,'koutsuuryou_syadou'=>'車道交通量'
   ,'koutsuuryou_hodou'=>'歩道交通量'
   ,'morido'=>'盛土'
   ,'kirido'=>'切土'
   ,'kyuu_curve'=>'急カーブ'
   ,'under_syadou'=>'車道アンダーパス'
   ,'under_hodou'=>'歩道アンダーパス'
   ,'kyuukoubai_syadou'=>'車道急勾配'
   ,'kyuukoubai_hodou'=>'歩道急勾配'
   ,'fumikiri_syadou'=>'車道踏切'
   ,'fumikiri_houdou'=>'歩道踏切'
   ,'kousaten_syadou'=>'車道交差点'
   ,'kousaten_hodou'=>'歩道交差点'
   ,'hodoukyou'=>'歩道橋'
   ,'tunnel_syadou'=>'車道トンネル'
   ,'tunnel_hodou'=>'歩道トンネル'
   ,'heimen_syadou'=>'車道平面'
   ,'heimen_hodou'=>'歩道平面'
   ,'kyouryou_syadou'=>'車道橋梁'
   ,'kyouryou_hodou'=>'歩道橋梁'
   ,'kosen_syadou'=>'車道跨線'
   ,'kosen_hodou'=>'歩道跨線'
   ,'etc_syadou'=>'車道その他'
   ,'etc_hodou'=>'歩道その他'
   ,'etc_comment_syadou'=>'車道その他コメント'
   ,'etc_comment_hodou'=>'歩道その他コメント'
   ,'netsugen_etc'=>'熱源'
   ,'douryoku_etc'=>'動力'
   ,'syuunetsu_cd'=>'集熱方法コード'
   ,'syuunetsu_nm'=>'集熱方法'
   ,'syuunetsu_etc'=>'集熱その他'
   ,'hounetsu_cd'=>'放熱方法コード'
   ,'hounetsu_nm'=>'放熱方法'
   ,'hounetsu_etc'=>'放熱その他'
   ,'denryoku_keiyaku_syubetsu_cd'=>'電力契約種別コード'
   ,'denryoku_keiyaku_syubetsu_nm'=>'電力契約種別名'
   ,'seibi_keii_syadou'=>'車道整備経緯'
   ,'sentei_riyuu_syadou'=>'車道選定理由'
   ,'seibi_keii_hodou'=>'歩道整備経緯'
   ,'sentei_riyuu_hodou'=>'歩道選定理由'
   ,'haishi_jikou'=>'廃止事項'
   ,'unit_shiyou'=>'ユニット仕様'
   ,'unit_shiyou_umu'=>'ユニット仕様有無'
   ,'unit_ichi'=>'ユニット位置'
   ,'unit_ichi_umu'=>'ユニット位置有無'
   ,'sencor_shiyou'=>'センサー仕様'
   ,'sencor_shiyou_umu'=>'センサー仕様有無'
   ,'sensor_ichi'=>'センサー位置'
   ,'sensor_ichi_umu'=>'センサー位置有無'
   ,'seigyoban_shiyou'=>'制御基盤仕様'
   ,'seigyoban_shiyou_umu'=>'制御基盤仕様有無'
   ,'seigyoban_ichi'=>'制御基板位置'
   ,'seigyoban_ichi_umu'=>'制御基板位置有無'
   ,'haisen_shiyou'=>'配管配線仕様'
   ,'haisen_shiyou_umu'=>'配管配線仕様有無'
   ,'haisen_ichi'=>'配管配線位置'
   ,'haisen_ichi_umu'=>'配管配線位置有無'
   ,'hokuden_ichi'=>'北電位置'
   ,'hokuden_ichi_umu'=>'北電位置有無'
   ,'check1'=>'チェック1'
   ,'check2'=>'チェック2'
   ,'old_id'=>'旧電算処理ID'
   ,'comment'=>'コメント(チェック)'
   ,'hs_check'=>'チェック'
   ,'comment_douroka'=>'道路課コメント'
   ,'comment_dogen'=>'建管コメント'
   ,'dhs'=>'dhs'
   ,'dctr'=>'dctr'
   ,'bundenban'=>'分電盤'
   ,'boira'=>'ボイラー'
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
  $csvFileName =$csvDirName .'RH_'. date("Ymd_His").'.csv';
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
