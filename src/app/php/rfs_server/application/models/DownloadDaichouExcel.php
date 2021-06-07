<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property CI_Zip $zip
 * @property SchShisetsu $SchShisetsu
 * @property SchCheck $SchCheck
 */
class DownloadDaichouExcel extends CI_Model {

  public function __construct() {
    parent::__construct();
    $this->excel_root = $this->config->config['www_path'];
    $this->rfs = $this->load->database('rfs', true);
  }

  // ==========================================
  //   呼び出し部
  // ==========================================

  /**
   * 台帳の Excel ブックをダウンロードする
   *
   * @param array shisetsu_cd
   *              file_path
   */
  public function dlDaichouData($param) {
    log_message('debug', __METHOD__);

    $file_path;
    $file_nm;

    $file_path=$this->excel_root.$param['file_path'];
    $file_nm=pathinfo( $file_path )["basename"];

    try {
      // Excelのダウンロード
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename='.$file_nm);
      readfile($file_path);

    } catch (RecordNotFoundException $e) {
      $this->error($e->getMessage());
    }
  }

  // ==========================================
  //   データ取得部
  // ==========================================
  /**
    * 施設の検索
    *   引数のsnoから施設基本情報を取得する。
    *
    * @param  sno
    * @return array 施設情報
  */
  public function getDaichouXlsData($sno){
    $sql = <<<EOF
        SELECT
          s.shisetsu_cd, de.file_path
        FROM
          rfs_t_daichou_excel de
        JOIN
          rfs_m_shisetsu s
        ON
          de.sno = s.sno
        WHERE
          de.sno = $sno
EOF;

    $query = $this->rfs->query($sql);
    $result = $query->row_array();

    return $result;
  }

}
