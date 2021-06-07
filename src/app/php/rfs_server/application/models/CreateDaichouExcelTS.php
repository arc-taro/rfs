<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("CreateDaichouExcel.php");

// 浸出装置登録用
class CreateDaichouExcelTS extends CreateDaichouExcel {

  protected static $daichou_info_arr = [
    'create_dt'=>'',
    'dj_old_id'=>'',
    'dj_syadou_haba'=>'',
    'c_kousetsu_str'=>'',
    'c_gaikion_str'=>'',
    'dj_rokata_haba'=>'',
    'c_roon_str'=>'',
    'c_romen_suibun_str'=>'',
    'dj_hodou_haba'=>'',
    'c_sonota_str'=>'',
    'c_sonota_input'=>'',
    'dj_juudan_koubai'=>'',
    'dj_kyokusen_hankei'=>'',
    'dj_kouzou'=>'',
    'dj_dourojoukyou_bikou'=>'',
    'endou_syu'=>'',
    'e_chimoku'=>'',
    'k_all_kadou_hour'=>'',
    'e_syokusei_sta'=>'',
    'k_naiyou'=>'',
    'e_haisui_syori'=>'',
    'j_maker_nm'=>'',
    'j_enkaku_sousa_str'=>'',
    'jigyou_nm'=>'',
    'j_jigyou_cost'=>'',
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
    'c_kousetsu'=>'',
    'c_gaikion'=>'',
    'c_roon'=>'',
    'c_romen_suibun'=>'',
    'c_sonota'=>'',
    'c_censor'=>'',
    't_youryou_chijou'=>'',
    't_youryou_chika'=>'',
    't_youryou_total'=>'',
    'y_kouka_entyou'=>'',
    'y_kouka_fukuin'=>'',
    'y_kouka_total'=>''
  ];

  protected $running_cost;
  protected $repair_cost;

  /**
  * コンストラクタ
  */
  public function __construct() {
    parent::__construct();
  }

  public function setRunningCost($running_cost){
    $this->running_cost = $running_cost;
  }

  public function setRepairCost($repair_cost){
    $this->repair_cost = $repair_cost;
  }

  /***
  *  浸出装置Excelの作成
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
    } else {
      // センサー有無を取得する
      if($daichou_info['c_kousetsu'] == 1 || $daichou_info['c_gaikion'] == 1 || $daichou_info['c_roon'] == 1||
         $daichou_info['c_romen_suibun'] == 1 || $daichou_info['c_sonota'] == 1 || !empty($daichou_info['c_sonota_input'])){
        $daichou_info['c_censor'] = '有';
      } else {
        $daichou_info['c_censor'] = '無';
      }
      // 貯蔵タンク容量（㍑）の全貯蔵容量を取得する
      $daichou_info['t_youryou_total'] = $daichou_info['t_youryou_chijou'] + $daichou_info['t_youryou_chika'];
      // 薬剤効果ひきづり効果範囲を取得する
      $daichou_info['y_kouka_total'] = $daichou_info['y_kouka_entyou'] * $daichou_info['y_kouka_fukuin'];
    }

    // ランニングコスト
    $running_cost = $this->running_cost;

    // 修理費を取得する
    $repair_cost = $this->repair_cost;

    $hosyuu_info = $this->getHosyuuRireki($sno);  // 補修情報を取得する
    $this->setMap($base_info, $daichou_info); // mapをセットする

    $params = array_merge($base_info, $daichou_info, $hosyuu_info, $running_cost, $repair_cost);

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
dj_old_id
, dj_syadou_haba
, dj_rokata_haba
, dj_hodou_haba
, dj_juudan_koubai
, dj_kyokusen_hankei
, dj_kouzou
, dj_dourojoukyou_bikou
, en.endou_syu
, e_chimoku
, e_syokusei_sta
, e_haisui_syori
, j_maker_nm
, CASE
  WHEN j_enkaku_sousa = 1 THEN '有'
  WHEN j_enkaku_sousa = 2 THEN '無'
  WHEN j_enkaku_sousa = 0 THEN '不明'
  END j_enkaku_sousa_str
, ji.jigyou_nm
, j_jigyou_cost
, c_kousetsu
, CASE
  WHEN c_kousetsu = 1 THEN '有'
  ELSE ''
  END c_kousetsu_str
, c_gaikion
, CASE
  WHEN c_gaikion = 1 THEN '有'
  ELSE ''
  END c_gaikion_str
, c_roon
, CASE
  WHEN c_roon = 1 THEN '有'
  ELSE ''
  END c_roon_str
, c_romen_suibun
, CASE
  WHEN c_romen_suibun = 1 THEN '有'
  ELSE ''
  END c_romen_suibun_str
, c_sonota
, CASE
  WHEN c_sonota = 1 THEN '有'
  ELSE ''
  END c_sonota_str
, c_sonota_input
, t_youryou_chijou
, t_youryou_chika
, k_all_kadou_hour
, k_naiyou
, y_kouka_entyou
, y_kouka_fukuin
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
EOF;

      $join= <<<EOF
    LEFT JOIN rfs_m_endou_syu en
    ON $daichou_tbl.endou_syu_cd = en.endou_syu_cd

    LEFT JOIN rfs_m_jigyou_nm ji
    ON $daichou_tbl.jigyou_nm_cd = ji.jigyou_nm_cd
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
