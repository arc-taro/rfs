<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Userのモデル

 * @access public
 * @package Model
 */
class RfsTZenkeiPictureModel extends CI_Model {
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

  public function get_rfs_t_zenkei_picture($data) {
    // $chk_mng_no = $data['chk_mng_no'];
    // $rireki_no = $data['rireki_no'];
    $rfsMShisetsuDatas = $data["rfsMShisetsuData"];
    $sno_in = '';
    foreach ($rfsMShisetsuDatas as $rfsMShisetsuData) {
      $sno_in .= $rfsMShisetsuData['sno'] . ',';
    }
    if(strlen($sno_in) != 0) {
      $sno_in = substr($sno_in, 0, strlen($sno_in) - 1);
    }
    // log_message('debug', $sno_in);

    $sql = <<<SQL
SELECT
 *
FROM
 rfs_t_zenkei_picture
WHERE
 sno in ({$sno_in})
SQL;

    // log_message('debug', $sql);
    $query = $this->DB_rfs->query($sql);
    $results = $query->result('array');
    // log_message('debug', print_r($result, true));
    for ($i=0; $i<count($results); $i++) {
      $result = $results[$i];
      if (!empty($result['path'])) {
        // img_base64
        $result['img_base64'] = '';
        $path = $this->config->config['image_real_path'] . $result['path'];
        // log_message('debug', $path);
        if ($data = @file_get_contents($path)) {
          $result['img_base64'] = base64_encode($data);
        }

        // filename
        $filenames = explode("/", $result['path']);
        $result['filename'] = $filenames[count($filenames)-1];

        $results[$i] = $result;
      }
    }
    return $results;
  }
}
