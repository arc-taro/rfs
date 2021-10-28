<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * マスタの検索
 *  SchMstが既に存在するが、附属物の検索に特化しすぎているため
 *  汎用化したい。附属物はリリースしているので、そのままにしておく
 *
 * 返却は配列
 *
 * @access public
 * @package Model
 */
class SchCommon extends CI_Model {

  protected $DB_rfs;
  protected $DB_imm;

  /**
   * コンストラクタ
   *
   * model SchCheckを初期化する。
   */
  public function __construct() {
    parent::__construct();
    $this->DB_rfs = $this->load->database('rfs',TRUE);
    if ($this->DB_rfs->conn_id === FALSE) {
      log_message('debug', '道路附帯施設管理システムデータベースに接続されていません');
      return;
    }
    $this->DB_imm = $this->load->database('imm',TRUE);
    if ($this->DB_imm->conn_id === FALSE) {
      log_message('debug', '維持管理システムデータベースに接続されていません');
      return;
    }
  }

  /*****************************/
  /********    マスタ    ********/
  /*****************************/
  /**
     * 建管・出張所取得
     *
     *   後期開発で、建管出張所データを取得するために
     *   public関数が必要なため作成
     *
     * @param   $get getクエリ <br>
     *          $get['syozoku_cd']        所属コード
     *          $get['dogen_cd']          建管コード
     * @return array
     *         array['dogen_row'] : <br>
     */
  public function getDogenSyucchoujo($get) {

    log_message('debug', 'get_dogen_syucchoujo');

    // 所属コードが1、10001の場合全建管が対象
    // 所奥族コード2以上の場合は該当建管は１つ
    $sql="";
    $sql.="select ";
    $sql.="jsonb_set('{}', '{dogen_info}', jsonb_agg(to_jsonb(all_info) order by dogen_cd)) AS dogen_row ";
    $sql.="from ";
    $sql.="(select ";
    $sql.="d.*";
    $sql.=", syucchoujo_row ";
    $sql.="from ";
    $sql.="(select ";
    $sql.="* ";
    $sql.="from ";
    $sql.="rfs_m_dogen ";
    // 所属コード1かつ10001以外の場合は建管を絞る
    if ($get['syozoku_cd'] != 1 && $get['syozoku_cd'] != 10001) {
      $sql.="where dogen_cd=".$get['dogen_cd'];
    }
    $sql.=") d ";
    $sql.="join ";
    $sql.="(select ";
    $sql.="s.dogen_cd ";
    $sql.=", jsonb_set('{}', '{syucchoujo_info}', jsonb_agg(to_jsonb(s)-'dogen_cd' order by syucchoujo_cd)) AS syucchoujo_row ";
    $sql.="from ";
    $sql.="(select syucchoujo_cd, syucchoujo_mei, dogen_cd, lt_lon, lt_lat, rb_lon, rb_lat from rfs_m_syucchoujo) as s ";
    $sql.="group by s.dogen_cd) as s_row ";
    $sql.="on ";
    $sql.="d.dogen_cd=s_row.dogen_cd) all_info ";

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

    //$r = print_r($result, true);
    log_message('debug', "sql=$sql");
    //log_message('debug', "result=$r");

    return $result;

  }

