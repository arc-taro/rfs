<?php
require_once __DIR__.'/Classes/PHPExcel.php';

/**
 * 結果のブックに複数の異なるシートを含める機能対応のラッパー
 */
class MultisheetExcelWrapper {
    const FORMAT_XLS = 'Excel5';
    const FORMAT_XLSX = 'Excel2007';

    /**
     * テンプレートファイルのディレクトリ
     * @var string
     */
    public $templateDir;
    /**
     * 結果ファイルのディレクトリ
     * @var string
     */
    public $resultDir;
    public $ignoreUndefinedVar = false;

    /**
     * @var PHPExcel
     */
    protected $book;

    protected $templateSheet;
    protected $resultSheet;

    /**
     * テンプレートを読み込む
     *
     * @param string $template テンプレートファイル名
     * @param string $sheet 初期選択のシート名
     */
    public function __construct($template = null, $sheet = null) {
        $this->templateDir = __DIR__.'/templates';
        $this->resultDir = __DIR__.'/results';

        if ($template) {
            $this->loadTemplate($template, $sheet);
        }
    }

    /**
     * テンプレートを読み込む（通常はコンストラクターから呼び出し）
     *
     * @param string $filename
     * @param string|int $sheet
     */
    public function loadTemplate($filename, $sheet = null) {
        $this->book = PHPExcel_IOFactory::load($this->templateDir.'/'.$filename);

        if ($sheet !== null) {
            $this->setTemplateSheet($sheet);
        } else {
            $this->templateSheet = $this->book->getSheet();
        }
    }

    /**
     * テンプレートとして使用するシートを変更する
     *
     * @param string|int $sheet シート名またはシート番号
     */
    public function setTemplateSheet($sheet) {
        if (is_numeric($sheet)) {
            $this->templateSheet = $this->book->getSheet($sheet);
        } else {
            $this->templateSheet = $this->book->getSheetByName('temp_' . $sheet);
        }

        if (!$this->templateSheet) {
            throw new ExcelWrapperException('テンプレートシートが見つかりません: '.$sheet);
        }
    }

  /**
     * シートを追加する。
     *
     * @param string $filename
     * @param string|int $src_sheet テンプレートのシート名
     * @param string|int $dest_sheet 出力先シート名
     */
    public function addTemplateSheet($filename,$src_sheet,$dest_sheet) {

    // テンプレートからシートを引きだす
//    $tmp_book = PHPExcel_IOFactory::load($this->templateDir.'/'.$filename);
    $new_sheet = $this->book->getSheetByName($src_sheet)->copy();
//      $new_sheet = $tmp_book->getSheetByName($src_sheet)->copy();
    // 新しいシートの名前
    $new_sheet->setTitle($dest_sheet);
    // シートのコピー
    $this->book->addSheet($new_sheet);
    // メモリの開放
/*
    $tmp_book->disconnectWorksheets();
    unset($tmp_book);
*/

  }

    /**
     * 1シート処理して結果ファイルに追加する
     *
     * @param array $params テンプレート変数
     * @param string $name 結果のシート名（同名のシートを複数追加するとエラー）
     */
    public function renderSheet(array $params = null, $name = null) {
        $this->resultSheet = clone $this->templateSheet;

        if ($name === null) {
            $name = substr($this->resultSheet->getTitle(), 5);
        }

        foreach ($this->book->getWorksheetIterator() as $existingSheet) {
            if ($existingSheet->getTitle() == $name) {
                throw new ExcelWrapperException('同名のシートが存在します: '.$name);
            }
        }

        $this->resultSheet->setTitle($name);
        $this->book->addSheet($this->resultSheet);

        // active sheet index は自動更新されないのでここで更新
        $newIndex = $this->book->getIndex($this->resultSheet);
        $this->book->setActiveSheetIndex($newIndex);

        $this->assignProcess($params);
    }

    /**
     * 処理結果の Excel ファイルを出力する
     *
     * @param string $outputName 出力先ファイル名
     * @param string $format ファイル形式（MultisheetExcelWrapper::FORMAT_XLS / MultisheetExcelWrapper::FORMAT_XLSX）。空なら拡張子から推測
     * @return string 出力先ファイルのフルパス
     */
    public function saveResult($filePath, $fileName, $format = self::FORMAT_XLS) {
        $this->removeTemplateSheets();

        // 保存前に最初のシートをアクティブにする
        $this->book->setActiveSheetIndex();

        $outputPath = $filePath . $fileName;

        $writer = PHPExcel_IOFactory::createWriter($this->book, $format);
        $writer->save($outputPath);

        return $outputPath;
    }

