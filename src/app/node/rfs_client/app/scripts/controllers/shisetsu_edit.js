'use strict';

/* globals OpenLayers:true */

var BaseCtrl = require("./base.js");
/**
 * @ngdoc function
 * @name rfsApp.controller:ShisetsuEditCtrl
 * @description
 * # ShisetsuEditCtrl
 * Controller of the rfsApp
 */
class ShisetsuEditCtrl extends BaseCtrl {
  constructor($scope, $filter, $http, $uibModal, $routeParams, $location, $q, $window) {
    super({
      $location: $location,
      $scope: $scope,
      $http: $http,
      $q: $q,
      messageBox: true
    });

    this.$location = $location;
    this.$http = $http;
    this.$uibModal = $uibModal;
    this.$q = $q;
    this.$scope = $scope;
    this.$window = $window;

    this.waitOverlay = false;
    this.err_message = [];
    this.sno = $routeParams.sno;
    // 1:main、2:台帳
    this.from = $routeParams.from;
    // 0:通常の遷移、1:id登録後の遷移
    this.fromid = $routeParams.fromid;

    // 新規の場合はsnoが0で来る
    if (this.sno == 0) {
      this.title = "基本情報の新規登録画面";
    } else {
      this.title = "基本情報の編集画面";
    }

    // 定数定義
    this.initData();

    // ログイン情報
    // セッションから建管と出張所コードを取得
    super.start($http).then(() => {

      // session情報を保持
      this.ath_dogen_cd = this.session.ath.dogen_cd;
      this.ath_syucchoujo_cd = this.session.ath.syucchoujo_cd;
      this.ath_syozoku_cd = this.session.ath.syozoku_cd;
      this.ath_account = this.session.ath.account_cd;
      this.mng_dogen_cd = this.session.mngarea.dogen_cd;
      this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
      this.imm_url = this.session.url;

      return this.initLoad();
    });
  }

  // 初期値
  initData() {
    // 閲覧モード
    this.editable = false;
    // 初期ステータス
    this.idPhase = false; // 新規の場合にtrueとなる
    if (this.sno == 0) {
      // 新規の時は、管理番号の保存と、内容の保存の2つに分けるため
      // 管理番号保存するまでは、idPhaseはtrue
      // 保存後はfalseとなる。
      // 施設情報編集時はfalse
      this.idPhase = true;
      // 編集モード固定
      this.editable = true;
    } else {
      // ID登録後は編集モードにしよう
      if (this.fromid == 1) {
        this.editable = true;
      }
      // 設置年度の値
      var date = new Date(); // 現在日付を取得
      var year = date.getFullYear();
      this.nendo_secchi = [];
      //this.nendo_haishi = [];
      // for (var i = year; i > 1989; i--) {
      //   this.nendo_secchi.push({
      //     "year": i,
      //     "gengou": "H" + (i - 1988) + "年"
      //   });
      //   this.nendo_haishi.push({
      //     "year": i,
      //     "gengou": "H" + (i - 1988) + "年"
      //   });
      // }
      // this.nendo_secchi.push({
      //   "year": 1989,
      //   "gengou": "H元年"
      // });
      // this.nendo_haishi.push({
      //   "year": 1989,
      //   "gengou": "H元年"
      // });
      // for (var i = 1988; i >= 1975; i--) {
      //   this.nendo_secchi.push({
      //     "year": i,
      //     "gengou": "S" + (i - 1925) + "年"
      //   });
      //   this.nendo_haishi.push({
      //     "year": i,
      //     "gengou": "S" + (i - 1925) + "年"
      //   });
      // }

      this.mst_kyouyou_kbn = [{
        kyouyou_kbn: 1,
        kyouyou_kbn_str: "供用"
      }, {
        kyouyou_kbn: 2,
        kyouyou_kbn_str: "一部休止"
      }, {
        kyouyou_kbn: 0,
        kyouyou_kbn_str: "休止"
      }];

      //横断区分
      this.mst_lr = [{
        id: 0,
        label: "L"
      }, {
        id: 1,
        label: "R"
      }, {
        id: 2,
        label: "C"
      }, {
        id: 3,
        label: "LR"
      }];

      //上下区分
      this.mst_ud = [{
        id: 0,
        label: "上"
      }, {
        id: 1,
        label: "下"
      }, {
        id: 2,
        label: "上下"
      }];

      // 緊急輸送道路
      this.mst_emergency_road = [{
        id: 1,
        label: "第一次"
      }, {
        id: 2,
        label: "第二次"
      }, {
        id: 3,
        label: "第三次"
      }];
    }
    // dataオブジェクトを初期化
    this.data = {
      zenkei: []
    };

    this.patrol_types = {
      houtei: [],
      huzokubutsu: [],
      teiki_pat: [],
    };
  }

