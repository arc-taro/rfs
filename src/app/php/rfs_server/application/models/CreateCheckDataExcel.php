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
class CreateCheckDataExcel extends CI_Model {
    const OUTPUT_XLS = 0;
    const OUTPUT_XLSX = 1;
    const PICTURE_STATUS_CHECK = 0;
    const PICTURE_STATUS_MEASURES = 1;
    const SHISETSU_KBN_BOSETSUSAKU = 4;
    const MODE_OLD = 0;         // 前回の点検
    const MODE_NEW = 1;         // 最新の点検
    const DIR_EXCEL = 'excels';
    const DIR_TMP = 'tmp';
    const DIR_BLANK = 'blank';
    /**
     * zip対象ファイル種別
     * ・基本情報のみ
     * ・ストック or 全データ
     */
    const BASE_INFO_ONLY = 0;
    const STOCK_OR_FULL_DATA = 1;
    // 点検管理番号未登録(施設情報の登録のみ)
    const NONE_CHK_MNG_NO = -1;
    // zipフォルダの保持期間(日)
    // 古いフォルダはzipをDLしたタイミングで削除する
    const ZIP_SAVE_DAYS = 3;
    /**
     * 写真画像のルートディレクトリ
     * @var string
     */
    public $picture_root;
    public $included_sheets = ['basic', 'photo', 'damage', 'figure'];
    /**
     * 防雪柵の最初の支柱のみ出力（開発用）
     * @var bool
     */
    public $bosetsu_first_shichu_only = false;
    protected $umu_str = [
        '有',
        '無'
    ];
    protected $motorway_str = [
        '自専道',
        '一般道'
    ];
    protected $lr_str = [
        'L',
        'R',
        'C',
        'LR'
    ];
    // TODO
    protected $check_policy_str = [
        'なし',
        '要スクリーニング',
        '要詳細調査'
    ];
    protected $check_status_str = [
        '未',
        '済'
    ];
    // TODO
    protected $necessity_measures_str = [
        '否',
        '要',
        '否'
    ];
    protected $sonsyou_str = [
        '',
        'a',
        'c',
        'e'
    ];
    protected $phase_nm = [
        '',
        'tk',
        'sc',
        'sy',
        '',
        'e'
    ];
    protected $sonsyou_naiyou_str = [
        '',
        'き裂',
        'ゆるみ・脱落',
        '破断',
        '腐食',
        '変形・欠損',
        'ひびわれ',
        'うき・剥離',
        '滞水',
        'その他'
    ];
    protected $sonota_no_str = [
        '',
        '①',
        '②',
        '③',
        '④',
        '⑤',
        '⑥',
        '⑦',
        '⑧',
        '⑨',
        '⑩',
        '⑪',
        '⑫',
        '⑬',
        '⑭',
        '⑮',
        '⑯',
        '⑰',
        '⑱',
        '⑲',
        '⑳'
    ];
    protected $shisetsu_judge_str;
    protected $judge_threshold = 2;
    /**
     * rfs_t_chk_huzokubutsu.surface の項目数
     * @var int
     */
    protected $surface_count = 3;
    // Excelファイル
    protected $file_path;
    protected $file_nm;
    protected $excel_info = array();
    protected $rfs;
    // hirano苦肉の策
    // 基本情報のみのExcel出力時に引数としてわたっていないものがあったため
    protected $tmp_chk_mng_no;
    protected $tmp_chk_mng_no_bs;
    protected $tmp_struct_idx_bs;
    protected $tmp_chk_times_bs;

    // 前回なのか今回なのかmodeがあるので保持する（引数に追加するのはきつい）
    protected $excel_output_mode;

    // haramoto 最悪だ
    public $outexcel_chk_mng_no = null;
    protected $xl; // MultisheetExcelWrapper
    protected $sheet_num = 1;

    public function __construct() {
        parent::__construct();
        $this->picture_root = $this->config->config['www_path'];
        $this->rfs = $this->load->database('rfs', true);
        $this->load->model('SchCheck');
    }

