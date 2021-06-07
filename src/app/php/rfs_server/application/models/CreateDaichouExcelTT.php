<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("CreateDaichouExcel.php");

// トンネル登録用
class CreateDaichouExcelTT extends CreateDaichouExcel {

  protected static $daichou_info_arr = [
    'toukyuu'=>'',
    'shisetsu_renzoku'=>'',
    'kussaku'=>'',
    'hekimen_kbn'=>'',
    'kanki_shisetsu'=>'',
    'secchi_kasyo_juudan'=>'',
    'secchi_kasyo_oudan'=>'',
    'syoumei_shisetsu'=>'',
    'syoumei_kisuu'=>'',
    'tuuhou_souchi'=>'',
    'push_button_num'=>'',
    'hijou_tel_num'=>'',
    'hijou_keihou_souchi'=>'',
    'keihou_hyoujiban_num'=>'',
    'tenmetsutou_num'=>'',
    'syouka_setsubi'=>'',
    'syoukaki_num'=>'',
    'syoukasen_str'=>'',
    'sonota_setsubi'=>'',
    'yuudou_hyoujiban_num'=>'',
    'kasai_kenchiki_str'=>'',
    'musen_tsuushin_setsubi_str'=>'',
    'radio_re_housou_setsubi_str'=>'',
    'warikomi_housou_str'=>'',
    'radio_re_musenkyoka_num'=>'',
    'jieisen_tsuushin_str'=>'',
    'jieisen_denki_str'=>'',
    'tk_kaisen_syu'=>'',
    'tk_kaisen_kyori'=>'',
    'tk_kaisen_id_str'=>'',
    'tk_kaisen_kyakuban_str'=>'',
    'tk_getsugaku'=>'',
    'tk_setsuzoku_moto'=>'',
    'tk_setsuzoku_saki'=>'',
    'tk_waribiki'=>'',
    'd_hokuden_kyakuban'=>'',
    'd_keiyaku_houshiki'=>'',
    'd_hikikomi'=>'',
    'd_denki_dai'=>'',
    'd_denki_ryou'=>'',
    'map'=>'',
    'kyoutsuu1'=>'',
    'kyoutsuu2'=>'',
    'kyoutsuu3'=>'',
    'dokuji1'=>'',
    'dokuji2'=>'',
    'dokuji3'=>'',
    'bikou'=>'',
    'create_dt'=>''
  ];

  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
  }

  /***
   *  台帳:トンネルExcelの作成
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  /**
    * Excel 出力の共通処理
    *
    * @param integer $sno
    */
  protected function editDaichouData($sno) {
    log_message('debug', __METHOD__);

    $base_info = $this->getShisetsuInfo($sno)[0]; // 基本情報を取得する
    $this->chgCoord($base_info);
    $this->getShisetsuKbnId($base_info);  // 施設区分IDを取得する
    $daichou_info = $this->getDaichouInfo($sno,$base_info['shisetsu_kbn'],$base_info['daityou_tbl']); // 台帳データを取得する
    // 台帳データがない場合、デフォルト値をセット
    if(!$daichou_info){
      $daichou_info= array_merge(self::$daichou_base_info_arr, self::$daichou_info_arr);
    }

    $hosyuu_info = $this->getHosyuuRireki($sno);  // 補修情報を取得する
    $this->setMap($base_info, $daichou_info); // mapをセットする

    $params = array_merge($base_info, $daichou_info, $hosyuu_info);

    // Excel作成
    $this->createSheet();

    if (in_array('施設台帳様式その1', $this->included_sheets)) {
      // メインのシートを作る
      $this->editSheet('施設台帳様式その1', $params);
    }

    if (in_array('施設台帳様式その2', $this->included_sheets)) {
      // 図面のシートを作る
      $this->editSheet('施設台帳様式その2', $params);
    }

    // Excelパスを作成
    $this->setExcelPath($base_info);

  }

  /**
    * 台帳の検索
    *   引数のsnoから台帳情報を取得する。
    *
    * @param integer sno
    * @param integer shisetsu_kbn
    * @return array 台帳情報
  */
  protected function getDaichouInfo($sno, $shisetsu_kbn,$daichou_tbl){
    log_message('debug', 'getDaichouInfo');

    $fields="";
    $join="";

    $fields= <<<EOF
t.toukyuu
, ren.shisetsu_renzoku
, heki.hekimen_kbn
, romen_syu
, CASE WHEN romen_syu = 1 THEN 'アスファルト'
  WHEN romen_syu = 2 THEN 'コンクリート'
  WHEN romen_syu = 0 THEN '不明' END romen_syu_str
, tenkenkou
, CASE WHEN tenkenkou = 1 THEN '有'
  WHEN tenkenkou = 2 THEN '無'
  WHEN tenkenkou = 0 THEN '不明' END tenkenkou_str
, kanshi_camera
, CASE WHEN kanshi_camera = 1 THEN '有'
  WHEN kanshi_camera = 2 THEN '無'
  WHEN kanshi_camera = 0 THEN '不明' END kanshi_camera_str
, kanki_shisetsu
, bousuiban
, CASE WHEN bousuiban = 1 THEN '有'
  WHEN bousuiban = 2 THEN '無'
  WHEN bousuiban = 0 THEN '不明' END bousuiban_str
, secchi1.secchi_kasyo secchi_kasyo_juudan
, secchi2.secchi_kasyo secchi_kasyo_oudan
, syo.syoumei_shisetsu
, tsu.tuuhou_souchi
, hijou.hijou_keihou_souchi
, syouka.syouka_setsubi
, so.sonota_setsubi
, to_char(musen_kyoka_dt,'YYYY-MM-DD') musen_kyoka_dt
, ' ' || tk_kaisen_id tk_kaisen_id_str
, ' ' || tk_kaisen_kyakuban tk_kaisen_kyakuban_str


, CASE WHEN syoukasen = 1 THEN '有'
  WHEN syoukasen = 2 THEN '無'
  WHEN syoukasen = 0 THEN '不明' END syoukasen_str
, CASE WHEN kasai_kenchiki = 1 THEN '有'
  WHEN kasai_kenchiki = 2 THEN '無'
  WHEN kasai_kenchiki = 0 THEN '不明' END kasai_kenchiki_str
, CASE WHEN musen_tsuushin_setsubi = 1 THEN '有'
  WHEN musen_tsuushin_setsubi = 2 THEN '無'
  WHEN musen_tsuushin_setsubi = 0 THEN '不明' END musen_tsuushin_setsubi_str
, CASE WHEN radio_re_housou_setsubi = 1 THEN '有'
  WHEN radio_re_housou_setsubi = 2 THEN '無'
  WHEN radio_re_housou_setsubi = 0 THEN '不明' END radio_re_housou_setsubi_str
, CASE WHEN warikomi_housou = 1 THEN '有'
  WHEN warikomi_housou = 2 THEN '無'
  WHEN warikomi_housou = 0 THEN '不明' END warikomi_housou_str
, CASE WHEN jieisen_denki = 1 THEN '有'
  WHEN jieisen_denki = 2 THEN '無'
  WHEN jieisen_denki = 0 THEN '不明' END jieisen_denki_str
, CASE WHEN jieisen_tsuushin = 1 THEN '有'
  WHEN jieisen_tsuushin = 2 THEN '無'
  WHEN jieisen_tsuushin = 0 THEN '不明' END jieisen_tsuushin_str
, kussaku
, syoumei_kisuu
, push_button_num
, hijou_tel_num
, keihou_hyoujiban_num
, tenmetsutou_num
, syoukaki_num
, yuudou_hyoujiban_num
, radio_re_musenkyoka_num
, tk_kaisen_syu
, tk_kaisen_kyori
, tk_getsugaku
, tk_setsuzoku_moto
, tk_setsuzoku_saki
, tk_waribiki
, d_hokuden_kyakuban
, d_keiyaku_houshiki
, d_hikikomi
, d_denki_dai
, d_denki_ryou
, kyoutsuu1
, kyoutsuu2
, kyoutsuu3
, dokuji1
, dokuji2
, dokuji3
, bikou
,to_char(create_dt,'YYYY.MM.DD') create_dt
EOF;

  $join= <<<EOF
  LEFT JOIN rfs_m_toukyuu t
  ON $daichou_tbl.toukyuu_cd = t.toukyuu_cd

  LEFT JOIN rfs_m_shisetsu_renzoku ren
  ON $daichou_tbl.shisetsu_renzoku_cd = ren.shisetsu_renzoku_cd

  LEFT JOIN rfs_m_hekimen_kbn heki
  ON $daichou_tbl.hekimen_kbn_cd = heki.hekimen_kbn_cd

  LEFT JOIN rfs_m_kanki_shisetsu kan
  ON $daichou_tbl.kanki_shisetsu_cd = kan.kanki_shisetsu_cd

  LEFT JOIN rfs_m_secchi_kasyo secchi1
  ON $daichou_tbl.secchi_kasyo_juudan_cd = secchi1.secchi_kasyo_cd AND secchi1.secchi_kasyo_kbn = 1

  LEFT JOIN rfs_m_secchi_kasyo secchi2
  ON $daichou_tbl.secchi_kasyo_oudan_cd = secchi2.secchi_kasyo_cd AND secchi2.secchi_kasyo_kbn = 2

  LEFT JOIN rfs_m_syoumei_shisetsu syo
  ON $daichou_tbl.syoumei_shisetsu_cd = syo.syoumei_shisetsu_cd

  LEFT JOIN rfs_m_tuuhou_souchi tsu
  ON $daichou_tbl.tuuhou_souchi_cd = tsu.tuuhou_souchi_cd

  LEFT JOIN rfs_m_hijou_keihou_souchi hijou
  ON $daichou_tbl.hijou_keihou_souchi_cd = hijou.hijou_keihou_souchi_cd

  LEFT JOIN rfs_m_syouka_setsubi syouka
  ON $daichou_tbl.syouka_setsubi_cd = syouka.syouka_setsubi_cd

  LEFT JOIN rfs_m_sonota_setsubi so
  ON $daichou_tbl.sonota_setsubi_cd = so.sonota_setsubi_cd
EOF;

    $sql= <<<EOF
SELECT
  $fields
FROM
  $daichou_tbl
  $join
WHERE
  $daichou_tbl.sno = $sno
EOF;

    $query = $this->rfs->query($sql);
    $result = null;
    if(isset($query->result('array')[0])){
      $result = $query->result('array')[0];
    }

/*
  log_message('debug', "sql=$sql");
  $r = print_r($result, true);
  log_message('debug', "result=$r");
*/

    return $result;

  }

}
