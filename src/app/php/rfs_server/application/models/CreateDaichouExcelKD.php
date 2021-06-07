<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("CreateDaichouExcel.php");

// 気象情報収集（テレメータ観測局装置）登録用
class CreateDaichouExcelKD extends CreateDaichouExcel {

  protected static $daichou_info_arr = [
    'create_dt'=>'',
    'unei_kbn'=>'',
    'kisyoudai_rgst'=>'',
    'musen_nm'=>'',
    'musen_menkyo_no'=>'',
    'kion_kei_str'=>'',
    'huukouhuusoku_kei_str'=>'',
    'usetsuryou_kei_str'=>'',
    'romenondo_kei_str'=>'',
    'shitei_kei_str'=>'',
    'sekisetsushin_kei_str'=>'',
    'romensekisetsushin_kei_str'=>'',
    'romentouketsu_kei_str'=>'',
    'usetsuryou_kei_kentei_dt'=>'',
    'huukouhuusoku_kei_kentei_dt'=>'',
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
  *  気象情報収集（テレメータ観測局装置）Excelの作成
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
unei_kbn
, kisyoudai_rgst
, musen_nm
, musen_menkyo_no
, CASE WHEN kion_kei = 1 THEN '有'
  WHEN kion_kei = 2 THEN '無'
  WHEN kion_kei = 0 THEN '不明' END kion_kei_str
, CASE WHEN huukouhuusoku_kei = 0 THEN '有'
  WHEN huukouhuusoku_kei = 1 THEN '無'
  WHEN huukouhuusoku_kei = 2 THEN '不明' END huukouhuusoku_kei_str
, CASE WHEN usetsuryou_kei = 1 THEN '有'
  WHEN usetsuryou_kei = 2 THEN '無'
  WHEN usetsuryou_kei = 0 THEN '不明' END usetsuryou_kei_str
, CASE WHEN romenondo_kei = 1 THEN '有'
  WHEN romenondo_kei = 2 THEN '無'
  WHEN romenondo_kei = 0 THEN '不明' END romenondo_kei_str
, CASE WHEN shitei_kei = 1 THEN '有'
  WHEN shitei_kei = 2 THEN '無'
  WHEN shitei_kei = 0 THEN '不明' END shitei_kei_str
, CASE WHEN sekisetsushin_kei = 1 THEN '有'
  WHEN sekisetsushin_kei = 2 THEN '無'
  WHEN sekisetsushin_kei = 0 THEN '不明' END sekisetsushin_kei_str
, CASE WHEN romensekisetsushin_kei = 1 THEN '有'
  WHEN romensekisetsushin_kei = 2 THEN '無'
  WHEN romensekisetsushin_kei = 0 THEN '不明' END romensekisetsushin_kei_str
, CASE WHEN romentouketsu_kei = 1 THEN '有'
  WHEN romentouketsu_kei = 2 THEN '無'
  WHEN romentouketsu_kei = 0 THEN '不明' END romentouketsu_kei_str
, usetsuryou_kei_kentei_dt
, huukouhuusoku_kei_kentei_dt
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
, to_char(create_dt,'YYYY-MM-DD') create_dt
, ' ' || tk_kaisen_id tk_kaisen_id_str
, ' ' || tk_kaisen_kyakuban tk_kaisen_kyakuban_str
EOF;

    $join= <<<EOF
LEFT JOIN
  rfs_m_unei_kbn u
ON
  $daichou_tbl.unei_kbn_cd = u.unei_kbn_cd AND shisetsu_kbn = $shisetsu_kbn
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
