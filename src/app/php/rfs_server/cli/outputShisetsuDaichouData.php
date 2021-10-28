<?php
require_once 'db.php';
ini_set('memory_limit', '5000M');
// 施設区分１
function _outputCSVDH($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
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
  //$csvFileName =$csvDirName .'DH_'. date("Ymd_His").'.csv';
  $csvFileName =$csvDirName .'DH_'. date("Ymd_His").'.csv';
  $fp = fopen($csvFileName,'w');
  mb_convert_variables('SJIS', 'UTF-8', $csv_header);
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      //$tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }

    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }
    
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

// 施設区分２
function _outputCSVJD($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
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
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach($csv_header as $key=>$value){
      // $tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }

    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

// 施設区分３
function _outputCSVSS($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
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
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      // $tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }

    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}


// 施設区分４
function _outputCSVBS($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
  // $daichou_data = json_decode($all_data['daichou_json']);
  $csv_daichou_header = [
    'daityou_no'=>'台帳番号'
    ,'zu_no'=>'図番号'
    ,'sekkei_sokudo'=>'設計速度'
    ,'sakusyu_cd'=>'柵種コード'
    ,'sakusyu'=>'柵種'
    ,'saku_kbn_cd'=>'柵区分コード'
    ,'saku_kbn'=>'柵区分'
    ,'saku_keishiki_cd'=>'柵形式コード'
    ,'saku_keishiki'=>'柵形式'
    ,'bikou_kikaku'=>'備考（規格など）'
    ,'kiso_keishiki_cd'=>'基礎形式コード'
    ,'kiso_keishiki'=>'基礎形式'
    ,'kiso_keijou'=>'基礎形状'
    ,'sekou_company'=>'施工会社'
    ,'i_maker_nm'=>'一般道路部分 メーカー名'
    ,'i_katashiki'=>'一般道路部分 型式'
    ,'i_span_len'=>'一般道路部分 スパン長'
    ,'i_span_num'=>'一般道路部分 スパン数'
    ,'i_sakukou'=>'一般道路部分 柵高'
    ,'t_maker_nm'=>'取付道路部分 メーカー名'
    ,'t_katashiki'=>'取付道路部分 型式'
    ,'t_span_len'=>'取付道路部分 スパン長'
    ,'t_span_num'=>'取付道路部分 スパン数'
    ,'t_sakukou'=>'取付道路部分 柵高'
    ,'haishi_dt_ryuu'=>'廃止の年月と理由'
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
    ,'update_busyo_cd'=>'更新部署コード'];

  //$csv_header = array_merge($csv_kihon_header,$csv_daichou_header,$tenken_header);
  $csv_header = array_merge($csv_kihon_header,$csv_daichou_header);

  // 建管
  //$tmp="daichou_dogen_${cd}_";
  $csvFileName =$csvDirName .'BS_'. date("Ymd_His").'.csv';
  $fp = fopen($csvFileName,'w');
  mb_convert_variables('SJIS-win', 'UTF-8', $csv_header);
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      // 施設台帳情報の設定
      //$tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }

    mb_convert_variables('SJIS-win', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }

    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

// 施設区分５
function _outputCSVYH($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
  $csv_daichou_header = [
    'kanri_no'=>'管理番号'
    ,'katashiki'=>'型式'
    ,'pole_keishiki'=>'ポール形式'
    ,'yh_type'=>'タイプ'
    ,'seizou_company'=>'製造会社'
    ,'yh_maker'=>'メーカー'
    ,'hakkou_cd'=>'発光コード'
    ,'hakkou'=>'発光'
    ,'yh_secchi'=>'設置年'
    ,'yh_secchi_yyyy'=>'設置年'
    ,'dengen'=>'電源'
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

  //建管
  //$tmp = "daichou_dogen_${cd}_";
  $csvFileName =$csvDirName .'YH_'. date("Ymd_His").'.csv';
  $fp = fopen($csvFileName,'w');
  mb_convert_variables('SJIS', 'UTF-8', $csv_header);
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      //$tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }
    
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

// 施設区分６
function _outputCSVKA($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
  $csv_daichou_header = [
  'unei_kbn_cd'=>'運営区分コード'
  ,'unei_kbn'=>'運営区分'
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
  $csvFileName =$csvDirName .'KA_'. date("Ymd_His").'.csv';
  $fp = fopen($csvFileName,'w');
  mb_convert_variables('SJIS', 'UTF-8', $csv_header);
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      //$tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }
    
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

// 施設区分７
function _outputCSVKB($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
    $all_data = getShisetsuTenkenAndDaichou($db);
    $csv_daichou_header = [
      'unei_kbn_cd'=>'運営区分コード'
      ,'unei_kbn'=>'運営区分'
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
    $csvFileName =$csvDirName .'KB_'. date("Ymd_His").'.csv';
    $fp = fopen($csvFileName,'w');
    mb_convert_variables('SJIS', 'UTF-8', $csv_header);
    $out_csv_header = array_merge($csv_header,$tenken_header);
    fputcsv($fp,(array)$out_csv_header);
    for ($i=0;$i<count($all_data);$i++) {
      $tmp=array();
      foreach((array)$csv_header as $key=>$value){
        //$tmp[] = $data[$i][$key];
        $daichou_data = json_decode($all_data[$i]['daichou_json']);
        $tmp[] = $daichou_data->$key;
      }
      mb_convert_variables('SJIS', 'UTF-8', $tmp);
  
      // 施設点検情報の設定
      $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
      $tenken_data = shapePerShisetsu($hikisu);
  
      foreach( $tenken_Fields as $field ) {
        if($field == 'struct_idx/bscnt') {
          $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
        } else if($field == '～') {
          $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
        } else if( !isset($tenken_data[$field]) ){
          $tmp[]="";
        } else if( preg_match('/judge/',$field) ){
          $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
        } else {
          $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
        }
      }
      
      fputcsv($fp,(array)$tmp);
    }
    fclose($fp);
    echo print_r($csvFileName,true)."\n";
    return $csvFileName;
  }

// 施設区分８
function _outputCSVKC($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
  $csv_daichou_header = [
    'unei_kbn_cd'=>'運営区分コード'
    ,'unei_kbn'=>'運営区分'
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
  $csvFileName =$csvDirName .'KC_'. date("Ymd_His").'.csv';
  $fp = fopen($csvFileName,'w');
  mb_convert_variables('SJIS', 'UTF-8', $csv_header);
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      //$tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }
    
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

// 施設区分９
function _outputCSVKD($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
  $csv_daichou_header = [
    'unei_kbn_cd'=>'運営区分コード'
    ,'unei_kbn'=>'運営区分'
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
  $csvFileName =$csvDirName .'KD_'. date("Ymd_His").'.csv';
  $fp = fopen($csvFileName,'w');
  mb_convert_variables('SJIS', 'UTF-8', $csv_header);
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      //$tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }
    
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

// 施設区分１０
function _outputCSVKI($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
  $csv_daichou_header = [
    'unei_kbn_cd'=>'運営区分コード'
    ,'unei_kbn'=>'運営区分'
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
  $csvFileName =$csvDirName .'KI_'. date("Ymd_His").'.csv';
  $fp = fopen($csvFileName,'w');
  mb_convert_variables('SJIS', 'UTF-8', $csv_header);
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      //$tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }
    
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

// 施設区分１１
function _outputCSVJH($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
  $csv_daichou_header = [
    'bikou'=>'備考'
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
  $csvFileName =$csvDirName .'JH_'. date("Ymd_His").'.csv';
  $fp = fopen($csvFileName,'w');
  mb_convert_variables('SJIS', 'UTF-8', $csv_header);
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      //$tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }
    
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

// 施設区分１２
function _outputCSVSD($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
  $csv_daichou_header = [
    'yokokuhyoushiki_umu'=>'予告標識有無'
    ,'yokokuhyoushiki_umu_str'=>'予告標識有無'
    ,'jouhouban_umu'=>'情報板有無'
    ,'jouhouban_umu_str'=>'情報板有無'
    ,'anzentou_syoumeitou'=>'安全灯照明灯'
    ,'syadanji_riyuu'=>'遮断時理由'
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
  $csvFileName =$csvDirName .'SD_'. date("Ymd_His").'.csv';
  $fp = fopen($csvFileName,'w');
  mb_convert_variables('SJIS', 'UTF-8', $csv_header);
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      //$tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }
    
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

// 施設区分１３
function _outputCSVDT($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
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
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      //$tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }
    
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

// 施設区分１４
function _outputCSVTT($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
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
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      //$tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }
    
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

// 施設区分１５
function _outputCSVCK($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
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
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      //$tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }
    
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

// 施設区分１６
function _outputCSVSK($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
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
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      //$tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }
    
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

// 施設区分１７
function _outputCSVBH($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
  $csv_daichou_header = [
  'kaidan'=>'階段'
  ,'kaidan_umu'=>'階段有無'
  ,'slope'=>'スロープ'
  ,'slope_umu'=>'スロープ有無'
  ,'oshiage'=>'押し上げ'
  ,'oshiage_umu'=>'押し上げ有無'
  ,'heating'=>'ヒーティング'
  ,'heating_umu'=>'ヒーティング有無'
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
  $csvFileName =$csvDirName .'BH_'. date("Ymd_His").'.csv';
  $fp = fopen($csvFileName,'w');
  mb_convert_variables('SJIS', 'UTF-8', $csv_header);
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      //$tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }
    
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

// 施設区分１８
function _outputCSVDY($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
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
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      //$tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }
    
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

// 施設区分１９
function _outputCSVDN($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
  $csv_daichou_header = [
    'daityou_no'=>'台帳番号'
,'genkyou_nendo'=>'現況年度'
,'genkyou_nendo_yyyy'=>'現況年度'
,'genkyou_no'=>'現況番号'
,'bouten_kanri_no'=>'防点管理番号'
,'bikou_kouhou'=>'備考'
,'noridaka_kou'=>'法高 高'
,'noridaka_tei'=>'法高 低'
,'norimen_koubai'=>'法面 勾配'
,'norimen_menseki'=>'法面 面積'
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
  $csvFileName =$csvDirName .'DN_'. date("Ymd_His").'.csv';
  $fp = fopen($csvFileName,'w');
  mb_convert_variables('SJIS', 'UTF-8', $csv_header);
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      //$tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }
    
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

// 施設区分２０
function _outputCSVTS($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
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
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      //$tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }
    
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

// 施設区分２１
function _outputCSVRH($csvDirName,$csv_kihon_header,$db,$tenken_header,$tenken_Fields) {
  // ２つのtmpテーブルからデータを取得
  $all_data = getShisetsuTenkenAndDaichou($db);
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
  $out_csv_header = array_merge($csv_header,$tenken_header);
  fputcsv($fp,(array)$out_csv_header);
  for ($i=0;$i<count($all_data);$i++) {
    $tmp=array();
    foreach((array)$csv_header as $key=>$value){
      //$tmp[] = $data[$i][$key];
      $daichou_data = json_decode($all_data[$i]['daichou_json']);
      $tmp[] = $daichou_data->$key;
    }
    mb_convert_variables('SJIS', 'UTF-8', $tmp);

    // 施設点検情報の設定
    $hikisu = (array)json_decode($all_data[$i]['tenken_json'],true);
    $tenken_data = shapePerShisetsu($hikisu);

    foreach( $tenken_Fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $tmp[] = mb_convert_encoding($tenken_data['struct_idx'] . '/' . $tenken_data['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $tmp[] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($tenken_data[$field]) ){
        $tmp[]="";
      } else if( preg_match('/judge/',$field) ){
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $tmp[] = mb_convert_encoding($tenken_data[$field], 'SJIS', 'UTF-8');
      }
    }
    
    fputcsv($fp,(array)$tmp);
  }
  fclose($fp);
  echo print_r($csvFileName,true)."\n";
  return $csvFileName;
}

/***
   * 点検データの整形
   * 1施設の点検データをNDS提供EXCELの仕様に合わせ整形する。
   * 引数:$shisetsu_row
   *      含まれるデータ:施設基本情報
   *                  点検管理main情報
   *                  点検 施設情報
   *                  点検 部材以下の情報(JSON形式)
   * 戻り値 1行の配列データ
   ***/
  function shapePerShisetsu($shisetsu_row) {
    //log_message("debug","shapePerShisetsu");

    $res = array();
    // データの展開
    foreach ($shisetsu_row as $key => $val) {
      // 部材以下のJSON形式データを配列化
      if ($key=="b_tk_sn_list") {
        $b_tk_sn=json_decode($val,true,JSON_NUMERIC_CHECK);
        //log_message("debug", print_r($b_tk_sn,true));
        if ($b_tk_sn){
          setOrLaterBuzai($b_tk_sn, $res);
        }
      // 点検にぶら下がる全損傷
      } else if ($key=="sn_list") {
        $tenken_kasyo_all_sonsyou=json_decode($val,true);
        if ($tenken_kasyo_all_sonsyou) {
          setAllSonsyou($tenken_kasyo_all_sonsyou, $res);
        }
      } else {
        // 施設と1対1の情報についてはこちらで保持
        $res[$key]=$val;
      }
    }
//確認用
    //log_message("debug", print_r($res,true));
    return $res;
  }

  /***
   * 部材以下データの整形
   * 1施設にぶら下がる部材以下データ配列をNDS提供EXCELの仕様に合わせ整形する。
   *
   * 引数:$data
   *      含まれるデータ:部材情報
   *                  部材にぶら下がる点検箇所配列
   *     $arr
   *      戻す値 NDS提供EXCELに合わせセットする
   *      整形した1行の配列データ
   ***/
  function setOrLaterBuzai($data, &$arr) {
    //log_message("debug","setOrLaterBuzai");

    // 部材ループ
    foreach ($data as $buzai) {
      $buzai_cd = $buzai['buzai_cd']; // 部材コード保持
//      log_message("debug","------------------------------------>部材コード:".$buzai_cd);

      // 部材内
      foreach ($buzai as $key => $val) {
        // 点検箇所配列
        if ($key=="tk_sn_list") {
          setTenkenKasyo($val, $arr);
        } else {
          // 部材の情報
          $arr["${key}_${buzai_cd}"]=$val;
        }
      }
    }
  }

  /***
   * 点検箇所および損傷データの整形
   * 1施設にぶら下がる点検箇所データ配列と全損傷をNDS提供EXCELの仕様に合わせ整形する。
   *
   * 引数:$data
   *      含まれるデータ:点検箇所情報
   *                  点検箇所毎の全損傷情報
   *     $arr
   *      戻す値 NDS提供EXCELに合わせセットする
   *      整形した1行の配列データ
   ***/
  function setTenkenKasyo($data, &$arr){

    $chk_sonsyou = "";        // 点検時健全性Ⅱ以上の損傷を保持
    $measure_sonsyou = "";    // 措置後健全性Ⅱ以上の損傷を保持
    $chk_pic_nm = "";         // 点検時健全性Ⅱ以上の写真名(番号のみ)を保持
    $measure_pic_nm = "";     // 措置後健全性Ⅱ以上の写真名(番号のみ)を保持

    // 損傷と写真は部材毎に集計
    $chk_sonsyous=array();
    $chk_pictures=array();
    $measure_sonsyous=array();
    $chk_sonsyou="";
    $chk_picture="";
    $measure_sonsyou="";
    $last_measures_dt = null;

    // 点検箇所ループ
    foreach ($data as $tenken_kasyo) {

      $buzai_cd=$tenken_kasyo['buzai_cd'];
      $buzai_detail_cd=$tenken_kasyo['buzai_detail_cd'];
      $tenken_kasyo_cd=$tenken_kasyo['tenken_kasyo_cd'];

      /*** 点検 ***/
      // 損傷・写真は健全性Ⅱ以上を列挙する(部材単位)
      if ($tenken_kasyo["check_judge"] >= 2) {
        $chk_sonsyou=$tenken_kasyo["sonsyou_naiyou_nm"];
        $chk_picture=$tenken_kasyo["picture_nm1"];
        // 保持配列に無ければセットする
        if (array_search($chk_sonsyou, $chk_sonsyous) === false) {
          array_push($chk_sonsyous,$chk_sonsyou);
        }
        if (array_search($chk_picture, $chk_pictures) === false) {
          array_push($chk_pictures,$chk_picture);
        }
      }
      /*** 措置後 ***/
      if ($tenken_kasyo["measures_judge"] >= 2) {
        $measure_sonsyou=$tenken_kasyo["sonsyou_naiyou_nm"];
        // 保持配列に無ければセットする
        if (array_search($measure_sonsyou, $measure_sonsyous) === false) {
          array_push($measure_sonsyous,$measure_sonsyou);
        }
      }
      /*** 措置日 ***/ // 最新を保持
      if ($tenken_kasyo["measures_dt"] && $tenken_kasyo["measures_dt"] > $last_measures_dt) {
        $last_measures_dt = $tenken_kasyo["measures_dt"];
      }
      // 点検箇所内ループ
      foreach ($tenken_kasyo as $key => $val) {
        $arr["${key}_${buzai_cd}_${buzai_detail_cd}_${tenken_kasyo_cd}"]=$val;
      }
    }

    // 損傷と写真番号について1絡むに「、」区切りでセット
    /*** 点検時損傷内容 ***/
    $tmp="";
    foreach ($chk_sonsyous as $chk_sonsyou) {
      if ($tmp!="") {
        $tmp.="、";
      }
      $tmp.=$chk_sonsyou;
    }
    $arr["check_sonsyou_naiyou_nm_${buzai_cd}"]=$tmp;
    //log_message("debug", print_r($tmp,true));
    /*** 点検時写真番号 ***/
    $tmp="";
    foreach ($chk_pictures as $chk_picture) {
      if ($tmp!="") {
        $tmp.="、";
      }
      $tmp.=$chk_picture;
    }
    $arr["picture_nm_${buzai_cd}"]=$tmp;
    //log_message("debug", print_r($tmp,true));
    /*** 措置後損傷内容 ***/
    $tmp="";
    foreach ($measure_sonsyous as $measure_sonsyou) {
      if ($tmp!="") {
        $tmp.="、";
      }
      $tmp.=$measure_sonsyou;
    }
    $arr["measures_sonsyou_naiyou_nm_${buzai_cd}"]=$tmp;
    //log_message("debug", print_r($tmp,true));
    /*** 最新の措置日 ***/
    $arr["measures_dt_${buzai_cd}"]=$last_measures_dt;
    //log_message("debug", print_r($tmp,true));
  }

    /***
   * 全損傷データの整形
   * 1施設にぶら下がる全損傷データ配列をNDS提供EXCELの仕様に合わせ整形する。
   *
   * 引数:$data
   *      含まれるデータ:全損傷情報
   *     $arr
   *      戻す値 NDS提供EXCELに合わせセットする
   *      整形した1行の配列データ
   ***/
  function setAllSonsyou($data, &$arr){
    //log_message("debug","setAllSonsyou");
    foreach ($data as $item) {
//      $buzai_cd=$item['buzai_cd'];
//      $buzai_detail_cd=$item['buzai_detail_cd'];
//      $tenken_kasyo_cd=$item['tenken_kasyo_cd'];
//      $sonsyou_naiyou_cd=$item['sonsyou_naiyou_cd'];
//      $arr["check_before_str_${buzai_cd}_${buzai_detail_cd}_${tenken_kasyo_cd}_${sonsyou_naiyou_cd}"]=$item['check_before_str'];
//      $arr["measures_after_str_${buzai_cd}_${buzai_detail_cd}_${tenken_kasyo_cd}_${sonsyou_naiyou_cd}"]=$item['measures_after_str'];
      $buzai_cd=$item[0];
      $buzai_detail_cd=$item[1];
      $tenken_kasyo_cd=$item[2];
      $sonsyou_naiyou_cd=$item[3];
      $arr["check_before_str_${buzai_cd}_${buzai_detail_cd}_${tenken_kasyo_cd}_${sonsyou_naiyou_cd}"]=$item[4];
      $arr["measures_after_str_${buzai_cd}_${buzai_detail_cd}_${tenken_kasyo_cd}_${sonsyou_naiyou_cd}"]=$item[5];
    }
  }