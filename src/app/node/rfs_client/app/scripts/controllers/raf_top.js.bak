'use strict';

var BaseCtrl = require("./base.js");

class RaftopCtrl extends BaseCtrl {
  constructor($scope, $filter, $anchorScroll, $http, $uibModal, $location, $routeParams, $q) {
    super({
      $location: $location,
      $scope: $scope,
      $http: $http,
      $q: $q,
    });

    this.$scope = $scope;
    this.$filter = $filter;
    this.$http = $http;
    this.$uibModal = $uibModal;
    this.$location = $location;
    this.$anchorScroll = $anchorScroll;
    this.$q = $q;

    this.shisetsukind = false;

    this.waitLoadOverlay = false; // 読込み中です
    this.disp_sum = false; // 集計表示の有無

    // // 設置年度の値
    var date = new Date(); // 現在日付を取得
    var year = date.getFullYear();
    var month = date.getMonth() + 1;
    // 月が3以下なら、1減算
    if (month <= 3) {
      year--;
    }
    this.target_nendo = [];
    // // 平成
    // for (var i = year; i >= 2016; i--) {
    //   this.target_nendo.push({
    //     "year": i,
    //     "gengou": "H" + (i - 1988) + "年"
    //   });
    // }

    // 初期値FROM、TOともに現在をデフォルトに
    this.target_nendo_from = year;
    this.target_nendo_to = year;

    // ログイン情報
    super.start(this.$http).then(() => {

      // session情報を保持
      this.ath_dogen_cd = this.session.ath.dogen_cd;
      this.ath_syucchoujo_cd = this.session.ath.syucchoujo_cd;
      this.ath_syozoku_cd = this.session.ath.syozoku_cd;
      this.ath_account = this.session.ath.account_cd;
      this.mng_dogen_cd = this.session.mngarea.dogen_cd;
      this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
      this.imm_url = this.session.url;

      this.waitLoadOverlay = true; // 読込み中です

      if (this.session.srch_fuzokubutsu != null) {
        let srch_fuzokubutsu = this.session.srch_fuzokubutsu;
        if (srch_fuzokubutsu.srch_done.srch.target_nendo_from) {
          this.target_nendo_from = srch_fuzokubutsu.srch_done.srch.target_nendo_from;
          // this.target_nendo_from = Number(srch_fuzokubutsu.srch_done.srch.target_nendo_from);
        }
        if (srch_fuzokubutsu.srch_done.srch.target_nendo_to) {
          this.target_nendo_to = srch_fuzokubutsu.srch_done.srch.target_nendo_to;
          // this.target_nendo_to = Number(srch_fuzokubutsu.srch_done.srch.target_nendo_to);
        }
      }

      return this.$http({
        method: 'GET',
        url: 'api/index.php/RafTopAjax/initRafTop',
        params: {
          dogen_cd: this.mng_dogen_cd,
          syucchoujo_cd: this.mng_syucchoujo_cd,
          syozoku_cd: this.ath_syozoku_cd,
          target_nendo_from: this.target_nendo_from,
          target_nendo_to: this.target_nendo_to,
        }
      });
    }).then((data) => {

      var json = data.data;
      // 年度リスト
      this.target_nendo = json.wareki_list;

      // 建管_出張所データ抽出
      this.dogen_syucchoujo_dat = JSON.parse(json.dogen_syucchoujo[0].dogen_row);
      this.sum_dh = json.dh;
      this.sum_dh_sochi = json.dh_sochi;
      this.sum_jd = json.jd;
      this.sum_jd_sochi = json.jd_sochi;
      this.sum_ss = json.ss;
      this.sum_ss_sochi = json.ss_sochi;
      this.sum_bs = json.bs;
      this.sum_bs_sochi = json.bs_sochi;
      this.sum_yh = json.yh;
      this.sum_yh_sochi = json.yh_sochi;
      this.sum_kk = json.kk;
      this.sum_kk_sochi = json.kk_sochi;
      this.sum_tk = json.tk;
      this.sum_tk_sochi = json.tk_sochi;

      // 検索時の年度を保持
      // this.nendo_from = Number(json.nendo.nendo_from);
      // this.nendo_to = Number(json.nendo.nendo_to);
      this.nendo_from = json.nendo.nendo_from;
      this.nendo_to = json.nendo.nendo_to;

      // 検索した年度でプルダウンを表示
      // 条件については、sessionの年度の有無を考慮している
      this.target_nendo_from = this.nendo_from;
      this.target_nendo_to = this.nendo_to;
      // H28をFromに、今をToに
      //this.nendo_from = this.target_nendo_from;
      //this.nendo_to = this.target_nendo_to;

      // 施設区分
      this.shisetsu_kbns = json.shisetsu_kbns;
      // 集計用
      this.sum_select = "";

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
    }).finally(() => {
      this.waitLoadOverlay = false; // 読込み中です
    });
  }

