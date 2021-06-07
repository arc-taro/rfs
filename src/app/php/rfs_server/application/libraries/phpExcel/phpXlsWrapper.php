<?php

require_once dirname(__FILE__).'/Classes/PHPExcel/IOFactory.php';

    class phpExcelWrapper{
        protected $objOutput;
        protected $intShtCnt;

        protected static function assignProcess(&$objPHPExcel, &$objWorksheet,$params ){
//$fp = fopen("/home/koike/public_html/brd/test.log","a");
//fputs($fp,"assignProcess ***\n");
//$r= print_r($params,true);
//fputs($fp,"$r\n");
//fclose($fp);

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

//$fp = fopen("/home/koike/public_html/brd/test.log","a");
//fputs($fp,"assignProcess\n");
//$r= print_r($match,true);
//fputs($fp,"match1=$r\n");
//fclose($fp);
                    foreach( $match[1] as $tag ){
                        $value="";

// TODO 印章のセンタリングを計算によって求めたい（暫定で固定のオフセットにしている）
// UPD 20120316 hirano オフセットパラメータ追加のため、IF分修正 -->
                        if( preg_match('/^picture:\$([^:]*?):([^:]*?):([^:]*?):([^:]*?):([^:]*?)$/',$tag,$match2) || preg_match('/^picture:\$([^:]*?):([^:]*?):([^:]*?)$/',$tag,$match2)){
                        //if( preg_match('/^picture:\$([^:]*?):([^:]*?):([^:]*?)$/',$tag,$match2)){
// UPD 20120316 hirano オフセットパラメータ追加のため、IF分修正 <--
                            // 画像の埋め込み

//$fp = fopen("/home/koike/public_html/brd/test.log","a");
//fputs($fp,"$match2\n");
//$r= print_r($match2,true);
//fputs($fp,"$match2=$r\n");
//fclose($fp);
                            if( $params[$match2[1]]!="" && file_exists($params[$match2[1]])){
                                    $objDrawing = new PHPExcel_Worksheet_Drawing();


// UPD 20130409 hirano イメージの実サイズを半分にする -->

                                    $file1 = $params[$match2[1]];                                     // 元画像ファイル
                                    $file2 = dirname(__FILE__)."/results/".uniqid("imgs_").".jpg";     // 画像保存先

//$fp = fopen("/home/koike/public_html/brd/test.log","a");
//fputs($fp,"No2------------------------------------------>\n");
//$r= print_r($params,true);
//fputs($fp,"$params=$r\n");
//fputs($fp,"file1=$file1\n");
//fputs($fp,"file2=$file2\n");
//fputs($fp,"$r\n");
//fclose($fp);
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
//debug
//$fp = fopen("./test.log","a");
//fputs($fp,"phpXlsWrapper assign_process\n");
//fputs($fp,"$r\n");
//fputs($fp,$match2[2]."\n");
//fputs($fp,$match2[3]."\n");
//fputs($fp,"match[4]\n");
//fputs($fp,$match2[4]."\n");
//fputs($fp,$oWidth."\n");
//fputs($fp,$oHeight."\n");
//fputs($fp,$objDrawing->getWidth()."\n");
//fputs($fp,$objDrawing->getHeight()."\n");
//fclose($fp);
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
                                    // UPD 20160825 yamashita -->
                                    //$objDrawing->setresizeProportional=true;
                                    $objDrawing->setResizeProportional(false);
                                    // UPD 20160825 yamashita <--
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
                        $val=eval("return $value;");
                        $cell->setValue(preg_replace('/<<'.$tag_esc.'>>/',$val,$cell->getValue()));

                        // 改行があった場合行高さ変更
/*                        if (preg_match('/\n/',$val)) {
                            $objWorksheet->getRowDimension($cell->getrow())->setRowHeight();
                        }
*/
                    }
                 }
            }
        }

        public static function writeXls($template,$params){
//$fp = fopen("/home/koike/public_html/brd/test.log","a");
//fputs($fp,"writeXls------------------------------------------->\n");
//fclose($fp);
            $objReader = PHPExcel_IOFactory::createReader('Excel5');
            // load template xls
            $objPHPExcel = $objReader->load(dirname(__FILE__)."/templates/".$template);
            $objWorksheet = $objPHPExcel->getActiveSheet();

            phpExcelWrapper::assignProcess($objPHPExcel,$objWorksheet,$params);

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $file = dirname(__FILE__)."/results/".rand()."_".$template;
            $objWriter->save($file);

            return $file;
        }

        // ADD 20110912 nakamura 複数シートを保存する関数 -->
        // 使い方
        // CreateXls($template,$params) 1枚目のシート出力に使用 ($template:テンプレート名,$params:パラメータ)
        // AddXls($template,$params)    2枚目以降のシート出力に使用($template:テンプレート名,$params:パラメータ)
        // writeXlsSheets($outputname)  ダウンロードに使用($outputname:出力ファイル名)
        // CreateXls
        // 機能：1枚目のシートを出力
        //       テンプレートをそのままプライベート変数に出力しカウンタをリセット
        public function CreateXls($template,$params){

            log_message('debug', "CreateXls");

            $this->intShtCnt = 0;
            $objReader = PHPExcel_IOFactory::createReader('Excel5');
            // load template xls
            $this->objOutput = $objReader->load(dirname(__FILE__)."/templates/".$template);
            $objWorksheet = $this->objOutput->getActiveSheet();

            $this->intShtCnt++;
            $objWorksheet->setTitle("Sheet".$this->intShtCnt);

            // UPD 20110916 nakamura -->
            $this->assignProcess($this->objOutput,$objWorksheet,$params);
            //phpExcelWrapper::assignProcess($this->objOutput,$objWorksheet,$params);
            // UPD 20110916 nakamura <--

            return $this->intShtCnt;
        }

        // AddXls
        // 機能：2枚目のシートを出力
        //       テンプレートを1枚目に読み込んだオブジェクトにシート追加する
        public function AddXls($template,$params){
            $objReader = PHPExcel_IOFactory::createReader('Excel5');
            $objPHPExcel = $objReader->load(dirname(__FILE__)."/templates/".$template);
            $objWorksheet = $objPHPExcel->getActiveSheet();

            // UPD 20110916 nakamura -->
            $this->assignProcess($objPHPExcel,$objWorksheet,$params);
            //phpExcelWrapper::assignProcess($objPHPExcel,$objWorksheet,$params);
            // UPD 20110916 nakamura <--

            $this->intShtCnt++;
            $objWorksheet->setTitle("Sheet".$this->intShtCnt);

            // UPD 20110922 nakamura -->
            $this->objOutput->addExternalSheet($objWorksheet);
            //$this->objOutput->AddSheet($objWorksheet,$this->intShtCnt-1);
            // UPD 20110922 nakamura <--

            // 20160823 yama
            $objPHPExcel->disconnectWorksheets();
            unset($objPHPExcel);
            //

            return $this->intShtCnt;
        }

        // writeXlsSheets
        // 機能：ダウンロード
        public function writeXlsSheets($outputname, $output_mode){

            if($output_mode == 1){
                $objWriter = PHPExcel_IOFactory::createWriter($this->objOutput, 'Excel2007');
                $bf_file = str_replace("xls","xlsx",$outputname);
                $file = DIR_LIB."/phpExcel/results/".rand()."_".$bf_file.".xlsx";
            }else if($output_mode == 0){
                $objWriter = PHPExcel_IOFactory::createWriter($this->objOutput, 'Excel5');
                $file = DIR_LIB."/phpExcel/results/".rand()."_".$outputname.".xls";
            }else{
                $objWriter = PHPExcel_IOFactory::createWriter($this->objOutput, 'Excel5');
                $file = DIR_LIB."/phpExcel/results/".rand()."_".$outputname.".xls";
            }

            $file = dirname(__FILE__)."/results/".rand()."_".$outputname;
            $objWriter->save($file);

            // ADD 20110921 nakamura -->
            $this->objOutput->disconnectWorksheets();
            unset($this->objOutput);
            // ADD 20110921 nakamura <--

            return $file;
        }
        // ADD 20110912 nakamura 複数シートを保存する関数 <--

        // ADD 20110912 nakamura debug -->
        /*
        public static function writeXlstest2($template,$params,$template1,$params1){
            $objReader = PHPExcel_IOFactory::createReader('Excel5');
            // load template xls
            $objPHPExcel = $objReader->load(DIR_LIB."/phpExcel/templates/".$template);
            $objWorksheet = $objPHPExcel->getActiveSheet();
            $objWorksheet->setTitle("test1");

            phpExcelWrapper::assignProcess($objPHPExcel,$objWorksheet,$params);

            $objPHPExcel1 = $objReader->load(DIR_LIB."/phpExcel/templates/".$template1);
            $objWorksheet1 = $objPHPExcel1->getActiveSheet();
            phpExcelWrapper::assignProcess($objPHPExcel1,$objWorksheet1,$params1);
            $objWorksheet1->setTitle("test2");

            $objPHPExcel->AddSheet($objWorksheet1,1);


            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $file = DIR_LIB."/phpExcel/results/".rand()."_".$template;
            $objWriter->save($file);

            return $file;
        }
        */
        // ADD 20110912 nakamura debug <--

        public static function writePDF($template,$params){
            $objReader = PHPExcel_IOFactory::createReader('Excel5');
            // load template xls
            $objPHPExcel = $objReader->load(dirname(__FILE__)."/templates/".$template);
            $objWorksheet = $objPHPExcel->getActiveSheet();
            phpExcelWrapper::assignProcess($objPHPExcel,$objWorksheet,$params);

            $objWriter = new PHPExcel_Writer_PDF($objPHPExcel);
            $objWriter->setFont('arialunicid0-japanese');
            $file = dirname(__FILE__)."/results/".rand()."_".str_replace('.xls', '.pdf',$template);
            $objWriter->save($file);

            return $file;
        }


// ADD 20130501 hirano ファイル名からシートを取得しエクセルを作成する
        // 使い方
        // CreateXls($filenm) 1枚目のシート出力に使用 ($falenm:ファイル名)
        // AddXls($filenm)    2枚目以降のシート出力に使用($falenm:ファイル名)
        // writeXlsSheets($outputname)  ダウンロードに使用($outputname:出力ファイル名)
        // CreateXls
        // 機能：1枚目のシートを出力
        //
        public function CreateXls2($filenm){
            $this->intShtCnt = 0;
            // load template xls
            $objReader = PHPExcel_IOFactory::createReader('Excel5');
            $this->objOutput = $objReader->load($filenm);
            $objWorksheet = $this->objOutput->getActiveSheet();

            $this->intShtCnt++;
            $objWorksheet->setTitle("Sheet".$this->intShtCnt);

            return $this->intShtCnt;
        }

        // AddXls
        // 機能：2枚目のシートを出力
        //
        public function AddXls2($filenm){
            $objReader = PHPExcel_IOFactory::createReader('Excel5');
            $objPHPExcel = $objReader->load($filenm);
            $objWorksheet = $objPHPExcel->getActiveSheet();

            $this->intShtCnt++;
            $objWorksheet->setTitle("Sheet".$this->intShtCnt);

            $this->objOutput->addExternalSheet($objWorksheet);

            return $this->intShtCnt;
        }

    }
?>
