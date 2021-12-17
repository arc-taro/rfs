<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Userのモデル

 * @access public
 * @package Model
 */
class RfsMShisetsuModel extends CI_Model {
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

  public function get_rfs_m_shisetsu($data) {
    $dogen_cd = $data['dogen_cd'];
    $syucchoujo_cd = $data['syucchoujo_cd'];

    $yyyy = date('Y', strtotime('-3 month'));

    $sql = <<<SQL
SELECT
  rfs_t_chk_main.chk_mng_no,
  rfs_t_chk_main.sno,
  rfs_t_chk_main.chk_times,
  rfs_t_chk_main.struct_idx,
  rfs_t_chk_main.target_dt,
  rfs_t_chk_main.busyo_cd,
  rfs_t_chk_main.kumiai_cd,
  rfs_m_shisetsu.shisetsu_cd,
  rfs_m_shisetsu.shisetsu_ver,
  rfs_m_shisetsu.shisetsu_kbn,
  rfs_m_shisetsu.shisetsu_keishiki_cd,
  rfs_m_shisetsu.rosen_cd,
  rfs_m_shisetsu.shityouson,
  rfs_m_shisetsu.azaban,
  rfs_m_shisetsu.lat,
  rfs_m_shisetsu.lon,
  rfs_m_shisetsu.dogen_cd,
  rfs_m_shisetsu.syucchoujo_cd,
  rfs_m_shisetsu.substitute_road,
  rfs_m_shisetsu.emergency_road,
  rfs_m_shisetsu.motorway,
  rfs_m_shisetsu.senyou,
  rfs_m_shisetsu.secchi,
  rfs_m_shisetsu.haishi,
  rfs_m_shisetsu.fukuin,
  rfs_m_shisetsu.sp,
  rfs_m_shisetsu.kp,
  rfs_m_shisetsu.lr,
  rfs_m_shisetsu.secchi_yyyy,
  rfs_m_shisetsu.haishi_yyyy,
  rfs_m_shisetsu.shisetsu_cd_daichou,
  rfs_m_shisetsu.kyouyou_kbn,
  rfs_m_shisetsu.sp_to,
  rfs_m_shisetsu.ud,
  rfs_m_shisetsu.koutsuuryou_day,
  rfs_m_shisetsu.koutsuuryou_oogata,
  rfs_m_shisetsu.koutsuuryou_hutuu,
  rfs_m_shisetsu.koutsuuryou_12,
  rfs_m_shisetsu.name,
  rfs_m_shisetsu.keishiki_kubun_cd1,
  rfs_m_shisetsu.keishiki_kubun_cd2,
  rfs_m_shisetsu.encho,
  rfs_m_shisetsu.seiri_no
FROM
 rfs_t_chk_main
 INNER JOIN rfs_m_shisetsu
 ON rfs_t_chk_main.sno = rfs_m_shisetsu.sno
 AND rfs_m_shisetsu.dogen_cd = {$dogen_cd}
 AND rfs_m_shisetsu.syucchoujo_cd = {$syucchoujo_cd}
 AND '{$yyyy}-04-01' <= rfs_t_chk_main.target_dt
SQL;
// where
// dogen_cd = {$dogen_cd}
// AND syucchoujo_cd = {$syucchoujo_cd}
// AND '2021-04-01' <= target_dt
    // log_message('debug', $sql);
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    // log_message('debug', print_r($result, true));
    return $result;
  }


  public function set_rfs_m_shisetsu($data) {
    $rfsMShisetsuDatas = $data["rfsMShisetsuData"];
    foreach ($rfsMShisetsuDatas as $rfsMShisetsuData) {
      if (isset($rfsMShisetsuData['isAdd'])) {
        // 追加
        $max_sno = $this->getMaxSno() + 1;
        $max_chk_mng_no = $this->getMaxChkMngNo() + 1;
        $this->setRfsMShisetsuAdd($rfsMShisetsuData, $max_sno);
        $this->setRfsTChkMainAdd($rfsMShisetsuData, $max_chk_mng_no, $max_sno, $rfsMShisetsuData['struct_idx']);
        // 支柱インデックス
        if ($rfsMShisetsuData['shisetsu_kbn'] == 4) {
          for ($i=0; $i<$rfsMShisetsuData['struct_idx']; $i++) {
            $max_chk_mng_no = $this->getMaxChkMngNo() + 1;
            $this->setRfsTChkMainAdd($rfsMShisetsuData, $max_chk_mng_no, $max_sno, $i);
          }
        }
      }
      else if (!isset($rfsMShisetsuData['isAdd']) && isset($rfsMShisetsuData['isEdit'])) {
        // 編集
        $this->setRfsMShisetsuEdit($rfsMShisetsuData);
        $this->setRfsTChkMainEdit($rfsMShisetsuData);
      }
    }
  }

