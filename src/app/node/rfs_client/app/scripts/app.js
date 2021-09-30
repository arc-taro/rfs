'use strict';
/*
window.onbeforeunload = function (event) {
    event = event || window.event;
    event.returnValue = 'ページから移動しますか？';
  }
*/
/**
 * @ngdoc overview
 * @name rfsApp
 * @description
 * # rfsApp
 *
 * Main module of the application.
 */

module.exports.angApp = angular
  .module('rfsApp', [
    'ngAnimate',
    'ngCookies',
    'ngResource',
    'ngRoute',
    'ngSanitize',
    'ngTouch',
    'ui.bootstrap',
    'flow',
    'angularjs-dropdown-multiselect',
    'autocomplete',
    'autocompletecstm',
    'ngDragDrop'
  ])
  .config(function ($routeProvider) {
    $routeProvider
      .when('/', {
        templateUrl: 'views/sys_top.html',
        controller: 'SystopCtrl',
        controllerAs: 'systop'
      })
      .when('/tenken/:sno/:struct_idx/:chk_mng_no', {
        templateUrl: 'views/tenken.html',
        controller: 'TenkenCtrl',
        controllerAs: 'tenken'
        //            })
        /*.when('/about/:shisetsu_cd/:shisetsu_cd_idx/:shisetsu_ver/:shisetsu_kbn/:chk_mng_no/:rireki_no', {
          templateUrl: 'views/about.html',
          controller: 'AboutCtrl',
          controllerAs: 'about'*/
      })
      .when('/main', {
        templateUrl: 'views/main.html',
        controller: 'MainCtrl',
        controllerAs: 'main'
      })
      .when('/kenzen_sum', {
        templateUrl: 'views/kenzen_sum.html',
        controller: 'KenzenSumCtrl',
        controllerAs: 'kenzen_sum'
      })
      .when('/shisetsu_edit/:sno/:from/:fromid', {
        templateUrl: 'views/shisetsu_edit.html',
        controller: 'ShisetsuEditCtrl',
        controllerAs: 'shisetsu'
      })
      .when('/tenken_rireki/:sno/:chk_mng_no', {
        templateUrl: 'views/tenken_rireki.html',
        controller: 'TenkenRirekiCtrl',
        controllerAs: 'tenken_rireki'
      })
      .when('/raf_top', {
        templateUrl: 'views/raf_top.html',
        controller: 'RaftopCtrl',
        controllerAs: 'raftop'
      })
      .when('/fam_main/:srch_kbn/:shisetsu_kbn/:secchi_idx/:kyouyou_kbn', {
        templateUrl: 'views/fam_main.html',
        controller: 'FamMainCtrl',
        controllerAs: 'fammain'
      })
      .when('/fam_edit/:sno', {
        templateUrl: 'views/fam_edit.html',
        controller: 'FameditCtrl',
        controllerAs: 'famedit'
      })
      .when('/raf_target', {
        templateUrl: 'views/raf_target.html',
        controller: 'RaftargetCtrl',
        controllerAs: 'raftarget'
      })
      .when('/gdh_main/:sno/:gdh_idx', {
        templateUrl: 'views/gdh_main.html',
        controller: 'GdhMainCtrl',
        controllerAs: 'gdhmain'
      })
      .when('/snow_removal', {
        templateUrl: 'views/snow_removal.html',
        controller: 'SrCsvCtrl',
        controllerAs: 'srcsv'
      })
      .when('/tenken_keikaku', {
        templateUrl: 'views/tenken_keikaku.html',
        controller: 'TenkenKeikakuCtrl',
        controllerAs: 'tenkenkeikaku'
      })
      .otherwise({
        redirectTo: '/'
      });
  })
  .config(['flowFactoryProvider', function (flowFactoryProvider) {
    flowFactoryProvider.factory = fustyFlowFactory;
    flowFactoryProvider.defaults = {
      simultaneousUploads: 4,
      chunkSize: 1024 * 300,
      throttleProgressCallbacks: 0
    };
  }]).run(function () {
    $('#loader-bg').delay(300).fadeOut(500);
    $('#loader').delay(100).fadeOut(300);
  });
