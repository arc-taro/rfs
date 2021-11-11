"use strict";

var BaseCtrl = require("./base.js");

class FamMainCtrl extends BaseCtrl {
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

    // Excel出力全選択状態
    // true:全選択／false:全クリア
    this.excel_all_chk_flg = true;

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
          method: "GET",
          url: "api/index.php/FamMainAjax/initFamMain",
          params: {
            dogen_cd: this.mng_dogen_cd,
            syucchoujo_cd: this.mng_syucchoujo_cd,
            syozoku_cd: this.ath_syozoku_cd,
            srch_kbn: this.srch_kbn,
            shisetsu_kbn: this.shisetsu_kbn,
            secchi_idx: this.secchi_idx,
            kyouyou_kbn: this.kyouyou_kbn
          }
        });
      })
      .then(data => {
        var json = data.data;

        // 和暦リスト
        this.secchi_nendo = json.wareki_list;
        // 建管_出張所データ抽出
        this.dogen_syucchoujo_dat = JSON.parse(
          json.dogen_syucchoujo[0].dogen_row
        );
        // 施設区分
        this.shisetsu_kbn_dat = json.shisetsu_kbn;
        this.srch.shisetsu_kbn_all_cnt = this.shisetsu_kbn_dat.length;

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

        this.keep_dogen = this.dogen;
        this.keep_syucchoujo = this.syucchoujo;

        if (this.syucchoujo_cd == 0) {
          this.filterRosen = this.filterRosens();
          this.srch.rosen_all_cnt = this.filterRosen.length;
        } else {
          // 出張所フィルター
          this.filterRosen = this.filterRosens();
          this.srch.rosen_all_cnt = this.filterRosen.length;
        }

        // 検索結果
        this.data = json.shisetsu_info;
        var srch_cnt = json.cnt;
        // 検索項目
        this.srch = json.srch;
        var message;
        // 700件を超えている場合、表示・取得をクリアし再絞込みしてもらう
        if (srch_cnt > this.max_srch_cnt) {
          this.clearResult();
          delete data.data;

          // 設定なしinitMap
          setTimeout(() => {
            this.initMap();
          }, 1000);

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

          // 設定なしinitMap
          setTimeout(() => {
            this.initMap();
          }, 1000);

          return this.alert("検索", message);
        } else {
          this.changeNumItem();

          // 数値化処理
          for (var i = 0; i < this.data.length; i++) {
            if (this.data[i].rosen_cd) {
              this.data[i].rosen_cd = Number(this.data[i].rosen_cd);
            }
            if (this.data[i].sp) {
              this.data[i].sp = Number(this.data[i].sp);
            }
            if (this.data[i].sp_to) {
              this.data[i].sp_to = Number(this.data[i].sp_to);
            }
            if (this.data[i].secchi_yyyy) {
              this.data[i].secchi_yyyy = this.data[i].secchi_yyyy;
              // this.data[i].secchi_yyyy = Number(this.data[i].secchi_yyyy);
            }
            if (this.data[i].haishi_yyyy) {
              this.data[i].haishi_yyyy = this.data[i].haishi_yyyy;
              // this.data[i].haishi_yyyy = Number(this.data[i].haishi_yyyy);
            }
            if (this.data[i].encho) {
              this.data[i].encho = Number(this.data[i].encho);
            }
            if (this.data[i].koutsuuryou_day) {
              this.data[i].koutsuuryou_day = Number(
                this.data[i].koutsuuryou_day
              );
            }
            if (this.data[i].koutsuuryou_oogata) {
              this.data[i].koutsuuryou_oogata = Number(
                this.data[i].koutsuuryou_oogata
              );
            }
            if (this.data[i].koutsuuryou_hutuu) {
              this.data[i].koutsuuryou_hutuu = Number(
                this.data[i].koutsuuryou_hutuu
              );
            }
            if (this.data[i].koutsuuryou_12) {
              this.data[i].koutsuuryou_12 = Number(this.data[i].koutsuuryou_12);
            }
          }

          // チェックされているデータを点検票出力対象に追加
          this.setCheckedData();

          // 設定ありinitMap
          setTimeout(() => {
            this.initMap();
            // 範囲指定
            if (this.srch.default_tab_map) {
              //        if (this.syucchoujo.syucchoujo_cd != 0) {
              if (
                this.syucchoujo.syucchoujo_cd &&
                this.syucchoujo.syucchoujo_cd != 0
              ) {
                this.map.zoomToExtent(
                  new OpenLayers.Bounds(
                    this.syucchoujo["lt_lon"],
                    this.syucchoujo["lt_lat"],
                    this.syucchoujo["rb_lon"],
                    this.syucchoujo["rb_lat"]
                  ).transform(this.projection4326, this.projection3857)
                );
              } else {
                this.map.zoomToExtent(
                  new OpenLayers.Bounds(
                    this.dogen["ext_left"],
                    this.dogen["ext_top"],
                    this.dogen["ext_right"],
                    this.dogen["ext_bottom"]
                  ).transform(this.projection4326, this.projection3857)
                );
              }
            }
            this.drawVector();
          }, 1000);

          this.range = this.getPageList(srch_cnt);
          this.setPage(this.current_page);

          // 検索実行時、条件を指定して検索を閉じる
          this.jyoukenkensaku = true;
          this.naiyoukensaku = false;

          message =
            srch_cnt +
            "件の結果を取得しました<BR>※検索結果には廃止した施設が含まれます";
          return this.alert("検索", message);
        }
      })
      .finally(data => {
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
        /* 照明が含まれているかチェック */
        this.exist_ss = this.chkExistSS();

        this.waitLoadOverlay = false; // 読込み中です
      });
  }

  // 初期変数設定
  initVariable() {
    // 検索最大値
    this.max_srch_cnt = 700;

    // リストページャ初期化
    this.max_page = 0;
    this.current_page = 1;
    this.items_per_page = 50; // 50件固定

    // 設置年度の値
    var date = new Date(); // 現在日付を取得
    var year = date.getFullYear();
    this.secchi_nendo = [];
    // for (var i = year; i > 1989; i--) {
    //   this.secchi_nendo.push({
    //     "year": i,
    //     "gengou": "H" + (i - 1988) + "年"
    //   });
    // }
    // this.secchi_nendo.push({
    //   "year": 1989,
    //   "gengou": "H元年"
    // });

    // // ADD 昭和を追加 20161024 -->
    // for (var i = 1988; i >= 1975; i--) {
    //   this.secchi_nendo.push({
    //     "year": i,
    //     "gengou": "S" + (i - 1925) + "年"
    //   });
    // }

    // 代替路の有無
    this.substitute_road_arr = [
      {
        id: "0",
        label: "有"
      },
      {
        id: "1",
        label: "無"
      }
    ];
    // 緊急輸送道路
    this.emergency_road_arr = [
      {
        id: "1",
        label: "第1次"
      },
      {
        id: "2",
        label: "第2次"
      },
      {
        id: "3",
        label: "第3次"
      }
    ];
    // 供用区分
    this.kyouyou_kbn_arr = [
      {
        id: "-2",
        label: "未入力"
      },
      {
        id: "1",
        label: "供用"
      },
      {
        id: "2",
        label: "一部休止"
      },
      {
        id: "0",
        label: "休止"
      }
    ];

    // Excel出力用
    this.post_data = {};

    // 施設検索の検索条件
    this.srch = {};

    // 設置年度に不明を含めるチェックボックスをクリア
    this.srch.include_secchi_null = false;

    this.srch.substitute_road_all_cnt = this.substitute_road_arr.length;
    this.srch.emergency_road_all_cnt = this.emergency_road_arr.length;
    this.srch.kyouyou_kbn_all_cnt = this.kyouyou_kbn_arr.length;

    // map絡み
    this.displayIndex = -1;
    this.map = null;
    this.projection3857 = new OpenLayers.Projection("EPSG:3857");
    this.projection4326 = new OpenLayers.Projection("EPSG:4326");
    this.selectControl = null;
    this.vectorLayers = [];

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
    this.srch.substitute_road_dat_model = [];
    this.srch.emergency_road_dat_model = [];
    this.srch.kyouyou_kbn_dat_model = [];
    this.srch.rosen_dat_model = [];

    // 照明が含まれるか
    this.chkSS = {
      onItemSelect: item => {
        // 照明が含まれているかチェックする
        this.exist_ss = this.chkExistSS();
      },
      onItemDeselect: item => {
        // 照明が含まれているかチェックする
        this.exist_ss = this.chkExistSS();
      },
      onSelectAll: () => {
        // 照明が含まれる
        this.exist_ss = true;
      },
      onDeselectAll: () => {
        // 照明は含まれていない
        this.exist_ss = false;
      }
    };

    // ソート条件
    this.sort_order = "['seq_no']"; // 項目名
    this.reverse = false; // 昇順、降順
    this.sort_style = [];

    // 数値項目配列
    this.numItem = [
      "rosen_cd",
      "sp",
      "sp_to",
      "encho",
      "koutsuuryou_day",
      "koutsuuryou_oogata",
      "koutsuuryou_hutuu",
      "koutsuuryou_12"
    ];
    // this.numItem = ['rosen_cd', 'sp', 'sp_to', 'secchi_yyyy', 'haishi_yyyy', 'encho', 'koutsuuryou_day', 'koutsuuryou_oogata', 'koutsuuryou_hutuu', 'koutsuuryou_12'];

    // 検索結果のソート条件(左表)
    this.sort_identifiers_left = [
      ["shisetsu_kbn_nm", "seq_no"],
      ["-shisetsu_kbn_nm", "seq_no"],
      ["name", "seq_no"],
      ["-name", "seq_no"],
      ["shisetsu_keishiki_nm", "seq_no"],
      ["-shisetsu_keishiki_nm", "seq_no"],
      ["keishiki_kubun1", "seq_no"],
      ["-keishiki_kubun1", "seq_no"],
      ["keishiki_kubun2", "seq_no"],
      ["-keishiki_kubun2", "seq_no"],
      ["shisetsu_cd", "seq_no"],
      ["-shisetsu_cd", "seq_no"],
      ["toutyuu_no", "seq_no"],
      ["-toutyuu_no", "seq_no"]
    ];

    // 検索結果のソート条件(右表)
    this.sort_identifiers_right = [
      ["syozoku_cd", "seq_no"],
      ["-syozoku_cd", "seq_no"],
      [
        "sort_priority_tenken_link",
        "shisetsu_kbn_nm",
        "sno",
        "struct_idx",
        "chk_mng_no",
        "shichu_cnt"
      ],
      [
        "-sort_priority_tenken_link",
        "shisetsu_kbn_nm",
        "sno",
        "struct_idx",
        "chk_mng_no",
        "shichu_cnt"
      ],
      ["rosen_cd", "seq_no"],
      ["-rosen_cd", "seq_no"],
      ["rosen_nm", "seq_no"],
      ["-rosen_nm", "seq_no"],
      ["sp", "seq_no"],
      ["-sp", "seq_no"],
      ["sp_to", "seq_no"],
      ["-sp_to", "seq_no"],
      ["lr", "lr_str", "seq_no"],
      ["-lr", "lr_str", "seq_no"],
      ["encho", "seq_no"],
      ["-encho", "seq_no"],
      ["fukuin", "seq_no"],
      ["-fukuin", "seq_no"],
      ["address", "seq_no"],
      ["-address", "seq_no"],
      ["kyouyou_kbn", "kyouyou_kbn_str", "seq_no"],
      ["-kyouyou_kbn", "kyouyou_kbn_str", "seq_no"],
      ["secchi_yyyy", "secchi", "seq_no"],
      ["-secchi_yyyy", "secchi", "seq_no"],
      ["haishi_yyyy", "haishi", "seq_no"],
      ["-haishi_yyyy", "haishi", "seq_no"],
      ["ud", "ud_str", "seq_no"],
      ["-ud", "ud_str", "seq_no"],
      ["koutsuuryou_day", "seq_no"],
      ["-koutsuuryou_day", "seq_no"],
      ["koutsuuryou_12", "seq_no"],
      ["-koutsuuryou_12", "seq_no"],
      ["koutsuuryou_hutuu", "seq_no"],
      ["-koutsuuryou_hutuu", "seq_no"],
      ["koutsuuryou_oogata", "seq_no"],
      ["-koutsuuryou_oogata", "seq_no"],
      ["substitute_road_str", "seq_no"],
      ["-substitute_road_str", "seq_no"],
      ["emergency_road_str", "seq_no"],
      ["-emergency_road_str", "seq_no"],
      ["motorway_str", "seq_no"],
      ["-motorway_str", "seq_no"],
      ["senyou", "seq_no"],
      ["-senyou", "seq_no"],
      ["dogen_mei", "seq_no"],
      ["-dogen_mei", "seq_no"],
      ["syucchoujo_mei", "seq_no"],
      ["-syucchoujo_mei", "seq_no"]
    ];

    setTimeout(() => {
      $("#sync-table2").on("scroll", evt => {
        $("#sync-table1").scrollTop($(evt.target).scrollTop());
      });
    }, 500);
  }

  chkExistSS() {
    for (let i = 0; i < this.srch.shisetsu_kbn_dat_model.length; i++) {
      if (this.srch.shisetsu_kbn_dat_model[i].shisetsu_kbn == 3) {
        return true;
      }
    }
    return false;
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
          this.data = json.shisetsu_info;
          this.srch_cnt = srch_cnt;
          this.changeNumItem(); // 数値項目を数値化

          // チェックされているデータを点検票出力対象に追加
          this.excel_all_chk_flg = true;
          this.SetExcelChkStatus();

          // 範囲指定
          if (this.srch.default_tab_map) {
            if (this.syucchoujo) {
              if (
                this.syucchoujo.syucchoujo_cd &&
                this.syucchoujo.syucchoujo_cd != 0
              ) {
                this.map.zoomToExtent(
                  new OpenLayers.Bounds(
                    this.syucchoujo["lt_lon"],
                    this.syucchoujo["lt_lat"],
                    this.syucchoujo["rb_lon"],
                    this.syucchoujo["rb_lat"]
                  ).transform(this.projection4326, this.projection3857)
                );
              } else {
                this.map.zoomToExtent(
                  new OpenLayers.Bounds(
                    this.dogen["ext_left"],
                    this.dogen["ext_top"],
                    this.dogen["ext_right"],
                    this.dogen["ext_bottom"]
                  ).transform(this.projection4326, this.projection3857)
                );
              }
            } else {
              this.map.zoomToExtent(
                new OpenLayers.Bounds(
                  this.dogen["ext_left"],
                  this.dogen["ext_top"],
                  this.dogen["ext_right"],
                  this.dogen["ext_bottom"]
                ).transform(this.projection4326, this.projection3857)
              );
            }
          }
          this.drawVector();

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

  /**
   * 地図の初期化
   */
  initMap() {
    // List表示タブが選択されている場合は初期化しない
    if (this.srch.default_tab_list) {
      return;
    }

    // map生成済みの場合は生成しない
    if (this.map) {
      return;
    }

    this.map = new OpenLayers.Map({
      div: "map",
      projection: this.projection3857,
      displayProjection: this.projection4326
    });

    this.map.addLayer(
      new OpenLayers.Layer.XYZ(
        "標準地図",
        "https://cyberjapandata.gsi.go.jp/xyz/std/${z}/${x}/${y}.png",
        {
          attribution:
            "<div style='text-align:right'><a href='https://www.gsi.go.jp/kikakuchousei/kikakuchousei40182.html' target='_blank'>国土地理院</a></div>",
          numZoomLevels: 19
        }
      )
    );

    this.map.isValidZoomLevel = function(zoomLevel) {
      return (
        zoomLevel != null &&
        zoomLevel >= 7 && // set min level here, could read from property
        zoomLevel < this.getNumZoomLevels()
      );
    };

    // VectorLayerの初期化
    // 20181225 欠番がない時代は、このループで良かったが
    // アイコンを落とすとき施設コードを使用していたのでこけた
    // なのでMAXの施設区分で初期化することにする
    let max_shisetsu_kbn_arr = this.shisetsu_kbn_dat.map(p => {
      return p.id;
    });
    let max_shisetsu_kbn = Math.max.apply(null, max_shisetsu_kbn_arr);
    for (var i = 0; i < max_shisetsu_kbn; i++) {
      //for (var i = 0; i < this.shisetsu_kbn_dat.length; i++) {
      this.vectorLayers[i] = new OpenLayers.Layer.Vector("vector layer" + i);
      this.vectorLayers[i].setVisibility(true);
    }
    this.map.addLayers(this.vectorLayers);

    // 範囲指定
    //    if (this.syucchoujo.syucchoujo_cd != 0) {
    if (this.syucchoujo.syucchoujo_cd && this.syucchoujo.syucchoujo_cd != 0) {
      this.map.zoomToExtent(
        new OpenLayers.Bounds(
          this.syucchoujo["lt_lon"],
          this.syucchoujo["lt_lat"],
          this.syucchoujo["rb_lon"],
          this.syucchoujo["rb_lat"]
        ).transform(this.projection4326, this.projection3857)
      );
    } else {
      this.map.zoomToExtent(
        new OpenLayers.Bounds(
          this.dogen["ext_left"],
          this.dogen["ext_top"],
          this.dogen["ext_right"],
          this.dogen["ext_bottom"]
        ).transform(this.projection4326, this.projection3857)
      );
    }

    this.selectControl = new OpenLayers.Control.SelectFeature(
      this.vectorLayers,
      {
        hover: true,
        clickout: true,
        toggle: true,
        callbacks: {
          over: feature => {
            this.displayIndex = feature.index;
            //          this.$scope.$apply();
          },
          out: () => {
            this.displayIndex = -1;
            //          this.$scope.$apply();
          },
          click: function(feature) {
            this.unselectAll();
            this.select(feature);
          }
        },
        onSelect: this.onPopupFeatureSelect.bind(this),
        onUnselect: this.onPopupFeatureUnselect.bind(this)
      }
    );
    this.map.addControls([this.selectControl]);
    this.selectControl.activate();

    // スケールラインのコントローラ定義
    this.scaleLineControl = new OpenLayers.Control.ScaleLine({
      maxWidth: 150,
      bottomOutUnits: "",
      bottomInUnits: "",
      geodesic: true
    });
    this.map.addControls([this.scaleLineControl]);
    this.scaleLineControl.activate();
  }

  /**
   * VectorLayerの描画
   */
  drawVector() {
    if (this.data == null) {
      return;
    }
    if (this.vectorLayers[0] == null) {
      return;
    }
    //this.vectorLayers[0].removeAllFeatures();
    for (var k = 0; k < this.data.length; k++) {
      var feature = new OpenLayers.Feature.Vector(
        new OpenLayers.Geometry.Point(
          this.data[k].lon,
          this.data[k].lat
        ).transform(this.projection4326, this.projection3857)
      );
      feature.style = {
        fillColor: "#669933",
        fillOpacity: 1,
        strokeColor: "#aaee77",
        strokeWidth: 3,
        pointRadius: 8,
        graphicWidth: 32,
        graphicHeight: 32
      };

      // console.debug(this.data[k].shisetsu_kbn);

      // アイコンの設定
      if (this.data[k].shisetsu_kbn == 1) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/dh_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 2) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/dj_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 3) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/ss_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 4) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/bs_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 5) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/yh_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 6) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/ka_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 7) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/kb_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 8) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/kc_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 9) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/kd_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 10) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/ki_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 11) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/jh_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 12) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/sd_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 13) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/dt_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 14) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/tt_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 15) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/ck_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 16) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/sk_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 17) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/bh_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 18) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/dy_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 19) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/dn_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 20) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/ts_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 21) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/rh_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 22) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/kk_1.png`;
      } else if (this.data[k].shisetsu_kbn == 23) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/tk_1.png`;
      } else if (this.data[k].shisetsu_kbn == 24) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/br_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 25) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/tn_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 26) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/kf_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 27) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/hd_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 28) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/gk_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 29) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/oh_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 30) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/sh_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 31) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/ok_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 32) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/gh_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 33) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/kk_1.gif`; 
      } else if (this.data[k].shisetsu_kbn == 34) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/mt_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 35) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/dm_1.gif`;
      } else if (this.data[k].shisetsu_kbn == 36) {
        feature.style.externalGraphic = `images/icon/shisetsu_mng/jm_1.gif`;
      }
      feature.data = this.data[k];
      this.vectorLayers[this.data[k].shisetsu_kbn - 1].addFeatures([feature]);
    }
  }

  /**
   *  Openlayers の　ポップアップファンクション
   *  @method onPopupClose
   */
  onPopupClose(evt) {
    this.selectControl.unselect(this.selectedFeature);
  }

  /**
   * OpenLayersのイベント：アイコン等が選択されたら
   * @method onPopupFeatureSelect
   * @param {OpanLayers.Feature} feature 選択されたfeature
   */
  onPopupFeatureSelect(feature) {
    var data = feature.data;
    var html = `
<div id="popupicon" style="width:300px">
<p>施設種別：${data.shisetsu_kbn_nm}</p>
<p>施設ID：${data.shisetsu_cd}</p>
<p>路線：${data.rosen_nm}</p>`;

    // 測点
    html += `<p>測点（SP）自：`;
    if (data.sp != null) {
      html += `${data.sp}`;
    } else {
      html += `-`;
    }
    html += `</p>`;

    // 測点
    html += `<p>測点（SP）至：`;
    if (data.sp_to != null) {
      html += `${data.sp_to}`;
    } else {
      html += `-`;
    }
    html += `</p>`;

    html += `<p>住所：${data.address}</p>`;

    // 設置年度
    if (data.secchi != null) {
      if (data.secchi == "") {
        html += `<p>設置年度：-</p>`;
      } else {
        html += `<p>設置年度：${data.secchi}</p>`;
      }
    } else {
      html += `<p>設置年度：-</p>`;
    }

    // 所属コード3以下とkanrisyaのみ基本情報を表示
    if (this.ath_syozoku_cd <= 3 || this.ath_syozoku_cd == 10001) {
      // 基本情報編集
      html += `<p><div style="cursor:pointer; font-size: 12px; padding: 3px;">`;
      html += `<a ng-click="fammain.editBaseInfoWrapper(1, ${data.sno})">`;
      html += `[基本情報編集]</a></div></p>`;
    }

    // 台帳表示
    html += `<p><div style="cursor:pointer; font-size: 12px; padding: 3px;">`;
    html += `<a ng-click="fammain.editDiatyouWrapper(${data.sno})">`;
    html += `[開く]</a></div></p>`;

    html += `</div>`;

    this.selectedFeature = feature;
    var popup = new OpenLayers.Popup.FramedCloud(
      "popup",
      feature.geometry.getBounds().getCenterLonLat(),
      null,
      html,
      null,
      true,
      this.onPopupClose.bind(this)
    );
    popup.panMapIfOutOfView = true;
    popup.autoSize = true;
    feature.popup = popup;
    this.map.addPopup(popup);
    this.$compile(jQuery("#popupicon"))(this.$scope);
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

  /**
   * OpenLayersのイベント：アイコン等が選択解除イベント
   * @method onPopupFeatureUnselect
   * @param {OpanLayers.Feature} feature 選択解除されたfeature
   */
  onPopupFeatureUnselect(feature) {
    if (feature.popup) {
      this.map.removePopup(feature.popup);
      feature.popup.destroy();
      feature.popup = null;
    }
  }

  /**
   * 画像の拡大図（モーダル）を開く
   */
  openImageModal(path) {
    var data = {
      path: path
    };
    var modal = this.$uibModal.open({
      animation: true,
      templateUrl: "views/modal_image.html",
      controller: "ModalImageCtrl as modal",
      size: "lg",
      resolve: {
        data: () => {
          return data;
        }
      }
    });
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
    if (this.data == null) {
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
          // Map範囲指定
          if (this.map) {
            this.map.zoomToExtent(
              new OpenLayers.Bounds(
                this.dogen["ext_left"],
                this.dogen["ext_top"],
                this.dogen["ext_right"],
                this.dogen["ext_bottom"]
              ).transform(this.projection4326, this.projection3857)
            );
          }

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
    if (this.data == null) {
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
          // Map範囲指定
          //      if (this.syucchoujo.syucchoujo_cd != 0) {
          if (this.map) {
            if (this.syucchoujo) {
              if (this.syucchoujo.syucchoujo_cd != 0) {
                this.map.zoomToExtent(
                  new OpenLayers.Bounds(
                    this.syucchoujo["lt_lon"],
                    this.syucchoujo["lt_lat"],
                    this.syucchoujo["rb_lon"],
                    this.syucchoujo["rb_lat"]
                  ).transform(this.projection4326, this.projection3857)
                );
              } else {
                this.map.zoomToExtent(
                  new OpenLayers.Bounds(
                    this.dogen["ext_left"],
                    this.dogen["ext_top"],
                    this.dogen["ext_right"],
                    this.dogen["ext_bottom"]
                  ).transform(this.projection4326, this.projection3857)
                );
              }
            } else {
              this.map.zoomToExtent(
                new OpenLayers.Bounds(
                  this.dogen["ext_left"],
                  this.dogen["ext_top"],
                  this.dogen["ext_right"],
                  this.dogen["ext_bottom"]
                ).transform(this.projection4326, this.projection3857)
              );
            }
          }
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
      // マップを選択状態に合わせる
      if (this.map) {
        if (
          this.syucchoujo.syucchoujo_cd &&
          this.syucchoujo.syucchoujo_cd != 0
        ) {
          this.map.zoomToExtent(
            new OpenLayers.Bounds(
              this.syucchoujo["lt_lon"],
              this.syucchoujo["lt_lat"],
              this.syucchoujo["rb_lon"],
              this.syucchoujo["rb_lat"]
            ).transform(this.projection4326, this.projection3857)
          );
        } else {
          this.map.zoomToExtent(
            new OpenLayers.Bounds(
              this.dogen["ext_left"],
              this.dogen["ext_top"],
              this.dogen["ext_right"],
              this.dogen["ext_bottom"]
            ).transform(this.projection4326, this.projection3857)
          );
        }
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

      if (this.map) {
        // マップを選択状態に合わせる
        if (
          this.syucchoujo.syucchoujo_cd &&
          this.syucchoujo.syucchoujo_cd != 0
        ) {
          this.map.zoomToExtent(
            new OpenLayers.Bounds(
              this.syucchoujo["lt_lon"],
              this.syucchoujo["lt_lat"],
              this.syucchoujo["rb_lon"],
              this.syucchoujo["rb_lat"]
            ).transform(this.projection4326, this.projection3857)
          );
        } else {
          this.map.zoomToExtent(
            new OpenLayers.Bounds(
              this.dogen["ext_left"],
              this.dogen["ext_top"],
              this.dogen["ext_right"],
              this.dogen["ext_bottom"]
            ).transform(this.projection4326, this.projection3857)
          );
        }
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
    this.data = null;
    this.max_page = 0;
    this.current_page = 1;

    if (this.selectControl) {
      this.selectControl.unselectAll();
    }

    // VectorLayerの削除
    if (this.vectorLayers[0] != null) {
      let max_shisetsu_kbn_arr = this.shisetsu_kbn_dat.map(p => {
        return p.id;
      });
      let max_shisetsu_kbn = Math.max.apply(null, max_shisetsu_kbn_arr);
      for (var i = 0; i < max_shisetsu_kbn; i++) {
        //for (var i = 0; i < this.shisetsu_kbn_dat.length; i++) {
        this.vectorLayers[i].removeAllFeatures();
      }
    }

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

  // 基本情報編集
  editBaseInfo(kbn, data) {
    /*
        // 29年度リリース対応
        // 基本情報が整うまで閲覧モードのみ
        if (kbn == 0) {
          this.alert('モード変更', '現在最新の施設情報をシステムに登録中のため、施設の新規追加はできません。');
          return;
        }*/

    var sno = 0; // 新規時は0で渡す
    // 新規時は基本情報は出張所必須
    if (kbn == 0 && !this.syucchoujo) {
      this.alert("基本情報登録", "出張所を選択してください");
      return;
    } else if (kbn == 0 && this.syucchoujo.syucchoujo_cd == 0) {
      this.alert("基本情報登録", "出張所を選択してください");
      return;
    }
    if (kbn == 1) {
      sno = data.sno;
    }
    this.$location.path("/shisetsu_edit/" + sno + "/1/0");
    this.$window.location.reload(false);
  }

  changeNumItem() {
    for (var i = 0; i < this.data.length; i++) {
      for (var j = 0; j < this.numItem.length; j++) {
        if (this.data[i][this.numItem[j]]) {
          if (Number(this.data[i][this.numItem[j]])) {
            this.data[i][this.numItem[j]] = Number(
              this.data[i][this.numItem[j]]
            );
          }
        }
      }
    }
  }

  /**
   * チェックされた施設台帳データをエクセルに出力する
   */
  submitShisetsuDataToExcel() {
    if (!this.data) {
      this.alert("施設台帳出力", "検索が実行されていません");
      return;
    }

    if (!this.checked_data) {
      this.alert("施設台帳出力", "対象のデータを選択してください");
      return;
    }

    // サーバーへ送る
    this.alert("施設台帳の出力", "施設台帳が出力されます");

    // 施設台帳出力(zip)パラメータ
    this.post_data.checked_data = JSON.stringify(this.checked_data);
    this.post_data.excel_ver = this.excel_ver ? 1 : 0;
  }

  /**
   * 検索結果リストを出力する
   */
  submitList() {
    if (!this.data) {
      this.alert("リスト出力", "検索が実行されていません");
      return;
    }

    // リスト出力パラメータ
    this.post_data.dogen_cd = this.dogen.dogen_cd;
    this.post_data.syucchoujo_cd = this.syucchoujo.syucchoujo_cd;
    this.post_data.srch = JSON.stringify(this.data);
    this.post_data.excel_ver = this.excel_ver ? 1 : 0;
  }

  // Excel全出力チェックボックス(全選択、全クリア)
  SetExcelChkStatus() {
    this.SetSrchDataChecked(this.excel_all_chk_flg);
  }

  // 令和3年度に追加した施設かどうかを返す（Excelのチェックボックスを無効にする）
  IsAdditionalShisetsuR03(shisetsuKbn) {
    return [24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36].indexOf(Number(shisetsuKbn)) > -1;
  }

  // 検索結果リストの全てのチェックボックスを更新
  SetSrchDataChecked(checked) {
    if (this.data == null) {
      return;
    }

    for (var i = 0; Object.keys(this.data).length > i; i++) {
      if (this.IsAdditionalShisetsuR03(this.data[i].shisetsu_kbn)) {
        // 令和3年度に追加した施設の場合、Excel出力を無効にする
        this.data[i].chkexcel = false;
      } else {
        this.data[i].chkexcel = checked;
      }
    }

    // チェックされているデータを点検票出力対象に追加
    this.setCheckedData();
  }

  // 検索結果リストの全ての内容を全出力チェックボックスに反映
  IsExcelAllChedked() {
    if (this.data == null) {
      return;
    }

    // チェックされているデータを点検票出力対象に追加
    this.setCheckedData();

    // 検索結果の各チェックボックスが同じ状態か
    var checked = this.data[0].chkexcel;
    for (var i = 0; Object.keys(this.data).length > i; i++) {
      if (this.data[i].chkexcel != checked) {
        return;
      }
    }

    // 検索結果が同じ状態の場合、全出力チェックボックスに反映させる
    this.excel_all_chk_flg = checked;
  }

  // 点検票zip出力用にチェックされたデータを取得する
  setCheckedData() {
    if (!this.data) {
      return;
    }

    this.checked_data = null;
    this.keishiki_not_input = null;

    for (var i = 0; Object.keys(this.data).length > i; i++) {
      // Excel出力チェックボックスの確認
      if (this.data[i].chkexcel == false) {
        continue;
      }

      // 形式の入力チェック
      if (this.data[i].shisetsu_keishiki_cd == null) {
        if (this.keishiki_not_input == null) {
          var message = "形式が未入力のデータが選択されています<br><br>";
          message += "【施設名】　：" + this.data[i].shisetsu_kbn_nm + "<br>";
          message += "【管理番号】：" + this.data[i].shisetsu_cd;
          this.keishiki_not_input = message;
        }
        //continue;
        // 形式未入力のデータも対象に追加(前回出力の場合は対象となるため)
      }

      // 点検管理番号が無い場合、施設のみの登録状態
      //      if(!this.data[i].chk_mng_no) {
      //        this.data[i].chk_mng_no = -1;
      //      }

      // Excel出力対象の設定
      if (!this.checked_data) {
        this.checked_data = {};
        this.checked_data.data_array = [];
      }

      var data = {};
      data.sno = this.data[i].sno;
      data.chk_mng_no = this.data[i].chk_mng_no;
      data.chk_times = this.data[i].chk_times;
      data.rireki_no = this.data[i].rireki_no;
      data.struct_idx = this.data[i].struct_idx;
      data.syucchoujo_cd = this.data[i].syucchoujo_cd;
      data.shisetsu_kbn = this.data[i].shisetsu_kbn;
      data.shisetsu_keishiki_cd = this.data[i].shisetsu_keishiki_cd;
      this.checked_data.data_array.push(data);
    }
  }

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
    /*
        if (this.current_page > 1) {
          this.current_page--;
          this.prev_disabled = this.prevPageDisabled();
          this.next_disabled = this.nextPageDisabled();
        }
    */
  }
  nextPage() {
    this.current_page++;
    this.setPage(this.current_page);
    /*
        if (this.current_page < this.max_page) {
          this.current_page++;
          this.prev_disabled = this.prevPageDisabled();
          this.next_disabled = this.nextPageDisabled();
        }
    */
  }

  prevPageDisabled() {
    return this.current_page === 1 ? "disabled" : "";
  }
  nextPageDisabled() {
    return this.current_page === this.max_page ? "disabled" : "";
  }
}

let angModule = require("../app.js");
angModule.angApp.controller("FamMainCtrl", [
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
    return new FamMainCtrl(
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
