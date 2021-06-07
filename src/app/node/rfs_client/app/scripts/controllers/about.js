'use strict';

/**
 * @ngdoc function
 * @name rfsApp.controller:AboutCtrl
 * @description
 * # AboutCtrl
 * Controller of the rfsApp
 */

var BaseCtrl = require("./base.js");
/**
 * この画面では、検索された施設の
 * 全ての健全性が確認できる画面を表示します
 * 検索されているため、すでに施設を特定するIDと
 * 最新の点検管理番号、履歴番号を渡してもらうことで
 * ここでの機能は指定された情報を表示することに特化します
 */
class AboutCtrl {
  constructor($scope, $uibModalInstance, $http, data) {

    this.$scope = $scope;
    this.$http = $http;

    this.shisetsu_cd = data.shisetsu_cd;
    this.shisetsu_cd_idx = data.shisetsu_cd_idx;
    this.shisetsu_ver = data.shisetsu_ver;
    this.shisetsu_kbn = data.shisetsu_kbn;
    this.chk_mng_no = data.chk_mng_no;
    this.rireki_no = data.rireki_no;

    $http({
      method: 'GET',
      url: 'api/index.php/SchDetailJudgeAjax/get_srch_detail_judge',
      params: {
        shisetsu_cd: this.shisetsu_cd,
        shisetsu_cd_idx: this.shisetsu_cd_idx,
        shisetsu_ver: this.shisetsu_ver,
        shisetsu_kbn: this.shisetsu_kbn,
        chk_mng_no: this.chk_mng_no,
        rireki_no: this.rireki_no
      }
    }).then((data) => {
      this.judgedatas = data.data;
      console.log(this.judgedatas);

      //console.log(data);
    });
    $scope.close = function () {
      $uibModalInstance.close();
    };
  }
}

let angModule = require('../app.js');
angModule.angApp.controller('AboutCtrl', ['$scope', '$uibModalInstance', '$http', 'data', function ($scope, $uibModalInstance, $http, data) {
  return new AboutCtrl($scope, $uibModalInstance, $http, data);
}]);
