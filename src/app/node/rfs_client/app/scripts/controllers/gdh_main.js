"use strict";

var BaseCtrl = require("./base.js");

class GdhMainCtrl extends BaseCtrl {
  constructor($scope, $http, $location, $uibModal, $anchorScroll, $routeParams, $q, $route, $window, $filter) {
    super({
      $location: $location,
      $scope: $scope,
      $http: $http,
      $q: $q,
      messageBox: true
    });

    // Angularの初期化
    this.$scope = $scope;
    this.$http = $http;
    this.$location = $location;
    this.$uibModal = $uibModal;
    this.$anchorScroll = $anchorScroll;
    this.$q = $q;
    this.$route = $route;
    this.$window = $window;
    this.$filter = $filter;

    // GET引数でもらう
    this.sno = $routeParams.sno;
    this.gdh_idx = $routeParams.gdh_idx;

    // 各項目
    this.gdh_syubetsu_cd = "";
    this.kousa_kbn_cd = "";
    this.brd_color_cd = "";

    // 現場写真
    this.genba_syashin_select = null;

    this.waitOverlay = false;

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

      this.waitLoadOverlay = true; // 読込み中です

      return this.$http({
        method: 'GET',
        url: 'api/index.php/FamEditAjax/initEditMain',
        params: {
          sno: this.sno,
        }
      });

    }).then((data) => {

      this.start();

    });
  }

  /***
   * オープン時に呼ばれる
   *
   *    マスタデータ取得
   *    集計
   *    変数設定
   ***/
  async start() {
    this.setVariable();

    //    // デフォルト設定
    //    await super.start(this.$http);

    // init処理
    var data = await this.$http({
      method: 'GET',
      url: 'api/index.php/GdhMainAjax/init',
      params: {
        sno: this.sno,
        gdh_idx: this.gdh_idx,
      }
    });

    // 取得データバインド
    var json = data.data;
    this.setData(json);

    this.waitLoadOverlay = false;
    // 明示
    this.$scope.$apply();
  }

  setData(json) {
    // マスタセット
    this.mst.gdh_syubetsu = json.mst.gdh_syubetsu;
    this.mst.kousa = json.mst.kousa;
    this.mst.brd_color = json.mst.brd_color;
    this.mst.taisaku_status = json.mst.taisaku_status;
    this.mst.taisaku_status_min = json.mst.taisaku_status_min;
    this.mst.taisaku_kouhou = json.mst.taisaku_kouhou;
    this.mst.gaitou_higaitou = json.mst.gaitou_higaitou;
    this.mst.yotei_nendo = json.mst.yotei_nendo;

    // 検索結果
    this.shisetsu_sub = json.data.shisetsu_sub[0];
    this.response_status = json.data.response_status;
    this.pic_data = json.data.pic_data;
    this.brd_color = json.data.brd_color[0];
    this.min_gdh_idx = json.data.min_gdh_idx;
    this.daichou = json.data.daichou;

    // 新規登録の場合、編集モードで表示
    if (this.gdh_idx < 0) {
      this.editable = true;
    }

    // 取得値を初期値に設定
    if (json.data.shisetsu_sub.length > 0) {
      this.gdh_syubetsu_cd = this.shisetsu_sub.gdh_syubetsu_cd;
      this.kousa_kbn_cd = this.shisetsu_sub.kousa_kbn_cd;
    }
    if (json.data.brd_color.length > 0) {
      this.brd_color_cd = this.brd_color.brd_color_cd;
    }
    for (let i = 0; i < this.pic_data.length; i++) {
      if (this.pic_data[i].upload_dt) {
        this.pic_data[i].upload_dt = new Date(this.pic_data[i].upload_dt);
      }
      if (this.pic_data[i].shooting_dt) {
        this.pic_data[i].shooting_dt = new Date(this.pic_data[i].shooting_dt);
      }
    }

    for (var i = 0; i < this.response_status.length; i++) {
      // 年度フォーマットを変換
      // if (this.response_status[i].yotei_nendo_yyyy) {
      //   this.response_status[i].yotei_nendo_yyyy = Number(this.response_status[i].yotei_nendo_yyyy);
      // }
      // 道央道の該当/非該当判定
      if (this.response_status[i].taisaku_kbn_cd == 4) {
        this.dououdou = this.response_status[i].dououdou;
      }
    }
  }

  setVariable() {
    // 閲覧モード
    this.editable = false;

    this.mst = {}; // マスタ格納用

/*    // 予定年度
    var date = new Date(); // 現在日付を取得
    var year = date.getFullYear();

     // 設置年度
    this.mst.yotei_nendo = [];
    for (var i = 2012; i <= year+6; i++) {
      this.mst.yotei_nendo.push({
        "year": (i),
        "gengou": "H" + ((i) - 1988)
      });
    }
 */  }

  // モード変更
  chgMode() {
    if (this.editable) {
      this.confirm('変更があった場合、内容が破棄されます。よろしいですか？', 0).then(() => {
        this.editable = false;
        // メッセージ非表示
        this.windowUnlock();
        this.$window.location.reload(false);
      });
    } else {
      // disp_idx変化なし
      this.editable = true;
    }
  }

  /**
   * 現場写真のダイアログを開く
   */
  openGenbaSyashin() {

    // 現場写真の対応建管と出張所を渡す
    var dogen_syucchoujo = Array();
    dogen_syucchoujo['dogen_cd'] = this.session.mngarea.dogen_cd;
    dogen_syucchoujo['syucchoujo_cd'] = this.session.mngarea.syucchoujo_cd;

    this.genba_syashin_modal = this.$uibModal.open({
      animation: true,
      templateUrl: 'views/genba_syashin.html',
      controller: 'GenbaSyashinCtrl',
      size: null,
      backdrop: false,
      windowClass: 'genba-syashin-gdh',
      resolve: {
        dogen_syucchoujo: () => {
          return dogen_syucchoujo;
        },
        select: () => {
          return this.genba_syashin_select;
        }
      }
    });
    this.genba_syashin_modal.result.then((select) => {
      this.genba_syashin_select = select;
      this.genba_syashin_modal = null;
    });
  }

  // アップロード関係
  /**
   * 添付ファイルを削除する
   */
  async removeAttach($flow) {
    var message = '添付を削除してよろしいですか？';
    if (await this.confirm(message, 0)) {
      var picdata = {
        file_nm: "",
        path: "dummy",
        lat: "",
        lon: "",
        date: "",
        upload_dt: "",
        upload_account_cd: ""
      };

      this.pic_data = [];
      this.pic_data.push(picdata);

      $flow.files = [];
      this.$scope.$apply();
    }
  }

  /**
   * アップロードされたら実行される
   */
  fileUploadSuccess($file, message, $flow) {
    console.log("!Uploaded!!!!");
    console.log($file);
    console.log(message);
    message = JSON.parse(message);
    var picdata = {
      file_nm: $file.name,
      path: message.path,
      lat: message.lat,
      lon: message.lon,
      date: message.date,
      upload_dt: new Date(),
      upload_account_cd: this.session.ath.account_cd
    };
    $flow.files = [];
    this.pic_data = [];
    this.pic_data.push(picdata);
  }

  /**
   * 画像の部分のドラッグ操作時(IN)
   * .dropに.drag-over-imgを足す（背景青色になる）
   */
  dragOver(evt) {
    $(evt.target).find(".drop").addClass('drag-over-img');
  }

  /**
   * 画像の部分のドラッグ操作時(OUT)
   * .dropに.drag-over-imgを抜く
   */
  dragOut(evt) {
    $(evt.target).find(".drop").removeClass('drag-over-img');
  }

  /**
   * 現場写真からのドロップ
   */
  async dragDrop(evt, url) {

    $(evt.target).find(".drop").removeClass('drag-over-img');

    // URLから画像をコピー
    var data = await this.$http({
      method: 'POST',
      url: 'api/index.php/GdhMainAjax/imageFromUrl',
      data: {
        sno: this.sno,
        gdh_idx: this.gdh_idx,
        url: this.pic_data[0].path,
      }
    });

    // コピーした画像を再表示
    this.pic_data[0].path = data.data.copy_path;
  }

  // 保存処理
  async save() {
    var message = '入力内容を保存してよろしいですか？';
    let res = await this.confirm(message, 0);
    if (res) {
      this.waitOverlay = true;
      if (this.response_status.length > 0) {
        // save処理
        var data = await this.$http({
          method: 'POST',
          url: 'api/index.php/GdhMainAjax/save',
          data: {
            sno: this.sno,
            gdh_idx: this.gdh_idx,
            gdh_syubetsu_cd: this.gdh_syubetsu_cd,
            kousa_kbn_cd: this.kousa_kbn_cd,
            brd_color_cd: this.brd_color_cd,
            response_status: this.response_status,
            pic_data: this.pic_data,
            dououdou: this.dououdou,
          }
        });

        var json = data.data;
        var newGdhIdx = json.gdh_idx;

        // 台帳保存
        await this.$http({
          method: 'POST',
          url: 'api/index.php/FamEditAjax/createDaichouExcel',
          data: {
            daichou: this.daichou,
          }
        });

        await this.alert('登録', '保存が完了しました');

        this.waitOverlay = false;

        // 表示内容更新
        if (this.gdh_idx < 0) {
          // 画面遷移
          var path = "#/gdh_main/" + this.sno + "/" + newGdhIdx;
          window.location.href = path;
        } else {
          // リロード
          this.windowUnlock();
          location.reload();
        }

      } else {
        await this.alert('登録', '更新対象データがありません')
      }
    }
  }

  async locationFamEdit() {
    var flg = true;
    if (this.editable) {
      var res = await this.confirm('変更があった場合、内容が破棄されます。よろしいですか？', 0);
      if (!res) {
        flg = false;
      }
    }

    if (flg) {
      // メッセージ非表示
      this.windowUnlock();
      window.location.href = "#/fam_edit/" + this.sno;
    }
  }

  close() {
    this.windowUnlock();
    location.reload();

    setTimeout(() => {
      this.windowLock();
    }, 1000);
    this.$uibModalInstance.close(null);
  }
}

let angModule = require('../app.js');
angModule.angApp.controller('GdhMainCtrl', ['$scope', '$http', '$location', '$uibModal', '$anchorScroll', '$routeParams', '$q', '$route', '$window', '$filter', function ($scope, $http, $location, $uibModal, $anchorScroll, $routeParams, $q, $route, $window, $filter) {
  return new GdhMainCtrl($scope, $http, $location, $uibModal, $anchorScroll, $routeParams, $q, $route, $window, $filter);
}]);
