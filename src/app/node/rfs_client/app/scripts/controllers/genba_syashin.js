'use strict';

/**
 * @ngdoc function
 * @name rfsApp.controller:AboutCtrl
 * @description
 * # AboutCtrl
 * Controller of the rfsApp
 */

class GenbaSyashinCtrl {

  constructor($scope, $uibModalInstance, $http, select, dogen_syucchoujo) {
    this.$scope = $scope;
    this.$http = $http;
    this.init_select = select;
    this.init_dogen_syucchoujo = dogen_syucchoujo;

    $scope.photo_list = [];

    $scope.org_photo_list = JSON.parse(JSON.stringify($scope.photo_list));

    $http({
      method: 'POST',
      url: 'api/index.php/PictureAjax/get_gs_title_list',
      data: {
        dogen_cd: this.init_dogen_syucchoujo['dogen_cd'],
        syucchoujo_cd: this.init_dogen_syucchoujo['syucchoujo_cd']
      }
    }).then((msg) => {
      $scope.title_list = msg.data.data;
      $scope.album_title = msg.data.data[0].title_cd;
      if (this.init_select) {
        $scope.album_title = this.init_select;
      }
      $scope.title_change();
    });

    $scope.close = function () {
      $uibModalInstance.close($scope.album_title);
    };
    $scope.reset = () => {
      $scope.photo_list = JSON.parse(JSON.stringify($scope.org_photo_list));
    };
    $scope.title_change = () => {
      $http({
        method: 'POST',
        url: 'api/index.php/PictureAjax/get_gs_picture_list',
        data: {
          title_cd: $scope.album_title,
          dogen_cd: this.init_dogen_syucchoujo['dogen_cd'],
          syucchoujo_cd: this.init_dogen_syucchoujo['syucchoujo_cd']
        }
      }).then((msg) => {
        $scope.photo_list = msg.data.data;
        $scope.org_photo_list = JSON.parse(JSON.stringify($scope.photo_list));
      });
    };
  }
}

let angModule = require('../app.js');
angModule.angApp.controller('GenbaSyashinCtrl', ['$scope', '$uibModalInstance', '$http', 'select', 'dogen_syucchoujo', function ($scope, $uibModalInstance, $http, select, dogen_syucchoujo) {
  return new GenbaSyashinCtrl($scope, $uibModalInstance, $http, select, dogen_syucchoujo);
}]);