  /**
   * 建管内の路線の本数を取得
   *  建管内では出張所またぎの路線が複数出てしまうので、
   *  GROUPした結果の本数を取得する
   *
   * @param   $dogen_cd       建管コード
   * @return array
   */
  public function getDogenRosenCnt($dogen_cd) {
    log_message('debug', 'getDogenRosenCnt');

    $sql= <<<EOF
SELECT
    count(*) cnt
FROM
  (
    SELECT
        vr.rosen_cd
    FROM
      v_rosen vr
      LEFT JOIN rfs_m_syucchoujo s
        ON vr.syucchoujo_cd = s.syucchoujo_cd
    WHERE
      dogen_cd=$dogen_cd
    GROUP BY
      vr.rosen_cd
  ) main
EOF;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
/*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result[0]['cnt'];
  }

  /***
   *
   * 施設区分マスタ内容取得
   *  施設区分をsort_no順に取得する
   *
   * @return array
   *
   */
  public function getShisetsuKbns() {
    log_message('debug', 'getShisetsuKbns');

    $sql= <<<EOF
SELECT
  shisetsu_kbn
  , shisetsu_kbn_nm
FROM
  rfs_m_shisetsu_kbn
ORDER BY
  sort_no
EOF;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    /*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result;
  }

  /***
   *
   * 施設区分マスタ内容取得(附属物用)
   *  施設区分をsort_no順に取得する
   *
   * @return array
   *
   */
  public function getShisetsuKbnsHuzokubutsu() {
    log_message('debug', 'getShisetsuKbnsHuzokubutsu');

    $sql= <<<EOF
SELECT
  shisetsu_kbn
  , shisetsu_kbn_nm
FROM
  rfs_m_shisetsu_kbn
WHERE
  shisetsu_kbn <= 5
ORDER BY
  sort_no
EOF;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    /*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result;
  }

  /***
   *
   * 路線取得
   *
   *  出張所内の路線を取得する。
   *  (建管内がほしい場合は、ネストしなければならないので
   *  JSON形式の取得関数を作成してください)
   *
   * @param $syucchoujo_cd 出張所コード
   *
   * @return array
   *
   */
  public function getRosens($syucchoujo_cd) {
    log_message('debug', 'getRosens');

    $sql= <<<EOF
    select
      distinct(rosen_cd) rosen_cd
      , rosen_cd || ' ： ' || rosen_nm as rosen_nm
      , cntx
      , cnty
      , ext1x
      , ext1y
      , ext2x
      , ext2y
    from
      v_rosen1_site
    where
      syucchoujo_cd = $syucchoujo_cd
    ORDER BY
      rosen_cd
EOF;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    /*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result;
  }

  /***
   *
   * 施設形式マスタ内容取得
   *  施設形式を取得する
   *
   * @return array
   *
   */
  public function getShisetsuKeishikis($shisetsu_kbn) {
    log_message('debug', 'getShisetsuKeishikis');

    $sql= <<<EOF
SELECT
  shisetsu_keishiki_cd
  , shisetsu_keishiki_nm
FROM
  rfs_m_shisetsu_keishiki
WHERE
  shisetsu_kbn = $shisetsu_kbn
ORDER BY
  shisetsu_keishiki_cd;
EOF;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    /*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result;
  }

  /***
   *
   * 形式区分マスタの取得
   *
   *  該当の施設区分の形式区分マスタを取得する
   *
   * @param $shisetsu_kbn 施設区分
   *
   * @return array
   *
   */
  public function getKeishikiKubuns($shisetsu_kbn) {
    log_message('debug', 'getKeishikiKubuns');

    $sql= <<<EOF
SELECT
    jsonb_agg(to_jsonb(main)) kk_info
FROM
  (
    SELECT
        syubetsu
      , syubetsu_title
      , jsonb_agg(
        to_jsonb(kk_val) - 'syubetsu' - 'syubetsu_title'
      ) kk_row
    FROM
      (
        SELECT
            syubetsu
          , keishiki_kubun_cd
          , syubetsu_title
          , keishiki_kubun
        FROM
          rfs_m_keishiki_kubun
        WHERE
          shisetsu_kbn = $shisetsu_kbn
        ORDER BY
          syubetsu
          , sort_no
      ) kk_val
    GROUP BY
      syubetsu
      , syubetsu_title
  ) main
EOF;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    /*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result;
  }

  /***
   *
   * 形式区分マスタの該当レコード取得
   *
   *  施設区分の形式区分マスタから引数の該当レコードを取得する
   *
   * @param $shisetsu_kbn 施設区分、$syubetsu 種別、$keishiki_kubun_cd 形式区分コード
   *
   * @return array
   *
   */
  public function getKeishikiKubunsRec($shisetsu_kbn,$syubetsu,$keishiki_kubun_cd) {
    log_message('debug', 'getKeishikiKubunsRec');

    $sql= <<<EOF
SELECT
    syubetsu
  , keishiki_kubun_cd
  , syubetsu_title
  , keishiki_kubun
FROM
  rfs_m_keishiki_kubun
WHERE
  shisetsu_kbn = $shisetsu_kbn
  AND syubetsu = $syubetsu
  AND keishiki_kubun_cd = $keishiki_kubun_cd
EOF;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    /*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result;
  }

  /**
     * 建管・出張所情報取得
     *
     * @param   $syucchoujo_cd 出張所コード
     * @return array
     */
  public function getKanrisyaInfo($syucchoujo_cd) {

    log_message('debug', 'getKanrisyaInfo');

    // 出張所コードは必須
    if ($syucchoujo_cd=="" || $syucchoujo_cd==0) {
      return null;
    }

    $sql= <<<EOF
SELECT
    d.dogen_cd
  , d.dogen_mei
  , s.syucchoujo_cd
  , s.syucchoujo_mei
  , s.lt_lat
  , s.rb_lat
  , s.lt_lon
  , s.rb_lon
FROM
  rfs_m_dogen d join rfs_m_syucchoujo s
    on d.dogen_cd = s.dogen_cd
WHERE
  s.syucchoujo_cd = $syucchoujo_cd
EOF;


    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

/*
    $r = print_r($result, true);
    log_message('debug', "sql=$sql");
    log_message('debug', "result=$r");
*/
    return $result;
  }


  /**
   * 施設区分マスタ取得
   *
   * @return array
   */
  public function getShisetsuKbnMst() {
    log_message('debug', 'getShisetsuKbnMst');
    $sql= <<<EOF
SELECT
    shisetsu_kbn cd
  , shisetsu_kbn_nm nm
FROM
  rfs_m_shisetsu_kbn
ORDER BY
  sort_no
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
/*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result;
  }

  /**
   * フェーズマスタ取得
   *
   * @return array
   */
  public function getPhaseMst() {
    log_message('debug', 'getPhaseMst');
    $sql= <<<EOF
SELECT
    id
  , phase cd
  , phase_str nm
FROM
  rfs_m_phase
WHERE
  phase <> 4
  AND
  phase <> 6
ORDER BY
  id
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    /*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result;
  }

  /**
   * 集計用フェーズマスタ取得
   *
   * @return array
   */
  public function getPhaseMstSum() {
    log_message('debug', 'getPhaseMstSum');
    $sql= <<<EOF
SELECT
    phase id
  , phase cd
  , phase_str nm
FROM
  rfs_m_phase_sum
WHERE
  phase != 4
  AND
  phase != 999
ORDER BY
  cd
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    /*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result;
  }

  /**
   * 健全性マスタ取得
   *
   * @return array
   */
  public function getJudgeMst() {
    log_message('debug', 'getJudgeMst');
    $sql= <<<EOF
SELECT
    shisetsu_judge cd
  , shisetsu_judge_nm nm
FROM
  rfs_m_shisetsu_judge
ORDER BY
  shisetsu_judge
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    /*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result;
  }

  /**
   * 施設区分取得
   *  選択プルダウン用
   *
   * 引数 kbn:1 全て
   *         2 附属物点検
   *
   * @return array
   */
  public function getShisetsuKbnFormulti($kbn) {
    log_message('debug', 'getShisetsuKbnFormulti');

    // 取得条件
    $where_shisetu_kbn="";
    if ($kbn == 1) {
    }else if($kbn == 2) {
      $where_shisetu_kbn=" AND shisetsu_kbn <= 5";
    }

    $sql= <<<EOF
SELECT
    shisetsu_kbn    as id
  , shisetsu_kbn_nm as label
  , shisetsu_kbn
  , sort_no
FROM
  rfs_m_shisetsu_kbn
WHERE
  TRUE
  $where_shisetu_kbn
ORDER BY
  sort_no
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    /*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result;
  }

  /**
   * 判定区分取得
   *
   * @return array
   */
  public function getShisetsuJudgeFormulti() {
    log_message('debug', 'getShisetsuJudgeFormulti');
    $sql= <<<EOF
SELECT
    shisetsu_judge as id
  , shisetsu_judge_nm as label
  , shisetsu_judge
FROM
  rfs_m_shisetsu_judge
ORDER BY
  id
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    //$r = print_r($result, true);
    //        log_message('debug', "sql=$sql");
    //log_message('debug', "result=$r");
    return $result;
  }

  /**
     * 路線取得
     *  取得は、建管単位で行い、情報として出張所コードを持つ。
     *  選択プルダウン用
     *
     * @param   道路附属物点検DBコネクション
     *          $get getクエリ <br>
     *          $get['syucchoujo_cd']          出張所コード（セッションから）
     * @return array
     */
  public function getRosenFormulti($get) {
    log_message('debug', 'get_rosen_formulti');
    // 所属コード判定
    if ($get['syozoku_cd']<=2 || $get['syozoku_cd']==10001) {
      $where_cd="TRUE";
    }else{
      $where_cd="dogen_cd=".$get['dogen_cd'];
    }
    $sql= <<<EOF
SELECT
    vr.rosen_cd                          as id
  , vr.rosen_cd || ' ： ' || vr.rosen_nm as label
  , vr.rosen_cd
  , s.dogen_cd
  , vr.syucchoujo_cd
FROM
  v_rosen vr
  LEFT JOIN rfs_m_syucchoujo s
    ON vr.syucchoujo_cd = s.syucchoujo_cd
WHERE
  $where_cd
ORDER BY
  dogen_cd
  , syucchoujo_cd
  , rosen_cd
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    //$r = print_r($result, true);
    //        log_message('debug', "sql=$sql");
    //log_message('debug', "result=$r");
    return $result;
  }

  /**
     * フェーズ取得
     *
     * @return array
     */
  public function getPhaseFormulti() {
    log_message('debug', 'getPhaseFormulti');
    $sql= <<<EOF
SELECT
    phase as id
  , phase_str as label
  , phase
FROM
  rfs_m_phase_sum
WHERE
  phase <> 4
  AND phase <> 999
ORDER BY
  id
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    //$r = print_r($result, true);
    //        log_message('debug', "sql=$sql");
    //log_message('debug', "result=$r");
    return $result;
  }

  /**
     * 点検会社取得
     *
     * @param   $get getクエリ <br>
     *          $get['syucchoujo_cd']          出張所コード（セッションから）
     * @return array
     */
  public function getGyousyaFormulti($get) {
    log_message('debug', 'getGyousyaFormulti');

    $dogen_cd=$get['dogen_cd'];
    // 所属コード判定
    // 管理者が何故か10001なので特別に
    if ($get['syozoku_cd']==1 || $get['syozoku_cd']==10001) {
      // 本庁
      $where_cd="TRUE";
    }else{
      // 建管、出張所、業者
      $where_cd="s.dogen_cd = $dogen_cd";
    }

    $sql= <<<EOF
    SELECT
        bs.busyo_cd as id
      , bs.busyo_mei as label
      , bs.busyo_cd
      , bs.busyo_mei
      , bs.syucchoujo_cd
      , s.dogen_cd
    FROM
      ac_m_busyo bs
    LEFT JOIN
      m_syucchoujo s
    ON
      bs.syucchoujo_cd = s.syucchoujo_cd
    WHERE
      $where_cd
    ORDER BY
      bs.syucchoujo_cd, bs.busyo_cd
EOF;
    $query = $this->DB_imm->query($sql);
    $result = $query->result('array');
    //$r = print_r($result, true);
    //        log_message('debug', "sql=$sql");
    //log_message('debug', "result=$r");
    return $result;

  }

  /**
   * シンプルマスタ取得
   *  全て取得、sort_noでソートのマスタ取得
   *
   * 引数:tbl_nmテーブル名
   *
   * @return array
   */
  public function getMstSimple($tbl_nm, $where="true") {
    log_message('debug', 'getMstSimple');
    $sql= <<<EOF
SELECT
    *
FROM
  $tbl_nm
WHERE
  $where
ORDER BY
  sort_no
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /**
   * 設置個所マスタ取得
   *
   * 引数:$kbn 1:縦断、2:横断
   *
   * @return array
   */
  public function getSecchiKasyo($kbn) {
    log_message('debug', 'getSecchiKasyo');
    $sql= <<<EOF
SELECT
    *
FROM
  rfs_m_secchi_kasyo
WHERE
  secchi_kasyo_kbn = $kbn
ORDER BY
  sort_no
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /**
   * 出張所マスタ取得
   *
   * 引数:$syucchoujo_cd 出張所コード
   *
   * @return array
   */
  public function getSyucchoujo($syucchoujo_cd) {
    log_message('debug', 'getSyucchoujo');
    $sql= <<<EOF
SELECT
    *
FROM
  rfs_m_syucchoujo
WHERE
  syucchoujo_cd = $syucchoujo_cd
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /**
   * 運営区分マスタ取得
   *
   * 引数:$shisetsu_kbn 施設区分
   *
   * @return array
   */
  public function getUneiKbn($shisetsu_kbn) {
    log_message('debug', 'getUneiKbn');
    $sql= <<<EOF
SELECT
    *
FROM
  rfs_m_unei_kbn
WHERE
  shisetsu_kbn = $shisetsu_kbn
ORDER BY
  sort_no
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /**
   * 樹木マスタ取得
   *
   * 引数:$kbn 1:高木、2:中低木
   *
   * @return array
   */
  public function getTree($kbn) {
    log_message('debug', 'getTree');
    $sql= <<<EOF
SELECT
    *
FROM
  rfs_m_tree
WHERE
  jumoku_syu = $kbn
ORDER BY
  sort_no
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /**
   * シンプルマスタ取得(マルチセレクタ用)
   *  全て取得、sort_noでソートのマスタ取得
   *
   * 引数:tbl_nmテーブル名
   *
   * @return array
   */
  public function getMstSimpleForMulti($tbl_nm,$id,$label,$where="true") {
    log_message('debug', 'getMstSimple');
    $sql= <<<EOF
SELECT
  $id as id
  , $label as label
  , *
FROM
  $tbl_nm
WHERE
  $where
ORDER BY
  sort_no
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /**********************************/
  /********     マスタ以外     ********/
  /**********************************/
  /**
     * 施設の検索
     *   引数のsnoから施設基本情報を取得する。
     *
     * @param  sno
     * @return array 施設情報
     */
  public function getShisetsu($sno) {
    log_message('debug', 'getShisetsu');

    $sql= <<<EOF
SELECT
  sno
  ,shisetsu_cd shisetsu_cd
  ,shisetsu_ver
  ,shisetsu_kbn
  ,shisetsu_keishiki_cd
  ,rosen_cd
  ,shityouson
  ,azaban
  ,lat
  ,lon
  ,dogen_cd
  ,syucchoujo_cd
  ,substitute_road
  ,emergency_road
  ,motorway
  ,senyou
  ,secchi
  ,haishi haishi
  ,fukuin
  ,sp
  ,kp
  ,lr
  ,secchi_yyyy
  ,haishi_yyyy
  ,shisetsu_cd_daichou
  ,kyouyou_kbn
  ,sp_to
  ,ud
  ,koutsuuryou_day
  ,koutsuuryou_oogata
  ,koutsuuryou_hutuu
  ,koutsuuryou_12
  ,name
  ,keishiki_kubun_cd1
  ,keishiki_kubun_cd2
  ,encho
  ,seiri_no
FROM
  rfs_m_shisetsu
WHERE
  sno = $sno
EOF;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
/*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result;
  }

  /**
     * 施設の検索
     *   引数のsnoから施設基本情報を取得する。
     *   コードに対するマスタから名称を取得する
     *
     * @param  sno
     * @return array 施設情報
     */
  public function getShisetsuDetail($sno) {
    log_message('debug', 'getShisetsuDetail');

    $sql= <<<EOF
SELECT
    s.*
  , CASE
    WHEN s.kyouyou_kbn = 0
    THEN '休止'
    WHEN s.kyouyou_kbn = 1
    THEN '供用'
    WHEN s.kyouyou_kbn = 2
    THEN '一部休止'
    END kyouyou_kbn_str
  , CASE
    WHEN s.lr = 0
    THEN 'L'
    WHEN s.lr = 1
    THEN 'R'
    WHEN s.lr = 2
    THEN 'C'
    WHEN s.lr = 3
    THEN 'LR'
    END lr_str
  , CASE
    WHEN s.ud = 0
    THEN '上'
    WHEN s.ud = 1
    THEN '下'
    WHEN s.ud = 2
    THEN '上下'
    END ud_str
  , CASE
    WHEN s.substitute_road = 0
    THEN '有'
    WHEN s.substitute_road = 1
    THEN '無'
    ELSE '-'
    END substitute_road_str
  , CASE
    WHEN s.emergency_road = 1
    THEN '第1次'
    WHEN s.emergency_road = 2
    THEN '第2次'
    WHEN s.emergency_road = 3
    THEN '第3次'
    ELSE '-'
    END emergency_road_str
  , CASE
    WHEN s.motorway = 0
    THEN '自専道'
    WHEN s.motorway = 1
    THEN '一般道'
    ELSE '-'
    END motorway_str
  , sk.shisetsu_kbn_nm
  , skei.shisetsu_keishiki_nm
  , syu.syucchoujo_mei
  , d.dogen_mei
  , r.rosen_nm
FROM
  rfs_m_shisetsu s
  LEFT JOIN rfs_m_shisetsu_kbn sk
    ON s.shisetsu_kbn = sk.shisetsu_kbn
  LEFT JOIN rfs_m_shisetsu_keishiki skei
    ON s.shisetsu_kbn = skei.shisetsu_kbn
    AND s.shisetsu_keishiki_cd = skei.shisetsu_keishiki_cd
  LEFT JOIN rfs_m_syucchoujo syu
    ON s.syucchoujo_cd = syu.syucchoujo_cd
  LEFT JOIN rfs_m_dogen d
    ON s.dogen_cd = d.dogen_cd
  LEFT JOIN rfs_m_rosen r
    ON s.rosen_cd = r.rosen_cd
WHERE
  sno = $sno
EOF;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    /*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result;
  }

      /**
     * 部署情報
     *
     * @param   $busyo_cd <br>
     * @return array
     */
    public function getBusyoRow($busyo_cd) {
      log_message('debug', 'getBusyoRow');
  
      $sql= <<<EOF
      SELECT
        *
      FROM
        ac_m_busyo bs
      WHERE
        busyo_cd=$busyo_cd
EOF;
      $query = $this->DB_imm->query($sql);
      $result = $query->result('array');
      //$r = print_r($result, true);
      //        log_message('debug', "sql=$sql");
      //log_message('debug', "result=$r");
      return $result;
  
    }
  
  /**
   * 和暦のリストを取得する。
   * 
   * 現行のプルダウンに使用するため、項目を合わせる必要があった。
   * year:西暦
   * gengou:表記和暦
   * 
   */
  public function getWarekiList($from,$to,$jun="ASC") {
    log_message('debug', __METHOD__);
    $sql= <<<EOF
    SELECT
    seireki as year
  , wareki_ryaku || '年' gengou 
FROM
  v_wareki_seireki 
WHERE
  seireki >= $from 
  AND seireki <= $to
ORDER BY 
  seireki $jun
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

    /**
   * 建管・出張所取得
   *
   * @return array
   *         array['dogen_row'] : <br>
   */
  public function getDogenSyucchoujo2() {

    log_message('debug', 'getDogenSyucchoujo');

    $sql = <<<SQL
SELECT
  jsonb_set(
    '{}'
    , '{dogen_info}'
    , jsonb_agg(to_jsonb(all_info))
  ) AS dogen_row
FROM
  (
    SELECT
      d.*
      , syucchoujo_row
    FROM
      (
        SELECT
          *
        FROM
          rfs_m_dogen
        WHERE
          dogen_cd = {$this->session['ath']['dogen_cd']}
          OR 0 = {$this->session['ath']['dogen_cd']}
      ) d JOIN (
        SELECT
          s.dogen_cd
          , jsonb_set(
            '{}'
            , '{syucchoujo_info}'
            , jsonb_agg(to_jsonb(s) - 'dogen_cd')
          ) AS syucchoujo_row
        FROM
          (
            SELECT
              syucchoujo_cd
              , syucchoujo_mei
              , dogen_cd
              , lt_lon
              , lt_lat
              , rb_lon
              , rb_lat
            FROM
              rfs_m_syucchoujo
            ORDER BY
              syucchoujo_cd
          ) AS s
        GROUP BY
          s.dogen_cd
        ORDER BY
          s.dogen_cd
      ) AS s_row
        ON d.dogen_cd = s_row.dogen_cd
  ) all_info

SQL;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

    //$r = print_r($result, true);
    //log_message('debug', "sql=$sql");
    //log_message('debug', "result=$r");

    return $result;

  }

  /**
   * 部材マスタ取得
   *  全て取得、buzai_cdでソートのマスタ取得
   *
   * 引数:
   *
   * @return array
   */
  public function getBuzaiMst() {
    log_message('debug', __METHOD__);
    $sql= <<<EOF
SELECT
    *
FROM
  rfs_m_buzai
ORDER BY
  shisetsu_kbn
  , buzai_cd
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /**
   * 施設健全性マスタ取得
   *  全て取得、buzai_cdでソートのマスタ取得
   *
   * 引数:
   *
   * @return array
   */
  public function getShisetsuJudgeMst() {
    log_message('debug', __METHOD__);
    $sql= <<<EOF
SELECT
    *
FROM
    rfs_m_shisetsu_judge
ORDER BY
    shisetsu_judge_nm
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

}
