"use strict";

var BaseCtrl = require("./base.js");

class CsvListCtrl extends BaseCtrl {
  constructor($scope, $http, $location, $anchorScroll, $q, $route, $window) {
    super({
      $location: $location,
      $scope: $scope,
      $http: $http,
      $q: $q,
    });

    // Angularの初期化
    this.$scope = $scope;
    this.$http = $http;
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
        url: 'api/index.php/CsvListController/init',
        params: {}
      });
    }).then((data) => {
      var json = data.data;
      this.csv_list = json.csv_list;
      for (let i = 0; i < this.csv_list.length; i++) {
        let shisetsu_arr = JSON.parse(this.csv_list[i].shisetsu_kbn_json);
        this.csv_list[i].shisetsu_nm = "";
        console.log(shisetsu_arr);
        for (let j = 0; j < shisetsu_arr.length; j++) {
          if (j > 0) {
            this.csv_list[i].shisetsu_nm = this.csv_list[i].shisetsu_nm + ",";
          }
          this.csv_list[i].shisetsu_nm = this.csv_list[i].shisetsu_nm + shisetsu_arr[j].shisetsu_kbn_nm;
        }
      }
      console.log(this.csv_list);


    }).finally(() => {
      this.waitLoadOverlay = false; // 読込み中です
    });
  }

  // 初期変数設定
  initVariable() {
    this.data = null;
  }

}

let angModule = require('../app.js');
angModule.angApp.controller('CsvListCtrl', ['$scope', '$http', '$location', '$anchorScroll', '$q', '$route', '$window', function ($scope, $http, $location, $anchorScroll, $q, $route, $window) {
  return new CsvListCtrl($scope, $http, $location, $anchorScroll, $q, $route, $window);
}]);
