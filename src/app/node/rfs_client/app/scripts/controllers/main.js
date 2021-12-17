"use strict";

/* globals OpenLayers:true */

var BaseCtrl = require("./base.js");
var _ = require("lodash");

/**
 * @ngdoc function
 * @name rfsApp.controller:MainCtrl
 * @description
 * # MainCtrl
 * Controller of the rfsApp
 */
class MainCtrl extends BaseCtrl {
  constructor(
    $scope,
    $filter,
    $http,
    $uibModal,
    $cookies,
    $compile,
    $location,
    $q,
    $document
  ) {
    super({
      $location: $location,
      $scope: $scope,
      $http: $http,
      $q: $q
    });

    this.$scope = $scope;
    this.$filter = $filter;
    this.$http = $http;
    this.$uibModal = $uibModal;
    this.$cookies = $cookies;
    this.$compile = $compile;
    this.$location = $location;
    this.$q = $q;
    this.$document = $document;

    // 検索項目表示用 trueが非表示
    // 検索後はここを閉じる。また、TOP画面から表示する時は検索が指定されている場合trueにする必要がある
    this.jyoukenkensaku = false;
    this.naiyoukensaku = true;

    // 案内標識DBの戻り先URLを削除
    localStorage.removeItem("RTN_URL");

    // リストページャ初期化
    this.max_page = 0;
    this.current_page = 1;
    this.items_per_page = 50; // 50件固定

    // クッキーから取得
    this.excel_ver = this.$cookies.excel_ver;
    if (this.excel_ver == "true") {
      this.excel_ver = true;
    } else {
      this.excel_ver = false;
    }

    this.waitSaveOverlay = false; // 保存中です
    this.waitLoadOverlay = false; // 読込み中です
    this.isViewSerchComplete = true; // 検索結果の件数表示フラグ(false:検索画面に戻った際は表示しない)

    // Map、Listタブのデフォルト表示
    this.default_tab = {};
    this.default_tab.map = true;
    this.default_tab.list = false;
    //        this.initialize = false;
    this.selected_list_tab = false;

    // Excel出力全選択状態
    // true:全選択／false:全クリア
    this.excel_all_chk_flg = true;

    // ソート条件
    this.sort_order = "['seq_no']"; // 項目名
    this.reverse = false; // 昇順、降順
    this.sort_style = [];

    // 数値項目配列
    this.numItem = ["rosen_cd", "sp", "secchi_yyyy"];

    // 検索結果のソート条件(左表)
    this.sort_identifiers_left = [
      ["shisetsu_kbn_nm", "seq_no"],
      ["-shisetsu_kbn_nm", "seq_no"],
      ["shisetsu_keishiki_nm", "seq_no"],
      ["-shisetsu_keishiki_nm", "seq_no"],
      ["target_dt", "seq_no"],
      ["-target_dt", "seq_no"],
      ["shisetsu_cd", "seq_no"],
      ["-shisetsu_cd", "seq_no"]
    ];

    // 検索結果のソート条件(右表)
    this.sort_identifiers_right = [
      //            ['syozoku_cd', 'seq_no'],
      //            ['-syozoku_cd', 'seq_no'],
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
      ["phase", "seq_no"],
      ["-phase", "seq_no"],
      ["check_shisetsu_judge", "seq_no"],
      ["-check_shisetsu_judge", "seq_no"],
      ["syoken", "seq_no"],
      ["-syoken", "seq_no"],
      ["measures_shisetsu_judge", "seq_no"],
      ["-measures_shisetsu_judge", "seq_no"],
      ["sort_priority_confirm_btn", "seq_no"],
      ["-sort_priority_confirm_btn", "seq_no"],
      ["rosen_cd", "seq_no"],
      ["-rosen_cd", "seq_no"],
      ["rosen_nm", "seq_no"],
      ["-rosen_nm", "seq_no"],
      ["sp", "seq_no"],
      ["-sp", "seq_no"],
      ["lr", "seq_no"],
      ["-lr", "seq_no"],
      ["syozaichi", "seq_no"],
      ["-syozaichi", "seq_no"],
      ["secchi_yyyy", "secchi", "seq_no"],
      ["-secchi_yyyy", "secchi", "seq_no"],
      ["chk_dt", "seq_no"],
      ["-chk_dt", "seq_no"],
      ["investigate_dt", "seq_no"],
      ["-investigate_dt", "seq_no"],
      ["re_measures_dt", "seq_no"],
      ["-re_measures_dt", "seq_no"],
      ["chk_company", "seq_no"],
      ["-chk_company", "seq_no"],
      ["chk_person", "seq_no"],
      ["-chk_person", "seq_no"],
      ["investigate_company", "seq_no"],
      ["-investigate_company", "seq_no"],
      ["investigate_person", "seq_no"],
      ["-investigate_person", "seq_no"],
      ["substitute_road_str", "seq_no"],
      ["-substitute_road_str", "seq_no"],
      ["emergency_road_str", "seq_no"],
      ["-emergency_road_str", "seq_no"],
      ["motorway_str", "seq_no"],
      ["-motorway_str", "seq_no"],
      ["senyou", "seq_no"],
      ["-senyou", "seq_no"],
      ["fukuin", "seq_no"],
      ["-fukuin", "seq_no"],
      ["dogen_mei", "seq_no"],
      ["-dogen_mei", "seq_no"],
      ["syucchoujo_mei", "seq_no"],
      ["-syucchoujo_mei", "seq_no"]
    ];

    // 施設検索の検索条件
    this.srch = {};

    // 点検票zip出力用
    this.checked_data = null;
    //    this.checked_data.data_array = [];
    this.keishiki_not_input = null;

    // Excel出力用
    this.post_data = {};

    // 検索成功後のパラメータ(検索成功時のみ設定)
    this.srch_done = {};

    this.awesomeThings = ["HTML5 Boilerplate", "AngularJS", "Karma"];

    this.displayIndex = -1;
    this.map = null;
    this.projection3857 = new OpenLayers.Projection("EPSG:3857");
    this.projection4326 = new OpenLayers.Projection("EPSG:4326");
    this.selectControl = null;
    this.vectorLayers = [];

    // // 設置年度の値
    // var date = new Date(); // 現在日付を取得
    // var year = date.getFullYear();
    this.setti_nendo = [];
    // for (var i = year; i > 1989; i--) {
    //   this.setti_nendo.push({
    //     "year": i,
    //     "gengou": "H" + (i - 1988) + "年"
    //   });
    // }
    // this.setti_nendo.push({
    //   "year": 1989,
    //   "gengou": "H元年"
    // });

    // // ADD 昭和を追加 20161024 -->
    // for (var i = 1989; i >= 1975; i--) {
    //   this.setti_nendo.push({
    //     "year": i,
    //     "gengou": "S" + (i - 1925) + "年"
    //   });
    // }
    /*
        this.setti_nendo.push({
          "year": 1988,
          "gengou": "S63年以前"
        });
    */
    // ADD 昭和を追加 20161024 <--

    // プルダウンチェックボックス設定
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
    this.srch.phase_dat_model = [];
    this.srch.chk_judge_dat_model = [];
    this.srch.measures_judge_dat_model = [];
    this.srch.rosen_dat_model = [];
    this.srch.patrolin_gyousya_dat_model = [];
    this.srch.patrolin_dat_model = [];
    this.srch.investigator_gyousya_dat_model = [];
    this.srch.investigator_dat_model = [];
    this.srch.struct_idx_dat_model = [];

    this.gyousya_dat_per_dogen = {};
    this.gyousya_dat_per_dogen.gyousya_info = [];
    this.gyousya_dat = {};
    this.gyousya_dat.gyousya_info = [];
    this.patrolin_dat_per_syucchoujo = {};
    this.patrolin_dat_per_syucchoujo.patrolin_info = [];

    /**
     * 一括確定保存
     */
    this.save_all_chk_flg = true;
    this.save_checked_data = null;

    this.houshin = {
      "houshins": [{
        "houshin_cd": "0",
        "houshin": "－"
      }, {
        "houshin_cd": "1",
        "houshin": "スクリーニング"
      }, {
        "houshin_cd": "2",
        "houshin": "詳細調査"
      }, {
        "houshin_cd": "3",
        "houshin": "詳細調査済"
      }, {
        "houshin_cd": "4",
        "houshin": "スクリーニング済"
      }]
    };

    //        this.waitLoadOverlay = true;

    // 点検会社のプルダウン連携イベント
    this.externalEvents_patrol_gyousya = {
      onItemSelect: item => {
        this.patrolin_dat = [];
        for (var i = 0; i < this.srch.patrolin_gyousya_dat_model.length; i++) {
          var busyo_cd = this.srch.patrolin_gyousya_dat_model[i].id;
          for (
            var j = 0;
            j < this.patrolin_dat_per_syucchoujo.patrolin_info.length;
            j++
          ) {
            if (
              busyo_cd ==
              this.patrolin_dat_per_syucchoujo.patrolin_info[j].busyo_cd
            ) {
              this.patrolin_dat.push(
                angular.copy(this.patrolin_dat_per_syucchoujo.patrolin_info[j])
              );
            }
          }
        }
        // 複数ある場合は、部署名：氏名
        if (this.srch.patrolin_gyousya_dat_model.length > 1) {
          for (var k = 0; k < this.patrolin_dat.length; k++) {
            this.patrolin_dat[k].label =
              this.patrolin_dat[k].busyo_mei + ":" + this.patrolin_dat[k].label;
          }
        }

        // 点検員のマルチセレクトをクリア
        this.srch.patrolin_dat_model = [];
      },
      onItemDeselect: item => {
        this.patrolin_dat = [];

        // 選択が減って何もない
        if (this.srch.patrolin_gyousya_dat_model.length === 0) {
          // 全点検員表示
          this.patrolin_dat = angular.copy(
            this.patrolin_dat_per_syucchoujo.patrolin_info
          );
        } else {
          for (
            var i = 0;
            i < this.srch.patrolin_gyousya_dat_model.length;
            i++
          ) {
            var busyo_cd = this.srch.patrolin_gyousya_dat_model[i].id;
            for (
              var j = 0;
              j < this.patrolin_dat_per_syucchoujo.patrolin_info.length;
              j++
            ) {
              if (
                busyo_cd ==
                this.patrolin_dat_per_syucchoujo.patrolin_info[j].busyo_cd
              ) {
                this.patrolin_dat.push(
                  angular.copy(
                    this.patrolin_dat_per_syucchoujo.patrolin_info[j]
                  )
                );
              }
            }
          }
        }
        // 選択が1業者以外の場合は、部署名：氏名
        if (this.srch.patrolin_gyousya_dat_model.length != 1) {
          for (var k = 0; k < this.patrolin_dat.length; k++) {
            this.patrolin_dat[k].label =
              this.patrolin_dat[k].busyo_mei + ":" + this.patrolin_dat[k].label;
          }
        }

        // 点検員のマルチセレクトをクリア
        this.srch.patrolin_dat_model = [];
      },
      //onSelectAll: function(item3) {console.log("3:"+item3);},
      onDeselectAll: () => {
        // パトロール員を初期化=全部
        this.srch.patrolin_dat_model = [];

        // パトロール員の最初は全てリストに表示するので部署名をセット
        this.patrolin_dat = angular.copy(
          this.patrolin_dat_per_syucchoujo.patrolin_info
        );
        for (var i = 0; i < this.patrolin_dat.length; i++) {
          // 最初は全部表示なので表示名に部署名を入れる
          this.patrolin_dat[i].label =
            this.patrolin_dat[i].busyo_mei + ":" + this.patrolin_dat[i].label;
        }

        // 点検員のマルチセレクトをクリア
        this.srch.patrolin_dat_model = [];
      }
      //onInitDone: function(item5) {console.log("5:"+item5);}
    };

    // 調査会社のプルダウン連携イベント
    this.externalEvents_investigator_gyousya = {
      onItemSelect: item => {
        this.investigator_dat = [];
        for (
          var i = 0;
          i < this.srch.investigator_gyousya_dat_model.length;
          i++
        ) {
          var busyo_cd = this.srch.investigator_gyousya_dat_model[i].id;
          for (
            var j = 0;
            j < this.mst_investigator_dat.investigator_info.length;
            j++
          ) {
            if (
              busyo_cd ==
              this.mst_investigator_dat.investigator_info[j].busyo_cd
            ) {
              this.investigator_dat.push(
                JSON.parse(
                  JSON.stringify(this.mst_investigator_dat.investigator_info[j])
                )
              );
            }
          }
        }
        // 複数ある場合は、部署名：氏名
        if (this.srch.investigator_gyousya_dat_model.length > 1) {
          for (var k = 0; k < this.investigator_dat.length; k++) {
            this.investigator_dat[k].label =
              this.investigator_dat[k].busyo_mei +
              ":" +
              this.investigator_dat[k].label;
          }
        }

        // 調査員のマルチセレクトをクリア
        this.srch.investigator_dat_model = [];
      },
      onItemDeselect: item => {
        //console.log(this.srch.investigator_gyousya_dat_model.length);

        // 調査員のマルチセレクトをクリア
        this.srch.investigator_dat_model = [];
      },
      //onSelectAll: function(item3) {console.log("3:"+item3);},
      onDeselectAll: () => {
        // パトロール員を初期化=全部
        this.srch.investigator_dat_model = [];
        // 調査員の最初は全てリストに表示するので部署名をセット
        this.investigator_dat = JSON.parse(
          JSON.stringify(this.mst_investigator_dat.investigator_info)
        );
        for (var j = 0; j < this.investigator_dat.length; j++) {
          // 最初は全部表示なので表示名に部署名を入れる
          this.investigator_dat[j].label =
            this.investigator_dat[j].busyo_mei +
            ":" +
            this.investigator_dat[j].label;
        }

        // 調査員のマルチセレクトをクリア
        this.srch.investigator_dat_model = [];
      }
      //onInitDone: function(item5) {console.log("5:"+item5);}
    };

    // 施設名のプルダウン連携イベント
    //        this.externalEvents_shisetsu_kbn = {
    //            onItemSelect: (item) => {
    //                if(item.label != '防雪柵') {
    //                    return;
    //                }
    //                this.getBsskStructIdx();
    //            },
    //            onItemDeselect: (item) => {
    //                var shisetsu_kbn_info = this.shisetsu_kbn_dat.shisetsu_kbn_info;
    //                var shisetsu_kbn_info_num = Object.keys(shisetsu_kbn_info).length;
    //
    //                for(var i = 0; shisetsu_kbn_info_num > i; i++) {
    //                    if(shisetsu_kbn_info[i].id != item.id) {
    //                        continue;
    //                    }
    //                    if(shisetsu_kbn_info[i].label != '防雪柵') {
    //                        continue;
    //                    }
    //                    delete this.struct_idx_dat;
    //                }
    //            },
    //            onSelectAll: () => {
    //                this.getBsskStructIdx();
    //            },
    //            onDeselectAll: () => {
    //                delete this.struct_idx_dat;
    //            },
    //        };

    // ログイン情報
    // セッションから建管と出張所コードを取得
    super
      .start($http)
      .then(() => {
        this.dogen_cd = this.session.ath.dogen_cd;
        this.sel_dogen_cd = this.session.mngarea.dogen_cd;
        this.syucchoujo_cd = this.session.ath.syucchoujo_cd;
        this.sel_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
        this.syozoku_cd = this.session.ath.syozoku_cd;
        this.imm_url = this.session.url;
        this.account = this.session.ath.account_cd;

        // 所属コードに応じてソート条件(右表)の先頭を削除
        // (昇順、降順の2つ)
        // →制限を外す(データ不備が多いので点検前に直したい意図)
        //      if (this.syozoku_cd != 3) {
        //        this.sort_identifiers_right.shift();
        //        this.sort_identifiers_right.shift();
        //      }

        this.dogen = {};
        this.syucchoujo = {};
        this.syucchoujo.syucchoujo_cd = 0;

        // 無い場合はSESSIONを探索
        if (this.session.srch_fuzokubutsu != null) {
          // 数値が文字列になってしまう
          var session_srch = this.session.srch_fuzokubutsu;

          session_srch.srch_done.dogen_cd = Number(
            session_srch.srch_done.dogen_cd
          );
          session_srch.srch_done.syucchoujo_cd = Number(
            session_srch.srch_done.syucchoujo_cd
          );
          session_srch.target_nendo_from = session_srch.target_nendo_from
            ? Number(session_srch.target_nendo_from)
            : "";
          session_srch.target_nendo_to = session_srch.target_nendo_to
            ? Number(session_srch.target_nendo_to)
            : "";
//          session_srch.rosen_cd = session_srch.rosen_cd
//            ? Number(session_srch.rosen_cd)
//            : "";
          session_srch.include_secchi_null = Number(
            session_srch.include_secchi_null
          );

          if (session_srch.shisetsu_kbn_dat_model) {
            for (
              var i = 0;
              i < session_srch.shisetsu_kbn_dat_model.length;
              i++
            ) {
              var shisetsu_kbn_arr = session_srch.shisetsu_kbn_dat_model[i];
              shisetsu_kbn_arr.id = Number(shisetsu_kbn_arr.id);
              shisetsu_kbn_arr.shisetsu_kbn = Number(
                shisetsu_kbn_arr.shisetsu_kbn
              );
            }
          }
          if (session_srch.phase_dat_model) {
            for (var i = 0; i < session_srch.phase_dat_model.length; i++) {
              var phase_arr = session_srch.phase_dat_model[i];
              phase_arr.id = Number(phase_arr.id);
              phase_arr.phase = Number(phase_arr.phase);
            }
          }
          if (session_srch.chk_judge_dat_model) {
            for (var i = 0; i < session_srch.chk_judge_dat_model.length; i++) {
              var chk_judge_arr = session_srch.chk_judge_dat_model[i];
              chk_judge_arr.id = Number(chk_judge_arr.id);
              chk_judge_arr.shisetsu_judge = Number(
                chk_judge_arr.shisetsu_judge
              );
            }
          }
          if (session_srch.measures_judge_dat_model) {
            for (
              var i = 0;
              i < session_srch.measures_judge_dat_model.length;
              i++
            ) {
              var measures_arr = session_srch.measures_judge_dat_model[i];
              measures_arr.id = Number(measures_arr.id);
              measures_arr.shisetsu_judge = Number(measures_arr.shisetsu_judge);
            }
          }
        }

        return $http({
          method: "GET",
          url: "api/index.php/MainSchAjax/get_srchentry",
          timeout: this.canceler.promise,
          params: {
            dogen_cd: this.dogen_cd,
            syucchoujo_cd: this.syucchoujo_cd,
            syozoku_cd: this.syozoku_cd
          }
        });
      })
      .then(data => {
        // 年度リスト
        this.setti_nendo = data.data.wareki_list;

        // 建管_出張所データ抽出
        this.dogen_syucchoujo_dat = JSON.parse(data.data[0].dogen_row);
        // 施設区分
        this.shisetsu_kbn_dat = JSON.parse(data.data[1].shisetsu_kbn_row);
        // 路線
        this.rosen_dat = JSON.parse(data.data[2].rosen_row);
        // 健全性（点検・調査時）
        this.chk_judge_dat = JSON.parse(data.data[3].shisetsu_judge_row);
        // 健全性（措置後）
        this.measures_judge_dat = JSON.parse(data.data[3].shisetsu_judge_row);
        // 状態
        this.phase_dat = JSON.parse(data.data[4].phase_row);
        // 点検業者
        this.gyousya_dat_all = JSON.parse(data.data[5].gyousya_row);
        // 調査業者
        this.investigator_gyousya_dat = JSON.parse(
          data.data[6].investigator_gyousya_row
        );
        // 調査員
        this.mst_investigator_dat = JSON.parse(data.data[7].investigator_row);
        // 支柱インデックス
        this.struct_idx_dat = JSON.parse(data.data[8].struct_idx_row);
        // 点検員
        this.mst_patrolin_dat = null;
        // 所属コード3以上で10001（管理者）じゃない
        if (this.syozoku_cd >= 3 && this.syozoku_cd != 10001) {
          this.mst_patrolin_dat = JSON.parse(data.data[9].patrolin_row);
        }

        //      if(this.syozoku_cd >= 3 && this.syozoku_cd != 10001) {
        //        var syucchoujo_all = {};
        //        syucchoujo_all.syucchoujo.syucchoujo_mei = '全て';
        //        syucchoujo_all.syucchoujo.syucchoujo_cd = 0;
        //
        //        for(var i = 0; Object.keys(this.dogen_syucchoujo_dat.dogen_info).length > i; i++) {
        //
        //          var syucchoujo_array = this.dogen_syucchoujo_dat.dogen_info[i].syucchoujo_row.syucchoujo_info;
        //
        //          syucchoujo_array.unshift(syucchoujo_all);
        //
        ////          for(var j = 0; Object.keys(syucchoujo_array).length > j; j++){
        ////
        ////
        ////          }
        //
        //        }
        //      }

        // 建管と出張所の関係をセット
        // 建管未選択
        if (this.sel_dogen_cd == 0) {
          // 未選択時は先頭にする
          var data_d = this.dogen_syucchoujo_dat.dogen_info[0];
          this.dogen = data_d;
          for (
            var l = 0;
            l < data_d.syucchoujo_row.syucchoujo_info.length;
            l++
          ) {
            var data_s = data_d.syucchoujo_row.syucchoujo_info[l];
            if (data_s.syucchoujo_cd == this.sel_syucchoujo_cd) {
              this.syucchoujo = data_s;
            }
          }
        } else {
          // 選択時は該当の建管をセット
          for (
            var k = 0;
            k < this.dogen_syucchoujo_dat.dogen_info.length;
            k++
          ) {
            var data_d = this.dogen_syucchoujo_dat.dogen_info[k];
            if (data_d.dogen_cd == this.sel_dogen_cd) {
              this.dogen = data_d;
              for (
                var l = 0;
                l < data_d.syucchoujo_row.syucchoujo_info.length;
                l++
              ) {
                var data_s = data_d.syucchoujo_row.syucchoujo_info[l];
                // 維持管理で選択された出張所をセット
                if (data_s.syucchoujo_cd == this.sel_syucchoujo_cd) {
                  this.syucchoujo = data_s;
                }
              }
            }
          }
        }

        this.keep_dogen = this.dogen;
        this.keep_syucchoujo = this.syucchoujo;

        // 点検会社、点検員リストの設定
        this.set_patrolin_by_kenkan();

        // 調査員の最初は全てリストに表示するので部署名をセット
        if (this.mst_investigator_dat) {
          this.investigator_dat = JSON.parse(
            JSON.stringify(this.mst_investigator_dat.investigator_info)
          );
          for (var j = 0; j < this.investigator_dat.length; j++) {
            // 最初は全部表示なので表示名に部署名を入れる
            this.investigator_dat[j].label =
              this.investigator_dat[j].busyo_mei +
              ":" +
              this.investigator_dat[j].label;
          }
        }

        if (this.sel_syucchoujo_cd == 0) {
          this.filterRosen = this.filterRosens();
        } else {
          // 出張所フィルター
          this.filterRosen = this.filterRosens();
        }

        // 検索結果を復帰
        if (this.session.srch_fuzokubutsu != null) {
          if (this.session.srch_fuzokubutsu.srch_done != null) {
            this.srch = this.session.srch_fuzokubutsu.srch_done.srch; // 検索条件

            // 検索条件を保持している場合、その条件で検索を行う
            // 検索画面に戻った際は検索結果の件数を表示しない

            // 条件追加TOP画面から来た場合は出す
            if (!this.session.srch_fuzokubutsu.raf_top) {
              this.isViewSerchComplete = false;
            }
            this.searchShisetsu();
            //this.searchShisetsuNum();
          }
        }

        //                    this.drawVector();
        //this.chg_syucchoujo(this.syucchoujo_cd);
        //console.log(data);

        //            this.waitLoadOverlay = false;
      })
      .finally(() => {
        setTimeout(() => {
          // 画面が表示されたときに実行しないといけないコード　（Openlayers等はエレメントがないと失敗する）
          $("#sync-table2").on("scroll", evt => {
            $("#sync-table1").scrollTop($(evt.target).scrollTop());
          });
          this.initMap();
          if (this.session.srch_fuzokubutsu.default_tab) {
            this.default_tab = this.session.srch_fuzokubutsu.default_tab; // 初期表示タブ
          }
        }, 100);
      });
  }

