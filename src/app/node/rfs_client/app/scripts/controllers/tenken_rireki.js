'use strict';

/**
 * @ngdoc function
 * @name rfsApp.controller:TenkenRirekiCtrl
 * @description
 * # TenkenRirekiCtrl
 * Controller of the rfsApp
 */
class TenkenRirekiCtrl {

  constructor($scope, $http, $location, $uibModalInstance, data) {

    this.$location = $location;
    this.$http = $http;
    this.$uibModalInstance = $uibModalInstance;

    this.sno = data.sno;
    //        this.shisetsu_cd = data.shisetsu_cd;
    //        this.shisetsu_ver = data.shisetsu_ver;
    this.struct_idx = data.struct_idx;

    this.judges = ['－', '－', '－', '－', '－', '－'];

    this.hoshi = [
            "未", " Ⅰ ", " Ⅱ ", " Ⅲ ", " Ⅳ "
        ];

    // 過去の点検票の取得
    this.$http({
      method: 'GET',
      url: 'api/index.php/TenkenRirekiAjax/get_rireki_data',
      params: {
        sno: this.sno,
        //                shisetsu_cd: this.shisetsu_cd,
        //                shisetsu_ver: this.shisetsu_ver,
        struct_idx: this.struct_idx
      }

    }).then((data) => {
      this.past_data = JSON.parse(data.data[0].past_data_row).past_data;
      console.log(this.data);

      for (var i = 0; Object.keys(this.past_data).length > i; i++) {
        this.past_data[i].rireki_num = Object.keys(this.past_data[i].rireki_data).length;

      }
    });

  }

  close() {
    this.$uibModalInstance.close();
  }

  // 西暦を元号に変換する
  convertADtoYear(ad) {
    var year = '-';
    var month = '';

    if ((ad == null) || (ad == '')) {
      return year;
    }
    year = Number(ad.substr(0, 4));
    year -= 1988;

    // 3/31までは前年度
    month = Number(ad.substr(5, 2));
    if (3 >= Number(month)) {
      year--;
    }

    return 'H' + year + '年';
  }

  getJudge(rireki) {
    var _judge = rireki.check_shisetsu_judge;

    if (rireki.phase > 3) {
      _judge = rireki.measures_shisetsu_judge;
    }

    switch (Number(_judge)) {
    case 1:
      _judge = 'Ⅰ';
      break;
    case 2:
      _judge = 'Ⅱ';
      break;
    case 3:
      _judge = 'Ⅲ';
      break;
    case 4:
      _judge = 'Ⅳ';
      break;
    default:
      break;
    }

    return _judge;
  }

  /*    getBaseInfoByChkMngNo(_shisetsu_cd, _shisetsu_cd_idx, _shisetsu_ver, _chk_mng_no) {
          var _baseinfo;

          this.$http({
              method: 'GET',
              url: 'api/index.php/CheckListSchAjax/get_baseinfo_by_chkmngno',
              params: {
                  chk_mng_no: _chk_mng_no,
                  shisetsu_cd: _shisetsu_cd,
                  shisetsu_cd_idx: _shisetsu_cd_idx,
                  shisetsu_ver: _shisetsu_ver
              }
          }).then((data)=>{
              _baseinfo = data.data[0];
              console.log(_baseinfo);

          });

          return _baseinfo;
      }*/

  /*    getTenkenKasyo(_chk_mng_no, _rireki_no, _shisetsu_kbn) {
          var _tenkenkasyo;

          this.$http({
              method: 'GET',
              url: 'api/index.php/CheckListSchAjax/get_chkdata',
              params: {
                  chk_mng_no: _chk_mng_no,
                  rireki_no: _rireki_no,
                  shisetsu_kbn: _shisetsu_kbn
              }
          }).then((data)=>{
              _tenkenkasyo = JSON.parse(data.data[0].buzai_row);
              console.log(_tenkenkasyo);
          });

          return _tenkenkasyo;
      }*/

  /**
   * ページ遷移用メソッド
   * @param link 遷移先のアドレス
   */
  location(link) {
    this.close();
    this.$location.path(link);
  }
}

//['支柱本体','支柱継手部（ジョイントA）','支柱継手部（ジョイントB）','路面境界面']
let angModule = require('../app.js');
angModule.angApp.controller('TenkenRirekiCtrl', ['$scope', '$http', '$location', '$uibModalInstance', 'data', function ($scope, $http, $location, $uibModalInstance, data) {
  return new TenkenRirekiCtrl($scope, $http, $location, $uibModalInstance, data);
}]);
