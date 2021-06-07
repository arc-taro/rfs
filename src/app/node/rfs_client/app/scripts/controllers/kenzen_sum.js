'use strict';

/* globals OpenLayers:true */

var BaseCtrl = require("./base.js");

/**
 * @ngdoc function
 * @name rfsApp.controller:KenzenSumCtrl
 * @description
 * # KenzenSumCtrl
 * Controller of the rfsApp
 */
class KenzenSumCtrl extends BaseCtrl {
  constructor($scope, $filter, $http, $uibModal, $cookies, $compile, $location, $q, $document) {
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
    this.$cookies = $cookies;
    this.$compile = $compile;
    this.$location = $location;
    this.$q = $q;
    this.$document = $document;

    this.mst = {};
    this.data = {
      shisetsu_list: [],
      buzai_list: []
    };
    this.init();
  }


  async init() {
    await super.start(this.$http);

    let data = await this.$http({
      method: 'GET',
      url: 'api/index.php/KenzenSumAjax/init',
      timeout: this.canceler.promise,
    });


    // 建管_出張所データ抽出
    this.dogen_syucchoujo_dat = JSON.parse(data.data.mst.dogen_syucchoujo_dat[0].dogen_row);
    this.mst.shisetsu_kbn = data.data.mst.shisetsu_kbn;
    this.mst.buzai = data.data.mst.buzai;
    this.mst.shisetsu_judge = data.data.mst.shisetsu_judge;

    // 建管と出張所の関係をセット
    this.dogen_cd = this.session.ath.dogen_cd;
    this.sel_dogen_cd = this.session.mngarea.dogen_cd;
    this.syucchoujo_cd = this.session.ath.syucchoujo_cd;
    this.sel_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
    // 建管未選択
    if (this.sel_dogen_cd == 0) {
      // 未選択時は先頭にする
      let data_d = this.dogen_syucchoujo_dat.dogen_info[0];
      this.dogen = data_d;
      for (let l = 0; l < data_d.syucchoujo_row.syucchoujo_info.length; l++) {
        let data_s = data_d.syucchoujo_row.syucchoujo_info[l];
        if (data_s.syucchoujo_cd == this.sel_syucchoujo_cd) {
          this.syucchoujo = data_s;
        }
      }
    } else {
      // 選択時は該当の建管をセット
      for (let k = 0; k < this.dogen_syucchoujo_dat.dogen_info.length; k++) {
        let data_d = this.dogen_syucchoujo_dat.dogen_info[k];
        if (data_d.dogen_cd == this.sel_dogen_cd) {
          this.dogen = data_d;
          for (let l = 0; l < data_d.syucchoujo_row.syucchoujo_info.length; l++) {
            let data_s = data_d.syucchoujo_row.syucchoujo_info[l];
            // 維持管理で選択された出張所をセット
            if (data_s.syucchoujo_cd == this.sel_syucchoujo_cd) {
              this.syucchoujo = data_s;
            }
          }
        }
      }
    }

    this.$scope.$apply();
  }


  // 建管変更時処理
  // 本庁権限のみ
  chgDogen() {
    // マネージメントエリア更新
    this.mngarea_update(this.$http, this.dogen.dogen_cd, ((this.syucchoujo) ? this.syucchoujo.syucchoujo_cd : 0)).then(() => {
      // mngarea上書き
      this.mng_dogen_cd = this.session.mngarea.dogen_cd;
      this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
    });

  }

  // 出張所変更時処理
  chgSyucchoujo() {

    // マネージメントエリア更新
    this.mngarea_update(this.$http, this.dogen.dogen_cd, ((this.syucchoujo) ? this.syucchoujo.syucchoujo_cd : 0)).then(() => {
      // mngarea上書き
      this.mng_dogen_cd = this.session.mngarea.dogen_cd;
      this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
    });
  }

  async updateShisetsuKbn() {
    let data = await this.$http({
      method: 'POST',
      url: 'api/index.php/KenzenSumAjax/sum',
      timeout: this.canceler.promise,
      data: {
        shisetsu_kbn: this.data.shisetsu_kbn
      }
    });

    this.data.shisetsu_list = data.data.data.shisetsu_list;
    this.data.judge_list = data.data.data.judge_list;
    this.data.buzai_list = _.filter(this.mst.buzai, { shisetsu_kbn: this.data.shisetsu_kbn });
    this.data.sum_judge_list = _.groupBy(this.data.judge_list, (judge_data) => {
      return judge_data.syucchoujo_cd + "_" + judge_data.rosen_cd + "_" + judge_data.shisetsu_kbn
    });
    console.log(this.data);
    this.$scope.$apply();
  }

  /**
   * 
   * @param {*} syucchoujo_cd 
   * @param {*} rosen_cd 
   * @param {*} shisetsu_kbn 
   * @param {*} buzai_cd 
   * @param {*} judge 1～4:健全性 5:3と4を集計する 
   */
  sum_jugde(syucchoujo_cd, rosen_cd, shisetsu_kbn, buzai_cd, judge) {
    let data = this.data.sum_judge_list[syucchoujo_cd + "_" + rosen_cd + "_" + shisetsu_kbn];
    if (!data) {
      return 0;
    }
    if (judge < 5) {
      return _.sumBy(data, (judge_data) => {
        if (buzai_cd == judge_data.buzai_cd && judge == judge_data.judge) {
          return judge_data.count;
        }
        return 0;
      });
    }
    return _.sumBy(data, (judge_data) => {
      if (buzai_cd == judge_data.buzai_cd && (judge_data.judge == 3 || judge_data.judge == 4)) {
        return judge_data.count;
      }
      return 0;
    });
  }
}

let angModule = require('../app.js');
angModule.angApp.controller('KenzenSumCtrl', ['$scope', '$filter', '$http', '$uibModal', '$cookies', '$compile', '$location', '$q', '$document', function ($scope, $filter, $http, $uibModal, $cookies, $compile, $location, $q, $document) {
  return new KenzenSumCtrl($scope, $filter, $http, $uibModal, $cookies, $compile, $location, $q, $document);
}]);
