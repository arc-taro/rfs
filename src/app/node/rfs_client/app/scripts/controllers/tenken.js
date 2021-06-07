'use strict';

var BaseCtrl = require("./base.js");
/**
 * @ngdoc function
 * @name rfsApp.controller:TenkenCtrl
 * @description
 * # TenkenCtrl
 * Controller of the rfsApp
 */
class TenkenCtrl extends BaseCtrl {
  constructor($scope, $http, $route, $routeParams, $location, $anchorScroll, $interval, $window, $uibModal, $q, $cookies) {
    super({
      $location: $location,
      $scope: $scope,
      $http: $http,
      $q: $q,
      messageBox: true
    });

    this.$location = $location;
    this.$anchorScroll = $anchorScroll;
    this.$window = $window;
    this.$http = $http;
    this.$uibModal = $uibModal;
    this.$scope = $scope;
    this.$q = $q;
    this.$cookies = $cookies;
    this.$route = $route;

    this.radioModel = '0';
    //this.change_flag = false;
    this.sno = $routeParams.sno;
    this.struct_idx = $routeParams.struct_idx;
    this.chk_mng_no = $routeParams.chk_mng_no;

    //this.unwatch = {};

    // クッキーから取得
    this.excel_ver = this.$cookies.excel_ver;
    if (this.excel_ver == 'true') {
      this.excel_ver = true;
    } else {
      this.excel_ver = false;
    }

    this.gdh_url = localStorage.getItem("RTN_URL");

    // 措置の状態
    this.sochi_cnt = 0;
    this.sochi_end_cnt = 0;

    this.current_phase = 1; // 確定保存時のDB保存前のフェーズ
    this.genba_syashin_select = null;
    this.btnIndex = 0;
    this.editable = false;
    this.deletable = false; // true:削除ボタン表示／false:削除ボタン非表示
    this.remandable = false; // true:差戻しボタン表示／false:差戻しボタン非表示
    this.is_new = true;
    this.maxInspection = 1;
    this.minInspection = 1;
    this.currentInspection = 0;
    this.isValidPast = false;
    this.candidate = {};
    this.waitOverlay = false; // 保存中です
    this.waitLoadOverlay = false; // 読込み中です
    this.waitDeleteOverlay = false; // 削除中です
    this.waitRemandOverlay = false; // 差戻し中です
    this.picture = {
      data: []
    };
    this.sankou_photos = [];
    this.zenkei_picture = {
      data: []
    };
    this.judges = ['－', '－', '－', '－', '－', '－'];

    this.rating = [
      "未", " a ", " c ", " e "
    ];

    this.hoshi = [
      "未", " Ⅰ ", " Ⅱ ", " Ⅲ ", " Ⅳ "
    ];

    // タイトル表示用
    this.sonsyou_titles = [
      " a ", " c ", " e "
    ];

    // タイトル表示用
    this.judge_titles = [
      " Ⅰ ", " Ⅱ ", " Ⅲ ", " Ⅳ "
    ];

    this.houshin = {
      "houshins": [
        {
          "houshin_cd": "0",
          "houshin": "－"
        },
        {
          "houshin_cd": "1",
          "houshin": "スクリーニング"
        },
        {
          "houshin_cd": "2",
          "houshin": "詳細調査"
        },
        {
          "houshin_cd": "3",
          "houshin": "詳細調査済"
        },
        {
          "houshin_cd": "4",
          "houshin": "スクリーニング済"
        },
      ]
    };

    // 最新の点検票を取得する（≒入力対象）
    this.loadLastChkData();

    $('[data-toggle="tooltip"]').tooltip();
  }

  // 最新の点検票を取得する（≒入力対象）
  loadLastChkData() {

    this.waitLoadOverlay = true;

    // ログイン情報
    // セッションから建管と出張所コードを取得
    super.start(this.$http).then(() => {

      // session情報を保持
      this.syozoku_cd = this.session.ath.syozoku_cd;
      this.busyo_cd = this.session.ath.busyo_cd;
      this.create_account = this.session.ath.account_cd;

      // 施設検索の条件がsessionにある場合は、施設管理の方へ戻るため記憶する。
      this.back = {};
      if (this.session.srch_shisetsu) {
        // 施設台帳から
        this.back.id = 2;
        this.back.title = "道路施設台帳画面";
        this.back.url = "/fam_edit/" + this.sno;
      } else {
        // 附属物点検検索から
        this.back.id = 1;
        this.back.title = "道路附属物点検検索画面";
        this.back.url = '/main';
      }

      // 削除ボタンの表示はkanrisyaユーザーのみ
      if (this.create_account == 9999999) {
        this.deletable = true;
      }
      // 差戻しボタンの表示は出張所権限以上かkanrisyaのみ
      if (this.syozoku_cd < 4 || this.create_account == 9999999) {
        this.remandable = true;
      }

      // 基本情報と部材情報を取得
      return this.$http({
        method: 'GET',
        url: 'api/index.php/CheckListSchAjax/get_kihon_chkmng_buzai',
        params: {
          chk_mng_no: this.chk_mng_no,
          sno: this.sno,
          struct_idx: this.struct_idx
        }
      });


      /*
            // 基本情報の取得
            return this.$http({
              method: 'GET',
              url: 'api/index.php/CheckListSchAjax/get_baseinfo_by_chkmngno',
              params: {
                chk_mng_no: this.chk_mng_no,
                sno: this.sno,
                struct_idx: this.struct_idx
              }
            });
      
          }).then((data) => {
            this.baseinfo = data.data[0];
            console.log(this.baseinfo);
      
            this.dogen_cd = this.baseinfo.dogen_cd;
            this.syucchoujo_cd = this.baseinfo.syucchoujo_cd;
      */
      /*
            if ((this.baseinfo.shisetsu_kbn_nm != null) && (this.baseinfo.shisetsu_kbn_nm == '防雪柵')) {
              this.isBssk = true;
            } else {
              this.isBssk = false;
            }
      */
      /*
            // 部材以下情報の取得
            if (this.baseinfo.chk_times == 0) {
              return this.$http({
                method: 'GET',
                url: 'api/index.php/CheckListSchAjax/get_chkdata_exist_only',
                params: {
                  chk_mng_no: this.baseinfo.chk_mng_no,
                  rireki_no: this.baseinfo.rireki_no,
                  shisetsu_kbn: this.baseinfo.shisetsu_kbn
                }
              });
            } else {
              return this.$http({
                method: 'GET',
                url: 'api/index.php/CheckListSchAjax/get_chkdata',
                params: {
                  chk_mng_no: this.baseinfo.chk_mng_no,
                  rireki_no: this.baseinfo.rireki_no,
                  shisetsu_kbn: this.baseinfo.shisetsu_kbn
                }
              });
            }
      */
    }).then((data) => {
      console.log(data);

      this.baseinfo = data.data.kihon_chkmng[0];
      this.dogen_cd = this.baseinfo.dogen_cd;
      this.syucchoujo_cd = this.baseinfo.syucchoujo_cd;
      this.tenken_kasyo = JSON.parse(data.data.buzai_row[0].buzai_row);
      //this.tenken_kasyo = JSON.parse(data.data[0].buzai_row);
      console.log(this.tenken_kasyo);
      if (data.data.buzai_row[0].is_new == 'false') {
        this.is_new = false;
      }
      //      if (data.data[0].is_new == 'false') {
      //        this.is_new = false;
      //      }
      console.log(this.is_new);

      // 新規作成の場合、点検箇所：その他を対象としない
      if (this.is_new) {
        this.setOtherTaisyouUmu();
      }

      //            console.log('imageShow');
      //            console.time("timer_imageShow");
      //            return this.imageShow();
      //
      //        }).then((data) => {
      //            console.timeEnd("timer_imageShow");
      /*       console.log('get_patrolin_investigator');
            console.time("timer");
      
            console.timeEnd("timer");
       */
      this.patrolins = JSON.parse(data.data.patrolin_investigator[0].jsonb_set);
      this.investigators = JSON.parse(data.data.patrolin_investigator[1].investigator_row);
      //      this.patrolins = JSON.parse(data.data[0].jsonb_set);
      //      this.investigators = JSON.parse(data.data[1].investigator_row);
      //console.log(this.patrolins);
      //console.log(this.investigators);

      // 点検員リストの初期表示
      if (this.patrolins) {
        for (var i = 0; Object.keys(this.patrolins.patrolins).length > i; i++) {
          if ((this.patrolins.patrolins[i].simei == this.baseinfo.chk_person) &&
            (this.patrolins.patrolins[i].company == this.baseinfo.chk_company)) {
            this.patrolin = this.patrolins.patrolins[i];
            break;
          }
        }
      }

      // 調査員リストの初期表示
      if (this.investigators) {
        for (var i = 0; Object.keys(this.investigators.investigator_info).length > i; i++) {
          if ((this.investigators.investigator_info[i].simei == this.baseinfo.investigate_person) &&
            (this.investigators.investigator_info[i].company == this.baseinfo.investigate_company)) {
            this.investigater = this.investigators.investigator_info[i];
            break;
          }
        }
      }

      // 形式のセット
      this.keishikis = JSON.parse(data.data.keishiki[0].keishiki_row);
      this.shisetsujudges = JSON.parse(data.data.shisetsu_judge[0].measures_judge_row);
      console.log("shisetsujudges");
      console.log(this.shisetsujudges);
      // 措置対象、措置完了件数の設定
      this.setMeasuresCnt();

      this.chkmngnos = JSON.parse(data.data.chkmngnos[0].chkmngnos);
      console.log(this.chkmngnos);

      // 点検管理番号(chk_mng_no)配列の最大数、現在の表示インデックスを設定
      this.maxInspection = Object.keys(this.chkmngnos.chkmngnos).length;
      this.currentInspection = this.maxInspection - 1;

      // 過去の点検履歴から表示された場合、現在の表示インデックスを変更
      var _chk_mng_no = this.chk_mng_no;
      var _chkmngnos = this.chkmngnos.chkmngnos;
      for (var i = 0; Object.keys(_chkmngnos).length > i; i++) {
        if (_chkmngnos[i].chk_mng_no == Number(_chk_mng_no)) {
          this.currentInspection = i;
        }
      }

      // 最新の点検票かつ過去の点検票がある場合
      if ((this.currentInspection + 1 === this.maxInspection) && (Number(this.maxInspection) > 1)) {
        this.isValidPast = true;
      }

      if (this.isValidPast) {
        // 基本情報の取得（最新の点検票の1つ前）
        return this.$http({
          method: 'GET',
          url: 'api/index.php/CheckListSchAjax/get_baseinfo_by_chkmngno',
          params: {
            chk_mng_no: this.chkmngnos.chkmngnos[this.currentInspection - 1].chk_mng_no,
            sno: this.sno,
            struct_idx: this.struct_idx
          }
        });
      }

      // 点検管理番号の取得
      /*      return this.$http({
              method: 'GET',
              url: 'api/index.php/CheckListSchAjax/get_chkmngnos',
              params: {
                sno: this.sno,
                struct_idx: this.struct_idx
              }
            });
      */
      /*      // 施設の健全性の取得
            return this.$http({
              method: 'GET',
              url: 'api/index.php/CheckListSchAjax/get_shisetsu_judge',
              params: {
                sno: this.sno,
                chk_mng_no: this.baseinfo.chk_mng_no
              }
            });
      */
      /*
            // 形式の取得
            return this.$http({
              method: 'GET',
              url: 'api/index.php/CheckListSchAjax/get_keishiki',
              params: {
                shisetsu_kbn: this.baseinfo.shisetsu_kbn
              }
            });
            */
      /*
            // パトロール員、調査員一覧の取得
            return this.$http({
              method: 'GET',
              url: 'api/index.php/CheckListSchAjax/get_patrolin_investigator',
              params: {
                syozoku_cd: this.syozoku_cd,
                syucchoujo_cd: this.syucchoujo_cd,
                busyo_cd: this.busyo_cd
              }
            });
      
          }).then((data) => {
      
             console.timeEnd("timer");
      
            this.patrolins = JSON.parse(data.data[0].jsonb_set);
            this.investigators = JSON.parse(data.data[1].investigator_row);
            console.log(this.patrolins);
            console.log(this.investigators);
      
            // 点検員リストの初期表示
            if (this.patrolins) {
              for (var i = 0; Object.keys(this.patrolins.patrolins).length > i; i++) {
                if ((this.patrolins.patrolins[i].simei == this.baseinfo.chk_person) &&
                  (this.patrolins.patrolins[i].company == this.baseinfo.chk_company)) {
                  this.patrolin = this.patrolins.patrolins[i];
                  break;
                }
              }
            }
      
            // 調査員リストの初期表示
            if (this.investigators) {
              for (var i = 0; Object.keys(this.investigators.investigator_info).length > i; i++) {
                if ((this.investigators.investigator_info[i].simei == this.baseinfo.investigate_person) &&
                  (this.investigators.investigator_info[i].company == this.baseinfo.investigate_company)) {
                  this.investigater = this.investigators.investigator_info[i];
                  break;
                }
              }
            }
      
            console.time("timer");
      
            // 形式の取得
            return this.$http({
              method: 'GET',
              url: 'api/index.php/CheckListSchAjax/get_keishiki',
              params: {
                shisetsu_kbn: this.baseinfo.shisetsu_kbn
              }
            });
       */
      //    }).then((data) => {
      //      console.timeEnd("timer");
      //      console.debug(data);


      //    }).then((data) => {
      /*       console.timeEnd("timer");
            this.shisetsujudges = JSON.parse(data.data[0].measures_judge_row);
            console.log(this.shisetsujudges);
      
            // 措置対象、措置完了件数の設定
            this.setMeasuresCnt();
      
            console.log('get_chkmngnos');
            console.time("timer");
      
            // 点検管理番号の取得
            return this.$http({
              method: 'GET',
              url: 'api/index.php/CheckListSchAjax/get_chkmngnos',
              params: {
                sno: this.sno,
                struct_idx: this.struct_idx
              }
            });
       */
      /*    }).then((data) => {
            console.timeEnd("timer");
            this.chkmngnos = JSON.parse(data.data[0].chkmngnos);
            console.log(this.chkmngnos);
      
            // 点検管理番号(chk_mng_no)配列の最大数、現在の表示インデックスを設定
            this.maxInspection = Object.keys(this.chkmngnos.chkmngnos).length;
            this.currentInspection = this.maxInspection - 1;
      
            // 過去の点検履歴から表示された場合、現在の表示インデックスを変更
            var _chk_mng_no = this.chk_mng_no;
            var _chkmngnos = this.chkmngnos.chkmngnos;
            for (var i = 0; Object.keys(_chkmngnos).length > i; i++) {
              if (_chkmngnos[i].chk_mng_no == Number(_chk_mng_no)) {
                this.currentInspection = i;
              }
            }
      
            // 最新の点検票かつ過去の点検票がある場合
            if ((this.currentInspection + 1 === this.maxInspection) && (Number(this.maxInspection) > 1)) {
              this.isValidPast = true;
            }
      
            if (this.isValidPast) {
              // 基本情報の取得（最新の点検票の1つ前）
              return this.$http({
                method: 'GET',
                url: 'api/index.php/CheckListSchAjax/get_baseinfo_by_chkmngno',
                params: {
                  chk_mng_no: this.chkmngnos.chkmngnos[this.currentInspection - 1].chk_mng_no,
                  sno: this.sno,
                  struct_idx: this.struct_idx
                }
              });
            }
      */
    }).then((data) => {
      console.log(data);

      if (this.isValidPast) {
        if (data.data[0] != null) {
          this.baseinfo_past = data.data[0];

          // 入力候補を設定する
          this.setCandidate(this.baseinfo_past, this.tenken_kasyo_past);

          // 部材以下情報の取得（過去履歴がある場合）
          return this.$http({
            method: 'GET',
            url: 'api/index.php/CheckListSchAjax/get_chkdata',
            params: {
              chk_mng_no: this.baseinfo_past.chk_mng_no,
              rireki_no: this.baseinfo_past.rireki_no,
              shisetsu_kbn: this.baseinfo_past.shisetsu_kbn
            }
          });
        }
      }

    }).then((data) => {
      if (this.isValidPast) {
        if (data.data[0].buzai_row != null) {
          this.tenken_kasyo_past = JSON.parse(data.data[0].buzai_row);
          console.log(this.tenken_kasyo_past);

          // 入力候補を設定する
          this.setTenkenKasyoPast(this.tenken_kasyo_past);
        }
      }

      // 防雪柵
      //if (this.isBssk) {
      if (this.baseinfo.shisetsu_kbn == 4) {
        if (this.currentInspection + 1 == this.maxInspection) {
          this.getBsskMngInfo();
        }
      }

    }).then((data) => {
      console.log('imageShow');
      console.time("timer_imageShow");

      this.waitLoadOverlay = false;

      return this.imageShow();

    }).finally((data) => {
      console.timeEnd("timer_imageShow");

      // 20161108 hirano 一時コメント化
      // ログインユーザが一致する場合、削除ボタンを表示する
      /*
            if (this.baseinfo.create_account == this.create_account) {
              this.deletable = true;
            }
      */

      // 点検票保存時のユーザを設定
      this.baseinfo.create_account = this.create_account;

      // 附属物テーブルにデータが無い（＝ぶら下がりが無い場合）編集モードで表示する
      // →実機確認により無条件で閲覧モードとする
      //            if(this.baseinfo.is_new_data === 'true') {
      //                this.editable = true;
      //            }

      // 健全性の再計算
      this.updateBuzaiJudge();

      /*
            this.unwatch[0] = this.$scope.$watch(
              angular.bind(this, function () {
                return this.baseinfo;
              }),
              angular.bind(this, function (value) {
                this.change_flag = true;
              }),
              true
            );

            this.unwatch[1] = this.$scope.$watch(
              angular.bind(this, function () {
                return this.tenken_kasyo;
              }),
              angular.bind(this, function (value) {
                this.change_flag = true;
              }),
              true
            );

            this.unwatch[2] = this.$scope.$watch(
              angular.bind(this, function () {
                return this.patrolins;
              }),
              angular.bind(this, function (value) {
                this.change_flag = true;
              }),
              true
            );

            this.unwatch[3] = this.$scope.$watch(
              angular.bind(this, function () {
                return this.picture.data;
              }),
              angular.bind(this, function (value) {
                this.change_flag = true;
              }),
              true
            );

            this.unwatch[4] = this.$scope.$watch(
              angular.bind(this, function () {
                return this.zenkei_picture.data;
              }),
              angular.bind(this, function (value) {
                this.change_flag = true;
              }),
              true
            );

            setTimeout(() => {
              this.change_flag = false;
            }, 10);
      */

      this.waitLoadOverlay = false;
    });
  }

