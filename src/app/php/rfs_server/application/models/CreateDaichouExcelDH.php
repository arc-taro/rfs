<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("CreateDaichouExcel.php");

// 道路標識登録用
class CreateDaichouExcelDH extends CreateDaichouExcel {

  protected static $daichou_info_arr = [
    'create_dt'=>'',
    'kousa_tanro'=>'',
    'syurui_bangou'=>'',
    'kousa_rosen'=>'',
    'hyoushiki_syu'=>'',
    'ryuui_jikou'=>'',
    'ban_sunpou'=>'',
    'ban_moji_size'=>'',
    'ban_zaishitsu'=>'',
    'ban_hansya_syoumei'=>'',
    'ban_no_hyouki_umu_str'=>'',
    'ban_tagengo_umu_str'=>'',
    'ban_kyouka1'=>'',
    'ban_kyouka2'=>'',
    'shichuu_houshiki'=>'',
    'shichuu_houshiki_bikou'=>'',
    'shichuu_kikaku'=>'',
    'shichuu_kikaku_bikou'=>'',
    'shichuu_tosou'=>'',
    'shichuu_kiso_keishiki'=>'',
    'shichuu_sunpou'=>'',
    'hyoushikityuu_no'=>'',
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
    'gdh_syubetsu'=>'',
    'kousa_kbn'=>'',
    'brd_color'=>''
  ];

  /**
  * コンストラクタ
  */
  public function __construct() {
    parent::__construct();
  }