  // 最初にデータを読み出す。
  initLoad() {
    // init
    return this.$http({
      method: 'GET',
      url: 'api/index.php/ShisetsuEditAjax/initShisetsuEdit',
      params: {
        sno: this.sno
      }
    }).then((data) => {
      var json = data.data;

      // 電気通信URL
      this.ele_url = json.ele_url;

      // 年度リスト
      this.nendo_secchi = json.wareki_list;
      this.nendo_haishi = json.wareki_list;

      // 新規・修正共通変数
      // 管理者
      this.kanrisya = json.kanri_info[0];
      // 施設区分をセット
      this.shisetsu_kbn_dat = json.shisetsu_kbn;

      // 新規以外
      if (this.sno != 0) {
        // 施設
        this.data = json.shisetsu[0];
        this.data.zenkei = [];

        // 座標変換
        var ret;
        var ret_arr = [];
        // 緯度
        if (this.data.lat) {
          ret = this.chgCoordFrom10(this.data.lat);
          ret_arr = ret.split(":");
          this.data.lat_deg = Number(ret_arr[0]);
          this.data.lat_min = Number(ret_arr[1]);
          this.data.lat_sec = Number(ret_arr[2]);
        }
        // 経度
        if (this.data.lon) {
          ret = "";
          ret_arr = [];
          ret = this.chgCoordFrom10(this.data.lon);
          ret_arr = ret.split(":");
          this.data.lon_deg = Number(ret_arr[0]);
          this.data.lon_min = Number(ret_arr[1]);
          this.data.lon_sec = Number(ret_arr[2]);
        }

        // 防雪柵の場合は防雪柵支柱データを保持
        if (this.data.shisetsu_kbn == 4) {
          this.data.bousetsusaku = json.bousetsusaku;
          // 点検票有無
          this.data.chk_data_exist = json.chk_data_exist;
          // 防雪柵カウント
          if (this.data.bousetsusaku) {
            this.dispIdx = 1; // 支柱表示INDEX1
            this.bousetsu_base_cnt = this.data.bousetsusaku.length - 1;
            // 全項目数値に変換しないと表示しなかった
            for (var i = 0; i < this.data.bousetsusaku.length; i++) {
              if (this.data.bousetsusaku[i].struct_no_s) {
                this.data.bousetsusaku[i].struct_no_s = Number(this.data.bousetsusaku[i].struct_no_s);
              }
              if (this.data.bousetsusaku[i].struct_no_e) {
                this.data.bousetsusaku[i].struct_no_e = Number(this.data.bousetsusaku[i].struct_no_e);
              }
              if (this.data.bousetsusaku[i].sp) {
                this.data.bousetsusaku[i].sp = Number(this.data.bousetsusaku[i].sp);
              }
            }
          }
        }
        // 施設形式
        this.shisetsu_keishiki_dat = json.shisetsu_keishiki;
        // 形式区分
        this.keishiki_kubun = JSON.parse(json.keishiki_kubun[0].kk_info);
        // 2個までしかない
        this.keishiki_kubun1 = {};
        this.keishiki_kubun2 = {};
        if (this.keishiki_kubun) {
          if (this.keishiki_kubun.length == 2) {
            this.keishiki_kubun1 = this.keishiki_kubun[0];
            this.keishiki_kubun2 = this.keishiki_kubun[1];
          } else if (this.keishiki_kubun.length == 1) {
            this.keishiki_kubun1 = this.keishiki_kubun[0];
          }
        }
        /***
         * 数値項目は、数値変換しないと表示しない
         * 数値項目
         *   測点(自)、測点(至)、交通量4つ、延長
         ***/
        if (this.data.sp) {
          this.data.sp = Number(this.data.sp);
        }
        if (this.data.sp_to) {
          this.data.sp_to = Number(this.data.sp_to);
        }
        if (this.data.koutsuuryou_day) {
          this.data.koutsuuryou_day = Number(this.data.koutsuuryou_day);
        }
        if (this.data.koutsuuryou_oogata) {
          this.data.koutsuuryou_oogata = Number(this.data.koutsuuryou_oogata);
        }
        if (this.data.koutsuuryou_hutuu) {
          this.data.koutsuuryou_hutuu = Number(this.data.koutsuuryou_hutuu);
        }
        if (this.data.koutsuuryou_12) {
          this.data.koutsuuryou_12 = Number(this.data.koutsuuryou_12);
        }
        if (this.data.encho) {
          this.data.encho = Number(this.data.encho);
        }

        // 路線
        this.rosen_dat = json.rosen;
        if (this.data.rosen_cd) {
          for (var i = 0; i < this.rosen_dat.length; i++) {
            var item = this.rosen_dat[i];
            if (item.rosen_cd == this.data.rosen_cd) {
              this.data.rrow = item;
              break;
            }
          }
        }

        // 設置年度と廃止年度が入らないのでセット
        if (this.data.secchi_yyyy) {
          for (var i = 0; i < this.nendo_secchi.length; i++) {
            var secchi_data = this.nendo_secchi[i];
            if (secchi_data.year == this.data.secchi_yyyy) {
              this.data.secchi_row = secchi_data;
              break;
            }
          }
        }
        // 設置年度と廃止年度が入らないのでセット
        if (this.data.haishi_yyyy) {
          for (var i = 0; i < this.nendo_haishi.length; i++) {
            var haishi_data = this.nendo_haishi[i];
            if (haishi_data.year == this.data.haishi_yyyy) {
              this.data.haishi_row = haishi_data;
              break;
            }
          }
        }

        // 施設区分と各種点検の組み合わせ一覧
        this.patrol_types = json.patrol_types;

        this.picture = {};
        this.imageShow();
      }
    });
  }

