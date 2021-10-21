"use strict";

var BaseCtrl = require("./base.js");

class TenkenKeikakuCtrl extends BaseCtrl {
  constructor(
    $scope,
    $http,
    $location,
    $uibModal,
    $anchorScroll,
    $routeParams,
    $q,
    $route,
    $compile,
    $window
  ) {
    super({
      $location: $location,
      $scope: $scope,
      $http: $http,
      $q: $q
    });

    // Angularの初期化
    this.$scope = $scope;
    this.$http = $http;
    this.$uibModal = $uibModal;
    this.$location = $location;
    this.$anchorScroll = $anchorScroll;
    this.$route = $route;
    this.$q = $q;
    this.$compile = $compile;

    /* GET引数
     *
     *  srch_kbn:検索区分
     *    1:sysTopから
     *    2:1以外(sessionに検索項目がある場合は検索を行う)
     *  shisetsu_kbn:施設区分
     *  secchi_idx:設置インデックス
     *    設置年度の範囲を表す
     *    1:20年以上
     *    2:10年以上20年未満
     *    3:5年以上10年未満
     *    4:5年未満
     *    5:設置年度不明
     *    6:計 = 設置年度の条件は付けずに検索
     *  kyouyou_kbn:供用区分
     *    1:供用
     *    0:休止
     *    -1:全て
     */
    this.srch_kbn = $routeParams.srch_kbn;
    this.shisetsu_kbn = $routeParams.shisetsu_kbn;
    this.secchi_idx = $routeParams.secchi_idx;
    this.kyouyou_kbn = $routeParams.kyouyou_kbn;

    // 初期設定
    this.initVariable();

    // 案内標識DBの戻り先URLを削除
    localStorage.removeItem("RTN_URL");

    // ログイン情報
    super
      .start(this.$http)
      .then(() => {
        // session情報を保持
        this.ath_dogen_cd = this.session.ath.dogen_cd;
        this.ath_syucchoujo_cd = this.session.ath.syucchoujo_cd;
        this.ath_syozoku_cd = this.session.ath.syozoku_cd;
        this.ath_account_cd = this.session.ath.account_cd;
        this.mng_dogen_cd = this.session.mngarea.dogen_cd;
        this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
        this.imm_url = this.session.url;

        this.waitLoadOverlay = true; // 読込み中です

        return this.$http({
          method: 'GET',
          url: 'api/index.php/TenkenKeikakuAjax/initTenkenKeikaku',
          params: {
            dogen_cd: this.mng_dogen_cd,
            syucchoujo_cd: this.mng_syucchoujo_cd,
            syozoku_cd: this.ath_syozoku_cd
          }
        });
      })
      .then(data => {
        const json = data.data;

        this.keikaku_list = [
          {
            sno: 1,
            shisetsu_kbn_nm: "橋梁",
            shisetsu_cd: "11111-010",
            rosen_cd: "1001",
            rosen_nm: "小樽定山渓線",
            lr_str: "区分1",
            sp: "9000",
            latest_teiki_pat: {
              wareki_ryaku: "R3",
              umu_str: "無"
            },
            latest_huzokubutsu: {
              w_chk_dt: "R1",
              check_shisetsu_judge_nm: "II"
            },
            houtei_nendo: "",
            houtei_shisetsu_judge: "",
            teiki_pat_nendo: "R4",
            teiki_pat_ijou_umu: "無",
            plans: [
            {
              target_dt: "2022-04-01",
              nendo: "R4",
              planned: false,
            },
            {
              target_dt: "2023-04-01",
              nendo: "R5",
              planned: true,
            },
            {
              target_dt: "2024-04-01",
              nendo: "R6",
              planned: false,
            },
            {
              target_dt: "2025-04-01",
              nendo: "R7",
              planned: false,
            },
            {
              target_dt: "2026-04-01",
              nendo: "R8",
              planned: false,
            },
            ]
          }
        ];
        if (this.keikaku_list.length > 0) {
          // チェックボックスのデータを利用してヘッダを取得する
          this.keikaku_nendo_headers = this.keikaku_list[0].plans.map(plan => plan.nendo);
        }

        // 和暦リスト
        this.secchi_nendo = json.wareki_list;

        // 点検計画のヘッダー部分（初期表示分は5年分生成しておく）
        this.patrol_plan_headers = [];

        // 建管_出張所データ抽出
        this.dogen_syucchoujo_dat = JSON.parse(
          json.dogen_syucchoujo[0].dogen_row
        );
        // 施設区分
        this.shisetsu_kbn_dat = json.shisetsu_kbn;
        this.srch.shisetsu_kbn_all_cnt = this.shisetsu_kbn_dat.length;

        // 電気通信URL
        this.ele_url = json.ele_url;

        // 路線
        this.rosen_dat = json.rosen;

        // 前画面からの建管・出張所を元に
        // 建管と出張所の関係をセット（建管は必ずある）
        // 選択時は該当の建管をセット
        for (var k = 0; k < this.dogen_syucchoujo_dat.dogen_info.length; k++) {
          var data_d = this.dogen_syucchoujo_dat.dogen_info[k];
          if (data_d.dogen_cd == this.mng_dogen_cd) {
            this.dogen = data_d;
            this.syucchoujo = {};
            this.syucchoujo.syucchoujo_cd = 0;
            for (
              var l = 0;
              l < data_d.syucchoujo_row.syucchoujo_info.length;
              l++
            ) {
              var data_s = data_d.syucchoujo_row.syucchoujo_info[l];
              // 維持管理で選択された出張所をセット
              if (data_s.syucchoujo_cd == this.mng_syucchoujo_cd) {
                this.syucchoujo = data_s;
              }
            }
          }
        }

      })
      .finally(data => {

        // 路線リストを生成
        this.filterRosen = this.filterRosens();
        this.srch.rosen_all_cnt = this.filterRosen.length;

        this.waitLoadOverlay = false; // 読込み中です
      });
  }

