"use strict";

var BaseCtrl = require("./base.js");

class RaftargetCtrl extends BaseCtrl {
  constructor($scope, $http, $location, $uibModal, $anchorScroll, $routeParams, $q, $route, $window) {
    super({
      $location: $location,
      $scope: $scope,
      $http: $http,
      $q: $q,
    });

    // Angularの初期化
    this.$scope = $scope;
    this.$http = $http;
    this.$uibModal = $uibModal;
    this.$location = $location;
    this.$anchorScroll = $anchorScroll;
    this.$route = $route;
    this.$q = $q;
    this.$window = $window;

    // 初期処理
    this.initVariable();

    // ログイン情報
    super.start(this.$http).then(() => {

      // session情報を保持
      this.ath_dogen_cd = this.session.ath.dogen_cd;
      this.ath_syucchoujo_cd = this.session.ath.syucchoujo_cd;
      this.ath_syozoku_cd = this.session.ath.syozoku_cd;
      this.ath_account_cd = this.session.ath.account_cd;
      this.mng_dogen_cd = this.session.mngarea.dogen_cd;
      this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
      this.imm_url = this.session.url;
      this.waitLoadOverlay = true;

      return this.$http({
        method: 'GET',
        url: 'api/index.php/RafTargetAjax/initRafTarget',
        params: {
          dogen_cd: this.mng_dogen_cd,
          syucchoujo_cd: this.mng_syucchoujo_cd,
          syozoku_cd: this.ath_syozoku_cd,
        }
      });
    }).then((data) => {

      var json = data.data;

      // 年度リスト
      this.secchi_nendo = data.data.wareki_list;

      // 建管_出張所データ抽出
      this.dogen_syucchoujo_dat = JSON.parse(json.dogen_syucchoujo[0].dogen_row);
      // 施設区分
      this.shisetsu_kbn_dat = json.shisetsu_kbn;
      this.srch.shisetsu_kbn_all_cnt = this.shisetsu_kbn_dat.length;
      // 路線
      this.rosen_dat = json.rosen;
      this.srch.rosen_all_cnt = this.rosen_dat.length;
      // フェーズ
      this.phase_dat = json.phase;
      this.srch.phase_all_cnt = this.phase_dat.length;
      // 健全性(点検時)
      this.chk_judge_dat = json.judge;
      this.srch.chk_judge_all_cnt = this.chk_judge_dat.length;
      // 健全性(措置後)
      this.measures_judge_dat = json.judge;
      this.srch.measures_judge_all_cnt = this.measures_judge_dat.length;
      // 点検会社
      this.gyousya_dat = json.chk_gyousya;
      this.srch.gyousya_all_cnt = this.gyousya_dat.length;

      // 前画面からの建管・出張所を元に
      // 建管と出張所の関係をセット（建管は必ずある）
      // 選択時は該当の建管をセット
      for (var k = 0; k < this.dogen_syucchoujo_dat.dogen_info.length; k++) {
        var data_d = this.dogen_syucchoujo_dat.dogen_info[k];
        if (data_d.dogen_cd == this.mng_dogen_cd) {
          this.dogen = data_d;
          this.syucchoujo = {};
          this.syucchoujo.syucchoujo_cd = 0;
          for (var l = 0; l < data_d.syucchoujo_row.syucchoujo_info.length; l++) {
            var data_s = data_d.syucchoujo_row.syucchoujo_info[l];
            // 維持管理で選択された出張所をセット
            if (data_s.syucchoujo_cd == this.mng_syucchoujo_cd) {
              this.syucchoujo = data_s;
            }
          }
        }
      }
      // 路線の絞り込み
      this.filterRosen = this.filterRosens();
      this.srch.rosen_all_cnt = this.filterRosen.length;

      // 業者フィルター
      this.filterGyousya = this.filterGyousyas();
      this.srch.gyousya_all_cnt = this.filterGyousya.length;

      var srch_cnt = json.cnt; // 件数

      // 検索結果がある場合はセット
      if (srch_cnt > 0) {
        // 検索項目
        this.srch = json.srch;
        var message;

        // 700件を超えている場合、表示・取得をクリアし再絞込みしてもらう
        if (srch_cnt > this.max_srch_cnt) {
          this.clearResult();
          delete data.data;
          message = '検索結果が' + String(this.max_srch_cnt) + '件を越えています<br>';
          message += '（' + String(srch_cnt) + '件）<br><br>';
          message += '検索条件を設定し、対象データを絞り込んでください<br>';
          return this.alert('検索', message);
        } else if (srch_cnt === 0) {
          message = '該当するデータがありません';
          this.jyoukenkensaku = false;
          this.naiyoukensaku = true;
          this.clearResult();
          delete data.data;
          return this.alert('検索', message);
        } else {
          this.data = json.shisetsu_info;
          this.srch_cnt = srch_cnt;
          this.changeNumItem(); // 数値項目を数値化
          // 登録済み件数
          this.rgst_num = this.getRgstNum();
          this.bousetsusaku_parent = this.getBousetsusakuParentNum();
          this.chk_num = this.rgst_num;

          if (this.rgst_num == this.srch_cnt || this.rgst_num + this.bousetsusaku_parent == this.srch_cnt) {
            this.all_checkbox = true;
          }

          this.range = this.getPageList(this.srch_cnt);
          this.current_page = 1; // 検索後は1ページ指定
          this.setPage(this.current_page);

          // 検索実行時、条件を指定して検索を閉じる
          this.jyoukenkensaku = true;
          this.naiyoukensaku = false;

        }
      }
    }).finally(() => {
      this.waitLoadOverlay = false; // 読込み中です
    });
  }

