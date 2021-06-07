<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/ComExcel.php';

/**     出力ルール
 *
 *    出力Excelの添付ファイル名はtemp_出力Excel名.xlsとします。(ファイル名をセット）
 *
 *    Excelのセルの値と等しいフィールド名で値をセットする。
 *    リストに関しては行を、○○○○○○_row0とし、後ろのINDEXをインクリメントしてください。
 *    なお、行件数をrow_cntフィールドにセットしてください
 *    ※row_cntは共通の出力outExcelでリスト行セット（セットしないと、wrapperのevalしているところでException発生）
 *    ***************************************************************************************
 *        ※セルの変数について…INDEX以外の場所に数字を入れるとINDEX=10以降読み取らないバグが発生
 *                              なので、全て英字で変数を作成してください
 *    ***************************************************************************************
 *
 * @property CI_Loader $load
 * @property SchShisetsu $SchShisetsu
 * @property SchCheck $SchCheck
 */
class CreateOutputExcel extends ComExcel {
    // 設定的な項目
    /**
     * 1シート当たりの出力件数
     * @var int[]
     */
    public $page_size = [
        'schList' => 89
    ];

    /**
     * rfs データベースへのコネクション
     * @var CI_DB
     */
    protected $rfs;

    protected $out_simple = array();                        // 単一の出力データ
    protected $out_list = array();                        // リストの出力データ
    protected $get = array();
    protected $post;
    protected $excel_ver = self::OUTPUT_XLS;

    // コンストラクタ
    public function __construct() {
        parent::__construct();

        $this->rfs = $this->load->database('rfs', true);
    }

    /*********************************/
    /**********  検索リスト出力 ***********/
    /*********************************/

    public function out_schList($post) {
        log_message('debug', __METHOD__);

        $this->post = $post;

        if (isset($post['excel_ver'])) {
            $this->excel_ver = $post['excel_ver'];
        }

//        $this->post['srch'] = json_decode($this->post['srch']);

        // データの取得
        $rows = $this->get_schList();

        $this->edit_schList($rows);

        $this->sub_out('list', $this->page_size['schList']);
    }

    protected function get_schList() {
        $this->load->model('SchShisetsu');

//        $dogen_cd = $this->post['dogen_cd'];
//        $syucchoujo_cd = $this->post['syucchoujo_cd'];
//        log_message('info', "\n $dogen_cd \n");
//        log_message('info', "\n $syucchoujo_cd \n");

//$kbn = $this->post['srch']['shisetsu_kbn_dat_model'][0]['shisetsu_kbn'];
//log_message('info', "\n $kbn \n");

        $json_result = $this->SchShisetsu->get_srch_shisetsu($this->post);

        $result = json_decode($json_result[0]['sch_result_row'])->sch_result;

        self::add_row_numbers($result);

        return $result;
    }

    /***********************************/
    /**********  データ編集部 **********/
    /***********************************/
    /****************************************************/
    /*    単一のデータをout_simple配列に、                */
    /*     リストデータをout_list配列にセットしてください     */
    /****************************************************/

    /****************/
    /* 検索リスト編集 */
    /****************/
    protected function edit_schList(array $list_data) {
        log_message('debug', __METHOD__);

        /***************/
        /*    単一データ */
        /***************/
        $this->out_simple['created'] = date('n月j日');

        /***************/
        /*    リストデータ */
        /***************/
        $this->out_list = $list_data;
    }

