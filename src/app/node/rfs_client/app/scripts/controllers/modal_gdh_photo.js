'use strict';

/**
 * @ngdoc function
 * @name controller:ModalGdhPhotoCtrl
 * @description
 */

class ModalGdhPhotoCtrl {
  constructor($scope, $uibModalInstance, $http, data) {
    this.$scope = $scope;
    this.$uibModalInstance = $uibModalInstance;
    this.$http = $http;

    // 呼び出し元からのデータのコピー
    this.data = angular.copy(data);

    // init
    this.start();
  }

  /***
   * オープン時に呼ばれる
   *
   *    最新の装置点検結果を取得する
   *
   ***/
  async start() {
    this.result = this.data;
  }


  close() {
    this.$uibModalInstance.close(null);
  }
}

let angModule = require('../app.js');

angModule.angApp.controller('ModalGdhPhotoCtrl', ['$scope', '$uibModalInstance', '$http', 'data', function ($scope, $uibModalInstance, $http, data) {
  return new ModalGdhPhotoCtrl($scope, $uibModalInstance, $http, data);
}]);