  // 初期変数設定
  initVariable() {

    // 変数の初期化
    this.jyoukenkensaku = false;
    this.naiyoukensaku = true;
    this.other = false;

    // 検索最大値
    this.max_srch_cnt = 5000;

    // リストページャ初期化
    this.max_page = 0;
    this.current_page = 1;
    this.items_per_page = 50; // 50件固定

    // 設置年度の値
    // var date = new Date(); // 現在日付を取得
    // var year = date.getFullYear();
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

    // 施設検索の検索条件
    this.srch = {};

    // 設置年度に不明を含めるチェックボックスをクリア
    this.srch.include_secchi_null = false;
    this.srch.not_chk_year = 10;
    // プルダウンチェックボックス設定
    this.extraSettings = {
      externalIdProp: '',
      buttonClasses: 'btn btn-default btn-xs btn-block',
      scrollable: true,
      scrollableHeight: "350px",
    };
    this.translationTexts = {
      checkAll: '全て',
      uncheckAll: '全て外す',
      buttonDefaultText: '選択',
      dynamicButtonTextSuffix: "個選択"
    };

    this.srch.shisetsu_kbn_dat_model = [];
    this.srch.phase_dat_model = [];
    this.srch.chk_judge_dat_model = [];
    this.srch.measures_judge_dat_model = [];
    this.srch.rosen_dat_model = [];
    this.srch.gyousya_dat_model = [];

    this.select_not_year = [
      {
        value: -1,
        label: "すべて選択する"
      }, {
        value: 1,
        label: "過去1年以内に点検していない施設"
      }, {
        value: 2,
        label: "過去2年以内に点検していない施設"
      }, {
        value: 3,
        label: "過去3年以内に点検していない施設"
      }, {
        value: 4,
        label: "過去4年以内に点検していない施設"
      }, {
        value: 5,
        label: "過去5年以内に点検していない施設"
      }, {
        value: 6,
        label: "過去6年以内に点検していない施設"
      }, {
        value: 7,
        label: "過去7年以内に点検していない施設"
      }, {
        value: 8,
        label: "過去8年以内に点検していない施設"
      }, {
        value: 9,
        label: "過去9年以内に点検していない施設"
      }, {
        value: 10,
        label: "過去10年以内に点検していない施設"
      }
    ];
    this.gyousya_dat_per_dogen = {};
    this.gyousya_dat_per_dogen.gyousya_info = [];
    this.gyousya_dat = {};
    this.gyousya_dat.gyousya_info = [];

    // ソート条件
    this.sort_order = "['seq_no']"; // 項目名
    this.reverse = false; // 昇順、降順
    this.sort_style = [];

    // 数値項目配列
    this.numItem = ['struct_idx', 'rosen_cd', 'sp', 'secchi_yyyy'];

    // 検索結果のソート条件
    this.sort_identifiers = [
      ['shisetsu_kbn_nm', 'seq_no'],
      ['-shisetsu_kbn_nm', 'seq_no'],
      ['shisetsu_keishiki_nm', 'seq_no'],
      ['-shisetsu_keishiki_nm', 'seq_no'],
      ['target_dt_ymd', 'seq_no'],
      ['-target_dt_ymd', 'seq_no'],
      ['shisetsu_cd', 'seq_no'],
      ['-shisetsu_cd', 'seq_no'],
      ['struct_idx', 'seq_no'],
      ['-struct_idx', 'seq_no'],
      ['rosen_cd', 'seq_no'],
      ['-rosen_cd', 'seq_no'],
      ['rosen_nm', 'seq_no'],
      ['-rosen_nm', 'seq_no'],
      ['sp', 'seq_no'],
      ['-sp', 'seq_no'],
      ['secchi_yyyy', 'secchi', 'seq_no'],
      ['-secchi_yyyy', 'secchi', 'seq_no'],
      ['lr', 'lr_str', 'seq_no'],
      ['-lr', 'lr_str', 'seq_no'],
      ['address', 'seq_no'],
      ['-address', 'seq_no'],
      ['phase', 'seq_no'],
      ['-phase', 'seq_no']
    ];
  }