    /***************************************/
    /**********  出力データ確定部 **********/
    /***************************************/
    /**
     *    sub_out
     *
     *         リストデータを出力する
     *
     *        引数：outname 出力ﾌｧｲﾙ名
     *                template名はtemp_ファイル名とします
     *              max MAX件数
     */
    protected function sub_out($outname, $max) {
        log_message('debug', __METHOD__);
        log_message('debug', "出力ヘッダ->".count($this->out_simple));
        log_message('debug', "出力リスト件数->".count($this->out_list));

        // MAX 件数を超える場合はシート追加
        if (count($this->out_list) <= $max) {

            // Excel出力配列
            $arr = array();

            // リスト外データ
            if ($this->out_simple) {
                foreach ($this->out_simple as $k => $v) {
                    $arr[$k] = $v;
                }
            }
            // リスト行データ
            for($i = 0; $i < count($this->out_list); $i++) {
                foreach ($this->out_list[$i] as $k => $v) {
                    $arr[$k.'_row'.$i] = $v;
                }
            }
            // Excel出力
            $this->outExcel($outname, $arr);
        }else{

            // シート枚数
            $sheet_cnt= ceil(count($this->out_list)/$max);

            log_message('debug', "シート枚数->$sheet_cnt");


            // シート枚数分ループ
            for ($s=1; $s<=$sheet_cnt; $s++) {

                // Excel出力配列
                $arr = array();

                // リスト外データ
                if ($this->out_simple) {
                    foreach ($this->out_simple as $k => $v) {
                        $arr[$k] = $v;
                    }
                }

                // 件数
                if ($s==$sheet_cnt) {
                    // 最後は端数の件数
                    $line_cnt= count($this->out_list)-$max*($s-1);
                }else{
                    // 途中の場合は最大行数になる
                    $line_cnt=$max;
                }

                // リスト行データ
                for($i = 0; $i < $line_cnt; $i++) {
                    foreach ($this->out_list[$max*($s-1)+$i] as $k => $v) {
                        $arr[$k.'_row'.$i] = $v;
                    }
                }

                // 最初
                if ($s==1) {

                    log_message('debug', "行数->$line_cnt");


                    // 最初はデータ込みでCREATE
                    $this->outExcel_create($outname, $arr, $line_cnt);
                } else{
                    // 2回目以降ADD
                    $this->outExcel_add($outname, $arr, $line_cnt);
                    // 最後に蓄えたタブを全て出力
                    if ($s==$sheet_cnt) {
                        $this->outExcel_last($outname);
                    }
                }
            }    // シートループ
        }    // シート単数or複数
    }


    /**********************************/
    /**********  Excel出力部 **********/
    /**********************************/
    /****************************************************************/
    /* outExcel                                                     */
    /*    Excel出力                                                    */
    /*        引数：    $outname    出力ファイル名                        */
    /*                $arr        出力データ（カラムに合わせた配列）    */
    /****************************************************************/
    protected function outExcel($outname, $arr) {

/*
        $r = print_r($arr, true);
        log_message('debug', "arr=$r");
*/

        /*** Wrapperのevalでエラーが発生しているので必ず渡す ***/
        $arr['row_cnt'] = count($this->out_list);

        // テンプレートファイルを指定する
        $tplt = "temp_$outname.xls";
        $arr['outname']=$outname;
        // debug------------------------------------
//        $r = print_r($arr,true);
//        log_message('debug', $r);
        // debug------------------------------------
        $this->render_excel($tplt, $arr);
    }

    /****************************************************************/
    /* outExcel_create                                                 */
    /*    複数シートExcel出力の最初                                    */
    /*        引数：    $outname    出力ファイル名                        */
    /*                $arr        出力データ（カラムに合わせた配列）    */
    /*                $cnt        リストの行数                        */
    /****************************************************************/
    protected function outExcel_create($outname, $arr, $cnt) {

        log_message('debug', "outExcel_create");

        /*** Wrapperのevalでエラーが発生しているので必ず渡す ***/
        $arr['row_cnt'] = $cnt;

        /*$r = print_r($arr,true);
log_message('debug', $r);
*/
        // テンプレートファイルを指定する
        $tplt = "temp_$outname.xls";
        $this->render_excel_create($tplt,$arr);
    }

    /****************************************************************/
    /* outExcel_add                                                 */
    /*    複数シートExcel出力の途中                                    */
    /*        引数：    $outname    出力ファイル名                        */
    /*                $arr        出力データ（カラムに合わせた配列）    */
    /*                $cnt        リストの行数                        */
    /****************************************************************/
    protected function outExcel_add($outname, $arr, $cnt) {

        log_message('debug', "outExcel_add");

        /*** Wrapperのevalでエラーが発生しているので必ず渡す ***/
        $arr['row_cnt'] = $cnt;

        /*$r = print_r($arr,true);
log_message('debug', $r);
*/
        // テンプレートファイルを指定する
        $tplt = "temp_$outname.xls";
        $this->render_excel_add($tplt,$arr);
    }

    /****************************************************************/
    /* outExcel_last                                                 */
    /*    複数シートExcel出力の最後                                    */
    /*        引数：    $outname    出力ファイル名                        */
    /*                $arr        出力データ（カラムに合わせた配列）    */
    /****************************************************************/
    protected function outExcel_last($outname) {
        /*log_message('debug', "outExcel_last");*/
        $this->render_excel_output($outname, $this->excel_ver);
    }

    // 補助

    /**
     * SQL の結果に行番号を追加する
     *
     * @param array $rows
     */
    protected static function add_row_numbers(array $rows) {
        $rownum = 1;
        foreach ($rows as $row) {
            $row->no = $rownum++;
        }
    }
}