  // 建設管理部リストの変更
  set_patrolin_by_kenkan() {
    var syucchoujo_cd;
    var gyousya_array = [];
    var gyousya_all = this.gyousya_dat_all.gyousya_info;

    // 選択された建管の出張所CDに対応する点検会社を設定
    angular.forEach(this.dogen.syucchoujo_row.syucchoujo_info, function(
      value,
      key
    ) {
      syucchoujo_cd = value.syucchoujo_cd;

      angular.forEach(gyousya_all, function(value, key) {
        if (syucchoujo_cd == value.syucchoujo_cd) {
          gyousya_array.push(value);
        }
      });
    });

    // 建管ごとの点検会社リストに設定
    this.gyousya_dat_per_dogen.gyousya_info = gyousya_array;

    // 建管変更時は出張所全選択
    //    if(this.syozoku_cd == 1 || this.syozoku_cd == 2 || this.syozoku_cd == 10001) {
    //      //this.syucchoujo = null;
    //      this.syucchoujo = {};
    //      this.syucchoujo.syucchoujo_cd = 0;
    //    }

    // 出張所(管理区域)リストの更新
    this.set_patrolin_by_syucchoujo();
  }

  // 出張所(管理区域)リストの変更
  set_patrolin_by_syucchoujo() {
    // nullの場合は全選択
    //    if(this.syucchoujo == null) {
    if (this.syucchoujo == null || this.syucchoujo.syucchoujo_cd == 0) {
      this.gyousya_dat.gyousya_info = this.gyousya_dat_per_dogen.gyousya_info;
    } else {
      // 選択された出張所CD
      var syucchoujo_cd = this.syucchoujo.syucchoujo_cd;
      var gyousya_array = [];
      var patrolin_array = [];

      // 出張所に所属する点検会社を設定
      angular.forEach(this.gyousya_dat_per_dogen.gyousya_info, function(
        value,
        key
      ) {
        if (syucchoujo_cd == value.syucchoujo_cd) {
          gyousya_array.push(value);
        }
      });
      this.gyousya_dat.gyousya_info = gyousya_array;

      // 本庁、建管、管理者は設定不要
      if (this.syozoku_cd == 3 || this.syozoku_cd == 4) {
        // 出張所に所属する点検員を設定
        angular.forEach(this.mst_patrolin_dat.patrolin_info, function(
          value,
          key
        ) {
          if (syucchoujo_cd == value.syucchoujo_cd) {
            patrolin_array.push(value);
          }
        });
        this.patrolin_dat_per_syucchoujo.patrolin_info = patrolin_array;

        // 出張所変更直後、パトロール員を最初は全てリストに表示するので部署名をセット

        this.patrolin_dat = angular.copy(
          this.patrolin_dat_per_syucchoujo.patrolin_info
        );
        for (var i = 0; i < this.patrolin_dat.length; i++) {
          this.patrolin_dat[i].label =
            this.patrolin_dat[i].busyo_mei + ":" + this.patrolin_dat[i].label;
        }
      }
    }

    // 点検会社、点検員のマルチセレクトをクリア
    this.srch.patrolin_gyousya_dat_model = [];
    this.srch.patrolin_dat_model = [];
  }

