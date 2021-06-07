<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 *
 *     COM Excel操作クラス
 *
 *     ComExcel.php
 *
 */

require_once __DIR__ . '/../libraries/phpExcel/phpXlsWrapper.php';

define('DIR_LIB', realpath(__DIR__ . '/../libraries'));

class ComExcel extends CI_Model {
    const OUTPUT_XLS = 0;
    const OUTPUT_XLSX = 1;

    /**
     * @var phpExcelWrapper
     */
    protected $xl;

    public function render_excel($template,$params, $output_mode = self::OUTPUT_XLS){
        //log_message('debug', __FILE__);
        log_message('debug', "render_excel");

        $file = phpExcelWrapper::writeXls($template,$params);
        $data = file_get_contents( $file);
        header("Content-Type: application/octet-stream");
        $outname = $this->get_output_filename($params['outname'], $output_mode);
        //header("Content-Length: ". filesize($file));
        header("Content-Disposition: attachment; filename=$outname");
        echo $data;

        exit;
    }

    /** -------------------------------------------------------------------
       * phpExcelラッパーによるPHPの書き出しとダウンロード
       * @param  Strings    $template    テンプレート名
       * @param  array    $params     パラメータ
       * @return
       */
    public function render_excel_create($template,$params){
        log_message('debug', "render_excel_create");

        $this->xl = new phpExcelWrapper;

        $file = $this->xl->CreateXls($template,$params);
    }

    /** -------------------------------------------------------------------
       * phpExcelラッパーによるPHPの書き出しとダウンロード
       * @param  Strings    $template    テンプレート名
       * @param  array    $params     パラメータ
       * @return
       */
    public function render_excel_add($template,$params){
        $file = $this->xl->AddXls($template,$params);
    }

    /** -------------------------------------------------------------------
       * phpExcelラッパーによるPHPの書き出しとダウンロード
       * @param  array    $params     パラメータ
       * @param  int    $output_mode    出力ファイル種別 (ADD 20130820 t.sato office2013対応)
       * @return
       */
    public function render_excel_output($outputname, $output_mode){
        $file = $this->xl->writeXlsSheets($outputname, $output_mode);

        $data = file_get_contents($file);
        header("Content-Type: application/octet-stream");
        $outname = $this->get_output_filename($outputname, $output_mode);
        header("Content-Disposition: attachment; filename=$outname");
        echo $data;
        exit;
    }

    /**
     * Excel ブックを保存する
     */
    public function save_excel_output($outputname, $output_mode) {
        $file = $this->xl->writeXlsSheets($outputname, $output_mode);

        return $file;
    }

    /**
     * 出力形式に応じてファイル名を生成
     *
     * @param string $outputname
     * @param int $output_mode
     * @return string
     */
    public function get_output_filename($outputname, $output_mode) {
        $outname = $outputname . '-' . date('Ymd-Hi');
        if ($output_mode == self::OUTPUT_XLSX) {
            $outname .= '.xlsx';
        } else {
            $outname .= '.xls';
        }

        return $outname;
    }
}