  // 建管変更時処理
  // 本庁権限のみ
  chgDogen() {
    this.disp_sum = false; // 集計非表示
    // 出張所を全てにする
    this.syucchoujo.syucchoujo_cd = 0;
    // セッション上書き
    this.mngarea_update(this.$http, this.dogen.dogen_cd, this.syucchoujo.syucchoujo_cd).then(() => {
      // mngarea上書き
      this.mng_dogen_cd = this.session.mngarea.dogen_cd;
      this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
      this.waitLoadOverlay = true; // 読込み中です
      // 再集計
      return this.$http({
        method: 'GET',
        url: 'api/index.php/RafTopAjax/getSumAll',
        params: {
          dogen_cd: this.dogen.dogen_cd,
          syucchoujo_cd: this.syucchoujo.syucchoujo_cd,
          syozoku_cd: this.ath_syozoku_cd,
          target_nendo_from: this.target_nendo_from,
          target_nendo_to: this.target_nendo_to
        }
      });
    }).then((data) => {
      var json = data.data;
      this.sum_dh = json.dh;
      this.sum_dh_sochi = json.dh_sochi;
      this.sum_jd = json.jd;
      this.sum_jd_sochi = json.jd_sochi;
      this.sum_ss = json.ss;
      this.sum_ss_sochi = json.ss_sochi;
      this.sum_bs = json.bs;
      this.sum_bs_sochi = json.bs_sochi;
      this.sum_yh = json.yh;
      this.sum_yh_sochi = json.yh_sochi;
      this.sum_kk = json.kk;
      this.sum_kk_sochi = json.kk_sochi;
      this.sum_tk = json.tk;
      this.sum_tk_sochi = json.tk_sochi;
      this.waitLoadOverlay = false; // 読込み中です
    });
  }

  chgSyucchoujo() {
    this.disp_sum = false; // 集計非表示
    // 他出張所選択から全てを選択した時の対応
    // 全て選択時はundefinedになる
    if (!this.syucchoujo) {
      this.syucchoujo = {};
      this.syucchoujo.syucchoujo_cd = 0;
    }
    // セッション上書き
    this.mngarea_update(this.$http, this.dogen.dogen_cd, this.syucchoujo.syucchoujo_cd).then(() => {
      // mngarea上書き
      this.mng_dogen_cd = this.session.mngarea.dogen_cd;
      this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
      this.waitLoadOverlay = true; // 読込み中です
      // 再集計
      return this.$http({
        method: 'GET',
        url: 'api/index.php/RafTopAjax/getSumAll',
        params: {
          dogen_cd: this.dogen.dogen_cd,
          syucchoujo_cd: this.syucchoujo.syucchoujo_cd,
          syozoku_cd: this.ath_syozoku_cd,
          target_nendo_from: this.target_nendo_from,
          target_nendo_to: this.target_nendo_to
        }
      });
    }).then((data) => {
      var json = data.data;
      this.sum_dh = json.dh;
      this.sum_dh_sochi = json.dh_sochi;
      this.sum_jd = json.jd;
      this.sum_jd_sochi = json.jd_sochi;
      this.sum_ss = json.ss;
      this.sum_ss_sochi = json.ss_sochi;
      this.sum_bs = json.bs;
      this.sum_bs_sochi = json.bs_sochi;
      this.sum_yh = json.yh;
      this.sum_yh_sochi = json.yh_sochi;
      this.sum_kk = json.kk;
      this.sum_kk_sochi = json.kk_sochi;
      this.sum_tk = json.tk;
      this.sum_tk_sochi = json.tk_sochi;
      this.waitLoadOverlay = false; // 読込み中です
    });
  }