  // 施設区分が附属物点検の実施対象かどうかを返す
  isHuzokubutsuTarget(shisetsuKbn) {
    if (this.patrol_types.huzokubutsu.indexOf(Number(shisetsuKbn)) > -1) {
      return true;
    }
    return false;
  }

  // IDの保存を行う
  saveId() {
    // console.debug(this.data);
    this.confirm('管理番号を登録してよろしいですか？', 1).then(() => {

      // スピナーの表示
      this.waitOverlay = true;

      // エラーメッセージ初期化
      this.err_message = [];

      // 入力チェック
      this.validateId();

      // エラーメッセージが入っている場合はエラーあり
      if (this.err_message.length > 0) {
        var errs = "";
        for (var i = 0; i < this.err_message.length; i++) {
          if (i != 0) {
            errs += "<br>";
          }
          errs += "・" + this.err_message[i];
        }
        this.alert("入力エラー", errs);
        this.waitOverlay = false;
        return this.$q.reject("入力エラー");
      }

      this.$http({
        method: 'POST',
        url: 'api/index.php/ShisetsuEditAjax/saveShisetsuCd',
        data: {
          data: this.data
        }
      }).then((data) => {
        // 0終了時は正常に編集された
        if (data.data == -1) {
          return this.alert("重複エラー", "施設管理番号が既に登録されています");
        } else if (data.data['status'] == 0) {
          // 正常
          this.sno = data.data['sno'];
          return this.alert("完了", "施設管理番号の登録が完了しました<br>引き続き施設情報を登録してください");
        }
      }).finally((data) => {
        // スピナーの非表示
        this.waitOverlay = false;
        if (this.sno) {
          this.$location.path("/shisetsu_edit/" + this.sno + "/" + this.from + "/1");
        }
      });
    });
  }

  save() {
    // console.debug(this.data);
    this.confirm('保存してよろしいですか？', 1).then(() => {

      // スピナーの表示
      this.waitOverlay = true;

      // エラーメッセージ初期化
      this.err_message = [];

      // 入力チェック
      this.validate();

      // エラーメッセージが入っている場合はエラーあり
      if (this.err_message.length > 0) {
        var errs = "";
        for (var i = 0; i < this.err_message.length; i++) {
          if (i != 0) {
            errs += "<br>";
          }
          errs += "・" + this.err_message[i];
        }
        this.alert("入力エラー", errs);
        this.waitOverlay = false;
        return this.$q.reject("入力エラー");
      }

      this.$http({
        method: 'POST',
        url: 'api/index.php/ShisetsuEditAjax/setShisetsu',
        data: {
          dogen_cd: this.dogen_cd,
          syucchoujo_cd: this.syucchoujo_cd,
          data: this.data
        }
      }).then((data) => {
        // 0終了時は正常に編集された
        if (data.data == -1) {
          return this.alert("重複エラー", "施設管理番号が既に登録されています");
        } else if (data.data == 0) {
          return this.imageSave();
        }

      }).then((data) => {
        return this.$http({
          method: 'POST',
          url: this.ele_url + 'api/index.php/OutputExcel/outChkDataSno',
          data: {
            sno: this.data.sno
          }
        });

      }).then((data) => {
        if (data.data == 0) {
          var mode;
          if (this.kbn == 0) {
            mode = "登録";
          } else {
            mode = "更新";
          }
          return this.alert("確認", mode + "が完了しました");
        }

      }).finally(() => {
        // スピナーの非表示
        this.waitOverlay = false;
        this.editable = false;
        /*
                  // メッセージ非表示
                  this.windowUnlock();
                  // リロード
                  this.$window.location.reload();
        */
      });

    });
  }
  /**
   * ID登録時バリデーション
   * @return true:OK false:Error
   */
  validateId() {
    // 必須チェック
    this.chkRequiredId();
    // 内容チェック
    this.chkInputId();
  }

  /**
   * バリデーション
   * @return true:OK false:Error
   */
  validate() {
    // 必須チェック
    this.chkRequired();
    // 内容チェック
    this.chkInput();
  }

  // 必須チェック ID登録時
  chkRequiredId() {
    // 施設名
    if (!this.data.shisetsu_kbn) {
      this.err_message.push("施設名が未選択です");
    }
    // 施設管理番号
    if (!this.data.shisetsu_cd) {
      this.err_message.push("施設管理番号が未入力です");
    }
  }