  // 初期変数設定
  initVariable() {
    // 検索最大値
    this.max_srch_cnt = 700;

    this.shisetsukind = false;

    this.keikaku_list = null;
    this.keikaku_nendo_headers = [];


    // 施設検索の検索条件
    this.srch = {};

    // プルダウンチェックボックス絡み
    this.extraSettings = {
      externalIdProp: "",
      buttonClasses: "btn btn-default btn-xs btn-block",
      scrollable: true,
      scrollableHeight: "350px"
    };
    this.translationTexts = {
      checkAll: "全て",
      uncheckAll: "全て外す",
      buttonDefaultText: "選択",
      dynamicButtonTextSuffix: "個選択"
    };
    this.srch.shisetsu_kbn_dat_model = [];
    this.srch.rosen_dat_model = [];

    // ソート条件
    this.sort_order = "['seq_no']"; // 項目名
    this.reverse = false; // 昇順、降順
    this.sort_style = [];

    // // 数値項目配列
    this.numItem = [
      "rosen_cd",
    //   "sp",
      "sp_to",
    //   "encho",
    //   "koutsuuryou_day",
    //   "koutsuuryou_oogata",
    //   "koutsuuryou_hutuu",
    //   "koutsuuryou_12"
    ];

    // 検索結果のソート条件(左表)
    this.sort_identifiers_left = [
      // ["shisetsu_kbn_nm", "seq_no"],
      // ["-shisetsu_kbn_nm", "seq_no"],
      // ["name", "seq_no"],
      // ["-name", "seq_no"],
      // ["shisetsu_keishiki_nm", "seq_no"],
      // ["-shisetsu_keishiki_nm", "seq_no"],
      // ["keishiki_kubun1", "seq_no"],
      // ["-keishiki_kubun1", "seq_no"],
      // ["keishiki_kubun2", "seq_no"],
      // ["-keishiki_kubun2", "seq_no"],
      // ["shisetsu_cd", "seq_no"],
      // ["-shisetsu_cd", "seq_no"],
      // ["toutyuu_no", "seq_no"],
      // ["-toutyuu_no", "seq_no"]
    ];

    // 検索結果のソート条件(右表)
    this.sort_identifiers_right = [
      // ["syozoku_cd", "seq_no"],
      // ["-syozoku_cd", "seq_no"],
      // [
      //   "sort_priority_tenken_link",
      //   "shisetsu_kbn_nm",
      //   "sno",
      //   "struct_idx",
      //   "chk_mng_no",
      //   "shichu_cnt"
      // ],
      // [
      //   "-sort_priority_tenken_link",
      //   "shisetsu_kbn_nm",
      //   "sno",
      //   "struct_idx",
      //   "chk_mng_no",
      //   "shichu_cnt"
      // ],
      // ["rosen_cd", "seq_no"],
      // ["-rosen_cd", "seq_no"],
      // ["rosen_nm", "seq_no"],
      // ["-rosen_nm", "seq_no"],
      // ["sp", "seq_no"],
      // ["-sp", "seq_no"],
      // ["sp_to", "seq_no"],
      // ["-sp_to", "seq_no"],
      // ["lr", "lr_str", "seq_no"],
      // ["-lr", "lr_str", "seq_no"],
      // ["encho", "seq_no"],
      // ["-encho", "seq_no"],
      // ["fukuin", "seq_no"],
      // ["-fukuin", "seq_no"],
      // ["address", "seq_no"],
      // ["-address", "seq_no"],
      // ["kyouyou_kbn", "kyouyou_kbn_str", "seq_no"],
      // ["-kyouyou_kbn", "kyouyou_kbn_str", "seq_no"],
      // ["secchi_yyyy", "secchi", "seq_no"],
      // ["-secchi_yyyy", "secchi", "seq_no"],
      // ["haishi_yyyy", "haishi", "seq_no"],
      // ["-haishi_yyyy", "haishi", "seq_no"],
      // ["ud", "ud_str", "seq_no"],
      // ["-ud", "ud_str", "seq_no"],
      // ["koutsuuryou_day", "seq_no"],
      // ["-koutsuuryou_day", "seq_no"],
      // ["koutsuuryou_12", "seq_no"],
      // ["-koutsuuryou_12", "seq_no"],
      // ["koutsuuryou_hutuu", "seq_no"],
      // ["-koutsuuryou_hutuu", "seq_no"],
      // ["koutsuuryou_oogata", "seq_no"],
      // ["-koutsuuryou_oogata", "seq_no"],
      // ["substitute_road_str", "seq_no"],
      // ["-substitute_road_str", "seq_no"],
      // ["emergency_road_str", "seq_no"],
      // ["-emergency_road_str", "seq_no"],
      // ["motorway_str", "seq_no"],
      // ["-motorway_str", "seq_no"],
      // ["senyou", "seq_no"],
      // ["-senyou", "seq_no"],
      // ["dogen_mei", "seq_no"],
      // ["-dogen_mei", "seq_no"],
      // ["syucchoujo_mei", "seq_no"],
      // ["-syucchoujo_mei", "seq_no"]
    ];

    setTimeout(() => {
      $("#sync-table2").on("scroll", evt => {
        $("#sync-table1").scrollTop($(evt.target).scrollTop());
      });
    }, 500);
  }
  