  // 新規作成の場合、点検箇所：その他を対象としない
  // 新規の場合しか呼ばれないようにした
  setOtherTaisyouUmu() {
    var _buzai = this.tenken_kasyo.buzai;
    var _buzai_detail_row;
    var _tenken_kasyo_row;

    for (var i = 0; Object.keys(_buzai).length > i; i++) {
      _buzai_detail_row = _buzai[i].buzai_detail_row;

      for (var j = 0; Object.keys(_buzai_detail_row).length > j; j++) {
        _tenken_kasyo_row = _buzai_detail_row[j].tenken_kasyo_row;

        for (var k = 0; Object.keys(_tenken_kasyo_row).length > k; k++) {
          if (_tenken_kasyo_row[k].tenken_kasyo_nm !== 'その他') {
            continue;
          }
          //if (this.is_new) {
          _tenken_kasyo_row[k].taisyou_umu = false;
          //}
        }
      }
    }
  }

  setCandidate(_baseinfo_past) {

    // 過去の点検票が無い場合
    if (2 > this.maxInspection) {
      return;
    }

    //        var historyNum = Object.keys(_baseinfo_past).length;
    var _chk_person = [];
    var _investigate_person = [];
    var _syoken = [];
    var _part_notable_chk = [];
    var _reason_notable_chk = [];
    var _special_report = [];

    //        for (var i = 0; historyNum > i; i++) {
    if (this.isValidObject(_baseinfo_past.chk_person)) {
      if (!this.isExist(_chk_person, _baseinfo_past.chk_person)) {
        _chk_person.push(_baseinfo_past.chk_person);
      }
    }
    if (this.isValidObject(_baseinfo_past.investigate_person)) {
      if (!this.isExist(_investigate_person, _baseinfo_past.investigate_person)) {
        _investigate_person.push(_baseinfo_past.investigate_person);
      }
    }
    if (this.isValidObject(_baseinfo_past.syoken)) {
      if (!this.isExist(_syoken, _baseinfo_past.syoken)) {
        _syoken.push(_baseinfo_past.syoken);
      }
    }
    if (this.isValidObject(_baseinfo_past.part_notable_chk)) {
      if (!this.isExist(_part_notable_chk, _baseinfo_past.part_notable_chk)) {
        _part_notable_chk.push(_baseinfo_past.part_notable_chk);
      }
    }
    if (this.isValidObject(_baseinfo_past.reason_notable_chk)) {
      if (!this.isExist(_reason_notable_chk, _baseinfo_past.reason_notable_chk)) {
        _reason_notable_chk.push(_baseinfo_past.reason_notable_chk);
      }
    }
    if (this.isValidObject(_baseinfo_past.special_report)) {
      if (!this.isExist(_special_report, _baseinfo_past.special_report)) {
        _special_report.push(_baseinfo_past.special_report);
      }
    }
    //        }

    this.candidate.chk_person = _chk_person;
    this.candidate.investigate_person = _investigate_person;
    this.candidate.syoken = _syoken;
    this.candidate.part_notable_chk = _part_notable_chk;
    this.candidate.reason_notable_chk = _reason_notable_chk;
    this.candidate.special_report = _special_report;
  }

  setTenkenKasyoPast(_tenken_kasyo_past) {

    //
    var _buzai = this.tenken_kasyo.buzai;
    var _buzai_detail_row;
    var _tenken_kasyo_row;
    var _buzai_past = _tenken_kasyo_past.buzai;
    var _buzai_detail_row_past;
    var _tenken_kasyo_row_past;

    for (var i = 0; Object.keys(_buzai_past).length > i; i++) {
      if (this.isValidObject(_buzai_past[i].hantei1)) {
        _buzai[i].hantei1_past = [_buzai_past[i].hantei1];
      }
      if (this.isValidObject(_buzai_past[i].hantei2)) {
        _buzai[i].hantei2_past = [_buzai_past[i].hantei2];
      }
      if (this.isValidObject(_buzai_past[i].hantei3)) {
        _buzai[i].hantei3_past = [_buzai_past[i].hantei3];
      }
      if (this.isValidObject(_buzai_past[i].hantei4)) {
        _buzai[i].hantei4_past = [_buzai_past[i].hantei4];
      }

      _buzai_detail_row = _buzai[i].buzai_detail_row;
      _buzai_detail_row_past = _buzai_past[i].buzai_detail_row;

      for (var j = 0; Object.keys(_buzai_detail_row_past).length > j; j++) {
        _tenken_kasyo_row = _buzai_detail_row[j].tenken_kasyo_row;
        _tenken_kasyo_row_past = _buzai_detail_row_past[j].tenken_kasyo_row;

        for (var k = 0; Object.keys(_tenken_kasyo_row_past).length > k; k++) {
          //                    _tenken_kasyo_row[k].check_policy_str_past = _tenken_kasyo_row_past[k].check_policy_str;

          if (this.isValidObject(_tenken_kasyo_row_past[k].measures_policy)) {
            _tenken_kasyo_row[k].measures_policy_past = [_tenken_kasyo_row_past[k].measures_policy];
          }
          if (this.isValidObject(_tenken_kasyo_row_past[k].check_bikou)) {
            _tenken_kasyo_row[k].check_bikou_past = [_tenken_kasyo_row_past[k].check_bikou];
          }
          if (this.isValidObject(_tenken_kasyo_row_past[k].measures_bikou)) {
            _tenken_kasyo_row[k].measures_bikou_past = [_tenken_kasyo_row_past[k].measures_bikou];
          }
        }
      }
    }
  }

  isValidObject(object) {
    if ((object !== null) && (object !== '')) {
      return true;
    } else {
      return false;
    }
  }

  isExist(array, data) {
    if (array.indexOf(data) >= 0) {
      return true;
    } else {
      return false;
    }
  }

  // 防雪柵用管理情報の取得
  // (防雪柵親データの基本情報、部材以下情報含む)
  getBsskMngInfo() {

    // 防雪柵支柱インデックス数の取得
    this.$http({
      method: 'GET',
      url: 'api/index.php/CheckListSchAjax/get_struct_idx_num',
      params: {
        sno: this.sno
      }

    }).then((data) => {
      this.struct_idx_num = data.data[0].struct_idx_num;
      console.log(this.struct_idx_num);

      // 防雪柵管理情報の取得
      return this.$http({
        method: 'GET',
        url: 'api/index.php/CheckListSchAjax/get_bousetsusaku_mng_info',
        params: {
          sno: this.sno
        }
      });

    }).then((data) => {
      console.log(data);

      if (data.data[0].bssk_parent_row != null) {
        this.bssk_mng_info = JSON.parse(data.data[0].bssk_parent_row).bssk_parent;
      } else {
        return this.addBsskMngInfo();
      }

    }).then((data) => {

      // 防雪柵親の基本情報の取得
      // (親データなのでstruct_idxは0固定)
      if (this.bssk_mng_info != null) {
        return this.$http({
          method: 'GET',
          url: 'api/index.php/CheckListSchAjax/get_baseinfo_by_chkmngno',
          params: {
            chk_mng_no: this.bssk_mng_info[0].chk_mng_no,
            sno: this.sno,
            struct_idx: 0
          }
        });
      }

    }).then((data) => {
      console.log(data);

      if (this.bssk_mng_info != null) {
        this.baseinfo_parent = data.data[0];

        // 防雪柵親の部材以下情報の取得
        return this.$http({
          method: 'GET',
          url: 'api/index.php/CheckListSchAjax/get_chkdata',
          params: {
            chk_mng_no: this.baseinfo_parent.chk_mng_no,
            rireki_no: this.baseinfo_parent.rireki_no,
            shisetsu_kbn: this.baseinfo_parent.shisetsu_kbn
          }
        });
      }

    }).then((data) => {
      console.log(data);
      if (this.bssk_mng_info != null) {
        this.tenken_kasyo_parent = JSON.parse(data.data[0].buzai_row);
      }

    });
  }

  // 防雪柵管理情報テーブルの追加
  addBsskMngInfo() {

    // 防雪柵親データの検索
    return this.$http({
      method: 'GET',
      url: 'api/index.php/CheckListSchAjax/get_chkmngno_bssk_parent',
      params: {
        sno: this.sno,
      }

    }).then((data) => {
      console.log(data);

      if (data.data[0] != null) {
        this.last_chk_mng_no = data.data[0].chk_mng_no;
      } else {
        // 防雪柵親データが無ければ、t_chk_mainに追加する
        return this.$http({
          method: 'POST',
          url: 'api/index.php/CheckListEdtAjax/add_chkdatamain',
          data: {
            sno: this.baseinfo.sno,
            create_account: this.baseinfo.create_account
          }
        });
      }

    }).then((data) => {
      if (this.last_chk_mng_no == null) {
        this.last_chk_mng_no = data.data[0].chk_mng_no;
      }
      console.log(this.last_chk_mng_no);

      // 防雪柵管理情報の追加
      return this.$http({
        method: 'POST',
        url: 'api/index.php/CheckListEdtAjax/add_bousetsusaku_mng_info',
        data: {
          chk_mng_no: this.last_chk_mng_no,
          chk_mng_no_struct: this.chk_mng_no
        }
      });

    }).then((data) => {

      // 防雪柵管理情報の取得
      return this.$http({
        method: 'GET',
        url: 'api/index.php/CheckListSchAjax/get_bousetsusaku_mng_info',
        params: {
          sno: this.sno
        }
      });

    }).then((data) => {
      if (data.data[0].bssk_parent_row != null) {
        this.bssk_mng_info = JSON.parse(data.data[0].bssk_parent_row).bssk_parent;
        //        console.log(this.bssk_mng_info);
      }

    });
  }

  // 健全性の集計
  // 集計は、点検時と措置後の両方
  // thisに含まれるjsonデータの部材と施設の健全性を集計する。
  // 呼ばれるタイミングは、部材が変更された場合（index書き換え前）
  // ※初期表示時は、集計結果が保存されているので、再計算無し
  sum_judge() {
    // 部材の健全性を確定
    this.sum_judge_buzai();
    // 施設の健全性を確定
    this.sum_judge_shisetsu();
  }

  // 健全性の集計
  // 集計は、点検時と措置後の両方
  // thisに含まれるjsonデータの部材の健全性を集計する。
  // 呼ばれるタイミングは、部材が変更された場合（index書き換え前）
  // ※※※初期表示時は、集計結果が保存されているので、再計算無し
  sum_judge_buzai() {
    var check_judge_tmp = 0; // 点検時の健全性
    var now_tenkenkasyo_judge_tmp = 0; // 点検箇所としての健全性
    var now_shisetsu_judge_tmp = 0; // 施設としての健全性

    // 部材はタブ変更前の部材
    // 部材詳細ループ
    this.tenken_kasyo.buzai[this.btnIndex].buzai_detail_row.forEach(function (val_buzai_detail, idx_buzai_detail, buzai_detail) {
      // 点検箇所ループ
      buzai_detail[idx_buzai_detail].tenken_kasyo_row.forEach(function (val_tenken_kasyo, idx_tenken_kasyo, tenken_kasyo) {

        if (tenken_kasyo[idx_tenken_kasyo].taisyou_umu) {

          if (check_judge_tmp < tenken_kasyo[idx_tenken_kasyo].check_judge) {
            check_judge_tmp = tenken_kasyo[idx_tenken_kasyo].check_judge;
          }
          // 措置後は措置後のみを集計するわけではなく
          // 点検箇所としての健全性を出す。
          if (tenken_kasyo[idx_tenken_kasyo].measures_judge >= 1 && tenken_kasyo[idx_tenken_kasyo].measures_judge <= 4) {
            // 措置の入力がある場合は措置後を現在の健全性とする
            now_tenkenkasyo_judge_tmp = tenken_kasyo[idx_tenken_kasyo].measures_judge;
          } else {
            // 措置入力が無い場合は点検時を現在の健全性とする
            now_tenkenkasyo_judge_tmp = tenken_kasyo[idx_tenken_kasyo].check_judge;
          }

          // 施設としての健全性を決定（1番悪いものをセット）
          if (now_shisetsu_judge_tmp < now_tenkenkasyo_judge_tmp) {
            now_shisetsu_judge_tmp = now_tenkenkasyo_judge_tmp;
          }
        }
      });
    });
    // 一番悪い健全性を部材の健全性とする
    this.tenken_kasyo.buzai[this.btnIndex].check_buzai_judge = check_judge_tmp;
    this.tenken_kasyo.buzai[this.btnIndex].measures_buzai_judge = now_shisetsu_judge_tmp;
  }