  // 表示ボタンクリック時の処理
  btnDisp() {
    this.waitLoadOverlay = true; // 読込み中です
    this.$http({
      method: 'GET',
      url: 'api/index.php/RafTopAjax/getSumAll',
      params: {
        dogen_cd: this.dogen.dogen_cd,
        syucchoujo_cd: this.syucchoujo.syucchoujo_cd,
        syozoku_cd: this.ath_syozoku_cd,
        target_nendo_from: this.target_nendo_from,
        target_nendo_to: this.target_nendo_to
      }
    }).then((data) => {
      var json = data.data;
      this.sum_dh = json.dh;
      this.sum_dh_sochi = json.dh_sochi;
      this.sum_jd = json.jd;
      this.sum_jd_sochi = json.jd_sochi;
      this.sum_ss = json.ss;
      this.sum_ss_sochi = json.ss_sochi;
      this.sum_bs = json.bs;
      this.sum_bs_sochi = json.bs_sochi;
      this.sum_yh = json.yh;
      this.sum_yh_sochi = json.yh_sochi;
      this.sum_kk = json.kk;
      this.sum_kk_sochi = json.kk_sochi;
      this.sum_tk = json.tk;
      this.sum_tk_sochi = json.tk_sochi;

      // 検索時の年度を保持
      this.nendo_from = this.target_nendo_from;
      this.nendo_to = this.target_nendo_to;

      this.waitLoadOverlay = false; // 読込み中です

      // 集計表示
      this.disp_sum = true;

    });
  }

  /***
   * 検索画面リンク前処理
   *
   * 検索画面に飛ぶ前に、sessionに条件をセットする
   *
   * 引数 shisetsu_kbn 施設区分
   *     val ・該当フェーズ(kbnが1の場合)
               999
   *         ・措置のインデックス(kbnが2の場合)
   *           1:措置不要、2:措置、3:措置完了、4:全て
   *     kbn 0:施設選択時、1:フェーズ、2:措置
   ***/
  linkMain(shisetsu_kbn, val, kbn) {

    var raf_params = {
      'kbn': kbn,
      'nendo_from': this.nendo_from,
      'nendo_to': this.nendo_to,
      'shisetsu_kbn': shisetsu_kbn,
      'val': val,
      'dogen_cd': this.dogen.dogen_cd,
      'syucchoujo_cd': this.syucchoujo.syucchoujo_cd
    };

    // sessionに条件を書き込み
    this.$http({
      method: 'GET',
      url: 'api/index.php/InquirySession/updSrchFuzokubutsuFromTop',
      params: {
        raf_params: raf_params
      }
    }).then((data) => {
      // セッション上書き
      //console.debug(data.data); // 使わないので消してね
      this.location('/main');
    });
  }
}


let angModule = require('../app.js');
angModule.angApp.controller('RaftopCtrl', ['$scope', '$filter', '$anchorScroll', '$http', '$uibModal', '$location', '$routeParams', '$q', function ($scope, $filter, $anchorScroll, $http, $uibModal, $location, $routeParams, $q) {
  return new RaftopCtrl($scope, $filter, $anchorScroll, $http, $uibModal, $location, $routeParams, $q);
}]);