  // 検索条件の整理
  arrangeSearchCondition() {
    // 入力→削除した要素の空文字を削除
    if (this.srch.shisetsu_cd != null) {
      if (this.srch.shisetsu_cd.length == 0) {
        delete this.srch.shisetsu_cd; // 施設管理番号
      }
    }

    if (this.srch.sp_from != null) {
      if (this.srch.sp_from.length == 0) {
        delete this.srch.sp_from; // 測点（前）
      }
    }

    if (this.srch.sp_to != null) {
      if (this.srch.sp_to.length == 0) {
        delete this.srch.sp_to; // 測点（後）
      }
    }
  }

  // 点検施設検索
  srchTenkenShisetsu() {
    this.waitLoadOverlay = true;
    this.clearResult();

    // 検索条件の整理
    this.arrangeSearchCondition();

    this.$http({
      method: "POST",
      url: "api/index.php/TenkenKeikakuAjax/srchTenkenShisetsu",
      data: {
        dogen_cd: this.dogen.dogen_cd,
        syucchoujo_cd: this.syucchoujo ? this.syucchoujo.syucchoujo_cd : 0,
        srch: this.srch
      }
    })
      .then(data => {
        var json = data.data;

        // 件数をチェック
        var srch_cnt = json.cnt;
        var message;

        // 700件を超えている場合、表示・取得をクリアし再絞込みしてもらう
        if (srch_cnt > this.max_srch_cnt) {
          this.clearResult();
          delete data.data;
          message =
            "検索結果が" + String(this.max_srch_cnt) + "件を越えています<br>";
          message += "（" + String(srch_cnt) + "件）<br><br>";
          message += "検索条件を設定し、対象データを絞り込んでください<br>";
          return this.alert("検索", message);
        } else if (srch_cnt === 0) {
          message = "該当するデータがありません";
          this.jyoukenkensaku = false;
          this.naiyoukensaku = true;
          this.clearResult();
          delete data.data;
          return this.alert("検索", message);
        } else {
          this.keikaku_list = json.shisetsu_info;
          this.srch_cnt = srch_cnt;
          this.changeNumItem(); // 数値項目を数値化

          this.range = this.getPageList(srch_cnt);
          this.current_page = 1; // 検索後は1ページ指定
          this.setPage(this.current_page);

          // 検索実行時、条件を指定して検索を閉じる
          this.jyoukenkensaku = true;
          this.naiyoukensaku = false;

          message = "";
          message =
            srch_cnt +
            "件の結果を取得しました";
          return this.alert("検索", message);
        }
      })
      .finally(() => {
        this.waitLoadOverlay = false;
      });
  }

