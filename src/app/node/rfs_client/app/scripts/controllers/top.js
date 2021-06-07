'use strict';

/* globals OpenLayers:true */

/**
 * @ngdoc function
 * @name rfsApp.controller:TopCtrl
 * @description
 * # TopCtrl
 * Controller of the rfsApp
 */
class TopCtrl {
  constructor($scope, $filter, $http) {

  }
}

let angModule = require('../app.js');
angModule.angApp.controller('TopCtrl', ['$scope', '$filter', '$http', function ($scope, $filter, $http) {
  return new TopCtrl($scope, $filter, $http);
}]);
