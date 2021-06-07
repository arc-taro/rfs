<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("CreateDaichouExcel.php");

// 道路照明施設登録用
class CreateDaichouExcelSS extends CreateDaichouExcel {

  protected static $daichou_info_arr = [
    'create_dt'=>'',
    'toutyuu_no'=>'',
    'secchi_bui'=>'',
    'kousa_rosen'=>'',
    'pole_kikaku'=>'',
    'pole_kikaku_bikou'=>'',
    'ramp_num'=>'',
    'tyoukou_umu'=>'',
    'syadou_ramp1'=>'',
    'syadou_ramp2'=>'',
    'hodou_ramp1'=>'',
    'hodou_ramp2'=>'',
    'timer_umu'=>'',
    'syadou_syoutou1'=>'',
    'syadou_syoutou2'=>'',
    'hodou_syoutou1'=>'',
    'hodou_syoutou2'=>'',
    'secchi_gyousya'=>'',
    'hodou_syoumei_payer'=>'',
    'd_hokuden_kyakuban'=>'',
    'd_keiyaku_houshiki'=>'',
    'd_hikikomi'=>'',
    'd_denki_dai'=>'',
    'd_denki_ryou'=>'',
    'kyoutsuu1'=>'',
    'kyoutsuu2'=>'',
    'kyoutsuu3'=>'',
    'dokuji1'=>'',
    'dokuji2'=>'',
    'dokuji3'=>'',
    'bikou'=>'',
    'tougu_secchi'=>''
  ];

  /**
  * コンストラクタ
  */
  public function __construct() {
    parent::__construct();
  }

  /***
  *  道路照明施設Excelの作成
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

    $huzokubutsu_info = $this->getHuzokubutsuRireki($base_info);  // 付属物点検情報を取得する
    $hosyuu_info = $this->getHosyuuRireki($sno);  // 補修情報を取得する

    $this->setMap($base_info, $daichou_info); // mapをセットする

    $params = array_merge($base_info, $daichou_info, $hosyuu_info,$huzokubutsu_info);

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
toutyuu_no
, secchi_bui
, kousa_rosen
, pol.pole_kikaku
, pole_kikaku_bikou
, syadou_ramp1
, syadou_ramp2
, hodou_ramp1
, hodou_ramp2
, syadou_syoutou1
, syadou_syoutou2
, hodou_syoutou1
, hodou_syoutou2
, ramp_num
, tyo.tyoukou_umu
, tim.timer_umu
, syoutou
, CASE
    WHEN syoutou = 1 THEN '有'
    WHEN syoutou = 2 THEN '無'
    WHEN syoutou = 0 THEN '不明' END syoutou_str
, secchi_gyousya
, hodou_syoumei_payer
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
, tougu_secchi
,to_char(create_dt,'YYYY.MM.DD') create_dt
EOF;

    $join= <<<EOF
LEFT JOIN
  rfs_m_pole_kikaku pol
ON
  $daichou_tbl.pole_kikaku_cd = pol.pole_kikaku_cd
LEFT JOIN
  rfs_m_tyoukou_umu tyo
ON
  $daichou_tbl.tyoukou_umu_cd = tyo.tyoukou_umu_cd
LEFT JOIN
  rfs_m_timer_umu tim
ON
  $daichou_tbl.timer_umu_cd = tim.timer_umu_cd
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