  //    getBsskStructIdx() {
  //
  //        this.srch.shisetsu_cd = this.srch.shisetsu_cd;
  //
  //        // 防雪柵を選択した場合
  //        this.$http({
  //            method: 'POST',
  //            url: 'api/index.php/MainSchAjax/get_srch_struct',
  //            data: {
  //                srch: this.srch,
  //            }
  //        }).success((data) => {
  //            this.struct_idx_dat = JSON.parse(data[0].struct_idx_row);
  //            console.log(data);
  //        });
  //    }

  // タブが非選択状態の処理
  DeselectTab(tabIndex) {
    // Map表示タブが非選択の場合、デフォルト表示はList表示とする
    if (tabIndex == 1) {
      this.default_tab.map = false;
      this.default_tab.list = true;
      this.selected_list_tab = true;
    }
    if (tabIndex == 2) {
      this.default_tab.map = true;
      this.default_tab.list = false;
      this.selected_list_tab = false;
    }
  }

  /**
   * 地図の初期化
   */
  initMap() {
    // List表示タブが選択されている場合は初期化しない
    if (this.default_tab.list) {
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
    for (var i = 0; i < 7; i++) {
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

    /*
            // 緯度経度初期値
            var lon = this.syucchoujo.lon;
            var lat = this.syucchoujo.lat;

            // 倍率を取得
            var bairitsu = this.syucchoujo.bairitsu;

            this.map.setCenter(new OpenLayers.LonLat(lon, lat).transform(this.projection4326, this.projection3857), bairitsu);
    */

    //        this.map.zoomToExtent(new OpenLayers.Bounds(this.data.rrow['ext1x'], this.data.rrow['ext2y'], this.data.rrow['ext2x'], this.data.rrow['ext1y']).transform(this.projection4326, this.projection3857));

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
      if (this.data[k].shisetsu_kbn == 1) {
        feature.style.externalGraphic = `images/icon/dh_${this.data[k].measures_shisetsu_judge}.gif`;
      } else if (this.data[k].shisetsu_kbn == 2) {
        feature.style.externalGraphic = `images/icon/dj_${this.data[k].measures_shisetsu_judge}.gif`;
      } else if (this.data[k].shisetsu_kbn == 3) {
        feature.style.externalGraphic = `images/icon/ds_${this.data[k].measures_shisetsu_judge}.gif`;
      } else if (this.data[k].shisetsu_kbn == 4) {
        feature.style.externalGraphic = `images/icon/bs_${this.data[k].measures_shisetsu_judge}.gif`;
      } else if (this.data[k].shisetsu_kbn == 5) {
        feature.style.externalGraphic = `images/icon/sp_${this.data[k].measures_shisetsu_judge}.gif`;
      } else if (this.data[k].shisetsu_kbn == 22) {
        feature.style.externalGraphic = `images/icon/kk_${this.data[k].measures_shisetsu_judge}.gif`;
      } else if (this.data[k].shisetsu_kbn == 23) {
        feature.style.externalGraphic = `images/icon/tk_${this.data[k].measures_shisetsu_judge}.gif`;
      }
      feature.data = this.data[k];
      let idx = 0;
      if (this.data[k].shisetsu_kbn <= 5) {
        idx = this.data[k].shisetsu_kbn - 1;
      } else if (this.data[k].shisetsu_kbn == 22) {
        idx = 5;
      } else if (this.data[k].shisetsu_kbn == 23) {
        idx = 6;
      }
      //console.log(idx);
      this.vectorLayers[idx].addFeatures([feature]);
      //this.vectorLayers[this.data[k].shisetsu_kbn - 1].addFeatures([feature]);
    }
  }

  // Map表示タブ選択
  displayMap() {
    if (!this.dogen) {
      return;
    }
    setTimeout(() => {
      this.initMap();
      this.drawVector();
    }, 1000);
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
<p>点検の実施状況：${data.phase_str_large}</p>
<p>点検時の健全性診断（判定区分）：${data.chk_shisetsu_judge_nm}</p>
<p>措置時の健全性診断（判定区分）：${data.measures_shisetsu_judge_nm}</p>
<p>路線：${data.rosen_nm}</p>`;

    // 測点
    html += `<p>測点（SP）：`;
    if (data.sp != null) {
      html += `${data.sp}`;
    } else {
      html += `-`;
    }
    html += `</p>`;

    html += `<p>住所：${data.syozaichi}</p>`;

    // 設置年度
    if (data.secchi != "-") {
      if (data.secchi == "H元年") {
        html += `<p>設置年度：${data.secchi}</p>`;
      } else {
        html += `<p>設置年度：${data.secchi}</p>`;
      }
    } else {
      html += `<p>設置年度：-</p>`;
    }

    // 点検実施年月日
    if (data.check_dt != null) {
      html += `<p>点検実施年月日：${data.check_dt}</p>`;
    } else {
      html += `<p>点検実施年月日：-</p>`;
    }

    // 点検員
    html += `<p>点検員（会社名）：`;
    if (data.chk_person != "") {
      html += `${data.chk_person}`;
    } else {
      html += `-`;
    }
    if (data.chk_company != "") {
      html += `（${data.chk_company}）`;
    }
    html += `</p>`;

    if (data.investigate_dt != null) {
      html += `<p>調査実施年月日：${data.investigate_dt}</p>`;
    } else {
      html += `<p>調査実施年月日：-</p>`;
    }

    // 調査員
    html += `<p>調査員（会社名）：`;
    if (data.investigate_person != "") {
      html += `${data.investigate_person}`;
    } else {
      html += `-`;
    }
    if (data.investigate_company != "") {
      html += `（${data.investigate_company}）`;
    }
    html += `</p>`;

    html += `<p>所見:${data.syoken}</p>`;

    // 基本情報編集
    //    html += `<p><div style="cursor:pointer; font-size: 12px; padding: 3px;">`;
    //    html += `<a ng-click="main.editBaseInfoWrapper(1, ${data.dogen_cd}, ${data.syucchoujo_cd}, ${data.sno}, ${data.shisetsu_cd}, ${data.shisetsu_ver})">`;
    //    html += `[基本情報編集]</a></div></p>`;

    // 点検票 or 防雪柵へのリンク
    if (
      data.sno != null &&
      data.struct_idx != null &&
      data.chk_mng_no != null
    ) {
      if (data.shisetsu_kbn_nm == "防雪柵") {
        html += `<p><span class="btn btn-xs btn-info" ng-click="main.openBsskChildren('${data.sno}', '${data.struct_idx}')">支柱リスト</span></p>`;
      } else {
        html += `<p><a style="cursor:pointer;" ng-click="main.location('/tenken/${data.sno}/${data.struct_idx}/${data.chk_mng_no}')">[点検調査票を開く]</a></p>`;
      }
    }
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
   * 条件選択のサブ画面の表示用
   */
  searchSelect(sel) {
    for (var i = 0; i < this.searchCondition.length; i++) {
      this.searchCondition[i].active = false;
    }
    sel.active = true;
  }

  /**
   * 検索の実行
   */
  searchExec(index) {
    this.searchCondition[index].checked = true;
    this.searchCondition[index].seached = true;
    this.searchCondition[index].active = false;
    this.searchCondition[index].changed = false;
    this.searchCheckChange(index);
  }

  /**
   * 検索条件の変更
   */
  searchChange(index) {
    this.searchCondition[index].changed = true;
    for (var i = 0; i < this.data.length; i++) {
      this.data[i].searchChecked = this.searchCondition[
        this.data[i].kindIndex
      ].checked;
    }
  }

  /**
   * 検索のチェックボックスの変更
   */
  searchCheckChange(index) {
    this.vectorLayers[index].setVisibility(this.searchCondition[index].checked);
  }

  // 検索項目の全てリセット
  all_reset() {
    delete this.srch.shisetsu_cd; // 施設管理番号
    delete this.srch.setti_nendo_front_sel; // 設置年度（前）
    delete this.srch.setti_nendo_back_sel; // 設置年度（後）
    delete this.srch.sicyouson; // 市町村
    delete this.srch.azaban; // 字番
    delete this.srch.sp_start; // 測点（前）
    delete this.srch.sp_end; // 測点（後）
    delete this.srch.rosen_cd; // 路線番号
    delete this.srch.chk_dt_front; // 点検実施年月日前
    delete this.srch.chk_dt_back; // 点検実施年月日後
    delete this.srch.measures_dt_front; // 調査実施年月日前
    delete this.srch.measures_dt_back; // 調査実施年月日後

    // 設置年度に不明を含めるチェックボックスをクリア
    this.srch.include_secchi_null = false;

    // マルチセレクトクリア
    this.multiselect_clear();

    // 検索結果、ソート条件クリア
    this.clearResult();

    this.alert("検索", "リセットしました");
  }

  multiselect_clear() {
    // プルダウンチェックボックスの初期化
    this.srch.shisetsu_kbn_dat_model = [];
    this.srch.struct_idx_dat_model = [];
    this.srch.phase_dat_model = [];
    this.srch.chk_judge_dat_model = [];
    this.srch.measures_judge_dat_model = [];
    this.srch.rosen_dat_model = [];
    this.srch.patrolin_gyousya_dat_model = [];
    this.srch.investigator_gyousya_dat_model = [];
    this.investigator_dat = [];

    if (this.syozoku_cd == 3 || this.syozoku_cd == 4) {
      this.patrolin_dat = [];
      this.patrolin_dat = JSON.parse(
        JSON.stringify(this.mst_patrolin_dat.patrolin_info)
      );
      for (var i = 0; i < this.patrolin_dat.length; i++) {
        // 最初は全部表示なので表示名に部署名を入れる
        this.patrolin_dat[i].label =
          this.patrolin_dat[i].busyo_mei + ":" + this.patrolin_dat[i].label;
      }
      this.srch.patrolin_dat_model = [];
    }

    // 調査員の最初は全てリストに表示するので部署名をセット
    if (this.mst_investigator_dat) {
      this.investigator_dat = JSON.parse(
        JSON.stringify(this.mst_investigator_dat.investigator_info)
      );
      for (var j = 0; j < this.investigator_dat.length; j++) {
        // 最初は全部表示なので表示名に部署名を入れる
        this.investigator_dat[j].label =
          this.investigator_dat[j].busyo_mei +
          ":" +
          this.investigator_dat[j].label;
      }
      this.srch.investigator_dat_model = [];
    }
  }

  // 施設検索の前に件数の取得確認
  searchShisetsuNum() {
    this.clearResult();

    var max_cnt = 700;

    this.waitLoadOverlay = true;

    // 検索結果件数の取得
    this.$http({
      method: "POST",
      url: "api/index.php/MainSchAjax/get_srch_shisetsu_num",
      timeout: this.canceler.promise,
      data: {
        dogen_cd: this.dogen.dogen_cd,
        syucchoujo_cd: this.syucchoujo ? this.syucchoujo.syucchoujo_cd : 0,
        srch: this.srch
      }
    })
      .then(data => {
        this.waitLoadOverlay = false;

        // 結果件数のチェック
        if (Number(data.data[0].sch_result_num) > max_cnt) {
          if (this.isViewSerchComplete) {
            var message =
              "検索結果が" + String(max_cnt) + "件を越えています<br>";
            message +=
              "（" + String(data.data[0].sch_result_num) + "件）<br><br>";
            message += "検索条件を設定し、対象データを絞り込んでください<br>";
            this.alert("検索", message);
          }
          this.isViewSerchComplete = true;
        } else if (Number(data.data[0].sch_result_num) == 0) {
          // 該当なしの場合終了
          var message = "該当するデータがありません";
          this.alert("検索", message);
          delete this.data;
          this.jyoukenkensaku = false;
          this.naiyoukensaku = true;
          this.isViewSerchComplete = true;
        } else {
          // 最大件数を越えていない場合のみ検索処理を実行する
          this.searchShisetsu();
        }
      })
      .finally(data => {
        //      this.waitLoadOverlay = false;
      });
  }

  // 施設検索
  searchShisetsu() {
    this.clearResult();
    var max_cnt = 700;
    this.waitLoadOverlay = true;

    // 検索条件の整理
    this.arrangeSearchCondition();

    this.srch.include_secchi_null = this.srch.include_secchi_null
      ? this.srch.include_secchi_null
      : false;
    this.$http({
      method: "POST",
      url: "api/index.php/MainSchAjax/getSearchFuzokubutsu",
      timeout: this.canceler.promise,
      data: {
        dogen_cd: this.dogen.dogen_cd,
        syucchoujo_cd: this.syucchoujo ? this.syucchoujo.syucchoujo_cd : 0,
        srch: this.srch,
        max_cnt: max_cnt
      }
    })
      .then(data => {
        delete this.data;
        this.jyoukenkensaku = false;
        this.naiyoukensaku = true;
        // 先に件数の確認
        // 処理が終わる条件
        if (data.data.cnt == 0) {
          // 該当なしの場合終了
          var message = "該当するデータがありません";
          this.alert("検索", message);
          this.isViewSerchComplete = true;
          return;
        } else if (data.data.cnt > max_cnt) {
          if (this.isViewSerchComplete) {
            var message =
              "検索結果が" + String(max_cnt) + "件を越えています<br>";
            message +=
              "（" + String(data.data[0].sch_result_num) + "件）<br><br>";
            message += "検索条件を設定し、対象データを絞り込んでください<br>";
            this.isViewSerchComplete = true;
            this.alert("検索", message);
          }
          return;
        }

        // 検索実行時、データがあったのでデータ部を表示
        this.jyoukenkensaku = true;
        this.naiyoukensaku = false;

        this.data = JSON.parse(data.data.data[0].sch_result_row).sch_result;
        this.changeNumItem(); // 数値項目を数値化
        this.resultRecords = Object.keys(this.data).length;

        for (var i = 0; Object.keys(this.data).length > i; i++) {
          // ソート優先順位の設定
          this.setSortPriorityTenkenLink(i);
          this.setSortPriorityConfirmBtn(i);
          // 指定文字数以上の所見の表記を省略
          this.omitDisplayedSyoken(i, 30);
          // 防雪柵の完了状態を設定
          if (this.data[i].shisetsu_kbn == 4) {
            this.setBsskCompleteStatus(i, this.data[i].sno);
          }
          // 年度を決定
          // this.data[i].target_nendo = this.chkWareki(this.data[i].target_dt);
          this.data[i].target_nendo = this.data[i].w_target_nendo;
        }
        // 検索成功時のパラメータを保持する
        this.srch_done.dogen_cd = this.dogen.dogen_cd;
        this.srch_done.syucchoujo_cd = this.syucchoujo
          ? this.syucchoujo.syucchoujo_cd
          : 0;
        this.srch_done.srch = angular.copy(this.srch);

        var args = {};
        args.dogen_cd = this.dogen.dogen_cd;
        args.syucchoujo_cd = this.syucchoujo
          ? this.syucchoujo.syucchoujo_cd
          : 0;
        args.srch_done = this.srch_done;
        args.default_tab = this.default_tab;

        // Map範囲指定
        if (this.default_tab.map) {
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

        // ページ設定
        var srch_cnt;
        if (this.data) {
          srch_cnt = this.data.length;
          this.range = this.getPageList(srch_cnt);
          this.setPage(this.current_page);
        } else {
          this.max_cnt = 0;
          this.current_page = 1;
          this.range = this.getPageList(0);
        }
        this.drawVector();
        this.setCheckedData();
        this.toggleCheckSaveAll();

        // 検索件数のメッセージ
        // この時点で結果がある
        if (this.isViewSerchComplete) {
          var message = "";
          message = this.resultRecords + "件の結果を取得しました";
          this.alert("検索", message);
        }

        // 検索結果の件数表示
        this.isViewSerchComplete = true;

        // 検索が正常に終わったので、検索条件をsessionに保持
        this.$http({
          method: "POST",
          url: "api/index.php/InquirySession/updSrchFuzokubutsu",
          data: {
            kbn: 2,
            upd: 1,
            tenken_params: args
          }
        }).then(data => {
          // セッション上書き
          this.session = data.data;
        });
      })
      .finally(data => {
        this.waitLoadOverlay = false;
      });
  }

  // chkWareki(dt) {
  //   if (!dt) {
  //     return "";
  //   }
  //   // 規定値
  //   var kitei_val;
  //   var gengou = '';
  //   var d = new Date(dt);
  //   // 昭和、平成の判定
  //   if (d.getFullYear() == 1988) {
  //     // 境目
  //     if ((Number(d.getMonth()) + 1) < 4) {
  //       return 'S63年';
  //     } else {
  //       return 'H元年';
  //     }
  //   } else if (d.getFullYear() > 1988) {
  //     kitei_val = (Number(d.getMonth()) + 1) < 4 ? 1989 : 1988;
  //     gengou = "H";
  //   } else if (d.getFullYear() < 1988) {
  //     kitei_val = (Number(d.getMonth()) + 1) < 4 ? 1926 : 1925;
  //     gengou = "S";
  //   }
  //   return gengou + (d.getFullYear() - kitei_val) + '年';
  // }

  // ソート優先順位の設定(項目名：点検調査票)
  setSortPriorityTenkenLink(i) {
    if (this.data[i].shisetsu_kbn == 4) {
      if (this.data[i].sno == null || this.data[i].shichu_cnt == 1) {
        // 3:防雪柵、開けない
        this.data[i].sort_priority_tenken_link = 3;
      } else {
        // 4:防雪柵、開く
        this.data[i].sort_priority_tenken_link = 4;
      }
    } else {
      if (
        this.data[i].sno == null ||
        this.data[i].struct_idx == null ||
        this.data[i].chk_mng_no == null
      ) {
        // 1:防雪柵以外、開けない
        this.data[i].sort_priority_tenken_link = 1;
      } else {
        // 2:防雪柵以外、開く
        this.data[i].sort_priority_tenken_link = 2;
      }
    }
  }

  // 指定文字数以上の所見の表記を省略
  omitDisplayedSyoken(index, maxLength) {
    if (this.data[index].syoken.length > maxLength) {
      this.data[index].syoken =
        this.data[index].syoken.substr(0, maxLength) + "…";
    }
  }

  // ソート優先順位の設定(項目名：施設の健全性)
  setSortPriorityConfirmBtn(i) {
    if (
      this.data[i].shisetsu_cd == null ||
      this.data[i].shisetsu_ver == null ||
      this.data[i].shisetsu_kbn == null ||
      this.data[i].chk_mng_no == null ||
      this.data[i].rireki_no == null
    ) {
      this.data[i].sort_priority_confirm_btn = 0;
    } else {
      // 確認ボタンが有効な場合
      this.data[i].sort_priority_confirm_btn = i + 1;
    }
  }

  // 防雪柵の完了状態を設定
  setBsskCompleteStatus(i, sno) {
    if (
      this.data[i].phase_str_large != this.data[i].min_phase_str ||
      this.data[i].phase_str_large != this.data[i].max_phase_str
    ) {
      this.data[i].phase_str_large_sub = "＊";
    }
  }

  // 検索条件の整理
  arrangeSearchCondition() {
    // 入力→削除した要素の空文字を削除
    if (this.srch.shisetsu_cd != null) {
      if (this.srch.shisetsu_cd.length == 0) {
        delete this.srch.shisetsu_cd; // 施設管理番号
      }
    }

    if (this.srch.sicyouson != null) {
      if (this.srch.sicyouson.length == 0) {
        delete this.srch.sicyouson; // 市町村
      }
    }

    if (this.srch.azaban != null) {
      if (this.srch.azaban.length == 0) {
        delete this.srch.azaban; // 字番
      }
    }

    if (this.srch.sp_start != null) {
      if (this.srch.sp_start.length == 0) {
        delete this.srch.sp_start; // 測点（前）
      }
    }

    if (this.srch.sp_end != null) {
      if (this.srch.sp_end.length == 0) {
        delete this.srch.sp_end; // 測点（後）
      }
    }
  }

  /**
   * 施設の健全性ダイアログを開く
   */
  openShisetsuJudge(param) {
    this.shisetsu_judge_modal = this.$uibModal.open({
      animation: true,
      templateUrl: "views/about.html",
      controller: "AboutCtrl as about",
      size: "lg",
      windowClass: "about-modal",
      resolve: {
        data: () => {
          return param;
        }
      }
    });
    /*this.shisetsu_judge_modal.result.then(() => {
    this.genba_syashin_modal = null;
});*/
  }

  /**
   * 防雪柵の子データ一覧を開く
   */
  openBsskChildren(sno, struct_idx) {
    this.bssk_children_modal = this.$uibModal.open({
      animation: true,
      templateUrl: "views/bssk_children.html",
      controller: "BsskChildrenCtrl as bssk_children",
      size: "lg",
      windowClass: "bssk_children-modal",
      resolve: {
        data: () => {
          return {
            sno: sno,
            struct_idx: struct_idx
          };
        }
      }
    });
  }

  // Excel2013へ出力チェックボックスクリック時
  excel_ver_click() {
    this.$cookies.excel_ver = this.excel_ver;
    //console.debug(this.excel_ver);
  }

  // 基本情報編集呼出しラッパー(Map表示から呼ばれる)
  editBaseInfoWrapper(
    kbn,
    dogen_cd,
    syucchoujo_cd,
    sno,
    shisetsu_cd,
    shisetsu_ver
  ) {
    var data = {};

    data.dogen_cd = dogen_cd;
    data.syucchoujo_cd = syucchoujo_cd;
    data.sno = sno;
    data.shisetsu_cd = sno;
    data.shisetsu_cd = shisetsu_cd;
    data.shisetsu_ver = shisetsu_ver;

    // 基本情報編集の呼出し(検索リストからの実行と同じ状態)
    this.editBaseInfo(kbn, data);
  }

  // 基本情報編集
  editBaseInfo(kbn, data) {
    // 新規
    // 新規時は、選択された建設管理部と出張所を渡す
    // 基本情報呼び出し引数
    if (kbn == 0 && this.syucchoujo.syucchoujo_cd == 0) {
      this.alert("基本情報登録", "出張所を選択してください");
      return;
    }

    this.$location.path("/shisetsu_info");
  }

  /**
   * チェックされた点検票データをエクセルに出力する
   */
  submitCheckedDataToExcel(kbn) {
    if (!this.data) {
      this.alert("点検票出力", "検索が実行されていません");
      return;
    }

    // 20161020_形式なしでもダウンロード可能にする
    // 形式未入力のアラートは最新点検出力のみ
    // ※前回(ストック含む)の場合、作成済みなので考慮しない
    /*
        if (this.account == 9999999) {} else {
          if (kbn == 1) {
            if (this.keishiki_not_input) {
              this.alert('点検票出力', this.keishiki_not_input);
              return;
            }
          }
        }
    */

    if (!this.checked_data) {
      this.alert("点検票出力", "対象のデータを選択してください");
      return;
    }

    // サーバーへ送る
    if (kbn == 0) {
      this.alert("点検票の出力", "前回の点検票が出力されます");
    } else if (kbn == 1) {
      this.alert("点検票の出力", "最新の点検票が出力されます");
    }

    // 点検票出力(zip)パラメータ
    this.post_data.mode = kbn;
    this.post_data.checked_data = JSON.stringify(this.checked_data);
    this.post_data.excel_ver = this.excel_ver ? 1 : 0;
  }

  /**
   * 検索結果リストをエクセルに出力する
   */
  submitListToExcel() {
    if (!this.data) {
      this.alert("リスト出力", "検索が実行されていません");
      return;
    }

    // リスト出力パラメータ
    this.post_data.dogen_cd = this.srch_done.dogen_cd;
    this.post_data.syucchoujo_cd = this.srch_done.syucchoujo_cd;
    this.post_data.srch = JSON.stringify(this.srch_done.srch);
    this.post_data.excel_ver = this.excel_ver ? 1 : 0;
  }

  /**
   * 検索結果リストを出力する
   */
  submitList() {
    if (!this.data || this.data.length == 0) {
      this.alert("リスト出力", "検索が実行されていません");
      return;
    }

    // リスト出力パラメータ
    this.post_data.srch = JSON.stringify(this.data);
  }

  /*    // 出張所プルダウン変更
      chg_syucchoujo() {
          this.displayIndex = -1;
          this.map = null;
          this.projection3857 = new OpenLayers.Projection("EPSG:3857");
          this.projection4326 = new OpenLayers.Projection("EPSG:4326");
          this.selectControl = null;
          this.vectorLayers = [];
  // マップの緯度経度変更
          this.displayMap() {
              setTimeout(
                  () => {
                      this.initMap();
                      this.drawVector();
                  }, 500);
          }
      }*/

  /**
   * 確認用メソッド
   * @reterun Promise(OK時にresolve)
   */
  confirm(content, nomal) {
    var ok;
    var ng;
    if (nomal == 0) {
      // OK赤CANCEL青
      ok = "btn-danger";
      ng = "btn-info";
    } else {
      // OK青CANCEL赤
      ok = "btn-info";
      ng = "btn-danger";
    }
    var deferred = this.$q.defer();
    $.confirm({
      title: "確認",
      content: content,
      confirmButtonClass: ok,
      cancelButtonClass: ng,
      confirmButton: "OK",
      cancelButton: "Cancel",
      animation: "RotateYR",
      confirm: () => {
        deferred.resolve(true);
      },
      cancel: function() {
        deferred.reject(false);
      }
    });
    return deferred.promise;
  }

  /**
   * アラートメソッド
   *
   */
  alert(title, msg) {
    $.alert({
      title: title,
      content: msg,
      confirmButtonClass: "btn-info",
      confirmButton: "OK",
      animation: "RotateYR"
    });
  }

  // Excel全出力チェックボックス(全選択、全クリア)
  SetExcelChkStatus() {
    this.SetSrchDataChecked(this.excel_all_chk_flg);
  }

  // 検索結果リストの全てのチェックボックスを更新
  SetSrchDataChecked(checked) {
    if (this.data == null) {
      return;
    }

    for (var i = 0; Object.keys(this.data).length > i; i++) {
      this.data[i].chkexcel = checked;
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

  //  isValidCheckedData() {
  //
  //    // 形式未入力のものが選択されている場合
  //    if(this.keishiki_non_input != null) {
  //      return false;
  //    }
  //
  //    // チェックされたデータが無い場合
  //    if(this.checked_data == null) {
  //      return false;
  //    }
  //
  //    return true;
  //  }

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

  // 建管変更時処理
  // 本庁権限のみ
  chgDogen() {
    // 検索結果が存在しない
    if (this.data == null) {
      this.chgKanriNoSrch(1);
      return;
    }

    // 検索結果が存在
    this.confirm("検索結果を初期化してよろしいですか？", 0)
      .then(() => {
        // 結果クリア
        this.clearResult();

        // Map範囲指定
        if (this.default_tab.map) {
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

        // Session
        var args = {};
        args.dogen_cd = this.dogen.dogen_cd;
        args.syucchoujo_cd = this.syucchoujo
          ? this.syucchoujo.syucchoujo_cd
          : 0;
        args.srch_done = this.srch_done;
        args.default_tab = this.default_tab;
        this.$http({
          method: "POST",
          url: "api/index.php/InquirySession/updSrchFuzokubutsu",
          data: {
            kbn: 2,
            upd: 1,
            tenken_params: args
          }
        }).then(data => {
          // セッション上書き
          this.session = data.data;
          // マネージメントエリア更新
          this.mngarea_update(
            this.$http,
            this.dogen.dogen_cd,
            this.syucchoujo ? this.syucchoujo.syucchoujo_cd : 0
          ).then(() => {
            // mngarea上書き
            this.mng_dogen_cd = this.session.mngarea.dogen_cd;
            this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
          });
        });
      })
      .catch(() => {
        // キャンセル時は戻す
        this.dogen = this.keep_dogen;
        this.syucchoujo = this.keep_syucchoujo;
      })
      .finally(() => {
        // 点検会社、点検員、路線の変更
        // 路線
        this.filterRosen = this.filterRosens();
        // 点検会社、点検員
        this.set_patrolin_by_kenkan();
      });
  }

  // 出張所変更時処理
  chgSyucchoujo() {
    // 他出張所選択から全てを選択した時の対応
    if (this.syucchoujo == null) {
      this.syucchoujo = {};
      this.syucchoujo.syucchoujo_cd = 0;
    }

    // 検索結果が存在しない
    if (this.data == null) {
      this.chgKanriNoSrch(2);
      return;
    }

    this.confirm("検索結果を初期化してよろしいですか？", 0)
      .then(() => {
        // 結果クリア
        this.clearResult();

        // Map範囲指定
        //      if (this.syucchoujo.syucchoujo_cd != 0) {
        if (this.default_tab.map) {
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

        // 条件表示変更
        this.jyoukenkensaku = false;
        this.naiyoukensaku = true;

        // 保存用
        this.keep_syucchoujo = this.syucchoujo;

        // Session
        var args = {};
        args.dogen_cd = this.dogen.dogen_cd;
        args.syucchoujo_cd = this.syucchoujo
          ? this.syucchoujo.syucchoujo_cd
          : 0;
        args.srch_done = this.srch_done;
        args.default_tab = this.default_tab;
        this.$http({
          method: "POST",
          url: "api/index.php/InquirySession/updSrchFuzokubutsu",
          data: {
            kbn: 2,
            upd: 1,
            tenken_params: args
          }
        }).then(data => {
          // セッション上書き
          this.session = data.data;
          // マネージメントエリア更新
          this.mngarea_update(
            this.$http,
            this.dogen.dogen_cd,
            this.syucchoujo ? this.syucchoujo.syucchoujo_cd : 0
          ).then(() => {
            // mngarea上書き
            this.mng_dogen_cd = this.session.mngarea.dogen_cd;
            this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
          });
        });
      })
      .catch(() => {
        // キャンセル時は戻す
        this.syucchoujo = this.keep_syucchoujo;
      })
      .finally(() => {
        // 点検会社、点検員、路線の変更
        // 路線
        this.filterRosen = this.filterRosens();
        // 点検会社、点検員
        this.set_patrolin_by_syucchoujo();
      });
  }

  clearResult() {
    // 検索結果の削除
    this.data = null;
    this.srch_done = {};
    this.checked_data = null;
    this.keishiki_not_input = null;

    this.max_page = 0;
    this.current_page = 1;

    if (this.map) {
      // 地図の初期化後だけ
      if (this.selectControl) {
        this.selectControl.unselectAll();
      }
      // VectorLayerの削除
      if (this.vectorLayers[0] != null) {
        for (var i = 0; i < 7; i++) {
          this.vectorLayers[i].removeAllFeatures();
        }
      }
    }

    // Excel出力全選択
    this.excel_all_chk_flg = true;
    this.save_all_chk_flg = true;

    // ソート条件クリア
    this.sort_order = "['seq_no']";
    this.reverse = false;
    this.sort_style = [];
    this.sort_style[this.sort_order] = {
      color: "#217dbb"
    };
  }

  // 選択建管/出張所でフィルタ
  // 呼ばれるタイミングは、オープン時/建管/出張所変更時
  filterRosens() {
    this.srch.rosen_dat_model = [];

    // 出張所選択
    if (this.syucchoujo.syucchoujo_cd != 0) {
      // 出張所フィルタ
      var syucchoujo_cd = this.syucchoujo.syucchoujo_cd;
      return this.rosen_dat.rosen_info.filter(function(value, index) {
        if (syucchoujo_cd == value.syucchoujo_cd) {
          return true;
        }
        return false;
      });
    } else {
      // 建管フィルタ
      var dogen_cd = this.dogen.dogen_cd;
      return this.rosen_dat.rosen_info.filter(function(value, index) {
        if (dogen_cd == value.dogen_cd) {
          return true;
        }
        return false;
      });
    }
  }

  // 建管、出張所変更時、検索結果無しの振る舞い用
  // kbn:1 土現
  //     2 出張所
  chgKanriNoSrch(kbn) {
    if (kbn == 1) {
      // 建管変更時は出張所全選択
      if (
        this.syozoku_cd == 1 ||
        this.syozoku_cd == 2 ||
        this.syozoku_cd == 10001
      ) {
        this.syucchoujo = {};
        this.syucchoujo.syucchoujo_cd = 0;
      }

      // 土現
      //      if (this.syucchoujo.syucchoujo_cd != 0) {
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

      // 保存用
      this.keep_dogen = this.dogen;
      this.keep_syucchoujo = this.syucchoujo;

      // 路線
      this.filterRosen = this.filterRosens();

      // 点検会社、点検員
      this.set_patrolin_by_kenkan();
    } else {
      // 他出張所選択から全てを選択した時の対応
      //      if(this.syucchoujo == null) {
      //        this.syucchoujo = {};
      //        this.syucchoujo.syucchoujo_cd = 0;
      //      }

      //      if (this.syucchoujo.syucchoujo_cd != 0) {
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

      // 保存用
      this.keep_syucchoujo = this.syucchoujo;

      // 路線
      this.filterRosen = this.filterRosens();

      // 点検会社、点検員
      this.set_patrolin_by_syucchoujo();
    }
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

  /*********************************
   * * 「一括確定保存」
   ********************************/
  /**
   * * checkbox: 「一括提出」の全てのチェックボックスを切替時
   */
  toggleCheckSaveAll() {
    if (_.isNull(this.data)) {
      // 検索結果が無い場合は終了
      return;
    }

    // 各チェックボックスが同じ状態か
    const target_chk_save = this.getTargetCheckSave();
    _.map(this.data, data => {
      if (!_.includes(target_chk_save, data.check_shisetsu_judge)) {
        // 健全性 1, 2 の行以外は continue
        // 211116: 3,4 も対象とするように仕様変更
        return;
      }
      data.chksave = this.save_all_chk_flg;
    });
    this.setCheckedSaveData();
  }

  /**
   * * checkbox: 「一括提出」のチェックボックスを切替時
   */
  toggleCheckSave() {
    this.setCheckedSaveData();
  }

  /**
   * * checkbox: 一括提出
   * 「一括確定保存」用にチェックされたデータを保存する
   */
  setCheckedSaveData() {
    if (!this.data) {
      // 検索結果が無い場合は終了
      return;
    }

    const save_checked_data = _(this.data).filter(data => {
      return !!data.chksave;
    }).map(d => {
      return {
        sno: d.sno,
        chk_mng_no: d.chk_mng_no,
        chk_times: d.chk_times,
        rireki_no: d.rireki_no,
        struct_idx: d.struct_idx,
        syucchoujo_cd: d.syucchoujo_cd,
        shisetsu_kbn: d.shisetsu_kbn,
        shisetsu_keishiki_cd: d.shisetsu_keishiki_cd
      };
    }).value();

    this.save_checked_data = save_checked_data;
  }

  /**
   * * button: 「一括確定保存」押下時処理
   */
  onFixedSave() {
    let self = this;

    if (_.isNil(this.data) || this.data.length === 0) {
      this.alert('一括確定保存', '検索が実行されていません');
      return;
    }

    if (_.isNil(this.save_checked_data) || this.save_checked_data.length === 0) {
      // checkbox: 「一括提出」 チェック無し
      this.alert('一括確定保存', '対象のデータを選択してください');
      return;
    }

    const message = '一括確定保存してよろしいですか？';
    this.confirm(message, 0).then(() => {
      this.bulkFixedSave(self);
    });
  }

  /**
   * * 「一括確定保存」処理
   */
  async bulkFixedSave(self) {
    let baseinfo = {},
        tenken_kasyo = [];

    this.waitSaveOverlay = true;
    _.each(this.save_checked_data, data => {

      super.start(this.$http).then(() => {
       // 基本情報と部材情報を取得
        return this.$http({
          method: 'GET',
          url: 'api/index.php/CheckListSchAjax/get_kihon_chkmng_buzai',
          params: {
            chk_mng_no: data.chk_mng_no,
            sno: data.sno,
            struct_idx: data.struct_idx
          }
        });
      }).then(result => {
        baseinfo = _.head(result.data.kihon_chkmng);
        tenken_kasyo = JSON.parse(_.head(result.data.buzai_row).buzai_row);
        console.log(baseinfo);
        console.log(tenken_kasyo);

        this.fixedSave({
          baseinfo: baseinfo,
          sno: data.sno,
          dogen_cd: baseinfo.dogen_cd,
          syucchoujo_cd: baseinfo.syucchoujo_cd,
          tenken_kasyo: tenken_kasyo
        }, self);
      }).finally(data => {
        //this.waitSaveOverlay = false;
      });
    });
    this.waitSaveOverlay = await false;
    await this.windowUnlock();
    await this.alert('一時保存', '保存が完了しました');
  }

  /**
   * * 「一括確定保存」処理
   */
  fixedSave(param, self) {
    console.time('fixedSave');

    // 基本情報、部材以下情報の更新(＝一時保存)
    this.$http({
      method: 'POST',
      url: 'api/index.php/CheckListEdtAjax/set_chkdata',
      data: {
        baseinfo: param.baseinfo,
        buzaidata: param.tenken_kasyo,
        mode: 'update'
      }

    }).then(data => {
      // フェーズ移行(フェーズ更新、履歴NOインクリメント)
      //this.current_phase = param.baseinfo.phase;
      self.changePhase(param);

      // 基本情報、部材以下情報の更新(フェーズ、履歴NO更新後)
      // ※mode未使用
      return this.$http({
        method: 'POST',
        url: 'api/index.php/CheckListEdtAjax/set_chkdata',
        data: {
          baseinfo: param.baseinfo,
          buzaidata: param.tenken_kasyo,
          mode: 'insert'
        }
      });
    }).then(data => {
      //return this.imageSave();

    }).then(data => {
      // 防雪柵管理情報の更新
      //if (param.baseinfo.shisetsu_kbn == 4) {
      //return this.saveBsskMngInfo(true);
      //}

    }).then(data => {

      // 施設の健全性の取得
      return this.$http({
        method: 'GET',
        url: 'api/index.php/CheckListSchAjax/get_shisetsu_judge',
        params: {
          sno: param.sno,
          chk_mng_no: param.baseinfo.chk_mng_no
        }
      });
    }).then(data => {
      //this.shisetsujudges = JSON.parse(data.data[0].measures_judge_row);
      //console.log(this.shisetsujudges);

      // Excelの生成
      return this.$http({
        method: 'GET',
        url: 'api/index.php/OutputExcel/save_chkData',
        params: {
          sno: param.sno,
          chk_mng_no: param.baseinfo.chk_mng_no,
          struct_idx: param.baseinfo.struct_idx,
          excel_ver: this.excel_ver ? 1 : 0
        }
      });
    }).then(data => {
      // nop.
      console.log(data);
    }).catch(error => {
      console.error(error);
    }).finally(() => {
      console.timeEnd('fixedSave');
      console.log(param.sno, param.baseinfo.chk_mng_no, param.baseinfo.struct_idx, this.excel_ver);
      //      this.waitSaveOverlay = false;
      //      this.windowUnlock();
      //      this.$window.location.reload(false);
    });
  }

  // フェーズ移行処理
  // (フェーズ更新、履歴NOインクリメント)
  changePhase(param) {
    // 点検箇所の健全性のチェック
    let tenken_kasyo = param.tenken_kasyo;
    let baseinfo = param.baseinfo;
    let max_judge = this.getWorstJudge(tenken_kasyo);

    // フェーズ移行処理
    // ※仕様変更により、フェーズ4は措置として独立。そのため、フェーズ4へ明示的に移行することはない。
    switch (Number(baseinfo.phase)) {
      case 1:
        // 点検（Ⅰ、Ⅱ、Ⅲ、Ⅳ）

        // 点検箇所の健全性Ⅲの場合フェーズを2(スクリーニング)
        // それ以外の場合は5(完了)
        if (max_judge === 3) {
          baseinfo.phase = 2;
          baseinfo.rireki_no = Number(baseinfo.rireki_no) + 1;
        } else {
          baseinfo.phase = 5;
          baseinfo.rireki_no = Number(baseinfo.rireki_no) + 1;
        }
        break;

      case 2:
        // スクリーニング（Ⅱ、Ⅲ）
        // 新規登録～入力修正＝（確定）保存
        // 点検時健全性がⅢの点検箇所の全てのスクリーニングが完了
        // ※スクリーニングの完了は、スクリーニング完了チェックボックスのチェックと点検前損傷、点検前健全性の変更を保存。

        // 点検箇所の健全性Ⅲの場合フェーズを3(詳細調査)
        // それ以外の場合は5(完了)
        if (max_judge === 3) {

          // ************** H29年度改修 **************
          // ここに来た場合以下のパターンに分かれる
          // 1.点検時健全性Ⅲ・詳細調査
          // 2.点検時健全性Ⅲ・完了
          // この判定は、最悪の健全性を取得する際の処理では
          // 記述ができなかったので、再度行う

          // 詳細調査の判定
          if (this.judgeDetailInvestigation(tenken_kasyo)) {
            baseinfo.phase = 3;
          } else {
            baseinfo.phase = 5;
          }
          // ************** H29年度改修 **************
          baseinfo.rireki_no = Number(baseinfo.rireki_no) + 1;
        } else if (max_judge === 1 || max_judge === 2) {
          baseinfo.phase = 5;
          baseinfo.rireki_no = Number(baseinfo.rireki_no) + 1;
        }
        break;

      case 3:
        // 詳細調査（Ⅱ、Ⅲ）
        // 詳細調査の完了
        // ※詳細調査の完了は、調査日の入力と、点検前損傷、点検前健全性の変更を保存

        baseinfo.phase = 5;
        baseinfo.rireki_no = Number(baseinfo.rireki_no) + 1;

        // 差戻し機能追加による変更
        // 詳細調査が完了し、フェーズが完了になった場合、
        // 詳細調査である点検箇所について、全て詳細調査済とする
        var _buzai = tenken_kasyo.buzai;
        var _buzai_detail_row;
        var _tenken_kasyo_row;

        for (var i = 0; Object.keys(_buzai).length > i; i++) {
          _buzai_detail_row = _buzai[i].buzai_detail_row;
          for (var j = 0; Object.keys(_buzai_detail_row).length > j; j++) {
            _tenken_kasyo_row = _buzai_detail_row[j].tenken_kasyo_row;
            for (var k = 0; Object.keys(_tenken_kasyo_row).length > k; k++) {
              // 調査(方針)が詳細調査の点検箇所があれば詳細調査済にする
              if (_tenken_kasyo_row[k].check_policy_str == this.houshin.houshins[2].houshin) {
                _tenken_kasyo_row[k].check_policy = this.houshin.houshins[3].houshin_cd;
                _tenken_kasyo_row[k].check_policy_str = this.houshin.houshins[3].houshin;
              }
            }
          }
        }

        break;

      case 5:
        // 完了
        // 全ての措置が完了した
        // 健全性Ⅲ、Ⅳが存在しない、または存在する場合は措置日記入済み

        // 完了から移行するフェーズは無し
        // 措置の保存があるので保存処理へ
        break;

      case 6:
        // 強制終了
        // 途中で強制終了した場合、フェーズを6(強制終了)、履歴番号を加算
        // 新規作成などのタイミングで既存の未完入力データを強制終了に移行する
        baseinfo.phase = 6;
        baseinfo.rireki_no = Number(baseinfo.rireki_no) + 1;
        break;

      default:
        break;
    }
  }

  // 健全性がⅢかつ措置後の健全性が無い点検箇所を検索
  getWorstJudge(tenken_kasyo) {

    let _worstJudge = 0;
    let _currentJudge = 0;

    let _buzai = tenken_kasyo.buzai;
    let _buzai_detail_row;
    let _tenken_kasyo_row;

    for (var i = 0; Object.keys(_buzai).length > i; i++) {
      _buzai_detail_row = _buzai[i].buzai_detail_row;

      for (var j = 0; Object.keys(_buzai_detail_row).length > j; j++) {
        _tenken_kasyo_row = _buzai_detail_row[j].tenken_kasyo_row;

        for (var k = 0; Object.keys(_tenken_kasyo_row).length > k; k++) {

          // 健全性Ⅳは無視（Ⅰ、Ⅱ、Ⅲのいずれかを取得）
          if (Number(_tenken_kasyo_row[k].check_judge) == 0 || Number(_tenken_kasyo_row[k].check_judge) == 4) {
            continue;
          }
          _currentJudge = Number(_tenken_kasyo_row[k].check_judge);

          if (Number(_tenken_kasyo_row[k].check_judge) == 3) {
            if (Number(_tenken_kasyo_row[k].measures_judge) == 1 || Number(_tenken_kasyo_row[k].measures_judge) == 2) {
              _currentJudge = Number(_tenken_kasyo_row[k].measures_judge);
            }
          }

          // 健全性が悪い場合
          if (_currentJudge > _worstJudge) {
            _worstJudge = _currentJudge;
          }
        }
      }
    }

    return Number(_worstJudge);
  }

  /***
   * スクリーニング時の詳細調査or完了のチェック
   *
   * スクリーニング時に健全性がⅢのデータを全てチェックする。
   * チェックは、調査(方針)が詳細調査なのか完了なのか
   * 一つでも詳細調査がある場合trueを返却し、全て完了の場合はfalseを返却する
   *
   ***/
  judgeDetailInvestigation(tenken_kasyo) {

    let detail_investigation = false;

    let _buzai = tenken_kasyo.buzai;
    let _buzai_detail_row;
    let _tenken_kasyo_row;

    for (let i = 0; Object.keys(_buzai).length > i; i++) {
      _buzai_detail_row = _buzai[i].buzai_detail_row;

      for (let j = 0; Object.keys(_buzai_detail_row).length > j; j++) {
        _tenken_kasyo_row = _buzai_detail_row[j].tenken_kasyo_row;

        for (let k = 0; Object.keys(_tenken_kasyo_row).length > k; k++) {

          // 健全性Ⅲ以外は無視
          if (Number(_tenken_kasyo_row[k].check_judge) != 3) {
            continue;
          }
          // 調査(方針)が詳細調査の点検箇所があれば次は詳細調査
          if (_tenken_kasyo_row[k].check_policy_str == this.houshin.houshins[2].houshin) {
            detail_investigation = true;
            break;
          }
        }
      }
    }
    return detail_investigation;
  }

  isCheckSaveDisabled(data) {
    const target_chk_save = this.getTargetCheckSave();
    return !_.includes(target_chk_save, data.check_shisetsu_judge);
  }

  // 「一括保存」対象の健全性
  getTargetCheckSave() {
    return [1, 2, 3, 4];
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

  //  createStockTenkenData() {
  //    var stock_num = 0;
  //
  //    this.$http({
  //      method: 'GET',
  //      url: 'api/index.php/OutputExcel/create_stock_data',
  //      params: {
  //        dogen_cd: this.dogen.dogen_cd,
  //        syucchoujo_cd: this.syucchoujo.syucchoujo_cd
  //      }
  //    });
  //  }

  //  sendLoop() {
  //    for(this.index = 0; stock_num > this.index; this.index++) {
  //      createStockExcel(this.stocks[this.index].sno, this.stocks[this.index].chk_mng_no, this.stocks[this.index].struct_idx);
  //    }
  //  }

  //  createStockExcel(sno, chk_mng_no, struct_idx) {
  //    // Excelの生成
  //    return this.$http({
  //      method: 'GET',
  //      url: 'api/index.php/OutputExcel/save_chkData',
  //      params: {
  //        sno: sno,
  //        chk_mng_no: chk_mng_no,
  //        struct_ids: struct_idx,
  //        excel_ver: 0
  //      }
  //    });
  //  }
}

let angModule = require("../app.js");
angModule.angApp.controller("MainCtrl", [
  "$scope",
  "$filter",
  "$http",
  "$uibModal",
  "$cookies",
  "$compile",
  "$location",
  "$q",
  "$document",
  function(
    $scope,
    $filter,
    $http,
    $uibModal,
    $cookies,
    $compile,
    $location,
    $q,
    $document
  ) {
    return new MainCtrl(
      $scope,
      $filter,
      $http,
      $uibModal,
      $cookies,
      $compile,
      $location,
      $q,
      $document
    );
  }
]);