  // 健全性の集計
  // 集計は、点検時と措置後の両方
  // thisに含まれるjsonデータの施設の健全性を集計する。
  // 呼ばれるタイミングは、部材が変更された場合（index書き換え前）
  // ※※※部材集計後に呼び出すこと
  // ※※※初期表示時は、集計結果が保存されているので、再計算無し
  sum_judge_shisetsu() {
    // 附属物の健全性を集計
    var check_buzai_judge_tmp = 0;
    var measures_buzai_judge_tmp = 0;
    // 部材ループ
    this.tenken_kasyo.buzai.forEach(function (val_buzai, idx_buzai, buzai) {
      if (check_buzai_judge_tmp < buzai[idx_buzai].check_buzai_judge) {
        check_buzai_judge_tmp = buzai[idx_buzai].check_buzai_judge;
      }
      if (measures_buzai_judge_tmp < buzai[idx_buzai].measures_buzai_judge) {
        measures_buzai_judge_tmp = buzai[idx_buzai].measures_buzai_judge;
      }
    });
    // 一番悪い健全性を部材の健全性とする
    this.baseinfo.check_shisetsu_judge = check_buzai_judge_tmp;
    this.baseinfo.measures_shisetsu_judge = measures_buzai_judge_tmp;
  }

  // 措置対象／措置完了件数の計算
  setMeasuresCnt() {

    // 措置件数
    this.sochi_end_cnt = 0;
    // 措置完了件数
    this.sochi_cnt = 0;

    // 点検時の健全性Ⅲ、Ⅳの件数を取得する
    var _buzai = this.tenken_kasyo.buzai;
    var _buzai_detail_row;
    var _tenken_kasyo_row;

    for (var i = 0; Object.keys(_buzai).length > i; i++) {
      _buzai_detail_row = _buzai[i].buzai_detail_row;

      for (var j = 0; Object.keys(_buzai_detail_row).length > j; j++) {
        _tenken_kasyo_row = _buzai_detail_row[j].tenken_kasyo_row;

        for (var k = 0; Object.keys(_tenken_kasyo_row).length > k; k++) {
          if (Number(_tenken_kasyo_row[k].check_judge) >= 3) {
            this.sochi_cnt++;
            if ((Number(_tenken_kasyo_row[k].measures_judge) == 1) || (Number(_tenken_kasyo_row[k].measures_judge) == 2)) {
              this.sochi_end_cnt++;
            }
          }
        }
      }
    }
  }

  // 健全性がⅢかつ措置後の健全性が無い点検箇所を検索
  getWorstJudge() {

    var _worstJudge = 0;
    var _currentJudge = 0;

    var _buzai = this.tenken_kasyo.buzai;
    var _buzai_detail_row;
    var _tenken_kasyo_row;

    for (var i = 0; Object.keys(_buzai).length > i; i++) {
      _buzai_detail_row = _buzai[i].buzai_detail_row;

      for (var j = 0; Object.keys(_buzai_detail_row).length > j; j++) {
        _tenken_kasyo_row = _buzai_detail_row[j].tenken_kasyo_row;

        for (var k = 0; Object.keys(_tenken_kasyo_row).length > k; k++) {

          // 健全性Ⅳは無視（Ⅰ、Ⅱ、Ⅲのいずれかを取得）
          if ((Number(_tenken_kasyo_row[k].check_judge) == 0) || (Number(_tenken_kasyo_row[k].check_judge) == 4)) {
            continue;
          }
          _currentJudge = Number(_tenken_kasyo_row[k].check_judge);

          if (Number(_tenken_kasyo_row[k].check_judge) == 3) {
            if ((Number(_tenken_kasyo_row[k].measures_judge) == 1) || (Number(_tenken_kasyo_row[k].measures_judge) == 2)) {
              _currentJudge = Number(_tenken_kasyo_row[k].measures_judge);
            }
          }

          // 健全性が悪い場合
          if (_currentJudge > _worstJudge) {
            _worstJudge = _currentJudge;
          }
        }
      }
    }

    return _worstJudge;
  }

  // 点検時の健全性がⅢの点検箇所をスクリーニング対象とする
  setScreeningTaisyou() {

    // スクリーニング対象の設定は点検フェーズのみ
    if (this.baseinfo.phase != 1) {
      return;
    }

    var _buzai = this.tenken_kasyo.buzai;
    var _buzai_detail_row;
    var _tenken_kasyo_row;

    for (var i = 0; Object.keys(_buzai).length > i; i++) {
      _buzai_detail_row = _buzai[i].buzai_detail_row;

      for (var j = 0; Object.keys(_buzai_detail_row).length > j; j++) {
        _tenken_kasyo_row = _buzai_detail_row[j].tenken_kasyo_row;

        for (var k = 0; Object.keys(_tenken_kasyo_row).length > k; k++) {

          // 健全性Ⅳは無視（Ⅰ、Ⅱ、Ⅲのいずれかを取得）
          if ((Number(_tenken_kasyo_row[k].check_judge) == 0) || (Number(_tenken_kasyo_row[k].check_judge) == 4)) {
            continue;
          }

          // 措置未実施で健全性がⅢの場合、スクリーニング対象をセット
          if ((Number(_tenken_kasyo_row[k].check_judge) == 3) && (Number(_tenken_kasyo_row[k].measures_judge) == 0)) {
            _tenken_kasyo_row[k].screening_taisyou = 1;
          } else {
            _tenken_kasyo_row[k].screening_taisyou = 0;
          }
        }
      }
    }
  }

  /* 編集モードボタン */
  ClickEditableFunction() {
    // 編集モードの場合
    if (this.editable) {
      this.confirm('変更があった場合無視されますがよろしいですか？', 0).then(() => {
        this.editable = !this.editable;
        this.windowUnlock();
        this.$window.location.reload(false);
      });
    } else {
      this.editable = !this.editable;
    }
    /*
        if (this.change_flag && this.editable) {
          this.confirm('変更を破棄してよろしいですか？', 0).then(() => {
            this.editable = !this.editable;
            this.change_flag = false;
            this.windowUnlock();
            this.$window.location.reload(false);
          });
        } else {
          this.editable = !this.editable;
          this.change_flag = false;
        }
    */
  }

  /* 部材切替ボタン */
  ClickBuzaiChangeFunction(index) {
    this.btnIndex = index;
  }

  /* 一時保存ボタン */
  ClickTmpSaveFunction() {

    if (!this.checkImageData()) {
      return;
    }
    // 健全性の再計算
    this.updateBuzaiJudge();

    // 点検箇所の健全性をコピー
    this.copyTenkenKasyoCheckJudge();

    // 措置対象、措置完了件数の設定
    this.setMeasuresCnt();

    this.setCheckStatus(true);

    this.waitOverlay = true;

    // パトロール員名の既存チェック、更新
    this.add_non_existent_patrolin();

    // 調査員名の既存チェック、更新
    this.add_non_existent_investigator();

    // 基本情報、部材以下情報の更新
    // ※mode未使用
    this.$http({
      method: 'POST',
      url: 'api/index.php/CheckListEdtAjax/set_chkdata',
      data: {
        baseinfo: this.baseinfo,
        buzaidata: this.tenken_kasyo,
        mode: 'update'
      }

    }).then((data) => {
      return this.imageSave();

    }).then((data) => {

      // 防雪柵管理情報の更新
      //if (this.isBssk) {
      if (this.baseinfo.shisetsu_kbn == 4) {
        return this.saveBsskMngInfo(false);
      }

    }).then((data) => {

      // 施設の健全性の取得
      return this.$http({
        method: 'GET',
        url: 'api/index.php/CheckListSchAjax/get_shisetsu_judge',
        params: {
          sno: this.sno,
          chk_mng_no: this.baseinfo.chk_mng_no
        }
      });

    }).then((data) => {
      this.shisetsujudges = JSON.parse(data.data[0].measures_judge_row);
      console.log(this.shisetsujudges);

      // Excelの生成
      return this.$http({
        method: 'GET',
        url: 'api/index.php/OutputExcel/save_chkData',
        params: {
          sno: this.sno,
          chk_mng_no: this.baseinfo.chk_mng_no,
          struct_idx: this.baseinfo.struct_idx,
          excel_ver: this.excel_ver ? 1 : 0
        }
      });

    }).finally((data) => {
      this.waitOverlay = false;
      this.editable = false;
      //this.change_flag = false;
      this.is_new = false;

      // 20161108 hirano 一時コメント化
      // this.deletable = true;

      // 基本情報の形式コードが入っていない&&入力形式があった場合は更新
      if (!this.baseinfo.shisetsu_keishiki_cd && this.baseinfo.input_keishiki_cd) {
        this.baseinfo.shisetsu_keishiki_cd = this.baseinfo.input_keishiki_cd;
        for (var $i = 0; $i < this.keishikis.keishiki_info.length; $i++) {
          if (this.baseinfo.shisetsu_keishiki_cd == this.keishikis.keishiki_info[$i].shisetsu_keishiki_cd) {
            this.baseinfo.shisetsu_keishiki_nm = this.keishikis.keishiki_info[$i].shisetsu_keishiki_nm;
          }
        }
      }

      /*
            this.unwatch = {};

            this.unwatch[0] = this.$scope.$watch(
              angular.bind(this, function () {
                return this.baseinfo;
              }),
              angular.bind(this, function (value) {
                this.change_flag = true;
              }),
              true
            );

            this.unwatch[1] = this.$scope.$watch(
              angular.bind(this, function () {
                return this.tenken_kasyo;
              }),
              angular.bind(this, function (value) {
                this.change_flag = true;
              }),
              true
            );

            this.unwatch[2] = this.$scope.$watch(
              angular.bind(this, function () {
                return this.patrolins;
              }),
              angular.bind(this, function (value) {
                this.change_flag = true;
              }),
              true
            );

            this.unwatch[3] = this.$scope.$watch(
              angular.bind(this, function () {
                return this.picture.data;
              }),
              angular.bind(this, function (value) {
                this.change_flag = true;
              }),
              true
            );

            this.unwatch[4] = this.$scope.$watch(
              angular.bind(this, function () {
                return this.zenkei_picture.data;
              }),
              angular.bind(this, function (value) {
                this.change_flag = true;
              }),
              true
            );

            setTimeout(() => {
              this.change_flag = false;
            }, 10);
      */

      this.alert('一時保存', '保存が完了しました');
    });
  }

  /* 確定保存ボタン */
  ClickFixedSaveFunction() {

    /*
        // 28年度の場合のみ通過
        // target_dtが2016/04/01<=target_dt<2017/04/01だけ通す
        var flg_28 = false;
        flg_28 = this.chkDate28(this.baseinfo.target_dt);

        // 29年度リリース対応
        // 基本情報が整うまで確定保存不可
        if (flg_28 == false) {
          this.alert('確定保存', '現在最新の施設情報をシステムに登録中のため、点検票の確定保存はできません。一時保存まで行ってください。');
          return;
        }
    */
    if (!this.checkImageData()) {
      return;
    }

    // スクリーニング対象の設定
    this.setScreeningTaisyou();

    // 健全性の再計算
    this.updateBuzaiJudge();

    // 点検箇所の健全性をコピー
    this.copyTenkenKasyoCheckJudge();

    // 措置対象、措置完了件数の設定
    this.setMeasuresCnt();

    // 形式必須
    if (!this.baseinfo.shisetsu_keishiki_cd) {
      // 形式が無い場合、必須入力
      //console.debug("形式なし！" + this.baseinfo.input_keishiki_cd);
      if (isNaN(this.baseinfo.input_keishiki_cd) || this.baseinfo.input_keishiki_cd == null) {
        // エラー
        this.alert('入力エラー', '確定保存時は形式は必須です');
        return;
      }
    }

    // フェーズごとの入力必須チェック処理
    if (!this.checkPhase()) {
      return;
    }

    // 保存処理（confirm → OK）
    var message = '確定保存してよろしいですか？<br><br>※' + this.baseinfo.phase_str_large + 'の入力内容が変更できなくなります';
    this.confirm(message, 0).then(() => {

      // プログレス表示
      this.waitOverlay = true;

      // パトロール員名の既存チェック、更新
      this.add_non_existent_patrolin();

      // 調査員名の既存チェック、更新
      this.add_non_existent_investigator();

      // 基本情報、部材以下情報の更新(＝一時保存)
      // ※mode未使用
      this.$http({
        method: 'POST',
        url: 'api/index.php/CheckListEdtAjax/set_chkdata',
        data: {
          baseinfo: this.baseinfo,
          buzaidata: this.tenken_kasyo,
          mode: 'update'
        }

      }).then((data) => {

        // フェーズ移行(フェーズ更新、履歴NOインクリメント)
        this.current_phase = this.baseinfo.phase;
        this.changePhase();

        // 基本情報、部材以下情報の更新(フェーズ、履歴NO更新後)
        // ※mode未使用
        return this.$http({
          method: 'POST',
          url: 'api/index.php/CheckListEdtAjax/set_chkdata',
          data: {
            baseinfo: this.baseinfo,
            buzaidata: this.tenken_kasyo,
            mode: 'insert'
          }
        });

      }).then((data) => {
        return this.imageSave();

      }).then((data) => {

        // 防雪柵管理情報の更新
        //if (this.isBssk) {
        if (this.baseinfo.shisetsu_kbn == 4) {
          return this.saveBsskMngInfo(true);
        }

      }).then((data) => {

        // 施設の健全性の取得
        return this.$http({
          method: 'GET',
          url: 'api/index.php/CheckListSchAjax/get_shisetsu_judge',
          params: {
            sno: this.sno,
            chk_mng_no: this.baseinfo.chk_mng_no
          }
        });

      }).then((data) => {
        this.shisetsujudges = JSON.parse(data.data[0].measures_judge_row);
        console.log(this.shisetsujudges);

        // Excelの生成
        return this.$http({
          method: 'GET',
          url: 'api/index.php/OutputExcel/save_chkData',
          params: {
            sno: this.sno,
            chk_mng_no: this.baseinfo.chk_mng_no,
            struct_idx: this.baseinfo.struct_idx,
            excel_ver: this.excel_ver ? 1 : 0
          }
        });

      }).then((data) => {
        // 20161108 hirano 一時コメント化
        // this.deletable = true;

        // 基本情報の形式コードが入っていない&&入力形式があった場合は更新
        if (!this.baseinfo.shisetsu_keishiki_cd && this.baseinfo.input_keishiki_cd) {
          this.baseinfo.shisetsu_keishiki_cd = this.baseinfo.input_keishiki_cd;
          for (var $i = 0; $i < this.keishikis.keishiki_info.length; $i++) {
            if (this.baseinfo.shisetsu_keishiki_cd == this.keishikis.keishiki_info[$i].shisetsu_keishiki_cd) {
              this.baseinfo.shisetsu_keishiki_nm = this.keishikis.keishiki_info[$i].shisetsu_keishiki_nm;
            }
          }
        }

        /*
                this.unwatch = {};

                this.unwatch[0] = this.$scope.$watch(
                  angular.bind(this, function () {
                    return this.baseinfo;
                  }),
                  angular.bind(this, function (value) {
                    this.change_flag = true;
                  }),
                  true
                );

                this.unwatch[1] = this.$scope.$watch(
                  angular.bind(this, function () {
                    return this.tenken_kasyo;
                  }),
                  angular.bind(this, function (value) {
                    this.change_flag = true;
                  }),
                  true
                );

                this.unwatch[2] = this.$scope.$watch(
                  angular.bind(this, function () {
                    return this.patrolins;
                  }),
                  angular.bind(this, function (value) {
                    this.change_flag = true;
                  }),
                  true
                );

                this.unwatch[3] = this.$scope.$watch(
                  angular.bind(this, function () {
                    return this.picture.data;
                  }),
                  angular.bind(this, function (value) {
                    this.change_flag = true;
                  }),
                  true
                );

                this.unwatch[4] = this.$scope.$watch(
                  angular.bind(this, function () {
                    return this.zenkei_picture.data;
                  }),
                  angular.bind(this, function (value) {
                    this.change_flag = true;
                  }),
                  true
                );

                setTimeout(() => {
                  this.change_flag = false;
                }, 10);*/
        return this.alert('確定保存', '保存が完了しました');
      }).finally(() => {
        this.waitOverlay = false;
        this.editable = false;
        this.is_new = false;
        this.windowUnlock();
        this.$window.location.reload(false);
      });
    });
  }

