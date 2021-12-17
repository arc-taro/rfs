<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Userのモデル

 * @access public
 * @package Model
 */
class RfsMRosenModel extends CI_Model {
  protected $DB_rfs;
  // protected $DB_imm;

  public function __construct() {
    parent::__construct();
    // rfs
    $this->DB_rfs = $this->load->database('rfs',TRUE);
    if ($this->DB_rfs->conn_id === FALSE) {
      log_message('debug', 'データベースに接続されていません');
      return;
    }
    // imm
    // $this->DB_imm = $this->load->database('imm',TRUE);
    // if ($this->DB_imm->conn_id === FALSE) {
    //   log_message('debug', '維持管理システムデータベースに接続されていません');
    //   return;
    // }
  }

  public function getRfsMRosen($data) {
    // log_message('debug', print_r($data, true));
    $dogen_cd = $data['dogen_cd'];
    $syucchoujo_cd = $data['syucchoujo_cd'];

    $yyyy = date('Y', strtotime('-3 month'));
    // log_message('debug', $yyyy);
    $from = $yyyy;
    $to = $yyyy;

    $where_syucchoujo = ($syucchoujo_cd !== 0)
      ? sprintf(' AND syucchoujo_cd = %s', $syucchoujo_cd)
      : ' ';

    /* $sql = <<<SQL
SELECT
 rfs_m_rosen.rosen_cd,
 rfs_m_rosen_s.syucchoujo_cd,
 rfs_m_rosen.rosen_nm,
 rfs_m_rosen.__rosen_no,
 rfs_m_rosen.kukan_1,
 rfs_m_rosen.kukan_2,
 rfs_m_rosen.sokuten_1,
 rfs_m_rosen.sokuten_2,
 rfs_m_rosen.kanri_enchou,
 rfs_m_rosen.jissi_enchou,
 rfs_m_rosen.rosen_no
FROM
 rfs_m_rosen
 INNER JOIN rfs_m_rosen_s
ON
 rfs_m_rosen.rosen_cd = rfs_m_rosen_s.rosen_cd
 AND rfs_m_rosen_s.syucchoujo_cd = {$syucchoujo_cd}
SQL; */

$sql = <<<SQL
WITH tmp1 AS (
SELECT
  rms.sno
  , rms.rosen_cd
  , rms.shisetsu_kbn
  , rtcm.chk_mng_no
  , rtcm.target_dt
  , rtcm.chk_times
  , COALESCE(rtch.rireki_no, - 1) rireki_no
  , COALESCE(rtch.phase, - 1) phase
  , rvct.nendo
FROM
  rfs_m_shisetsu rms
  INNER JOIN rfs_t_chk_main rtcm
    ON rms.sno = rtcm.sno
  LEFT JOIN rfs_t_chk_huzokubutsu rtch
    ON rtcm.chk_mng_no = rtch.chk_mng_no
  INNER JOIN public.rfs_v_chk_target AS rvct
    ON rtcm.chk_mng_no = rvct.chk_mng_no
WHERE
  (
    (trim(rms.haishi) = '' OR rms.haishi IS NULL)
    AND rms.haishi_yyyy IS NULL
  )
  AND rtcm.struct_idx = 0
  AND shisetsu_kbn IN (1,2,3,4,5)
  AND dogen_cd = $dogen_cd
  $where_syucchoujo
  AND rvct.nendo BETWEEN $from AND $to
)
, tmp2 AS( -- chk_timesの最大値で絞り込み
SELECT
  sno
  , rosen_cd
  , shisetsu_kbn
  , chk_mng_no
  , rireki_no
  , phase
  , chk_times
  , target_dt
FROM
  tmp1
  NATURAL JOIN (
    SELECT
      sno
      , rosen_cd
      , MAX(chk_times) chk_times
    FROM
      tmp1
    GROUP BY
      rosen_cd
      , sno
  ) t2
)
, tmp3 AS( -- rireki_noの最大値で絞り込み
SELECT
  *
FROM
  tmp2
  NATURAL JOIN (
    SELECT
      sno
      , rosen_cd
      , MAX(rireki_no) rireki_no
    FROM
      tmp2
    GROUP BY
      rosen_cd
      , sno
  ) t2
)

SELECT
shisetsu_kbn
, rosen_cd
, rmr.rosen_nm
, target_dt
, rfs_m_phase_sum.phase
, COALESCE(cnt, 0) AS cnt
FROM
rfs_m_phase_sum
LEFT JOIN (
  SELECT --phaseの個別集計
    shisetsu_kbn
    , rosen_cd
    , phase
    , target_dt
    , count(*) cnt
  FROM
    tmp3
  GROUP BY
    rosen_cd
    , shisetsu_kbn
    , target_dt
    , phase
) goukei
  ON rfs_m_phase_sum.phase = goukei.phase
INNER JOIN rfs_m_rosen AS rmr
  USING (rosen_cd)
WHERE cnt > 0
ORDER BY
  rosen_cd ASC,
  phase ASC
SQL;

    // log_message('debug', $sql);
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    // log_message('debug', print_r($result, true));

    return $result;
  }
}
