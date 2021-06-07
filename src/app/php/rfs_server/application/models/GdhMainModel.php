<?php

class GdhMainModel extends CI_Model {

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

  /***
   * 標識整備進捗情報取得
   * 引数の条件での検索結果を返却する。
   *
   * 引数:$sno, $gdh_idx
   * 戻り値:標識整備進捗情報
   ***/
  public function getTResponseStatus($sno, $gdh_idx) {
    log_message("debug", "getTResponseStatus");

    $sql= <<<EOF
/* 履歴番号最大を取得  */
WITH tResponseStatusMax AS (
  SELECT
    tmp1.sno
    , tmp1.gdh_idx
    , tmp1.rireki_no
    , tmp1.taisaku_kbn_cd
    , tmp1.yotei_nendo_yyyy
    , CASE
      WHEN tmp1.taisaku_status_cd IS NULL
        THEN 3
      ELSE tmp1.taisaku_status_cd
      END AS taisaku_status_cd
    , tmp1.taisaku_kouhou_cd
    , tmp1.dououdou
  FROM
    gdh_t_response_status tmp1 JOIN (
      SELECT
        sno
        , MAX(rireki_no) rireki_no
      FROM
        gdh_t_response_status
      WHERE
        gdh_idx > 0
        AND gdh_idx = $gdh_idx
      GROUP BY
        sno
    ) tmp2
      ON tmp1.sno = tmp2.sno
      AND tmp1.rireki_no = tmp2.rireki_no
  WHERE
    tmp1.sno = $sno
    AND tmp1.gdh_idx > 0
    AND tmp1.gdh_idx = $gdh_idx
  ORDER BY
    sno
    , rireki_no
    , taisaku_kbn_cd
)
SELECT
  mTaisakuKbn.taisaku_kbn_cd
  , mTaisakuKbn.taisaku_kbn
  , tResponseStatusMax.sno
  , tResponseStatusMax.gdh_idx
  , tResponseStatusMax.rireki_no
  , tResponseStatusMax.yotei_nendo_yyyy
  , tResponseStatusMax.taisaku_status_cd
  , tResponseStatusMax.taisaku_kouhou_cd
  , tResponseStatusMax.dououdou
FROM
  gdh_m_taisaku_kbn mTaisakuKbn
  LEFT JOIN tResponseStatusMax tResponseStatusMax
    ON mTaisakuKbn.taisaku_kbn_cd = tResponseStatusMax.taisaku_kbn_cd
ORDER BY
  tResponseStatusMax.sno
  , tResponseStatusMax.gdh_idx
  , tResponseStatusMax.rireki_no
  , mTaisakuKbn.taisaku_kbn_cd
EOF;

    //log_message('debug', $sql);

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /***
   * 案内標識基本情報取得
   * 引数の条件での検索結果を返却する。
   *
   * 引数:$sno
   * 戻り値:案内標識基本情報
   ***/
  public function getTShisetsuSub($sno) {
    log_message("info", "getTShisetsuSub");

    $sql= <<<EOF
SELECT
 *
FROM
  gdh_t_shisetsu_sub
WHERE
  sno = $sno
EOF;

    //log_message('debug', $sql);

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /***
   * 対応状況 案内標識板
   * 引数の条件での検索結果を返却する。
   *
   * 引数:
   *      $sno
   *      $gdhIdx
   * 戻り値:対応状況 案内標識板
   ***/
  public function getTBrd($sno, $gdhIdx) {
    log_message("debug", "getTBrd");

    $sql= <<<EOF
SELECT
 *
FROM
  gdh_t_brd
WHERE
  sno = $sno
  AND gdh_idx = $gdhIdx
EOF;

    //log_message('debug', $sql);

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /***
   * 案内標識写真情報取得
   *
   * 引数:
   *      $sno
   *      $gdhIdx
   *
   * 戻り値:案内標識写真情報
   ***/
  public function getTPicture($sno, $gdhIdx) {
    log_message("debug", "getTPicture");

    $sql = <<<EOF
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
      , MAX(picture_cd) picture_cd
    FROM
      gdh_t_picture
    WHERE
      sno = $sno
      AND gdh_idx = $gdhIdx
      AND use_flg = 1
    GROUP BY
      sno
  ) tmp2
    ON tmp1.sno = tmp2.sno
    AND tmp1.picture_cd = tmp2.picture_cd
WHERE
  tmp1.sno = $sno
  AND tmp1.gdh_idx = $gdhIdx
  AND tmp1.use_flg = 1
ORDER BY
  sno
EOF;

    //log_message('debug', $sql);

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /***
   * 保存処理
   *
   * 引数: $param
   *
   * 戻り値: なし
   ***/
  public function saveMain($param){
    log_message('debug', 'saveMain');

    $sno=$param['sno'];
    $gdh_idx=$param['gdh_idx'];

    // 新規登録（追加）の場合
    if ($gdh_idx < 0) {
      // 最大+1のgdhIdx取得
      $gdh_idx = $this->getNextGdhIdx($sno);
    }

    $gdh_syubetsu_cd=$param['gdh_syubetsu_cd'];
    $kousa_kbn_cd=$param['kousa_kbn_cd'];

    $brd_color_cd=$param['brd_color_cd'];
    $response_status=$param['response_status'];
    $dououdou=$param['dououdou'];
    $pic_data=$param['pic_data'];

    $this->DB_rfs->trans_begin();

    // 案内標識基本情報 登録
    $this->insertTShisetsuSub($sno, $gdh_syubetsu_cd, $kousa_kbn_cd);

    // 案内標識板色 登録
    $this->insertTBrd($sno, $gdh_idx, $brd_color_cd);

    // 履歴番号取得
    $rireki_no = $this->getNextRirekiNo($sno, $gdh_idx);

    // 1枚目のgdh_idxを取得
    $min_gdh_idx = $this->getMinGdhIdx($sno);

    // 対応状況 登録
    for ($i=0;$i<count($response_status);$i++) {

      // データ登録
      $this->insertTResponseStatus($sno, $gdh_idx, $response_status[$i], $dououdou, $rireki_no);

      // 1枚目の予定年度を2枚目以降にも設定
      if ($gdh_idx == $min_gdh_idx) {
          $this->setNendo($sno, $gdh_idx, $response_status[$i]);
      }
    }

    // 対応状況 集約データ（gdh_idx=0） 更新
    $this->insertTResponseStatusIndex($sno);

    if ($pic_data) {
      // 画像データ削除
      $this->updateTPicture($sno, $gdh_idx);
      // 画像登録
      $this->insertTPicture($sno, $gdh_idx, $pic_data);
    } else {
      // 画像データ削除
      $this->updateTPicture($sno, $gdh_idx);
    }

    // トランザクション処理
    if ($this->DB_rfs->trans_status() === FALSE) {
      $this->DB_rfs->trans_rollback();
    } else {
      $this->DB_rfs->trans_commit();
    }

    return $gdh_idx;
  }

  /***
   * 案内標識基本情報 登録
   *
   * 引数:
   *      $sno
   *      $gdh_syubetsu_cd
   *      $kousa_kbn_cd
   * 戻り値:なし
   ***/
  private function insertTShisetsuSub($sno, $gdh_syubetsu_cd, $kousa_kbn_cd) {
    log_message("debug", "insertTShisetsuSub");
    $gdh_syubetsu_cd=$this->convertNullStr($gdh_syubetsu_cd);
    $kousa_kbn_cd=$this->convertNullStr($kousa_kbn_cd);

    // 削除処理
    $del_sql = "DELETE FROM gdh_t_shisetsu_sub WHERE sno = $sno";
    $query = $this->DB_rfs->query($del_sql);

    $ins_sql = <<<EOF
INSERT INTO gdh_t_shisetsu_sub (
  sno,
  gdh_syubetsu_cd,
  kousa_kbn_cd
) VALUES (
  $sno,
  $gdh_syubetsu_cd,
  $kousa_kbn_cd
)
EOF;

    //log_message('DEBUG', $ins_sql);
    $query = $this->DB_rfs->query($ins_sql);
  }

  /***
   * 案内標識板色 登録
   *
   * 引数:
   *      $sno
   *      $gdh_idx
   *      $brd_color_cd 標識板色
   * 戻り値:なし
   ***/
  private function insertTBrd($sno, $gdh_idx, $brd_color_cd) {
    log_message("debug", "insertTBrd");
    $brd_color_cd = $this->convertNullStr($brd_color_cd);
    // 削除処理
    $del_sql = "DELETE FROM gdh_t_brd WHERE sno = $sno AND gdh_idx = $gdh_idx";
    $query = $this->DB_rfs->query($del_sql);

    //log_message('DEBUG', $del_sql);

    $ins_sql = <<<EOF
INSERT INTO gdh_t_brd (
  sno,
  gdh_idx,
  brd_color_cd
) VALUES (
  $sno,
  $gdh_idx,
  $brd_color_cd
)
EOF;

    //log_message('DEBUG', $ins_sql);
    $query = $this->DB_rfs->query($ins_sql);
  }

  /***
    * 案内標識追加用に最大＋1のgdhIdxを取得する
    *
    * 引数:$sno
    * 戻り値：最大のgdhIdx+1
    ***/
  public function getNextGdhIdx($sno) {
    log_message("debug", "getNextGdhIdx");

    $sql = <<<EOF
SELECT
  COALESCE(MAX(tmp1.gdh_idx), 0) +1 AS gdhIdx
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
    AND tmp1.rireki_no = tmp2.rireki_no
    AND tmp1.gdh_idx = tmp2.gdh_idx
WHERE
  tmp1.sno = $sno
  AND tmp1.gdh_idx > 0
EOF;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

    if (isset($result[0]['gdhidx'])) {
      return $result[0]['gdhidx'];
    }
    return 1;
  }

  /***
    * 履歴番号取得
    *
    * 引数:
    *      $sno
    *      $gdh_idx
    * 戻り値:最大履歴番号
    ***/
  private function getNextRirekiNo($sno, $gdh_idx) {
    log_message("debug", "getNextRirekiNo");

    $sql = <<<EOF
SELECT COALESCE(MAX(rireki_no), 0) +1 AS rireki_no
FROM
  gdh_t_response_status
WHERE
  sno = $sno
  AND gdh_idx = $gdh_idx
EOF;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

    return $result[0]['rireki_no'];
  }

  /***
    * 対応状況 登録
    *
    * 引数:
    *      $sno sno
    *      $gdh_idx gdh_idx
    *      $response_status 対応状況入力情報
    *      $dououdou 道央道入力情報
    *      $rireki_no 履歴番号
    * 戻り値:なし
    ***/
  private function insertTResponseStatus($sno, $gdh_idx, $response_status, $dououdou, $rireki_no) {
    log_message("debug", "insertTResponseStatus");

    $taisaku_kbn_cd = $this->convertNullStr($response_status['taisaku_kbn_cd']);
    $yotei_nendo_yyyy = $this->convertNullStr($response_status['yotei_nendo_yyyy']);
    $taisaku_status_cd = $this->convertNullStr($response_status['taisaku_status_cd']);
    $taisaku_kouhou_cd = $this->convertNullStr($response_status['taisaku_kouhou_cd']);
    $dououdou = $this->convertNullStr($dououdou);

    $sql = <<<EOF
INSERT INTO gdh_t_response_status (
  sno
  , gdh_idx
  , rireki_no
  , taisaku_kbn_cd
  , yotei_nendo_yyyy
  , taisaku_status_cd
  , taisaku_kouhou_cd
  , dououdou
) VALUES (
  $sno
  , $gdh_idx
  , $rireki_no
  , $taisaku_kbn_cd
  , $yotei_nendo_yyyy
  , $taisaku_status_cd
  , $taisaku_kouhou_cd
  , $dououdou
)
EOF;

    //log_message('DEBUG', $sql);

    $query = $this->DB_rfs->query($sql);
  }

  /***
    * 対応状況Index 登録
    *
    * 引数:$sno sno
    * 戻り値:なし
    ***/
  private function insertTResponseStatusIndex($sno) {
    log_message("debug", "insertTResponseStatusIndex");

    $sql = <<<EOF
/* 履歴番号最大を取得  */
WITH tResponseStatusMax AS (
  SELECT
    tmp1.sno
    , tmp1.gdh_idx
    , tmp1.rireki_no
    , tmp1.taisaku_kbn_cd
    , tmp1.yotei_nendo_yyyy
    , CASE
      WHEN tmp1.taisaku_status_cd IS NULL
        THEN 3
      ELSE tmp1.taisaku_status_cd
      END AS taisaku_status_cd
    , tmp1.taisaku_kouhou_cd
    , CASE
      WHEN tmp1.dououdou IS NULL
        THEN 0
      ELSE tmp1.dououdou
      END AS dououdou
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
      AND tmp1.rireki_no = tmp2.rireki_no
      AND tmp1.gdh_idx = tmp2.gdh_idx
  WHERE
    tmp1.sno = $sno
    AND tmp1.gdh_idx > 0
)
, youTaisakuCount AS (
  SELECT
    sno
    , taisaku_kbn_cd
    , count(*) AS count
  FROM
    tResponseStatusMax
  WHERE
    taisaku_status_cd = 2
  GROUP BY
    sno
    , taisaku_kbn_cd
  ORDER BY
    sno
    , taisaku_kbn_cd
)
, shukeiTmp AS (
  SELECT
    sno
    , taisaku_kbn_cd
    , MIN(yotei_nendo_yyyy) AS yotei_nendo_yyyy
    , CASE
      WHEN MAX(taisaku_status_cd) = 3
        THEN 3
      WHEN MAX(taisaku_status_cd) = 2
        THEN 2
      WHEN MIN(taisaku_status_cd) = 1
        THEN 1
      ELSE 4
      END AS taisaku_status_cd
    , MAX(taisaku_kouhou_cd) AS taisaku_kouhou_cd
    , CASE
      WHEN SUM(dououdou) > 0
        THEN 1
      ELSE 0
      END AS dououdou
  FROM
    tResponseStatusMax
  GROUP BY
    sno
    , taisaku_kbn_cd
  ORDER BY
    sno
    , taisaku_kbn_cd
)
SELECT
  shukeiTmp.sno
  , shukeiTmp.taisaku_kbn_cd
  , shukeiTmp.yotei_nendo_yyyy
  , CASE
    WHEN youTaisakuCount.count > 0
      THEN 2
    ELSE shukeiTmp.taisaku_status_cd
    END AS taisaku_status_cd
  , shukeiTmp.taisaku_kouhou_cd
  , shukeiTmp.dououdou
  , youTaisakuCount.count
FROM
  shukeiTmp shukeiTmp
  LEFT JOIN youTaisakuCount youTaisakuCount
    ON shukeiTmp.sno = youTaisakuCount.sno
    AND shukeiTmp.taisaku_kbn_cd = youTaisakuCount.taisaku_kbn_cd
EOF;

    //log_message('DEBUG', $sql);

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

    // 集約データ（gdh_idx=0）の最大履歴番号取得
    $rireki_no = $this->getNextRirekiNo($sno, 0);

    for ($i=0;$i<count($result);$i++) {
      $response_status['yotei_nendo_yyyy']=$result[$i]['yotei_nendo_yyyy'];
      $response_status['taisaku_kbn_cd']=$result[$i]['taisaku_kbn_cd'];
      $response_status['taisaku_status_cd']=$result[$i]['taisaku_status_cd'];
      $response_status['taisaku_kouhou_cd']=$result[$i]['taisaku_kouhou_cd'];
      $dououdou=$result[$i]['dououdou'];

      // INSERT処理
      $this->insertTResponseStatus($sno, 0, $response_status, $dououdou, $rireki_no);
    }
  }

  /***
    * 案内標識写真テーブル 登録
    *
    * 引数:
    *      $sno
    *      $gdh_idx
    *      $pic_data 写真データ
    *
    ***/
  private function insertTPicture($sno, $gdh_idx, $pic_data) {
    log_message("debug", "insertTPicture");

    for($i=0;$i<count($pic_data);$i++){

      if(preg_match('/upload\/temp\/.*/', $pic_data[$i]['path'], $m)){

        // 一時パス
        $tmpPath = $pic_data[$i]['path'];
        // ADD hirano 出張所コードがほしい -->
        $syucchoujo_cd=$this->getSyucchoujoCd($sno);
        // <--
        // 添付ファイルのコピー
        $ext = pathinfo($pic_data[$i]['path'], PATHINFO_EXTENSION);
        $filename = basename($pic_data[$i]['path']);
        // UPD パス二出張所を追加 -->
        $path = "{$this->config->config['attach_path']}$syucchoujo_cd/$sno/";
        //$path = "{$this->config->config['attach_path']}$sno/";
        // <--
        $server_path = $this->config->config['www_path'].$path;
        //「$directory_path」で指定されたディレクトリが存在するか確認
        if(!is_dir($server_path)){
          //存在しないときの処理（「$directory_path」で指定されたディレクトリを作成する）
          mkdir($server_path, 0755,true);
        }
        $src_file = $this->config->config['www_path'].$pic_data[$i]['path'];
        $dist_file = $server_path.$pic_data[$i]['file_nm'];
        copy( $src_file, $dist_file);

        // パスを元ファイル名で書き換え
        $pic_data[$i]['path'] = $path.$pic_data[$i]['file_nm'];

        // T_UPLOADテーブルのレコード削除
        $this->deleteTUpload($tmpPath);

        // ファイル削除
        unlink ($src_file);
      }

      $pic_path = $pic_data[$i]['path'];
      if ($pic_path == 'dummy') {
        $pic_path = "null";
      } else {
        $pic_path = pg_escape_literal($pic_data[$i]['path']);
      }
      $lat = isset($pic_data[$i]['lat'])?$this->convertNullStr($pic_data[$i]['lat']):0;
      $lon = isset($pic_data[$i]['lon'])?$this->convertNullStr($pic_data[$i]['lon']):0;
      $exif_dt = isset($pic_data[$i]['exif_dt'])?pg_escape_literal($pic_data[$i]['exif_dt']):"null";
      $shooting_dt = isset($pic_data[$i]['shooting_dt'])?pg_escape_literal($pic_data[$i]['shooting_dt']):"null";
      $description = isset($pic_data[$i]['description'])?pg_escape_literal($pic_data[$i]['description']):"null";

      $sql= <<<SQL
WITH maxPictureCd AS (
  SELECT
    COALESCE(
      (
        SELECT
          max(picture_cd)
        FROM
          gdh_t_picture
        WHERE
          sno = $sno
          AND gdh_idx = $gdh_idx
        GROUP BY
          sno
          , gdh_idx
      )
      , 0
    ) + 1 AS max_pic_cd
)
INSERT INTO gdh_t_picture(
  sno
  , gdh_idx
  , picture_cd
  , "path"
  , update_dt
  , lat
  , lon
  , use_flg
  , exif_dt
  , shooting_dt
  , description
) VALUES (
  $sno
  , $gdh_idx
  , (SELECT max_pic_cd FROM maxPictureCd)
  , $pic_path
  , null
  , $lat
  , $lon
  , 1
  , $exif_dt
  , $shooting_dt
  , $description
)
SQL;
      //log_message('debug', "sql=$sql");

      $query = $this->DB_rfs->query($sql);

    }
    return ;
  }

  /***
   * 削除処理
   *
   * 引数: $param
   *
   * 戻り値: なし
   ***/
  public function delMain($param){
    log_message('debug', 'delMain');

    $this->DB_rfs->trans_begin();

    $sno=$param['sno'];
    $gdh_idx=$param['gdh_idx'];

    // 対応状況削除
    $this->deleteTResponseStatus($sno, $gdh_idx);

    // 画像データ削除
    $this->updateTPicture($sno, $gdh_idx);

    // 集約データ（gdh_idx=0）更新
    // 削除対象以外の案内標識が1つ以上存在する場合
    if ($this->countGdhIdx($sno) > 0) {
      // 集約データ（gdh_idx=0） 更新
      $this->insertTResponseStatusIndex($sno);
    // 削除対象以外に案内標識が存在しない場合
    } else {
      // 集約データ（gdh_idx=0） 削除
      $this->deleteTResponseStatus($sno, 0);
    }

    // トランザクション処理
    if ($this->DB_rfs->trans_status() === FALSE) {
      $this->DB_rfs->trans_rollback();
    } else {
      $this->DB_rfs->trans_commit();
    }

    return $gdh_idx;
  }

  /***
    * 対応状況テーブル 削除
    *
    * 引数:
    *      $sno
    *      $gdh_idx
    *
    ***/
  private function deleteTResponseStatus($sno, $gdh_idx) {
    log_message("debug", "deleteTResponseStatus");

    $sql= <<<SQL
DELETE FROM gdh_t_response_status
WHERE sno = $sno
  AND gdh_idx = $gdh_idx
SQL;
    //log_message('debug', "sql=$sql");

    $query = $this->DB_rfs->query($sql);
  }

  /***
    * 案内標識板数 取得
    *
    * 引数:$sno
    *
    * 戻り値：案内標識板数
    ***/

  public function countGdhIdx($sno) {
    log_message("debug", "countGdhIdx");

    $sql = <<<SQL
SELECT count(*) cnt
FROM (
  SELECT
    DISTINCT gdh_idx
  FROM
    gdh_t_response_status
  WHERE
    sno = $sno
    AND gdh_idx > 0
) idx
SQL;

    //log_message('debug', "sql=$sql");

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

    return $result[0]['cnt'];
  }

  /***
    * 案内標識写真テーブル 更新
    *
    * 引数:
    *      $sno
    *      $gdh_idx
    *
    ***/
  private function updateTPicture($sno, $gdh_idx) {
    log_message("debug", "updateTPicture");

      $sql= <<<SQL
UPDATE gdh_t_picture
SET use_flg = 0
WHERE sno = $sno
  AND gdh_idx = $gdh_idx
SQL;
      //log_message('debug', "sql=$sql");

      $query = $this->DB_rfs->query($sql);

    return ;
  }

  /***
    * 案内標識板一枚目の gdh_idx を取得
    *
    * 引数:$sno
    *
    * 戻り値：一枚目のgdh_idx
    ***/
  public function getMinGdhIdx($sno) {
    log_message("debug", "getMinGdhIdx");

    $sql = <<<SQL
SELECT
  min(gdh_idx) as min_gdh_idx
FROM
  gdh_t_response_status
WHERE
  sno = $sno
  AND gdh_idx > 0
GROUP BY
  sno
SQL;

    //log_message('debug', "sql=$sql");

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

    if (isset($result[0])) {
      return $result[0]['min_gdh_idx'];
    }
    return 0;
  }

  /**
   * 年度を二枚目以降の標識に設定
   *
   * 引数：
   *      $sno
   *      $min_gdh_idx
   *      $response_status
   *
   * 戻り値：なし
   *
   */
  private function setNendo($sno, $min_gdh_idx, $response_status) {
    log_message("debug", "setNendo");

    $taisaku_kbn_cd = $this->convertNullStr($response_status['taisaku_kbn_cd']);
    $yotei_nendo_yyyy = $this->convertNullStr($response_status['yotei_nendo_yyyy']);

    // 対策工法
    //$taisaku_kouhou_cd = $this->convertNullStr($response_status['taisaku_kouhou_cd']);

    if ($taisaku_kbn_cd == "null") {
      return;
    }

    $sql = <<<SQL
UPDATE gdh_t_response_status res
SET
  yotei_nendo_yyyy = $yotei_nendo_yyyy
FROM
  (
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
        AND tmp1.rireki_no = tmp2.rireki_no
        AND tmp1.gdh_idx = tmp2.gdh_idx
    WHERE
      tmp1.sno = $sno
      AND tmp1.gdh_idx > 0
  ) tmp
WHERE
  res.sno = tmp.sno
  AND res.gdh_idx > $min_gdh_idx
  AND res.gdh_idx = tmp.gdh_idx
  AND res.rireki_no = tmp.rireki_no
  AND res.taisaku_kbn_cd = $taisaku_kbn_cd
SQL;

    //log_message('debug', "sql=$sql");

    $query = $this->DB_rfs->query($sql);
  }

  /***
    * アップロード画像パス 削除
    *
    * 引数: $tmpFilePath
    *
    ***/
  private function deleteTUpload($tmpFilePath) {
    log_message("debug", "deleteTUpload");

    $tmpFilePath = pg_escape_literal($tmpFilePath);

    $sql= <<<SQL
DELETE FROM t_upload WHERE path = $tmpFilePath
SQL;
    //log_message('debug', "sql=$sql");

    $query = $this->DB_rfs->query($sql);
  }

  private function convertNullStr($param) {
    if (isset($param)) {
      if (strlen($param) == 0) {
        return "null";
      }
      return $param;
    }
    return "null";
  }

  // ADD hirano snoから出張所コードを取得
  protected function getSyucchoujoCd($sno) {

    $sql= <<<SQL
SELECT
    syucchoujo_cd
FROM
  rfs_m_shisetsu
WHERE
  sno = $sno
ORDER BY
  shisetsu_ver desc
LIMIT
  1
SQL;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result[0]['syucchoujo_cd'];
  }

  /**
   * 案内標識用予定年度の取得
   * 
   * ±7年を取得する
   * 
   */
  public function getYoteiNendo() {
    log_message('debug', __METHOD__);
    $now_yyyy=date("Y");

    $sql= <<<EOF
    SELECT
      seireki as year
    , wareki_ryaku gengou
FROM
  v_wareki_seireki_future 
WHERE
  seireki >= {$now_yyyy} - 7 
  AND seireki <= {$now_yyyy} + 7
ORDER BY
  seireki DESC
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

}