  // 選択建管/出張所でフィルタ
  // 呼ばれるタイミングは、オープン時/建管/出張所変更時
  filterRosens() {
    this.srch.rosen_dat_model = [];
    // 出張所選択
    if (this.syucchoujo.syucchoujo_cd != 0) {
      // 出張所フィルタ
      var syucchoujo_cd = this.syucchoujo.syucchoujo_cd;
      return this.rosen_dat.filter(function(value, index) {
        if (syucchoujo_cd == value.syucchoujo_cd) {
          return true;
        }
        return false;
      });
    } else {
      // 建管フィルタ
      var dogen_cd = this.dogen.dogen_cd;
      return this.rosen_dat.filter(function(value, index) {
        if (dogen_cd == value.dogen_cd) {
          return true;
        }
        return false;
      });
    }
  }

  // 異常有無欄のセルのスタイルを生成する
  getIjouUmuCellStyle(ijyou_umu_flg) {
    const result = {};
    if (ijyou_umu_flg) {
      result['backgroundColor'] = '#FF0000';
      result['color'] = "#FFFFFF";
    }
    return result;
  }

  // 健全性欄のセルのスタイルを生成する（健全性によって背景色が変わるため）
  getCheckShisetsuJudgeCellStyle(check_shisetsu_judge) {
    const result = {};
    switch(check_shisetsu_judge) {
      case "1":
        result['backgroundColor'] = '#0000FF';
        result['color'] = "#FFFFFF";
        break;
      case "2":
        result['backgroundColor'] = "#00B050";
        result['color'] = "#FFFFFF";
        break;
      case "3":
        result['backgroundColor'] = "#FFC000";
        result['color'] = "#FFFFFF";
        break;
    }
    return result;
  }

  // Map表示タブ選択
  displayMap() {
    if (!this.dogen) {
      return;
    }
    setTimeout(() => {
      this.srch.default_tab_map = true;
      this.srch.default_tab_list = false;
      this.initMap();
      this.drawVector();
      this.$http({
        method: "GET",
        url: "api/index.php/InquirySession/updSrchDisp",
        params: {
          map_list: "map"
        }
      });
    }, 1000);
  }

  // Map表示タブ選択
  displayList() {
    this.srch.default_tab_map = false;
    this.srch.default_tab_list = true;
    this.$http({
      method: "GET",
      url: "api/index.php/InquirySession/updSrchDisp",
      params: {
        map_list: "list"
      }
    });
  }