  // 28年度のチェック
  // 引数が28年度の場合true
  chkDate28(date) {
    if (moment("2016-04-01").unix() <= moment(date).unix() && moment(date).unix() < moment("2017-04-01").unix()) {
      return true;
    }
    return false;
  }

  /** 参考写真のチェック */
  checkImageData() {

    // 参考写真
    let max_owner_cd = 0;
    for (let i = 0; i < this.sankou_photos.length; i++) {
      if (Number(this.sankou_photos[i].owner_cd) > max_owner_cd) {
        max_owner_cd = Number(this.sankou_photos[i].owner_cd);
      }
    }
    for (let i = 0; i < this.sankou_photos.length; i++) {
      // まだowner_cdが振られていない（クライアントにしかない写真）にowner_cdを振る
      if (this.sankou_photos[i].path) {
        if (!this.sankou_photos[i].owner_cd) {
          this.sankou_photos[i].owner_cd = max_owner_cd + 1;
          max_owner_cd++;
        }
      }
    }
    // 写真枚数のチェック
    // 状況写真はここに入らないので、純粋のリストの枚数のチェック
    if (this.sankou_photos.length > 27) {
      this.alert('入力エラー', '参考写真は最大27枚までの登録です。');
      return false;
    }
    return true;
  }
  // フェーズチェック処理
  // (入力チェック)
  checkPhase() {

    // 措置（方針）、措置年月日のチェック
    if (!this.validate_measures_policy_dt()) {
      return false;
    }

    // フェーズ移行処理
    // ※仕様変更により、フェーズ4は措置として独立。そのため、フェーズ4へ明示的に移行することはない。
    switch (Number(this.baseinfo.phase)) {
      case 1: // 点検（Ⅰ、Ⅱ、Ⅲ、Ⅳ）

        // 入力必須項目のチェック
        if (!this.validate_chk_dt()) {
          return false;
        }
        if (!this.validate_chk_person()) {
          return false;
        }
        if (!this.validate_sonsyou_naiyou()) {
          return false;
        }
        if (!this.validate_leading_to_decistion()) {
          return false;
        }
        if (!this.validate_unexecuted_damage()) {
          return false;
        }
        break;

      case 2: // スクリーニング（Ⅱ、Ⅲ）
        // 新規登録～入力修正＝（確定）保存
        // 点検時健全性がⅢの点検箇所の全てのスクリーニングが完了
        // ※スクリーニングの完了は、スクリーニング完了チェックボックスのチェックと点検前損傷、点検前健全性の変更を保存。
        if (!this.validate_complete_screening()) {
          return false;
        }
        if (!this.validate_sonsyou_naiyou()) {
          return false;
        }
        if (!this.validate_leading_to_decistion()) {
          return false;
        }
        break;

      case 3: // 詳細調査（Ⅱ、Ⅲ）
        // 詳細調査の完了
        // ※詳細調査の完了は、調査日の入力と、点検前損傷、点検前健全性の変更を保存

        // 入力必須項目のチェック
        if (!this.check_investigate) {
          this.alert('入力エラー', '詳細調査完了が未チェックです');
          return false;
        }
        if (!this.validate_investigate_dt()) {
          return false;
        }
        if (!this.validate_investigate_person()) {
          return false;
        }
        if (!this.validate_leading_to_decistion()) {
          return false;
        }
        break;

      case 5: // 完了
        // 全ての措置が完了した
        // 健全性Ⅲ、Ⅳが存在しない、または存在する場合は措置日記入済み

        // 完了から移行するフェーズは無し
        // 措置の保存があるので保存処理へ
        break;

      case 6: // 強制終了
        // 途中で強制終了した場合、フェーズを6(強制終了)、履歴番号を加算
        // 新規作成などのタイミングで既存の未完入力データを強制終了に移行する
        break;

      default:
        break;
    }

    return true;
  }

  // フェーズ移行処理
  // (フェーズ更新、履歴NOインクリメント)
  changePhase() {

    // 点検箇所の健全性のチェック
    var max_judge = this.getWorstJudge();

    // フェーズ移行処理
    // ※仕様変更により、フェーズ4は措置として独立。そのため、フェーズ4へ明示的に移行することはない。
    switch (Number(this.baseinfo.phase)) {
      case 1: // 点検（Ⅰ、Ⅱ、Ⅲ、Ⅳ）

        // 点検箇所の健全性Ⅲの場合フェーズを2(スクリーニング)
        // それ以外の場合は5(完了)
        if (Number(max_judge) === 3) {
          this.baseinfo.phase = 2;
          this.baseinfo.rireki_no = Number(this.baseinfo.rireki_no) + 1;
          //                    this.setPictureRirekiNo(Number(this.baseinfo.rireki_no));

        } else {
          this.baseinfo.phase = 5;
          this.baseinfo.rireki_no = Number(this.baseinfo.rireki_no) + 1;
          //                    this.setPictureRirekiNo(Number(this.baseinfo.rireki_no));

        }
        break;

      case 2: // スクリーニング（Ⅱ、Ⅲ）
        // 新規登録～入力修正＝（確定）保存
        // 点検時健全性がⅢの点検箇所の全てのスクリーニングが完了
        // ※スクリーニングの完了は、スクリーニング完了チェックボックスのチェックと点検前損傷、点検前健全性の変更を保存。

        // 点検箇所の健全性Ⅲの場合フェーズを3(詳細調査)
        // それ以外の場合は5(完了)
        if (max_judge === 3) {

          // ************** H29年度改修 **************
          // ここに来た場合以下のパターンに分かれる
          // 1.点検時健全性Ⅲ・詳細調査
          // 2.点検時健全性Ⅲ・完了
          // この判定は、最悪の健全性を取得する際の処理では
          // 記述ができなかったので、再度行う

          // 詳細調査の判定
          if (this.judgeDetailInvestigation()) {
            this.baseinfo.phase = 3;
          } else {
            this.baseinfo.phase = 5;
          }
          //this.baseinfo.phase = 3;
          // ************** H29年度改修 **************
          this.baseinfo.rireki_no = Number(this.baseinfo.rireki_no) + 1;
          //                    this.setPictureRirekiNo(Number(this.baseinfo.rireki_no));

        } else if (max_judge === 1 || max_judge === 2) {
          this.baseinfo.phase = 5;
          this.baseinfo.rireki_no = Number(this.baseinfo.rireki_no) + 1;
          //                    this.setPictureRirekiNo(Number(this.baseinfo.rireki_no));

        }
        break;

      case 3: // 詳細調査（Ⅱ、Ⅲ）
        // 詳細調査の完了
        // ※詳細調査の完了は、調査日の入力と、点検前損傷、点検前健全性の変更を保存

        this.baseinfo.phase = 5;
        this.baseinfo.rireki_no = Number(this.baseinfo.rireki_no) + 1;
        //                this.setPictureRirekiNo(Number(this.baseinfo.rireki_no));

        // 差戻し機能追加による変更
        // 詳細調査が完了し、フェーズが完了になった場合、
        // 詳細調査である点検箇所について、全て詳細調査済とする
        var _buzai = this.tenken_kasyo.buzai;
        var _buzai_detail_row;
        var _tenken_kasyo_row;

        for (var i = 0; Object.keys(_buzai).length > i; i++) {
          _buzai_detail_row = _buzai[i].buzai_detail_row;
          for (var j = 0; Object.keys(_buzai_detail_row).length > j; j++) {
            _tenken_kasyo_row = _buzai_detail_row[j].tenken_kasyo_row;
            for (var k = 0; Object.keys(_tenken_kasyo_row).length > k; k++) {
              // 調査(方針)が詳細調査の点検箇所があれば詳細調査済にする
              if (_tenken_kasyo_row[k].check_policy_str == this.houshin.houshins[2].houshin) {
                _tenken_kasyo_row[k].check_policy = this.houshin.houshins[3].houshin_cd;
                _tenken_kasyo_row[k].check_policy_str = this.houshin.houshins[3].houshin;
              }
            }
          }
        }

        break;

      case 5: // 完了
        // 全ての措置が完了した
        // 健全性Ⅲ、Ⅳが存在しない、または存在する場合は措置日記入済み

        // 完了から移行するフェーズは無し
        // 措置の保存があるので保存処理へ
        break;

      case 6: // 強制終了
        // 途中で強制終了した場合、フェーズを6(強制終了)、履歴番号を加算
        // 新規作成などのタイミングで既存の未完入力データを強制終了に移行する
        this.baseinfo.phase = 6;
        this.baseinfo.rireki_no = Number(this.baseinfo.rireki_no) + 1;
        //                this.setPictureRirekiNo(Number(this.baseinfo.rireki_no));
        break;

      default:
        break;
    }
  }

  /***
   * スクリーニング時の詳細調査or完了のチェック
   *
   * スクリーニング時に健全性がⅢのデータを全てチェックする。
   * チェックは、調査(方針)が詳細調査なのか完了なのか
   * 一つでも詳細調査がある場合trueを返却し、全て完了の場合はfalseを返却する
   *
   ***/
  judgeDetailInvestigation() {

    var detail_investigation = false;

    var _buzai = this.tenken_kasyo.buzai;
    var _buzai_detail_row;
    var _tenken_kasyo_row;

    for (var i = 0; Object.keys(_buzai).length > i; i++) {
      _buzai_detail_row = _buzai[i].buzai_detail_row;

      for (var j = 0; Object.keys(_buzai_detail_row).length > j; j++) {
        _tenken_kasyo_row = _buzai_detail_row[j].tenken_kasyo_row;

        for (var k = 0; Object.keys(_tenken_kasyo_row).length > k; k++) {

          // 健全性Ⅲ以外は無視
          if (Number(_tenken_kasyo_row[k].check_judge) != 3) {
            continue;
          }
          // 調査(方針)が詳細調査の点検箇所があれば次は詳細調査
          if (_tenken_kasyo_row[k].check_policy_str == this.houshin.houshins[2].houshin) {
            detail_investigation = true;
            break;
          }
        }
      }
    }
    return detail_investigation;
  }

  // 詳細調査は行わないチェックボックス
  EndCheck(row) {
    if (row.end_check == true) {
      // 瞬間的にチェックが付けられるとついてしまうのでここでチェック
      if (!row.screening_flg) {
        row.end_check = false;
        return;
      }
      row.check_policy = this.houshin.houshins[4].houshin_cd;
      row.check_policy_str = this.houshin.houshins[4].houshin;
    } else {
      row.check_policy = this.houshin.houshins[2].houshin_cd;
      row.check_policy_str = this.houshin.houshins[2].houshin;
    }
  }

  // 健全性Ⅲのスクリーニングチェックが完了しているか
  isCompleteScreening() {

    // スクリーニング対象かつ、スクリーニング未完了のものを検索する
    var _buzai = this.tenken_kasyo.buzai;
    var _buzai_detail_row;
    var _tenken_kasyo_row;

    for (var i = 0; Object.keys(_buzai).length > i; i++) {
      _buzai_detail_row = _buzai[i].buzai_detail_row;

      for (var j = 0; Object.keys(_buzai_detail_row).length > j; j++) {
        _tenken_kasyo_row = _buzai_detail_row[j].tenken_kasyo_row;

        // スクリーニング対象かつスクリーニング未実施の場合、未完了
        for (var k = 0; Object.keys(_tenken_kasyo_row).length > k; k++) {
          if (Number(_tenken_kasyo_row[k].screening_taisyou) == 1) {
            // スクリーニングにチェックされていないものがある
            if (_tenken_kasyo_row[k].screening_flg == false) {
              return false;
            }
          }
        }
      }

    }

    return true;
  }

  // 点検箇所の入力（健全性）に対して損傷の種類が選択されているか
  isSelectedSonsyouNaiyou() {

    // 健全性が2以上のものは損傷の種類を選択
    var _buzai = this.tenken_kasyo.buzai;
    var _buzai_detail_row;
    var _tenken_kasyo_row;

    for (var i = 0; Object.keys(_buzai).length > i; i++) {
      _buzai_detail_row = _buzai[i].buzai_detail_row;

      for (var j = 0; Object.keys(_buzai_detail_row).length > j; j++) {
        _tenken_kasyo_row = _buzai_detail_row[j].tenken_kasyo_row;
        for (var k = 0; Object.keys(_tenken_kasyo_row).length > k; k++) {
          // hirano追加 点検対象有無がチェックついていない場合はチェックしない
          if (_tenken_kasyo_row[k].taisyou_umu == false) {
            continue;
          }
          if (Number(_tenken_kasyo_row[k].check_judge) > 1) {
            if (Number(_tenken_kasyo_row[k].sonsyou_naiyou_cd) === 0) {
              return false;
            }
          }
        }
      }
    }

    return true;
  }