  // 必須チェック 内容登録時
  chkRequired() {
    // 施設管理番号
    if (!this.data.shisetsu_cd) {
      this.err_message.push("施設管理番号が未入力です");
    }
    // 形式(附属物点検施設のみ)
    if (this.isHuzokubutsuTarget(this.data.shisetsu_kbn)) {
      if (!this.data.rrow) {
        this.err_message.push("路線が未選択です");
      }
      if (!this.data.shisetsu_keishiki_cd) {
        this.err_message.push("形式が未選択です");
      }
    }
  }

  // 内容チェック ID登録時
  chkInputId() {
    // 管理番号入力チェック
    if (!this.chkShisetsuCd()) {
      // 半角英数とハイフン
      this.err_message.push("管理番号は半角英数またはハイフンのみの入力です");
    }
  }

  // 内容チェック 内容登録時
  chkInput() {
    // 管理番号入力チェック
    if (!this.chkShisetsuCd()) {
      // 半角英数とハイフン
      this.err_message.push("管理番号は半角英数またはハイフンのみの入力です");
    }

    var lat_deg = String(this.data.lat_deg);
    var lat_min = String(this.data.lat_min);
    var lat_sec = String(this.data.lat_sec);
    // 緯度
    // 全部入っているか、全部入っていない以外はエラー
    if (lat_deg.match(/^[0-9]+$/) && lat_min.match(/^[0-9]+$/) && (lat_sec.match(/^[0-9]+$/) || lat_sec.match(/^[0-9]+\.[0-9]+$/))) {
      // 変換し緯度10進に保存
      var lat_10 = this.chgCoordFrom60(this.data.lat_deg, this.data.lat_min, this.data.lat_sec);
      this.data.lat = lat_10;
    } else if (!this.data.lat_deg && !this.data.lat_min && !this.data.lat_sec) {
      // 全部入っていない場合はスルー
      delete this.data.lat;
    } else {
      // どれか入っていない場合はエラー
      this.err_message.push("緯度の入力が不正です");
    }

    var lon_deg = String(this.data.lon_deg);
    var lon_min = String(this.data.lon_min);
    var lon_sec = String(this.data.lon_sec);
    // 緯度
    // 全部入っているか、全部入っていない以外はエラー
    if (lon_deg.match(/^[0-9]+$/) && lon_min.match(/^[0-9]+$/) && (lon_sec.match(/^[0-9]+$/) || lon_sec.match(/^[0-9]+\.[0-9]+$/))) {
      // 変換し緯度10進に保存
      var lon_10 = this.chgCoordFrom60(this.data.lon_deg, this.data.lon_min, this.data.lon_sec);
      this.data.lon = lon_10;
    } else if (!this.data.lon_deg && !this.data.lon_min && !this.data.lon_sec) {
      // 全部入っていない場合はスルー
      delete this.data.lon;
    } else {
      // どれか入っていない場合はエラー
      this.err_message.push("経度の入力が不正です");
    }

    // 施設区分が防雪柵の場合は、支柱番号をチェック
    if (this.data.shisetsu_kbn == 4) {
      // 支柱インデックスが無かったらエラー
      if (this.data.bousetsusaku.length == 1) {
        this.err_message.push("支柱が登録されていません。");
      } else {
        // 先頭は1じゃないとダメ
        if (this.data.bousetsusaku[1].struct_no_s != 1) {
          this.err_message.push("支柱番号の最初は1のみが有効です");
        }
        // 先頭の要素は親なのでOK
        for (var i = 1; i < this.data.bousetsusaku.length; i++) {
          var bs_data = this.data.bousetsusaku[i];
          // 支柱番号は+10まで
          if (bs_data.struct_no_s + 11 <= bs_data.struct_no_e) {
            this.err_message.push(i + "/" + (this.data.bousetsusaku.length - 1) + "の支柱番号の数が多すぎます");
          }
          // 前のEndと今のStartは同じでなければならない(最初を除く)
          var bs_data_before = this.data.bousetsusaku[i - 1];
          if (bs_data_before.struct_no_e != bs_data.struct_no_s && i > 1) {
            this.err_message.push(i + "/" + (this.data.bousetsusaku.length - 1) + "で、支柱番号の始まりが不正です");
          }
          // 支柱番号は後ろの方が大きくないとNG
          if (bs_data.struct_no_s >= bs_data.struct_no_e) {
            this.err_message.push(i + "/" + (this.data.bousetsusaku.length - 1) + "で、支柱番号の大小が不正です");
          }
        }
      }
    }
  }

  chkShisetsuCd() {

    // 入っていない場合は正常
    if (!this.data.shisetsu_cd) {
      return true;
    }

    // 半角英数とハイフンのみ
    if (this.data.shisetsu_cd.match(/^[a-zA-Z0-9\-]+$/) == null) {
      return false;
    }
    return true;
  }

