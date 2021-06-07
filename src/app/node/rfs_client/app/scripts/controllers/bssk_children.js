'use strict';

/**
 * @ngdoc function
 * @name rfsApp.controller:BsskChildrenCtrl
 * @description
 * # BsskChildrenCtrl
 * Controller of the rfsApp
 */

/**
 * 防雪柵の各支柱インデックスの情報を表示
 */
class BsskChildrenCtrl {
  constructor($scope, $uibModalInstance, $uibModal, $http, data, $location, $rootScope) {

    this.$scope = $scope;
    this.$http = $http;
    this.$uibModalInstance = $uibModalInstance;
    this.$uibModal = $uibModal;
    this.$location = $location;
    this.$rootScope = $rootScope;

    this.srch = {
      'sno': '',
      'struct_idx': '',
    };
    this.srch.sno = data.sno;
    this.srch.struct_idx = data.struct_idx;

    // ソート条件
    this.sort_order = "['seq_no']"; // 項目名
    this.reverse = false; // 昇順、降順
    this.sort_style = [];

    // 数値項目配列
    this.numItem = ['struct_idx', 'struct_no_s', 'struct_no_e', 'rosen_cd', 'sp', 'secchi_yyyy'];

    // 検索結果のソート条件(左表)
    this.sort_identifiers_left = [
            ['shisetsu_kbn_nm', 'seq_no'],
            ['-shisetsu_kbn_nm', 'seq_no'],
            ['shisetsu_keishiki_nm', 'seq_no'],
            ['-shisetsu_keishiki_nm', 'seq_no'],
            ['shisetsu_cd', 'seq_no'],
            ['-shisetsu_cd', 'seq_no'],
            ['struct_idx', 'seq_no'],
            ['-struct_idx', 'seq_no'],
            ['struct_no_s', 'seq_no'],
            ['-struct_no_s', 'seq_no'],
            ['struct_no_e', 'seq_no'],
            ['-struct_no_e', 'seq_no']
        ];

    // 検索結果のソート条件(右表)
    this.sort_identifiers_right = [
//            ['syozoku_cd', 'seq_no'],
//            ['-syozoku_cd', 'seq_no'],
            ['sort_priority_tenken_link', 'shisetsu_kbn_nm', 'sno', 'struct_idx', 'chk_mng_no', 'shichu_cnt'],
            ['-sort_priority_tenken_link', 'shisetsu_kbn_nm', 'sno', 'struct_idx', 'chk_mng_no', 'shichu_cnt'],
            ['phase', 'seq_no'],
            ['-phase', 'seq_no'],
            ['check_shisetsu_judge', 'seq_no'],
            ['-check_shisetsu_judge', 'seq_no'],
            ['syoken', 'seq_no'],
            ['-syoken', 'seq_no'],
            ['measures_shisetsu_judge', 'seq_no'],
            ['-measures_shisetsu_judge', 'seq_no'],
            ['sort_priority_confirm_btn', 'seq_no'],
            ['-sort_priority_confirm_btn', 'seq_no'],
            ['rosen_cd', 'seq_no'],
            ['-rosen_cd', 'seq_no'],
            ['rosen_nm', 'seq_no'],
            ['-rosen_nm', 'seq_no'],
            ['sp', 'seq_no'],
            ['-sp', 'seq_no'],
            ['syozaichi', 'seq_no'],
            ['-syozaichi', 'seq_no'],
            ['secchi_yyyy', 'seq_no'],
            ['-secchi_yyyy', 'seq_no'],
            ['check_dt', 'seq_no'],
            ['-check_dt', 'seq_no'],
            ['measures_dt', 'seq_no'],
            ['-measures_dt', 'seq_no'],
            ['re_measures_dt', 'seq_no'],
            ['-re_measures_dt', 'seq_no'],
            ['chk_company', 'seq_no'],
            ['-chk_company', 'seq_no'],
            ['chk_person', 'seq_no'],
            ['-chk_person', 'seq_no'],
            ['investigate_company', 'seq_no'],
            ['-investigate_company', 'seq_no'],
            ['investigate_person', 'seq_no'],
            ['-investigate_person', 'seq_no'],
            ['substitute_road_str', 'seq_no'],
            ['-substitute_road_str', 'seq_no'],
            ['emergency_road_str', 'seq_no'],
            ['-emergency_road_str', 'seq_no'],
            ['motorway_str', 'seq_no'],
            ['-motorway_str', 'seq_no'],
            ['senyou', 'seq_no'],
            ['-senyou', 'seq_no'],
            ['fukuin', 'seq_no'],
            ['-fukuin', 'seq_no'],
            ['dogen_mei', 'seq_no'],
            ['-dogen_mei', 'seq_no'],
            ['syucchoujo_mei', 'seq_no'],
            ['-syucchoujo_mei', 'seq_no'],
        ];

    // 所属コードに応じてソート条件(右表)の先頭を削除
    // (昇順、降順の2つ)
    // →制限を外す(データ不備が多いので点検前に直したい意図)
    //        if(this.syozoku_cd != 3) {
    //            this.sort_identifiers_right.shift();
    //            this.sort_identifiers_right.shift();
    //        }

    // 検索結果の保持
    if (this.$rootScope.args != null) {
      this.main_srch = {};
      this.main_srch.srch_done = this.$rootScope.args.srch_done; // 検索条件
      this.main_srch.default_tab = this.$rootScope.args.default_tab; // 初期表示タブ

      $rootScope.args = null;
    }

    setTimeout(() => {
      $("#sync-table4").on('scroll', (evt) => {
        $("#sync-table3").scrollTop($(evt.target).scrollTop());
      });
    }, 500);

    this.$http({
      method: 'POST',
      url: 'api/index.php/SchBsskChildrenAjax/get_srch_bssk_children',
      data: {
        sno: this.srch.sno,
        struct_idx: this.srch.struct_idx
      }

    }).then((data) => {
      if (data.data[0].sch_result_row != null) {
        this.data = JSON.parse(data.data[0].sch_result_row).sch_result;
        this.changeNumItem(); // 数値項目を数値化

        for (var i = 0; Object.keys(this.data).length > i; i++) {

          // ソート優先順位の設定
          this.setSortPriorityTenkenLink(i);
          this.setSortPriorityConfirmBtn(i);

          // 指定文字数以上の所見の表記を省略
          this.omitDisplayedSyoken(i, 30);
        }

      } else {
        delete this.data;
      }
    });

    $scope.close = function () {
      $uibModalInstance.close();
    };
  }