  // 施設検索
  srchShisetsu() {
    this.waitLoadOverlay = true;
    this.clearResult();

    // 検索条件の整理
    this.arrangeSearchCondition();

    this.$http({
      method: "POST",
      url: "api/index.php/FamMainAjax/srchShisetsu",
      data: {
        dogen_cd: this.dogen.dogen_cd,
        syucchoujo_cd: this.syucchoujo ? this.syucchoujo.syucchoujo_cd : 0,
        srch: this.srch
      }
    })
      .then(data => {
        var json = data.data;

        // 件数をチェック
        var srch_cnt = json.cnt;
        var message;

        // 700件を超えている場合、表示・取得をクリアし再絞込みしてもらう
        if (srch_cnt > this.max_srch_cnt) {
          this.clearResult();
          delete data.data;
          message =
            "検索結果が" + String(this.max_srch_cnt) + "件を越えています<br>";
          message += "（" + String(srch_cnt) + "件）<br><br>";
          message += "検索条件を設定し、対象データを絞り込んでください<br>";
          return this.alert("検索", message);
        } else if (srch_cnt === 0) {
          message = "該当するデータがありません";
          this.jyoukenkensaku = false;
          this.naiyoukensaku = true;
          this.clearResult();
          delete data.data;
          return this.alert("検索", message);
        } else {
          this.keikaku_list = json.shisetsu_info;
          this.srch_cnt = srch_cnt;
          this.changeNumItem(); // 数値項目を数値化

          this.range = this.getPageList(srch_cnt);
          this.current_page = 1; // 検索後は1ページ指定
          this.setPage(this.current_page);

          // 検索実行時、条件を指定して検索を閉じる
          this.jyoukenkensaku = true;
          this.naiyoukensaku = false;

          message = "";
          message =
            srch_cnt +
            "件の結果を取得しました<BR>※検索結果には廃止した施設が含まれます";
          return this.alert("検索", message);
        }
      })
      .finally(() => {
        this.waitLoadOverlay = false;
      });
  }

  // 検索条件の整理
  arrangeSearchCondition() {
    // 入力→削除した要素の空文字を削除
    if (this.srch.shisetsu_cd != null) {
      if (this.srch.shisetsu_cd.length == 0) {
        delete this.srch.shisetsu_cd; // 施設管理番号
      }
    }

    if (this.srch.shityouson != null) {
      if (this.srch.shityouson.length == 0) {
        delete this.srch.shityouson; // 市町村
      }
    }

    if (this.srch.azaban != null) {
      if (this.srch.azaban.length == 0) {
        delete this.srch.azaban; // 字番
      }
    }

    if (this.srch.sp_from != null) {
      if (this.srch.sp_from.length == 0) {
        delete this.srch.sp_from; // 測点（前）
      }
    }

    if (this.srch.sp_to != null) {
      if (this.srch.sp_to.length == 0) {
        delete this.srch.sp_to; // 測点（後）
      }
    }
  }

  // 基本情報編集呼出しラッパー(Map表示から呼ばれる)
  editBaseInfoWrapper(kbn, sno) {
    var data = {};
    data.sno = sno;
    // 基本情報編集の呼出し(検索リストからの実行と同じ状態)
    this.editBaseInfo(kbn, data);
  }

  // 基本情報編集呼出しラッパー(Map表示から呼ばれる)
  editDiatyouWrapper(sno) {
    this.$location.path("/fam_edit/" + sno);
  }

  // 検索項目の全てリセット
  allReset() {
    delete this.srch.shisetsu_cd; // 施設管理番号
    delete this.srch.secchi_nendo_from; // 設置年度（前）
    delete this.srch.secchi_nendo_to; // 設置年度（後）
    delete this.srch.sp_from; // 測点（前）
    delete this.srch.sp_to; // 測点（後）
    delete this.srch.shityouson; // 市町村
    delete this.srch.azaban; // 字番

    // 設置年度に不明を含めるチェックボックスをクリア
    this.srch.include_secchi_null = false;

    // マルチセレクトクリア
    this.multiselect_clear();

    // 検索結果、ソート条件クリア
    this.clearResult();

    this.alert("検索", "リセットしました");
  }

  // multiselectクリア
  multiselect_clear() {
    this.srch.shisetsu_kbn_dat_model = [];
    this.srch.substitute_road_dat_model = [];
    this.srch.emergency_road_dat_model = [];
    this.srch.kyouyou_kbn_dat_model = [];
    this.srch.rosen_dat_model = [];
  }

  /**
   * 条件選択のサブ画面の表示用
   */
  searchSelect(sel) {
    for (var i = 0; i < this.searchCondition.length; i++) {
      this.searchCondition[i].active = false;
    }
    sel.active = true;
  }

