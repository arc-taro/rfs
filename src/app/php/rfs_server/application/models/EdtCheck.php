<?php

/**
 * 点検表の更新を行う
 * 返却は配列
 *
 * @access public
 * @package Model
 */
class EdtCheck extends CI_Model {

    /**
     * コンストラクタ
     *
     * model EdtCheckを初期化する。
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * 基本情報の更新
     *
     * @param $post setクエリ <br>
     *        $post['baseinfo']     基本情報
     * @return
     */
    public function set_baseinfo($post) {
        log_message('debug', 'set_baseinfo');

        if (($post['baseinfo'] == "") || ($post['mode'] == "")) {
            // 終了
            return null;
        }

        $DB_rfs=$this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        // JSONデータを分解し、連想配列型に格納する
        $json = $post['baseinfo'];

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

            $query = $DB_rfs->query($sql);

//            log_message('debug', "sql = " . $sql);
    }

    /**
     * 部材以下データの更新
     *
     * @param $post setクエリ <br>
     *        $post['baseinfo']     基本情報コード
     *        $post['buzaidata']    部材以下データ
     * @return
     */
    public function set_chkdata($post) {
        log_message('debug', 'set_chkdata');

        if (($post['baseinfo'] == "") || ($post['buzaidata'] == "") || ($post['mode'] == "")) {
            // 終了
            return null;
        }

        $DB_rfs=$this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        // 点検管理番号、履歴番号
        $chk_mng_no = $post['baseinfo']['chk_mng_no'];
        $rireki_no = $post['baseinfo']['rireki_no'];

        // JSONデータを分解し、連想配列型に格納する
        $json = $post['buzaidata'];

        // 部材
        $buzai = $json['buzai'];
        foreach($buzai as $key => $buzai_row) {

            $buzai_cd = intval($buzai_row['buzai_cd']);
            $check_buzai_judge = intval($buzai_row['check_buzai_judge']);
            $measures_buzai_judge = intval($buzai_row['measures_buzai_judge']);
            $necessity_measures = 2;
            if($measures_buzai_judge > 2) {
                $necessity_measures = 1;
            }
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

            $query = $DB_rfs->query($sql);

            foreach($buzai_row['buzai_detail_row'] as $key => $buzai_detail_row) {

                // 部材詳細コード
                $buzai_detail_cd = intval($buzai_detail_row['buzai_detail_cd']);
                foreach($buzai_detail_row['tenken_kasyo_row'] as $key => $tenken_kasyo_row) {

                    $tenken_kasyo_cd = intval($tenken_kasyo_row['tenken_kasyo_cd']);
                    $check_status = intval($tenken_kasyo_row['check_status']);
                    foreach($tenken_kasyo_row['sonsyou_naiyou_row'] as $key => $sonsyou_naiyou_row) {

                        $sonsyou_naiyou_cd = intval($sonsyou_naiyou_row['sonsyou_naiyou_cd']);
                        $check_before = intval($sonsyou_naiyou_row['check_before']);
                        $measures_after = intval($sonsyou_naiyou_row['measures_after']);

                        // 点検状況の設定
                        // その他を除き、1項目でも未実施があれば'未'となる
//                        if($sonsyou_naiyou_cd != 9) {
//                            if(($check_before == 0) && ($measures_after == 0)) {
//                                $check_status = 0;
//                            }
//                        }

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

                        $query = $DB_rfs->query($sql);

//                        log_message('debug', "sql=$sql");
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

                    $query = $DB_rfs->query($sql);

//                    log_message('debug', "sql=$sql");
                }
            }
        }
    }

    /**
     * パトロール員名の追加
     *
     * @param $post setクエリ <br>
     *        $post['syozoku_cd']       所属コード（セッションから）
     *        $post['syucchoujo_cd']    出張所コード（セッションから）
     *        $post['busyo_cd']         部署コード（セッションから）
     *        $post['simei']            パトロール員氏名
     * @return
     */
    public function add_patrolin($post) {
        log_message('debug', 'add_patrolin');

        if (($post['syozoku_cd'] == "") || ($post['syucchoujo_cd'] == "") || ($post['busyo_cd'] == "") || ($post['simei'] == "")) {
            // 終了
            return null;
        }

        $DB_imm = $this->load->database('imm',TRUE);
        if ($DB_imm->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        // パトロール員情報
        $syozoku_cd = $post['syozoku_cd'];
        $syucchoujo_cd = $post['syucchoujo_cd'];
        $busyo_cd = $post['busyo_cd'];
        $simei = pg_escape_literal($post['simei']);

/*
        log_message('debug', 'syozoku_cd = ' . $syozoku_cd);
        log_message('debug', 'syucchoujo_cd = ' . $syucchoujo_cd);
        log_message('debug', 'busyo_cd = ' . $busyo_cd);
        log_message('debug', 'simei = ' . $simei);
*/

    // hirano 何かの理由で既に存在する場合は保存しない
        $sql = <<<EOF
        select
      count(*) cnt
        from
          pt_m_patrolin
        where
          syucchoujo_cd = $syucchoujo_cd
          and simei=$simei
EOF;
        $query = $DB_imm->query($sql);
    $cnt_arr = $query->result('array');

    if (cnt_arr[0]['cnt']>0) {
      return;
    }

        // 最大sort_no、patrolin_cdの取得
/*        $sql = <<<EOF
        select
          max(pt.patrolin_cd) as patrolin_cd
          , max(pt.sort_no) as sort_no
        from
          pt_m_patrolin pt
        left join
          ac_m_busyo ac
          on pt.busyo_cd = ac.busyo_cd
        where
          pt.syucchoujo_cd = $syucchoujo_cd
          and pt.busyo_cd = $busyo_cd
EOF;
*/
  $sql = <<<EOF
        select
          max(pt.patrolin_cd) as patrolin_cd
          , max(pt.sort_no) as sort_no
        from
          pt_m_patrolin pt
        where
          pt.syucchoujo_cd = $syucchoujo_cd
EOF;
        $query = $DB_imm->query($sql);
        foreach ($query->result_array() as $row) {
            $patrolin_cd = intval($row['patrolin_cd']) + 1;
            $sort_no = intval($row['sort_no']) + 1;
        }

        // [パトロール員]テーブルの更新
        $sql= <<<EOF
        insert into pt_m_patrolin
        values(
          $syucchoujo_cd
          , $simei
          , $patrolin_cd
          , $sort_no
          , $busyo_cd
        )
EOF;
        $query = $DB_imm->query($sql);

//        log_message('debug', "sql=$sql");
    }

    /**
     * 調査員名の追加
     *
     * @param $post setクエリ <br>
     *        $post['syucchoujo_cd']    出張所コード（セッションから）
     *        $post['busyo_cd']         部署コード（セッションから）
     *        $post['simei']            調査員氏名
     * @return
     */
    public function add_investigator($post) {
        log_message('debug', 'add_investigator');

        if (($post['syucchoujo_cd'] == "") || ($post['simei'] == "")) {
            // 終了
            return null;
        }

        $DB_imm = $this->load->database('imm',TRUE);
        if ($DB_imm->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        // 調査員情報
        $syucchoujo_cd = $post['syucchoujo_cd'];
        $busyo_cd = $post['busyo_cd'];
        $simei = pg_escape_literal($post['simei']);

//        log_message('debug', 'syucchoujo_cd = ' . $syucchoujo_cd);
//        log_message('debug', 'busyo_cd = ' . $busyo_cd);
//        log_message('debug', 'simei = ' . $simei);

        // 最大sort_no、investigator_cdの取得
        $sql = <<<EOF
        select
          max(investigator.investigator_cd) as investigator_cd
          , max(investigator.sort_no) as sort_no
        from
          rfs_m_investigator investigator
        left join
          ac_m_busyo ac
          on ac.busyo_cd = investigator.busyo_cd
        --where
          --investigator.busyo_cd = $busyo_cd
EOF;
        $query = $DB_imm->query($sql);
        foreach ($query->result_array() as $row) {
            $investigator_cd = intval($row['investigator_cd']) + 1;
            $sort_no = intval($row['sort_no']) + 1;
        }

//        log_message('debug', 'investigator_cd = ' . $investigator_cd);
//        log_message('debug', 'sort_no = ' . $sort_no);

        // 調査員テーブルの更新
        $sql= <<<EOF
        insert into rfs_m_investigator
          (busyo_cd, investigator_cd, simei, sort_no)
        values(
          $busyo_cd
          , $investigator_cd
          , $simei
          , $sort_no
        )
EOF;
        $query = $DB_imm->query($sql);

//        log_message('debug', "sql=$sql");
    }

    /**
     * 点検データ_メインへの追加
     *
     * @param $post setクエリ <br>
     *        $post['sno']              施設シリアル番号
     *        $post['create_account']   作成アカウント
     * @return
     */
    public function add_chkdatamain($post) {
        log_message('debug', 'add_chkdatamain');

        if ($post['sno'] == "") {
            // 終了
            return null;
        }

        $DB_rfs=$this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        $sno = $post['sno'];
        $create_account = $post['create_account'];
        $target_dt = pg_escape_literal(date('Y-m-d H:i:s'));

        $sql= <<<EOF
        insert into rfs_t_chk_main
          (chk_mng_no, sno, target_dt)
        values(
          (
            select max(chk_mng_no) +1
            from rfs_t_chk_main
          )
          , $sno
          , $target_dt
        )
EOF;
        $query = $DB_rfs->query($sql);

//        log_message('debug', "sql = " . $sql);

        $sql= <<<EOF
        insert into rfs_t_chk_huzokubutsu
          (chk_mng_no, create_account)
        values(
          (
            select max(chk_mng_no)
            from rfs_t_chk_main
          )
          , $create_account
        )
EOF;
        $query = $DB_rfs->query($sql);

//        log_message('debug', "sql = " . $sql);

        $sql= <<<EOF
        select
          max(chk_mng_no) as last_chk_mng_no
        from
          rfs_t_chk_huzokubutsu
EOF;
        $query = $DB_rfs->query($sql);
        $result = $query->result('array');

//        log_message('debug', "sql = " . $sql);

        return $result;
    }

    /**
     * 附属物データの追加
     *
     * @param $post setクエリ <br>
     *        $post['chk_mng_no']       点検管理番号
     *        $post['create_account']   作成アカウント
     * @return
     */
    public function add_huzokubutsu($post) {
        log_message('debug', 'add_chkdatamain');

        if (($post['chk_mng_no'] == "")) {
            // 終了
            return null;
        }

        $DB_rfs=$this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        $chk_mng_no = $post['chk_mng_no'];
        $create_account = $post['create_account'];

        $sql= <<<EOF
        insert into rfs_t_chk_huzokubutsu
          (chk_mng_no, rireki_no, create_account)
        values(
          $chk_mng_no
          , 0
          , $create_account
        )
EOF;
        $query = $DB_rfs->query($sql);

//        log_message('debug', "sql = " . $sql);
    }

    /**
     * 防雪柵管理情報の追加
     *
     * @param $post setクエリ <br>
     *        $post['chk_mng_no']           点検管理番号
     *        $post['chk_mng_no_struct']    点検管理番号（struct_idx）
     *        $post['shisetsu_cd']          施設コード
     *        $post['shisetsu_ver']         施設バージョン
     *        $post['struct_idx']           支柱インデックス
     * @return
     */
    public function add_bousetsusaku_mng_info($post) {
        log_message('debug', 'add_bousetsusaku_mng_info');

//        if (($post['chk_mng_no'] == "") || ($post['chk_mng_no_struct'] == "") || ($post['shisetsu_cd'] == "") || ($post['shisetsu_ver'] == "") || ($post['struct_idx'] == "")) {
//            // 終了
//            return null;
//        }

        $DB_rfs=$this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        $chk_mng_no = pg_escape_literal($post['chk_mng_no']);
        $chk_mng_no_struct = pg_escape_literal($post['chk_mng_no_struct']);

        $sql= <<<EOF
        insert into rfs_t_chk_bousetsusaku
        select
          $chk_mng_no
          , $chk_mng_no_struct
          , 0
        where not exists
          (
            select 1
            from rfs_t_chk_bousetsusaku tcb
            where
              (
                tcb.chk_mng_no = $chk_mng_no
                and tcb.chk_mng_no_struct = $chk_mng_no_struct
              )
          );
EOF;
        $query = $DB_rfs->query($sql);

//        log_message('debug', "sql = " . $sql);
    }

    /**
     * 防雪柵管理情報の更新
     *
     * @param $post setクエリ <br>
     *        $post['chk_mng_no']           点検管理番号
     *        $post['chk_mng_no_struct']    点検管理番号（struct_idx）
     * @return
     */
    public function set_bousetsusaku_mng_info_complete($post) {
        log_message('debug', 'set_bousetsusaku_mng_info_complete');

//        if (($post['chk_mng_no'] == "") || ($post['chk_mng_no_struct'] == "")) {
            // 終了
//            return null;
//        }

        $DB_rfs=$this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        $chk_mng_no = $post['chk_mng_no'];
        //$chk_mng_no_struct = $post['chk_mng_no_struct'];

        $sql= <<<EOF
        update
          rfs_t_chk_bousetsusaku
        set
          comp_flg = 1
        where
          chk_mng_no = $chk_mng_no
          --and chk_mng_no_struct = $chk_mng_no_struct
EOF;
        $query = $DB_rfs->query($sql);

//        log_message('debug', "sql = " . $sql);
    }

    /**
     * 点検票データの削除
     *
     * @param $post setクエリ <br>
     *        $post['chk_mng_no']       点検管理番号
     * @return
     */
    public function del_chkdata($post) {
        log_message('debug', 'del_chkdata');

        if ($post['chk_mng_no'] == "") {
            // 終了
            return null;
        }

        $DB_rfs=$this->load->database('rfs',TRUE);
        if ($DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }

        // 点検管理番号、履歴番号
        $chk_mng_no = $post['chk_mng_no'];


        // 附属物テーブルの該当レコード削除
        // : rfs_t_chk_huzokubutsu
        $sql = <<<EOF
        delete
        from rfs_t_chk_huzokubutsu
        where chk_mng_no = $chk_mng_no;
EOF;
        $query = $DB_rfs->query($sql);
//        log_message('debug', "sql=$sql");


        // 部材テーブルの該当レコード削除
        // : rfs_t_chk_buzai
        $sql = <<<EOF
        delete
        from rfs_t_chk_buzai
        where chk_mng_no = $chk_mng_no;
EOF;
        $query = $DB_rfs->query($sql);
//        log_message('debug', "sql=$sql");


        // 点検箇所テーブルの該当レコード削除
        // : rfs_t_chk_tenken_kasyo
        $sql = <<<EOF
        delete
        from rfs_t_chk_tenken_kasyo
        where chk_mng_no = $chk_mng_no;
EOF;
        $query = $DB_rfs->query($sql);
//        log_message('debug', "sql=$sql");


        // 損傷内容テーブルの該当レコード削除
        // : rfs_t_chk_sonsyou
        $sql = <<<EOF
        delete
        from rfs_t_chk_sonsyou
        where chk_mng_no = $chk_mng_no;
EOF;
        $query = $DB_rfs->query($sql);
//        log_message('debug', "sql=$sql");


        // Excel管理テーブルの該当レコード削除
        // : rfs_t_chk_excel
        $sql = <<<EOF
        delete
        from rfs_t_chk_excel
        where chk_mng_no = $chk_mng_no;
EOF;
        $query = $DB_rfs->query($sql);
//        log_message('debug', "sql=$sql");


        // 写真テーブルの該当レコード削除
        // : rfs_t_chk_picture
        $sql = <<<EOF
        delete
        from rfs_t_chk_picture
        where chk_mng_no = $chk_mng_no;
EOF;
        $query = $DB_rfs->query($sql);
//        log_message('debug', "sql=$sql");


        // 防雪柵管理情報テーブルの該当レコード削除
        // : rfs_t_chk_bousetsusaku
        $sql = <<<EOF
        select *
        from rfs_t_chk_bousetsusaku
        where chk_mng_no_struct = $chk_mng_no;
EOF;
        $query = $DB_rfs->query($sql);
//        log_message('debug', "sql=$sql");

        // 該当レコードが無い場合は削除しない
        $record_num = $query->num_rows();

        if($record_num != 0) {
            $row = $query->first_row();
            $parent_chk_mng_no = $row->chk_mng_no;

            // 完了フラグをクリア(0:未完了)に設定
            $sql = <<<EOF
            update rfs_t_chk_bousetsusaku
            set comp_flg = 0
            where chk_mng_no = $parent_chk_mng_no;
EOF;
            $query = $DB_rfs->query($sql);
//            log_message('debug', "sql=$sql");

            // 親データの附属物レコードを削除
            // 削除を行うのは最後の子データの場合のみ（他にも子データがある場合は削除しない）
            // : rfs_t_chk_bousetsusaku
            $sql = <<<EOF
            select *
            from rfs_t_chk_bousetsusaku
            where chk_mng_no = $parent_chk_mng_no;
EOF;
            $query = $DB_rfs->query($sql);
//            log_message('debug', "sql=$sql");

            // 防雪柵管理テーブルが該当レコード（削除対象）のみの場合
            if($query->num_rows() == 1) {
                $sql = <<<EOF
                delete
                from rfs_t_chk_huzokubutsu
                where chk_mng_no = $parent_chk_mng_no;
EOF;
                $query = $DB_rfs->query($sql);
//                log_message('debug', "sql=$sql");
            }

            // 該当レコードを削除
            $sql = <<<EOF
            delete
            from rfs_t_chk_bousetsusaku
            where chk_mng_no_struct = $chk_mng_no;
EOF;
            $query = $DB_rfs->query($sql);
//            log_message('debug', "sql=$sql");
        }
    }

  /**
     * 形式の登録
     *
     * @param $post setクエリ <br>
     *        $post['baseinfo']     基本情報
     * @return
     */
  public function set_keishiki($post) {
    log_message('debug', 'set_keishiki');

    $sno=$post['baseinfo']['sno'];
    $keishiki_cd=$post['baseinfo']['input_keishiki_cd'];

    $DB_rfs=$this->load->database('rfs',TRUE);
    if ($DB_rfs->conn_id === FALSE) {
      log_message('debug', 'データベースに接続されていません');
      return;
    }

    // 施設基本情報に形式をUPDATEする
    $sql= <<<EOF
        update
          rfs_m_shisetsu
        set
          shisetsu_keishiki_cd=$keishiki_cd
        where
          sno=$sno;
EOF;

    $query = $DB_rfs->query($sql);

    //            log_message('debug', "sql = " . $sql);
  }

  /**
     * 引数のフェーズに変更する。
     * ※差戻し時に呼ばれ、履歴番号をインクリメントする
     *
     * @param $post <br>
     *        $post['chk_mng_no']   点検管理番号
     *        $post['set_phase']    設定するフェーズ
     * @return
     */
  public function tenkenRemand($post){

    $DB_rfs=$this->load->database('rfs',TRUE);
    if ($DB_rfs->conn_id === FALSE) {
      log_message('debug', 'データベースに接続されていません');
      return;
    }

    $chk_mng_no=$post['chk_mng_no'];

    // 管理番号から基本情報と点検情報を取得する
    $shisetsuAndCheck=$this->getShisetsuAndCheck($DB_rfs, $chk_mng_no);
    $shisetsu_kbn=$shisetsuAndCheck['shisetsu_kbn'];
    $struct_idx=$shisetsuAndCheck['struct_idx'];

    // 点検票が詳細調査済含かつ完了フェーズの場合を確認
    $fin_and_end_investigete=$this->getFinAndEndInvestigete($DB_rfs, $chk_mng_no);
    if ($fin_and_end_investigete) {
      // 差し戻すのは詳細調査
      $remand_phase=3;
    } else {
      // 差し戻すのは点検中
      $remand_phase=1;
    }

    //    log_message("debug",$shisetsu_kbn);
    //    log_message("debug",$struct_idx);

    // 点検管理番号から該当レコードを取得
    // 対象テーブル
    // rfs_t_chk_huzokubutsu
    // rfs_t_chk_buzai
    // rfs_t_chk_tenken_kasyo
    // rfs_t_chk_sonsyou
    // rfs_t_chk_bousetsusaku

    $DB_rfs->trans_start();
    $this->createChkData($DB_rfs, "rfs_t_chk_huzokubutsu", $chk_mng_no, $remand_phase);
    $this->createChkData($DB_rfs, "rfs_t_chk_buzai", $chk_mng_no, $remand_phase);
    $this->createChkData($DB_rfs, "rfs_t_chk_tenken_kasyo", $chk_mng_no, $remand_phase);
    $this->createChkData($DB_rfs, "rfs_t_chk_sonsyou", $chk_mng_no, $remand_phase);
    if ($shisetsu_kbn==4) { // 防雪柵
      // 防雪柵の場合は、completeも考えなければならない。
      // rfs_t_chk_bousetsusakuを更新
      $this->updateBousetsusaku($DB_rfs, $chk_mng_no);
      // 親も考慮しなければならない
      $this->updateParentPhase($DB_rfs, $chk_mng_no);
    }
    $DB_rfs->trans_complete();

    // Excel出力に必要なものを返却する
    $ret['sno']=$shisetsuAndCheck['sno'];
    $ret['chk_mng_no']=$chk_mng_no;
    $ret['struct_idx']=$shisetsuAndCheck['struct_idx'];
    $ret['excel_ver']=0;

    return $ret;
  }

  /***
   * 基本情報と点検管理情報を取得する。
   *
   * 引数の点検管理番号から、基本情報(rfs_m_shisetsu)と
   *  点検管理情報(rfs_t_chk_main)を取得する。
   *
   * 今のところ必要なものは、施設区分と支柱インデックスなので、
   * 最低限のフィールドを取得する。
   *
   * 追加があれば、SELECT句に入れてください。
   *
   * 引数:chk_mng_no 点検管理番号
   *
   ***/
  protected function getShisetsuAndCheck($DB, $chk_mng_no) {
    $sql= <<<EOF
SELECT
    c.sno
  , s.shisetsu_kbn
  , s.shisetsu_cd
  , c.struct_idx
FROM
  rfs_t_chk_main c JOIN rfs_m_shisetsu s
    ON c.sno = s.sno
WHERE
  chk_mng_no = $chk_mng_no;
EOF;

    $query = $DB->query($sql);
    $result = $query->result('array');
    /*
    log_message('debug', "sql=$sql");
    log_message('debug', "result=".print_r($result, true));
*/
    return $result[0];
  }

  /***
   * 引数の点検管理番号の最新の状態が、完了フェーズかつ詳細調査済の点検箇所が
   * 含まれるか確認し、含まれる場合TRUE、含まれない場合FALSEを返却する。
   *
   * 引数:DB コネクション
   *     chk_mng_no 点検管理番号
   *
   * 戻り値:boolean 完了＆詳細調査済みの点検箇所ありの場合true
   *               それ以外false
   *
   ***/
  protected function getFinAndEndInvestigete($DB, $chk_mng_no) {

    // 点検附属物の取得
    $huzokubutsu=$this->getHuzokubutsu($DB, $chk_mng_no);

    // フェーズ確認
    $phase=$huzokubutsu['phase'];
    if($phase!=5){
      return false;
    }

    // 詳細調査済の件数取得
    $investigate_cnt=$this->getInvestigateCnt($DB, $chk_mng_no);
    if($investigate_cnt==0){
      return false;
    }

    // フェーズ完了＆詳細調査済有
    return true;
  }

  /***
   * 引数の点検管理番号の附属物点検データの最新(履歴番号が最大)のデータを取得
   *
   * 引数:DB コネクション
   *     chk_mng_no 点検管理番号
   *
   * 戻り値:Array 附属物点検データ
   *
   ***/
  protected function getHuzokubutsu($DB, $chk_mng_no) {
    $sql= <<<EOF
SELECT
    h_tmp1.*
FROM
  rfs_t_chk_huzokubutsu h_tmp1 JOIN (
    SELECT
        chk_mng_no
      , max(rireki_no) rireki_no
    FROM
      rfs_t_chk_huzokubutsu
    GROUP BY
      chk_mng_no
  ) h_tmp2
    ON h_tmp1.chk_mng_no = h_tmp2.chk_mng_no
    AND h_tmp1.rireki_no = h_tmp2.rireki_no
WHERE
  h_tmp1.chk_mng_no = $chk_mng_no;
EOF;
    $query = $DB->query($sql);
    $result = $query->result('array');
    return $result[0];
  }

  /***
   * 引数の点検管理番号の点検箇所点検データの最新(履歴番号が最大)のデータの内
   * 詳細調査済の点検箇所の件数を取得
   *
   * 引数:DB コネクション
   *     chk_mng_no 点検管理番号
   *
   * 戻り値:Array 詳細調査済点検箇所の件数
   *
   ***/
  protected function getInvestigateCnt($DB, $chk_mng_no) {
    $sql= <<<EOF
SELECT
    count(tk_tmp1.*) cnt
FROM
  rfs_t_chk_tenken_kasyo tk_tmp1 JOIN (
    SELECT
        chk_mng_no
      , max(rireki_no) rireki_no
    FROM
      rfs_t_chk_tenken_kasyo
    GROUP BY
      chk_mng_no
  ) tk_tmp2
    ON tk_tmp1.chk_mng_no = tk_tmp2.chk_mng_no
    AND tk_tmp1.rireki_no = tk_tmp2.rireki_no
WHERE
  tk_tmp1.chk_mng_no = $chk_mng_no
  AND tk_tmp1.check_policy = 3;
EOF;
    $query = $DB->query($sql);
    $result = $query->result('array');
    return $result[0]['cnt'];
  }

  /***
   * 引数のテーブルにおける最大の履歴番号データに対し、
   * 履歴番号を加算し、差戻しデータを作成する。
   *
   * 引数:DB コネクション
   *     tbl_nm テーブル名
   *     chk_mng_no 点検管理番号
   *     remand_phase 差戻しフェーズ
   *
   ***/
  protected function createChkData($DB, $tbl_nm, $chk_mng_no, $remand_phase) {
    // 点検関連データの取得
    $chk_data = $this->getChkData($DB, $tbl_nm, $chk_mng_no);
    // 取得した全データをループ
    for ($i=0;$i<count($chk_data);$i++) {
      // 附属物データの場合はフェーズも変更
      if ($tbl_nm=="rfs_t_chk_huzokubutsu") {
        $chk_data[$i]['phase']=$remand_phase;
      }
      // 点検箇所の場合、調査(方針)が詳細調査済以外はスクリーニング、調査(方針)を初期化
      if ($tbl_nm=="rfs_t_chk_tenken_kasyo") {
        // 差戻しが点検中の場合
        if ($remand_phase==1) {
          // 詳細調査済以外の点検箇所のみ変更が必要
          // 詳細調査済の点検票は変更しない
          if ($chk_data[$i]['check_policy']!=3) {
            // 健全性がⅢの場合のみスクリーニングとする
            if ($chk_data[$i]['check_judge']==3) {
              // スクリーニング対象ではあるがスクリーニングのチェックは外す
              $chk_data[$i]['screening_taisyou'] = 1;
              $chk_data[$i]['screening'] = 0;
              $chk_data[$i]['check_policy'] = 1;
            // 健全性がⅢ以外の場合は健全性据置でスクリーニングは初期化
            }else{
              // スクリーニング初期化
              $chk_data[$i]['screening_taisyou'] = 0;
              $chk_data[$i]['screening'] = 0;
              $chk_data[$i]['check_policy'] = 0;
            }
          }
        // 差戻しが詳細調査の場合
        }else{
          // 詳細調査済み以外
          // 詳細調査へ戻るとき特に点検箇所の変更は不要
          // 画面側で、詳細調査フェーズの場合、詳細調査済
        }
      }
      $chk_data[$i]['rireki_no']=$chk_data[$i]['rireki_no']+1;  // 履歴加算
      $this->fixData($DB, $tbl_nm, $chk_data[$i]);  // 文字列整形
      $this->insertChkData($DB, $tbl_nm, $chk_data[$i]);  // insert
    }
  }

  // 登録データをSQLに合わせた形に変換する
  protected function fixData($DB, $tbl_nm, &$chk_data) {

    $int_fields = array ("integer", "smallint", "bigint", "numeric", "real", "double precision"); // 数値型
    $table_structure = $this->getTableStructure($DB, $tbl_nm);  // テーブルの型を取得

    // 登録データを全てチェックする
    foreach ($table_structure as $item){
      $fieldnm=$item['column_name'];
      $type=$item['data_type'];
      // 数値型のチェック
      if (array_search($type, $int_fields)===false) {
        $chk_data[$fieldnm]=$this->chkItem($chk_data,$fieldnm,2);
      }else{
        $chk_data[$fieldnm]=$this->chkItem($chk_data,$fieldnm,1);
      }
    }
  }

  // テーブルの構造を取得する
  protected function getTableStructure($DB, $table_nm) {

    $sql= <<<EOF
SELECT
    *
FROM
  information_schema.columns
WHERE
  table_name = '$table_nm'
ORDER BY
  ordinal_position;
EOF;
    $query = $DB->query($sql);
    $result = $query->result('array');
    /*
    log_message('debug', "sql=$sql");
    log_message('debug', "result=".print_r($result, true));
*/
    return $result;
  }

  /***
   *  登録用項目チェック
   *    登録項目がある場合その値(文字列の場合は登録用文字列)、
   *    無い場合は数値項目はNULLを、文字列項目は空文字を返却
   *
   *  引数
   *    $obj 項目格納オブジェクト
   *    $key 項目キー
   *    $kbn 1:数値項目、2:文字列項目
   ***/
  protected function chkItem($obj, $key, $kbn){
    if ($kbn==1) {
      // 数値項目
      $obj[$key]=isset($obj[$key])?$obj[$key]:"null";
    }else if ($kbn==2) {
      $obj[$key]=isset($obj[$key])?pg_escape_literal($obj[$key]):"null";
    }
    return $obj[$key];
  }


  // 該当の点検管理番号の最大履歴番号データを取得
  public function getChkData($DB, $tbl_nm, $chk_mng_no) {

    $sql= <<<EOF
SELECT
    *
FROM
  $tbl_nm
WHERE
  chk_mng_no = $chk_mng_no
  AND rireki_no = (
    SELECT
        MAX(rireki_no) rireki_no
    FROM
      $tbl_nm
    WHERE
      chk_mng_no = $chk_mng_no
  )
EOF;

    $query = $DB->query($sql);
    $result = $query->result('array');
    /*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result;
  }

  // 引数の点検データ(履歴更新済み)をINSERTする
  public function insertChkData($DB, $tbl_nm, $ins_data) {

    $sql= "INSERT INTO $tbl_nm (";
    $first=true;
    foreach ($ins_data as $key => $val){
      if ($first) {
        $first=false;
      }else{
        $sql.=", ";
      }
      $sql.=$key;
    }
    $sql.= ") VALUES (";
    $first=true;
    foreach ($ins_data as $key => $val){
      if ($first) {
        $first=false;
      }else{
        $sql.=", ";
      }
      $sql.=$val;
    }
    $sql.= ");";

//    log_message('debug', "sql=$sql");
    $query = $DB->query($sql);
  }

  /***
   * 防雪柵データの更新
   * rfs_t_chk_bousetsusakuのcomp_flgを未完了にする。
   *
   * 引数の点検管理番号は、子データの点検管理番号であり、
   * rfs_t_chk_bousetsusaku上に1件しかない。
  ***/
  protected function updateBousetsusaku($DB, $chk_mng_no) {

    $sql= <<<EOF
        UPDATE
          rfs_t_chk_bousetsusaku
        SET
          comp_flg=0
        WHERE
          chk_mng_no_struct=$chk_mng_no;
EOF;

    //log_message('debug', "sql=$sql");
    $query = $DB->query($sql);

  }

  /***
   * 防雪柵データの更新
   * 親データのフェーズを保存し直す
   ***/
  protected function updateParentPhase($DB, $chk_mng_no) {

    $BS_all=$this->getBousetsusakuAll($DB, $chk_mng_no);
    // 全子データで親データのphaseを決定
    /***
     * 1.SQL結果はphaseで降順
     * 2.全て5なら完了
     * 3.5以外の場合その値が親のphase
     * 
     ***/
    $p_chk_mng_no = $BS_all[0]['parent_chk_mng_no'];
    $p_phase=5; // 親フェーズ
    for ($i=0;$i<count($BS_all);$i++) {
      if ($BS_all[$i]['phase']==5) {
        continue;
      }
      // 5以外の最大の値が親のフェーズとなる
      $p_phase=$BS_all[$i]['phase'];
      break;
    }
    
    $sql= <<<EOF
        UPDATE
          rfs_t_chk_huzokubutsu
        SET
          phase = $p_phase
        , update_dt = NOW()
        WHERE
          chk_mng_no=$p_chk_mng_no;
EOF;

    //log_message('debug', "sql=$sql");
    $query = $DB->query($sql);
  }

  protected function getBousetsusakuAll($DB, $chk_mng_no) {
    $sql= <<<EOF
    WITH p_chk_mng_no AS ( 
      SELECT
          chk_mng_no 
      FROM
        rfs_t_chk_bousetsusaku 
      WHERE
        chk_mng_no_struct = $chk_mng_no
    ) 
    , c_chk_mng_no AS ( 
      SELECT
          rfs_t_chk_bousetsusaku.* 
      FROM
        rfs_t_chk_bousetsusaku JOIN p_chk_mng_no 
          ON rfs_t_chk_bousetsusaku.chk_mng_no = p_chk_mng_no.chk_mng_no
    ) 
    , h_tmp AS ( 
      SELECT
          c_chk_mng_no.chk_mng_no parent_chk_mng_no
        , rfs_t_chk_huzokubutsu.* 
      FROM
        rfs_t_chk_huzokubutsu JOIN c_chk_mng_no 
          ON rfs_t_chk_huzokubutsu.chk_mng_no = c_chk_mng_no.chk_mng_no_struct
    ) 
    SELECT
        tmp1.* 
    FROM
      h_tmp tmp1 JOIN ( 
        SELECT
            chk_mng_no
          , max(rireki_no) rireki_no 
        FROM
          h_tmp 
        GROUP BY
          chk_mng_no
      ) tmp2 
        ON tmp1.chk_mng_no = tmp2.chk_mng_no 
        AND tmp1.rireki_no = tmp2.rireki_no 
    ORDER BY
      phase DESC
EOF;
    $query = $DB->query($sql);
    $query = $DB->query($sql);
    $result = $query->result('array');
    /*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result;
  }
  
}