  openMap() {

    // 地図用
    //        this.data.syucchoujoLat = this.kanrisya.lat;
    //        this.data.syucchoujoLon = this.kanrisya.lon;
    this.data.syucchoujo = {};
    this.data.syucchoujo.lt_lat = this.kanrisya.lt_lat;
    this.data.syucchoujo.rb_lat = this.kanrisya.rb_lat;
    this.data.syucchoujo.lt_lon = this.kanrisya.lt_lon;
    this.data.syucchoujo.rb_lon = this.kanrisya.rb_lon;
    this.data.editable = this.editable;

    // 10進に変換した値をセットする
    var lat_deg = String(this.data.lat_deg);
    var lat_min = String(this.data.lat_min);
    var lat_sec = String(this.data.lat_sec);
    // 緯度
    // 全部入っているか、全部入っていない以外はエラー
    if (lat_deg.match(/^[0-9]+$/) && lat_min.match(/^[0-9]+$/) && (lat_sec.match(/^[0-9]+$/) || lat_sec.match(/^[0-9]+\.[0-9]+$/))) {
      // 変換し緯度10進に保存
      var lat_10 = this.chgCoordFrom60(this.data.lat_deg, this.data.lat_min, this.data.lat_sec);
      this.data.lat = lat_10;
    } else {
      this.data.lat = "";
    }

    var lon_deg = String(this.data.lon_deg);
    var lon_min = String(this.data.lon_min);
    var lon_sec = String(this.data.lon_sec);
    // 緯度
    // 全部入っているか、全部入っていない以外はエラー
    if (lon_deg.match(/^[0-9]+$/) && lon_min.match(/^[0-9]+$/) && (lon_sec.match(/^[0-9]+$/) || lon_sec.match(/^[0-9]+\.[0-9]+$/))) {
      // 変換し緯度10進に保存
      var lon_10 = this.chgCoordFrom60(this.data.lon_deg, this.data.lon_min, this.data.lon_sec);
      this.data.lon = lon_10;
    } else {
      this.data.lon = "";
    }

    this.map_modal = this.$uibModal.open({
      animation: true,
      templateUrl: 'views/shisetsu_map.html',
      controller: 'ShisetsuMapCtrl as map',
      size: "lg",
      resolve: {
        data: () => {
          return this.data;
        }
      }
    });
    this.map_modal.result.then((data) => {
      if (data) {
        if (data.lat && data.lon) {
          this.setLonLat(data.lon, data.lat);
        }
      }
    });
  }

  /**
   * 画像ファイルをサーバーへアップする。
   * @param $flow flowライブラリのオブジェクト
   * @param tenken_kasyo_row tenken_kasyoのオブジェクト nullの場合は全景
   * @param status 0:点検前 1:措置後
   */
  imageUpload($flow, use_flg, struct_idx) {
    struct_idx = struct_idx | 0;
    /*// 新規＆全景写真の場合は別処理
    if (this.kbn == 0 && struct_idx == 0) {
        this.newZenkeiUpload($flow, struct_idx);
    } else {
        this.imageUploadMain($flow, struct_idx);
    }*/

    if ($flow.files.length > 1) {
      $flow.files.shift();
    }

    $flow.opts.query = {
      mode: 'zenkei_picture',
      sno: this.sno,
      shisetsu_cd: this.data.shisetsu_cd,
      shisetsu_ver: this.data.shisetsu_ver,
      struct_idx: struct_idx,
      query_cd: this.picture.query_cd,
      use_flg: use_flg,
    };
    $flow.upload();
    $flow.on("complete", () => {
      console.log("!Uploaded!!!!");
      $flow.files = [];
      this.imageShow();
    });

  }