  /***
  *  道路標識Excelの作成
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

    $this->load->model('GdhMainModel');

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

    $params = array_merge($base_info, $daichou_info, $hosyuu_info, $huzokubutsu_info);

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

    // 案内標識データベース 対応状況取得
    $response_status = $this->getGdhResponseStatus($sno);

    // 案内標識板面数、健全性取得
    $gdh_idx_count = $this->GdhMainModel->countGdhIdx($sno);
    // 健全性取得
    $judge = $this->getShisetsuJudge($sno);

    $add_info = array();
    $add_info['gdh_idx_count']=$gdh_idx_count;  // 案内標識板面数
    $add_info['measures_shisetsu_judge_nm']=""; // 健全性

    if (isset($judge)) {
      if (isset($judge['measures_shisetsu_judge_nm'])) {
        $add_info['measures_shisetsu_judge_nm'] = $judge['measures_shisetsu_judge_nm'];
      }
    }

    // ページ数判定
    if ($gdh_idx_count <= 6) {
      $pageNo = 1;
    } else {
      // 様式その3のページ数計算
      $pageNo = $gdh_idx_count / 6;
      $tmp = $gdh_idx_count % 6;
      if ($tmp != 0) {
        $pageNo = $pageNo + 1;
      }
    }

    // ページ数回繰り返し
    for ($i = 1; $i <= $pageNo; $i++) {
      if ($i > 1) {
        // 対応状況データから先頭6件削除
        $response_status = array_splice($response_status, 6);
      }

      // データ設定
      $response_status_info = $this->initGdhResponseStatusInfo($i);
      $response_status_info = $this->createGdhResponseStatusInfo($response_status_info, $response_status);

      $params = array();
      $params = array_merge($base_info, $daichou_info, $hosyuu_info, $huzokubutsu_info, $response_status_info, $add_info);

      // その3シート作成
      if ($i == 1) {
        if (in_array('施設台帳様式その3', $this->included_sheets)) {
          // 図面のシートを作る
          $this->editSheet('施設台帳様式その3', $params);
        }
      } else {
        // その3シートの2ページ目以降作成
        $this->editSheetForCopy('施設台帳様式その3','施設台帳様式その3_'.$i, $params);
      }
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
kousa_tanro
,syurui_bangou
,kousa_rosen
,hyoushiki_syu
,ryuui_jikou
,ban_sunpou
,ban_moji_size
,ban_zaishitsu
,ban_hansya_syoumei
,ban_no_hyouki_umu
,ban_tagengo_umu
,ban_kyouka1
,ban_kyouka2
,shichuu_houshiki
,shichuu_houshiki_bikou
,shichuu_kikaku
,shichuu_kikaku_bikou
,shichuu_tosou
,shichuu_kiso_keishiki
,shichuu_sunpou
,hyoushikityuu_no
,d_hokuden_kyakuban
,d_keiyaku_houshiki
,d_hikikomi
,d_denki_dai
,d_denki_ryou
,kyoutsuu1
,kyoutsuu2
,kyoutsuu3
,dokuji1
,dokuji2
,dokuji3
,bikou
,to_char(create_dt,'YYYY.MM.DD') create_dt
, CASE
        WHEN ban_no_hyouki_umu = 1 THEN '有'
        WHEN ban_no_hyouki_umu = 2 THEN '無'
        WHEN ban_no_hyouki_umu = 0 THEN '不明'
        END ban_no_hyouki_umu_str
, CASE
        WHEN ban_tagengo_umu = 1 THEN '有'
        WHEN ban_tagengo_umu = 2 THEN '無'
        WHEN ban_tagengo_umu = 0 THEN '不明'
        END ban_tagengo_umu_str
, gdh_m_gdh_syubetsu.gdh_syubetsu
, gdh_m_kousa.kousa_kbn
, gdh_m_brd_color.brd_color
EOF;

    $join= <<<EOF
LEFT JOIN
  rfs_m_kousa_tanro tan
ON
  $daichou_tbl.kousa_tanro_cd = tan.kousa_tanro_cd
LEFT JOIN
  rfs_m_hyoushiki_syu hyo
ON
  $daichou_tbl.hyoushiki_syu_cd = hyo.hyoushiki_syu_cd
LEFT JOIN
  rfs_m_shichuu_houshiki hou
ON
  $daichou_tbl.shichuu_houshiki_cd = hou.shichuu_houshiki_cd
LEFT JOIN
  rfs_m_shichuu_kikaku kik
ON
  $daichou_tbl.shichuu_kikaku_cd = kik.shichuu_kikaku_cd
LEFT JOIN gdh_t_shisetsu_sub
  ON $daichou_tbl.sno = gdh_t_shisetsu_sub.sno
LEFT JOIN gdh_m_gdh_syubetsu
  ON gdh_t_shisetsu_sub.gdh_syubetsu_cd = gdh_m_gdh_syubetsu.gdh_syubetsu_cd
LEFT JOIN gdh_m_kousa
  ON gdh_t_shisetsu_sub.kousa_kbn_cd = gdh_m_kousa.kousa_kbn_cd
LEFT JOIN gdh_t_brd
  ON $daichou_tbl.sno = gdh_t_brd.sno
LEFT JOIN gdh_m_brd_color
  ON gdh_t_brd.brd_color_cd = gdh_m_brd_color.brd_color_cd
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

// log_message('debug', "DH sql=$sql");
// $r = print_r($result, true);
// log_message('debug', "DH result=$r");

    return $result;

  }

  /**
   * 健全性 取得
   *
   * 引数：$sno
   *
   * 戻り値：健全性
   */
  private function getShisetsuJudge($sno) {
    log_message('debug', 'getShisetsuJudge');

    $sql= <<<EOF
with rfsTChkHuzokubutsu AS (
  SELECT
    tmp1.*
    , rfsMShisetsuJudge.shisetsu_judge_nm
  FROM
    rfs_t_chk_huzokubutsu tmp1 JOIN (
      SELECT
        chk_mng_no
        , MAX(rireki_no) rireki_no
      FROM
        rfs_t_chk_huzokubutsu
      GROUP BY
        chk_mng_no
    ) tmp2
      ON tmp1.chk_mng_no = tmp2.chk_mng_no
      AND tmp1.rireki_no = tmp2.rireki_no
    LEFT JOIN rfs_m_shisetsu_judge rfsMShisetsuJudge
      ON rfsMShisetsuJudge.shisetsu_judge = tmp1.measures_shisetsu_judge
  ORDER BY
    chk_mng_no
)
/* 最新の点検結果取得  */
, rfsTChkMainMax AS (
  SELECT
    tmp1.*
  FROM
    rfs_t_chk_main tmp1 JOIN (
      SELECT
        sno
        , MAX(chk_times) chk_times
      FROM
        rfs_t_chk_main
      GROUP BY
        sno
    ) tmp2
      ON tmp1.sno = tmp2.sno
      AND tmp1.chk_times = tmp2.chk_times
  ORDER BY
    sno
)
SELECT
  rfsMShisetsu.sno
  , rfsTChkHuzokubutsu.check_shisetsu_judge
  , rfsTChkHuzokubutsu.measures_shisetsu_judge
  , judgeC.shisetsu_judge_nm as check_shisetsu_judge_nm
  , judgeM.shisetsu_judge_nm as measures_shisetsu_judge_nm
FROM
  rfs_m_shisetsu rfsMShisetsu
  LEFT JOIN rfsTChkMainMax rfsTChkMainMax
    ON rfsTChkMainMax.sno = rfsMShisetsu.sno
  LEFT JOIN rfsTChkHuzokubutsu rfsTChkHuzokubutsu
    ON rfsTChkHuzokubutsu.chk_mng_no = rfsTChkMainMax.chk_mng_no
  LEFT JOIN rfs_m_shisetsu_judge judgeC
    ON judgeC.shisetsu_judge = rfsTChkHuzokubutsu.check_shisetsu_judge
  LEFT JOIN rfs_m_shisetsu_judge judgeM
    ON judgeM.shisetsu_judge = rfsTChkHuzokubutsu.measures_shisetsu_judge
WHERE
  rfsMShisetsu.sno = $sno
EOF;

    $query = $this->rfs->query($sql);
    $result = null;
    if(isset($query->result('array')[0])){
      $result = $query->result('array')[0];
    }
    return $result;
  }