  // 建管変更時処理
  // 本庁権限のみ
  // 1.mngarea更新
  // 2.検索初期化
  //    元々検索結果が無い場合はマップのズームを変える
  chgDogen() {
    // 検索結果無
    if (this.keikaku_list == null) {
      this.chgKanriNoSrch(1);
      // セッション上書き
      this.mngarea_update(
        this.$http,
        this.dogen.dogen_cd,
        this.syucchoujo.syucchoujo_cd
      ).then(() => {
        // mngarea上書き
        this.mng_dogen_cd = this.session.mngarea.dogen_cd;
        this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
      });
      // 検索結果有
    } else {
      this.confirm("検索結果を初期化してよろしいですか？", 0)
        .then(() => {
          // 結果クリア
          this.clearResult();

          // 条件表示変更
          this.jyoukenkensaku = false;
          this.naiyoukensaku = true;

          // 保存用
          this.keep_dogen = this.dogen;
          this.keep_syucchoujo = this.syucchoujo;
          this.syucchoujo = {};
          this.syucchoujo.syucchoujo_cd = 0;
        })
        .catch(() => {
          // キャンセル時は戻す
          this.dogen = this.keep_dogen;
          this.syucchoujo = this.keep_syucchoujo;
        })
        .finally(() => {
          // 路線
          this.filterRosen = this.filterRosens();
          this.srch.rosen_all_cnt = this.filterRosen.length;
          // セッション上書き
          this.mngarea_update(
            this.$http,
            this.dogen.dogen_cd,
            this.syucchoujo.syucchoujo_cd
          ).then(() => {
            // mngarea上書き
            this.mng_dogen_cd = this.session.mngarea.dogen_cd;
            this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
            // 基本情報編集の有無
            if (
              this.ath_syozoku_cd <= 2 ||
              this.ath_syozoku_cd == 10001 ||
              (this.ath_syozoku_cd == 3 &&
                this.ath_syucchoujo_cd == this.mng_syucchoujo_cd)
            ) {
              // 基本情報有
              this.in_kihonedit = true;
            } else {
              // 基本情報無し
              this.in_kihonedit = false;
            }
          });
        });
    }
  }

  // 出張所変更時処理
  chgSyucchoujo() {
    // 検索結果が存在しない
    if (this.keikaku_list == null) {
      this.chgKanriNoSrch(2);
      // セッション上書き
      this.mngarea_update(
        this.$http,
        this.dogen.dogen_cd,
        this.syucchoujo.syucchoujo_cd
      ).then(() => {
        // mngarea上書き
        this.mng_dogen_cd = this.session.mngarea.dogen_cd;
        this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
      });
      // 検索結果有
    } else {
      this.confirm("検索結果を初期化してよろしいですか？", 0)
        .then(() => {
          // 結果クリア
          this.clearResult();

          // 条件表示変更
          this.jyoukenkensaku = false;
          this.naiyoukensaku = true;
          // 保存用
          this.keep_syucchoujo = this.syucchoujo;
        })
        .catch(() => {
          // キャンセル時は戻す
          this.syucchoujo = this.keep_syucchoujo;
        })
        .finally(() => {
          // 路線
          this.filterRosen = this.filterRosens();
          this.srch.rosen_all_cnt = this.filterRosen.length;
          // セッション上書き
          this.mngarea_update(
            this.$http,
            this.dogen.dogen_cd,
            this.syucchoujo.syucchoujo_cd
          ).then(() => {
            // mngarea上書き
            this.mng_dogen_cd = this.session.mngarea.dogen_cd;
            this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
            // 基本情報編集の有無
            if (
              this.ath_syozoku_cd <= 2 ||
              this.ath_syozoku_cd == 10001 ||
              (this.ath_syozoku_cd == 3 &&
                this.ath_syucchoujo_cd == this.mng_syucchoujo_cd)
            ) {
              // 基本情報有
              this.in_kihonedit = true;
            } else {
              // 基本情報無し
              this.in_kihonedit = false;
            }
          });
        });
    }
  }

