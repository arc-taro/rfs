<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../libraries/phpExcel/MultisheetExcelWrapper.php';

/**
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property CI_Zip $zip
 * @property SchShisetsu $SchShisetsu
 * @property SchCheck $SchCheck
 */
class CreateShisetsuDataExcel extends CI_Model {

  const TMP_PATH = __DIR__ . '/../libraries/phpExcel/results/';

  protected $file_path;
  protected $file_nm;
  protected $file_path_nm;

  public function __construct() {
    parent::__construct();

    $this->picture_root = $this->config->config['www_path'];

    $this->rfs = $this->load->database('rfs', true);

    $this->load->model('SchCheck');
  }

  /**
   * チェック対象の施設台帳 Excel ファイルパスを取得する
   *
   * @param array $checked_data
   * @param int $excel_ver
   */
  public function getExcelInfo($checked_data, $excel_ver) {
    log_message('debug', __METHOD__);

    // Excel管理情報の取得
    $excel_info = $this->schExcelInfo($checked_data);

    return $excel_info;
  }

  // Excel管理テーブルの対象レコードを取得
  protected function schExcelInfo($checked_data) {

    $sno = $checked_data['sno'];

    $sql = <<<EOF
SELECT
  s.sno ssno
  , s.syucchoujo_cd
  , s.shisetsu_cd
  , s.shisetsu_kbn
  , e.sno esno
  , e.file_path
FROM
  rfs_m_shisetsu s
  LEFT JOIN rfs_t_daichou_excel e
    ON e.sno = s.sno
WHERE
  s.sno = $sno
EOF;
    //$r = print_r($sql, true);
    //log_message('info', "\n $r \n");
    $query = $this->rfs->query($sql);

    return $query->result_array();
  }
}
