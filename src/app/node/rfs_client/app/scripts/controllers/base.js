'use strict';

/**
 * @ngdoc function
 * @name rfsApp.controller:AboutCtrl
 * @description
 * # AboutCtrl
 * Controller of the rfsApp
 */


/**
 * この画面では、検索された施設の
 * 全ての健全性が確認できる画面を表示します
 * 検索されているため、すでに施設を特定するIDと
 * 最新の点検管理番号、履歴番号を渡してもらうことで
 * ここでの機能は指定された情報を表示することに特化します
 */
module.exports = class BaseCtrl {
  constructor(opt) {
    if (!opt) {
      opt = {};
    }

    this.$scope = opt.$scope;
    this.$http = opt.$http;
    this.$location = opt.$location;
    this.$q = opt.$q;

    if (this.$q) {
      this.canceler = this.$q.defer();
    }
    if (opt.messageBox) {
      this.windowLock();
    } else {
      this.windowUnlock();
    }

    this.location_message = "変更があった場合、内容が破棄されます。よろしいですか？";

    //    this.$scope.reload_code = String(Math.random());
    setTimeout(() => {
      window.scrollTo(0, 0);

      // Enterを殺す
      $("input").on("keydown", function (e) {
        if ((e.which && e.which === 13) || (e.keyCode && e.keyCode === 13)) {
          return false;
        } else {
          return true;
        }
      });
    }, 10);

  }

  windowLock() {
    window.onbeforeunload = function (event) {
      event = event || window.event;
      event.returnValue = 'ページから移動しますか？';
    };
  }

  windowUnlock() {
    window.onbeforeunload = function (event) {};
  }

  start($http) {
    var http;
    if ($http) {
      http = $http;
    } else {
      http = this.$http;
    }
    return http({
      method: 'GET',
      url: 'api/index.php/InquirySession/get_session',
      params: {}
    }).then((data) => {
      this.session = data.data;
    }).catch((err) => {
      this.session = null;
      console.log(err);
      window.onbeforeunload = null;
      location.href = err.data.url;
    });
  }

  /**
   * 確認用メソッド
   * @reterun Promise(OK時にresolve)
   */
  confirm(content, nomal) {
    var ok;
    var ng;
    if (nomal == 0) {
      // OK赤CANCEL青
      ok = 'btn-danger';
      ng = 'btn-info';
    } else {
      // OK青CANCEL赤
      ok = 'btn-info';
      ng = 'btn-danger';
    }
    var deferred = this.$q.defer();
    $.confirm({
      title: '確認',
      content: content,
      confirmButtonClass: ok,
      cancelButtonClass: ng,
      confirmButton: 'OK',
      cancelButton: 'Cancel',
      animation: 'RotateYR',
      confirm: () => {
        deferred.resolve(true);
      },
      cancel: function () {
        deferred.reject(false);
      }
    });
    return deferred.promise;
  }

  /**
   * アラートメソッド
   *
   */
  alert(title, msg) {
    var deferred = this.$q.defer();
    $.alert({
      title: title,
      content: msg,
      confirmButtonClass: 'btn-info',
      confirmButton: 'OK',
      animation: 'RotateYR',
      confirm: () => {
        deferred.resolve(true);
      }
    });
    return deferred.promise;
  }

  /**
   * 道路附帯施設管理システム内の建管、出張所をセッションで保持
   *
   *  維持管理システムで使用しているmngareaを書き換える
   *
   **/
  mngarea_update($http, dogen_cd, syucchoujo_cd) {
    return $http({
      method: 'GET',
      url: 'api/index.php/InquirySession/updMngarea',
      params: {
        dogen_cd: dogen_cd,
        syucchoujo_cd: syucchoujo_cd
      }
    }).then((data) => {
      // セッション上書き
      this.session = data.data;
    }).catch((err) => {
      this.session = null;
      console.log(err);
      window.onbeforeunload = null;
      location.href = err.data.url;
    });
  }


  location(link, confirm_mode) {
    // すべてのhttpをキャンセルする。
    if (this.canceler) {
      this.canceler.resolve();
    }
    // confirm_modeがtrueの時だけ、チェックする。
    if (confirm_mode) {
      this.confirm(this.location_message).then(() => {
        this.$location.path(link);
      });
    } else {
      this.$location.path(link);
    }
  }
}
