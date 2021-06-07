<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 道路施設管理システムトップに関するモデル
 * 返却は配列
 *
 * @access public
 * @package Model
 */
class SysTopModel extends CI_Model {

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

  public function refreshSumShisetsu(){
    $sql= <<<SQL
    REFRESH MATERIALIZED VIEW rfs_mv_shisetsu_sum;
SQL;
    $this->DB_rfs->query($sql);

  }
  /**
   * 施設管理システム集計の取得
   *
   *  施設の集計【供用・休止・合計】を
   *  取得する関数
   *
   * 引数：$dogen_cd 建管コード
   *      $syucchoujo_cd 出張所コード
   *      $shisetsu_kbn 施設区分
   */
  public function getSumShisetsu($dogen_cd, $syucchoujo_cd, $shisetsu_kbn) {
    log_message('debug', 'getSumShisetsu');

    // 出張所が0の場合は条件セットなし
    $syucchoujo_where="";
    if ($syucchoujo_cd!=0) {
      $syucchoujo_where = "AND syucchoujo_cd = ".$syucchoujo_cd." ";
    }

    $now_yyyy=date('Y');

    $sql= <<<EOF
select
  rfs_m_shisetsu_sum_idx.idx
  , $shisetsu_kbn as shisetsu_kbn
  , rfs_m_shisetsu_sum_idx.str
  , COALESCE(sum(t2.cnt_kyouyou),0) as cnt_kyouyou
  , COALESCE(sum(t2.cnt_ichibu),0) as cnt_ichibu
  , COALESCE(sum(t2.cnt_kyuushi),0) as cnt_kyuushi
  , COALESCE(sum(t2.cnt_noinput),0) as cnt_noinput
  , COALESCE(sum(t2.cnt_all),0) as cnt_all
from
  rfs_m_shisetsu_sum_idx
  left join (
    select
      *
    from
      rfs_mv_shisetsu_sum
    where
      shisetsu_kbn = $shisetsu_kbn
      AND dogen_cd = $dogen_cd
      $syucchoujo_where
  ) as t2
    on rfs_m_shisetsu_sum_idx.idx = t2.idx
group by
  rfs_m_shisetsu_sum_idx.idx
  , t2.shisetsu_kbn
  , rfs_m_shisetsu_sum_idx.str
order by
  idx

EOF;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

//    log_message('debug', "sql=$sql");
//    $r = print_r($result, true);
//    log_message('debug', "result=$r");

    return $result;

  }

}