  anchorScroll(anchor) {
    this.$location.hash(anchor);
    this.$anchorScroll();
    this.$location.url(this.$location.path());
    if (anchor == 'shisetsuinfo') {
      this.shisetsuinfo = false;
    } else if (anchor == 'tenkenrireki') {
      this.tenkenrireki = false;
    } else if (anchor == 'other') {
      this.other = false;
    }
  }

  // 検索項目の全てリセット
  all_reset() {
    this.srch.not_chk_year = 10;
    delete this.srch.zennen_check; // 前年度検索チェック
    delete this.srch.shisetsu_cd; // 施設管理番号
    delete this.srch.setti_nendo_front_sel; // 設置年度（前）
    delete this.srch.setti_nendo_back_sel; // 設置年度（後）
    delete this.srch.sicyouson; // 市町村
    delete this.srch.azaban; // 字番
    delete this.srch.tenken_nendo; // 点検年度
    delete this.srch.tenken_dt_front_sel; // 点検年月日
    delete this.srch.tenken_dt_back_sel; // 点検年月日
    delete this.srch.chousa_dt_front_sel; // 調査年月日
    delete this.srch.chousa_dt_back_sel; // 調査年月日

    // 設置年度に不明を含めるチェックボックスをクリア
    this.srch.include_secchi_null = false;

    // マルチセレクトクリア
    this.multiselect_clear();

    // 検索結果、ソート条件クリア
    this.clearResult();

    this.alert('検索', 'リセットしました');
  }