  // ソート優先順位の設定(項目名：点検調査票)
  setSortPriorityTenkenLink(i) {

    if ((this.data[i].sno == null) || (this.data[i].struct_idx == null) || (this.data[i].chk_mng_no == null)) {
      // 1:防雪柵以外、開けない
      this.data[i].sort_priority_tenken_link = 1;
    } else {
      // 2:防雪柵以外、開く
      this.data[i].sort_priority_tenken_link = 2;
    }
  }

  // 指定文字数以上の所見の表記を省略
  omitDisplayedSyoken(index, maxLength) {

    if (this.data[index].syoken.length > maxLength) {
      this.data[index].syoken = this.data[index].syoken.substr(0, maxLength) + '…';
    }
  }

  // ソート優先順位の設定(項目名：施設の健全性)
  setSortPriorityConfirmBtn(i) {

    if ((this.data[i].shisetsu_cd == null) || (this.data[i].shisetsu_ver == null) || (this.data[i].shisetsu_kbn == null) || (this.data[i].chk_mng_no == null) || (this.data[i].rireki_no == null)) {
      this.data[i].sort_priority_confirm_btn = 0;

    } else {
      // 確認ボタンが有効な場合
      this.data[i].sort_priority_confirm_btn = i + 1;
    }
  }

  //    close() {
  //        this.$uibModalInstance.close();
  //    }

  /**
   * 施設の健全性ダイアログを開く
   */
  openShisetsuJudge(param) {

    this.shisetsu_judge_modal = this.$uibModal.open({
      animation: true,
      templateUrl: 'views/about.html',
      controller: 'AboutCtrl as about',
      size: "lg",
      windowClass: "about-modal",
      resolve: {
        data: () => {
          return param;
        }
      }
    });
  }

  // 基本情報編集
  editBaseInfo(data) {
    this.$uibModalInstance.close();

    this.$rootScope.args = {};
    this.$rootScope.args.kbn = 1;

    // 更新
    // 更新時は行データが必要
    // 基本情報呼び出し引数
    this.$rootScope.args.dogen_cd = data.dogen_cd;
    this.$rootScope.args.syucchoujo_cd = data.syucchoujo_cd;
    this.$rootScope.args.sno = data.sno;
    this.$rootScope.args.shisetsu_cd = data.shisetsu_cd;
    this.$rootScope.args.shisetsu_ver = data.shisetsu_ver;

    // 再表示した際の検索結果保存
    this.$rootScope.args.srch_done = this.main_srch.srch_done;
    this.$rootScope.args.default_tab = this.main_srch.default_tab;

    this.$location.path("/shisetsu_info");
  }

  /**
   * ページ遷移用メソッド
   * @param link 遷移先のアドレス
   */
  location(link) {
    this.$uibModalInstance.close();

    // 再表示した際の検索結果保存
    if (this.main_srch != null) {
      this.$rootScope.args = {};
      //            this.$rootScope.args.dogen_cd = this.main_srch.dogen_cd;
      //            this.$rootScope.args.syucchoujo_cd = this.main_srch.syucchoujo_cd;
      //            this.$rootScope.args.srch = this.main_srch.srch;
      //            this.$rootScope.args.default_tab = this.main_srch.default_tab;
      this.$rootScope.args.srch_done = this.main_srch.srch_done;
      this.$rootScope.args.default_tab = this.main_srch.default_tab;
    }

    this.$location.path(link);
  }

  // ソート対象項目名を設定
  SetSortOrder(item) {

    var current = JSON.stringify(this.sort_order);
    var set = JSON.stringify(item);

    // ソート済みの項目を選択した場合はソートリセット
    if (current == set) {
      this.sort_order = "['seq_no']";

    } else {
      // 複数指定時は配列が設定される
      this.sort_order = item;
    }

    // 選択されたソート項目の色を変更
    this.sort_style = [];
    this.sort_style[this.sort_order] = {
      color: '#217dbb'
    };
  }

  changeNumItem() {
    for (var i = 0; i < this.data.length; i++) {
      for (var j = 0; j < this.numItem.length; j++) {
        if (this.data[i][this.numItem[j]]) {
          if (Number(this.data[i][this.numItem[j]])) {
            this.data[i][this.numItem[j]] = Number(this.data[i][this.numItem[j]]);
          }
        }
      }
    }
  }

}

let angModule = require('../app.js');
angModule.angApp.controller('BsskChildrenCtrl', ['$scope', '$uibModalInstance', '$uibModal', '$http', 'data', '$location', '$rootScope', function ($scope, $uibModalInstance, $uibModal, $http, data, $location, $rootScope) {
  return new BsskChildrenCtrl($scope, $uibModalInstance, $uibModal, $http, data, $location, $rootScope);
}]);