  /**
   * 案内標識データベース 対応状況取得
   *
   * 引数：$sno
   *
   * 戻り値：対応状況
   */
  private function getGdhResponseStatus($sno) {
    log_message('debug', 'getGdhResponseStatus');

    $sql= <<<EOF
/* 履歴番号最大を取得  */
WITH tResponseStatusMax AS (
  SELECT
    tmp1.*
  FROM
    gdh_t_response_status tmp1 JOIN (
      SELECT
        sno
        , gdh_idx
        , MAX(rireki_no) rireki_no
      FROM
        gdh_t_response_status
      WHERE
        gdh_idx > 0
      GROUP BY
        sno
        , gdh_idx
    ) tmp2
      ON tmp1.sno = tmp2.sno
      AND tmp1.gdh_idx = tmp2.gdh_idx
      AND tmp1.rireki_no = tmp2.rireki_no
  WHERE
    tmp1.sno = $sno
    AND tmp1.gdh_idx > 0
)
, gdh_detail AS (
  SELECT
    tResponseStatusMax.sno
    , tResponseStatusMax.gdh_idx
    , tResponseStatusMax.rireki_no
    , tResponseStatusMax.taisaku_kbn_cd
    , mTaisakuKbn.taisaku_kbn
    , tResponseStatusMax.taisaku_status_cd
    , smTaisakuStatus.taisaku_status
    , tResponseStatusMax.yotei_nendo_yyyy
    , tResponseStatusMax.taisaku_kouhou_cd
    , mTaisakuKouhou.taisaku_kouhou
    , tResponseStatusMax.dououdou
    , smGaitouHigaitou.gaitou_higaitou
  FROM
    tResponseStatusMax tResponseStatusMax
    LEFT JOIN gdh_m_taisaku_kbn mTaisakuKbn
      ON mTaisakuKbn.taisaku_kbn_cd = tResponseStatusMax.taisaku_kbn_cd
    LEFT JOIN gdh_sm_taisaku_status smTaisakuStatus
      ON smTaisakuStatus.taisaku_status_cd = tResponseStatusMax.taisaku_status_cd
    LEFT JOIN gdh_sm_gaitou_higaitou smGaitouHigaitou
      ON smGaitouHigaitou.gaitou_higaitou_cd = tResponseStatusMax.dououdou
    LEFT JOIN gdh_m_taisaku_kouhou mTaisakuKouhou
      ON mTaisakuKouhou.taisaku_kouhou_cd = tResponseStatusMax.taisaku_kouhou_cd
  WHERE
    tResponseStatusMax.sno = $sno
  ORDER BY
    tResponseStatusMax.sno
    , tResponseStatusMax.gdh_idx
    , tResponseStatusMax
    , taisaku_kbn_cd
)
, gdhPicture AS (
  SELECT
    tmp1.sno
    , tmp1.gdh_idx
    , tmp1.picture_cd
    , tmp1.path
    , tmp1.update_dt
    , tmp1.lat
    , tmp1.lon
    , tmp1.use_flg
    , TO_CHAR(tmp1.exif_dt, 'yyyy/MM/dd') AS exif_dt
    , TO_CHAR(tmp1.shooting_dt, 'yyyy/MM/dd') AS shooting_dt
    , tmp1.description
  FROM
    gdh_t_picture tmp1 JOIN (
      SELECT
        sno
        , gdh_idx
        , MAX(picture_cd) picture_cd
      FROM
        gdh_t_picture
      WHERE
        gdh_idx > 0
        AND use_flg = 1
      GROUP BY
        sno
        , gdh_idx
    ) tmp2
      ON tmp1.sno = tmp2.sno
      AND tmp1.gdh_idx = tmp2.gdh_idx
      AND tmp1.picture_cd = tmp2.picture_cd
  WHERE
    tmp1.sno = $sno
    AND use_flg = 1
  ORDER BY
    sno
    , gdh_idx
)
SELECT
  main.gdh_idx
  , main.taisaku_status
  , main.taisaku_kouhou
  , main.dououdou
  , main.gaitou_higaitou
  , main.yotei_nendo_yyyy
  , '$this->excel_root' || gdhPicture.path AS path
  , COALESCE(gdhPicture.shooting_dt, gdhPicture.exif_dt, '不明') AS shooting_dt
FROM
  (
    SELECT
      gdh_idx
      , JSON_AGG(taisaku_status) AS taisaku_status
      , JSON_AGG(taisaku_kouhou) AS taisaku_kouhou
      , JSON_AGG(dououdou) AS dououdou
      , JSON_AGG(gaitou_higaitou) AS gaitou_higaitou
      , JSON_AGG(yotei_nendo_yyyy) AS yotei_nendo_yyyy
    FROM
      gdh_detail
    GROUP BY
      gdh_idx
    ORDER BY
      gdh_idx
  ) main
  LEFT JOIN gdhPicture gdhPicture
    ON main.gdh_idx = gdhPicture.gdh_idx
ORDER BY main.gdh_idx
EOF;

    $query = $this->rfs->query($sql);
    $result = $query->result('array');

    return $result;
  }

