<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("CreateDaichouExcel.php");

// 防雪柵登録用
class CreateDaichouExcelBS extends CreateDaichouExcel {

  protected static $daichou_info_arr = [
    'create_dt'=>'',
    'daityou_no'=>'',
    'zu_no'=>'',
    'sekkei_sokudo'=>'',
    'sakusyu'=>'',
    'saku_kbn'=>'',
    'saku_keishiki'=>'',
    'bikou_kikaku'=>'',
    'kiso_keishiki'=>'',
    'kiso_keijou'=>'',
    'sekou_company'=>'',
    'i_maker_nm'=>'',
    't_maker_nm'=>'',
    'i_katashiki'=>'',
    't_katashiki'=>'',
    'haishi_dt_ryuu'=>'',
    'i_span_len'=>'',
    't_span_len'=>'',
    'i_span_num'=>'',
    't_span_num'=>'',
    'i_sakukou'=>'',
    't_sakukou'=>'',
    'kyoutsuu1'=>'',
    'kyoutsuu2'=>'',
    'kyoutsuu3'=>'',
    'dokuji1'=>'',
    'dokuji2'=>'',
    'dokuji3'=>'',
    'bikou'=>'',
  ];

  /**
  * コンストラクタ
  */
  public function __construct() {
    parent::__construct();
  }

  /***
  *  防雪柵Excelの作成
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
daityou_no
, zu_no
, sekkei_sokudo
, s.sakusyu
, sa.saku_kbn
, sak.saku_keishiki
, bikou_kikaku
, kis.kiso_keishiki
, kiso_keijou
, sekou_company
, i_maker_nm
, i_katashiki
, i_span_len
, i_span_num
, i_sakukou
, t_maker_nm
, t_katashiki
, t_span_len
, t_span_num
, t_sakukou
, haishi_dt_ryuu
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
  rfs_m_sakusyu s
ON
  $daichou_tbl.sakusyu_cd = s.sakusyu_cd
LEFT JOIN
  rfs_m_saku_kbn sa
ON
  $daichou_tbl.saku_kbn_cd = sa.saku_kbn_cd
LEFT JOIN
  rfs_m_saku_keishiki sak
ON
  $daichou_tbl.saku_keishiki_cd = sak.saku_keishiki_cd
LEFT JOIN
  rfs_m_kiso_keishiki kis
ON
  $daichou_tbl.kiso_keishiki_cd = kis.kiso_keishiki_cd
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