  multiselect_clear() {
    // プルダウンチェックボックスの初期化
    this.srch.shisetsu_kbn_dat_model = [];
    this.srch.phase_dat_model = [];
    this.srch.chk_judge_dat_model = [];
    this.srch.measures_judge_dat_model = [];
    this.srch.rosen_dat_model = [];
    this.srch.gyousya_dat_model = [];
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

  // 選択建管/出張所でフィルタ
  // 呼ばれるタイミングは、オープン時/建管/出張所変更時
  filterRosens() {
    this.srch.rosen_dat_model = [];
    // 出張所選択
    if (this.syucchoujo.syucchoujo_cd != 0) {
      // 出張所フィルタ
      var syucchoujo_cd = this.syucchoujo.syucchoujo_cd;
      return this.rosen_dat.filter(function (value, index) {
        if (syucchoujo_cd == value.syucchoujo_cd) {
          return true;
        }
        return false;
      });
    } else {
      // 建管フィルタ
      var dogen_cd = this.dogen.dogen_cd;
      return this.rosen_dat.filter(function (value, index) {
        if (dogen_cd == value.dogen_cd) {
          return true;
        }
        return false;
      });
    }
  }

  // 選択建管/出張所でフィルタ
  // 呼ばれるタイミングは、オープン時/建管/出張所変更時
  filterGyousyas() {
    this.srch.gyousya_dat_model = [];
    // 出張所選択
    if (this.syucchoujo.syucchoujo_cd != 0) {
      // 出張所フィルタ
      var syucchoujo_cd = this.syucchoujo.syucchoujo_cd;
      return this.gyousya_dat.filter(function (value, index) {
        if (syucchoujo_cd == value.syucchoujo_cd) {
          return true;
        }
        return false;
      });
    } else {
      // 建管フィルタ
      var dogen_cd = this.dogen.dogen_cd;
      return this.gyousya_dat.filter(function (value, index) {
        if (dogen_cd == value.dogen_cd) {
          return true;
        }
        return false;
      });
    }
  }

  // 建管変更時処理
  // 本庁権限のみ
  chgDogen() {
    // 検索結果が存在しない
    if (this.data == null) {
      this.chgKanriNoSrch(1);
      // セッション上書き
      this.mngarea_update(this.$http, this.dogen.dogen_cd, this.syucchoujo.syucchoujo_cd).then(() => {
        // mngarea上書き
        this.mng_dogen_cd = this.session.mngarea.dogen_cd;
        this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
      });
    } else {
      // 検索結果が存在
      this.confirm('検索結果を初期化してよろしいですか？', 0).then(() => {
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
      }).catch(() => {
        // キャンセル時は戻す
        this.dogen = this.keep_dogen;
        this.syucchoujo = this.keep_syucchoujo;
      }).finally(() => {
        // 点検会社、路線の変更
        // 路線
        this.filterRosen = this.filterRosens();
        this.srch.rosen_all_cnt = this.filterRosen.length;
        // 業者フィルター
        this.filterGyousya = this.filterGyousyas();
        this.srch.gyousya_all_cnt = this.filterGyousya.length;
        // セッション上書き
        this.mngarea_update(this.$http, this.dogen.dogen_cd, this.syucchoujo.syucchoujo_cd).then(() => {
          // mngarea上書き
          this.mng_dogen_cd = this.session.mngarea.dogen_cd;
          this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
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
      this.mngarea_update(this.$http, this.dogen.dogen_cd, this.syucchoujo.syucchoujo_cd).then(() => {
        // mngarea上書き
        this.mng_dogen_cd = this.session.mngarea.dogen_cd;
        this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
      });
    } else {
      this.confirm('検索結果を初期化してよろしいですか？', 0).then(() => {
        // 結果クリア
        this.clearResult();
        // 条件表示変更
        this.jyoukenkensaku = false;
        this.naiyoukensaku = true;
        // 保存用
        this.keep_syucchoujo = this.syucchoujo;
      }).catch(() => {
        // キャンセル時は戻す
        this.syucchoujo = this.keep_syucchoujo;
      }).finally(() => {
        // 点検会社、路線の変更
        // 路線
        this.filterRosen = this.filterRosens();
        this.srch.rosen_all_cnt = this.filterRosen.length;
        // 業者フィルター
        this.filterGyousya = this.filterGyousyas();
        this.srch.gyousya_all_cnt = this.filterGyousya.length;
        // セッション上書き
        this.mngarea_update(this.$http, this.dogen.dogen_cd, this.syucchoujo.syucchoujo_cd).then(() => {
          // mngarea上書き
          this.mng_dogen_cd = this.session.mngarea.dogen_cd;
          this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
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
      if (this.ath_syozoku_cd == 1 || this.ath_syozoku_cd == 2 || this.ath_syozoku_cd == 10001) {
        this.syucchoujo = {};
        this.syucchoujo.syucchoujo_cd = 0;
      }
      // 保存用（キャンセル時に戻す時用）
      this.keep_dogen = this.dogen;
      this.keep_syucchoujo = this.syucchoujo;
      // 路線
      this.filterRosen = this.filterRosens();
      this.srch.rosen_all_cnt = this.filterRosen.length;
      // 点検会社、点検員
      this.set_patrolin_by_kenkan();
    } else {
      // 他出張所選択から全てを選択した時の対応
      if (!this.syucchoujo) {
        this.syucchoujo = {};
        this.syucchoujo.syucchoujo_cd = 0;
      }

      // 保存用（キャンセル時に戻す時用）
      this.keep_syucchoujo = this.syucchoujo;
      // 点検会社、路線の変更
      // 路線
      this.filterRosen = this.filterRosens();
      this.srch.rosen_all_cnt = this.filterRosen.length;
      // 業者フィルター
      this.filterGyousya = this.filterGyousyas();
      this.srch.gyousya_all_cnt = this.filterGyousya.length;
    }
  }

  /*** 施設検索 ***/
  srchShisetsu() {

    this.waitLoadOverlay = true;
    this.clearResult();

    // 検索条件の整理
    this.arrangeSearchCondition();

    this.$http({
      method: 'POST',
      url: 'api/index.php/RafTargetAjax/srchShisetsu',
      data: {
        dogen_cd: this.dogen.dogen_cd,
        syucchoujo_cd: (this.syucchoujo) ? this.syucchoujo.syucchoujo_cd : 0,
        srch: this.srch,
      }

    }).then((data) => {

      var json = data.data;

      // 件数をチェック
      var srch_cnt = json.cnt;
      var message;

      // 700件を超えている場合、表示・取得をクリアし再絞込みしてもらう
      if (srch_cnt > this.max_srch_cnt) {
        this.clearResult();
        delete data.data;
        message = '検索結果が' + String(this.max_srch_cnt) + '件を越えています<br>';
        message += '（' + String(srch_cnt) + '件）<br><br>';
        message += '検索条件を設定し、対象データを絞り込んでください<br>';
        return this.alert('検索', message);
      } else if (srch_cnt === 0) {
        message = '該当するデータがありません';
        this.jyoukenkensaku = false;
        this.naiyoukensaku = true;
        this.clearResult();
        delete data.data;
        return this.alert('検索', message);
      } else {
        this.data = json.shisetsu_info;
        this.bousetsusaku_parent = this.getBousetsusakuParentNum();
        this.srch_cnt = srch_cnt;
        this.changeNumItem(); // 数値項目を数値化

        // 登録済み件数
        this.rgst_num = this.getRgstNum();
        this.chk_num = this.rgst_num;

        if (this.rgst_num == this.srch_cnt || this.rgst_num + this.bousetsusaku_parent == this.srch_cnt) {
          this.all_checkbox = true;
        }

        this.range = this.getPageList(this.srch_cnt);
        this.current_page = 1; // 検索後は1ページ指定
        this.setPage(this.current_page);

        // 検索実行時、条件を指定して検索を閉じる
        this.jyoukenkensaku = true;
        this.naiyoukensaku = false;

        message = '';
        message = this.srch_cnt + '件の結果を取得しました';
        return this.alert('検索', message);
      }

    }).finally(() => {
      this.waitLoadOverlay = false;
    });

  }

  // 登録済み件数をFilterを行い求める
  getRgstNum() {
    // rgst_chkフィルター
    var rgst_dat = this.filterRgst();
    return rgst_dat.length;
  }

  // 防雪柵親の件数
  getBousetsusakuParentNum() {
    var bousetsukusaku_parent_dat = this.filterBousetsusakuParent();
    return bousetsukusaku_parent_dat.length;
  }

  // 選択建管/出張所でフィルタ
  // 呼ばれるタイミングは、オープン時/建管/出張所変更時
  filterRgst() {
    return this.data.filter(function (value) {
      if (value.target == 1) {
        return true;
      }
      return false;
    });
  }

  // 防雪柵の親をフィルター
  filterBousetsusakuParent() {
    return this.data.filter(function (value) {
      if (value.shisetsu_kbn == 4 && value.struct_idx == null) {
        return true;
      }
      return false;
    });
  }

  // チェック件数をFilterを行い求める
  getCheckNum() {
    // chk_numフィルター
    var chk_num_arr = this.filterCheck();
    return chk_num_arr.length;
  }

  // チェック数を全てチェックする
  filterCheck() {
    return this.data.filter(function (value) {
      if (value.tmp_chk == 1) {
        return true;
      }
      return false;
    });
  }

  // 検索結果の削除
  clearResult() {
    // 検索結果の削除
    this.data = null;
    this.max_page = 0;
    this.current_page = 1;
    this.rgst_num = 0;
    this.chk_num = 0;
    this.srch_cnt = 0;
    this.all_checkbox = false;

    // ソート条件クリア
    this.sort_order = "['seq_no']";
    this.reverse = false;
    this.sort_style = [];
    this.sort_style[this.sort_order] = {
      color: '#217dbb'
    };
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
    if (this.srch.include_secchi_null != null) {
      if (this.srch.include_secchi_null == false) {
        delete this.srch.include_secchi_null; // 不明対象チェックボックス
      }
    }
  }

  // 点検施設の更新
  // チェックだけなので、詳細はサーバーで処理することにした
  RgstChkShisetsu() {

    var rgst_flg = false;
    // 保存すべき情報があるかチェック
    for (var i = 0; i < this.data.length; i++) {
      var item = this.data[i];
      if (item.target != item.input_target) {
        // 点検対象と入力点検対象が異なっている場合は更新があったので登録が必要
        rgst_flg = true;
        break;
      }
    }

    if (rgst_flg == false) {
      // メッセージを出して終了
      this.alert('点検対象登録', '点検対象施設に変更がありませんでした');
      return;
    }

    this.$http({
      method: 'POST',
      url: 'api/index.php/RafTargetAjax/rgstRafTarget',
      data: {
        data: this.data,
      }
    }).then(() => {
      // メッセージを出して終了
      return this.alert('点検対象登録', '点検対象施設の登録が完了しました');
    }).then(() => {
      // メッセージ非表示
      this.windowUnlock();
      this.$window.location.reload(false);
    });
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
      color: '#217dbb'
    };
  }

  // 点検対象選択チェックボックス変更時処理
  chkboxTarget(data) {
    /***
     *チェックボックスがクリックされているので、値を逆転させる。
     * 初回ng-modelの変数がundefindとなったので無視
     * ※data.targetはDB値
     * 入力値はdata.input_target
     ***/
    if (data.input_target == 1) {
      data.input_target = 0;
      this.chk_num--;
      // チェックが外れた場合全チェックがTRUEならチェックを外す
      if (this.all_checkbox == true) {
        this.all_checkbox = false;
      }
    } else {
      data.input_target = 1;
      this.chk_num++;
      // フルで埋まった時はallにチェックを付ける
      if (this.chk_num == this.srch_cnt) {
        this.all_checkbox = true;
      }
    }
  }

  // チェックされたチェックボックスの状態に
  // 全てのデータを合わせる
  allCheckBoxClick() {
    for (var i = 0; i < this.data.length; i++) {
      if (this.all_checkbox == true) {
        // 防雪柵で、全子データが完了していないものはチェックできない
        if (this.data[i].shisetsu_kbn == 4 && this.data[i].comp_flg == 0) {
          continue;
        }
        // 無条件に全てTRUE
        this.data[i].input_target = 1;
        this.data[i].tmp_chk = true;
      } else {
        // チェックが外せないものは飛ばす
        /*
                if (this.data[i].target == 1 && ((this.data[i].exist_chk == 1 && this.data[i].phase != 5) || (this.data[i].shisetsu_kbn == 4 && this.data[i].comp_flg == 0) || (this.data[i].shisetsu_kbn == 4 && this.data[i].struct_idx == null))) {
                  continue;
                }
        */
        // 防雪柵の場合で、チェックを外す場合、他の子データは特に関係ない
        // したがって全て、点検データがあり、完了でないものだけがチェックを外せない
        if (this.data[i].target == 1 && (this.data[i].exist_chk == 1 && this.data[i].phase != 5)) {
          continue;
        }

        this.data[i].input_target = 0;
        this.data[i].tmp_chk = false;
      }
    }
    this.chk_num = this.getCheckNum();
  }

  changeNumItem() {
    for (var i = 0; i < this.data.length; i++) {
      for (var j = 0; j < this.numItem.length; j++) {
        if (this.data[i][this.numItem[j]]) {
          if (Number(this.data[i][this.numItem[j]])) {
            this.data[i][this.numItem[j]] = Number(this.data[i][this.numItem[j]]);
          }
        }
      }
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

let angModule = require('../app.js');
angModule.angApp.controller('RaftargetCtrl', ['$scope', '$http', '$location', '$uibModal', '$anchorScroll', '$routeParams', '$q', '$route', '$window', function ($scope, $http, $location, $uibModal, $anchorScroll, $routeParams, $q, $route, $window) {
  return new RaftargetCtrl($scope, $http, $location, $uibModal, $anchorScroll, $routeParams, $q, $route, $window);
}]);