  /**
    * 初期化
    *
    * 引数：$pageNo ページ番号
    *
    * 戻り値:$response_status_info 対応状況格納 初期化データ
    */
  private function initGdhResponseStatusInfo($pageNo) {
    log_message('DEBUG', "initGdhResponseStatusInfo");

    $renban=0;
    if ($pageNo>0) {
      $renban = $pageNo*6-6;
    }
    $response_status_info = array();
    // 対応状況初期化
    for ($i=1; $i<=6;$i++) {
      // 連番
      $response_status_info["idx_num_$i"]=$i+$renban;
      // 板面写真、撮影日
      $response_status_info["banmen_photo_$i"]="-";
      $response_status_info["shooting_dt_$i"]="-";
      for ($j=1; $j<=5;$j++) {
        // 対策、対策工法、予定年度
        $response_status_info["taisaku_status_$i"."_"."$j"]="-";
        $response_status_info["taisaku_kouhou_$i"."_"."$j"]="-";
        $response_status_info["yotei_nendo_yyyy_"."$j"]="-";
      }
    }
    return $response_status_info;
  }

  /**
    * 対応状況データ設定
    * 引数：$response_status_info 対応状況格納
    *      $response_status 対応状況取得結果
    */
  private function createGdhResponseStatusInfo($response_status_info, $response_status) {
    log_message('DEBUG', "createGdhResponseStatusInfo");

    // 初期化
    $idx = 0;
    $preIdx=0;

    for ($i = 0; $i < count($response_status); $i++) {
      $gdh_idx = $response_status[$i]['gdh_idx'];
      if ($preIdx != $gdh_idx) {
        $idx++;
      }

      // 撮影日
      $response_status_info['shooting_dt_'.$idx]=$response_status[$i]['shooting_dt'];
      // 板面写真
      $response_status_info['banmen_photo_'.$idx]=$response_status[$i]['path'];

      // 道央道判定
      $dououdou = 0;
      $dououdou_lst = json_decode($response_status[$i]['dououdou']);
      for ($j = 0; $j < count($dououdou_lst); $j++) {
        if ($j == 3) {
          $dououdou = $dououdou_lst[$j];
        }
      }

      // 対策
      $taisaku_status_lst = json_decode($response_status[$i]['taisaku_status']);
      // 道央道該当
      $dououdou_gaitou_lst = json_decode($response_status[$i]['gaitou_higaitou']);
      // 対策区分毎に対策の値を取得
      for ($j = 0; $j < count($taisaku_status_lst); $j++) {
        $kbn = $j +1;
        // 対策 値設定
        if (isset($taisaku_status_lst[$j])) {
          $response_status_info["taisaku_status_$idx"."_"."$kbn"]=$taisaku_status_lst[$j];
        } else {
          $response_status_info["taisaku_status_$idx"."_"."$kbn"]="-";
        }
        // 道央道 該当/非該当
        if ($j == 3) {
          $response_status_info["taisaku_status_$idx"."_5"]=$dououdou_gaitou_lst[$j];
        }
      }

      // 対策工法
      $taisaku_kouhou_lst = json_decode($response_status[$i]['taisaku_kouhou']);
      // 対策区分毎に対策工法の値を取得
      for ($j = 0; $j < count($taisaku_kouhou_lst); $j++) {
        $kbn = $j +1;
        // 対策工法 値設定
        if (isset($taisaku_kouhou_lst[$j])) {
          $response_status_info["taisaku_kouhou_$idx"."_"."$kbn"]=$taisaku_kouhou_lst[$j];
        } else {
          $response_status_info["taisaku_kouhou_$idx"."_"."$kbn"]="-";
        }
      }

      // 予定年度
      $yotei_nendo_yyyy_lst = json_decode($response_status[$i]['yotei_nendo_yyyy']);
      for ($j = 0; $j < count($yotei_nendo_yyyy_lst); $j++) {
        $kbn = $j + 1;
        if (isset($yotei_nendo_yyyy_lst[$j])) {
          $yotei_nendo = $this->getWareki($yotei_nendo_yyyy_lst[$j]);
          $response_status_info["yotei_nendo_yyyy_$kbn"] = $yotei_nendo;
        } else {
          $response_status_info["yotei_nendo_yyyy_$kbn"] = "-";
        }
      }
      $preIdx = $gdh_idx;
    }
    return $response_status_info;
  }

  // 西暦を和暦に変換
  public function getWareki($year) {
    $sql = <<<SQL
SELECT
      * 
  FROM
    v_wareki_seireki 
  WHERE
    seireki = $year
SQL;
    $query = $this->rfs->query($sql);
    $tmp = $query->result('array');
    return $tmp[0]['wareki_ryaku'];

    // if($year == 1989) { // 平成元年
    //   return $year_name . $year . 'H元';
    // } else if($year > 1989) { // 平成
    //   $year_name = "H";
    //   $year -= 1988;
    //   return $year_name . $year;
    // } else if($year >= 1925) { // 昭和
    //   $year_name = "S";
    //   $year -= 1925;
    //   return $year_name . $year;
    // } else {
    //   return '';
    // }
  }
}