  // 措置に対して措置（方針）、措置年月日が入力されているか
  isDescribedMeasures() {

    // 措置の健全性がある場合は措置（方針）、措置年月日を入力
    var _buzai = this.tenken_kasyo.buzai;
    var _buzai_detail_row;
    var _tenken_kasyo_row;

    for (var i = 0; Object.keys(_buzai).length > i; i++) {
      _buzai_detail_row = _buzai[i].buzai_detail_row;

      for (var j = 0; Object.keys(_buzai_detail_row).length > j; j++) {
        _tenken_kasyo_row = _buzai_detail_row[j].tenken_kasyo_row;

        for (var k = 0; Object.keys(_tenken_kasyo_row).length > k; k++) {
          if (Number(_tenken_kasyo_row[k].measures_judge) > 0) {
            if (_tenken_kasyo_row[k].measures_policy === '') {
              return false;
            }

            if (_tenken_kasyo_row[k].measures_dt == null) {
              return false;
            }

            if (_tenken_kasyo_row[k].measures_dt === '') {
              return false;
            }
          }
        }
      }
    }

    return true;
  }

  // 判定に至るまでの考え方(1～4)の各項目が入力されているか
  isInputLeadingToDecistion() {

    // 点検フェーズ確定保存時に入力する
    var _buzai = this.tenken_kasyo.buzai;
    var _buzai_detail_row;

    for (var i = 0; Object.keys(_buzai).length > i; i++) {
      _buzai_detail_row = _buzai[i].buzai_detail_row;

      // 対象となる点検箇所の確認()
      if (!this.effectiveTenkenKasyo(_buzai_detail_row)) {
        continue;
      }

      // 1.外観上から判断できる要因
      if ((_buzai[i].hantei1 == null) || (_buzai[i].hantei1 == '')) {
        if (_buzai[i].measures_buzai_judge > 2) {
          return _buzai[i].buzai_nm;
        }
        _buzai[i].hantei1 = '健全性において問題なし';
      }

      // 2.（前回点検からの）進行性
      if ((_buzai[i].hantei2 == null) || (_buzai[i].hantei2 == '')) {
        if (_buzai[i].measures_buzai_judge > 2) {
          return _buzai[i].buzai_nm;
        }
        _buzai[i].hantei2 = '健全性において問題なし';
      }

      // 3.耐久性・耐荷力へ与える影響
      if ((_buzai[i].hantei3 == null) || (_buzai[i].hantei3 == '')) {
        if (_buzai[i].measures_buzai_judge > 2) {
          return _buzai[i].buzai_nm;
        }
        _buzai[i].hantei3 = '健全性において問題なし';
      }

      // 4.想定される補修方法等
      if ((_buzai[i].hantei4 == null) || (_buzai[i].hantei4 == '')) {
        if (_buzai[i].measures_buzai_judge > 2) {
          return _buzai[i].buzai_nm;
        }
        _buzai[i].hantei4 = '健全性において問題なし';
      }
    }

    return '';
  }

  // 対象となる点検箇所があるか
  effectiveTenkenKasyo(_buzai_detail_row) {

    var _tenken_kasyo_row;

    for (var j = 0; Object.keys(_buzai_detail_row).length > j; j++) {
      _tenken_kasyo_row = _buzai_detail_row[j].tenken_kasyo_row;

      for (var k = 0; Object.keys(_tenken_kasyo_row).length > k; k++) {
        if (_tenken_kasyo_row[k].taisyou_umu == true) {
          return true;
        }
      }
    }

    // 部材の全ての点検箇所が対象でない場合
    return false;
  }

  // 点検箇所の未実施チェック
  setCheckStatus(isTmpSave) {

    var _buzai = this.tenken_kasyo.buzai;
    var _buzai_detail_row;
    var _tenken_kasyo_row;
    var _sonsyou_naiyou_row;
    var _check_status;
    var _isAllChecked = true;
    var _tenkenKasyoNm = '';

    for (var i = 0; Object.keys(_buzai).length > i; i++) {
      _buzai_detail_row = _buzai[i].buzai_detail_row;

      for (var j = 0; Object.keys(_buzai_detail_row).length > j; j++) {
        _tenken_kasyo_row = _buzai_detail_row[j].tenken_kasyo_row;

        for (var k = 0; Object.keys(_tenken_kasyo_row).length > k; k++) {
          if (!_tenken_kasyo_row[k].taisyou_umu) {
            continue;
          }

          _sonsyou_naiyou_row = _tenken_kasyo_row[k].sonsyou_naiyou_row;

          _check_status = 1;
          for (var l = 0; Object.keys(_sonsyou_naiyou_row).length > l; l++) {
            // その他を除く
            if (_sonsyou_naiyou_row[l].sonsyou_naiyou_cd == 9) {
              continue;
            }
            // 未実施箇所がある場合
            if (_sonsyou_naiyou_row[l].check_before == 0) {
              _check_status = 0;
              _isAllChecked = false;

              _tenkenKasyoNm = _tenken_kasyo_row[k].tenken_kasyo_nm + '(' +
                _tenken_kasyo_row[k].sign + ')';
            }
          }
          // 1箇所でも未実施の場合は未実施となる
          _tenken_kasyo_row[k].check_status = _check_status;
        }
      }
    }

    if (isTmpSave) {
      return '';
    }

    // 未実施の損傷内容が無い場合、その他の特記事項を自動入力する
    if (_isAllChecked) {
      // 20161121 hirano 無い場合のみ上書き
      // 既存の文言は上書きする
      if (!this.baseinfo.part_notable_chk) {
        this.baseinfo.part_notable_chk = '特になし';
      } else {
        if (this.baseinfo.part_notable_chk == "") {
          this.baseinfo.part_notable_chk = '特になし';
        }
      }
      if (!this.baseinfo.reason_notable_chk) {
        this.baseinfo.reason_notable_chk = '特になし';
      } else {
        if (this.baseinfo.reason_notable_chk == "") {
          this.baseinfo.reason_notable_chk = '特になし';
        }
      }
      if (!this.baseinfo.special_report) {
        this.baseinfo.special_report = '特になし';
      } else {
        if (this.baseinfo.special_report == "") {
          this.baseinfo.special_report = '特になし';
        }
      }
      //      if((!this.baseinfo.special_report) || (this.baseinfo.special_report == '')) {
      //this.baseinfo.special_report = '特になし';
      //      }
    }

    return _tenkenKasyoNm;
  }

  //    // 調査日の入力が完了しているか
  //    isSetInvestigateDate() {
  //
  //        // 調査日未入力チェック
  //        if (this.baseinfo.investigate_dt !== '') {
  //            return true;
  //        }
  //        return false;
  //    }

  // 健全性Ⅲ、Ⅳの措置（方針）が入力されているか
  //    isSetMeasuresPolicies() {
  //
  //        // 健全性Ⅲ、Ⅳ、措置（方針）未入力のものを検索する
  //        angular.forEach(this.tenken_kasyo.buzai, function (value, key) {
  //            angular.forEach(value.buzai_detail_row, function (value, key) {
  //                angular.forEach(value.tenken_kasyo_row, function (value, key) {
  //
  //                    if (Number(value.measures_judge) >= 3) {
  //                        if (value.measures_policy === '') {
  //                            return false;
  //                        }
  //                    }
  //                });
  //            });
  //        });
  //
  //        return true;
  //    }

  // パトロール員名の既存チェック、更新
  add_non_existent_patrolin() {

    var chk_person = this.baseinfo.chk_person;
    var chk_company = this.baseinfo.chk_company;
    var exists = false;

    // リスト中の名前に一致するものがあるか確認
    if (this.patrolins) {
      for (var i = 0; Object.keys(this.patrolins.patrolins).length > i; i++) {
        if ((chk_person == this.patrolins.patrolins[i].simei) && (chk_company == this.patrolins.patrolins[i].company)) {
          exists = true;
          break;
        }
      }
    }

    // パトロール員が存在しない場合、追加
    if (!exists) {
      this.$http({
        method: 'POST',
        url: 'api/index.php/CheckListEdtAjax/add_patrolin',
        data: {
          syozoku_cd: this.syozoku_cd,
          syucchoujo_cd: this.syucchoujo_cd,
          busyo_cd: this.busyo_cd,
          simei: this.baseinfo.chk_person
        }
      });
    }
  }

  SelectedPatrolinList() {
    this.baseinfo.chk_company = this.patrolin.company;
    this.baseinfo.chk_person = this.patrolin.simei;
  }

  // 調査員名の既存チェック、更新
  add_non_existent_investigator() {

    var investigate_person = this.baseinfo.investigate_person;
    var investigate_company = this.baseinfo.investigate_company;
    var exists = false;

    // リスト中の名前に一致するものがあるか確認
    if (this.investigators) {
      for (var i = 0; Object.keys(this.investigators.investigator_info).length > i; i++) {
        if ((investigate_person == this.investigators.investigator_info[i].simei) && (investigate_company == this.investigators.investigator_info[i].company)) {
          exists = true;
          break;
        }
      }
    }

    // 調査員が存在しない場合、追加
    if (!exists) {
      this.$http({
        method: 'POST',
        url: 'api/index.php/CheckListEdtAjax/add_investigator',
        data: {
          syucchoujo_cd: this.syucchoujo_cd,
          busyo_cd: this.busyo_cd,
          simei: this.baseinfo.investigate_person
        }
      });
    }
  }

  SelectedInvestigatorList() {
    this.baseinfo.investigate_company = this.investigator.company;
    this.baseinfo.investigate_person = this.investigator.simei;
  }

  // 点検実施年月日の入力チェック
  validate_chk_dt() {
    if (this.baseinfo.chk_dt == null) {
      this.alert('入力エラー', '点検実施年月日が未入力です');
      return false;
    }

    if (this.baseinfo.chk_dt == '') {
      this.alert('入力エラー', '点検実施年月日が未入力です');
      return false;
    }
    return true;
  }

  // 点検員の入力チェック
  validate_chk_person() {
    if (this.baseinfo.chk_person == null) {
      this.alert('入力エラー', '点検員が未入力です');
      return false;
    }

    if (this.baseinfo.chk_person == '') {
      this.alert('入力エラー', '点検員が未入力です');
      return false;
    }
    return true;
  }

  // 調査日の入力チェック
  validate_investigate_dt() {
    if (this.baseinfo.investigate_dt == null) {
      this.alert('入力エラー', '調査日が未入力です');
      return false;
    }

    if (this.baseinfo.investigate_dt == '') {
      this.alert('入力エラー', '調査日が未入力です');
      return false;
    }
    return true;
  }

  // 調査員の入力チェック
  validate_investigate_person() {
    if (this.baseinfo.investigate_person == null) {
      this.alert('入力エラー', '調査員が未入力です');
      return false;
    }

    if (this.baseinfo.investigate_person == '') {
      this.alert('入力エラー', '調査員が未入力です');
      return false;
    }
    return true;
  }

  // スクリーニング完了の入力チェック
  validate_complete_screening() {
    if (!this.isCompleteScreening()) {
      this.alert('入力エラー', 'スクリーニング未完了の点検箇所があります');
      return false;
    }

    return true;
  }

  // 点検箇所入力時の損傷の種類入力チェック
  validate_sonsyou_naiyou() {
    if (!this.isSelectedSonsyouNaiyou()) {
      this.alert('入力エラー', '損傷の種類が選択されていない箇所があります');
      return false;
    }

    return true;
  }

  // 点検箇所入力時の損傷の種類入力チェック
  validate_measures_policy_dt() {
    if (!this.isDescribedMeasures()) {
      this.alert('入力エラー', '措置（方針）または措置年月日が未入力です');
      return false;
    }

    return true;
  }


  // 判定に至るまでの考え方の入力チェック
  validate_leading_to_decistion() {
    var buzaiNm = this.isInputLeadingToDecistion();
    if (buzaiNm != '') {
      this.alert('入力エラー', buzaiNm + 'の判定に至るまでの考え方が未入力です');
      return false;
    }

    return true;
  }

  // 点検箇所の未実施損傷内容チェック
  validate_unexecuted_damage() {
    var tenkenKasyoNm = this.setCheckStatus(false);
    if (tenkenKasyoNm != '') {
      this.alert('入力エラー', '点検箇所：' + tenkenKasyoNm + 'に未実施の損傷内容があります');
      return false;
    }

    return true;
  }

  // 確定フェーズの健全性の表示
  getJudgePerPhase(phase) {

    var _judge = '－';
    if (this.shisetsujudges == null) {
      return _judge;
    }

    /* 20170822 hirano なんか違う
        var _judgeNum = Object.keys(this.shisetsujudges.measures_judges).length;
        if (_judgeNum > phase) {
          _judge = this.shisetsujudges.measures_judges[phase - 1].shisetsu_judge_nm;
        }

        // 完了状態の場合健全性を表示する
        if ((this.baseinfo.phase == '5') && (Number(phase) == 5)) {
          _judge = this.shisetsujudges.measures_judges[_judgeNum - 1].shisetsu_judge_nm;
        }
    */

    // 取得健全性を逆順ループし、最初に見つかったものが
    // 該当のフェーズの健全性(履歴番号で昇順にしているので)
    // また、取得健全性の逆順ループの最初のフェーズが引数より小さい場合は
    // そのフェーズまで到達していないということなのでハイフンとする。

    // 例:フェーズが1,2,3,5,3,1,2,3と移行した場合
    // 履歴番号で昇順なので最後の3から逆順ループ
    // 1⇒3になった時差戻しが起こっているので、3は最新ではない。
    // この場合、点検中とスクリーニングと詳細調査の健全性が表示されることになる。

    // 逆順ループ
    for (var i = this.shisetsujudges.measures_judges.length - 1; i >= 0; i--) {
      // ループ終了判定
      // 初回のみ
      if (i == this.shisetsujudges.measures_judges.length - 1) {
        // 引数のフェーズの方が最新のフェーズより大きい場合は引数のフェーズまで到達していない
        if (phase > this.shisetsujudges.measures_judges[i].phase) {
          break;
        }
      }

      // 引数のフェーズと等しいフェーズの健全性をセットし終了
      if (phase == this.shisetsujudges.measures_judges[i].phase) {
        _judge = this.shisetsujudges.measures_judges[i].shisetsu_judge_nm;
        break;
      }
    }

    return _judge;
  }

  // 基本情報、部材以下データの保存
  //    saveChkData(_mode) {
  //
  //        // プログレス表示
  //        this.waitOverlay = true;
  //
  //        // パトロール員名の既存チェック、更新
  //        this.add_non_existent_patrolin();
  //
  //        // 調査員名の既存チェック、更新
  //        this.add_non_existent_investigator();
  //
  //        // 基本情報、部材以下情報の更新
  //        this.$http({
  //            method: 'POST',
  //            url: 'api/index.php/CheckListEdtAjax/set_chkdata',
  //            data: {
  //                baseinfo: this.baseinfo,
  //                buzaidata: this.tenken_kasyo,
  //                mode: _mode
  //            }
  //
  //        }).then((data) => {
  //            return this.imageSave();
  //
  //        }).then((data) => {
  //            this.waitOverlay = false;
  //
  //        });
  //    }