  /**
   * 画像を表示するために、サーバに画像のパスを問い合わせ表示する。
   */
  imageShow() {
    return this.$http({
      method: 'POST',
      url: 'api/index.php/PictureAjax/get_picture',
      data: {
        mode: 'zenkei_picture',
        sno: this.data.sno,
        //shisetsu_cd: this.data.shisetsu_cd,
        //shisetsu_ver: this.data.shisetsu_ver,
        //                syucchoujo_cd: this.syucchoujo_cd,
        use_flg: [1, 2],
        query_cd: this.picture.query_cd
      }

    }).then((data) => {
      this.picture.query_cd = data.data.query_cd;
      // 今のzenkei_pictureと新しいものをマージする。
      // 今のzenkei_picutreのdelフラグはクライアント側が最新
      let old_zenkei_picutre = this.zenkei_picture;
      this.zenkei_picture = data.data;
      if (old_zenkei_picutre) {
        this.zenkei_picture.data.forEach((val, key) => {
          for (let i = 0; i < old_zenkei_picutre.data.length; i++) {
            if (old_zenkei_picutre.data[i].zenkei_picture_cd == val.zenkei_picture_cd) {
              val.del = old_zenkei_picutre.data[i].del;
              val.description = old_zenkei_picutre.data[i].description;
              val.exif_dt = old_zenkei_picutre.data[i].exif_dt;
            }
          }
        });
      }

      // this.zenkei_pictureからthis.zenkeiへ反映する。
      var show_key = -1;
      this.zenkei_picture.data.forEach((val, key) => {
        if (val.struct_idx == 0) {
          if (val.del != 1) {
            if (val.use_flg == 1) {
              show_key = key;
            }
            if (val.exif_dt) {
              val.exif_dt = moment(val.exif_dt).toDate();
            }
            this.data.zenkei[val.use_flg] = val;
          }
        } else {
          for (var i = 0; i < this.data.bousetsusaku.length; i++) {
            var info = this.data.bousetsusaku[i];
            if (info.struct_idx == val.struct_idx && val.del != 1) {
              info.image_src = val.path;
            }
          }
        }
      });
      // 緯度経度がなくて、全景写真のデータに緯度経度があった場合
      // 緯度経度があっても上書く
      //if (!this.data.lon && this.zenkei_picture.data[show_key] && Number(this.zenkei_picture.data[show_key].lon)) {
      if (this.zenkei_picture.data[show_key] && Number(this.zenkei_picture.data[show_key].lon) && this.editable == true) {
        let lon = Number(this.zenkei_picture.data[show_key].lon);
        let lat = Number(this.zenkei_picture.data[show_key].lat);
        this.setLonLat(lon, lat);
      }

    });
  }

  setLonLat(lon, lat) {
    // 10進の緯度経度をセット
    this.data.lon = lon;
    this.data.lat = lat;
    // 座標変換
    var ret;
    var ret_arr = [];
    // 緯度
    ret = this.chgCoordFrom10(lat);
    ret_arr = ret.split(":");
    this.data.lat_deg = Number(ret_arr[0]);
    this.data.lat_min = Number(ret_arr[1]);
    this.data.lat_sec = Number(ret_arr[2]);
    // 経度
    ret = "";
    ret_arr = [];
    ret = this.chgCoordFrom10(lon);
    ret_arr = ret.split(":");
    this.data.lon_deg = Number(ret_arr[0]);
    this.data.lon_min = Number(ret_arr[1]);
    this.data.lon_sec = Number(ret_arr[2]);

    // 住所の取得
    return this.$http({
      method: 'GET',
      url: 'api/index.php/SrhShisetsuAjax/get_addr',
      params: {
        mode: 'addr',
        lat: lat,
        lon: lon
      }
    }).then((data) => {
      if (data) {
        // 住所をセット
        this.data.shityouson = data.data[0].twon;
        this.data.azaban = data.data[0].streat;
      }
    });
  }

  /**
   * 一時保存と確定保存時に画像を本登録する。
   */
  imageSave() {
    this.zenkei_picture.data.forEach((value, key) => {
      if (value.del != 1) {
        value.del = 0;
      }
    });

    let data = JSON.parse(JSON.stringify(this.zenkei_picture.data));
    data.forEach((value, key) => {
      if (value.exif_dt) {
        value.exif_dt = moment(value.exif_dt).format("YYYY-MM-DD");
      }
    });

    return this.$http({
      method: 'POST',
      url: 'api/index.php/PictureAjax/save_fix_picture',
      data: {
        // 全景写真用
        sno: this.sno,
        //                shisetsu_cd: this.data.shisetsu_cd,
        //                shisetsu_ver: this.data.shisetsu_ver,
        zenkei_picture_data: data
      }
    });
  }

  /**
   * 画像を削除する。（サーバ処理は行わない。サーバ処理はimageSaveで行われる。）
   * this.picture.data.delに1を入れ、削除フラグを立てる。
   */
  deleteImage(use_flg, struct_idx) {
    struct_idx = struct_idx | 0;

    for (var i = 0; i < this.zenkei_picture.data.length; i++) {
      var pic = this.zenkei_picture.data[i];
      if (struct_idx == 0 && pic.struct_idx == 0) {
        if (use_flg == pic.use_flg) {
          this.data.zenkei[use_flg] = null;
          pic.del = 1;
        }
      } else {
        for (var j = 0; j < this.data.bousetsusaku.length; j++) {
          var info = this.data.bousetsusaku[j];
          if (struct_idx == info.struct_idx && info.struct_idx == pic.struct_idx) {
            info.image_src = null;
            pic.del = 1;
          }
        }
      }
    }
  }

