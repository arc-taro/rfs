'use strict';

/* globals OpenLayers:true */

var BaseCtrl = require("./base.js");
/**
 * @ngdoc function
 * @name rfsApp.controller:MainCtrl
 * @description
 * # MainCtrl
 * Controller of the rfsApp
 */
class SystopCtrl extends BaseCtrl {
  constructor($scope, $filter, $anchorScroll, $http, $uibModal, $location, $q) {
    super();

    this.$scope = $scope;
    this.$filter = $filter;
    this.$http = $http;
    this.$uibModal = $uibModal;
    this.$location = $location;
    this.$anchorScroll = $anchorScroll;
    this.$q = $q;

    // 検索項目表示用 trueが非表示
    // 検索後はここを閉じる。また、TOP画面から表示する時は検索が指定されている場合trueにする必要がある
    /*this.jyoukenkensaku = false;
    this.naiyoukensaku = true;*/

    this.shisetsukind = false;

    this.waitLoadOverlay = false; // 読込み中です

    // ログイン情報
    super.start(this.$http).then(() => {

      console.log('ARCテスト05311610');

      // session情報を保持
      this.ath_dogen_cd = this.session.ath.dogen_cd;
      this.ath_syucchoujo_cd = this.session.ath.syucchoujo_cd;
      this.ath_syozoku_cd = this.session.ath.syozoku_cd;
      this.ath_account = this.session.ath.account_cd;
      this.mng_dogen_cd = this.session.mngarea.dogen_cd;
      this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
      this.imm_url = this.session.url;
      this.waitLoadOverlay = true; // 読込み中です

      return this.$http({
        method: 'GET',
        url: 'api/index.php/SysTopAjax/initSysTop',
        params: {
          dogen_cd: this.mng_dogen_cd,
          syucchoujo_cd: this.mng_syucchoujo_cd,
          syozoku_cd: this.ath_syozoku_cd
        }
      });
    }).then((data) => {
      var json = data.data;
      // 建管_出張所データ抽出
      this.dogen_syucchoujo_dat = JSON.parse(json.dogen_syucchoujo[0].dogen_row);
      this.sum_dh = json.dh;
      this.sum_jd = json.jd;
      this.sum_ss = json.ss;
      this.sum_bs = json.bs;
      this.sum_yh = json.yh;
      this.sum_ka = json.ka;
      this.sum_kb = json.kb;
      this.sum_kc = json.kc;
      this.sum_kd = json.kd;
      this.sum_ki = json.ki;
      this.sum_jh = json.jh;
      this.sum_sd = json.sd;
      this.sum_dt = json.dt;
      this.sum_tt = json.tt;
      this.sum_ck = json.ck;
      this.sum_sk = json.sk;
      this.sum_bh = json.bh;
      this.sum_dy = json.dy;
      this.sum_dn = json.dn;
      this.sum_ts = json.ts;
      this.sum_rh = json.rh;
      this.sum_kk = json.kk;
      this.sum_tk = json.tk;
      this.sum_br = json.br;
      this.sum_ok = json.ok;
      this.sum_kf = json.kf;
      this.sum_hd = json.hd;
      this.sum_gk = json.gk;

      // 電気通信URL
      this.ele_url = json.ele_url;

      // 建管と出張所の関係をセット
      // 建管未選択
      if (this.mng_dogen_cd == 0) {
        // 未選択時は先頭にする
        var data_d = this.dogen_syucchoujo_dat.dogen_info[0];
        this.dogen = data_d;
        this.dogen.dogen_cd = this.dogen.dogen_cd;
        // 出張所初期化
        this.syucchoujo = {};
        this.syucchoujo.syucchoujo_cd = 0;

        for (var l = 0; l < data_d.syucchoujo_row.syucchoujo_info.length; l++) {
          var data_s = data_d.syucchoujo_row.syucchoujo_info[l];
          if (data_s.syucchoujo_cd == this.mng_syucchoujo_cd) {
            this.syucchoujo = data_s;
          }
        }
      } else {
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
      }
      // セッション上書き(未選択時選択に変更しているため)
      return this.mngarea_update(this.$http, this.dogen.dogen_cd, this.syucchoujo.syucchoujo_cd);
    }).then(() => {
      // mngarea上書き
      this.mng_dogen_cd = this.session.mngarea.dogen_cd;
      this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
    }).finally(() => {
      this.waitLoadOverlay = false; // 読込み中です
    });
  }