  // 防雪柵管理情報の保存
  saveBsskMngInfo(isFixedSave) {

    /**
      一時保存および確定保存するタイミングで、子データの健全性を全て取得し、最悪のものを
      親データの健全性として設定する。
      ・施設検索画面において、親データは子データの健全性の中で最悪のものを表示する
      ・施設検索画面において、親データは子データのフェーズを表示する（複数のフェーズがある場合、xx～xxとなる）
      ・防雪柵管理情報テーブルは、子データの点検状態が全て完了になった際に完了フラグが設定される
      ・防雪柵情報テーブルは、点検票を表示したタイミングでレコードを生成する
      ・レコードを参照するのは、防雪柵を保存した時のみ
     */

    /**
      確定保存の場合
      一時保存のタイミングで親データに更新した場合、子データの点検日を古い日付に変更された際に
      点検日、点検員が更新されないため、確定保存時のみ更新する

      ※本処理が実行される時点では、フェーズチェックにてフェーズが変更されているため、
      　フェーズは変更前の値を参照する（= current_phase）
        最新のフェーズ（フェーズチェック後）は、phaseにて取得可能。
    */

    // 調査日、調査員は詳細調査の確定保存の場合
    if (isFixedSave && this.current_phase == 3) {
      // 調査日、調査員の更新
      this.updateInvestigateData();
    }

    // 点検日、点検員、所見は点検の確定保存の場合
    if (isFixedSave && this.current_phase == 1) {
      // 点検日、点検員の更新
      this.updateChkData();

      // 所見の更新
      this.updateSyoken();
    }

    // 防雪柵管理情報の追加
    return this.$http({
      method: 'POST',
      url: 'api/index.php/CheckListEdtAjax/add_bousetsusaku_mng_info',
      data: {
        chk_mng_no: this.bssk_mng_info[0].chk_mng_no,
        chk_mng_no_struct: this.baseinfo.chk_mng_no
      }

    }).then((data) => {

      // 全ての子データの完了状態を取得
      return this.$http({
        method: 'POST',
        url: 'api/index.php/SchBsskChildrenAjax/get_srch_bssk_children',
        data: {
          sno: this.sno
        }
      });

    }).then((data) => {

      // レコードが無い場合は完了状態はfalseのまま
      var isComplete = false;

      if (data.data[0].sch_result_row != null) {
        this.bsskdata = JSON.parse(data.data[0].sch_result_row).sch_result;

        // 全ての子データが完了済みの場合、親データを完了状態にする
        isComplete = true;
        for (var j = 0; Object.keys(this.bsskdata).length > j; j++) {

          if (this.bsskdata[j].phase == null) {
            isComplete = false;
            continue;
          }
          if (this.bsskdata[j].phase != 5) {
            isComplete = false;
          }
        }
      }

      // 全ての子データが完了した場合、完了状態にする
      if (isComplete) {
        this.baseinfo_parent.phase = 5;

        // 防雪柵管理情報の更新
        return this.$http({
          method: 'POST',
          url: 'api/index.php/CheckListEdtAjax/set_bousetsusaku_mng_info_complete',
          data: {
            chk_mng_no: this.bssk_mng_info[0].chk_mng_no
          }
        });
      }

    }).then((data) => {

      // 防雪柵親データの点検情報、所見、調査情報を更新
      // ※mode未使用
      return this.$http({
        method: 'POST',
        url: 'api/index.php/CheckListEdtAjax/set_chkdata',
        data: {
          baseinfo: this.baseinfo_parent,
          buzaidata: this.tenken_kasyo_parent,
          mode: 'update'
        }
      });

    }).then((data) => {

      // 防雪柵親データに子データの健全性を反映する
      return this.$http({
        method: 'POST',
        url: 'api/index.php/CheckListEdtAjax/merge_to_bssk_parent',
        data: {
          sno: this.sno
        }
      });

    });

  }

  // 点検日、点検員の更新
  updateChkData() {

    // 点検日、点検員の更新
    if (this.baseinfo.chk_dt != null) {
      var child_dt = new Date(this.baseinfo.chk_dt);
      var parent_dt;
      var isUpdate = false;

      // 親データの点検日がnull、もしくは子データよりも古い場合、点検日と点検員を更新する
      if (this.baseinfo_parent.chk_dt == null) {
        isUpdate = true;
      } else {
        parent_dt = new Date(this.baseinfo_parent.chk_dt);
        if (child_dt > parent_dt) {
          isUpdate = true;
        }
      }

      // 子データの点検日が新しい場合、点検日と点検員を更新する
      if (isUpdate) {
        // 点検日
        this.baseinfo_parent.chk_dt = this.baseinfo.chk_dt;

        // 点検会社
        if (this.baseinfo.chk_company != null) {
          this.baseinfo_parent.chk_company = this.baseinfo.chk_company;
        }

        // 点検員
        if (this.baseinfo.chk_person != null) {
          this.baseinfo_parent.chk_person = this.baseinfo.chk_person;
        }
      }
    }
  }

  // 調査日、調査員の更新
  updateInvestigateData() {

    // 調査日、調査員の更新
    if (this.baseinfo.investigate_dt != null) {
      var child_dt = new Date(this.baseinfo.investigate_dt);
      var parent_dt;
      var isUpdate = false;

      // 親データの調査日がnull、もしくは子データよりも古い場合、調査日と調査員を更新する
      if (this.baseinfo_parent.investigate_dt == null) {
        isUpdate = true;
      } else {
        parent_dt = new Date(this.baseinfo_parent.investigate_dt);
        if (child_dt > parent_dt) {
          isUpdate = true;
        }
      }

      // 子データの調査日が新しい場合、調査日と調査員を更新する
      if (isUpdate) {
        // 調査日
        this.baseinfo_parent.investigate_dt = this.baseinfo.investigate_dt;

        // 調査会社
        if (this.baseinfo.investigate_company != null) {
          this.baseinfo_parent.investigate_company = this.baseinfo.investigate_company;
        }

        // 調査員
        if (this.baseinfo.investigate_person != null) {
          this.baseinfo_parent.investigate_person = this.baseinfo.investigate_person;
        }
      }
    }
  }

  // 所見の更新
  updateSyoken() {
    // 所見を追記する
    if (this.baseinfo.syoken == null) {
      return;
    }
    if (this.baseinfo.syoken.length > 0) {
      var syoken = "";
      if (this.baseinfo_parent.syoken != null) {
        if (this.baseinfo_parent.syoken.length > 0) {
          syoken = "\n";
        }
      }
      syoken += this.baseinfo.syoken;
      this.baseinfo_parent.syoken += syoken;
    }
  }

  // 防雪柵管理情報の完了確認
  /*  isCompleteBsskMngInfo() {

      var isComplete = false;
      var bsskMngInfoNum = Object.keys(this.bssk_mng_info).length;

      // 未実施箇所がある場合、未完了
      if (Number(this.struct_idx_num) > bsskMngInfoNum) {
        return isComplete;
      }

      for (var i = 0; Object.keys(this.bssk_mng_info).length > i; i++) {
        if (!this.isCompleteBsskPhase(this.bssk_mng_info[i].chk_mng_no_struct)) {
          break;
        }
        isComplete = true;
      }

      return isComplete;
    }*/

  // 防雪柵管理情報内の子データの完了確認
  /*  isCompleteBsskPhase(_chk_mng_no) {

      var isPhaseComplete = false;

      // 基本情報の取得
      this.$http({
        method: 'GET',
        url: 'api/index.php/CheckListSchAjax/get_baseinfo_by_chkmngno',
        params: {
          chk_mng_no: _chk_mng_no,
          sno: this.sno,
          struct_idx: this.struct_idx
        }

      }).then((data) => {
        if (Number(data.data[0].phase) == 5) {
          isPhaseComplete = true;
        }
        console.log(this.baseinfo);

      }).finally((data) => {
        return isPhaseComplete;

      });

    }*/

  // 防雪柵管理情報の完了確認
  /*  setAllBsskMngInfoComplete() {

      var _chk_mng_no = this.bssk_mng_info[0].chk_mng_no;

      for (var i = 0; Object.keys(this.bssk_mng_info).length > i; i++) {
        this.setBsskMngInfoComplete(_chk_mng_no, this.bssk_mng_info[i].chk_mng_no_struct);
      }
    }*/

  // 防雪柵管理情報内の子データを完了状態に更新
  /*  setBsskMngInfoComplete(_chk_mng_no, _chk_mng_no_struct) {

      // 防雪柵管理情報の更新
      this.$http({
        method: 'POST',
        url: 'api/index.php/CheckListEdtAjax/set_bousetsusaku_mng_info_complete',
        data: {
          chk_mng_no: _chk_mng_no,
          chk_mng_no_struct: _chk_mng_no_struct
        }

      });
    }*/

  back_srch() {

  }

  /* 新規登録ボタン */
  //    ClickRegisterFunction() {
  //
  //        // 現在入力中のデータを強制終了
  //        if (this.baseinfo.phase != 5) {
  //            this.baseinfo.phase = 6;
  //            this.saveChkData('update');
  //        }
  //
  //        // 点検データ_メインの追加
  //        this.$http({
  //            method: 'POST',
  //            url: 'api/index.php/CheckListEdtAjax/add_chkdatamain',
  //            data: {
  //                sno: this.sno
  //            }
  //
  //        }).then((data) => {
  //
  //            // 追加した基本情報の取得
  //            return this.$http({
  //                method: 'GET',
  //                //                url: 'api/index.php/CheckListSchAjax/get_baseinfo_new',
  //                url: 'api/index.php/CheckListSchAjax/get_baseinfo_by_chkmngno',
  //                params: {
  //                    chk_mng_no: 0,
  //                    sno: this.sno,
  //                    struct_idx: this.struct_idx
  //                }
  //            });
  //
  //        }).then((data) => {
  //            this.baseinfo = data.data[0];
  //            console.log(this.baseinfo);
  //
  //            // 部材以下情報の取得
  //            return this.$http({
  //                method: 'GET',
  //                url: 'api/index.php/CheckListSchAjax/get_chkdata_new',
  //                params: {
  //                    chk_mng_no: this.baseinfo.chk_mng_no,
  //                    rireki_no: this.baseinfo.rireki_no,
  //                    shisetsu_kbn: this.baseinfo.shisetsu_kbn
  //                }
  //            });
  //
  //        }).then((data) => {
  //            this.tenken_kasyo = JSON.parse(data.data[0].buzai_row);
  //            console.log(data);
  //
  //            // 点検管理番号の取得
  //            return this.$http({
  //                method: 'GET',
  //                url: 'api/index.php/CheckListSchAjax/get_chkmngnos',
  //                params: {
  //                    sno: this.sno
  //                }
  //            });
  //        }).then((data) => {
  //            this.chkmngnos = JSON.parse(data.data[0].chkmngnos);
  //            console.log(this.chkmngnos);
  //
  //            // 施設の健全性の取得
  //            return this.$http({
  //                method: 'GET',
  //                url: 'api/index.php/CheckListSchAjax/get_shisetsu_judge',
  //                params: {
  //                    sno: this.sno,
  //                    chk_mng_no: this.baseinfo.chk_mng_no
  //                }
  //            });
  //        }).then((data) => {
  //            this.shisetsujudges = JSON.parse(data.data[0].measures_judge_row);
  //            console.log(this.shisetsujudges);
  //
  //            this.maxInspection = Object.keys(this.chkmngnos.chkmngnos).length;
  //            this.currentInspection = this.maxInspection - 1;
  //
  //            // 基本情報の取得（過去履歴がある場合）
  //            return this.$http({
  //                method: 'GET',
  //                url: 'api/index.php/CheckListSchAjax/get_baseinfo_past',
  //                params: {
  //                    chk_mng_no: this.chkmngnos.chkmngnos[this.maxInspection - 1].chk_mng_no,
  //                    sno: this.sno,
  //                    struct_index: this.struct_idx
  //                }
  //            });
  //        }).then((data) => {
  //            this.baseinfo_pasts = JSON.parse(data.data[0].baseinfo_past_row);
  //            console.log(this.baseinfo_pasts);
  //
  //            // 入力履歴表示用に配列に入れる
  //            this.candidate = {};
  //            this.setCandidate(this.candidate, this.baseinfo_pasts.baseinfo_past);
  //
  //            // 部材以下情報の取得（過去履歴がある場合）
  //            return this.$http({
  //                method: 'GET',
  //                url: 'api/index.php/CheckListSchAjax/get_chkdata',
  //                params: {
  //                    chk_mng_no: this.baseinfo_pasts.baseinfo_past[0].chk_mng_no,
  //                    rireki_no: this.baseinfo_pasts.baseinfo_past[0].rireki_no,
  //                    shisetsu_kbn: this.baseinfo_pasts.baseinfo_past[0].shisetsu_kbn
  //                }
  //            });
  //        });
  //
  //    }

  /* 点検票表示（前） */
  clickPreviousFunctionConfirm() {
    // 編集モードの場合
    if (this.editable) {
      this.confirm('変更があった場合無視されますがよろしいですか？', 0).then(() => {
        this.clickPreviousFunction();
      });
    } else {
      this.clickPreviousFunction();
    }
    /*
        if (this.change_flag) {
          this.confirm('変更を破棄してよろしいですか？', 0).then(() => {

            //                // 過去の点検票は更新不可のため監視を解除する
            //                angular.forEach(this.unwatch, function(value, key) {
            //                    value();
            //                });
            //                this.change_flag = false;

            this.clickPreviousFunction();
          });
        } else {

          //            // 過去の点検票は更新不可のため監視を解除する
          //            angular.forEach(this.unwatch, function(value, key) {
          //                value();
          //            });
          //            this.change_flag = false;

          this.clickPreviousFunction();
        }
    */
  }

  /* 点検票表示（前） */
  clickPreviousFunction() {
    if (this.currentInspection === 0) {
      return;
    }
    this.currentInspection--;
    this.editable = false;

    var _currentInspection = this.currentInspection;
    var _chk_mng_no = 0;

    angular.forEach(this.chkmngnos.chkmngnos, function (value, key) {
      if (key === _currentInspection) {
        _chk_mng_no = value.chk_mng_no;
      }
    });

    // 基本情報、部材以下情報の取得
    //        this.loadPastChkData(_chk_mng_no);

    var link = '/tenken/' + this.sno + '/' + this.struct_idx + '/' + _chk_mng_no;
    this.location(link);
  }