  /**
   * 現場写真のダイアログを開く
   */
  openGenbaSyashin() {

    /*
            // 施設管理番号が無いと保存できない仕様
            if (!this.chkUpladOk()) {
                return;
            }
    */

    // 現場写真の対応建管と出張所を渡す
    var dogen_syucchoujo = {};
    dogen_syucchoujo['dogen_cd'] = this.data.dogen_cd;
    dogen_syucchoujo['syucchoujo_cd'] = this.data.syucchoujo_cd;

    this.genba_syashin_modal = this.$uibModal.open({
      animation: true,
      templateUrl: 'views/genba_syashin2.html',
      controller: 'GenbaSyashinCtrl',
      size: null,
      backdrop: false,
      windowClass: 'genba-syashin',
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
   * @param model tenken_kasyo_row
   * @param url 現場写真のURL
   */
  dragDrop(evt, jq_evt, model, use_index, struct_idx) {

    struct_idx = struct_idx | 0;
    $(evt.target).find(".drop").removeClass('drag-over-img');
    var promise;
    promise = this.$http({
      method: 'POST',
      url: 'api/index.php/PictureAjax/image_from_url',
      data: {
        mode: 'zenkei_picture',
        sno: this.sno,
        shisetsu_cd: this.data.shisetsu_cd,
        shisetsu_ver: this.data.shisetsu_ver,
        struct_idx: struct_idx,
        query_cd: this.picture.query_cd,
        use_flg: use_index,
        url: model.src,
        description: model.description,
        exif_dt: model.exif_dt,
      }
    });
    promise.then((data) => {
      this.imageShow();
    });

  }

  /**
   * ページ遷移用メソッド
   * @param link 遷移先のアドレス
   */
  location(link) {
    this.confirm('変更があった場合無視されますがよろしいですか？', 0).then(() => {
      this.$location.path(link);
    });
  }

  /* 支柱INDEX 1戻す */
  ClickPreviousFunction() {
    this.dispIdx = this.dispIdx - 1;
  }

  /* 支柱INDEX 1進む */
  ClickNextFunction() {
    this.dispIdx = this.dispIdx + 1;
  }

  /* 支柱INDEX追加 */
  ClickCreateIdx() {

    this.confirm('防雪柵の支柱インデックスを追加しますか？', 1).then(() => {
      var last = this.data.bousetsusaku[this.data.bousetsusaku.length - 1];
      // 最初は1~11
      var struct_no_e;
      if (last['struct_idx'] == 0) {
        struct_no_e = 1;
      } else {
        struct_no_e = last['struct_no_e'];
      }
      var arr = {
        struct_idx: Number(last['struct_idx']) + 1,
        struct_no_s: Number(struct_no_e),
        struct_no_e: Number(struct_no_e) + 10,
      };
      this.data.bousetsusaku.push(arr);
      this.dispIdx = this.data.bousetsusaku.length - 1;
    });

  }

  /* 支柱インデックスの削除 */
  ClickDeleteIdx() {
    this.confirm('表示の防雪柵支柱インデックスを削除しますか？', 1).then(() => {
      // 表示中のindexを削除する
      this.data.bousetsusaku.splice(this.dispIdx, 1);
      if (this.dispIdx != 1) {
        this.dispIdx--;
      }
    });
  }

  /**
   * 施設名変更イベント
   *
   * 施設名を変更したときに、頭文字2文字を決定する。
   *
   */
  shisetsuChange() {
    if (this.data.shisetsu_kbn == 1) {
      this.data.shisetsu_cd = "DH";
    } else if (this.data.shisetsu_kbn == 2) {
      this.data.shisetsu_cd = "JD";
    } else if (this.data.shisetsu_kbn == 3) {
      this.data.shisetsu_cd = "SS";
    } else if (this.data.shisetsu_kbn == 4) {
      this.data.shisetsu_cd = "BS";
    } else if (this.data.shisetsu_kbn == 5) {
      this.data.shisetsu_cd = "YH";
    } else if (this.data.shisetsu_kbn == 6) {
      this.data.shisetsu_cd = "KA";
    } else if (this.data.shisetsu_kbn == 7) {
      this.data.shisetsu_cd = "KB";
    } else if (this.data.shisetsu_kbn == 8) {
      this.data.shisetsu_cd = "KC";
    } else if (this.data.shisetsu_kbn == 9) {
      this.data.shisetsu_cd = "KD";
    } else if (this.data.shisetsu_kbn == 10) {
      this.data.shisetsu_cd = "KI";
    } else if (this.data.shisetsu_kbn == 11) {
      this.data.shisetsu_cd = "JH";
    } else if (this.data.shisetsu_kbn == 12) {
      this.data.shisetsu_cd = "SD";
    } else if (this.data.shisetsu_kbn == 13) {
      this.data.shisetsu_cd = "DT";
    } else if (this.data.shisetsu_kbn == 14) {
      this.data.shisetsu_cd = "TT";
    } else if (this.data.shisetsu_kbn == 15) {
      this.data.shisetsu_cd = "CK";
    } else if (this.data.shisetsu_kbn == 16) {
      this.data.shisetsu_cd = "SK";
    } else if (this.data.shisetsu_kbn == 17) {
      this.data.shisetsu_cd = "BH";
    } else if (this.data.shisetsu_kbn == 18) {
      this.data.shisetsu_cd = "DY";
    } else if (this.data.shisetsu_kbn == 19) {
      this.data.shisetsu_cd = "DN";
    } else if (this.data.shisetsu_kbn == 20) {
      this.data.shisetsu_cd = "TS";
    } else if (this.data.shisetsu_kbn == 21) {
      /*********************************/
      /*** ロードヒーティングは未決定だよ ***/
      /*********************************/
      this.data.shisetsu_cd = "RH";
    } else if (this.data.shisetsu_kbn == 22) {
      this.data.shisetsu_cd = "KK";
    } else if (this.data.shisetsu_kbn == 23) {
      this.data.shisetsu_cd = "TK";
    } else if (this.data.shisetsu_kbn == 24) {
      this.data.shisetsu_cd = "BR";
    } else if (this.data.shisetsu_kbn == 25) {
      this.data.shisetsu_cd = "TU";
    } else if (this.data.shisetsu_kbn == 26) {
      this.data.shisetsu_cd = "DK";
    } else if (this.data.shisetsu_kbn == 27) {
      this.data.shisetsu_cd = "HD";
    } else if (this.data.shisetsu_kbn == 28) {
      // カルテ点検の場合は何もセットしない
      this.data.shisetsu_cd = "";
    } else if (this.data.shisetsu_kbn == 29) {
      this.data.shisetsu_cd = "FB";
    } else if (this.data.shisetsu_kbn == 30) {
      this.data.shisetsu_cd = "SH";
    } else if (this.data.shisetsu_kbn == 31) {
      this.data.shisetsu_cd = "CL";
    } else if (this.data.shisetsu_kbn == 32) {
      // カルテ点検の場合は何もセットしない
      this.data.shisetsu_cd = "";
    } else if (this.data.shisetsu_kbn == 33) {
      // カルテ点検の場合は何もセットしない
      this.data.shisetsu_cd = "";
    } else if (this.data.shisetsu_kbn == 34) {
      this.data.shisetsu_cd = "DF";
    } else if (this.data.shisetsu_kbn == 35) {
      this.data.shisetsu_cd = "HM";
    } else if (this.data.shisetsu_kbn == 36) {
      this.data.shisetsu_cd = "JM";
    }
  }

  // 路線変更時マップ表示位置を変更する＝緯度経度が落ちている場合はメッセージ表示
  chgRosen() {
    // 緯度経度のチェック
    if (this.data.lat || this.data.lon || this.data.shityouson || this.data.azaban) {
      this.confirm('座標、所在地を破棄しますか？', 1).then(() => {
        this.data.lat = "";
        this.data.lon = "";
        this.data.lat_deg = "";
        this.data.lat_min = "";
        this.data.lat_sec = "";
        this.data.lon_deg = "";
        this.data.lon_min = "";
        this.data.lon_sec = "";
        this.data.shityouson = "";
        this.data.azaban = "";
      });
    }

  }

  // 60進数を10進数に変換
  chgCoordFrom60(deg, min, sec) {
    var coord = deg + (min / 60) + (sec / 60 / 60);
    return coord;
  }

  // 10進数を60進数に変換
  // コロン区切りにする
  chgCoordFrom10(coord10) {
    var deg = Math.floor(coord10);
    var min = Math.floor((coord10 - deg) * 60);
    var sec = Math.round(((coord10 - deg) * 60 - min) * 60 * 100000) / 100000;
    return deg + ":" + min + ":" + sec;
  }

  // モード変更
  chgMode() {

    /*
        // 29年度リリース対応
        // 基本情報が整うまで閲覧モードのみ
        this.alert('モード変更', '現在最新の施設情報をシステムに登録中のため、基本情報は閲覧のみとなります');
        return;
    */

    if (this.editable) {
      this.confirm('変更があった場合、内容が破棄されます。よろしいですか？', 0).then(() => {
        // メッセージ非表示
        //this.windowUnlock();
        //this.$window.location.reload();
        this.initLoad();
        this.editable = false;
      });
    } else {
      // disp_idx変化なし
      this.editable = true;
    }
  }

  back() {

    // 来た画面に戻る
    if (this.from == 1) {
      // 検索画面
      this.location("/fam_main/0/0/0/0");
    } else if (this.from == 2) {
      // 台帳登録
      this.location("/fam_edit/" + this.sno);
    } else if (this.from == 3) {
      // 台帳登録
      this.location("/sys_top/");
    }
  }

  location(link) {
    var message_flg;
    message_flg = this.idPhase == true ? false : this.editable;
    super.location(link, message_flg);
  }

}

let angModule = require('../app.js');
angModule.angApp.controller('ShisetsuEditCtrl', ['$scope', '$filter', '$http', '$uibModal', '$routeParams', '$location', '$q', '$window', function ($scope, $filter, $http, $uibModal, $routeParams, $location, $q, $window) {
  return new ShisetsuEditCtrl($scope, $filter, $http, $uibModal, $routeParams, $location, $q, $window);
}]);
