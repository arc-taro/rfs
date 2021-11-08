<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 道路施設台帳に関するモデル
 * 返却は配列
 *
 * @access public
 * @package Model
 */
class FamEditModel extends CI_Model {
  protected $DB_rfs;  // rfsコネクション
  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
    $this->DB_rfs = $this->load->database('rfs',TRUE);
    if ($this->DB_rfs->conn_id === FALSE) {
      log_message('debug', 'データベースに接続されていません');
      return;
    }
  }

  /**
   *
   * 台帳の取得
   *  snoと施設区分により台帳を取得する
   *
   * 引数:sno、tbl_nm
   * 戻り値:array
   */
  public function getDaityou($sno, $tbl_nm) {
    log_message('info', 'getDaityou');
    $Fields_str=$this->getFields($tbl_nm);
    $sql= <<<EOF
SELECT
  $Fields_str
FROM
  $tbl_nm
WHERE
  sno = $sno
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  // 各施設区分の取得カラム(全て)をSQLのSELECT句として返す
  // ※ものによってフォーマットが必要なため
  // 戻り値、カラム文字列(フォーマット含む)
  protected function getFields($tbl_nm) {
    log_message('info', 'getFields');
    // カラム名を取得
    $column=$this->column($tbl_nm);

    // 日付型の列文字列を変更する
    $col="";
    for ($i=0;$i<count($column);$i++){
      if ($i>0) {
        $col.=", ";
      }
      // timestamp型はフォーマット
      if(preg_match('/timestamp/',$column[$i]['data_type'])){
        $column[$i]['column_name']="to_char(".$column[$i]["column_name"].",'YYYY-MM-DD') ".$column[$i]["column_name"];
      }
      $col.=$column[$i]['column_name'];
    }
    return $col;
  }

  // 引数のテーブル名の列を全て取得し配列で返却
  protected function column($tbl_nm) {
    log_message('info', 'column');
    $sql= <<<EOF
SELECT
    column_name, data_type
FROM
    information_schema.columns
WHERE
    table_name = '$tbl_nm'
ORDER BY
    ordinal_position;
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }


  /**
   *
   * 補修履歴の取得
   *  snoにぶら下がる補修履歴を取得
   *
   * 引数:sno
   * 戻り値:array
   */
  public function getHosyuuRireki($sno) {
    log_message('info', 'getHosyuuRireki');
    $sql= <<<EOF
SELECT
  *
FROM
  rfs_t_chk_hosyuu_rireki
WHERE
  sno = $sno
ORDER BY
  hosyuu_rireki_id
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /**
   *
   * 案内標識データベースデータの取得
   *  snoにぶら下がる案内標識DBデータを取得
   *
   * 引数:sno
   * 戻り値:array
   */
  public function getGdhDBData($sno) {
    log_message('info', 'getGdhDBData');
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
        AND sno = $sno
      GROUP BY
        sno
        , gdh_idx
    ) tmp2
      ON tmp1.sno = tmp2.sno
      AND tmp1.rireki_no = tmp2.rireki_no
      AND tmp1.gdh_idx = tmp2.gdh_idx
  WHERE tmp1.gdh_idx > 0
  ORDER BY
    sno
    , gdh_idx
)
/* 対応状況取得 */
, taisakuStatusMain AS (
  SELECT
    tResponseStatusMax.gdh_idx
    , tResponseStatusMax.sno
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
)
, gdhPictures AS (
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
    , TO_CHAR(tmp1.shooting_dt, 'yyyy/MM/dd') AS  shooting_dt
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
  jsonb_agg(to_jsonb(main)) response_info
FROM
  (
    SELECT
      TMP.gdh_idx
      , jsonb_agg(to_jsonb(TMP) ORDER BY taisaku_kbn_cd) AS detail
    FROM
      (
        SELECT
          taisakuStatusMain.sno
          , taisakuStatusMain.gdh_idx
          , mTaisakuKbn.taisaku_kbn_cd
          , mTaisakuKbn.taisaku_kbn
          , COALESCE(taisakuStatusMain.taisaku_status, '未入力') AS  taisaku_status
          , taisakuStatusMain.yotei_nendo_yyyy
          , warekiseirekifuture.wareki_ryaku
          , taisakuStatusMain.taisaku_kouhou
          , taisakuStatusMain.dououdou
          , taisakuStatusMain.gaitou_higaitou
          , gdhPictures.picture_cd
          , gdhPictures.path
          , gdh_m_outside_url.outside_url
        FROM
          gdh_m_taisaku_kbn mTaisakuKbn
          LEFT JOIN taisakuStatusMain taisakuStatusMain
            ON taisakuStatusMain.taisaku_kbn_cd = mTaisakuKbn.taisaku_kbn_cd
          LEFT JOIN gdhPictures gdhPictures
            ON gdhPictures.sno = taisakuStatusMain.sno
            AND gdhPictures.gdh_idx = taisakuStatusMain.gdh_idx
          LEFT JOIN gdh_m_outside_url
            ON taisakuStatusMain.sno = gdh_m_outside_url.sno
            AND taisakuStatusMain.gdh_idx = gdh_m_outside_url.gdh_idx
          LEFT JOIN v_wareki_seireki_future warekiseirekifuture
            ON taisakuStatusMain.yotei_nendo_yyyy = warekiseirekifuture.seireki
        ORDER BY
          taisakuStatusMain.gdh_idx
          , taisakuStatusMain.taisaku_kbn_cd
      ) TMP
    GROUP BY
      TMP.gdh_idx
  ) main
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /**
   *
   * ランニングコストの取得
   *  snoにぶら下がるランニングコストを取得
   *
   * 引数:sno
   * 戻り値:array
   */
  public function getRunningCost($sno) {
    log_message('info', 'getRunningCost');
    $sql= <<<EOF
SELECT
  *
FROM
  rfs_t_running_cost
WHERE
  sno = $sno
ORDER BY
  running_cost_id
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /**
   *
   * 修理費の取得
   *  snoにぶら下がる修理費を取得
   *
   * 引数:sno
   * 戻り値:array
   */
  public function getRepairCost($sno) {
    log_message('info', 'getRepairCost');
    $sql= <<<EOF
SELECT
  *
FROM
  rfs_t_repair_cost
WHERE
  sno = $sno
ORDER BY
  repair_cost_id
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /**
   * 施設台帳保存
   *  台帳と補修履歴を保存する
   */
  public function saveShisetsuDaichou($post) {
    log_message('info', 'saveShisetsuDaichou');

    // トランザクション
    $this->DB_rfs->trans_start();
    $this->saveDaichou($post['daichou']);
    // 浸出装置のみ
    if ($post['daichou']['shisetsu_kbn']==20) {
      $this->saveCost($post['running_cost'],$post['repair_cost']);
    }
    $this->saveHosyuuRireki($post['hosyuu']);
    // 確定
    $this->DB_rfs->trans_complete();
    return;
  }

  /***
   *  台帳:共通部分の変数セット
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  protected function setDaichouCommon(&$daichou) {
    // 共通
    $daichou['sno']=$daichou['sno'];
    $daichou['shisetsu_kbn']=$daichou['shisetsu_kbn'];
    $daichou['bikou']=$this->chkItem($daichou, 'bikou',2);
    $daichou['kyoutsuu1']=$this->chkItem($daichou, 'kyoutsuu1',2);
    $daichou['kyoutsuu2']=$this->chkItem($daichou, 'kyoutsuu2',2);
    $daichou['kyoutsuu3']=$this->chkItem($daichou, 'kyoutsuu3',2);
    $daichou['dokuji1']=$this->chkItem($daichou, 'dokuji1',2);
    $daichou['dokuji2']=$this->chkItem($daichou, 'dokuji2',2);
    $daichou['dokuji3']=$this->chkItem($daichou, 'dokuji3',2);
    $daichou['create_account']=$this->chkItem($daichou, 'create_account',2);;
    $daichou['create_dt']=pg_escape_literal($daichou['create_dt']);
    $daichou['update_account'] = $this->session['ath']['account_cd'];
    // 最終更新者追加START
    $daichou['update_busyo_cd'] = $this->session['ath']['busyo_cd'];
    $daichou['update_account_nm'] = $this->chkItem($daichou, 'update_account_nm',2);
    // 最終更新者追加END
    // 作成者が無い場合
    if ($daichou['create_account']=="") {
      // ログインユーザーをセット
      $daichou['create_account'] = $this->session['ath']['account_cd'];
    }
  }

  /***
   *  台帳:通信回線の変数セット
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  protected function setDaichouTsuushin(&$daichou) {
    // 通信回線
    $daichou['tk_kaisen_syu']=$this->chkItem($daichou, 'tk_kaisen_syu',2);
    $daichou['tk_kaisen_kyori']=$this->chkItem($daichou, 'tk_kaisen_kyori',2);
    $daichou['tk_kaisen_id']=$this->chkItem($daichou, 'tk_kaisen_id',2);
    $daichou['tk_kaisen_kyakuban']=$this->chkItem($daichou, 'tk_kaisen_kyakuban',2);
    $daichou['tk_setsuzoku_moto']=$this->chkItem($daichou, 'tk_setsuzoku_moto',2);
    $daichou['tk_setsuzoku_saki']=$this->chkItem($daichou, 'tk_setsuzoku_saki',2);
    $daichou['tk_getsugaku']=$this->chkItem($daichou, 'tk_getsugaku',2);
    $daichou['tk_waribiki']=$this->chkItem($daichou, 'tk_waribiki',2);
  }

  /***
   *  台帳:電気の変数セット
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  protected function setDaichouDenki(&$daichou) {
    // 電気
    $daichou['d_hokuden_kyakuban']=$this->chkItem($daichou, 'd_hokuden_kyakuban',2);
    $daichou['d_keiyaku_houshiki']=$this->chkItem($daichou, 'd_keiyaku_houshiki',2);
    $daichou['d_kaisen_id']=$this->chkItem($daichou, 'd_kaisen_id',2);
    $daichou['d_kaisen_kyakuban']=$this->chkItem($daichou, 'd_kaisen_kyakuban',2);
    $daichou['d_hikikomi']=$this->chkItem($daichou, 'd_hikikomi',2);
    $daichou['d_denki_dai']=$this->chkItem($daichou, 'd_denki_dai',2);
    $daichou['d_denki_ryou']=$this->chkItem($daichou, 'd_denki_ryou',2);
  }

  /***
   *  補修履歴の更新
   *    画面で入力された内容を正とし、
   *    全削除、全登録を行う。
   *    また、削除チェックがされているものは登録しない
   *
   *  引数
   *    $hosyuu 画面入力の補修履歴
   ***/
  protected function saveHosyuuRireki($hosyuu) {
    log_message('info', "saveHosyuuRireki");
    // データが無い場合は終了
    if (count($hosyuu) == 0) {
      return;
    }
    // 該当補修履歴の削除
    $this->delHosyuuRireki($hosyuu[0]['sno']);
    // データの整備
    $cnt=0;
    for ($i=0;$i<count($hosyuu);$i++) {
      // delflgが1の場合は、登録から除外
      if (isset($hosyuu[$i]['delflg'])) {
        if ($hosyuu[$i]['delflg']==1) {
          continue;
        }
      }
      $cnt++;
      $setArr=array();
      // 登録内容をセット
      $setArr['sno']=$hosyuu[$i]['sno'];
      $setArr['hosyuu_rireki_id']=$cnt;
      $setArr['check_nendo']=$this->chkItem($hosyuu[$i], 'check_nendo',2);
      $setArr['check_yyyy']=$this->chkItem($hosyuu[$i], 'check_yyyy',1);
      $setArr['check_naiyou']=$this->chkItem($hosyuu[$i], 'check_naiyou',2);
      $setArr['repair_nendo']=$this->chkItem($hosyuu[$i], 'repair_nendo',2);
      $setArr['repair_yyyy']=$this->chkItem($hosyuu[$i], 'repair_yyyy',1);
      $setArr['repair_naiyou']=$this->chkItem($hosyuu[$i], 'repair_naiyou',2);
      // 補修履歴登録
      $this->insHosyuuRireki($setArr);
    }
  }

  /***
   *  補修履歴の削除
   *    引数snoの補修履歴を全て削除する
   *
   *  引数
   *    $sno 施設システム内番号
   ***/
  protected function delHosyuuRireki($sno) {
    log_message('info', "delHosyuuRireki");
    $sql= <<<EOF
DELETE FROM
  rfs_t_chk_hosyuu_rireki
WHERE
  sno = $sno
EOF;
    $query = $this->DB_rfs->query($sql);
  }

  /***
   *  補修履歴の登録
   *    引数の1レコードを登録する
   *
   *  引数
   *    $hosyuu 補修履歴1レコード
   ***/
  protected function insHosyuuRireki($hosyuu) {
    log_message('info', "insHosyuuRireki");
    $sql= <<<EOF
INSERT
INTO rfs_t_chk_hosyuu_rireki(
  sno
  , hosyuu_rireki_id
  , check_nendo
  , check_yyyy
  , check_naiyou
  , repair_nendo
  , repair_yyyy
  , repair_naiyou
) VALUES (
  ${hosyuu['sno']}
  , ${hosyuu['hosyuu_rireki_id']}
  , ${hosyuu['check_nendo']}
  , ${hosyuu['check_yyyy']}
  , ${hosyuu['check_naiyou']}
  , ${hosyuu['repair_nendo']}
  , ${hosyuu['repair_yyyy']}
  , ${hosyuu['repair_naiyou']}
);
EOF;
    $query = $this->DB_rfs->query($sql);
  }

  /***
   *  登録用項目チェック
   *    登録項目がある場合その値(文字列の場合は登録用文字列)、
   *    無い場合は数値項目はNULLを、文字列項目は空文字を返却
   *
   *  引数
   *    $obj 項目格納オブジェクト
   *    $key 項目キー
   *    $kbn 1:数値項目、2:文字列項目
   ***/
  protected function chkItem($obj, $key, $kbn){
    if ($kbn==1) {
      // 数値項目
      $obj[$key]=isset($obj[$key])?$obj[$key]:"null";
    }else if ($kbn==2) {
      $obj[$key]=isset($obj[$key])?pg_escape_literal($obj[$key]):"null";
    }else if ($kbn==3) {
      $obj[$key]=isset($obj[$key])?pg_escape_literal($obj[$key]):"'f'";
    }
    return $obj[$key];
  }

  /***
   * 附属物点検履歴を取得する
   * 点検回数で降順
   *
   * 引数:$sno sno
   *     $shisetsu_kbn 施設区分
   *
   ***/
  public function getHuzokubutsu($sno, $shisetsu_kbn){

    // SQL1発だと複雑で時間が掛かりそう。
    // 点検の回数は多くならないので、分けて取得することにする
    // 点検データを回数単位で取得
    $chk_main = $this->getChkMainData($sno, $shisetsu_kbn);
    // 点検データが無い場合は終了
    if (count($chk_main)==0) {
      return $chk_main;
    }
    // 全点検データループ
    for ($i=0;$i<count($chk_main);$i++) {
      $chk_mng_no=$chk_main[$i]['chk_mng_no'];
      $check_shisetsu_judge=$chk_main[$i]['check_shisetsu_judge'];
      // 点検年を和暦にする
      // ----> SQLに和暦変換を仕込む
      //$chk_main[$i]['chk_dt']=$this->chgWareki($chk_main[$i]['chk_dt']);
      $chk_main[$i]['chk_dt']=$chk_main[$i]['w_chk_dt'];
      // 部材名をぶら下げる
      $buzai_nms=$this->getBuzaiNm($shisetsu_kbn, $chk_mng_no, $check_shisetsu_judge);
      $chk_main[$i]['buzai_nm']=$buzai_nms;
      // 措置年をぶら下げる
      $sochi_dt=$this->getSoshiDt($chk_mng_no);
      $chk_main[$i]['sochi_dt']=$sochi_dt;
    }
    return $chk_main;
  }

  /***
   * 点検回数毎の点検票データを、施設の点検結果を含めて取得する。
   * 点検回数で降順、支柱インデックスで昇順
   *
   * 引数:$sno sno
   *
   ***/
  protected function getChkMainData($sno, $shisetsu_kbn){
    log_message('info', 'getChkMainData');

    // 防雪柵の場合は親はいらない
    // それ以外はstruct_idx=0
    $where_struct_idx="";
    if ($shisetsu_kbn == 4) {
      // 防雪柵
      $where_struct_idx = "AND struct_idx > 0";
    } else {
      $where_struct_idx = "AND struct_idx = 0";
    }

    $sql= <<<EOF
SELECT
    c.chk_mng_no
  , c.sno
  , c.chk_times
  , c.struct_idx
  , h.chk_dt
  , wareki_to_char(h.chk_dt, 'ggLL年') w_chk_dt
  , h.check_shisetsu_judge
  , sj1.shisetsu_judge_nm check_shisetsu_judge_nm
  , h.syoken
  , h.measures_shisetsu_judge
  , sj2.shisetsu_judge_nm measures_shisetsu_judge_nm
FROM
  (SELECT * FROM rfs_t_chk_main WHERE sno = $sno $where_struct_idx) c JOIN (
      SELECT
          h1.*
      FROM
        rfs_t_chk_huzokubutsu h1 JOIN (
          SELECT
              chk_mng_no
            , MAX(rireki_no) rireki_no
          FROM
            rfs_t_chk_huzokubutsu
          GROUP BY
            chk_mng_no
        ) h2
          ON h1.chk_mng_no = h2.chk_mng_no
          AND h1.rireki_no = h2.rireki_no
    ) h
    ON c.chk_mng_no = h.chk_mng_no
    LEFT JOIN rfs_m_shisetsu_judge sj1
    ON h.check_shisetsu_judge = sj1.shisetsu_judge
    LEFT JOIN rfs_m_shisetsu_judge sj2
    ON h.measures_shisetsu_judge = sj2.shisetsu_judge
ORDER BY
  c.chk_times DESC
  , struct_idx
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /***
   * 該当の点検で最悪の健全性(既求)を全て取得し、部材名を「、」で連結し返却する
   *
   * 引数:$shisetsu_kbn 施設区分
   *      $chk_mng_no chk_mng_no
   *      $check_shisetsu_judge 点検時健全性
   *
   ***/
  protected function getBuzaiNm($shisetsu_kbn, $chk_mng_no, $check_shisetsu_judge){
    $buzai = $this->getChkBuzaiData($shisetsu_kbn, $chk_mng_no, $check_shisetsu_judge);
    // 部材名を連結
    $buzai_nms="";
    for ($j=0;$j<count($buzai);$j++) {
      if ($j>0) {
        $buzai_nms.="、";
      }
      $buzai_nms.=$buzai[$j]['buzai_nm'];
    }
    return $buzai_nms;
  }

  /***
   * 部材データで引数のchk_mng_no内で最悪の健全性の部材レコードを取得する。
   * 同じ健全性がある場合も複数取得する。
   *
   * 引数:$shisetsu_kbn 施設区分
   *      $chk_mng_no chk_mng_no
   *      $check_shisetsu_judge 点検時健全性
   *
   ***/
  protected function getChkBuzaiData($shisetsu_kbn, $chk_mng_no, $check_shisetsu_judge){
    log_message('info', 'getChkBuzaiData');
    $sql= <<<EOF
SELECT
    b.chk_mng_no
  , b.buzai_cd
  , b_mst.buzai_nm
  , b.check_buzai_judge
FROM
  (
    SELECT
        b1.*
    FROM
      (
        SELECT
            *
        FROM
          rfs_t_chk_buzai
        WHERE
          chk_mng_no = $chk_mng_no
-- 点検管理番号があるからいらないのでは？
--          AND check_buzai_judge = $check_shisetsu_judge
      ) b1 JOIN (
        SELECT
            chk_mng_no
          , buzai_cd
          , MAX(rireki_no) rireki_no
        FROM
          rfs_t_chk_buzai
        WHERE
          chk_mng_no = $chk_mng_no
-- 点検管理番号があるからいらないのでは？
--          AND check_buzai_judge = $check_shisetsu_judge
        GROUP BY
          chk_mng_no
          , buzai_cd
          , rireki_no
      ) b2
        ON b1.chk_mng_no = b2.chk_mng_no
        AND b1.buzai_cd = b2.buzai_cd
        AND b1.rireki_no = b2.rireki_no
  ) b
  LEFT JOIN (
    SELECT
        *
    FROM
      rfs_m_buzai
    WHERE
      shisetsu_kbn = $shisetsu_kbn
  ) b_mst
    ON b.buzai_cd = b_mst.buzai_cd
GROUP BY
    b.chk_mng_no
  , b.buzai_cd
  , b_mst.buzai_nm
  , b.check_buzai_judge
ORDER BY
  b.buzai_cd
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /***
   * 該当の点検において、一番新しい措置日を取得する
   *
   * 引数:$chk_mng_no chk_mng_no
   *
   ***/
  protected function getSoshiDt($chk_mng_no){
    $tenken_kasyo = $this->getChkTenkenKasyoData($chk_mng_no);
    $soshi_dt=$tenken_kasyo[0]['w_sochi_dt'];
    //$soshi_dt=$tenken_kasyo[0]['max_measures_dt'];
    // 和暦変換
    //$soshi_dt=$this->chgWareki($soshi_dt);
    return $soshi_dt;
  }

  /***
   * 部材データで引数のchk_mng_no内で最悪の健全性の部材レコードを取得する。
   * 同じ健全性がある場合も複数取得する。
   *
   * 引数:$shisetsu_kbn 施設区分
   *      $chk_mng_no chk_mng_no
   *      $check_shisetsu_judge 点検時健全性
   *
   ***/
  protected function getChkTenkenKasyoData($chk_mng_no){
    log_message('info', 'getChkTenkenKasyoData');
    $sql= <<<EOF
WITH sochi_tmp AS (
  SELECT
    MAX(measures_dt) max_measures_dt
FROM
  rfs_t_chk_tenken_kasyo
WHERE
  chk_mng_no = $chk_mng_no)
  SELECT
    max_measures_dt
    , wareki_to_char(max_measures_dt, 'ggLL年') w_sochi_dt
  FROM 
    sochi_tmp;
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /***
   * 引数の日付を和暦に変換する
   *
   * ※1989年1~3は昭和63年とし、
   *   1989年4月以降は平成元年とする。
   *
   * 引数:$dt 日付 YYYY-MM-DD
   *
   ***/
  protected function chgWareki($dt) {
    if (!$dt) {
      return "-";
    }
    $yyyy=date('Y', strtotime($dt));
    $mm=date('m', strtotime($dt));
    if ($yyyy==1989) {
      if ((int)$mm<4) {
        return "S63年";
      } else {
        return "H元年";
      }
    }else if ($yyyy>1989) {
      // 平成
      $kitei = (int)$mm < 4 ? 1989 : 1988;
      $gengou="H";
    }else if ($yyyy<1989) {
      // 昭和
      $kitei = (int)$mm < 4 ? 1926 : 1925;
      $gengou="S";
    }
    return $gengou.((int)$yyyy - (int)$kitei)."年";
  }

  /**
   * 電気通信施設点検結果取得
   * 
   * 引数のsnoの電気通信施設点検結果を全て取得する
   * 取得内容
   *   点検年（和暦）H12年など
   *   点検結果 未点検、点検中、異常あり、異常なし
   *   点検管理番号
   */
  public function getChkDenki($sno) {
    log_message('debug', __METHOD__);
    $sql= <<<SQL
WITH chk_main AS (SELECT * FROM ele_t_chk_main WHERE sno = $sno)
, chk_shisetsu_tmp AS ( 
  SELECT
        ele_t_chk_shisetsu.* 
    FROM
      chk_main JOIN ele_t_chk_shisetsu 
        ON chk_main.chk_mng_no = ele_t_chk_shisetsu.chk_mng_no
) 
, chk_shisetsu AS ( 
  SELECT
        tmp1.* 
    FROM
      chk_shisetsu_tmp tmp1 JOIN ( 
        SELECT
              chk_mng_no
            , max(rireki_no) rireki_no 
          FROM
            chk_shisetsu_tmp 
          GROUP BY
            chk_mng_no
      ) tmp2 
        ON tmp1.chk_mng_no = tmp2.chk_mng_no 
        AND tmp1.rireki_no = tmp2.rireki_no
) 
SELECT
      *
    , wareki_to_char(chk_dt, 'ggLL年') w_chk_dt
    , CASE 
      WHEN chk_shisetsu_result = 1 
        THEN '異常あり' 
      WHEN chk_shisetsu_result = 0 
        THEN '異常なし' 
      ELSE '' 
      END result_str 
  FROM
    chk_shisetsu
SQL;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /***
   * 定期パトロールを取得する
   *
   * 引数:$sno 施設番号
   *
   ***/
  public function getTeikiPatrol($sno){
    log_message('info', 'getTeikiPatrol');

    // 定期パトロール本体のデータを取得
    $sql= <<<EOF
SELECT 
  tls.tenken_list_cd,
  shi.sno,
  shi.shisetsu_kbn,
  tld.tenken_list_detail_cd,
  wf.wareki_ryaku || '年' wareki_ryaku,
  tld.ijyou_umu_flg,
  tls.tenken_list_name
FROM
  teiki_patrol.tenken_lists tls
INNER JOIN teiki_patrol.tenken_list_details  tld
  ON tls.tenken_list_cd = tld.tenken_list_cd
LEFT JOIN v_wareki_seireki_future wf
  ON EXTRACT(YEAR FROM tls.deliveried_at) = wf.seireki
LEFT JOIN rfs_m_shisetsu shi
  ON tld.sno = shi.sno
WHERE
  tld.sno = $sno
  AND wf.wareki_ryaku IS NOT NULL
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    
    for($i = 0; $i < count($result); $i++) {
      $tenken_list_detail_cd = $result[$i]['tenken_list_detail_cd'];
      $shisetsu_kbn = $result[$i]['shisetsu_kbn'];
      // 部材を取得して結果と結合
      $buzai_sql = <<<EOF
SELECT
  sbu.shisetsu_buzai_nm
FROM
  teiki_patrol.sonsyou_naiyou ssn
LEFT JOIN public.rfs_m_shisetsu_buzai sbu
  ON ssn.buzai_cd = sbu.shisetsu_buzai_kbn AND sbu.shisetsu_kbn = $shisetsu_kbn
WHERE
  ssn.tenken_list_detail_cd = $tenken_list_detail_cd
EOF;

      $buzai_query = $this->DB_rfs->query($buzai_sql);
      $buzai_result = $buzai_query->result('array');

      // 連想配列の配列からshisesu_buzai_nmの配列に直してカンマ区切りで結合
      $buzai_nm_array = array_map(function($buzai){ return $buzai['shisetsu_buzai_nm'];}, $buzai_result);
      $all_buzai = implode(',', $buzai_nm_array);

    log_message('info', "buzai=$all_buzai");

      $result[$i]['buzai'] = $all_buzai;
    }

    return $result;
  }

  /***
   * 法定点検を取得する
   *
   * 引数:$sno 施設番号
   *
   ***/
  public function getHouteiTenken($sno){
    log_message('info', 'getHouteiTenken');
    $sql= <<<EOF
-- Angular側の表示を容易にするため、rfs_t_chk_houtei(rtch)1レコード（1行）に対して、
-- 複数の添付ファイルの情報を保持するため、各添付ファイルの情報はJSON文字列として複数の情報を1レコードとして取得する
WITH attachfile AS (
  SELECT
    rtha.chk_mng_no
    , json_agg(rtha) json
  FROM
    rfs_t_houtei_attachfile rtha
  GROUP BY
    rtha.chk_mng_no
)
SELECT
  rtch.*
  ,vwsf.wareki_ryaku || '年' gengou
  ,af.json attach_files
FROM
  rfs_t_chk_houtei rtch
LEFT JOIN
  attachfile af
  ON rtch.chk_mng_no = af.chk_mng_no
LEFT JOIN
  v_wareki_seireki_future vwsf
  ON date_part('year', rtch.target_dt) = vwsf.seireki
WHERE
  rtch.sno = $sno
ORDER BY
  rtch.chk_times DESC
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    $result = array_map(function($row) {
      // attach_filesはJSON文字列なので連想配列に戻す
      $row['attach_files'] = is_null($row['attach_files']) ? [] : json_decode($row['attach_files']);
      return $row;
    }, $result);
    return $result;
  }

}