  /* 点検票表示（次） */
  clickNextFunction() {
    if (this.currentInspection + 1 === this.maxInspection) {
      return;
    }
    this.currentInspection++;
    this.editable = false;

    var _currentInspection = this.currentInspection;
    var _chk_mng_no = 0;
    angular.forEach(this.chkmngnos.chkmngnos, function (value, key) {
      if (key === _currentInspection) {
        _chk_mng_no = value.chk_mng_no;
      }
    });

    //        if(this.currentInspection + 1 === this.maxInspection) {
    //            this.chk_mng_no = _chk_mng_no;
    //            this.loadLastChkData();
    //
    //        } else {
    //            // 基本情報、部材以下情報の取得
    //            this.loadPastChkData(_chk_mng_no);
    //        }

    var link = '/tenken/' + this.sno + '/' + this.struct_idx + '/' + _chk_mng_no;
    this.location(link);
  }

  // 過去の点検票を取得する
  //    loadPastChkData(chk_mng_no) {
  //
  //        this.waitLoadOverlay = true;
  //
  //        // 基本情報の取得
  //        this.$http({
  //            method: 'GET',
  //            url: 'api/index.php/CheckListSchAjax/get_baseinfo_by_chkmngno',
  //            params: {
  //                chk_mng_no: chk_mng_no,
  //                sno: this.sno,
  //                struct_idx: this.struct_idx
  //            }
  //
  //        }).then((data) => {
  //            console.log(data);
  //            this.baseinfo = data.data[0];
  //
  //            // 部材以下情報の取得
  //            return this.$http({
  //                method: 'GET',
  //                url: 'api/index.php/CheckListSchAjax/get_chkdata',
  //                params: {
  //                    chk_mng_no: this.baseinfo.chk_mng_no,
  //                    rireki_no: this.baseinfo.rireki_no,
  //                    shisetsu_kbn: this.baseinfo.shisetsu_kbn
  //                }
  //            });
  //
  //        }).then((data) => {
  //            console.log(data);
  //            this.tenken_kasyo = JSON.parse(data.data[0].buzai_row);
  //
  //            // 施設の健全性の取得
  //            return this.$http({
  //                method: 'GET',
  //                url: 'api/index.php/CheckListSchAjax/get_shisetsu_judge',
  //                params: {
  //                    sno: this.sno,
  //                    chk_mng_no: this.baseinfo.chk_mng_no
  //                }
  //            });
  //
  //        }).then((data) => {
  //            console.log(data);
  //            this.shisetsujudges = JSON.parse(data.data[0].measures_judge_row);
  //
  //            // 写真の取得
  //            this.imageShow();
  //
  //        }).finally((data) => {
  //
  //            this.waitLoadOverlay = false;
  //
  //            // 過去の点検票は更新不可のため、確認ダイアログも不要
  //            this.change_flag = false;
  //
  //        });
  //    }

  // 部材の健全性を更新する
  updateBuzaiJudge() {
    var _btnIndex = this.btnIndex;
    for (var i = 0; Object.keys(this.tenken_kasyo.buzai).length > i; i++) {
      this.btnIndex = i;
      this.sum_judge();
    }
    this.btnIndex = _btnIndex;
  }

  buzaiClick(index) {
    this.sum_judge();
    this.btnIndex = index;
  }

  // 点検箇所健全性変更
  chg_tenkenkasyo_judge(row) {

    // 閲覧モードの場合は変更させない
    if (!this.editable) {
      return;
    }

    // 詳細調査完了の点検箇所は何もしない
    if (row.check_policy == this.houshin.houshins[3].houshin_cd) {
      return;
    }

    // 詳細調査時詳細調査、詳細調査済の場合以外は何もしない
    if (this.baseinfo.phase == "3") {
      if (!(row.registered_check_policy == this.houshin.houshins[2].houshin_cd || row.registered_check_policy == this.houshin.houshins[3].houshin_cd)) {
        return;
      }
    }

    // 差戻し考慮
    // 「点検」と「スクリーニング・詳細調査」では処理が異なる
    if (this.baseinfo.phase == "1") {

      // 点検中の場合
      // 選択された健全性と既に対象の有無になっているかと調査(方針)が比較対象
      // 健全性をⅢにした場合
      if (row.check_judge === 3) {
        // 元々スクリーニングしていた
        if (row.registered_screening_flg) {
          // スクリーニングの結果、詳細調査をせず完了の場合
          if (row.registered_check_policy == this.houshin.houshins[4].houshin_cd) {
            // スクリーニングやり直し
            row.screening_taisyou === 0;
            row.screening_flg = false;
            row.screening = 0;
            row.check_policy = this.houshin.houshins[1].houshin_cd;
            row.check_policy_str = this.houshin.houshins[1].houshin;
          } else {
            row.screening_taisyou === 1;
            row.screening_flg = true;
            row.screening = 1;
            // ３でスクリーニングがチェックは詳細調査
            row.check_policy = this.houshin.houshins[2].houshin_cd;
            row.check_policy_str = this.houshin.houshins[2].houshin;
          }
        } else {
          // 元々スクリーニングしていない(対象かもしれないが)
          // ３でスクリーニング未チェックはスクリーニング
          row.check_policy = this.houshin.houshins[1].houshin_cd;
          row.check_policy_str = this.houshin.houshins[1].houshin;
        }
        // 健全性がⅢ以外
      } else {
        // 調査していない、または対象でない場合、強制的に初期化する。
        row.screening_taisyou = 0;
        row.screening_flg = false;
        row.screening = 0;
        row.check_policy = this.houshin.houshins[0].houshin_cd;
        row.check_policy_str = this.houshin.houshins[0].houshin;
      }

    } else {
      // ↓↓↓ 元コード ↓↓↓
      // 健全性をⅢにした場合
      if (row.check_judge === 3) {
        // スクリーニングがチェック済
        if (row.screening_flg == true) {
          // ３でスクリーニングがチェックは詳細調査
          row.check_policy = this.houshin.houshins[2].houshin_cd;
          row.check_policy_str = this.houshin.houshins[2].houshin;
          // スクリーニングが未チェック
        } else {
          // ３でスクリーニング未チェックはスクリーニング
          row.check_policy = this.houshin.houshins[1].houshin_cd;
          row.check_policy_str = this.houshin.houshins[1].houshin;
        }

        // 健全性がⅢ以外
      } else {
        // スクリーニング対象の場合はスクリーニングにチェックを入れる
        if (row.screening_taisyou === 1) {
          row.screening_flg = true;
          row.screening = 1;
        }
        // ３でもなくスクリーニングでもないは－
        row.check_policy = this.houshin.houshins[4].houshin_cd;
        row.check_policy_str = this.houshin.houshins[4].houshin;
      }
      // ↑↑↑ 元コード ↑↑↑
    }

    // スクリーニングチェックをクリアした際に戻すため
    //        row.check_judge_pre = row.check_judge;
  }

  // スクリーニングチェックボックス
  // row:tenken_kasyo_row
  ChgScreening(row) {
    if (row.screening_flg == true) {
      row.screening = 1;
      if (row.check_judge == 3) {
        // スクリーニングチェックで３は詳細調査
        row.check_policy = this.houshin.houshins[2].houshin_cd;
        row.check_policy_str = this.houshin.houshins[2].houshin;
      } else {
        // スクリーニングチェックで３以外は－
        row.check_policy = this.houshin.houshins[0].houshin_cd;
        row.check_policy_str = this.houshin.houshins[0].houshin;
      }
    } else {
      // 健全性の診断により調査（方針）を変更
      if (row.check_judge == 3) {
        // スクリーニングチェックではなく３はスクリーニング
        row.check_policy = this.houshin.houshins[1].houshin_cd;
        row.check_policy_str = this.houshin.houshins[1].houshin;
      } else {
        // スクリーニング対象の場合
        if (row.screening_taisyou === 1) {
          // スクリーニング対象で３ではない場合はスクリーニングになる
          row.check_policy = this.houshin.houshins[1].houshin_cd;
          row.check_policy_str = this.houshin.houshins[1].houshin;

          if (row.check_judge_pre !== null) {
            row.check_judge = row.check_judge_pre;
          }
        } else {
          // スクリーニングチェックでもなく３でもないは－
          row.check_policy = this.houshin.houshins[0].houshin_cd;
          row.check_policy_str = this.houshin.houshins[0].houshin;
        }
      }
    }
  }

  // 点検箇所の健全性をコピー
  // スクリーニングチェッククリア時の再設定用として保持
  copyTenkenKasyoCheckJudge() {

    var _buzai = this.tenken_kasyo.buzai;
    var _buzai_detail_row;
    var _tenken_kasyo_row;

    for (var i = 0; Object.keys(_buzai).length > i; i++) {
      _buzai_detail_row = _buzai[i].buzai_detail_row;

      for (var j = 0; Object.keys(_buzai_detail_row).length > j; j++) {
        _tenken_kasyo_row = _buzai_detail_row[j].tenken_kasyo_row;

        for (var k = 0; Object.keys(_tenken_kasyo_row).length > k; k++) {
          _tenken_kasyo_row[k].check_judge_pre = _tenken_kasyo_row[k].check_judge;
        }
      }
    }
  }

  anchorScroll(anchor) {
    this.$location.hash(anchor);
    this.$anchorScroll();

    this.$location.url(this.$location.path());
  }

  windowopen(url) {
    this.$window.open(url, '健全性参考写真', 'width=850,height=800,menubar=no,toolbar=no,location=no,scrollbars=yes');
  }

  /**
   * 点検票を削除する
   * 新規入力可能な状態とする
   */
  DeleteTenkenData() {

    // 削除確認
    this.confirm('削除してよろしいですか？<br>(削除した点検票は元に戻すことはできません)', 0).then(() => {
      this.waitDeleteOverlay = true;

      // 点検票の削除
      this.$http({
        method: 'POST',
        url: 'api/index.php/CheckListEdtAjax/del_chkdata',
        data: {
          chk_mng_no: this.chk_mng_no,
        }

        // 2016-10-27 全景写真を削除しないように修正
        /*      }).then((data) => {
                console.log(data);

                this.deleteImage(null, 0);
                this.imageSave();*/

      }).then((data) => {

        // 防雪柵親データに子データの健全性を反映する
        return this.$http({
          method: 'POST',
          url: 'api/index.php/CheckListEdtAjax/merge_to_bssk_parent',
          data: {
            sno: this.sno
          }
        });

      }).then((data) => {

        // Excelの生成
        return this.$http({
          method: 'GET',
          url: 'api/index.php/OutputExcel/save_chkData',
          params: {
            sno: this.sno,
            chk_mng_no: this.baseinfo.chk_mng_no,
            struct_idx: this.baseinfo.struct_idx,
            excel_ver: this.excel_ver ? 1 : 0
          }
        });
      }).then(() => {
        return this.alert('削除', '削除が完了しました');
      }).finally(() => {
        this.waitDeleteOverlay = false;
        this.windowUnlock();
        this.$window.location.reload(false);
      });
    });
  }

  /**
   * 過去の点検結果を開く
   */
  OpenRireki() {
    // 編集モードの場合
    if (this.editable) {
      this.confirm('変更があった場合無視されますがよろしいですか？', 0).then(() => {
        this.tenken_rireki_modal = this.$uibModal.open({
          animation: true,
          templateUrl: 'views/tenken_rireki.html',
          controller: 'TenkenRirekiCtrl as tenken_rireki',
          size: "lg",
          resolve: {
            data: () => {
              return {
                sno: this.sno,
                struct_idx: this.struct_idx
              };
            }
          }
        });
      });
    } else {
      this.tenken_rireki_modal = this.$uibModal.open({
        animation: true,
        templateUrl: 'views/tenken_rireki.html',
        controller: 'TenkenRirekiCtrl as tenken_rireki',
        size: "lg",
        resolve: {
          data: () => {
            return {
              sno: this.sno,
              struct_idx: this.struct_idx
            };
          }
        }
      });
    }
    /*
        if (this.change_flag) {
          this.confirm('変更を破棄してよろしいですか？', 0).then(() => {
            this.tenken_rireki_modal = this.$uibModal.open({
              animation: true,
              templateUrl: 'views/tenken_rireki.html',
              controller: 'TenkenRirekiCtrl as tenken_rireki',
              size: "lg",
              resolve: {
                data: () => {
                  return {
                    sno: this.sno,
                    struct_idx: this.struct_idx
                  };
                }
              }
            });
          });

        } else {
          this.tenken_rireki_modal = this.$uibModal.open({
            animation: true,
            templateUrl: 'views/tenken_rireki.html',
            controller: 'TenkenRirekiCtrl as tenken_rireki',
            size: "lg",
            resolve: {
              data: () => {
                return {
                  sno: this.sno,
                  struct_idx: this.struct_idx
                };
              }
            }
          });
        }*/
  }

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

  // 説明コメントの表示
  ShowDescription(target) {

    // 点検フェーズの説明
    if (target == 'tenken') {
      this.alert('点検', '<br>点検調査票の最初の状態となります。<br>以下の項目を入力した後、確定保存を行ったタイミングでフェーズを移行します。<br>・点検実施年月日<br>・点検員<br>・対象となる点検箇所の健全性<br>・損傷の種類<br>・判定に至るまでの考え方<br>・点検時の写真', 0);
    }

    // スクリーニングフェーズの説明
    if (target == 'screening') {
      this.alert('スクリーニング', '<br>点検フェーズにて健全性がⅢの点検箇所がある場合、本フェーズに移行します。', 0);
    }

    // 詳細調査フェーズの説明
    if (target == 'investigate') {
      this.alert('詳細調査', '<br>スクリーニングフェーズにてスクリーニング実施後にも健全性がⅢの点検箇所がある場合、本フェーズに移行します。', 0);
    }

    // 点検完了フェーズの説明
    if (target == 'tenken_complete') {
      this.alert('点検完了', '<br>点検箇所の健全性にてⅢが無い、または措置されている状態となります。', 0);
    }

    // 措置状況の説明
    if (target == 'measure') {
      this.alert('措置の表記について', '<br>措置(A/B)<br><br>A…措置を完了した点検箇所の数<br>（点検時の健全性のⅢまたはⅣの件数）<br><br>B…措置が必要な点検箇所の数<br>（措置後の健全性がⅠまたはⅡの件数）<br><br>AとBの数が一致している場合、一時保存または確定保存を行ったタイミングで完了状態に移行します。<br><br>例）<br>支柱本体(Pph)の点検時の健全性がⅢの場合、Bの値が加算されます。（0/1）<br>支柱本体(Pph)の措置後の健全性をIまたはⅡにした場合、Aの値が加算されます。（1/1）', 0);
    }

  }

  /* 支柱INDEX追加 */
  //    ClickCreateIdx() {
  //
  //        this.confirm('防雪柵の支柱番号を追加しますか？', 1).then(() => {
  //            var last = this.data.bs_row.bs_info[this.data.bs_row.bs_info.length - 1];
  //            var arr = {
  //                struct_idx: last['struct_idx'] + 1,
  //                struct_no_s: last['struct_no_e'] + 1,
  //                struct_no_e: last['struct_no_e'] + 10,
  //            };
  //            this.data.bs_row.bs_info.push(arr);
  //            this.dipIdx = this.data.bs_row.bs_info.length - 1;
  //        });
  //    }