    public function downloadResult($filePath, $fileName, $format = self::FORMAT_XLS) {
        $downloadName = $fileName;

        $outputPath = $this->saveResult($filePath, $fileName, $format);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.$downloadName);
        readfile($outputPath);

//        exit;
    }

    protected function removeTemplateSheets() {
        while ($this->book->getSheetCount()
            && preg_match('/^temp_/', $this->book->getSheet()->getTitle())) {
            $this->book->removeSheetByIndex();
        }
    }

    protected function getExtension($format) {
        if ($format == self::FORMAT_XLS) {
            return '.xls';
        } elseif ($format == self::FORMAT_XLSX) {
            return '.xlsx';
        } else {
            throw new InvalidArgumentException('不明な形式です: '.$format);
        }
    }

    /**
     * メモリの開放
     */
    public function destroy() {
        $this->book->disconnectWorksheets();
        unset($this->book);
    }

    //
    // ================== phpExcelWrapper からコピー =======================
    //

    /**
     * テンプレート変数を置換する
     *
     * @param PHPExcel $objPHPExcel
     * @param PHPExcel_Worksheet $objWorksheet
     * @param array $params
     */
    protected function assignProcess(array $params) {
        $objPHPExcel = $this->book;
        $objWorksheet = $this->resultSheet;

            $list_data=array();
            $row_data=array();
            foreach ($objWorksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(true);
                foreach ($cellIterator as $cell) {
                     $match = array();
                     $match2 = array();
                    if( !preg_match_all('/<<(.*?)>>/',$cell->getValue(),$match,PREG_PATTERN_ORDER)){
                        continue;
                    }

                    foreach( $match[1] as $tag ){
                        $value="";

// TODO 印章のセンタリングを計算によって求めたい（暫定で固定のオフセットにしている）
// UPD 20120316 hirano オフセットパラメータ追加のため、IF分修正 -->
                        if( preg_match('/^picture:\$([^:]*?):([^:]*?):([^:]*?):([^:]*?):([^:]*?)$/',$tag,$match2) || preg_match('/^picture:\$([^:]*?):([^:]*?):([^:]*?)$/',$tag,$match2)){
                        //if( preg_match('/^picture:\$([^:]*?):([^:]*?):([^:]*?)$/',$tag,$match2)){
// UPD 20120316 hirano オフセットパラメータ追加のため、IF分修正 <--
                            // 画像の埋め込み

                            if( $params[$match2[1]]!="" && file_exists($params[$match2[1]])){
                                    $objDrawing = new PHPExcel_Worksheet_Drawing();


// UPD 20130409 hirano イメージの実サイズを半分にする -->

                                    $file1 = $params[$match2[1]];                                     // 元画像ファイル
                                    $file2 = dirname(__FILE__)."/results/".uniqid("imgs_").".jpg";     // 画像保存先

// UPD 20150510 haramoto 入力画像の形式ごとに開く方法を変更 -->
                                    $image_type = exif_imagetype($file1);
                                    switch($image_type) {
                                        case IMAGETYPE_JPEG:
                                            $in = ImageCreateFromJPEG($file1);                              // 元画像ファイル読み込み
                                            break;
                                        case IMAGETYPE_PNG:
                                            $in = imagecreatefrompng($file1);
                                            break;
                                        case IMAGETYPE_GIF:
                                            $in = imagecreatefromgif($file1);
                                            break;
                                        case IMAGETYPE_BMP;
                                        $in = $this->ImageCreateFromBMP($file1);
                                        break;
                                    }
// UPD 20150223 入力画像がJPGではなくPNG-->
                                    //$in = ImageCreateFromPNG($file1);
                                    //$in = ImageCreateFromJPEG($file1);                              // 元画像ファイル読み込み
// UPD 20150223 入力画像がJPGではなくPNG<--
// UPD 20150510 haramoto 入力画像の形式ごとに開く方法を変更 <--


                                    //$moto_size = GetImageSize($file1);                               // 元画像サイズ取得
                                    //$width = $moto_size[0] / 2;                                        // 生成する画像サイズ（横）
                                    //$height = $moto_size[1] / 2;                                       // 生成する画像サイズ（縦）
                                    //$out = ImageCreateTrueColor($width, $height);                     // 画像生成
                                    //ImageCopyResampled($out, $in, 0, 0, 0, 0, $width, $height, $moto_size[0], $moto_size[1]);    // サイズ変更・コピー
                                    ImageJPEG($in, $file2);
                                    //ImageJPEG($out, $file2);                                        // 画像保存

                                    $objDrawing->setPath($file2);
                                    //$objDrawing->setPath($params[$match2[1]]);
                                    $objDrawing->setCoordinates($cell->getCoordinate());


                                    //$size = getimagesize($params[$match2[1]]);
                                    $size = getimagesize($file2);

// UPD 20130409 hirano イメージの実サイズを半分にする <--


                                    $oWidth = $size[0];
                                    $oHeight= $size[1];
                                    // 貼り付け時のサイズを決定
// UPD 20111102 nakamura 変数間違い -->
                                    $pWidth = $match2[2]/$oWidth;
                                    $pHeight= $match2[3]/$oHeight;
                                    //$pWidth = $match2[1]/$oWidth;
                                    //$pHeight= $match2[2]/$oWidth;
// UPD 20111102 nakamura 変数間違い <--
// UPD 20111102 nakamura 判定間違い -->
                                    // 縦横比固定
                                    $objDrawing->setResizeProportional = false;
                                    // 大きさ設定
                                    if( $pWidth <= $pHeight ){
                                        // 画像のほうが横方向が長いとき
                                        // 画像を横方向の比率で全体を調整
                                        $objDrawing->setWidthAndHeight($match2[2] * 0.877,$oHeight * $pWidth);
                                        //$objDrawing->setWidth($match2[2] * 0.877);
                                        //$objDrawing->setHeight($oHeight * $pWidth);
                                        // 縦方向が余るのでオフセットを計算
                                        $ofsy = $match2[3] - $oHeight * $pWidth;
                                        $ofsx = 0;
                                    } else {
                                        // 画像のほうが縦方向が長いとき
                                        // 画像を縦方向の比率で全体を調整
                                        $objDrawing->setWidthAndHeight($oWidth * $pHeight * 0.877,$match2[3]);
                                        //$objDrawing->setWidth($oWidth * $pHeight * 0.877);
                                        //$objDrawing->setHeight($match2[3]);
                                        // 横方向が余るのでオフセットを計算
                                        $ofsx = $match2[2] - $oWidth * $pHeight;
                                        $ofsy = 0;
                                    }
                                    /*if( $pWidth > $pHeight ){
                                        $objDrawing->setWidth($match2[1]);
                                    }else{
                                        $objDrawing->setHeight($match2[2]);
                                    }
                                    */
// UPD 20120316 hirano オフセット変更パラメータがある場合（4つ目はX、5つ目はY）はオフセットを増 -->
                                    if ($match2[4]) {
                                        $objDrawing->setOffsetX($match2[4] + $ofsx);
                                        $objDrawing->setOffsetY($match2[5] + $ofsy);
                                    } else {
                                        // 元々の流れ
                                        $objDrawing->setOffsetX(5 + $ofsx);
                                        $objDrawing->setOffsetY(5 + $ofsy);
                                    }

// UPD 20111102 nakamura 判定間違い <--
// UPD 20111102 nakamura オフセット調整 -->
//                                    $objDrawing->setOffsetX(5 + $ofsx);
//                                    $objDrawing->setOffsetY(5 + $ofsy);
//                                    $objDrawing->setOffsetX(5);
//                                    $objDrawing->setOffsetY(5);
// UPD 20111102 nakamura オフセット調整 <--
// UPD 20120316 hirano オフセット変更パラメータがある場合はオフセットを増 <--

                                    $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
                            }
                            $value="";
                            // ADD 20110922 nakamura 固定の画像貼り付け -->
                        } elseif( preg_match('/^fixpict:([^:]*?):$/',$tag,$match2)){
                            // 画像の埋め込み
                            $file = dirname(__FILE__)."/templates/".$match2[1].".bmp";
                            if( $match2[1]!="" && file_exists($file)){
                                    $objDrawing = new PHPExcel_Worksheet_Drawing();
                                    $objDrawing->setPath($file);
                                    $objDrawing->setCoordinates($cell->getCoordinate());
                                    $size = getimagesize($file);
                                    // UPD 20160831 yamashita -->
                                    //$objDrawing->setresizeProportional=true;
                                    $objDrawing->setResizeProportional(false);
                                    // UPD 20160831 yamashita <--
                                    // UPD 20111102 nakamura -->
                                    $objDrawing->setWidth($size[0] * 0.877);    // 画像が横に伸びる現象が不明
                                    //$objDrawing->setWidth($size[0]);        // 画像が横に伸びる現象が不明
                                    // UPD 20111102 nakamura <--
                                    $objDrawing->setHeight($size[1]);
                                    $objDrawing->setOffsetX(5);
                                    $objDrawing->setOffsetY(5);
                                    $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
                            }
                            $value="";

                            $cell->setValue($value);
                            continue;

                        } elseif (preg_match('/^circle:\$([^:]*?):([^:]*?):([^:]*?):([^:]*?)$/',
                            $tag, $match2)) {
                            // 変数が真なら丸を付ける
                            if ($params[$match2[1]]) {
                                $draw = new PHPExcel_Worksheet_Drawing();
                                $draw->setPath(__DIR__ . '/templates/images/circle.png');
                                $draw->setWidth($match2[2]);
                                $draw->setHeight($match2[3]);
                                $draw->setCoordinates($match2[4]);
                                $draw->setWorksheet($objPHPExcel->getActiveSheet());
                            }

                            // ADD 20110922 nakamura 固定の画像貼り付け -->
                        }elseif( preg_match('/^if:(.*?) then (.*?) else (.*?)$/',$tag,$match2)){
                        // 条件分岐によって違うPHP式
                            $cond =     preg_replace('/\$([a-zA-z0-9_]+)/',"\$params['$1']",$match2[1]);
                            $value1 =    preg_replace('/\$([a-zA-z0-9_]+)/',"\$params['$1']",$match2[2]);
                            $value2 =    preg_replace('/\$([a-zA-z0-9_]+)/',"\$params['$1']",$match2[3]);
                            if( eval("return $cond;") ){
                                $value=eval("return $value1;");
                            }else{
                                $value=eval("return $value2;");
                            }
                        }else if( preg_match('/^loop:(.*?)$/',$tag,$match2) ){
                        // リストデータの読み込み
                            $name = preg_replace('/\$([a-zA-z0-9_]+)/',"\$params['$1']",$match2[1]);
                            if( $name ){
                                $list_data = eval("return $name;");
                            }
                        }else if( preg_match('/^loop_row$/',$tag,$match2)){
                            if( $list_data ){
                                $row_data=array_shift($list_data);
                            }else{
                                unset($row_data);
                            }
                        }else if( preg_match('/^loop_cell:(.*?)$/',$tag,$match2)){
                            $name = preg_replace('/\$([a-zA-z0-9_]+)/',"$1",$match2[1]);
                            if( $row_data[$name] ){
                                $value="'".$row_data[$name]."'";
                            }else{
                                $value="''";
                            }
                        }else if( preg_match('/^(.*?)$/',$tag,$match2)){

                            // 式の埋め込み
                            $value = preg_replace('/\$([a-zA-z0-9_]+)/',"\$params['$1']",$match2[1]);

                            // _rowが含まれている場合はセット数を意識する
                            if (preg_match('/_row/', $tag)) {
                                if (mb_ereg_replace('[^0-9]', '', $tag)>=$params['row_cnt']){
                                    $value="''";
                                }
                            }

                        }

/*                        log_message('debug', "---------------tag------------------>$tag");
                        log_message('debug', "---------------eval----------------->".eval("return $value;"));
*/
                        $tag_esc = preg_replace('/(\$|\[|\])/','\\\$1',$tag);
                        if ($this->ignoreUndefinedVar) {
                            @$val=eval("return $value;");
                        } else {
                            $val=eval("return $value;");
                        }
                        //$cell->setValue(preg_replace('/<<'.$tag_esc.'>>/',$val,$cell->getValue()));
                        // 文字列項目に桁数が多い数値を入れると指数が入ってしまうのでsetValueではなくsetValueExplicitを使う
                        // 数値項目についても問題ないことを確認済み
                        $cell->setValueExplicit(preg_replace('/<<'.$tag_esc.'>>/',$val,$cell->getValue()));

                        // 改行があった場合行高さ変更
/*                        if (preg_match('/\n/',$val)) {
                            $objWorksheet->getRowDimension($cell->getrow())->setRowHeight();
                        }
*/
                    }
                 }
            }
    }

  function ImageCreateFromBMP($filename) {
    //画像ファイルをバイナリモードでOpen
    if (! $f1 = fopen($filename, "rb")) return FALSE;

    //1: 概要データのロード
    $FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1, 14));
    if ($FILE['file_type'] != 19778) return FALSE;

    //2: BMPデータのロード
    $BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel'.
                  '/Vcompression/Vsize_bitmap/Vhoriz_resolution'.
                  '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1, 40));
    $BMP['colors'] = pow(2, $BMP['bits_per_pixel']);
    if ($BMP['size_bitmap'] == 0) $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
    $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel']/8;
    $BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
    $BMP['decal'] = ($BMP['width']*$BMP['bytes_per_pixel']/4);
    $BMP['decal'] -= floor($BMP['width']*$BMP['bytes_per_pixel']/4);
    $BMP['decal'] = 4-(4*$BMP['decal']);
    if ($BMP['decal'] == 4) $BMP['decal'] = 0;

    //3:PALETTEデータのロード
    $PALETTE = array();
    if ($BMP['colors'] < 16777216) {
      $PALETTE = unpack('V'.$BMP['colors'], fread($f1,$BMP['colors']*4));
    }

    //4: イメージデータのロード
    $IMG = fread($f1,$BMP['size_bitmap']);
    $VIDE = chr(0);

    $res = imagecreatetruecolor($BMP['width'],$BMP['height']);
    $P = 0;
    $Y = $BMP['height']-1;
    while ($Y >= 0) {
      $X=0;
      while ($X < $BMP['width']) {
        if ($BMP['bits_per_pixel'] == 24) $COLOR = unpack("V",substr($IMG,$P,3).$VIDE);
        elseif ($BMP['bits_per_pixel'] == 16) {
          $COLOR = unpack("n",substr($IMG,$P,2));
          $COLOR[1] = $PALETTE[$COLOR[1]+1];
        }
        elseif ($BMP['bits_per_pixel'] == 8) {
          $COLOR = unpack("n",$VIDE.substr($IMG,$P,1));
          $COLOR[1] = $PALETTE[$COLOR[1]+1];
        }
        elseif ($BMP['bits_per_pixel'] == 4) {
          $COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
          if (($P*2)%2 == 0) $COLOR[1] = ($COLOR[1] >> 4) ;
          else $COLOR[1] = ($COLOR[1] & 0x0F);
          $COLOR[1] = $PALETTE[$COLOR[1]+1];
        }
        elseif ($BMP['bits_per_pixel'] == 1) {
          $COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
          if (($P*8)%8 == 0) $COLOR[1] = $COLOR[1] >> 7;
          elseif (($P*8)%8 == 1) $COLOR[1] = ($COLOR[1] & 0x40) >> 6;
          elseif (($P*8)%8 == 2) $COLOR[1] = ($COLOR[1] & 0x20) >> 5;
          elseif (($P*8)%8 == 3) $COLOR[1] = ($COLOR[1] & 0x10) >> 4;
          elseif (($P*8)%8 == 4) $COLOR[1] = ($COLOR[1] & 0x8) >> 3;
          elseif (($P*8)%8 == 5) $COLOR[1] = ($COLOR[1] & 0x4) >> 2;
          elseif (($P*8)%8 == 6) $COLOR[1] = ($COLOR[1] & 0x2) >> 1;
          elseif (($P*8)%8 == 7) $COLOR[1] = ($COLOR[1] & 0x1);
          $COLOR[1] = $PALETTE[$COLOR[1]+1];
        }
        else return FALSE;
        imagesetpixel($res,$X,$Y,$COLOR[1]);
        $X++;
        $P += $BMP['bytes_per_pixel'];
      }
      $Y--;
      $P+=$BMP['decal'];
    }

    //作業終了
    fclose($f1);

    return $res;
  }
}

class ExcelWrapperException extends Exception {
}