    // ==========================================
    //   呼び出し部
    // ==========================================
    /**
     * 点検票の Excel ブックを出力する
     *
     * @param int $sno
     * @param int $chk_mng_no
     * @param int $struct_idx
     * @param int $excel_ver
     */
    public function output_check_data($sno, $chk_mng_no, $struct_idx, $excel_ver) {
        log_message('debug', __METHOD__);
        try {
            // 最新のデータを出力する
            $this->excel_output_mode=self::MODE_NEW;
            $this->edit_check_data($sno, $chk_mng_no, $struct_idx);

            $this->xl->downloadResult($this->picture_root.$this->file_path, $this->file_nm, $this->get_format($excel_ver));

            echo 'result saved';

            // Excel管理情報の更新
            $this->add_excel_mng();

        } catch (RecordNotFoundException $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 点検票の Excel ブックを保存する
     *
     * @param int $sno
     * @param int $chk_mng_no
     * @param int $struct_idx
     * @param int $excel_ver
     */
    public function save_check_data($sno, $chk_mng_no, $struct_idx, $excel_ver) {
      log_message('debug', __METHOD__);

      // 保存時の対象は最新のデータ
      $this->excel_output_mode=self::MODE_NEW;

      // 基本情報登録時はchk_mng_noは-9
      if ($chk_mng_no!=-9) {
        // 保存するだけ（今までのロジック）
        $this->edit_check_data($sno, $chk_mng_no, $struct_idx);
        $this->xl->saveResult($this->picture_root.$this->file_path, $this->file_nm, $this->get_format($excel_ver));
        $this->xl->destroy();
        // Excel管理情報の更新
        $this->add_excel_mng();
      }
      if ($chk_mng_no==-9) {
        // 最新の親データのchk_mng_noを取得する
        // 防雪柵の判定をしたい
        $shisetsu_info=$this->getNewCheckMainParent($sno);
        if (!$shisetsu_info) {
          return;
        }
        // 基本情報から来ても防雪柵以外は処理しなきゃ。
        if ($shisetsu_info[0]['shisetsu_kbn']!=4) {
          // 保存するだけ（今までのロジック）
          $this->edit_check_data($sno, $shisetsu_info[0]['chk_mng_no'], $struct_idx);
          $this->xl->saveResult($this->picture_root.$this->file_path, $this->file_nm, $this->get_format($excel_ver));
          $this->xl->destroy();
          // Excel管理情報の更新
          $this->add_excel_mng();
        }
      }else{
        // 防雪柵の判定をしたい
        $shisetsu_info=$this->get_shisetsu_kbn($sno, $chk_mng_no);
      }
      if ($shisetsu_info[0]['shisetsu_kbn']==4) {
        // 防雪柵の場合は、同じ施設の様式１を全て更新しなければならない
        // 存在するExcelを全て作成し直す必要がある
        $bs_excels=$this->get_bs_excels($sno, $shisetsu_info[0]['chk_times']);
        foreach($bs_excels as $shichu) {
          // 直前で保存したExcelは触らなくてOK
          if ($shichu['chk_mng_no']==$chk_mng_no) {
            continue;
          }
          // Excel作成し直し
          $this->edit_check_data($sno, $shichu['chk_mng_no'], $shichu['struct_idx']);
          $this->xl->saveResult($this->picture_root.$this->file_path, $this->file_nm, $this->get_format($excel_ver));
          $this->xl->destroy();
          // Excel管理情報の更新
          $this->add_excel_mng();
        }
      }
    }

    // hirano 施設の情報を点検管理に絡めて取得
    public function get_shisetsu_kbn($sno, $chk_mng_no) {
      $sql = <<<EOF
select
    s.sno
  , s.shisetsu_kbn
  , c.chk_mng_no
  , c.chk_times
from
  rfs_t_chk_main c join rfs_m_shisetsu s
    on c.sno = s.sno
where
  c.chk_mng_no = $chk_mng_no
  and s.sno = $sno
EOF;
      $query = $this->rfs->query($sql);
      return $query->result_array();
    }

    // hirano 施設SNOからrfs_t_chk_mainの最新情報を取得
  // ただし、get_shisetsu_kbnの代わりに呼ばれるので、同じ戻りとする
    public function getNewCheckMainParent($sno) {
      $sql = <<<EOF
select
    s.sno
  , s.shisetsu_kbn
  , c.chk_mng_no
  , c.chk_times
from
  rfs_t_chk_main c join rfs_m_shisetsu s
    on c.sno = s.sno
where
  s.sno = $sno
  and c.struct_idx = 0
  and c.chk_times = (
    select
        max(chk_times)
    from
      rfs_t_chk_main
    where
      sno = $sno
  )
EOF;
      $query = $this->rfs->query($sql);
      return $query->result_array();
    }

    // hirano 同じ防雪柵の子データでエクセルがあるデータを全て抽出
    public function get_bs_excels($sno, $chk_times) {
      $sql = <<<EOF
select
    *
from
  rfs_t_chk_excel
where
  sno = $sno
  and chk_times = $chk_times
order by
  struct_idx
EOF;
      $query = $this->rfs->query($sql);
      return $query->result_array();
    }

    /**
     * 点検票の Excel ブックを保存する
     *
     * @param int $sno
     * @param int $chk_mng_no
     * @param int $struct_idx
     * @param int $excel_ver
     */
    public function output_check_data_pack($mode, $checked_data, $excel_ver) {
      log_message('debug', __METHOD__);
      ini_set("max_execution_time", 300);
      $this->load->library('zip');
      $rand = rand(2, 100000);

      // モードを保持⇒前回の点検を出力する際は前回の点検回数でデータを取得したりExcelを作成する必要がある
      // mode自体は、画面からPOSTされていて、コントローラーからの引数として渡ってくる
      // ※点検票から出力される際も、最新の点検票であることを明記した
      $this->excel_output_mode=$mode;

      // zip保存パス
      $zip_path_base = $this->picture_root .
                       self::DIR_EXCEL . '/' .
                       self::DIR_TMP . '/';
      $zip_path = $zip_path_base .
                  date('Ymd') . '/' .
                  $rand . '/';
      $zip_nm = date('YmdHis') . '.zip';

      // zip対象ファイルをzipフォルダへコピーする
      foreach ($checked_data["data_array"] as $chk) {
        // zip保存パスに出張所CDを追加
        $zip_path_tmp = $zip_path . $chk["syucchoujo_cd"] . '/';
        //log_message("info","\n $zip_path \n $zip_nm \n");
        // zipフォルダの作成
        if(!is_dir($zip_path_tmp)) {
          mkdir($zip_path_tmp, 0777, true);
        }
        // 施設ごとの空ファイルパスの設定
        $file_path_nm = $this->get_blank_file_path($chk);
        // 点検管理番号がない場合、点検回数がnullとなる
        // 施設登録のみで最新点検を指定した場合、基本情報のみが登録された点検票を出力する
        // 施設登録のみで前回点検を指定した場合、対象データはなし(=空の点検票が出力)
        // （点検対象施設の場合はt_chk_mainに登録されるため、点検回数がnullとなることはない）
        // 対象施設で点検票未入力の場合、部材以下データが登録されていないため履歴NOがnullとなる
        // （rfs_t_chk_huzokubutsuにデータが無いため）

        /**
         * 今年度対象施設で点検データあり(rfs_t_chk_mainに登録あり、rfs_t_chk_huzokubutsuに登録あり)
         * 今年度対象施設で点検データ未入力(rfs_t_chk_mainに登録あり、rfs_t_chk_huzokubutsuに登録なし)
         * 新しく施設情報のみを登録した場合(rfs_t_chk_mainに登録なし)
         **/
        $type = self::BASE_INFO_ONLY;

// 1.点検番号、点検回数がない＝点検管理テーブルにデータなし ⇒ 点検管理テーブルに無いのではなくExcelがそもそもない
// 2.点検番号あり、点検回数あり＝点検管理テーブルにデータあり ⇒ 点検管理テーブルの有無ではなくExcelが作成できる
//   履歴なし、最新選択
//   上記条件が満たされた場合は、基本情報のみを作成
        // 点検管理番号、点検回数が存在
        if(!is_null($chk['chk_mng_no']) && !is_null($chk['chk_times'])) {
          // 点検データがある場合はExcel作成対象
          if($chk['rireki_no']!=-1) {
            $type = self::STOCK_OR_FULL_DATA;
          }
          // 点検管理番号、点検回数があり、前回出力の場合Excel作成対象 => ストックが無い場合もある(下で吸収)
          if($mode == self::MODE_OLD) {
            $type = self::STOCK_OR_FULL_DATA;
          }
        }
        // 初期化 防雪柵子データ用変数
        $this->tmp_chk_mng_no_bs;
        $this->tmp_struct_idx_bs;
        $this->tmp_chk_times_bs;

        // 点検回数に応じた処理
        if($type == self::STOCK_OR_FULL_DATA) {
          // 点検回数が0かつ前回点検を指定した場合
          // 点検回数が1以上の場合
          if((($chk['chk_times'] == 0) && ($mode == self::MODE_OLD)) ||
            ($chk['chk_times'] >= 1)) {
            // 前回点検を指定、点検回数が1以上の場合、点検回数を減算
            if(($chk['chk_times'] >= 1) && ($mode == self::MODE_OLD)) {
              $chk['chk_times'] -= 1;
            }
            /*** これ以降点検回数は前回をクリックされた場合は、chk['chk_times']に点検回数-1、最新をクリックされた場合はそのままの点検回数が入っている。 ***/

            // Excel管理情報の取得(該当点検回数の情報)
            $path_nm_array = $this->get_excel_mng($chk);
            $struct_array = []; // sno、点検回数の全てを取得しているので、防雪柵の場合全て取得する、Excel管理テーブルにあるものはセットする
            // sno,syucchoujo_cd,chk_timesによるexcelデータの結果を全てループさせている
            // Excelデータにあるものはそのままzipフォルダに入れる。
            // 防雪柵時は親データは含まれない（excelデータには存在しない）
            foreach($path_nm_array as $path_nm) {
              $file_path_nm = $this->picture_root . $path_nm['file_path'] . $path_nm['file_nm'];
              // Excel管理情報に登録されているパスが存在するか確認
              if($file_path_nm != null) {
                if(!is_dir(pathinfo($file_path_nm)["dirname"])) {
                  $file_path_nm = $this->get_blank_file_path($chk);
                }
              } else {
                $file_path_nm = $this->get_blank_file_path($chk);
              }
              /**
               * 空ファイルパスを設定済み
               * ・点検回数が無い場合
               * ・点検回数が0かつ最新の点検を指定した場合
               * ・Excel管理情報が無い場合
               * ・Excel管理情報のパスが無い場合
               ***/
               // zip対象フォルダへコピー
              $file_nm = basename($file_path_nm);
              //log_message("info","\n $file_path_nm \n $zip_path_tmp$file_nm \n");
              log_message("info","\n $file_path_nm \n");
              copy($file_path_nm, $zip_path_tmp . $file_nm);
              chmod($zip_path_tmp . $file_nm, 0777);
              $struct_array[] = $path_nm['struct_idx']; // 後の比較対象とする(防雪柵対策)
            } // Excel管理情報ループ

            // 防雪柵以外でExcel管理情報があった場合は次の行へ
            // 防雪柵は防雪柵ループ内で行っている
            if ($chk['shisetsu_kbn']!=4 && count($path_nm_array)>0) {
              continue;
            }

            /* これ以降Excel管理情報に無いデータが対象 */

            // Excel管理情報にない支柱データの場合、Excel作成を作成する
            if($chk['shisetsu_kbn'] == self::SHISETSU_KBN_BOSETSUSAKU) {
              $shichu_array = $this->get_bssk_shisetsu_info($chk);
              foreach($shichu_array as $shichu) {
                // 親データは処理しない
                if($shichu['struct_idx'] == 0) {
                  continue;
                }
                // Excel管理情報にある支柱データは処理しない
                if(in_array($shichu['struct_idx'], $struct_array)) {
                  continue;
                }
                // 子データ情報をセット(該当点検回数時）
                if ($this->set_bs_child_dat($chk['sno'],$chk['chk_times'],$shichu['struct_idx'])==false) {
                  // 子データなし=点検対象ではない=空のExcel作成⇒点検管理では、点検管理番号？として登録される=検索されることはない
                  $this->edit_check_data($chk['sno'], self::NONE_CHK_MNG_NO, $shichu['struct_idx'], true);
                } else {
                  // Excelデータ作成（漏れている子データを作成）
                  $this->edit_check_data($chk['sno'], $this->tmp_chk_mng_no_bs, $this->tmp_struct_idx_bs);
                }
                // hirano 追加 正しいDirにもExcelを保存
                $this->xl->saveResult($this->picture_root.$this->file_path, $this->file_nm, $this->get_format($excel_ver));
                // Excel管理情報の更新（漏れている子データを登録）
                $this->add_excel_mng();
                // Excel保存
                $this->xl->saveResult($zip_path_tmp, $this->file_nm, $this->get_format(self::OUTPUT_XLS));
                $this->xl->destroy();
                chmod($zip_path_tmp . $this->file_nm, 0777);
              } // 防雪柵支柱インデックスループ
              // 初期化
              $this->tmp_chk_mng_no_bs;
              $this->tmp_struct_idx_bs;
              $this->tmp_chk_times_bs;
              // ここまで防雪柵
            } else {
              // 防雪柵以外の処理
              // Excel管理情報に無いので、引数の点検回数が点検管理にあるかチェックする
              $chkmain=$this->get_chk_main($chk['sno'],$chk['chk_times']);
              if (count($chkmain)==0) {
                // ない場合=点検登録もされてない
                  $this->edit_check_data($chk['sno'], self::NONE_CHK_MNG_NO, 0, true);
              } else {
                // 点検管理番号は前回と今回で変わるので、検索結果から使用
                // ある場合=点検データが無いだけ
                  $this->edit_check_data($chk['sno'],$chkmain[0]['chk_mng_no'], 0);
              }
              // hirano 追加 正しいDirにもExcelを保存
              $this->xl->saveResult($this->picture_root.$this->file_path, $this->file_nm, $this->get_format($excel_ver));
              // Excel管理情報の更新（漏れている子データを登録）
              $this->add_excel_mng();
              // Excel保存
              $this->xl->saveResult($zip_path_tmp, $this->file_nm, $this->get_format(self::OUTPUT_XLS));
              $this->xl->destroy();
              chmod($zip_path_tmp . $this->file_nm, 0777);
            }
          } // $type == self::STOCK_OR_FULL_DATA 終わり
        } else {
          // $type = self::BASE_INFO_ONLYスタート
          // 点検管理番号が無い場合Excelは作成しない = なくても作成する⇒ストックがない場合でも前回点検はヘッダーのみのExcel出力を行うため
//          if(!$chk['chk_mng_no']){
//            continue;
//          }
          // Excel管理情報の取得(空でも登録済みがある)
          // あくまでもsno,syucchoujo_cd,chk_timesで検索
          $path_nm_array = $this->get_excel_mng($chk);
          //$r = print_r($path_nm_array, true);
          //log_message('info', "path_nm_array--------------------------->\n $r \n");
          // Excel管理データありの場合zipにそのまま移す
          if ($path_nm_array) {
            foreach($path_nm_array as $path_nm) {
              $file_path_nm = $this->picture_root . $path_nm['file_path'] . $path_nm['file_nm'];
              // Excel管理情報に登録されているパスが存在するか確認
              if($file_path_nm != null) {
                if(!is_dir(pathinfo($file_path_nm)["dirname"])) {
                  $file_path_nm = $this->get_blank_file_path($chk);
                }
              } else {
                $file_path_nm = $this->get_blank_file_path($chk);
              }

              /**
               * 空ファイルパスを設定済み
               * ・点検回数が無い場合
               * ・点検回数が0かつ最新の点検を指定した場合
               * ・Excel管理情報が無い場合
               * ・Excel管理情報のパスが無い場合
               ***/
              // zip対象フォルダへコピー
              $file_nm = basename($file_path_nm);
              //log_message("info","\n $file_path_nm \n $zip_path_tmp$file_nm \n");
              copy($file_path_nm, $zip_path_tmp . $file_nm);
              chmod($zip_path_tmp . $file_nm, 0777);
              $struct_array[] = $path_nm['struct_idx'];
            } // Excel管理情報ループ
            // 防雪柵以外でExcel管理情報があった場合は次の行へ
            if ($chk['shisetsu_kbn']!=4) {
              continue;
            }
            // 上のループで入っていない子データは基本情報のみを作成する
            if($chk['shisetsu_kbn'] == self::SHISETSU_KBN_BOSETSUSAKU) {
              $shichu_array = $this->get_bssk_shisetsu_info($chk);
              foreach($shichu_array as $shichu) {
                // 親データは処理しない
                if($shichu['struct_idx'] == 0) {
                  continue;
                }
                // Excel管理情報にある支柱データは処理しない
                if(in_array($shichu['struct_idx'], $struct_array)) {
                  continue;
                }
                // 子データ情報をセット
                if ($this->set_bs_child_dat($chk['sno'],$chk['chk_times'],$shichu['struct_idx'])==false) {
                  continue;
                }
                // Excelデータ作成（漏れている子データを作成）
                //$this->edit_check_data($chk['sno'], $chk['chk_mng_no'], $shichu['struct_idx'], true);
                $this->edit_check_data($chk['sno'], self::NONE_CHK_MNG_NO, $shichu['struct_idx']);
                // hirano 追加 正しいDirにもExcelを保存
                $this->xl->saveResult($this->picture_root.$this->file_path, $this->file_nm, $this->get_format($excel_ver));
                // Excel管理情報の更新（漏れている子データを登録）
                $this->add_excel_mng();
                // Excel保存
                $this->xl->saveResult($zip_path_tmp, $this->file_nm, $this->get_format(self::OUTPUT_XLS));
                $this->xl->destroy();
                chmod($zip_path_tmp . $this->file_nm, 0777);
              } // 防雪柵支柱インデックスループ
              // 初期化
              $this->tmp_chk_mng_no_bs;
              $this->tmp_struct_idx_bs;
              $this->tmp_chk_times_bs;
              // ここまで防雪柵
            } else {
              // 防雪柵以外の処理
              // Excel管理情報に無いので、引数の点検回数が点検管理にあるかチェックする
              $chkmain=$this->get_chk_main($chk['sno'],$chk['chk_times']);
              if (count($chkmain)==0) {
                // ない場合=点検登録もされてない
                  $this->edit_check_data($chk['sno'], self::NONE_CHK_MNG_NO, $shichu['struct_idx'], true);
              } else {
                // ある場合=点検データが無いだけ-おそらくこちらしか通らない
                  $this->edit_check_data($chk['sno'],$chk['chk_mng_no'], 0);
              }
              // hirano 追加 正しいDirにもExcelを保存
              $this->xl->saveResult($this->picture_root.$this->file_path, $this->file_nm, $this->get_format($excel_ver));
              // Excel管理情報の更新（漏れている子データを登録）
              $this->add_excel_mng();
              // Excel保存
              $this->xl->saveResult($zip_path_tmp, $this->file_nm, $this->get_format(self::OUTPUT_XLS));
              $this->xl->destroy();
              chmod($zip_path_tmp . $this->file_nm, 0777);
            }
          }else{ // Excel管理情報にひとつも無い
            /******************************************************************************/
            /*** ここにくるのは、点検管理番号がないか、点検回数がない場合、また点検データが無い場合も ***/
            /******************************************************************************/
            // なぜ点検管理番号を使用したかわからないが、DB保存時に必要なのでtmp_chk_mng_noにセットするようにする
            // ※※※※※防雪柵の場合は、この点検管理番号は親なので子の管理番号が必要！！！
            $this->tmp_chk_mng_no= $chk['chk_mng_no'];
            // 基本情報のみ出力の処理判定のため
            $chk['chk_mng_no'] = self::NONE_CHK_MNG_NO;
            if($mode == self::MODE_OLD) {
              // zip対象フォルダへコピー
              $file_nm = basename($file_path_nm);
              //log_message("info","\n $file_path_nm \n $zip_path_tmp$file_nm \n");
              copy($file_path_nm, $zip_path_tmp . $file_nm);
              chmod($zip_path_tmp . $file_nm, 0777);
            } else {
              // 基本情報のみの点検票を作成する
              $file_nm_array = $this->edit_check_data_wrapper($zip_path_tmp, $chk);
              foreach($file_nm_array as $file_nm) {
                chmod($zip_path_tmp . $file_nm, 0777);
              }
            }
          }
        }
      }
      //log_message("info","\n $zip_path \n $zip_nm \n");
      // zip対象フォルダを圧縮
      // zip : zipファイル保存パス 対象フォルダ
      $command = "cd ". $zip_path ."; " . "zip -r ". $zip_path . $zip_nm ." .";
//        log_message("info","\n $command \n");
      exec($command);
      chmod($zip_path . $zip_nm, 0777);
      // 圧縮したファイルをダウンロードさせる
//        header('Pragma: public');
      header("Content-Type: application/octet-stream");
      header("Content-Disposition: attachment; filename=".$zip_nm);
      readfile($zip_path . $zip_nm);
      // 期限を過ぎたフォルダを削除
      $this->del_past_dir($zip_path_base);
    }

    /**
     * 特定様式除外（開発用）
     *
     * @param string $sheet basic, photo, damage, figure
     */
    public function exclude_sheet($sheet) {
        $this->included_sheets = array_diff($this->included_sheets, [$sheet]);
    }

    /**
     * 施設情報登録のみのExcel 出力の共通処理
     *
     * @param string $zip_path
     * @param array $chk
     */
    protected function edit_check_data_wrapper($zip_path, $chk) {
      log_message('debug', __METHOD__);
      $file_nm_array = [];

      // 初期化
      $this->tmp_chk_mng_no_bs;
      $this->tmp_struct_idx_bs;
      $this->tmp_chk_times_bs;
      if($chk['shisetsu_kbn'] == self::SHISETSU_KBN_BOSETSUSAKU) {
        // 防雪柵(引数のchkは親の情報）
        $shichu_array = $this->get_bssk_shisetsu_info($chk);
        // 支柱データ登録済みの場合、子データのみを出力する
        // 親データのみの場合は親データのみを出力する
        if(count($shichu_array) >= 2) {
          array_shift($shichu_array);
        }
        foreach($shichu_array as $shichu) {
          // 子データ情報をセット
          if ($this->set_bs_child_dat($chk['sno'],$chk['chk_times'],$shichu['struct_idx'])==false) {
            continue;
          }
          $this->edit_check_data($chk['sno'], self::NONE_CHK_MNG_NO, $shichu['struct_idx']);
          // hirano 追加 正しいDirにもExcelを保存
          $this->xl->saveResult($this->picture_root.$this->file_path, $this->file_nm, $this->get_format($excel_ver));
          // Excel保存
          $this->xl->saveResult($zip_path, $this->file_nm, $this->get_format(self::OUTPUT_XLS));
          $this->xl->destroy();
          // Excel管理情報の更新
          $this->add_excel_mng();
        }
        // 初期化
        $this->tmp_chk_mng_no_bs;
        $this->tmp_struct_idx_bs;
        $this->tmp_chk_times_bs;
      } else {
        // 防雪柵以外
        $this->edit_check_data($chk['sno'], self::NONE_CHK_MNG_NO, $chk['struct_idx']);
        // hirano 追加 正しいDirにもExcelを保存
        $this->xl->saveResult($this->picture_root.$this->file_path, $this->file_nm, $this->get_format($excel_ver));
        // Excel保存
        $this->xl->saveResult($zip_path, $this->file_nm, $this->get_format(self::OUTPUT_XLS));
        // hirano 追加 正しいExcelDirにもExcelのコピー
        $this->xl->destroy();
        // Excel管理情報の更新
        $this->add_excel_mng();
      }
      $file_nm_array[] = $this->file_nm;
      return $file_nm_array;
    }

    /**
     * Excel 出力の共通処理
     *
     * @param int $sno
     * @param int $chk_mng_no
     * @param int $struct_idx
     */
    protected function edit_check_data($sno, $chk_mng_no, $struct_idx, $is_base_info_only = false) {
        log_message('debug', __METHOD__);
        $excluded_sheets = array_diff(['basic', 'photo', 'damage', 'figure'], $this->included_sheets);
        if ($excluded_sheets) {
            log_message('info', '除外されている様式があります: ' . implode(', ', $excluded_sheets));
        }
        $this->load_check_data_masters();
        // 基本情報(施設データ)の取得
        $base_info = $this->get_base_info($sno, $chk_mng_no, $struct_idx);

        if ($base_info['shisetsu_kbn'] == self::SHISETSU_KBN_BOSETSUSAKU) {
            if($chk_mng_no != self::NONE_CHK_MNG_NO) {
                $parent_chk_mng_no = $this->get_bssk_parent_chk_mng_no($sno, $chk_mng_no);
                $base_info = $this->get_base_info($sno, $parent_chk_mng_no, 0); // 基本情報を親に書き換え
            }
            // 防雪柵の子データの中で点検票未記入のもの
            if($is_base_info_only) {
                $chk_mng_no = self::NONE_CHK_MNG_NO;
            }
        }
        if (!$base_info) {
            $this->error('施設データが見つかりません。');
        }
        // 様式テンプレートを読み込み
        $keishiki_cd = $base_info['shisetsu_keishiki_cd'] ?: 0;
        if($chk_mng_no != self::NONE_CHK_MNG_NO){
            $template = sprintf('temp_check_%d_%d.xls', $base_info['shisetsu_kbn'], $keishiki_cd);
            $this->xl = new MultisheetExcelWrapper($template);
            $check_data = $this->get_check_data(
                $base_info['shisetsu_kbn'], $base_info['chk_mng_no'], $base_info['rireki_no']);
            if (!$check_data) {
                $this->error('点検データが見つかりません。');
            }
        }else{
          $template = sprintf('temp_check_%d_%d_0.xls', $base_info['shisetsu_kbn'], $keishiki_cd);
          $this->xl = new MultisheetExcelWrapper($template);
          // 防雪柵の子データの場合、親の基本情報のみ取得する
          if($base_info['shisetsu_kbn'] == self::SHISETSU_KBN_BOSETSUSAKU && !$is_base_info_only) {
            $check_data = $this->get_check_data(
            $base_info['shisetsu_kbn'], $base_info['chk_mng_no'], $base_info['rireki_no']);
          }
          if (!$check_data) {
            // 部材以下データにはダミーを設定
            $check_data = array();
            for($i = 0; 5 > $i; $i++) {
                $check_data['check_buzai_judge' . $i] = '';
                $check_data['measures_buzai_judge' . $i] = '';
            }
          }
        }
        // if($chk_mng_no == self::NONE_CHK_MNG_NO) {
        //     $template = sprintf('temp_check_%d_%d_0.xls', $base_info['shisetsu_kbn'], $keishiki_cd);
        //     $this->xl = new MultisheetExcelWrapper($template);

        //     // 部材以下データにはダミーを設定
        //     $check_data = array();
        //     for($i = 0; 5 > $i; $i++) {
        //         $check_data['check_buzai_judge' . $i] = '';
        //         $check_data['measures_buzai_judge' . $i] = '';
        //     }
        // } else {
        //     $template = sprintf('temp_check_%d_%d.xls', $base_info['shisetsu_kbn'], $keishiki_cd);
        //     $this->xl = new MultisheetExcelWrapper($template);
        //     $check_data = $this->get_check_data(
        //         $base_info['shisetsu_kbn'], $base_info['chk_mng_no'], $base_info['rireki_no']);
        //     if (!$check_data) {
        //         $this->error('点検データが見つかりません。');
        //     }
        // }
        // // 防雪柵の子データの場合、親の基本情報のみ取得する
        // if($base_info['shisetsu_kbn'] == self::SHISETSU_KBN_BOSETSUSAKU && !$is_base_info_only) {
        //     $check_data = $this->get_check_data(
        //         $base_info['shisetsu_kbn'], $base_info['chk_mng_no'], $base_info['rireki_no']);
        //     if (!$check_data) {
        //         $this->error('点検データが見つかりません。');
        //     }
        // }
        // Excelファイルパスの設定
        // （ここではパスの設定のみ、保存はExcelWrapper内で行う）
        // 前回点検の場合でストックがないことがある（ストック以外は必ずある前提だが）
        $this->file_path = self::DIR_EXCEL .'/' .
                           $base_info['syucchoujo_cd'] . '/' .
                           $base_info['sno'] . '/' .
                           ($base_info['chk_times'] ? $base_info['chk_times'] : 0) . '/';

        $this->file_nm = $base_info['shisetsu_cd'] . '_' .
                         ($base_info['chk_times'] ? $base_info['chk_times'] : 0) . '_' .
                         $this->get_const('phase_nm', $base_info['phase']) . '_' .
                         $base_info['measures_shisetsu_judge'] . '_';

        // 防雪柵の時のみ支柱番号をファイル名に追加
        if ($base_info['shisetsu_kbn'] == self::SHISETSU_KBN_BOSETSUSAKU) {
            $this->file_nm .= $struct_idx . '_';
        }
        $this->file_nm .= date('Ymd') . '.xls';

        $file_path_nm = $this->picture_root . $this->file_path . $this->file_nm;

        //「$file_path_nm」で指定されたディレクトリが存在するか確認
        if(!is_dir(pathinfo($file_path_nm)["dirname"])) {
            mkdir(pathinfo($file_path_nm)["dirname"], 0755,true);
        }
        // Excel管理テーブル検索パラメータ
        $this->excel_info['sno'] = $base_info['sno'];
        $this->excel_info['chk_mng_no'] = $chk_mng_no;
        $this->excel_info['syucchoujo_cd'] = $base_info['syucchoujo_cd'];
        $this->excel_info['chk_times'] = ($base_info['chk_times'] ? $base_info['chk_times'] : 0);
        $this->excel_info['struct_idx'] = $struct_idx;
        if ($base_info['shisetsu_kbn'] == self::SHISETSU_KBN_BOSETSUSAKU) {
            // 防雪柵
            // 各様式の出力
            // 子データの基本情報、部材以下データの取得
            $base_info_child = $this->get_base_info($sno, $chk_mng_no, $struct_idx);

//log_message("info","------base_info_child------>".print_r($base_info_child,true));

            $shichu = $this->get_bosetsusaku_shichu($sno, $struct_idx);
            if($chk_mng_no == self::NONE_CHK_MNG_NO) {
                $shichu_check_data = array();
            } else {
                $shichu_check_data = $this->get_bosetsusaku_check_data($base_info_child);
            }
            // 点検票記録様式シート（防雪柵）
            if (in_array('basic', $this->included_sheets)) {
                $this->edit_bousetsusaku_basic_sheet($base_info, $base_info_child, $check_data, $shichu_check_data);
            }
            // 支柱の部材以下データの取得
            if($chk_mng_no == self::NONE_CHK_MNG_NO) {
                $shichu_check_data = array();
            } else {
                $shichu_check_data = $this->get_bosetsusaku_check_data($base_info_child);
            }
            if (in_array('photo', $this->included_sheets)) {
                $this->sheet_num = 1;
                $this->edit_photo_sheet($shichu + $base_info_child, $shichu_check_data);
            }
            if (in_array('damage', $this->included_sheets)) {
                $this->sheet_num = 1;
                $this->edit_damage_sheet($shichu + $base_info_child, $shichu_check_data);
            }
            if (in_array('figure', $this->included_sheets)) {
                $this->sheet_num = 1;
//                log_message('debug', 'struct_idx='.$shichu['struct_idx']);
                $this->edit_bosetsusaku_figure_sheet($shichu + $base_info_child);
            }
        } else {
            // 防雪柵以外
            // 各様式の出力
            if (in_array('basic', $this->included_sheets)) {
                $this->edit_basic_sheet($base_info, $check_data);
            }
            if (in_array('photo', $this->included_sheets)) {
                $this->sheet_num = 1;
                $this->edit_photo_sheet($base_info, $check_data);
            }
            if (in_array('damage', $this->included_sheets)) {
                $this->sheet_num = 1;
                $this->edit_damage_sheet($base_info, $check_data);
            }
            if (in_array('figure', $this->included_sheets)) {
                $this->sheet_num = 1;
                $this->edit_figure_sheet($base_info);
            }
        }
    }

    /**
     * エラーを出力して終了
     *
     * @param string $message
     */
    protected function error($message) {
        $bt = debug_backtrace();
        log_message('error', sprintf('%s (%s:%d)', $message, $bt[0]['file'], $bt[0]['line']));

        echo $message;
        exit;
    }

    // ==========================================
    //   データ取得部
    // ==========================================

    protected function load_check_data_masters() {
        $query = $this->rfs->get('rfs_m_shisetsu_judge');
        foreach ($query->result() as $row) {
            $this->shisetsu_judge_str[$row->shisetsu_judge] = $row->shisetsu_judge_nm;
        }
    }

    protected function get_bssk_parent_chk_mng_no($sno, $chk_mng_no) {
        $params = ['sno' => $sno];
        if($chk_mng_no > 1000000) {
            $query = $this->SchCheck->get_chkmngno_bssk_parent($params);
        } else {
            $query = $this->SchCheck->get_chkmngno_bssk_parent_stock($params);
        }
        return $query[0]['chk_mng_no'];
    }

    // 前回点検のExcelを作成する場合（主にストック点検）点検回数は最新ではなく一つ前となる
    protected function get_base_info($sno, $chk_mng_no, $struct_idx) {
        $params = [];

//log_message("info","sno:".$sno);
//log_message("info","chk_mng_no:".$chk_mng_no);
//log_message("info","struct_idx:".$struct_idx);

        // 前回点検の場合点検回数を減算
        if ($this->excel_output_mode == self::MODE_OLD) {
          $chk_times="max(chk_times) - 1 ";
        } else {
          $chk_times="max(chk_times) ";
        }

// ---> そのインデックスの点検回数ではなく、この施設の最大の点検回数で無いとNG
// ---> インデックスすら関係なくその施設の最大の点検回数で無いとNG
  $sql = <<<EOF
WITH chk_main AS (SELECT
    *
FROM
  rfs_t_chk_main
WHERE
  chk_times = (
    SELECT
        $chk_times
    FROM
      rfs_t_chk_main
    WHERE
      sno = $sno
      --AND struct_idx = $struct_idx
  )
  AND sno = $sno
  AND struct_idx = $struct_idx)
EOF;
// インデックスすら関係なくその施設の最大の点検回数で無いとNG <---
// そのインデックスの点検回数ではなく、この施設の最大の点検回数で無いとNG <--

        if($chk_mng_no == self::NONE_CHK_MNG_NO) {
            // デフォルト処理
            // 施設登録のみで点検管理番号(chk_mng_no)が無い場合
            $sql .= <<<EOF
                select
                    s.sno,
                    s.shisetsu_ver,
                    s.shisetsu_kbn,
                    s.shisetsu_keishiki_cd,
                    c.chk_mng_no,
                    c.struct_idx,
                    c.chk_times,
                    skbn.shisetsu_kbn_nm,
                    skeishiki.shisetsu_keishiki_nm,
                    s.lat,
                    s.lon,
                    s.shisetsu_cd,
                    s.syucchoujo_cd,
                    rosen.rosen_no,
                    rosen.rosen_nm,
                    s.shityouson,
                    s.azaban,
                    s.sp,
                    s.lr,
                    dogen.dogen_mei,
                    syucchoujo.syucchoujo_mei,
                    s.substitute_road,
                    s.emergency_road,
                    s.motorway,
                    s.senyou,
                    s.secchi,
                    s.fukuin,
                    zenkei_picture.path zenkei_picture

                from rfs_m_shisetsu s
                    left join chk_main c
                        on c.sno = s.sno
                    left join rfs_m_shisetsu_kbn skbn
                        on skbn.shisetsu_kbn = s.shisetsu_kbn
                    left join rfs_m_shisetsu_keishiki skeishiki
                        on skeishiki.shisetsu_kbn = s.shisetsu_kbn
                        and skeishiki.shisetsu_keishiki_cd = s.shisetsu_keishiki_cd
                    left join rfs_m_rosen rosen
                        on rosen.rosen_cd = s.rosen_cd
                    left join rfs_m_dogen dogen
                        on dogen.dogen_cd = s.dogen_cd
                    left join rfs_m_syucchoujo syucchoujo
                        on syucchoujo.syucchoujo_cd = s.syucchoujo_cd
                    left join (select * from rfs_t_zenkei_picture where use_flg=1) zenkei_picture
                        on zenkei_picture.sno = s.sno
                where s.sno = $sno
EOF;

/*             $sql .= '
                 and c.chk_times = (
                     select max(chk_times)
                     from rfs_t_chk_main
                     where sno = s.sno
                 )
                ';
*/
            $params[] = $sno;

        } else {
            // 点検管理番号(chk_mng_no)がある場合
            $sql .= <<<EOF
                select
                    s.sno,
                    s.shisetsu_ver,
                    s.shisetsu_kbn,
                    s.shisetsu_keishiki_cd,
                    c.chk_mng_no,
                    c.struct_idx,
                    c.chk_times,
                    case
                      when ch.rireki_no is null
                      then 0
                      else ch.rireki_no
                      end as rireki_no,
                    case
                      when ch.phase is null
                      then 1
                      else ch.phase
                      end as phase,
                    skbn.shisetsu_kbn_nm,
                    skeishiki.shisetsu_keishiki_nm,
                    s.lat,
                    s.lon,
                    s.shisetsu_cd,
                    s.syucchoujo_cd,
                    rosen.rosen_no,
                    rosen.rosen_nm,
                    s.shityouson,
                    s.azaban,
                    s.sp,
                    s.lr,
                    dogen.dogen_mei,
                    syucchoujo.syucchoujo_mei,
                    ch.chk_dt,
                    ch.investigate_dt,
                    ch.chk_company,
                    ch.chk_person,
                    ch.investigate_company,
                    ch.investigate_person,
                    s.substitute_road,
                    s.emergency_road,
                    s.motorway,
                    s.senyou,
                    ch.check_shisetsu_judge,
                    ch.syoken,
                    ch.measures_shisetsu_judge,
                    s.secchi,
                    s.fukuin,

                    ch.part_notable_chk,
                    ch.reason_notable_chk,
                    ch.special_report,
                    ch.surface,

                    zenkei_picture.path zenkei_picture

                from rfs_m_shisetsu s
                    left join chk_main c
                        on c.sno = s.sno
                    left join rfs_m_shisetsu_kbn skbn
                        on skbn.shisetsu_kbn = s.shisetsu_kbn
                    left join rfs_m_shisetsu_keishiki skeishiki
                        on skeishiki.shisetsu_kbn = s.shisetsu_kbn
                        and skeishiki.shisetsu_keishiki_cd = s.shisetsu_keishiki_cd
                    left join rfs_m_rosen rosen
                        on rosen.rosen_cd = s.rosen_cd
                    left join rfs_m_dogen dogen
                        on dogen.dogen_cd = s.dogen_cd
                    left join rfs_m_syucchoujo syucchoujo
                        on syucchoujo.syucchoujo_cd = s.syucchoujo_cd
                    left join (
                            select distinct on (chk_mng_no) *
                            from rfs_t_chk_huzokubutsu ch1
                            where not exists (
                                select 1
                                from rfs_t_chk_huzokubutsu ch2
                                where ch1.chk_mng_no = ch2.chk_mng_no
                                    and ch1.rireki_no < ch2.rireki_no
                            )
                        ) ch
                        on ch.chk_mng_no = c.chk_mng_no
                    left join (select * from rfs_t_zenkei_picture where use_flg=1) zenkei_picture
                        on zenkei_picture.sno = s.sno
                        and zenkei_picture.struct_idx = c.struct_idx
                where s.sno = $sno
EOF;
            $params[] = $sno;

            if ($chk_mng_no) {
              // -9は基本情報から飛んできたとき
              // -9の場合は無い場合と同じ振る舞いを。
              // 条件式あまり触りたくなかったのでここに書く
              if ($chk_mng_no==-9) {
                  $sql .= <<<EOF
--                    and c.chk_times = (
--                        select max(chk_times)
--                        from rfs_t_chk_main
--                        where sno = s.sno
--                            and struct_idx = ?
--                    )
                    and ch.rireki_no = (
                        select max(rireki_no)
                        from rfs_t_chk_huzokubutsu
                        where chk_mng_no = c.chk_mng_no
                    )
                    and c.chk_mng_no = (
                        select max(chk_mng_no)
                        from rfs_t_chk_main
                        where sno = s.sno
                    )
EOF;
                  $params[] = $struct_idx;
              }else{

/*                 $sql .= '
                   and c.chk_times = (
                     select max(chk_times)
                     from rfs_t_chk_main
                     where chk_mng_no = c.chk_mng_no
                   )
                   and c.chk_mng_no = ?
                 ';
*/
                 $params[] = $chk_mng_no;
              }
            } else {
                $sql .= <<<EOF
--                    and c.chk_times = (
--                        select max(chk_times)
--                        from rfs_t_chk_main
--                        where sno = s.sno
--                            and struct_idx = ?
--                    )
                    and ch.rireki_no = (
                        select max(rireki_no)
                        from rfs_t_chk_huzokubutsu
                        where chk_mng_no = c.chk_mng_no
                    )
                    and c.chk_mng_no = (
                        select max(chk_mng_no)
                        from rfs_t_chk_main
                        where sno = s.sno
                    )
EOF;
                $params[] = $struct_idx;
            }
            $sql .= <<<EOF
                order by c.struct_idx
EOF;
        }

        /* @var $query CI_DB_result */
//log_message("info","SQL---->".$sql);
//        $query = $this->rfs->query($sql, $params);
        $query = $this->rfs->query($sql);
        $bi = $query->row_array();
        if (!$bi) {
            throw new RecordNotFoundException('施設データが見つかりません。');
        }

        // データ加工処理
        if($chk_mng_no == self::NONE_CHK_MNG_NO) {
          // デフォルト処理(要素の追加)
          $bi['chk_mng_no'] = null;
//            $bi['chk_times'] = null;
          $bi['phase'] = null;
          $bi['measures_shisetsu_judge'] = null;
          $bi['surface'] = null;
          $bi += $this->date2ymd(time(), 'output_dt');

          $bi['substitute_road_str'] = $this->get_const('umu_str', $bi['substitute_road']);
          $bi['motorway_str'] = $this->get_const('motorway_str', $bi['motorway']);
          $bi['lr_str'] = $this->get_const('lr_str', $bi['lr']);

          $bi += $this->deg2dms($bi['lat'], 'lat');
          $bi += $this->deg2dms($bi['lon'], 'lon');
          if (!$bi['secchi']) {
            $bi['secchi'] = '不明';
          }
          if ($bi['zenkei_picture'] && file_exists($this->picture_root . $bi['zenkei_picture'])) {
            $bi['zenkei_picture'] = $this->picture_root . $bi['zenkei_picture'];
          }
          if ($bi['shisetsu_kbn'] == self::SHISETSU_KBN_BOSETSUSAKU) {
            $bi['struct_idx_num'] = $this->get_struct_idx_num($bi['sno']);
          }
        } else {
          $bi += $this->date2ymd(time(), 'output_dt');

          $bi['substitute_road_str'] = $this->get_const('umu_str', $bi['substitute_road']);
          $bi['motorway_str'] = $this->get_const('motorway_str', $bi['motorway']);
          $bi['lr_str'] = $this->get_const('lr_str', $bi['lr']);

          $bi += $this->deg2dms($bi['lat'], 'lat');
          $bi += $this->deg2dms($bi['lon'], 'lon');

          $bi += $this->date2ymd($bi['chk_dt'], 'chk_dt');
          $bi += $this->date2ymd($bi['investigate_dt'], 'investigate_dt');

          $bi['check_shisetsu_judge_str']="";
          if($bi['check_shisetsu_judge']){
            $bi['check_shisetsu_judge_str'] = $this->shisetsu_judge_str[$bi['check_shisetsu_judge']];
          }
          $bi['measures_shisetsu_judge_str']="";
          if($bi['check_shisetsu_judge']){
            // 20191107 hirano 措置後の方が良くなっている場合のみ再判定を表示する
            if ($bi['check_shisetsu_judge'] > $bi['measures_shisetsu_judge']) {
              $bi['measures_shisetsu_judge_str'] = $this->shisetsu_judge_str[$bi['measures_shisetsu_judge']];
            }
          }
          if (!$bi['secchi']) {
              $bi['secchi'] = '不明';
          }
          if ($bi['zenkei_picture'] && file_exists($this->picture_root . $bi['zenkei_picture'])) {
              $bi['zenkei_picture'] = $this->picture_root . $bi['zenkei_picture'];
          }
          if ($bi['shisetsu_kbn'] == self::SHISETSU_KBN_BOSETSUSAKU) {
              $bi['struct_idx_num'] = $this->get_struct_idx_num($bi['sno']);
          }
        }
        return $bi;
    }

    protected function get_check_data($shisetsu_kbn, $chk_mng_no, $rireki_no) {
        $chk = $this->SchCheck->get_chkdata_exist_only([
            'shisetsu_kbn' => $shisetsu_kbn,
            'chk_mng_no' => $chk_mng_no,
            'rireki_no' => $rireki_no
        ]);
        $buzai_json = json_decode($chk[0]['buzai_row']);
        return $buzai_json->buzai;
    }

    /**
     * 防雪柵支柱の点検データを得る
     *
     * @param int $sno
     * @param int $struct_idx
     * @param int $rireki_no
     * @return array
     */
//    protected function get_bosetsusaku_check_data($sno, $struct_idx, $rireki_no) {
    protected function get_bosetsusaku_check_data($base_info) {
        $sql = '
            select
                c.*,
                s.shisetsu_kbn
            from rfs_t_chk_main c
                left join rfs_m_shisetsu s
                    on s.sno = c.sno
            where c.sno = ?
                and c.struct_idx = ?
                and c.chk_times = ?
--            order by c.chk_times desc limit 1
        ';
        $params = [
            $base_info['sno'],
            $base_info['struct_idx'],
            $base_info['chk_times']
        ];
        $check_main = $this->rfs->query($sql, $params)->row();

        return $this->get_check_data($check_main->shisetsu_kbn, $check_main->chk_mng_no, $base_info['rireki_no']);
    }

    protected function get_check_parts($shisetsu_kbn, $chk_mng_no, $rireki_no) {
        ini_set('xdebug.var_display_max_depth', 9);

        // 部材を得る
        $parts = $this->rfs
            ->where('shisetsu_kbn', $shisetsu_kbn)
            ->order_by('buzai_cd')
            ->get('rfs_m_buzai')
            ->result();

        foreach ($parts as $buzai) { // 部材
            $buzai_cd = $buzai->buzai_cd;

            // 部材ごとの点検データを得る
            $b = $buzai;
            $c = new SafeObject($this->rfs
                ->where('chk_mng_no', $chk_mng_no)
                ->where('rireki_no', $rireki_no)
                ->where('buzai_cd', $buzai_cd)
                ->get('rfs_t_chk_buzai')
                ->row());
            $b->check_buzai_judge = $c->check_buzai_judge ?: 0;
            $b->measures_buzai_judge = $c->measures_buzai_judge ?: 0;
            $b->hantei1 = $c->hantei1;
            $b->hantei2 = $c->hantei2;
            $b->hantei3 = $c->hantei3;
            $b->hantei4 = $c->hantei4;

            // 部材詳細を得る
            $buzai->buzai_details = $this->rfs
                ->where('shisetsu_kbn', $shisetsu_kbn)
                ->where('buzai_cd', $buzai_cd)
                ->order_by('buzai_detail_cd')
                ->get('rfs_m_buzai_detail')
                ->result();

            foreach ($buzai->buzai_details as $buzai_detail) { // 部材詳細
                $buzai_detail_cd = $buzai_detail->buzai_detail_cd;

                // 点検箇所を得る
                $buzai_detail->tenken_kasyos = $this->rfs
                    ->where('shisetsu_kbn', $shisetsu_kbn)
                    ->where('buzai_cd', $buzai_cd)
                    ->where('buzai_detail_cd', $buzai_detail_cd)
                    ->order_by('tenken_kasyo_cd')
                    ->get('rfs_m_tenken_kasyo')
                    ->result();

                foreach ($buzai_detail->tenken_kasyos as $tenken_kasyo) { // 点検箇所
                    $tenken_kasyo_cd = $tenken_kasyo->tenken_kasyo_cd;

                    // 対象有無の集計用
                    $tenken_kasyo->taisyou_umu = false;

                    // 点検箇所ごとの損傷内容を得る
                    $tenken_kasyo->sonsyou_naiyous = $this->rfs
                        ->select('cs.sonsyou_naiyou_cd, s.sonsyou_naiyou_nm')
                        ->from('rfs_m_chk_sonsyou cs')
                        ->join('rfs_m_sonsyou_naiyou s', 's.sonsyou_naiyou_cd = cs.sonsyou_naiyou_cd')
                        ->where('shisetsu_kbn', $shisetsu_kbn)
                        ->where('buzai_cd', $buzai_cd)
                        ->where('buzai_detail_cd', $buzai_detail_cd)
                        ->where('tenken_kasyo_cd', $tenken_kasyo_cd)
                        ->order_by('sonsyou_naiyou_cd')
                        ->get()
                        ->result();

                    foreach ($tenken_kasyo->sonsyou_naiyous as $sonsyou_naiyou) { // 損傷内容
                        $sonsyou_naiyou_cd = $sonsyou_naiyou->sonsyou_naiyou_cd;

                        // 点検箇所/損傷内容ごとの点検データを得る
                        $s = $sonsyou_naiyou;
                        $c = new SafeObject($this->rfs
                            ->where('chk_mng_no', $chk_mng_no)
                            ->where('rireki_no', $rireki_no)
                            ->where('buzai_cd', $buzai_cd)
                            ->where('buzai_detail_cd', $buzai_detail_cd)
                            ->where('tenken_kasyo_cd', $tenken_kasyo_cd)
                            ->where('sonsyou_naiyou_cd', $sonsyou_naiyou_cd)
                            ->get('rfs_t_chk_tenken_kasyo')
                            ->row());

                        $s->chk_mng_no = $c->chk_mng_no ?: $chk_mng_no;
                        $s->rireki_no = $c->rireki_no ?: $rireki_no;

                        $s->taisyou_umu = $c->taisyou_umu === null || $c->taisyou_umu == 1;
                        $s->check_status = $c->check_status;

                        $judge_def = function($val) {
                            if ($val === null || $val == -1) {
                                return 1;
                            } else {
                                return $val;
                            }
                        };
                        $s->check_judge = $judge_def($c->check_judge);
                        $s->measures_judge = $judge_def($c->measures_judge);

                        $screening_def = function($val) {
                            if ($val === null || $val == -1) {
                                return 0;
                            } else {
                                return $val;
                            }
                        };
                        $s->screening = $screening_def($c->screening);
                        $s->screening_flg = $c->screening == 1;
                        $s->screening_taisyou = $screening_def($c->screening_taisyou);

                        $s->check_policy = $c->check_policy;
                        $s->measures_policy = $c->measures_policy;
                        $s->measures_dt = $c->measures_dt;
                        $s->check_bikou = $c->check_bikou;
                        $s->measures_bikou = $c->measures_bikou;

                        if ($s->taisyou_umu) {
                            $tenken_kasyo->taisyou_umu = true;
                        }
                    }
                }
            }
        }

        return $parts;
    }

    protected function get_check_picture($chk_mng_no, $buzai_cd, $buzai_detail_cd, $tenken_kasyo_cd, $status) {
        $sql = '
            select cp.*
            from rfs_t_chk_picture cp
            where chk_mng_no = ?
                and buzai_cd = ?
                and buzai_detail_cd = ?
                and tenken_kasyo_cd = ?
                and status = ?
            order by picture_cd desc
            limit 1
        ';
        $params = [
            $chk_mng_no,
            $buzai_cd,
            $buzai_detail_cd,
            $tenken_kasyo_cd,
            $status
        ];

        $query = $this->rfs->query($sql, $params);

        return $query->row_array();
    }

    protected function get_struct_idx_num($sno) {
        $result = $this->SchCheck->get_struct_idx_num(['sno' => $sno]);

        return $result[0]['struct_idx_num'];
    }

    protected function get_bosetsusaku_shichu($sno, $struct_idx) {
        $sql = '
            select *
            from rfs_m_bousetsusaku_shichu
            where sno = ?
                and struct_idx = ?
            order by struct_idx
        ';

        return $this->rfs->query($sql, [$sno, $struct_idx])->result_array()[0];
    }

    // Excel管理テーブルの対象レコードを更新
    protected function add_excel_mng() {

        $sno = $this->excel_info['sno'];
    $chk_mng_no = $this->excel_info['chk_mng_no'];
        $syucchoujo_cd = $this->excel_info['syucchoujo_cd'];
        $chk_times = $this->excel_info['chk_times'];
        $struct_idx = $this->excel_info['struct_idx'];

        // デフォルト処理
        if($chk_mng_no == self::NONE_CHK_MNG_NO) {
        // hirano修正
            // -1の場合は基本情報のみのデータなので正しい点検管理番号を振る = -1の場合はExcelのみ作成した場合もある
          // 防雪柵子データの場合
          if ($this->tmp_chk_mng_no_bs) {
            $chk_mng_no=$this->tmp_chk_mng_no_bs;
            $struct_idx = $this->tmp_struct_idx_bs;
            $chk_times = $this->tmp_chk_times_bs;
          }else if ($this->tmp_chk_mng_no) {
            $chk_mng_no=$this->tmp_chk_mng_no;
          }else{
            $chk_mng_no=0; // 関係のない0をセットする⇒ぶら下がる点検表がないための処置
          }
        }
        if($this->outexcel_chk_mng_no){
          $chk_mng_no=$this->outexcel_chk_mng_no;
          log_message("debug","****************************************");
        }

        $sql = <<<EOF
        select
          count(id)
        from
          rfs_t_chk_excel tce
        where
          (
            tce.sno = $sno
            and tce.chk_mng_no = $chk_mng_no
            and tce.syucchoujo_cd = $syucchoujo_cd
            and tce.chk_times = $chk_times
            and tce.file_path = '$this->file_path'
            and tce.struct_idx = $struct_idx
          )
EOF;

        $query = $this->rfs->query($sql);
        $result = $query->row_array();
        $create_dt = date('Y-m-d H:i:s');

        // レコードがある場合
        if($result['count'] != 0) {

            $sql = <<<EOF
            update rfs_t_chk_excel tce
            set
              file_nm = '$this->file_nm'
              , create_dt = '$create_dt'
            where
              sno = $sno
              and chk_mng_no = $chk_mng_no
              and syucchoujo_cd = $syucchoujo_cd
              and chk_times = $chk_times
              and file_path = '$this->file_path'
              and struct_idx = $struct_idx
EOF;
        } else {
            $sql = <<<EOF
            insert into rfs_t_chk_excel
              (
                id
                , sno
                , chk_mng_no
                , syucchoujo_cd
                , chk_times
                , struct_idx
                , file_path
                , file_nm
                , create_dt
              )
            select
              (
                select
                  case when max(id) is null then
                    1
                  else
                    max(id) + 1
                  end as id
                from rfs_t_chk_excel
              )
              , $sno
              , $chk_mng_no
              , $syucchoujo_cd
              , $chk_times
              , $struct_idx
              , '$this->file_path'
              , '$this->file_nm'
              , '$create_dt'
EOF;
        }

        $query = $this->rfs->query($sql);

//        log_message('debug', "sql=$sql");
    }

    // Excel管理テーブルの対象レコードを取得
    protected function get_excel_mng($chk) {

        $sno = $chk['sno'];
        $chk_mng_no = $chk['chk_mng_no'];
        $syucchoujo_cd = $chk['syucchoujo_cd'];
        $chk_times = $chk['chk_times'];

        $sql = <<<EOF
        select
          tce.file_path
          , tce.file_nm
          , tce.struct_idx
        from
          rfs_t_chk_excel tce
        where
          (
            tce.sno = $sno
            and tce.syucchoujo_cd = $syucchoujo_cd
            and tce.chk_times = $chk_times
          )
EOF;
        $query = $this->rfs->query($sql);

        return $query->result_array();
    }

    // 防雪柵の施設情報を取得
    protected function get_bssk_shisetsu_info($chk) {

        $sno = $chk['sno'];

        $sql = <<<EOF
        select
          mbs.sno
          , mbs.struct_idx
          , mbs.struct_no_s
          , mbs.struct_no_e
        from
          rfs_m_bousetsusaku_shichu mbs
        where
          mbs.sno = $sno
        order by
          mbs.struct_idx;
EOF;
        $query = $this->rfs->query($sql);

        return $query->result_array();
    }

    // 防雪柵の施設情報を取得(chk_main連携版（点検管理番号がほしいから）)
    protected function get_bssk_shisetsu_info_chk_main($chk) {

        $sno = $chk['sno'];
    $chk_times=$chk['chk_times'];

        $sql = <<<EOF
        select
          mbs.sno
          , mbs.struct_idx
          , mbs.struct_no_s
          , mbs.struct_no_e
        from
          rfs_m_bousetsusaku_shichu mbs
        where
          mbs.sno = $sno
        order by
          mbs.struct_idx;
EOF;
        $query = $this->rfs->query($sql);

        return $query->result_array();
    }

    // 対象施設の最大点検回数を取得
    protected function get_chk_times($chk) {

        $sno = $chk['sno'];

        $sql = <<<EOF
        select
          max(mbs.chk_times)
        from
          rfs_t_chk_main tcm
        where
          tcm.sno = $sno;
EOF;
        $query = $this->rfs->query($sql);

        return $query->result_array();
    }

    // Excel管理テーブルの対象レコードを取得
    protected function getChkMngNoOther($chk_mng_no) {
/*
        $sql = <<<EOF
select
  sno
  , chk_mng_no
  , chk_times
from
  rfs_t_chk_main
where
  sno = (
    select
        sno
    from
      rfs_t_chk_main
    where
      chk_mng_no = $chk_mng_no
    group by
      sno
  )
  and chk_times = (
    select
        chk_times
    from
      rfs_t_chk_main
    where
      chk_mng_no = $chk_mng_no
    group by
      chk_times
  )
  and struct_idx <> 0
EOF;
*/
        $sql = <<<EOF
WITH chk_main_tmp AS (
  SELECT
      *
  FROM
    rfs_t_chk_main
  WHERE
    chk_mng_no = $chk_mng_no
)
, chk_main AS (
                                                  -- 防雪柵内の全ての点検対象の子データ
  SELECT
      c.*
  FROM
    rfs_t_chk_main c JOIN chk_main_tmp ctmp
      ON c.sno = ctmp.sno
      AND c.chk_times = ctmp.chk_times
  WHERE
    c.struct_idx > 0
)
, huzokubutsu_tmp AS (
                                                  -- 附属物の絞込み
  SELECT
      h.*
  FROM
    rfs_t_chk_huzokubutsu h JOIN chk_main c
      ON h.chk_mng_no = c.chk_mng_no
)
, huzokubutsu AS (
                                                  -- 附属物の履歴番号最新
  SELECT
      tmp1.*
  FROM
    huzokubutsu_tmp tmp1 JOIN (
      SELECT
          chk_mng_no
        , MAX(rireki_no) rireki_no
      FROM
        huzokubutsu_tmp
      GROUP BY
        chk_mng_no
    ) tmp2
      ON tmp1.chk_mng_no = tmp2.chk_mng_no
      AND tmp1.rireki_no = tmp2.rireki_no
)
, c_h_join AS (
  SELECT
      c.*
    , h.chk_mng_no h_chk_mng_no
  FROM
    chk_main c
    LEFT JOIN huzokubutsu h
      ON c.chk_mng_no = h.chk_mng_no
)
SELECT
    *
FROM
  c_h_join
WHERE
  h_chk_mng_no IS NOT NULL
EOF;
        $query = $this->rfs->query($sql);
        return $query->result_array();
    }

   /* 対象の点検箇所データを取得
  *
  * 取得  点検時（点検時健全性Ⅱ以上）
  *    措置後（措置後健全性Ⅱ以上）
  *    最新の措置年月日（入力されている措置日全て））
  *
    * 条件：引数の子点検管理番号（rireki_noは最大）
  *
  * 引数：$chk_mng_no_child ある防雪柵の子データ全ての点検管理番号
  *
  * 戻り値：array key:check、sochi、sochi_dts
  *
    */
    protected function getTargetTenkenTaisyouYoushiki1($chk_mng_no_child) {
        log_message('debug', __METHOD__);

    $check = $this->getJudge2over($chk_mng_no_child, 1);
    $sochi = $this->getJudge2over($chk_mng_no_child, 2);
    $sochi_dts = $this->getSochiDts($chk_mng_no_child);
    $buzai = $this->getBuzaiBousetsusaku();

    $res=array();
    $res['check']=$check;
    $res['sochi']=$sochi;
    $res['sochi_dts']=$sochi_dts;
    $res['buzai']=$buzai;

        return $res;
    }

   /* 対象の点検箇所データを取得
  *
    * 条件：引数のExcelデータの点検管理番号（rireki_noは最大）
  *    点検時（点検時健全性Ⅱ以上）
  *    措置後（措置後健全性Ⅱ以上）
  *
  * 引数：$excel Excel管理テーブル情報（chk_mng_noを使用）
  *    $kbn 1:点検時、2:措置後
  *
  * 戻り値：array sql結果
  *
    */
    protected function getJudge2over($excels,$kbn) {
        log_message('debug', __METHOD__);

    $cnt=1;
    $sql="";
    foreach($excels as $item) {
      if ($cnt>1) {
        $sql .= " UNION ";
      }
      $sql .= "(";
      $sql .= "SELECT ";
      $sql .= "tk.chk_mng_no, ";
      $sql .= "tk.buzai_cd, ";
      $sql .= "tk.check_judge, ";
      $sql .= "tk.measures_judge, ";
      $sql .= "tk.sonsyou_naiyou_cd, ";
      $sql .= "sn.sonsyou_naiyou_nm, ";
      $sql .= "p.picture_nm ";
      $sql .= "FROM ";
      $sql .= "rfs_t_chk_tenken_kasyo tk ";
      $sql .= "LEFT JOIN ";
      $sql .= "rfs_m_sonsyou_naiyou sn ";
      $sql .= "ON ";
      $sql .= "tk.sonsyou_naiyou_cd=sn.sonsyou_naiyou_cd ";
      $sql .= "LEFT JOIN ";
      if ($kbn==1) {
        // 点検時
        $sql .= "(SELECT * FROM rfs_t_chk_picture WHERE status = 0) p " ;
      } else {
        // 措置後
        $sql .= "(SELECT * FROM rfs_t_chk_picture WHERE status = 1) p " ;
      }
      $sql .= "ON ";
      $sql .= "tk.chk_mng_no=p.chk_mng_no ";
      $sql .= "AND ";
      $sql .= "tk.buzai_cd=p.buzai_cd ";
      $sql .= "AND ";
      $sql .= "tk.buzai_detail_cd=p.buzai_detail_cd ";
      $sql .= "AND ";
      $sql .= "tk.tenken_kasyo_cd=p.tenken_kasyo_cd ";
      $sql .= "WHERE ";
      $sql .= "tk.chk_mng_no = ".$item['chk_mng_no']." ";
      $sql .= "AND tk.rireki_no = (SELECT MAX(rireki_no) FROM rfs_t_chk_tenken_kasyo WHERE chk_mng_no = ".$item['chk_mng_no'].") ";
      if ($kbn==1) {
        // 点検時
        $sql .= "AND tk.check_judge >= 2";
      } else {
        // 措置後
        $sql .= "AND tk.measures_judge >= 2";
      }
      $sql .= ") ";
      $cnt++;
        $query = $this->rfs->query($sql);
        return $query->result_array();
      }
    }

   /* 部材毎で最新の措置年月日を取得する
  *
    * 条件：引数のExcelデータの点検管理番号（rireki_noは最大）
  *
  * 引数：$excel Excel管理テーブル情報（chk_mng_noを使用）
  *
  * 戻り値：array sql結果
  *
    */
    protected function getSochiDts($excels) {
        log_message('debug', __METHOD__);

    $cnt=1;
    $sql="";
    foreach($excels as $item) {
      if ($cnt>1) {
        $sql .= " UNION ";
      }
      $sql .= "(";
      $sql .= "SELECT ";
      $sql .= "chk_mng_no, buzai_cd, MAX(measures_dt) ";
      $sql .= "FROM ";
      $sql .= "rfs_t_chk_tenken_kasyo ";
      $sql .= "WHERE ";
      $sql .= "chk_mng_no = ".$item['chk_mng_no']." ";
      $sql .= "AND rireki_no = (";
      $sql .= "SELECT ";
      $sql .= "MAX(rireki_no) ";
      $sql .= "FROM ";
      $sql .= "rfs_t_chk_tenken_kasyo ";
      $sql .= "WHERE ";
      $sql .= "chk_mng_no = ".$item['chk_mng_no'];
      $sql .= ") ";
      $sql .= "GROUP BY ";
      $sql .= "chk_mng_no, ";
      $sql .= "buzai_cd";
      $sql .= ")";
      $cnt++;
        $query = $this->rfs->query($sql);
        return $query->result_array();
      }
    }

   /* 部材マスタから防雪柵の部材を取得
  *
  * 戻り値：array sql結果
  *
    */
    protected function getBuzaiBousetsusaku() {
        log_message('debug', __METHOD__);

        $sql = <<<EOF
SELECT
    buzai_cd
  , buzai_nm
FROM
  rfs_m_buzai
WHERE
  shisetsu_kbn = 4
ORDER BY
  buzai_cd
EOF;
        $query = $this->rfs->query($sql);
        return $query->result_array();
    }

    // ==========================================
    //   編集部
    // ==========================================

    /**
     * 基本情報シートの編集
     *
     * @param array $base_info
     * @param array $check_data
     */
    protected function edit_basic_sheet(array $base_info, array $check_data) {
        log_message('debug', __METHOD__);

        $this->xl->setTemplateSheet('点検表記録様式');

        $params = $base_info;

        $judge_threshold = 2;

        // デフォルト処理
        if(!$base_info['chk_mng_no']) {
            $this->xl->renderSheet($params);
            return;
        }

        // 部材ごとのデータ取得処理
        foreach ($check_data as $i => $buzai) { // 部材
            $params['check_buzai_judge'.$i] = $this->shisetsu_judge_str[$buzai->check_buzai_judge];
            $params['measures_buzai_judge'.$i] = $this->shisetsu_judge_str[$buzai->measures_buzai_judge];

            $sonsyou_naiyous = [];
            $picture_names = [];
            $check_picture_nms = '';
            $is_blank = true;       // 措置なしの場合は空白にする
            foreach ($buzai->buzai_detail_row as $buzai_detail) { // 部材詳細
                foreach ($buzai_detail->tenken_kasyo_row as $tenken_kasyo) { // 点検箇所

                    // 点検箇所の健全性がⅡ以上の場合
                    $get_picture_nm_flg = false;
                    if ($tenken_kasyo->check_judge >= $judge_threshold) {
                        $sonsyou_naiyou_name = $this->get_const('sonsyou_naiyou_str', $tenken_kasyo->sonsyou_naiyou_cd);
                        if (!in_array($sonsyou_naiyou_name, $sonsyou_naiyous)) {
                            $sonsyou_naiyous[] = $sonsyou_naiyou_name;
                        }
                        $get_picture_nm_flg = true;
                    }

                    // 写真名
                    if($get_picture_nm_flg) {
                        $picture = $this->get_check_picture($base_info['chk_mng_no'], $buzai->buzai_cd, $buzai_detail->buzai_detail_cd, $tenken_kasyo->tenken_kasyo_cd, self::PICTURE_STATUS_CHECK);
                        if($picture) {
                            if($picture['picture_nm']) {
                                if($picture['path']) {
                                    $picture_name = str_replace("写真", "", $picture['picture_nm']);
                                    if (!in_array($picture_name, $picture_names)) {
                                        $picture_names[] = $picture_name;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $params['picture_nms'.$i] = implode('、', $picture_names);

            // 損傷内容をカンマ区切りで表示する
            $sonsyou_naiyous_str = implode('、', $sonsyou_naiyous);
            if(count($sonsyou_naiyous) > 1) {
                $sonsyou_naiyous_str = mb_substr($sonsyou_naiyous_str, 0, -1, "UTF-8");
            }
            $params['check_sonsyou_naiyou_nms'.$i] = $sonsyou_naiyous_str;

            $get_picture_nm_flg = false;

            // 措置後の判定 II 以上の損傷の種類を取得
            $sonsyou_naiyous = [];
            $picture_names = [];
            $measures_picture_nms = '';
            $last_measures_dt = null;
            foreach ($buzai->buzai_detail_row as $buzai_detail) { // 部材詳細
                foreach ($buzai_detail->tenken_kasyo_row as $tenken_kasyo) { // 点検箇所

                    // 点検箇所の健全性がⅡ以上の場合
                    $get_picture_nm_flg = false;
                    if ($tenken_kasyo->measures_judge >= $judge_threshold) {
                        $sonsyou_naiyou_name = $this->get_const('sonsyou_naiyou_str', $tenken_kasyo->sonsyou_naiyou_cd);
                        if (!in_array($sonsyou_naiyou_name, $sonsyou_naiyous)) {
                            $sonsyou_naiyous[] = $sonsyou_naiyou_name;
                        }
                        $get_picture_nm_flg = true;
                    }

                    // 部材ごとの最新措置日付
                    if ($tenken_kasyo->measures_dt && $tenken_kasyo->measures_dt > $last_measures_dt) {
                        $last_measures_dt = $tenken_kasyo->measures_dt;
                    }

                    // 点検箇所に措置が実施されているか
                    if($tenken_kasyo->measures_judge != 0) {
                        $is_blank = false;
                    }

                    // 写真名
                    if($get_picture_nm_flg) {
                        $picture = $this->get_check_picture($base_info['chk_mng_no'], $buzai->buzai_cd, $buzai_detail->buzai_detail_cd, $tenken_kasyo->tenken_kasyo_cd, self::PICTURE_STATUS_MEASURES);
                        if($picture) {
                            if($picture['picture_nm']) {
                                if($picture['path']) {
                                    $picture_name = str_replace("写真", "", $picture['picture_nm']);
                                    if (!in_array($picture_name, $picture_names)) {
                                        $picture_names[] = $picture_name;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if($is_blank) {
                $params['measures_buzai_judge'.$i] = '';
            } else {
                $params['measures_buzai_judge'.$i] = $this->shisetsu_judge_str[$buzai->measures_buzai_judge];
            }

            if((count($params['picture_nms'.$i]) != 0) && (count($picture_names) != 0)) {
                $params['picture_nms'.$i] .= '、';
            }
            $params['picture_nms'.$i] .= implode('、', $picture_names);

            // 損傷内容をカンマ区切りで表示する
            $sonsyou_naiyous_str = implode('、', $sonsyou_naiyous);
            if(count($sonsyou_naiyous) > 1) {
                $sonsyou_naiyous_str = mb_substr($sonsyou_naiyous_str, 0, -1, "UTF-8");
            }
            $params['measures_sonsyou_naiyou_nms'.$i] = $sonsyou_naiyous_str;

            $params += $this->date2ymd($last_measures_dt, 'measures_dt', $i);
        }

        // 再判定実施年月日
        $params['re_determination_year'] = '';
        $params['re_determination_mon'] = '';
        $params['re_determination_day'] = '';
        $re_determination = [];

        // 再判定日を決定
        $redetermination_dt=$this->getReDeterminationDt($base_info['chk_mng_no']);
        $re_determination = $this->date2ymd($redetermination_dt[0]['dt'], 're_determination');
        // 再判定日付を修正
        // 点検日、詳細点検日、最大の措置日の中で、最大の日付を再判定日付と位置付ける
        // 20191107 措置後で改善が見られた場合のみ再判定を表示する
        if ($re_determination['check_shisetsu_judge']>$re_determination['measures_shisetsu_judge']) {
          $params['re_determination_year'] = $re_determination['re_determination_year'];
          $params['re_determination_mon'] = $re_determination['re_determination_mon'];
          $params['re_determination_day'] = $re_determination['re_determination_day'];
        }

/*         if(!is_null($base_info['chk_dt'])) {
//            $params += $this->date2ymd($base_info['chk_dt'], 're_determination');
//$r = print_r($params, true);
//log_message('info', "\n $r \n");
            $re_determination = $this->date2ymd($base_info['chk_dt'], 're_determination');
            $params['re_determination_year'] = $re_determination['re_determination_year'];
            $params['re_determination_mon'] = $re_determination['re_determination_mon'];
            $params['re_determination_day'] = $re_determination['re_determination_day'];
        }
        if(!is_null($base_info['investigate_dt'])) {
//            $params += $this->date2ymd($base_info['investigate_dt'], 're_determination', 0);
            $re_determination = $this->date2ymd($base_info['investigate_dt'], 're_determination');
            $params['re_determination_year'] = $re_determination['re_determination_year'];
            $params['re_determination_mon'] = $re_determination['re_determination_mon'];
            $params['re_determination_day'] = $re_determination['re_determination_day'];
        }
 */
        $this->xl->renderSheet($params);
    }

    /**
     * 点検日、詳細点検日、最大の措置日のうち最大の日付を再判定措置日として返却する
     * 引数：点検管理番号
     */
    protected function getReDeterminationDt($chk_mng_no) {

      $sql = <<<EOF
WITH huzokubutsu_tmp AS ( 
  SELECT
        * 
    FROM
      rfs_t_chk_huzokubutsu 
    WHERE
      chk_mng_no = $chk_mng_no
) 
, huzokubutsu AS ( 
  SELECT
        tmp1.* 
    FROM
      huzokubutsu_tmp tmp1 
    WHERE
      rireki_no = (SELECT max(rireki_no) FROM huzokubutsu_tmp)
) 
, tenken_kasyo_tmp AS ( 
  SELECT
        * 
    FROM
      rfs_t_chk_tenken_kasyo 
    WHERE
      chk_mng_no = $chk_mng_no
) 
, tenken_kasyo AS ( 
  SELECT
        tmp1.* 
    FROM
      tenken_kasyo_tmp tmp1 
    WHERE
      rireki_no = (SELECT max(rireki_no) FROM tenken_kasyo_tmp)
) 
, max_measures_dt AS ( 
  SELECT
        chk_mng_no
      , max(measures_dt) measures_dt 
    FROM
      tenken_kasyo 
    GROUP BY
      chk_mng_no
) 
, each_dt AS ( 
  SELECT
        chk_mng_no
      , chk_dt dt 
    FROM
      huzokubutsu 
  UNION 
  SELECT
        chk_mng_no
      , investigate_dt dt 
    FROM
      huzokubutsu 
  UNION 
  SELECT
        chk_mng_no
      , measures_dt dt 
    FROM
      max_measures_dt
) 
SELECT
      chk_mng_no
    , max(dt) dt 
  FROM
    each_dt 
  GROUP BY
    chk_mng_no
EOF;
              $query = $this->rfs->query($sql);
              return $query->result_array();
    }

    /**
     * 基本情報（防雪柵）シートの編集
     *
     * @param array $base_info
     * @param array $base_info_child
     * @param array $check_data
     * @param array $shichu_check_data
     */
    protected function edit_bousetsusaku_basic_sheet(array $base_info, array $base_info_child, array $check_data, array $shichu_check_data) {
        log_message('debug', __METHOD__);

        $this->xl->setTemplateSheet('点検表記録様式');

        $params = $base_info;

        $judge_threshold = 2;

        // デフォルト処理
        if(!$base_info['chk_mng_no']) {
            $this->xl->renderSheet($params);
            return;
        }

        // 部材ごとのデータ取得処理（親データ）
        foreach ($check_data as $i => $buzai) { // 部材
            $params['check_buzai_judge'.$i] = $this->shisetsu_judge_str[$buzai->check_buzai_judge];
            $params['measures_buzai_judge'.$i] = $this->shisetsu_judge_str[$buzai->measures_buzai_judge];
        }

    // 様式１の部材毎の健全性Ⅱ以上の点検時：損傷内容、写真番号、措置後：損傷内容、実施年月日を取得
    $youshiki1_sum = $this->getBousetsusakuSumYoushiki1($base_info_child['chk_mng_no']);

    // 戻りをExcel用配列にセット
    foreach($youshiki1_sum['buzai'] as $i => $buzai) {
      $params['check_sonsyou_naiyou_nms'.$i] = $buzai['chk_sonsyou'];
          $params['picture_nms'.$i] = $buzai['chk_pic_nm'];
      $params['measures_sonsyou_naiyou_nms'.$i] = $buzai['sochi_sonsyou'];
      $params += $this->date2ymd($buzai['latest_sochi_dt_b'], 'measures_dt', $i);
    }

    $params += $this->date2ymd($youshiki1_sum['latest_sochi_dt_h'], 're_determination');

        // 部材ごとのデータ取得処理（子データ）
/*        foreach ($shichu_check_data as $i => $buzai) { // 部材
            $sonsyou_naiyous = [];
            $picture_names = [];
            $check_picture_nms = '';
            $is_blank = true;       // 措置なしの場合は空白にする
            foreach ($buzai->buzai_detail_row as $buzai_detail) { // 部材詳細
                foreach ($buzai_detail->tenken_kasyo_row as $tenken_kasyo) { // 点検箇所

                    // 点検箇所の健全性がⅡ以上の場合
                    $get_picture_nm_flg = false;
                    if ($tenken_kasyo->check_judge >= $judge_threshold) {
                        $sonsyou_naiyou_name = $this->get_const('sonsyou_naiyou_str', $tenken_kasyo->sonsyou_naiyou_cd);
                        if (!in_array($sonsyou_naiyou_name, $sonsyou_naiyous)) {
                            $sonsyou_naiyous[] = $sonsyou_naiyou_name;
                        }
                        $get_picture_nm_flg = true;
                    }

                    // 写真名
                    if($get_picture_nm_flg) {
                        $picture = $this->get_check_picture($base_info_child['chk_mng_no'], $buzai->buzai_cd, $buzai_detail->buzai_detail_cd, $tenken_kasyo->tenken_kasyo_cd, self::PICTURE_STATUS_CHECK);
                        if($picture) {
                            if($picture['picture_nm']) {
                                if($picture['path']) {
                                    $picture_name = str_replace("写真", "", $picture['picture_nm']);
                                    if (!in_array($picture_name, $picture_names)) {
                                        $picture_names[] = $picture_name;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $params['picture_nms'.$i] = implode('、', $picture_names);

            // 損傷内容をカンマ区切りで表示する
            $sonsyou_naiyous_str = implode('、', $sonsyou_naiyous);
            if(count($sonsyou_naiyous) > 1) {
                $sonsyou_naiyous_str = mb_substr($sonsyou_naiyous_str, 0, -1, "UTF-8");
            }
            $params['check_sonsyou_naiyou_nms'.$i] = $sonsyou_naiyous_str;

            $get_picture_nm_flg = false;

            // 措置後の判定 II 以上の損傷の種類を取得
            $sonsyou_naiyous = [];
            $picture_names = [];
            $measures_picture_nms = '';
            $last_measures_dt = null;

            foreach ($buzai->buzai_detail_row as $buzai_detail) { // 部材詳細
                foreach ($buzai_detail->tenken_kasyo_row as $tenken_kasyo) { // 点検箇所

                    // 点検箇所の健全性がⅡ以上の場合
                    $get_picture_nm_flg = false;
                    if ($tenken_kasyo->measures_judge >= $judge_threshold) {
                        $sonsyou_naiyou_name = $this->get_const('sonsyou_naiyou_str', $tenken_kasyo->sonsyou_naiyou_cd);
                        if (!in_array($sonsyou_naiyou_name, $sonsyou_naiyous)) {
                            $sonsyou_naiyous[] = $sonsyou_naiyou_name;
                        }
                        $get_picture_nm_flg = true;
                    }

                    // 部材ごとの最新措置日付
                    if ($tenken_kasyo->measures_dt && $tenken_kasyo->measures_dt > $last_measures_dt) {
                        $last_measures_dt = $tenken_kasyo->measures_dt;
                    }

                    // 点検箇所に措置が実施されているか
                    if($tenken_kasyo->measures_judge != 0) {
                        $is_blank = false;
                    }

                    // 写真名
                    if($get_picture_nm_flg) {
                        $picture = $this->get_check_picture($base_info_child['chk_mng_no'], $buzai->buzai_cd, $buzai_detail->buzai_detail_cd, $tenken_kasyo->tenken_kasyo_cd, self::PICTURE_STATUS_MEASURES);
                        if($picture) {
                            if($picture['picture_nm']) {
                                if($picture['path']) {
                                    $picture_name = str_replace("写真", "", $picture['picture_nm']);
                                    if (!in_array($picture_name, $picture_names)) {
                                        $picture_names[] = $picture_name;
                                    }
                                }
                            }
                        }
                    }
                }
            }
//$r = print_r($is_blank, true);
//log_message('info', "is_blank \n $r \n");

            if($is_blank) {
                $params['measures_buzai_judge'.$i] = '';
            } else {
                $params['measures_buzai_judge'.$i] = $this->shisetsu_judge_str[$buzai->measures_buzai_judge];
            }

            if((count($params['picture_nms'.$i]) != 0) && (count($picture_names) != 0)) {
                $params['picture_nms'.$i] .= '、';
            }
            $params['picture_nms'.$i] .= implode('、', $picture_names);

            // 損傷内容をカンマ区切りで表示する
            $sonsyou_naiyous_str = implode('、', $sonsyou_naiyous);
            if(count($sonsyou_naiyous) > 1) {
                $sonsyou_naiyous_str = mb_substr($sonsyou_naiyous_str, 0, -1, "UTF-8");
            }
            $params['measures_sonsyou_naiyou_nms'.$i] = $sonsyou_naiyous_str;

            $params += $this->date2ymd($last_measures_dt, 'measures_dt', $i);
        }

        // 再判定実施年月日
        $params['re_determination_year'] = '';
        $params['re_determination_mon'] = '';
        $params['re_determination_day'] = '';
        $re_determination = [];
        if(!is_null($base_info['chk_dt'])) {
//            $params += $this->date2ymd($base_info['chk_dt'], 're_determination');
//$r = print_r($params, true);
//log_message('info', "\n $r \n");
            $re_determination = $this->date2ymd($base_info['chk_dt'], 're_determination');
            $params['re_determination_year'] = $re_determination['re_determination_year'];
            $params['re_determination_mon'] = $re_determination['re_determination_mon'];
            $params['re_determination_day'] = $re_determination['re_determination_day'];
        }
        if(!is_null($base_info['investigate_dt'])) {
//            $params += $this->date2ymd($base_info['investigate_dt'], 're_determination', 0);
            $re_determination = $this->date2ymd($base_info['investigate_dt'], 're_determination');
            $params['re_determination_year'] = $re_determination['re_determination_year'];
            $params['re_determination_mon'] = $re_determination['re_determination_mon'];
            $params['re_determination_day'] = $re_determination['re_determination_day'];
        }
*/
        // 施設形式名の表示
        // テンプレートに記載されているので、取得は不要
//        if(isset($params['shisetsu_keishiki_nm'])) {
//            $params['shisetsu_keishiki_nm'] = '（' . $params['shisetsu_keishiki_nm'] . '）';
//        }

//      $r = print_r($params, true);
//      log_message('info', "params ===================> \n $r \n");

        $this->xl->renderSheet($params);
    }

    /**
     * 防雪柵親データ作成（点検箇所）
   *　 点検箇所データについて、点検管理番号毎に集約する。
   *   集約内容：
     *     ①点検時
     *     損傷内容 判定区分Ⅱ以上の点検箇所を全て集約し、「、」区切りで取得（同じものは1個で）
     *     写真番号 判定区分Ⅱ以上の写真番号を全て集約し、「、」区切りで取得（同じものは1個で）
     *
     *     ②措置後
     *     損傷内容 判定区分Ⅱ以上の点検箇所を全て集約し、「、」区切りで取得（同じものは1個で）
     *     判定日付 措置を行った最新の措置年月日を取得
     *
     * @param array $chk_mng_no 点検管理番号
     *
     *  return array key：chk_sonsyou、chk_pic_nm、sochi_sonsyou、sochi_hantei_dt
     *
     */
  protected function getBousetsusakuSumYoushiki1($chk_mng_no) {
        log_message('debug', __METHOD__);

    // 点検管理番号から、取得すべき全子データの点検管理番号を取得する
    $bs_child=$this->getChkMngNoOther($chk_mng_no);

    // 点検箇所データの取得
    $target_tk = $this->getTargetTenkenTaisyouYoushiki1($bs_child);

    $res=array();
    $res['buzai']=array();
    $latest_sochi_dt_h="";      // 最新措置日
    // 部材ループ
    foreach($target_tk['buzai'] as $buzai) {
      $chk_naiyou=array();      // 点検時損傷内容
      $chk_pic_nm=array();      // 点検時写真名
      $sochi_naiyou=array();      // 措置後損傷内容

      $buzai_cd=$buzai['buzai_cd'];


      /*** 点検時データ ***/
      foreach($target_tk['check'] as $check) {
        $tmp=array();
        $tmp_p=array();
        // 等しい部材コードを配列にセット
        if ($check['buzai_cd']==$buzai_cd) {
          // 中身あるときだけ
          if ($check['sonsyou_naiyou_cd']) {
            // 損傷内容
            if (!$chk_naiyou) {
              $tmp['sonsyou_naiyou_cd']=$check['sonsyou_naiyou_cd'];
              $tmp['sonsyou_naiyou_nm']=$check['sonsyou_naiyou_nm'];
              array_push($chk_naiyou, $tmp);
            }else{
              $sonsyou_naiyou_cd=$check['sonsyou_naiyou_cd'];
              // 配列がある場合は同じ損傷内容が存在したらセットしない
              $search=0;
              foreach($chk_naiyou as $item) {
                if ($item['sonsyou_naiyou_cd']==$sonsyou_naiyou_cd) {
                  $search=1;
                  break;
                }
              }
              // 無い場合
              if ($search==0) {
                $tmp['sonsyou_naiyou_cd']=$check['sonsyou_naiyou_cd'];
                $tmp['sonsyou_naiyou_nm']=$check['sonsyou_naiyou_nm'];
                array_push($chk_naiyou, $tmp);
              }
            }
          }
          // 中身あるときだけ
          if ($check['picture_nm']) {
            // 写真番号
            if (!$chk_pic_nm) {
              $tmp_p['chk_mng_no']=$check['chk_mng_no'];
              $tmp_p['buzai_cd']=$check['buzai_cd'];
              $tmp_p['picture_nm']=$check['picture_nm'];
              array_push($chk_pic_nm, $tmp_p);
            }else{
              $picture_nm=$check['picture_nm'];
              // 配列がある場合は同じ写真が存在したらセットしない
              $search=0;
              foreach($chk_pic_nm as $item) {
                if ($item['picture_nm']==$picture_nm) {
                  $search=1;
                  break;
                }
              }
              // 無い場合
              if ($search==0) {
                $tmp_p['chk_mng_no']=$check['chk_mng_no'];
                $tmp_p['buzai_cd']=$check['buzai_cd'];
                $tmp_p['picture_nm']=$check['picture_nm'];
                array_push($chk_pic_nm, $tmp_p);
              }
            }
          }
        }
      }

      /*** 措置後データ ***/
      foreach($target_tk['sochi'] as $sochi) {
        // 措置は一つしか要素が無いのでcontinue
        if (!$sochi['sonsyou_naiyou_cd']) {
          continue;
        }
        $tmp=array();
        // 等しい部材コードを配列にセット
        if ($sochi['buzai_cd']==$buzai_cd) {
          if (!$sochi_naiyou) {
            $tmp['sonsyou_naiyou_cd']=$sochi['sonsyou_naiyou_cd'];
            $tmp['sonsyou_naiyou_nm']=$sochi['sonsyou_naiyou_nm'];
            array_push($sochi_naiyou, $tmp);
          }else{
            $sonsyou_naiyou_cd=$sochi['sonsyou_naiyou_cd'];
            // 配列がある場合は同じ損傷内容が存在したらセットしない
            $search=0;
            foreach($sochi_naiyou as $item) {
              if ($item['sonsyou_naiyou_cd']==$sonsyou_naiyou_cd) {
                $search=1;
                break;
              }
            }
            // 無い場合
            if ($search==0) {
              $tmp['sonsyou_naiyou_cd']=$sochi['sonsyou_naiyou_cd'];
              $tmp['sonsyou_naiyou_nm']=$sochi['sonsyou_naiyou_nm'];
              array_push($sochi_naiyou, $tmp);
            }
          }
        }
      }

      /*** 措置日データ ***/
      $latest_sochi_dt_b="";    // 部材毎最新措置日
      foreach($target_tk['sochi_dts'] as $sochi_dt) {
        // 措置日は一つしか要素が無いのでcontinue
        if (!$sochi_dt['max']) {
          continue;
        }
        $tmp=array();
        // 等しい部材コードを配列にセット
        if ($sochi_dt['buzai_cd']==$buzai_cd) {
          if ($latest_sochi_dt_b=="") {
            $latest_sochi_dt_b=$sochi_dt['max'];
          }else{
            $max=$sochi_dt['max'];
            // 同じ部材内では一番新しい日付のみ保持
            if(strtotime($max) > strtotime($latest_sochi_dt_b)){
              $latest_sochi_dt_b=$max;
            }
          }
        }
      }
      // 措置日の最新を決定
      if ($latest_sochi_dt_h=="") {
        $latest_sochi_dt_h=$latest_sochi_dt_b;
      }else{
        if(strtotime($latest_sochi_dt_b) > strtotime($latest_sochi_dt_h)){
          $latest_sochi_dt_h=$latest_sochi_dt_b;
        }
      }

      // ソートします
      $chk_naiyou=$this->sortArray($chk_naiyou, "sonsyou_naiyou_cd", 0, "sonsyou_naiyou_nm");
      $chk_pic_nm=$this->sortArray($chk_pic_nm, "picture_nm", 0, "picture_nm");
      $sochi_naiyou=$this->sortArray($sochi_naiyou, "sonsyou_naiyou_cd", 0, "sonsyou_naiyou_nm");

      $tmp=array();
      $tmp['buzai_cd']=$buzai_cd;
      $tmp['chk_sonsyou']=implode('、', $chk_naiyou);
      $tmp['chk_pic_nm']=implode('、', $chk_pic_nm);
      $tmp['sochi_sonsyou']=implode('、', $sochi_naiyou);
      $tmp['latest_sochi_dt_b']=$latest_sochi_dt_b;
      array_push($res['buzai'], $tmp);
    }
    $res['latest_sochi_dt_h']=$latest_sochi_dt_h;
    return $res;
  }

  /**
     * 配列を引数のキーでソートし、引数のvalueの配列で返却する
   *
   * @param array $arr  対象配列
     * @param string $key  キー
     * @param integer $sort  0:昇順、1:降順
   * @param string $val  返却value
     */
    protected function sortArray(&$arr, $field, $sort, $val) {
    $tmp=array();
    foreach($arr as $key => $row){
      $tmp[$key] = $row[$field];
    }
    if ($sort==0) {
      array_multisort($tmp, $arr);
    } else {
      array_multisort($tmp, SORT_DESC, $arr);
    }
    $res=array();
    foreach($arr as $row){
      array_push($res, $row[$val]);
    }
    return $res;
  }

    /**
     * 状況写真（損傷状況）シートの編集
     *
     * @param array $base_info
     * @param array $check_data
     */
    protected function edit_photo_sheet(array $base_info, array $check_data) {
        log_message('debug', __METHOD__);
        $this->xl->setTemplateSheet('状況写真');
        // デフォルト処理
        if(!$base_info['chk_mng_no']) {
            $params = $base_info;
            $this->xl->renderSheet($params, '状況写真' . $this->sheet_num++);
            return;
        }

        $tenken_kasyos = [];

        foreach ($check_data as $buzai) { // 部材
            foreach ($buzai->buzai_detail_row as $buzai_detail) { // 部材詳細
                foreach ($buzai_detail->tenken_kasyo_row as $tenken_kasyo) { // 点検箇所
                    // 点検箇所が対象なら出力する
                    if ($tenken_kasyo->taisyou_umu) {
                        $tenken_kasyos[] = $tenken_kasyo;
                    }
                }
            }
        }
        // 点検箇所の写真を1シートに2か所ずつ出力
        $chunk_size = 2;
        $chunks = array_chunk($tenken_kasyos, $chunk_size);
//         log_message('debug', print_r($tenken_kasyos,1));
        foreach ($chunks as $sheet => $chunk) {
//            log_message('debug', 'sheet='.$sheet);
            $params = $base_info;

            for ($c = 0; $c < $chunk_size; $c++) {
                if (isset($chunk[$c])) {
                    $tk = new SafeObject($chunk[$c]);
                } else {
                    $tk = new SafeObject(null);
                }

                $params['tenken_kasyo_nm_' . $c] = $tk->tenken_kasyo_nm;
                $params['check_judge_str_' . $c] = $this->get_const('shisetsu_judge_str', $tk->check_judge);
                $params['measures_judge_str_' . $c] = $this->get_const('shisetsu_judge_str', $tk->measures_judge);
                $params['check_policy_str_' . $c] = $this->get_const('check_policy_str', $tk->check_policy);
                $params['measures_policy_str_' . $c] = $tk->measures_policy;
                $params['check_bikou_' . $c] = $tk->check_bikou;
                $params['measures_bikou_' . $c] = $tk->measures_bikou;

                $params += $this->date2ymd($tk->measures_dt, 'measures_dt', $c);

                // 損傷内容はリストで選択されている名前を表示する
                $params['check_sonsyou_naiyou_nms_' . $c] = $this->get_const('sonsyou_naiyou_str', $tk->sonsyou_naiyou_cd);;
                $params['measures_sonsyou_naiyou_nms_' . $c] = $this->get_const('sonsyou_naiyou_str', $tk->sonsyou_naiyou_cd);;

                $params['check_picture_' . $c] = null;
                $params['measures_picture_' . $c] = null;
                $params['check_picture_cd_' . $c] = '';
                $params['measures_picture_cd_' . $c] = '';

                if ($tk->is_object()) {
                    // 点検時の写真を得る
                    $picture = $this->get_check_picture(
                        $base_info['chk_mng_no'], $tk->buzai_cd, $tk->buzai_detail_cd, $tk->tenken_kasyo_cd,
                        self::PICTURE_STATUS_CHECK);
                    if ($picture) {
                        if($picture['path']) {
                            $params['check_picture_' . $c] = $this->picture_root . $picture['path'];
                            $params['check_picture_cd_' . $c] = $picture['picture_nm'];
                        }
                    }
                    // 措置後の写真を得る
                    $picture = $this->get_check_picture(
                        $base_info['chk_mng_no'], $tk->buzai_cd, $tk->buzai_detail_cd, $tk->tenken_kasyo_cd,
                        self::PICTURE_STATUS_MEASURES);
                    if ($picture) {
                        if($picture['path']) {
                            $params['measures_picture_' . $c] = $this->picture_root . $picture['path'];
                            $params['measures_picture_cd_' . $c] = $picture['picture_nm'];
                        }
                    }
                }
            }
            $this->xl->renderSheet($params, '状況写真' . $this->sheet_num++);
        }
    }

    /**
     * 損傷度記録表シートの編集
     *
     * @param array $base_info
     * @param array $check_data
     */
    protected function edit_damage_sheet(array $base_info, array $check_data) {
        log_message('debug', __METHOD__);

        $this->xl->setTemplateSheet('損傷度記録表');

        // デフォルト処理
        if(!$base_info['chk_mng_no']) {
            $params = $base_info;
            $this->xl->renderSheet($params, '損傷度記録表' . $this->sheet_num++);
            return;
        }

        $sonsyou_naiyous = $this->rfs
            ->order_by('sonsyou_naiyou_cd')
            ->get('rfs_m_sonsyou_naiyou')
            ->result();

        $params = $base_info;

        $ntk = 0;

        foreach ($check_data as $nb => $buzai) { // 部材
            // 部材の不足データを取得
            if($base_info['chk_mng_no']) {
                $buzai_ex = new SafeObject($this->rfs
                    ->where('chk_mng_no', $base_info['chk_mng_no'])
                    ->where('rireki_no', $base_info['rireki_no'])
                    ->where('buzai_cd', $buzai->buzai_cd)
                    ->get('rfs_t_chk_buzai')
                    ->row());

                if(!is_null($buzai_ex->necessity_measures)) {
                    $params['necessity_measures_' . $nb]
                        = $this->get_const('necessity_measures_str', $buzai_ex->necessity_measures);
                } else {
                    $params['necessity_measures_' . $nb]
                        = $this->get_const('necessity_measures_str', 2);
                }

            } else {
                $params['necessity_measures_' . $nb] = '';
            }

            // 部材の健全性
            // (措置健全性はループ処理の後で設定)
            $params['check_buzai_judge_' . $nb]
                = $this->get_const('shisetsu_judge_str', $buzai->check_buzai_judge);

            $params['hantei1_' . $nb] = $buzai->hantei1;
            $params['hantei2_' . $nb] = $buzai->hantei2;
            $params['hantei3_' . $nb] = $buzai->hantei3;
            $params['hantei4_' . $nb] = $buzai->hantei4;

            $is_valid_tenken_kasyo = false;
            foreach ($buzai->buzai_detail_row as $buzai_detail) { // 部材詳細
                foreach ($buzai_detail->tenken_kasyo_row as $tenken_kasyo) { // 点検箇所
                    // 点検箇所の対象の有無
                    if($tenken_kasyo->taisyou_umu) {
                        $params['taisyou_umu_' . $ntk] = '有';

                        $is_valid_tenken_kasyo = true;      // 有効な点検箇所あり

                        $params['check_status_' . $ntk]
                            = $this->get_const('check_status_str', $tenken_kasyo->check_status);
                        $params['check_judge_' . $ntk]
                            = $this->get_const('shisetsu_judge_str', $tenken_kasyo->check_judge);
                        $params['measures_judge_' . $ntk]
                            = $this->get_const('shisetsu_judge_str', $tenken_kasyo->measures_judge);
                        if($tenken_kasyo->measures_judge == 0) {
                            $params['measures_judge_' . $ntk]
                                = $this->get_const('shisetsu_judge_str', $tenken_kasyo->check_judge);
                        }
                        foreach ($sonsyou_naiyous as $sonsyou_naiyou) {
                            $suffix = $ntk . '_' . ($sonsyou_naiyou->sonsyou_naiyou_cd - 1);
                            $params['check_before_' . $suffix] = '';
                            $params['measures_after_' . $suffix] = '';
                        }
                        foreach ($tenken_kasyo->sonsyou_naiyou_row as $sonsyou_naiyou) { // 損傷内容
                            $suffix = $ntk . '_' . ($sonsyou_naiyou->sonsyou_naiyou_cd - 1);
                            $params['check_before_' . $suffix]
                                = $this->get_const('sonsyou_str', $sonsyou_naiyou->check_before);
                            $params['measures_after_' . $suffix]
                                = $this->get_const('sonsyou_str', $sonsyou_naiyou->measures_after);
                        }

                        $sonota_no = $ntk + 1;
                        if(21 > $sonota_no) {
                            $sonota_no = $this->get_const('sonota_no_str', $sonota_no);
                        }
                        $params['sonota_no_' . $ntk] = $sonota_no;
                        $params['sonota_kasyo_' . $ntk] = 'その他';
                        $params['sonota_kigou_' . $ntk] = '-';
                    } else {
                        $params['taisyou_umu_' . $ntk] = '無';

                        if($tenken_kasyo->tenken_kasyo_nm == 'その他') {
                            $params['taisyou_umu_' . $ntk] = '';
                        }

                        $params['check_status_' . $ntk] = '';
                        $params['check_judge_' . $ntk] = '';
                        $params['measures_judge_' . $ntk] = '';

                        foreach ($sonsyou_naiyous as $sonsyou_naiyou) {
                            $suffix = $ntk . '_' . ($sonsyou_naiyou->sonsyou_naiyou_cd - 1);
                            $params['check_before_' . $suffix] = '';
                            $params['measures_after_' . $suffix] = '';
                        }

                        foreach ($tenken_kasyo->sonsyou_naiyou_row as $sonsyou_naiyou) { // 損傷内容
                            $suffix = $ntk . '_' . ($sonsyou_naiyou->sonsyou_naiyou_cd - 1);
                            $params['check_before_' . $suffix] = '';
                            $params['measures_after_' . $suffix] = '';
                        }

                        $params['sonota_no_' . $ntk] = '';
                        $params['sonota_kasyo_' . $ntk] = '';
                        $params['sonota_kigou_' . $ntk] = '';
                    }
                    $ntk++;
                }
            }
            // 措置後健全性が無い場合は、点検時の健全性を設定する
            $params['measures_buzai_judge_' . $nb]
                = $this->get_const('shisetsu_judge_str', $buzai->measures_buzai_judge);
            if($is_valid_tenken_kasyo) {
                if($this->get_const('shisetsu_judge_str', $buzai->measures_buzai_judge) == 0) {
                    $params['measures_buzai_judge_' . $nb]
                        = $this->get_const('shisetsu_judge_str', $buzai->measures_buzai_judge);
                }
            }
        }
        $this->xl->renderSheet($params, '損傷度記録表' . $this->sheet_num++);
    }

    /**
     * 点検箇所特定附図シートの編集
     *
     * @param array $base_info
     */
    protected function edit_figure_sheet(array $base_info) {
        log_message('debug', __METHOD__);

        $this->xl->setTemplateSheet('点検箇所特定附図');

        $params = $base_info;

        if($base_info['chk_mng_no']) {
            for ($i = 1; $i <= $this->surface_count; $i++) {
                $params['surface_' . $i] = $i == $base_info['surface'];
            }
        }
        try {
            $this->xl->renderSheet($params);
        } catch (InvalidArgumentException $e) {
            // shisetsu_keishiki_cd が空の場合は様式4を出力しない
            log_message('error', $e->getMessage());
        }
    }

    /**
     * 点検箇所特定附図（防雪柵）シートの編集
     *
     * @param array $base_info
     * @param array $shichu
     */
    protected function edit_bosetsusaku_figure_sheet(array $base_info) {
        log_message('debug', __METHOD__);

        $this->xl->setTemplateSheet('点検箇所特定附図');

        $params = $base_info;

        if($base_info['surface']) {
            for ($i = 1; $i <= $this->surface_count; $i++) {
                $params['surface_' . $i] = $i == $base_info['surface'];
            }
        } else {
            $params['surface_1'] = null;
            $params['surface_2'] = null;
            $params['surface_3'] = null;
        }
        // 支柱開始番号が0の場合(＝施設情報登録のみ(chk_mng_no)なし、支柱登録なし)
        // 支柱番号が無いため、出力しない
        if($base_info['struct_no_s'] == 0) {
            for ($i = 0; $i < 11; $i++) {
                $params['struct_no_'.$i] = '';
            }
        } else {
            for ($i = 0; $i < 11; $i++) {
                $params['struct_no_'.$i] = $base_info['struct_no_s'] + $i;
            }
        }
        $this->xl->renderSheet($params, '点検箇所特定附図' . $this->sheet_num++);
    }
    // ====================================================================
    protected function get_format($excel_ver) {
        if ($excel_ver == self::OUTPUT_XLSX) {
            return MultisheetExcelWrapper::FORMAT_XLSX;
        } else {
            return MultisheetExcelWrapper::FORMAT_XLS;
        }
    }

    // 施設ごとの空ファイルのパスを取得する
    protected function get_blank_file_path($chk) {
        if(!$chk['shisetsu_keishiki_cd']) {
            $chk['shisetsu_keishiki_cd'] = 0;
        }

        return $this->picture_root .
               self::DIR_EXCEL . '/' .
               self::DIR_BLANK . '/' .
               $chk['shisetsu_kbn'] . '/' .
               $chk['shisetsu_kbn'] . '_' .
               $chk['shisetsu_keishiki_cd'] . '_blank.xls';
    }

    // 期限を過ぎたフォルダを削除
    protected function del_past_dir($search_dir) {

        $dir_array = scandir($search_dir);
        $today = new DateTime(date('Ymd'));
        foreach($dir_array as $dir) {

            $past = new DateTime(basename($dir));
            $interval = $today->diff($past);
            $days = $interval->format('%d');

            if($days >= self::ZIP_SAVE_DAYS) {
                $command = "cd ". $search_dir ."; ". "rm -r -f ". $dir;
                exec($command);
            }
        }
    }

    /**
     * 定数を取得
     *
     * null なら空文字列を返す
     *
     * @param string|string[] $constant 静的プロパティ名または配列
     * @param int $value 配列キー
     * @return string
     */
    protected function get_const($constant, $value) {
        if ($value === null) {
            return '';
        }

        if (is_array($constant)) {
            return $constant[$value];
        } elseif (isset($this->$constant) && is_array($this->$constant)) {
            return $this->{$constant}[$value];
        } else {
            log_message('error', '不明な定数です: '.$constant);
        }
    }

    /**
     * 日付を年月日に分割
     *
     * @param int|string $date
     * @param string $prefix
     * @param string $suffix
     * @return string[]
     */
    protected function date2ymd($date, $prefix, $suffix = '') {
        $key = function($name) use($prefix, $suffix) {
            return $prefix . '_' . $name . $suffix;
        };

        if (!$date) {
            return [
                $key('year') => '',
                $key('mon') => '',
                $key('day') => ''
            ];
        }

        if (!is_numeric($date)) {
            $date = strtotime($date);
        }

        return [
            $key('year') => date('Y', $date),
            $key('mon') => date('n', $date),
            $key('day') => date('j', $date)
        ];
    }

    /**
     * 度を度分秒に分割
     *
     * @param float $decimal
     * @param string $prefix
     * @return string[]
     */
    protected function deg2dms($decimal, $prefix) {
        $key = function($name) use($prefix) {
            return $prefix.'_'.$name;
        };

        if ($decimal === null) {
            return [
                $key('deg') => '',
                $key('min') => '',
                $key('sec') => ''
            ];
        }

        return [
            $key('deg') => floor($decimal),
            $key('min') => ($decimal * 60) % 60,
            $key('sec') => ($decimal * 3600) % 60
        ];
    }

    // 防雪柵親データの部材情報作成(ストック点検用)
    // 子データの健全性を反映する
    public function create_stock_data() {

        log_message('info', "\n\n *** \n stock data create start \n *** \n\n");

        $parent_mng_array = $this->get_bssk_parent_snos();

        // 防雪柵親データ数分ループ
        foreach($parent_mng_array as $parent_mng) {

//if($parent_mng['sno'] == 5964) {
//    exit;
//}
            // 親データの基本情報を取得
            $base_info_parent = $this->get_baseinfo_by_chkmngno([
                'chk_mng_no' => $parent_mng['chk_mng_no'],
                'sno' => $parent_mng['sno'],
                'struct_idx' => 0
            ])[0];

            // 親データの部材情報を取得
            $result = $this->get_chkdata_tmp(4, $parent_mng['chk_mng_no'], 0);
            $buzai_json = json_decode($result[0]['buzai_row'], true);
//$r = print_r($result, true);
//log_message('info', "\n $r \n");
            $check_data_parent = $buzai_json['buzai'];

            // 子データリストを取得
            $child_mng_array = $this->get_bssk_childeren($parent_mng['sno']);

            // 防雪柵子データ分ループ
            foreach($child_mng_array as $child_mng) {

        // ループ変数が、防雪柵マスタを見ているため、点検管理番号が無い場合がある
        if (!$child_mng['chk_mng_no']) {
          continue;
        }

                // 子データの基本情報を取得
                $base_info_child = $this->get_baseinfo_by_chkmngno([
                    'chk_mng_no' => $child_mng['chk_mng_no'],
                    'sno' => $child_mng['sno'],
                    'struct_idx' => 0
                ])[0];

                // 子データの部材情報を取得
                $result = $this->get_chkdata_tmp(4, $child_mng['chk_mng_no'], 0);
                $buzai_json = json_decode($result[0]['buzai_row'], true);
                $check_data_child = $buzai_json['buzai'];

                // 健全性の悪いものを取得
                $index = 0;
                foreach($check_data_child as $buzai) {   // 部材
                    // 部材の健全性の比較
                    if($buzai['check_buzai_judge'] > $check_data_parent[$index]['check_buzai_judge']) {
                        $check_data_parent[$index]['check_buzai_judge'] = $buzai['check_buzai_judge'];
                    }

                    if($buzai['measures_buzai_judge'] > $check_data_parent[$index]['measures_buzai_judge']) {
                        $check_data_parent[$index]['measures_buzai_judge'] = $buzai['measures_buzai_judge'];
                    }
                    $index++;
                }
                // 施設の健全性の比較
                if($base_info_child['check_shisetsu_judge'] > $base_info_parent['check_shisetsu_judge']) {
                    $base_info_parent['check_shisetsu_judge'] = $base_info_child['check_shisetsu_judge'];
                }
                if($base_info_child['measures_shisetsu_judge'] > $base_info_parent['measures_shisetsu_judge']) {
                    $base_info_parent['measures_shisetsu_judge'] = $base_info_child['measures_shisetsu_judge'];
                }
            }
            // ストック点検は完了状態とする
            $base_info_parent['phase'] = 5;
            // 親データの基本情報を保存
            $this->set_baseinfo($base_info_parent);
            // 親データの部材情報を保存
            $this->set_chkdata($base_info_parent, $check_data_parent);
        }
    }

    /**
     * 防雪柵親データに子データの健全性を反映する
     *
     * @param $post['sno']          施設シリアル番号
     * @param $post['chk_mng_no']   点検管理番号
     * @return
     */
    public function merge_to_bssk_parent($post) {
        log_message('debug', 'merge_to_bssk_parent');
      $sno = null;
      $chk_times="";
      // snoとchk_mng_noで別々に動くように変更
      if(isset($post['sno'])){
        $sno = $post['sno'];
        // 親データを取得
        $parent_mng = $this->get_bssk_parent_by_sno($sno)[0];
        $chk_times = $parent_mng['chk_times'];

      }else if(isset($post['chk_mng_no'])){
        $chk_mng_no = $post['chk_mng_no'];
        // 親データを取得
        $parent_mng = $this->get_bssk_parent_by_chk_mng_no($chk_mng_no)[0];
        $sno = $parent_mng['sno'];
        $chk_times = $parent_mng['chk_times'];
        // 子データ無かった場合終了
        $chk_child_cnt = $this -> getChildChk($sno, $chk_times);
        if ($chk_child_cnt==0) {
          return; //  終了
        }
      }else{
        return;
      }

        // 親データの基本情報を取得
        $base_info_parent = $this->get_baseinfo_by_chkmngno([
            'chk_mng_no' => $parent_mng['chk_mng_no'],
            'sno' => $sno,
            'struct_idx' => 0
        ])[0];

        // 親データの部材情報を取得
        $result = $this->get_chkdata_tmp(4, $parent_mng['chk_mng_no'], 0);
        $buzai_json = json_decode($result[0]['buzai_row'], true);
        $check_data_parent = $buzai_json['buzai'];

        // 子データリストを取得
        $child_mng_array = $this->get_bssk_childeren($parent_mng['sno'], false);

        // 防雪柵子データ分ループ
        $check_buzai_judges = array();          // 未実施
        $measures_buzai_judges = array();       // 未実施
        $base_info_parent['check_shisetsu_judge'] = 0;
        $base_info_parent['measures_shisetsu_judge'] = 0;

        $chk_dt = null;
        $chk_company = "";
        $chk_person = "";
        $investigate_dt = null;
        $investigate_company = "";
        $investigate_person = "";
        $syoken = "";
        $is_complete = true;

        // 施設の健全性を保持（部材ループ中に最悪を保持する）
        $check_buzai_judges_h=0;
        $measures_buzai_judges_h=0;

        foreach($child_mng_array as $child_mng) {
          // ループ変数が、防雪柵マスタを見ているため、点検管理番号が無い場合がある
          if (!$child_mng['chk_mng_no']) {
            continue;
          }
          // 子データの基本情報を取得
          $base_info_child = $this->get_baseinfo_by_chkmngno([
              'chk_mng_no' => $child_mng['chk_mng_no'],
              'sno' => $child_mng['sno'],
              'struct_idx' => 0 // 飛び先で使用していない
          ])[0];

// hirano 子データの履歴番号は０ではなくMAX値（最新）じゃないとダメでしょ！
          $rireki_no_arr = $this->getLatestRirekiNoBuzai($child_mng['chk_mng_no']);
          $rireki_no=0;
          if ($rireki_no_arr[0]['max_rireki_no']) {
            $rireki_no = $rireki_no_arr[0]['max_rireki_no'];
          }
          // 子データの部材情報を取得
          $result = $this->get_chkdata_tmp(4, $child_mng['chk_mng_no'], $rireki_no);

          $buzai_json = json_decode($result[0]['buzai_row'], true);
          $check_data_child = $buzai_json['buzai'];

          // 健全性の悪いものを取得
          $index = 0;
          foreach($check_data_child as $buzai) {   // 部材
            // 部材の健全性の初期値
            if(is_null($check_buzai_judges[$index])) {
                $check_buzai_judges[$index] = 0;
            }
            if(is_null($measures_buzai_judges[$index])) {
                $measures_buzai_judges[$index] = 0;
            }
            // 部材の点検時健全性
            if($buzai['check_buzai_judge'] > $check_buzai_judges[$index]) {
              $check_buzai_judges[$index] = $buzai['check_buzai_judge'];
            }
            // 附属物として最悪をセットする
            if($buzai['check_buzai_judge'] > $check_buzai_judge_h) {
              $check_buzai_judge_h = $buzai['check_buzai_judge'];
            }
            // 部材の措置後健全性
            if($buzai['measures_buzai_judge'] > $measures_buzai_judges[$index]) {
              $measures_buzai_judges[$index] = $buzai['measures_buzai_judge'];
            }
            // 附属物として最悪をセットする
            if($buzai['measures_buzai_judge'] > $measures_buzai_judges_h) {
              $measures_buzai_judges_h = $buzai['measures_buzai_judge'];
            }
            $check_data_parent[$index]['check_buzai_judge'] = $check_buzai_judges[$index];
            $check_data_parent[$index]['measures_buzai_judge'] = $measures_buzai_judges[$index];
            $index++;
          }

// ここで求めた健全性と比較
// 元が何であっても関係ないので部材ループ内で最悪なものをセット
          // 施設の健全性の比較
          $base_info_parent['check_shisetsu_judge'] = $check_buzai_judge_h;
          $base_info_parent['measures_shisetsu_judge'] = $measures_buzai_judges_h;
          // 点検日、点検会社、点検員の更新
          if(!is_null($base_info_child['chk_dt'])) {
            $isUpdate = false;
            $chk_dt_child = strtotime($base_info_child['chk_dt']);
            // 親データの点検日がnullもしくは子データよりも古い場合、更新処理を行う
            if(is_null($chk_dt)) {
              $isUpdate = true;
            } else {
              $chk_dt_parent = strtotime($chk_dt);
            if($chk_dt_child > $chk_dt_parent) {
              $isUpdate = true;
            }
          }
          // 点検日と点検会社と点検員を更新する
          if ($isUpdate) {
            // 点検日
            $chk_dt = $base_info_child['chk_dt'];
            // 点検会社
            if(!is_null($base_info_child['chk_company'])) {
              $chk_company = $base_info_child['chk_company'];
            }
            // 点検員
            if (!is_null($base_info_child['chk_person'])) {
              $chk_person = $base_info_child['chk_person'];
            }
          }
        }
        // 調査日、調査会社、調査員の更新
        if(!is_null($base_info_child['investigate_dt'])) {
          $isUpdate = false;
          $investigate_dt_child = strtotime($base_info_child['investigate_dt']);
          // 親データの調査日がnullもしくは子データよりも古い場合、更新処理を行う
          if(is_null($investigate_dt)) {
            $isUpdate = true;
          } else {
            $investigate_dt_parent = strtotime($investigate_dt);
            if($investigate_dt_child > $investigate_dt_parent) {
              $isUpdate = true;
            }
          }
          // 調査日と調査会社と調査員を更新する
          if ($isUpdate) {
            // 調査日
            $investigate_dt = $base_info_child['investigate_dt'];
            // 調査会社
            if(!is_null($base_info_child['investigate_company'])) {
              $investigate_company = $base_info_child['investigate_company'];
            }
            // 調査員
            if (!is_null($base_info_child['investigate_person'])) {
              $investigate_person = $base_info_child['investigate_person'];
            }
          }
        }
        // 所見の更新
        if((!is_null($base_info_child['syoken'])) && (strlen($base_info_child['syoken']) > 0)) {
          if(strlen($syoken) > 0) {
            $syoken .= PHP_EOL;
          }
          $syoken .= $base_info_child['syoken'];
        }
        if($base_info_child['phase'] != 5) {
          $is_complete = false;
        }
      }
      $base_info_parent['chk_dt'] = $chk_dt;
      $base_info_parent['chk_company'] = $chk_company;
      $base_info_parent['chk_person'] = $chk_person;
      $base_info_parent['investigate_dt'] = $investigate_dt;
      $base_info_parent['investigate_company'] = $investigate_company;
      $base_info_parent['investigate_person'] = $investigate_person;
      $base_info_parent['syoken'] = $syoken;
      // 子データが全て完了の場合、親データも完了状態とする
      if($is_complete == true) {
        $base_info_parent['phase'] = 5;
      } else {
        // ストックはストック用を
        // 施設の親のフェーズを決定する
        if ($chk_times==0) {
          $min_phase=$this->getPhaseParentStock($sno);
        } else{
          $min_phase=$this->getPhaseParent($sno, $chk_times);
        }
        $base_info_parent['phase'] = $min_phase;
      }
      // 親データの基本情報を保存
      $this->set_baseinfo($base_info_parent);
      // 親データの部材情報を保存
      $this->set_chkdata($base_info_parent, $check_data_parent);
    }

    /**
     * 引数の点検管理番号において、最大の履歴番号を取得する
     *
     * @param $chk_mng_no  点検管理番号
     * @return
     */
    public function getLatestRirekiNoBuzai($chk_mng_no) {

        $sql = <<<EOF
select
    max(rireki_no) max_rireki_no
from
  rfs_t_chk_buzai
where
  chk_mng_no = $chk_mng_no
EOF;
        $query = $this->rfs->query($sql);
        return $query->result_array();
    }

    // 防雪柵親データの施設シリアル番号を取得
    public function get_bssk_parent_snos() {

        $sql = <<<EOF
        select
          tcm.sno
          , tcm.chk_mng_no
          , tcm.chk_times
          , tcm.struct_idx
        from rfs_t_chk_main tcm
        left join rfs_m_shisetsu ms
          on ms.sno = tcm.sno
        where true
          and ms.shisetsu_kbn = 4
          and tcm.chk_mng_no < 1000000
          and tcm.struct_idx = 0
--        limit 1
EOF;
        $query = $this->rfs->query($sql);
        return $query->result_array();
    }

    // 防雪柵親データの点検管理番号を取得
    public function get_bssk_parent_by_sno($sno) {

        $sql = <<<EOF
        select
          tcm.sno
          , tcm.chk_mng_no
          , tcm.chk_times
          , tcm.struct_idx
        from
          (
            select
              chk_mng_no
              , sno
              , struct_idx
              , chk_times
            from
              rfs_t_chk_main tcm1
            where not exists
              (
                select 1
                from rfs_t_chk_main tcm2
                where
                  tcm1.sno = tcm2.sno
                  and tcm1.chk_times < tcm2.chk_times
                  and tcm1.struct_idx = tcm2.struct_idx
              )
              and struct_idx = 0
          ) tcm
        left join rfs_m_shisetsu ms
          on ms.sno = tcm.sno
        where true
          and ms.shisetsu_kbn = 4
          and tcm.struct_idx = 0
          and tcm.sno = $sno
EOF;
        $query = $this->rfs->query($sql);
        return $query->result_array();
    }

  /**
     * 防雪柵の子データのchk_mng_noから親のデータを取得する
     *
     * @param $chk_mng_no  点検管理番号
     * @return
     */
    public function get_bssk_parent_by_chk_mng_no($chk_mng_no){
      $sql = <<<SQL
        select
          parent.sno
          , parent.chk_mng_no
          , parent.chk_times
          , parent.struct_idx
        from
          rfs_t_chk_main child
          inner join rfs_t_chk_main parent
            on child.sno = parent.sno
            and child.chk_times = parent.chk_times
            and parent.struct_idx = 0
            and child.chk_mng_no = $chk_mng_no
          left join rfs_m_shisetsu ms
            on ms.sno = parent.sno
        where
          ms.shisetsu_kbn = 4
SQL;
      $query = $this->rfs->query($sql);
      return $query->result_array();
    }

    // 防雪柵子データを取得
    public function get_bssk_childeren($sno, $is_stock = true) {

        if($is_stock) {
            $sql = <<<EOF
            select
              mbs.sno
              , tcm.chk_mng_no
              , mbs.struct_idx
            from
              rfs_m_shisetsu ms
              left join rfs_m_bousetsusaku_shichu mbs
                on ms.sno = mbs.sno
              left join
                (
                  select
                    chk_mng_no
                    , sno
                    , struct_idx
                  from
                    rfs_t_chk_main tcm1
                  where not exists
                    (
                      select 1
                      from rfs_t_chk_main tcm2
                      where
                        tcm1.sno = tcm2.sno
                        and tcm1.chk_times > tcm2.chk_times
                        and tcm1.struct_idx = tcm2.struct_idx
                        and tcm1.chk_mng_no > tcm2.chk_mng_no
                    )
                    and struct_idx != 0
                ) tcm
                on mbs.sno = tcm.sno
                and mbs.struct_idx = tcm.struct_idx
              where
                mbs.struct_idx != 0
                and ms.sno = $sno
                and ms.shisetsu_kbn = 4
              order by
                ms.shisetsu_cd
EOF;
        } else {
            $sql = <<<EOF
            select
              mbs.sno
              , tcm.chk_mng_no
              , mbs.struct_idx
            from
              rfs_m_shisetsu ms
              left join rfs_m_bousetsusaku_shichu mbs
                on ms.sno = mbs.sno
              left join
                (
                  select
                    chk_mng_no
                    , sno
                    , struct_idx
                  from
                    rfs_t_chk_main tcm1
                  where not exists
                    (
                      select 1
                      from rfs_t_chk_main tcm2
                      where
                        tcm1.sno = tcm2.sno
                        and tcm1.chk_times < tcm2.chk_times
                        and tcm1.struct_idx = tcm2.struct_idx
                        and tcm1.chk_mng_no < tcm2.chk_mng_no
                    )
                    and struct_idx != 0
                ) tcm
                on mbs.sno = tcm.sno
                and mbs.struct_idx = tcm.struct_idx
              where
                mbs.struct_idx != 0
                and ms.sno = $sno
                and ms.shisetsu_kbn = 4
              order by
                ms.shisetsu_cd
EOF;
        }
        $query = $this->rfs->query($sql);
        return $query->result_array();
    }

    public function get_baseinfo_by_chkmngno($get){
        log_message('debug', 'get_baseinfo_by_chkmngno');

        $chk_mng_no = $get['chk_mng_no'];
        $sno = $get['sno'];
        $struct_idx = $get['struct_idx'];

            $sql= <<<EOF
            select
                s.sno
              , s.shisetsu_cd
              , s.shisetsu_ver
              , s.shisetsu_kbn
              , s.dogen_cd
              , msk.shisetsu_kbn_nm
              , s.shisetsu_keishiki_cd
              , mske.shisetsu_keishiki_nm
              , tcm.chk_mng_no
              , tcm.chk_times
              , case
                when tch.rireki_no is null then
                  0
                else
                  tch.rireki_no
                end as rireki_no
              , tcm.struct_idx
              , tcm.target_dt
              , s.rosen_cd
              , r.rosen_nm
              , s.shityouson
              , s.azaban
              , s.lat
              , s.lon
              , s.dogen_cd
              , d.dogen_mei
              , s.syucchoujo_cd
              , ms.syucchoujo_mei
              , s.substitute_road
              , case
                  when s.substitute_road = 0
                  then '有'
                  else '無'
                  end as substitute_road_str
              , s.emergency_road
              , case
                  when s.emergency_road is null
                  then ''
                  else '第' || s.emergency_road || '次'
                  end as emergency_road_str
              , s.motorway
              , case
                  when s.motorway = 0
                  then '自専道'
                  when s.motorway = 1
                  then '一般道'
                  else ''
                  end as motorway_str
              , s.senyou
              , to_char(tch.chk_dt,'YYYY-MM-DD') as chk_dt
              , tch.chk_company
              , tch.chk_person
              , to_char(tch.investigate_dt,'YYYY-MM-DD') as investigate_dt
              , tch.investigate_company
              , tch.investigate_person
              , case
                  when tch.surface is null
                  then 0
                  else tch.surface
                  end as surface
              , tch.part_notable_chk
              , tch.reason_notable_chk
              , tch.special_report
              , case
                  when tch.phase is null
                  then 1
                  else tch.phase
                  end as phase
              , tch.syoken
              , tch.update_dt
              , case
                  when tch.check_shisetsu_judge is null
                  then 0
                  else tch.check_shisetsu_judge
                  end as check_shisetsu_judge
              , case
                  when tch.measures_shisetsu_judge is null
                  then 0
                  else tch.measures_shisetsu_judge
                  end as measures_shisetsu_judge
              , s.sp
--              , case
--                  when s.lr = 0
--                  then '左'
--                  else '右'
--                  end as lr
              , CASE
                  WHEN s.lr = 0
                  THEN '左'
                  WHEN s.lr = 1
                  THEN '右'
                  WHEN s.lr = 2
                  THEN '中央'
                  WHEN s.lr = 3
                  THEN '左右'
                  ELSE '-'
                  END lr
              , case
                when tch.chk_mng_no is null then
                  'true'
                else
                  'false'
                end as is_new_data
              , case when tch.create_account is null then
                  0
                else
                  tch.create_account
                end as create_account
              , case when mp.phase_str_large is null then
                  '点検'
                else
                  mp.phase_str_large
                end as phase_str_large

            from
              rfs_m_shisetsu s
              left join rfs_t_chk_main tcm
                on s.sno = tcm.sno
              left join rfs_m_shisetsu_kbn msk
                on s.shisetsu_kbn = msk.shisetsu_kbn
              left join rfs_m_shisetsu_keishiki mske
                on s.shisetsu_kbn = mske.shisetsu_kbn
                and s.shisetsu_keishiki_cd = mske.shisetsu_keishiki_cd
              left join rfs_m_rosen r
                on s.rosen_cd = r.rosen_cd
              left join rfs_m_dogen d
                on s.dogen_cd = d.dogen_cd
              left join rfs_m_syucchoujo ms
                on s.syucchoujo_cd = ms.syucchoujo_cd
              left join
                (
                  select
                    distinct on (chk_mng_no) *
                  from
                    rfs_t_chk_huzokubutsu tch1
                  where not exists
                    (
                      select 1
                      from rfs_t_chk_huzokubutsu tch2
                      where
                        tch1.chk_mng_no = tch2.chk_mng_no
                        and tch1.rireki_no < tch2.rireki_no
                    )
                ) tch
                on tcm.chk_mng_no = tch.chk_mng_no
              left join rfs_m_phase mp
                on tch.phase = mp.phase

            where
              s.sno = $sno
              and tcm.chk_times = (
                select
                    max(chk_times)
                from
                  rfs_t_chk_main
                where
                  chk_mng_no = $chk_mng_no
              )
              and tcm.chk_mng_no = $chk_mng_no

            order by
              struct_idx;
EOF;

        $query = $this->rfs->query($sql);
        $result = $query->result('array');
        return $result;
    }

    public function get_chkdata_tmp($shisetsu_kbn, $chk_mng_no, $rireki_no) {
        log_message('debug', 'get_chkdata');

        $sql= <<<EOF
select
    buzai.shisetsu_kbn
  , sk.shisetsu_kbn_nm
  , (
      select
        case when count(*) = 0
        then 'true'
        else 'false'
        end as is_new
      from
        rfs_t_chk_buzai tcb
      where
        chk_mng_no = $chk_mng_no
        and rireki_no = 0
    ) is_new
  , jsonb_set(
    '{}'
    , '{buzai}'
    , jsonb_agg(
      to_jsonb("buzai") - 'shisetsu_kbn'
      order by
        buzai_cd
    )
  ) as buzai_row
from
  (
    select
        buzai_detail.shisetsu_kbn
      , buzai_detail.buzai_cd
      , b.buzai_nm
      , case
        when tcb.check_buzai_judge is null
        then 0
        else tcb.check_buzai_judge
        end as check_buzai_judge
      , case
        when tcb.measures_buzai_judge is null
        then 0
        else tcb.measures_buzai_judge
        end as measures_buzai_judge
      , case
        when tcb.hantei1 is null
        then ''
        else tcb.hantei1
        end as hantei1
      , case
        when tcb.hantei2 is null
        then ''
        else tcb.hantei2
        end as hantei2
      , case
        when tcb.hantei3 is null
        then ''
        else tcb.hantei3
        end as hantei3
      , case
        when tcb.hantei4 is null
        then ''
        else tcb.hantei4
        end as hantei4
      , jsonb_agg(
        to_jsonb("buzai_detail") - 'shisetsu_kbn' - 'buzai_cd' - 'check_buzai_judge' - 'measures_buzai_judge'
        - 'hantei1' - 'hantei2' - 'hantei3' - 'hantei4'
        order by
          buzai_detail_cd
      )     as buzai_detail_row
    from
      (
        select
            tenken_kasyo.shisetsu_kbn
          , tenken_kasyo.buzai_cd
          , tenken_kasyo.buzai_detail_cd
          , bd.buzai_detail_nm
          , bd.sample_url
          , jsonb_agg(
--            to_jsonb("tenken_kasyo") - 'shisetsu_kbn' - 'buzai_cd' - 'buzai_detail_cd'
            to_jsonb("tenken_kasyo")
            order by
              tenken_kasyo_cd
          )     as tenken_kasyo_row
        from
          (
            select
                sonsyou_naiyou.shisetsu_kbn
              , sonsyou_naiyou.buzai_cd
              , sonsyou_naiyou.buzai_detail_cd
              , sonsyou_naiyou.tenken_kasyo_cd
              , sonsyou_naiyou.tenken_kasyo_nm
              , sonsyou_naiyou.sign
--              , tctk.chk_mng_no
              , case
                when tctk.chk_mng_no is null
                then $chk_mng_no
                else tctk.chk_mng_no
                end as chk_mng_no
--              , tctk.rireki_no
              , case
                when tctk.rireki_no is null
                then $rireki_no
                else tctk.rireki_no
                end as rireki_no
              , case
                when tctk.sonsyou_naiyou_cd is null
                then 0
                else tctk.sonsyou_naiyou_cd
                end as sonsyou_naiyou_cd
              , case
                when tctk.taisyou_umu is null
                then true
                when tctk.taisyou_umu = 0
                then true
                else false
                end as taisyou_umu
              , tctk.check_status
              , case
                when tctk.check_judge is null
                then 1
                when tctk.check_judge = -1
                then 1
                else tctk.check_judge
                end as check_judge
              , check_judge as check_judge_pre
              , case
                when tctk.measures_judge is null
                then 0
                when tctk.measures_judge = -1
                then 0
                else tctk.measures_judge
                end as measures_judge
              , case
                when tctk.screening is null
                then 0
                when tctk.screening = -1
                then 0
                else tctk.screening
                end as screening
              , case
                when tctk.screening = 1
                then true
                else false
                end as screening_flg
              , case
                when tctk.screening_taisyou is null
                then 0
                when tctk.screening_taisyou = -1
                then 0
                else tctk.screening_taisyou
                end as screening_taisyou
              , case
                when tctk.check_policy is null
                then 0
                else tctk.check_policy
                end as check_policy
              , case
                when tctk.check_policy = 0
                then '－'
                when tctk.check_policy = 1
                then 'スクリーニング'
                when tctk.check_policy = 2
                then '詳細調査'
                else '－'
                end as check_policy_str
              , case
                when tctk.measures_policy is null
                then ''
                else tctk.measures_policy
                end as measures_policy
              , to_char(tctk.measures_dt,'YYYY-MM-DD') as measures_dt
--              , case
--                when tctk.picture_cd_before is null
--                then 0
--                when tctk.picture_cd_before = -1
--                then 0
--                else tctk.picture_cd_before
--                end as picture_cd_before
--              , case
--                when tctk.picture_cd_after is null
--                then 0
--                when tctk.picture_cd_after = -1
--                then 0
--                else tctk.picture_cd_after
--                end as picture_cd_after
              , case
                when tctk.check_bikou is null
                then ''
                else tctk.check_bikou
                end as check_bikou
              , case
                when tctk.measures_bikou is null
                then ''
                else tctk.measures_bikou
                end as measures_bikou
              , jsonb_agg(
                to_jsonb("sonsyou_naiyou") - 'shisetsu_kbn' - 'buzai_cd' - 'buzai_detail_cd' - 'tenken_kasyo_cd' - 'tenken_kasyo_nm'
                 - 'sign' - 'check_policy' - 'measures_policy' - 'measures_dt'
                 - 'check_bikou' - 'measures_bikou' - 'screening'
                order by
                  sonsyou_naiyou.sonsyou_naiyou_cd
              )      as sonsyou_naiyou_row
            from
              (
                select
                    mcs.shisetsu_kbn
                  , mcs.buzai_cd
                  , mcs.buzai_detail_cd
                  , mcs.tenken_kasyo_cd
                  , tk.tenken_kasyo_nm
                  , tk.sign
                  , mcs.sonsyou_naiyou_cd
                  , sn.sonsyou_naiyou_nm
                  , case
                    when dat.check_before is null
                    then 1
                    else dat.check_before
                    end as check_before
                  , case
                    when dat.measures_after is null
                    then 0
                    else dat.measures_after
                    end as measures_after
                from
                  rfs_m_chk_sonsyou mcs
                  left join (
                    select
                        tcs.chk_mng_no
                      , shisetsu.shisetsu_kbn
                      , tcs.buzai_cd
                      , tcs.buzai_detail_cd
                      , tcs.tenken_kasyo_cd
                      , tcs.sonsyou_naiyou_cd
                      , case
                        when tcs.check_before is null
                        then 1
                        when tcs.check_before = -1
                        then 1
                        else tcs.check_before
                        end as check_before
                      , case
                        when tcs.measures_after is null
                        then 0
                        when tcs.measures_after = -1
                        then 0
                        else tcs.measures_after
                        end as measures_after
                    from
                      (
                        select
                            *
                        from
                          rfs_t_chk_sonsyou
                        where
                          chk_mng_no = $chk_mng_no
                          and rireki_no = $rireki_no
                      ) tcs
                      left join (
                        select
                            s.shisetsu_kbn
                          , cm.chk_mng_no
                        from
                          rfs_m_shisetsu s join rfs_t_chk_main cm
                            on s.sno = cm.sno
                        where
                          cm.chk_mng_no = $chk_mng_no
                      ) shisetsu
                        on tcs.chk_mng_no = shisetsu.chk_mng_no
                  ) dat
                    on mcs.shisetsu_kbn = dat.shisetsu_kbn
                    and mcs.buzai_cd = dat.buzai_cd
                    and mcs.buzai_detail_cd = dat.buzai_detail_cd
                    and mcs.tenken_kasyo_cd = dat.tenken_kasyo_cd
                    and mcs.sonsyou_naiyou_cd = dat.sonsyou_naiyou_cd
                  left join rfs_m_tenken_kasyo tk
                    on mcs.shisetsu_kbn = tk.shisetsu_kbn
                    and mcs.buzai_cd = tk.buzai_cd
                    and mcs.buzai_detail_cd = tk.buzai_detail_cd
                    and mcs.tenken_kasyo_cd = tk.tenken_kasyo_cd
                  left join rfs_m_sonsyou_naiyou sn
                    on mcs.sonsyou_naiyou_cd = sn.sonsyou_naiyou_cd
                where
                  mcs.shisetsu_kbn = $shisetsu_kbn
              ) sonsyou_naiyou
              left join (
                select
                    *
                from
                  rfs_t_chk_tenken_kasyo
                where
                  chk_mng_no = $chk_mng_no
                  and rireki_no = $rireki_no
              ) tctk
                on sonsyou_naiyou.buzai_cd = tctk.buzai_cd
                and sonsyou_naiyou.buzai_detail_cd = tctk.buzai_detail_cd
                and sonsyou_naiyou.tenken_kasyo_cd = tctk.tenken_kasyo_cd
            group by
              sonsyou_naiyou.shisetsu_kbn
              , sonsyou_naiyou.buzai_cd
              , sonsyou_naiyou.buzai_detail_cd
              , sonsyou_naiyou.tenken_kasyo_cd
              , sonsyou_naiyou.tenken_kasyo_nm
              , sonsyou_naiyou.sign
              , tctk.chk_mng_no
              , tctk.rireki_no
              , tctk.sonsyou_naiyou_cd
              , tctk.taisyou_umu
              , tctk.check_status
              , tctk.check_judge
              , tctk.measures_judge
              , tctk.screening
              , tctk.screening_taisyou
              , tctk.check_policy
              , tctk.measures_policy
              , tctk.measures_dt
--              , tctk.picture_cd_before
--              , tctk.picture_cd_after
              , tctk.check_bikou
              , tctk.measures_bikou
          ) tenken_kasyo
          left join rfs_m_buzai_detail bd
            on tenken_kasyo.shisetsu_kbn = bd.shisetsu_kbn
            and tenken_kasyo.buzai_cd = bd.buzai_cd
            and tenken_kasyo.buzai_detail_cd = bd.buzai_detail_cd
        group by
          tenken_kasyo.shisetsu_kbn
          , tenken_kasyo.buzai_cd
          , tenken_kasyo.buzai_detail_cd
          , bd.buzai_detail_nm
          , bd.sample_url
      ) buzai_detail
      left join rfs_m_buzai b
        on buzai_detail.shisetsu_kbn = b.shisetsu_kbn
        and buzai_detail.buzai_cd = b.buzai_cd
      left join (
        select
            *
        from
          rfs_t_chk_buzai
        where
          chk_mng_no = $chk_mng_no
          and rireki_no = $rireki_no
      ) tcb
        on buzai_detail.buzai_cd = tcb.buzai_cd
    group by
      buzai_detail.shisetsu_kbn
      , buzai_detail.buzai_cd
      , b.buzai_nm
      , tcb.check_buzai_judge
      , tcb.measures_buzai_judge
      , tcb.hantei1
      , tcb.hantei2
      , tcb.hantei3
      , tcb.hantei4
  ) buzai
  left join rfs_m_shisetsu_kbn sk
    on buzai.shisetsu_kbn = sk.shisetsu_kbn
group by
  buzai.shisetsu_kbn
  , sk.shisetsu_kbn_nm;
EOF;
        $query = $this->rfs->query($sql);
        $result = $query->result('array');
        return $result;
    }

    public function set_baseinfo($baseinfo) {
        log_message('debug', 'set_baseinfo');

        // JSONデータを分解し、連想配列型に格納する
        $json = $baseinfo;

        $chk_mng_no = $json['chk_mng_no'];
        $rireki_no = $json['rireki_no'];
        $chk_dt = isset($json['chk_dt']) ? pg_escape_literal($json['chk_dt']) : "NULL";
        $chk_company =  pg_escape_literal($json['chk_company']);
        $chk_person =  pg_escape_literal($json['chk_person']);
        $investigate_dt = isset($json['investigate_dt']) ? pg_escape_literal($json['investigate_dt']) : "NULL";
        $investigate_company =  pg_escape_literal($json['investigate_company']);
        $investigate_person =  pg_escape_literal($json['investigate_person']);
        $surface = intval($json['surface']);
        $part_notable_chk = pg_escape_literal($json['part_notable_chk']);
        $reason_notable_chk = pg_escape_literal($json['reason_notable_chk']);
        $special_report = pg_escape_literal($json['special_report']);
        $phase = $json['phase'];
        $check_shisetsu_judge = intval($json['check_shisetsu_judge']);
        $syoken = pg_escape_literal($json['syoken']);
        $update_dt = pg_escape_literal(date('Y-m-d H:i:s'));
        $measures_shisetsu_judge = intval($json['measures_shisetsu_judge']);
        $create_account = $json['create_account'];

        // [点検データ_附属物]テーブルの追記
        $sql= <<<EOF
        insert
          into rfs_t_chk_huzokubutsu
        values(
          $chk_mng_no
          , $rireki_no
          , $chk_dt
          , $chk_company
          , $chk_person
          , $investigate_dt
          , $investigate_company
          , $investigate_person
          , $surface
          , $part_notable_chk
          , $reason_notable_chk
          , $special_report
          , $phase
          , $check_shisetsu_judge
          , $syoken
          , $update_dt
          , $measures_shisetsu_judge
          , $create_account
        )
        ON CONFLICT ON CONSTRAINT rfs_t_chk_huzokubutsu_pkey
        DO UPDATE SET
          phase = $phase
          , chk_dt = $chk_dt
          , investigate_dt = $investigate_dt
          , update_dt = $update_dt
          , chk_company = $chk_company
          , chk_person = $chk_person
          , investigate_company = $investigate_company
          , investigate_person = $investigate_person
          , surface = $surface
          , part_notable_chk = $part_notable_chk
          , reason_notable_chk = $reason_notable_chk
          , special_report = $special_report
          , check_shisetsu_judge = $check_shisetsu_judge
          , syoken = $syoken
          , measures_shisetsu_judge = $measures_shisetsu_judge
          , create_account = $create_account
EOF;

            $query = $this->rfs->query($sql);
    }

    // hirano 子データキーセット
    public function set_bs_child_dat($sno,$chk_times,$struct_idx) {

        // 子データの点検管理番号
        $bs_child_arr=$this->get_chk_main_bs($sno,$chk_times,$struct_idx);

        // 該当の点検管理情報が無い場合子データが点検対象ではないということなので、
        // この場合は基本情報のみのexcelは作成しない
        if(count($bs_child_arr) == 0) {
            $bs_child=$bs_child_arr[0];
            $this->tmp_chk_mng_no_bs="";
            $this->tmp_struct_idx_bs=$struct_idx;
            $this->tmp_chk_times_bs=$chk_times;
            return false;
        }
        $bs_child=$bs_child_arr[0];
        $this->tmp_chk_mng_no_bs=$bs_child['chk_mng_no'];
        $this->tmp_struct_idx_bs=$bs_child['struct_idx'];
        $this->tmp_chk_times_bs=$bs_child['chk_times'];

        return true;
    }

    // hirano 防雪柵子データの点検管理情報を取得する
    public function get_chk_main_bs($sno,$chk_times,$struct_idx) {

        $sql = <<<EOF
    select
        *
    from
      rfs_t_chk_main
    where
      sno = $sno
      and chk_times = $chk_times
      and struct_idx = $struct_idx
EOF;

        $query = $this->rfs->query($sql);
        return $query->result_array();
    }

    // hirano 点検管理情報を取得する
    public function get_chk_main($sno,$chk_times) {

        $sql = <<<EOF
    select
        *
    from
      rfs_t_chk_main
    where
      sno = $sno
      and chk_times = $chk_times
      and struct_idx = 0
EOF;
        $query = $this->rfs->query($sql);
        return $query->result_array();
    }

    public function set_chkdata($baseinfo, $buzai) {
        log_message('debug', 'set_chkdata');

        // 点検管理番号、履歴番号
        $chk_mng_no = $baseinfo['chk_mng_no'];
        $rireki_no = $baseinfo['rireki_no'];

        foreach($buzai as $buzai_row) {
            $buzai_cd = intval($buzai_row['buzai_cd']);
            $check_buzai_judge = intval($buzai_row['check_buzai_judge']);
            $necessity_measures = 2;
            if($check_buzai_judge > 2) {
                $necessity_measures = 1;
            }
            $measures_buzai_judge = intval($buzai_row['measures_buzai_judge']);
            $hantei1 = pg_escape_literal($buzai_row['hantei1']);
            $hantei2 = pg_escape_literal($buzai_row['hantei2']);
            $hantei3 = pg_escape_literal($buzai_row['hantei3']);
            $hantei4 = pg_escape_literal($buzai_row['hantei4']);

            // 一時保存：最初の保存以降は、同じPKなので必ずUPDATEとなる
            // 確定保存：履歴NOが+1されているので、必ずINSERTとなる

            // [点検データ_部材]テーブルの更新
            $sql= <<<EOF
            insert into rfs_t_chk_buzai (
              chk_mng_no
              , rireki_no
              , buzai_cd
              , necessity_measures
              , check_buzai_judge
              , hantei1
              , hantei2
              , hantei3
              , hantei4
              , measures_buzai_judge
            )
            values (
              $chk_mng_no
              , $rireki_no
              , $buzai_cd
              , $necessity_measures
              , $check_buzai_judge
              , $hantei1
              , $hantei2
              , $hantei3
              , $hantei4
              , $measures_buzai_judge
            )
            ON CONFLICT ON CONSTRAINT rfs_t_chk_buzai_pkey
            DO UPDATE SET
              necessity_measures = $necessity_measures
              , check_buzai_judge = $check_buzai_judge
              , measures_buzai_judge = $measures_buzai_judge
              , hantei1 = $hantei1
              , hantei2 = $hantei2
              , hantei3 = $hantei3
              , hantei4 = $hantei4;
EOF;

            $query = $this->rfs->query($sql);

            foreach($buzai_row['buzai_detail_row'] as $buzai_detail_row) {
                // 部材詳細コード
                $buzai_detail_cd = intval($buzai_detail_row['buzai_detail_cd']);
                foreach($buzai_detail_row['tenken_kasyo_row'] as $key => $tenken_kasyo_row) {
                    $tenken_kasyo_cd = intval($tenken_kasyo_row['tenken_kasyo_cd']);
                    $check_status = intval($tenken_kasyo_row['check_status']);
                    foreach($tenken_kasyo_row['sonsyou_naiyou_row'] as $key => $sonsyou_naiyou_row) {
                        $sonsyou_naiyou_cd = intval($sonsyou_naiyou_row['sonsyou_naiyou_cd']);
                        $check_before = intval($sonsyou_naiyou_row['check_before']);
                        $measures_after = intval($sonsyou_naiyou_row['measures_after']);

                        // [点検データ_損傷内容]テーブルの追記
                        $sql= <<<EOF
                        insert into rfs_t_chk_sonsyou
                        values(
                          $chk_mng_no
                          , $rireki_no
                          , $buzai_cd
                          , $buzai_detail_cd
                          , $tenken_kasyo_cd
                          , $sonsyou_naiyou_cd
                          , $check_before
                          , $measures_after
                        )
                        ON CONFLICT ON CONSTRAINT rfs_t_chk_sonsyou_pkey
                        DO UPDATE SET
                          check_before = $check_before
                          , measures_after = $measures_after
EOF;
                        $query = $this->rfs->query($sql);
                    }
                    $taisyou_umu = ($tenken_kasyo_row['taisyou_umu']) ? 0 : 1;
                    // 対象の有無が無しの場合、入力項目は全て無視し、備考に'点検対象無し'を保存
                    if($taisyou_umu == 1) {
                        $check_bikou = pg_escape_literal('点検対象無し');
                        $measures_bikou = pg_escape_literal('点検対象無し');
                    } else {
                        $check_bikou = pg_escape_literal($tenken_kasyo_row['check_bikou']);
                        $measures_bikou = pg_escape_literal($tenken_kasyo_row['measures_bikou']);
                    }
                    $sonsyou_naiyou_cd = intval($tenken_kasyo_row['sonsyou_naiyou_cd']);
                    $check_judge = intval($tenken_kasyo_row['check_judge']);
                    $measures_judge = intval($tenken_kasyo_row['measures_judge']);
                    $measures_policy = pg_escape_literal($tenken_kasyo_row['measures_policy']);
                    $_measures_dt_insert =  ', NULL';
                    $_measures_dt_update =  ', measures_dt = NULL';
                    if(isset($tenken_kasyo_row['measures_dt'])) {
                        $_measures_dt_insert =  ', ' . pg_escape_literal($tenken_kasyo_row['measures_dt']);
                        $_measures_dt_update =  ', measures_dt = ' . pg_escape_literal($tenken_kasyo_row['measures_dt']);
                    }
                    $screening = intval($tenken_kasyo_row['screening']);
                    $check_policy = $tenken_kasyo_row['check_policy'];
                    $screening_taisyou = intval($tenken_kasyo_row['screening_taisyou']);

                    // [点検データ_点検箇所]テーブルの追記
                    $sql= <<<EOF
                    insert into rfs_t_chk_tenken_kasyo
                    values(
                      $chk_mng_no
                      , $rireki_no
                      , $buzai_cd
                      , $buzai_detail_cd
                      , $tenken_kasyo_cd
                      , $taisyou_umu
                      , $check_status
                      , $sonsyou_naiyou_cd
                      , $check_judge
                      , $measures_judge
                      , $measures_policy
                      $_measures_dt_insert
                      , $check_bikou
                      , $measures_bikou
                      , $screening
                      , $check_policy
                      , $screening_taisyou
                    )
                    ON CONFLICT ON CONSTRAINT rfs_t_chk_tenken_kasyo_pkey
                    DO UPDATE SET
                      taisyou_umu = $taisyou_umu
                      , check_status = $check_status
                      , sonsyou_naiyou_cd = $sonsyou_naiyou_cd
                      , check_judge = $check_judge
                      , measures_judge = $measures_judge
                      , measures_policy = $measures_policy
                      $_measures_dt_update
                      , check_bikou = $check_bikou
                      , measures_bikou = $measures_bikou
                      , screening = $screening
                      , check_policy = $check_policy
                      , screening_taisyou = $screening_taisyou
EOF;

                    $query = $this->rfs->query($sql);
                }
            }
        }
    }

  /***
   * 20170325 追加仕様(漏れ)
   *
   * 防雪柵親データのフェーズを確定する
   * この処理を通るときは、データを保存する時。
   * 施設内で最新の点検回数のデータに対して行う。
   *
   * 親のフェーズは子データの集約となるが、
   * 全ての子データの中で、一番フェーズが若い者を
   * 親データのフェーズとする。
   *
   * また、ここは防雪柵子データ保存後の処理なので
   * 必ず一つはphaseがある
   ***/
  protected function getPhaseParent($sno,$chk_times) {

    $sql= <<<EOF
SELECT
  min(h.phase) min_phase
FROM
  (
    SELECT
        s1.*
    FROM
      rfs_m_shisetsu s1 JOIN (
        SELECT
            shisetsu_cd
          , max(shisetsu_ver) shisetsu_ver
        FROM
          rfs_m_shisetsu
        GROUP BY
          shisetsu_cd
      ) s2
        ON s1.shisetsu_cd = s2.shisetsu_cd
        AND s1.shisetsu_ver = s2.shisetsu_ver
    WHERE
      sno = $sno
  ) s
  LEFT JOIN (
--    SELECT
--        c1.*
--    FROM
--      rfs_t_chk_main c1 JOIN (
--        SELECT
--            sno
--          , max(chk_times) chk_times
--        FROM
--          rfs_t_chk_main
--        GROUP BY
--          sno
--      ) c2
--        ON c1.sno = c2.sno
--        AND c1.chk_times = c2.chk_times
--    WHERE
--      struct_idx > 0

-- 点検回数の最大ではなく引数の点検回数に変更
SELECT
* 
FROM
rfs_t_chk_main 
WHERE
struct_idx > 0 
AND sno = $sno
AND chk_times = $chk_times

  ) c
    ON s.sno = c.sno
  LEFT JOIN (
    SELECT
        h1.*
    FROM
      rfs_t_chk_huzokubutsu h1 JOIN (
        SELECT
            chk_mng_no
          , max(rireki_no) rireki_no
        FROM
          rfs_t_chk_huzokubutsu
        GROUP BY
          chk_mng_no
      ) h2
        ON h1.chk_mng_no = h2.chk_mng_no
        AND h1.rireki_no = h2.rireki_no
  ) h
    ON c.chk_mng_no = h.chk_mng_no
EOF;

    $query = $this->rfs->query($sql);
    $result = $query->result('array');
    return $result[0]['min_phase'];

  }

  /***
   * 20170803 ストック用
   * 防雪柵親データのフェーズを確定する
   * この処理を通るときは、データを保存する時。
   * 施設内でchk_timesが0(ストック)のデータに対して行う。
   *
   * 親のフェーズは子データの集約となるが、
   * 全ての子データの中で、一番フェーズが若い者を
   * 親データのフェーズとする。
   *
   * また、ここは防雪柵子データ保存後の処理なので
   * 必ず一つはphaseがある
   ***/
  protected function getPhaseParentStock($sno) {

    $sql= <<<EOF
SELECT
  min(h.phase) min_phase
FROM
  (
    SELECT
        s1.*
    FROM
      rfs_m_shisetsu s1 JOIN (
        SELECT
            shisetsu_cd
          , max(shisetsu_ver) shisetsu_ver
        FROM
          rfs_m_shisetsu
        GROUP BY
          shisetsu_cd
      ) s2
        ON s1.shisetsu_cd = s2.shisetsu_cd
        AND s1.shisetsu_ver = s2.shisetsu_ver
    WHERE
      sno = $sno
  ) s
  LEFT JOIN (
    SELECT
        *
    FROM
      rfs_t_chk_main
    WHERE
    chk_times = 0
    AND
      struct_idx > 0
  ) c
    ON s.sno = c.sno
  LEFT JOIN (
    SELECT
        h1.*
    FROM
      rfs_t_chk_huzokubutsu h1 JOIN (
        SELECT
            chk_mng_no
          , max(rireki_no) rireki_no
        FROM
          rfs_t_chk_huzokubutsu
        GROUP BY
          chk_mng_no
      ) h2
        ON h1.chk_mng_no = h2.chk_mng_no
        AND h1.rireki_no = h2.rireki_no
  ) h
    ON c.chk_mng_no = h.chk_mng_no
EOF;

    $query = $this->rfs->query($sql);
    $result = $query->result('array');
    return $result[0]['min_phase'];
  }

  private function getChildChk($sno,$chk_times){
    $sql= <<<EOF
    WITH chk_main AS ( 
      SELECT
            * 
        FROM
          rfs_t_chk_main 
        WHERE
          sno = $sno 
          AND chk_times = $chk_times
    ) 
    SELECT
          count(*) cnt 
      FROM
        rfs_t_chk_huzokubutsu h JOIN chk_main cm 
          ON h.chk_mng_no = cm.chk_mng_no
EOF;

    $query = $this->rfs->query($sql);
    $result = $query->result('array');
    return $result[0]['cnt'];

  }

}

/**
 * Undefined property エラーを出さないオブジェクト
 */
class SafeObject {
    protected $obj;
    /**
     * @param object $obj
     */
    public function __construct($obj) {
        $this->obj = $obj;
    }
    public function __get($name) {
        if (isset($this->obj->$name)) {
            return $this->obj->$name;
        }
    }
    /**
     * @return bool
     */
    public function is_object() {
        return is_object($this->obj);
    }
}
class RecordNotFoundException extends RuntimeException {
}