  // 建管変更時処理
  // 本庁権限のみ
  // 1.mngarea更新
  // 2.再集計
  chgDogen() {
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
        url: 'api/index.php/SysTopAjax/getSumAll',
        params: {
          dogen_cd: this.dogen.dogen_cd,
          syucchoujo_cd: this.syucchoujo.syucchoujo_cd
        }
      });
    }).then((data) => {
      var json = data.data;
      // 建管_出張所データ抽出
      this.sum_dh = json.dh;
      this.sum_jd = json.jd;
      this.sum_ss = json.ss;
      this.sum_bs = json.bs;
      this.sum_yh = json.yh;
      this.sum_ka = json.ka;
      this.sum_kb = json.kb;
      this.sum_kc = json.kc;
      this.sum_kd = json.kd;
      this.sum_ki = json.ki;
      this.sum_jh = json.jh;
      this.sum_sd = json.sd;
      this.sum_dt = json.dt;
      this.sum_tt = json.tt;
      this.sum_ck = json.ck;
      this.sum_sk = json.sk;
      this.sum_bh = json.bh;
      this.sum_dy = json.dy;
      this.sum_dn = json.dn;
      this.sum_ts = json.ts;
      this.sum_rh = json.rh;
      this.sum_kk = json.kk;
      this.sum_tk = json.tk;
      this.waitLoadOverlay = false; // 読込み中です
    });
  }

  // 出張所変更時処理
  // 1.mngarea更新
  // 2.再集計
  chgSyucchoujo() {
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
        url: 'api/index.php/SysTopAjax/getSumAll',
        params: {
          dogen_cd: this.dogen.dogen_cd,
          syucchoujo_cd: this.syucchoujo.syucchoujo_cd
        }
      });
    }).then((data) => {
      var json = data.data;
      // 建管_出張所データ抽出
      this.sum_dh = json.dh;
      this.sum_jd = json.jd;
      this.sum_ss = json.ss;
      this.sum_bs = json.bs;
      this.sum_yh = json.yh;
      this.sum_ka = json.ka;
      this.sum_kb = json.kb;
      this.sum_kc = json.kc;
      this.sum_kd = json.kd;
      this.sum_ki = json.ki;
      this.sum_jh = json.jh;
      this.sum_sd = json.sd;
      this.sum_dt = json.dt;
      this.sum_tt = json.tt;
      this.sum_ck = json.ck;
      this.sum_sk = json.sk;
      this.sum_bh = json.bh;
      this.sum_dy = json.dy;
      this.sum_dn = json.dn;
      this.sum_ts = json.ts;
      this.sum_rh = json.rh;
      this.sum_kk = json.kk;
      this.sum_tk = json.tk;
      this.waitLoadOverlay = false; // 読込み中です
    });
  }

  // 集計処理
  btnDisp() {

    this.waitLoadOverlay = true; // 読込み中です

    this.$http({
      method: 'GET',
      url: 'api/index.php/SysTopAjax/getSumAll',
      params: {
        dogen_cd: this.dogen.dogen_cd,
        syucchoujo_cd: this.syucchoujo.syucchoujo_cd
      }
    }).then((data) => {
      var json = data.data;
      // 建管_出張所データ抽出
      this.sum_dh = json.dh;
      this.sum_jd = json.jd;
      this.sum_ss = json.ss;
      this.sum_bs = json.bs;
      this.sum_yh = json.yh;
      this.sum_ka = json.ka;
      this.sum_kb = json.kb;
      this.sum_kc = json.kc;
      this.sum_kd = json.kd;
      this.sum_ki = json.ki;
      this.sum_jh = json.jh;
      this.sum_sd = json.sd;
      this.sum_dt = json.dt;
      this.sum_tt = json.tt;
      this.sum_ck = json.ck;
      this.sum_sk = json.sk;
      this.sum_bh = json.bh;
      this.sum_dy = json.dy;
      this.sum_dn = json.dn;
      this.sum_ts = json.ts;
      this.sum_rh = json.rh;
      this.sum_kk = json.kk;
      this.sum_tk = json.tk;
      this.waitLoadOverlay = false; // 読込み中です

    });
  }

  anchorScroll(anchor) {
    this.$location.hash(anchor);
    this.$anchorScroll();

    this.$location.url(this.$location.path());
  }

  // 基本情報編集
  editBaseInfo() {

    /*
        // 29年度リリース対応
        // 基本情報が整うまで閲覧モードのみ
        this.alert('モード変更', '現在最新の施設情報をシステムに登録中のため、施設の新規追加はできません。');
        return;
    */

    // 新規時は基本情報は出張所必須
    if (!this.syucchoujo) {
      this.alert('基本情報登録', '出張所を選択してください');
      return;
    } else if (this.syucchoujo.syucchoujo_cd == 0) {
      this.alert('基本情報登録', '出張所を選択してください');
      return;
    }
    this.$location.path("/shisetsu_edit/0/3/0");
    this.$window.location.reload();
  }

}

let angModule = require('../app.js');
angModule.angApp.controller('SystopCtrl', ['$scope', '$filter', '$anchorScroll', '$http', '$uibModal', '$location', '$q', function ($scope, $filter, $anchorScroll, $http, $uibModal, $location, $q) {
  return new SystopCtrl($scope, $filter, $anchorScroll, $http, $uibModal, $location, $q);
}]);
