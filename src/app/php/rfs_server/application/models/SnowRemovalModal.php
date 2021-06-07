<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 道路施設管理システムトップに関するモデル
 * 返却は配列
 *
 * @access public
 * @package Model
 */
class SnowRemovalModal extends CI_Model {

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

  // 除雪システム用データを取得する
  public function getSnowRemovalData($post) {
    $bs_data=null;
    $rh_data=null;
    $dogen_cd=$post['dogen_cd'];
    $syucchoujo_cd=$post['syucchoujo_cd'];
    $rslt=array();
    if ($post['chk_bs']=="true") {
      $bs_data=$this->getBsData($dogen_cd,$syucchoujo_cd);
      if ($bs_data) {
        $rslt['bs']['shisetsu_kbn']=4;
        $rslt['bs']['data']=$bs_data;
      }
    }
    if ($post['chk_rh']=="true") {
      $rh_data=$this->getRhData($dogen_cd,$syucchoujo_cd);
      if ($rh_data) {
        $rslt['rh']['shisetsu_kbn']=21;
        $rslt['rh']['data']=$rh_data;
      }
    }
    return $rslt;
  }

  // 防雪柵データ取得
  protected function getBsData($dogen_cd,$syucchoujo_cd) {
    // 出張所がある場合は出張所コード
    // 出張所が無い場合は建管コード
    // 建管コードがない場合は設定なし（全て）
    $where = "";
    if ($syucchoujo_cd!=0) {
      $where="AND syucchoujo_cd=".$syucchoujo_cd." ";
    } else if ($dogen_cd!=0) {
      $where="AND dogen_cd=".$dogen_cd." ";
    }
    $sql= <<<EOF
    WITH tmp_shisetsu AS ( 
      SELECT
        sno
        , shisetsu_cd
        , shisetsu_ver
        , rosen_cd
        , dogen_cd
        , syucchoujo_cd
        , haishi
        , haishi_yyyy
        , encho 
      FROM
        rfs_m_shisetsu 
      WHERE
        shisetsu_kbn = 4
        AND (haishi IS NULL OR haishi = '')
        $where
    ) 
    , shisetsu AS ( 
      SELECT
          tmp1.* 
      FROM
        tmp_shisetsu tmp1 JOIN ( 
          SELECT
              shisetsu_cd
            , MAX(shisetsu_ver) shisetsu_ver 
          FROM
            tmp_shisetsu 
          GROUP BY
            shisetsu_cd
        ) tmp2 
          ON tmp1.shisetsu_cd = tmp2.shisetsu_cd 
          AND tmp1.shisetsu_ver = tmp2.shisetsu_ver
    ) 
    , shisetsu_daichou AS ( 
      SELECT
          shisetsu.shisetsu_cd
        , shisetsu.rosen_cd
        , shisetsu.dogen_cd
        , shisetsu.syucchoujo_cd
        , shisetsu.haishi
        , shisetsu.haishi_yyyy
        , shisetsu.encho
        , bs.sakusyu_cd
        , bs.saku_kbn_cd
        , bs.saku_keishiki_cd 
      FROM
        shisetsu 
        LEFT JOIN rfs_t_daichou_bs bs 
          ON shisetsu.sno = bs.sno
    ) 
    SELECT
        sd.shisetsu_cd
      , sd.rosen_cd
      , sd.dogen_cd
      , sd.syucchoujo_cd
      , sd.haishi
      , sd.haishi_yyyy
      , sd.encho
      , sd.sakusyu_cd
      , sakusyu.sakusyu
      , sd.saku_kbn_cd
      , saku_kbn.saku_kbn
      , sd.saku_keishiki_cd 
      , saku_keishiki.saku_keishiki
      , kr.snow_removal_keishiki_cd 
      , sr_keishiki.snow_removal_keishiki_nm
      , '' haishi_snow_removal
      , '' haishinen_snow_removal
    FROM
      shisetsu_daichou sd 
      LEFT JOIN rfs_m_bs_keishiki_relation kr 
        ON sd.sakusyu_cd = kr.sakusyu_cd 
        AND sd.saku_kbn_cd = kr.saku_kbn_cd 
        AND sd.saku_keishiki_cd = kr.saku_keishiki_cd
      LEFT JOIN rfs_m_sakusyu sakusyu 
        ON sd.sakusyu_cd = sakusyu.sakusyu_cd
      LEFT JOIN rfs_m_saku_kbn saku_kbn
        ON sd.saku_kbn_cd   = saku_kbn.saku_kbn_cd  
      LEFT JOIN rfs_m_saku_keishiki saku_keishiki 
        ON sd.saku_keishiki_cd = saku_keishiki.saku_keishiki_cd
      LEFT JOIN rfs_m_snow_removal_keishiki sr_keishiki 
        ON kr.snow_removal_keishiki_cd = sr_keishiki.snow_removal_keishiki_cd
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

  // ロードヒーティングデータ取得
  protected function getRhData($dogen_cd,$syucchoujo_cd) {
    // 出張所がある場合は出張所コード
    // 出張所が無い場合は建管コード
    // 建管コードがない場合は設定なし（全て）
    $where = "";
    if ($syucchoujo_cd!=0) {
      $where="AND syucchoujo_cd=".$syucchoujo_cd." ";
    } else if ($dogen_cd!=0) {
      $where="AND dogen_cd=".$dogen_cd." ";
    }
    $sql= <<<EOF
    WITH tmp_shisetsu AS ( 
      SELECT
          sno
        , shisetsu_cd
        , shisetsu_ver
        , rosen_cd
        , shityouson
        , azaban
        , dogen_cd
        , syucchoujo_cd
        , haishi
        , haishi_yyyy
        , keishiki_kubun_cd1
        , keishiki_kubun_cd2 
      FROM
        rfs_m_shisetsu 
      WHERE
        shisetsu_kbn = 21
        AND (haishi IS NULL OR haishi = '')
        $where
    ) 
    , shisetsu AS ( 
      SELECT
          tmp1.* 
      FROM
        tmp_shisetsu tmp1 JOIN ( 
          SELECT
              shisetsu_cd
            , MAX(shisetsu_ver) shisetsu_ver 
          FROM
            tmp_shisetsu 
          GROUP BY
            shisetsu_cd
        ) tmp2 
          ON tmp1.shisetsu_cd = tmp2.shisetsu_cd 
          AND tmp1.shisetsu_ver = tmp2.shisetsu_ver
    ) 
    , keishiki_kubun1 AS ( 
      SELECT
          * 
      FROM
        rfs_m_keishiki_kubun 
      WHERE
        shisetsu_kbn = 21 
        AND syubetsu = 1
    ) 
    , keishiki_kubun2 AS ( 
      SELECT
          * 
      FROM
        rfs_m_keishiki_kubun 
      WHERE
        shisetsu_kbn = 21 
        AND syubetsu = 2
    ) 
    SELECT
        shisetsu.shisetsu_cd
      , shisetsu.rosen_cd
      , shisetsu.shityouson
      , shisetsu.azaban
      , shisetsu.dogen_cd
      , shisetsu.syucchoujo_cd
      , shisetsu.haishi
      , shisetsu.haishi_yyyy
      , shisetsu.keishiki_kubun_cd1
      , k1.keishiki_kubun keishiki_kubun1
      , shisetsu.keishiki_kubun_cd2
      , k2.keishiki_kubun keishiki_kubun2
      , rh.menseki_syadou
      , rh.menseki_hodou 
      , '' syahodou_cd
      , '' nendo
      , '' haishi_snow_removal
      , '' haishinen_snow_removal
      , CASE 
        WHEN shisetsu.keishiki_kubun_cd1 = 3 THEN '温泉'
        ELSE k2.keishiki_kubun
        END AS netsugen
    FROM
      shisetsu 
      LEFT JOIN rfs_t_daichou_rh rh 
        ON shisetsu.sno = rh.sno 
      LEFT JOIN keishiki_kubun1 k1 
        ON shisetsu.keishiki_kubun_cd1 = k1.keishiki_kubun_cd 
      LEFT JOIN keishiki_kubun2 k2 
        ON shisetsu.keishiki_kubun_cd2 = k2.keishiki_kubun_cd
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
  
}