  private function getMaxChkMngNo() {

    $sql = <<<EOF
SELECT
 COALESCE(MAX(chk_mng_no), 100000000) max_chk_mng_no 
FROM
 rfs_t_chk_main
WHERE
 chk_mng_no > 100000000
EOF;

   $query = $this->DB_rfs->query($sql);
   $result = $query->result('array');
   return $result[0]['max_chk_mng_no'];
}

private function getMaxSno() {

  $sql = <<<EOF
SELECT
 COALESCE(MAX(sno), 100000000) max_sno 
FROM
 rfs_m_shisetsu
WHERE
 sno > 100000000
EOF;

 $query = $this->DB_rfs->query($sql);
 $result = $query->result('array');
 return $result[0]['max_sno'];
}

  public function setRfsMShisetsuAdd($rfsMShisetsuData, $max_sno) {

    $sno = $max_sno;
    $rosen_cd = $rfsMShisetsuData['rosen_cd'];
    $shisetsu_kbn = $rfsMShisetsuData['shisetsu_kbn'];
    $shisetsu_keishiki_cd = $rfsMShisetsuData['shisetsu_keishiki_cd'];
    $shisetsu_cd = $rfsMShisetsuData['shisetsu_cd'];
    $lr = $this->DB_rfs->escape($rfsMShisetsuData['lr']);
    $sp = $this->DB_rfs->escape($rfsMShisetsuData['sp']);
    $sp_to = $this->DB_rfs->escape($rfsMShisetsuData['sp_to']);

    $this->DB_rfs->trans_start();

    $sql = <<<EOF
INSERT
INTO rfs_m_shisetsu (
 sno,
 shisetsu_cd,
 shisetsu_ver,
 shisetsu_kbn,
 shisetsu_keishiki_cd,
 rosen_cd,
 lr,
 sp,
 sp_to
)
VALUES (
 {$sno},
 '{$shisetsu_cd}',
 1,
 {$shisetsu_kbn},
 {$shisetsu_keishiki_cd},
 {$rosen_cd},
 {$lr},
 {$sp},
 {$sp_to}
)
EOF;

    log_message('debug', $sql);
    $query = $this->DB_rfs->query($sql);

    if ($this->DB_rfs->trans_status() === FALSE) {
      $this->DB_rfs->trans_rollback();
      return false;
    }

    $this->DB_rfs->trans_complete();
  }

  public function setRfsTChkMainAdd($rfsMShisetsuData, $max_chk_mng_no, $max_sno, $struct_idx) {

    $chk_mng_no = $max_chk_mng_no;
    $sno = $max_sno;
    
    $this->DB_rfs->trans_start();

    $sql = <<<EOF
INSERT
INTO rfs_t_chk_main (
 chk_mng_no,
 sno,
 chk_times,
 struct_idx,
 target_dt
)
VALUES (
 {$chk_mng_no},
 {$sno},
 1,
 {$struct_idx},
 now()
)
EOF;

    log_message('debug', $sql);
    $query = $this->DB_rfs->query($sql);

    if ($this->DB_rfs->trans_status() === FALSE) {
      $this->DB_rfs->trans_rollback();
      return false;
    }

    $this->DB_rfs->trans_complete();
  }


  public function setRfsMShisetsuEdit($rfsMShisetsuData) {

    $sno = $rfsMShisetsuData['sno'];

    $rosen_cd = $rfsMShisetsuData['rosen_cd'];
    $shisetsu_keishiki_cd = $rfsMShisetsuData['shisetsu_keishiki_cd'];
    $lr = $this->DB_rfs->escape($rfsMShisetsuData['lr']);
    $sp = $this->DB_rfs->escape($rfsMShisetsuData['sp']);
    $sp_to = $this->DB_rfs->escape($rfsMShisetsuData['sp_to']);

    $this->DB_rfs->trans_start();

    $sql = <<<EOF
UPDATE
 rfs_m_shisetsu
SET
 rosen_cd={$rosen_cd},
 shisetsu_keishiki_cd='{$shisetsu_keishiki_cd}',
 lr={$lr},
 sp={$sp},
 sp_to={$sp_to}
WHERE
 sno={$sno}
EOF;

    log_message('debug', $sql);
    $query = $this->DB_rfs->query($sql);

    if ($this->DB_rfs->trans_status() === FALSE) {
      $this->DB_rfs->trans_rollback();
      return false;
    }

    $this->DB_rfs->trans_complete();
  }

  public function setRfsTChkMainEdit($rfsMShisetsuData) {

    $chk_mng_no = $rfsMShisetsuData['chk_mng_no'];

    $struct_idx = $rfsMShisetsuData['struct_idx'];
    
    $this->DB_rfs->trans_start();

    $sql = <<<EOF
UPDATE
 rfs_t_chk_main
SET
 struct_idx={$struct_idx}
WHERE
 chk_mng_no={$chk_mng_no}
EOF;

    log_message('debug', $sql);
    $query = $this->DB_rfs->query($sql);

    if ($this->DB_rfs->trans_status() === FALSE) {
      $this->DB_rfs->trans_rollback();
      return false;
    }

    $this->DB_rfs->trans_complete();
  }

}