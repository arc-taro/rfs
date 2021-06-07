<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("CreateDaichouExcel.php");

// 大型スノーポール登録用
class CreateDaichouExcelYH extends CreateDaichouExcel {

  protected static $daichou_info_arr = [
    'create_dt'=>'',
    'kanri_no'=>'',
    'seizou_company'=>'',
    'yh_maker'=>'',
    'katashiki'=>'',
    'pole_keishiki'=>'',
    'yh_type'=>'',
    'hakkou'=>'',
    'yh_secchi'=>'',
    'yh_secchi_yyyy'=>'',
    'dengen'=>'',
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
    'bikou'=>''
  ];

  /**
  * コンストラクタ
  */
  public function __construct() {
    parent::__construct();
  }

  /***
  *  大型スノーポールExcelの作成
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
kanri_no
, seizou_company
, yh_maker
, katashiki
, pole_keishiki
, yh_type
, hak.hakkou
, yh_secchi
, yh_secchi_yyyy
, dengen
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
LEFT JOIN
  rfs_m_hakkou hak
ON
  $daichou_tbl.hakkou_cd = hak.hakkou_cd
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

    return $result;

  }

}