  // 建管、出張所変更時、検索結果無しの振る舞い用
  // kbn:1 建管
  //     2 出張所
  chgKanriNoSrch(kbn) {
    // 建管
    if (kbn == 1) {
      // 建管変更時は出張所全選択
      if (
        this.ath_syozoku_cd == 1 ||
        this.ath_syozoku_cd == 2 ||
        this.ath_syozoku_cd == 10001
      ) {
        this.syucchoujo = {};
        this.syucchoujo.syucchoujo_cd = 0;
      }
      
      // 保存用（キャンセル時に戻す時用）
      this.keep_dogen = this.dogen;
      this.keep_syucchoujo = this.syucchoujo;
      // 路線
      this.filterRosen = this.filterRosens();
      this.srch.rosen_all_cnt = this.filterRosen.length;
      // 出張所
    } else {
      // 他出張所選択から全てを選択した時の対応
      if (!this.syucchoujo) {
        this.syucchoujo = {};
        this.syucchoujo.syucchoujo_cd = 0;
      }

      // 保存用（キャンセル時に戻す時用）
      this.keep_syucchoujo = this.syucchoujo;
      // 路線
      this.filterRosen = this.filterRosens();
      this.srch.rosen_all_cnt = this.filterRosen.length;
    }
  }

  // 検索結果の削除
  clearResult() {
    // 検索結果の削除
    this.keikaku_list = null;
    // this.max_page = 0;
    // this.current_page = 1;

    // ソート条件クリア
    this.sort_order = "['seq_no']";
    this.reverse = false;
    this.sort_style = [];
    this.sort_style[this.sort_order] = {
      color: "#217dbb"
    };
  }

  // ソート対象項目名を設定
  SetSortOrder(item) {
    var current = JSON.stringify(this.sort_order);
    var set = JSON.stringify(item);

    // ソート済みの項目を選択した場合はソートリセット
    if (current == set) {
      this.sort_order = "['seq_no']";
    } else {
      // 複数指定時は配列が設定される
      this.sort_order = item;
    }

    // 選択されたソート項目の色を変更
    this.sort_style = [];
    this.sort_style[this.sort_order] = {
      color: "#217dbb"
    };
  }

  changeNumItem() {
    for (var i = 0; i < this.keikaku_list.length; i++) {
      for (var j = 0; j < this.numItem.length; j++) {
        if (this.keikaku_list[i][this.numItem[j]]) {
          if (Number(this.keikaku_list[i][this.numItem[j]])) {
            this.keikaku_list[i][this.numItem[j]] = Number(
              this.keikaku_list[i][this.numItem[j]]
            );
          }
        }
      }
    }
  }

  // /**
  //  * 検索結果リストを出力する
  //  */
  // submitList() {
  //   if (!this.data) {
  //     this.alert("リスト出力", "検索が実行されていません");
  //     return;
  //   }

  //   // リスト出力パラメータ
  //   this.post_data.dogen_cd = this.dogen.dogen_cd;
  //   this.post_data.syucchoujo_cd = this.syucchoujo.syucchoujo_cd;
  //   this.post_data.srch = JSON.stringify(this.data);
  // }

  /*********************/
  /* ページネーション部分 */
  /*********************/
  getPageList(srch_cnt) {
    this.max_page = Math.ceil(srch_cnt / this.items_per_page);
    var ret = [];
    for (var i = 1; i <= this.max_page; i++) {
      ret.push(i);
    }
    return ret;
  }

  setPage(n) {
    this.current_page = n;
    this.prev_disabled = this.prevPageDisabled();
    this.next_disabled = this.nextPageDisabled();
  }
  prevPage() {
    this.current_page--;
    this.setPage(this.current_page);
  }
  nextPage() {
    this.current_page++;
    this.setPage(this.current_page);
  }

  prevPageDisabled() {
    return this.current_page === 1 ? "disabled" : "";
  }
  nextPageDisabled() {
    return this.current_page === this.max_page ? "disabled" : "";
  }
}

let angModule = require("../app.js");
angModule.angApp.controller("TenkenKeikakuCtrl", [
  "$scope",
  "$http",
  "$location",
  "$uibModal",
  "$anchorScroll",
  "$routeParams",
  "$q",
  "$route",
  "$compile",
  "$window",
  function(
    $scope,
    $http,
    $location,
    $uibModal,
    $anchorScroll,
    $routeParams,
    $q,
    $route,
    $compile,
    $window
  ) {
    return new TenkenKeikakuCtrl(
      $scope,
      $http,
      $location,
      $uibModal,
      $anchorScroll,
      $routeParams,
      $q,
      $route,
      $compile,
      $window
    );
  }
]);