  // 画像データの履歴Noをセット
  setPictureRirekiNo(rireki_no) {
    for (var i = 0; i < this.picture.data.length; i++) {
      this.picture.data[i].rireki_no = rireki_no;
    }
  }

  /**
   * 画像ファイルを読み込みExif情報を取得する。
   * @param $file flow.jsのファイル
   * @param kasyo 写真を読み込んだ箇所オブジェクト
   */
  loadImageFile($file, $event, $flow, kasyo) {
    var file = $file.file;
    EXIF.getData(file, function () {
      // コールバック
      var dt = moment(file.exifdata.DateTime, "YYYY:MM:DD HH:mm:ss");
      kasyo.chousa_dt = dt.toDate();
      console.log(dt);
    });
    $flow.upload();
  }

  /**
   * 画像ファイルをサーバーへアップする。
   * @param $flow flowライブラリのオブジェクト
   * @param tenken_kasyo_row tenken_kasyoのオブジェクト nullの場合は全景
   * @param status 0:点検前 1:措置後
   */
  imageUpload($flow, tenken_kasyo_row, status) {
    if ($flow.files.length > 1) {
      $flow.files.shift();
    }
    if (tenken_kasyo_row) {
      $flow.opts.query = {
        mode: 'chk_picture',
        sno: this.sno,
        chk_mng_no: tenken_kasyo_row.chk_mng_no,
        //                rireki_no: tenken_kasyo_row.rireki_no,
        rireki_no: 0,
        buzai_cd: tenken_kasyo_row.buzai_cd,
        buzai_detail_cd: tenken_kasyo_row.buzai_detail_cd,
        tenken_kasyo_cd: tenken_kasyo_row.tenken_kasyo_cd,
        status: status,
        query_cd: this.picture.query_cd
      };
    } else {
      $flow.opts.query = {
        mode: 'zenkei_picture',
        sno: this.sno,
        shisetsu_cd: this.baseinfo.shisetsu_cd,
        shisetsu_ver: this.baseinfo.shisetsu_ver,
        struct_idx: this.baseinfo.struct_idx,
        query_cd: this.picture.query_cd
      };
    }
    $flow.upload();
    $flow.on("complete", () => {
      console.log("!Uploaded!!!!");
      $flow.files = [];
      this.imageShow();
    });
  }

  /**
   * サーバで処理した画像ファイルを展開する
   * @param $message サーバーレスポンス
   */
  imageUpdate(tenken_kasyo_row) {
    var data = this.picture.data;
    for (var i = 0; i < data.length; i++) {
      if (Number(data[i].chk_mng_no) === tenken_kasyo_row.chk_mng_no &&
        Number(data[i].rireki_no) === 0 &&
        Number(data[i].buzai_cd) === tenken_kasyo_row.buzai_cd &&
        Number(data[i].buzai_detail_cd) === tenken_kasyo_row.buzai_detail_cd &&
        Number(data[i].tenken_kasyo_cd) === tenken_kasyo_row.tenken_kasyo_cd &&
        Number(data[i].del) != 1
      ) {
        if (!tenken_kasyo_row.picture) {
          tenken_kasyo_row.picture = {};
        }
        if (Number(data[i].status) === 0) {
          tenken_kasyo_row.picture.src0 = data[i].path;
        } else {
          tenken_kasyo_row.picture.src1 = data[i].path;
        }
      }
    }
  }

  /**
   * 画像を表示するために、サーバに画像のパスを問い合わせ表示する。
   */
  async imageShow() {
    console.log("imageShow_in");
    let data = await this.$http({
      method: 'POST',
      url: 'api/index.php/PictureAjax/get_picture',
      data: {
        mode: 'zenkei_picture',
        sno: this.baseinfo.sno,
        //        shisetsu_cd: this.baseinfo.shisetsu_cd,
        //        shisetsu_ver: this.baseinfo.shisetsu_ver,
        struct_idx: this.baseinfo.struct_idx,
        query_cd: this.picture.query_cd
      }
    });

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
          }
        }
      });
    }

    data = await this.$http({
      method: 'POST',
      url: 'api/index.php/PictureAjax/get_picture',
      data: {
        mode: 'chk_picture',
        chk_mng_no: this.baseinfo.chk_mng_no,
        //                    rireki_no: this.baseinfo.rireki_no,
        rireki_no: 0,
        query_cd: this.picture.query_cd
      }
    });

    console.log("imageShow()1");
    // DBから取得した最後の写真以外は削除対象とする
    let arrayLength = this.zenkei_picture.data.length;
    for (var i = 0; arrayLength > i; i++) {
      if (i !== arrayLength - 1) {
        this.zenkei_picture.data[i].del = 1;
      } else if (this.zenkei_picture.data[i].del != 1) {
        this.zenkei_picture.image_src = this.zenkei_picture.data[i].path;
      }
    }
    console.log("imageShow()2");

    // 点検箇所写真
    let old_picture = this.picture;
    this.picture = data.data;
    // 古い情報とマージ（削除フラグはクライアント側しか持っていない）
    for (var i = 0; i < this.picture.data.length; i++) {
      for (var i2 = 0; i2 < old_picture.data.length; i2++) {
        var new_data = this.picture.data[i];
        var old_data = old_picture.data[i2];
        if (new_data.picture_cd == old_data.picture_cd) {
          new_data.del = old_data.del;
        }
      }
    }
    console.log("imageShow()3");
    angular.forEach(this.tenken_kasyo.buzai, (value, key) => {
      angular.forEach(value.buzai_detail_row, (value, key) => {
        angular.forEach(value.tenken_kasyo_row, (value, key) => {
          this.imageUpdate(value);
        });
      });
    });


    data = await this.$http({
      method: 'POST',
      url: 'api/index.php/PictureAjax/get_picture',
      data: {
        mode: 'sankou_photo',
        chk_mng_no: this.baseinfo.chk_mng_no,
      }
    });
    this.sankou_photos = data.data;

  }

  changeAllCheck() {
    for (let i = 0; i < this.sankou_photos.length; i++) {
      this.sankou_photos[i].exif_out = this.all_check;
    }
  }

  /**
   * 一時保存と確定保存時に画像を本登録する。
   */
  async imageSave() {
    angular.forEach(this.picture.data, (value, key) => {
      if (value.del != 1) {
        value.del = 0;
      }
    });
    angular.forEach(this.zenkei_picture.data, (value, key) => {
      if (value.del != 1) {
        value.del = 0;
      }
    });

    await this.$http({
      method: 'POST',
      url: 'api/index.php/PictureAjax/save_fix_picture',
      data: {
        chk_mng_no: this.baseinfo.chk_mng_no,
        //                rireki_no: this.baseinfo.rireki_no,
        rireki_no: 0,
        data: this.picture.data,
        // 全景写真用
        sno: this.baseinfo.sno,
        struct_idx: this.baseinfo.struct_idx,
        zenkei_picture_data: this.zenkei_picture.data,
        sankou_photos: this.sankou_photos,
      }
    });
  }

  /**
   * 画像を削除する。（サーバ処理は行わない。サーバ処理はimageSaveで行われる。）
   * this.picture.data.delに1を入れ、削除フラグを立てる。
   * @param tenken_kasyo_row tenken_kasyoのオブジェクト nullの場合は全景
   * @param status 0:点検時 1:措置時
   */
  deleteImage(tenken_kasyo_row, status) {
    if (tenken_kasyo_row) {
      var data = this.picture.data;
      for (var i = 0; i < data.length; i++) {
        if (Number(data[i].chk_mng_no) === tenken_kasyo_row.chk_mng_no &&
          Number(data[i].buzai_cd) === tenken_kasyo_row.buzai_cd &&
          Number(data[i].buzai_detail_cd) === tenken_kasyo_row.buzai_detail_cd &&
          Number(data[i].tenken_kasyo_cd) === tenken_kasyo_row.tenken_kasyo_cd &&
          Number(data[i].status) === status
        ) {
          this.picture.data[i].del = 1;
          if (status === 0) {
            tenken_kasyo_row.picture.src0 = null;
          } else {
            tenken_kasyo_row.picture.src1 = null;
          }
        }
      }
    } else {
      for (var i = 0; i < this.zenkei_picture.data.length; i++) {
        this.zenkei_picture.data[i].path = "";
        this.zenkei_picture.data[i].del = 1;
      }
      this.zenkei_picture.image_src = null;
    }
  }

  /**
   * 現場写真のダイアログを開く
   */
  openGenbaSyashin() {

    // 現場写真の対応建管と出張所を渡す
    var dogen_syucchoujo = Array();
    dogen_syucchoujo['dogen_cd'] = this.baseinfo.dogen_cd;
    dogen_syucchoujo['syucchoujo_cd'] = this.baseinfo.syucchoujo_cd;

    this.genba_syashin_modal = this.$uibModal.open({
      animation: true,
      templateUrl: 'views/genba_syashin.html',
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
   * @param status 0:点検時 1:措置時
   */
  dragDrop(evt, jq_evt, model, url, status) {
    $(evt.target).find(".drop").removeClass('drag-over-img');
    var promise;
    if (model) {
      promise = this.$http({
        method: 'POST',
        url: 'api/index.php/PictureAjax/image_from_url',
        data: {
          mode: 'chk_picture',
          sno: this.sno,
          chk_mng_no: model.chk_mng_no,
          rireki_no: model.rireki_no,
          buzai_cd: model.buzai_cd,
          buzai_detail_cd: model.buzai_detail_cd,
          tenken_kasyo_cd: model.tenken_kasyo_cd,
          query_cd: this.picture.query_cd,
          status: status,
          url: url
        }
      });
    } else {
      promise = this.$http({
        method: 'POST',
        url: 'api/index.php/PictureAjax/image_from_url',
        data: {
          mode: 'zenkei_picture',
          sno: this.sno,
          shisetsu_cd: this.baseinfo.shisetsu_cd,
          shisetsu_ver: this.baseinfo.shisetsu_ver,
          struct_idx: this.baseinfo.struct_idx,
          query_cd: this.picture.query_cd,
          url: url
        }
      });
    }
    promise.then((data) => {
      this.imageShow();
    });

  }


  openGenbaSyashinSankou() {

    // 引数としてのオブジェクト
    let data = {
      syucchoujo_cd: this.syucchoujo_cd,
      dogen_cd: this.dogen_cd
    };

    let modal = this.$uibModal.open({
      animation: true,
      templateUrl: 'views/modal_genba_syasin_sel2.html',
      controller: 'ModalGenbaSyasinCtrl as modal',
      size: "lg",
      windowClass: "modal_window",
      resolve: {
        data: () => {
          return data;
        }
      }
    });

    modal.result.then((data) => {
      if (data) {
        // データの変更
        this.sankou_photos = this.sankou_photos.concat(data.photo_list);
      }
    });
  }

  async fileUploadSuccessSankou($file, message, $flow) {

    console.log("!Uploaded!!!!");
    console.log($file);
    message = JSON.parse(message);
    console.log(message);

    // 拡張子のチェック
    let ext = message.path.match(/(.*)(?:\.([^.]+$))/)[2].toUpperCase();
    if (!ext.match(/JPG|JPEG|PNG|GIF|BMP/)) {
      await new Promise((resolve) => {
        $.alert({
          title: "ファイル選択エラー",
          content: "画像を選択してください",
          animation: "RotateYR",
          buttons: {
            OK: {
              btnClass: "btn-blue",
              action: function () {
                resolve(true);
              }
            }
          }
        });
      });
      return;
    }

    let photo = {};
    photo.file_name = $file.name;
    photo.path = message.path;
    photo.lon = message.lon;
    photo.lat = message.lat;
    photo.exif_dt = message.date;
    this.sankou_photos.push(photo);
    if ($flow.files[$flow.files.length - 1].name == $file.name) {
      $flow.files = [];
    }

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
  /*
    alert(title, msg) {
      $.alert({
        title: title,
        content: msg,
        confirmButtonClass: 'btn-info',
        confirmButton: 'OK',
        animation: 'RotateYR',
      });
    }
  */

  /**
   * ページ遷移用メソッド
   * @param link 遷移先のアドレス
   */
  location(link) {
    // 編集モードの場合
    if (this.editable) {
      this.confirm('変更があった場合無視されますがよろしいですか？', 0).then(() => {
        this.$location.path(link);
        this.windowUnlock();
        //this.$window.location.reload(false);
      });
    } else {
      this.$location.path(link);
      this.windowUnlock();
      //this.$window.location.reload(false);
    }

    /*
        if (this.change_flag) {
          this.confirm('変更を破棄してよろしいですか？', 0).then(() => {
            this.$location.path(link);
            this.windowUnlock();
            //this.$window.location.href = link;
            this.$window.location.reload(false);
          });
        } else {
          this.$location.path(link);
          this.windowUnlock();
          //this.$window.location.href = link;
          this.$window.location.reload(false);
        }
    */
  }

  locationGdh() {
    this.windowUnlock();
    var url = this.gdh_url + "#/search";
    this.$window.location.href = url;
  }

  /**
   * 点検票をエクセルに出力する
   */
  //  outputExcel() {
  //
  //    this.$http({
  //      method : 'POST',
  //      url : 'api/index.php/OutputExcel/out_chkData',
  //      data: {
  //        sno: this.sno,
  //        chk_mng_no: this.baseinfo.chk_mng_no,
  //        struct_idx: this.baseinfo.struct_idx,
  //        excel_ver: this.excel_ver
  //      }
  //    });
  //
  //  }

  // Excel2013へ出力チェックボックスクリック時
  excel_ver_click() {
    this.$cookies.excel_ver = this.excel_ver;
    //console.debug(this.excel_ver);
  }

  // 戻るクリックイベント
  backUrl(url) {
    this.windowUnlock();
    this.location(url);
  }

  /***
   * 差戻し処理
   *  差戻しボタンをクリックしたときに呼ばれる。
   *  強制的に点検票に戻す
   ***/
  TenkenRemand() {
    // 削除確認
    this.confirm('点検票を差戻しますか？', 0).then(() => {
      this.waitRemandOverlay = true;
      // 点検票の削除
      this.$http({
        method: 'POST',
        url: 'api/index.php/CheckListEdtAjax/tenkenRemand',
        data: {
          chk_mng_no: this.chk_mng_no
        }
      }).then(() => {
        return this.alert('差戻し', '差戻しが完了しました');
      }).finally(() => {
        this.waitRemandOverlay = false;
        this.windowUnlock();
        this.$window.location.reload(false);
      });
    });
  }
}

//['支柱本体','支柱継手部（ジョイントA）','支柱継手部（ジョイントB）','路面境界面']
let angModule = require('../app.js');
angModule.angApp.controller('TenkenCtrl', ['$scope', '$http', '$route', '$routeParams', '$location', '$anchorScroll', '$interval', '$window', '$uibModal', '$q', '$cookies', function ($scope, $http, $route, $routeParams, $location, $anchorScroll, $interval, $window, $uibModal, $q, $cookies) {
  return new TenkenCtrl($scope, $http, $route, $routeParams, $location, $anchorScroll, $interval, $window, $uibModal, $q, $cookies);
}]);
