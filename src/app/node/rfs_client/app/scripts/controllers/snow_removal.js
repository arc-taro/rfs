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
class SrCsvCtrl extends BaseCtrl {
  constructor($scope, $filter, $http, $location, $q) {
    super();

    this.$scope = $scope;
    this.$http = $http;
    this.$location = $location;
    this.$q = $q;

    this.waitCreateCsvOverlay = false; // 作成中です

    // ログイン情報
    super.start(this.$http).then(() => {

      // session情報を保持
      this.ath_dogen_cd = this.session.ath.dogen_cd;
      this.ath_syucchoujo_cd = this.session.ath.syucchoujo_cd;
      this.ath_syozoku_cd = this.session.ath.syozoku_cd;

      return this.$http({
        method: 'GET',
        url: 'api/index.php/SnowRemovalController/init',
        params: {}
      });
    }).then((data) => {
      var json = data.data;
      // 建管_出張所データ抽出
      this.dogen_syucchoujo_dat = JSON.parse(json.dogen_syucchoujo[0].dogen_row);
    }).finally(() => {

      // チェックボックスデフォルトチェック
      this.chk_bs = true;
      this.chk_rh = true;

      this.dogen = {};
      this.syucchoujo = {};
      // 出張所の場合
      if (this.ath_syozoku_cd == 3 || this.ath_syozoku_cd == 4) {
        // 1個しかないはず
        var data_d = this.dogen_syucchoujo_dat.dogen_info[0];
        this.dogen = data_d;
        this.dogen.dogen_cd = this.ath_dogen_cd;
        // 出張所初期化
        this.syucchoujo = {};
        for (var l = 0; l < data_d.syucchoujo_row.syucchoujo_info.length; l++) {
          var data_s = data_d.syucchoujo_row.syucchoujo_info[l];
          if (data_s.syucchoujo_cd == this.ath_syucchoujo_cd) {
            this.syucchoujo = data_s;
          }
        }
      } else if (this.ath_syozoku_cd == 2) {
        // 1個しかないはず
        var data_d = this.dogen_syucchoujo_dat.dogen_info[0];
        this.dogen = data_d;
        this.dogen.dogen_cd = this.ath_dogen_cd;
        // 出張所初期化
        this.syucchoujo = {};
        this.syucchoujo.syucchoujo_cd = 0;
      }
    });
  }

  // 建管変更時処理
  // 本庁権限のみ
  // 1.mngarea更新
  // 2.再集計
  chgDogen() {
    this.syucchoujo.syucchoujo_cd = 0;
  }

  // 出張所変更時処理
  // 1.mngarea更新
  // 2.再集計
  chgSyucchoujo() {
    if (!this.syucchoujo) {
      this.syucchoujo = {};
      this.syucchoujo.syucchoujo_cd = 0;
    }
  }
}

let angModule = require('../app.js');
angModule.angApp.controller('SrCsvCtrl', ['$scope', '$filter', '$http', '$location', '$q', function ($scope, $filter, $http, $location, $q) {
  return new SrCsvCtrl($scope, $filter, $http, $location, $q);
}]);
