"use strict";

var BaseCtrl = require("./base.js");
var BaseCtrl = require("./base.js");

class FameditCtrl extends BaseCtrl {
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

    this.gdh_url = localStorage.getItem("RTN_URL");

    // GET引数でもらう
    this.sno = $routeParams.sno;

    // 初期処理
    this.initVariable();

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

      return this.$http({
        method: 'GET',
        url: 'api/index.php/FamEditAjax/initEditMain',
        params: {
          sno: this.sno,
        }
      });
    }).then((data) => {

      var json = data.data;

      this.shisetsu = json.shisetsu[0]; // 基本情報
      this.syucchoujo = json.syucchoujo[0]; // 出張所
      this.setKeishikiKubunArr(json); // 形式区分セット
      this.setMst(json); // 各施設マスタ設定
      this.setInclude(this.shisetsu.shisetsu_kbn); // includeするファイルの指定
      this.secchi_nendo = [];
      this.secchi_nendo = json.wareki_list; // 和暦リスト
      this.zenkei_picture = []; // 全景写真の初期化
      this.zumen = {};

      this.teiki_patrol = json.teiki_patrol;// 定期パトロール
      this.tpat_url = json.tpat_url; // 定期パトロールのURL（オリジンの後ろの部分）

      this.houtei = json.houtei; // 法定点検
      for (let iHoutei = 0; iHoutei < this.houtei.length; iHoutei++) {
        // ファイルパスからファイル名を取得して保持する
        for (let iAttachFile = 0; iAttachFile < this.houtei[iHoutei].attach_files.length; iAttachFile++) {
          this.houtei[iHoutei].attach_files[iAttachFile].file_name = "";
          if (this.houtei[iHoutei].attach_files[iAttachFile].file_path) {
            this.houtei[iHoutei].attach_files[iAttachFile].file_name = this.houtei[iHoutei].attach_files[iAttachFile].file_path.substring(this.houtei[iHoutei].attach_files[iAttachFile].file_path.lastIndexOf('/') + 1, this.houtei[iHoutei].attach_files[iAttachFile].file_path.length);
          }
        }
      }
      // 台帳作成日編集可否
      this.desabled_create_dt = true;
      // 台帳データ
      if (json.daichou.length == 0) {
        // 新規の場合は、snoとshisetsu_kbnをセット
        this.daichou = {};
        this.daichou.sno = this.sno;
        this.daichou.shisetsu_kbn = this.shisetsu.shisetsu_kbn;
        // 新規なので台帳作成日の入力可
        this.desabled_create_dt = false;
      } else {
        // 既存の場合は、施設区分をセット
        this.daichou = json.daichou[0];
        // UPD 20200108 hirano 文字列にするために空白をくっつけたのでtrim
        this.daichou.d_hokuden_kyakuban = this.daichou.d_hokuden_kyakuban.substr(0, this.daichou.d_hokuden_kyakuban.length - 1);
        // 更新者は表示上「所属：更新者名」とする。
        if (json.busyo_row != '') {
          this.daichou.update_account_nm = json.busyo_row['busyo_mei'] + ":" + this.daichou.update_account_nm

        }

        // 作成日の整形
        if (this.daichou.create_dt) {
          this.daichou.create_dt = this.formatYmd(this.daichou.create_dt);
        } else {
          // 作成日が無い場合は入力可
          this.desabled_create_dt = false;
        }
        this.daichou.shisetsu_kbn = this.shisetsu.shisetsu_kbn;
        this.chk_humei = false;
        if (this.daichou.humei == 1) {
          this.chk_humei = true;
        }

        // 個別変数定義
        // データがある場合の施設毎の項目設定
        let shisetsu_kbn = this.shisetsu.shisetsu_kbn;
        if (this.master.shisetsu[shisetsu_kbn]) {
          this.chgNumDaichou(this.master.shisetsu[shisetsu_kbn].num);
          // プルダウンセット
          this.setPullDown(this.master.shisetsu[shisetsu_kbn].pulldown);
          this.formatYmdItem(this.master.shisetsu[shisetsu_kbn].date);
        }
      }

      // 共通数値項目変換
      this.chgNumShisetsu(this.master.shisetsu[0].num);

      // 緑化樹木の場合のみデータの有無にかからず設定
      if (this.shisetsu.shisetsu_kbn == 16) {
        // 樹木関係初期化
        this.daichou.kouboku_jumoku_cd = [];
        this.daichou.kouboku_num = [];
        this.daichou.tyuuteiboku_jumoku_cd = [];
        this.daichou.tyuuteiboku_num = [];
        for (var i = 1; i <= 10; i++) {
          this.daichou.kouboku_jumoku_cd[i] = this.daichou['kouboku_jumoku_cd' + i];
          this.daichou.kouboku_num[i] = this.daichou['kouboku_num' + i];
          this.daichou.tyuuteiboku_jumoku_cd[i] = this.daichou['tyuuteiboku_jumoku_cd' + i];
          this.daichou.tyuuteiboku_num[i] = this.daichou['tyuuteiboku_num' + i];
        }
      }

      // 浸出装置の場合
      if (this.shisetsu.shisetsu_kbn == 20) {
        // ランニングコスト入力欄初期化
        this.r_nendo_row = null;
        this.yakuzai_nm = "";
        this.sanpu_ryou = "";
        this.sanpu_cost = "";
        this.denki_cost = "";
        this.keiyaku_denryoku = "";
        this.kouka_hani_menseki = "";
        this.tekiyou = "";
        // 修理費入力欄初期化
        //        this.s_nendo_row = {};
        this.repair_cost = "";
        this.repair_naiyou = "";
        // ランニングコストデータ
        if (json.running_cost_arr.length == 0) {
          this.running_cost_arr = [];
          this.running_cost_arr.sno = this.sno;
        } else {
          this.running_cost_arr = json.running_cost_arr;
        }
        // 修理費データ
        if (json.repair_cost_arr.length == 0) {
          this.repair_cost_arr = [];
          this.repair_cost_arr.sno = this.sno;
        } else {
          this.repair_cost_arr = json.repair_cost_arr;
        }
      } else {
        this.running_cost_arr = [];
        this.repair_cost_arr = [];
      }

      // 施設区分と各種点検の組み合わせ一覧
      this.patrol_types = json.patrol_types;

      // 施設区分に応じて実施する点検の表示欄のテンプレートを挿入
      for (const shisetsu_kbn of Object.keys(this.master.shisetsu)) {
        if (this.isTeikiPatTarget(Number(shisetsu_kbn))) {
          const denkiIndex = this.master.shisetsu[shisetsu_kbn].include_tpl.daichou_rireki.indexOf(this.DENKI_TEMPLATE_PATH);
          // 電気のテンプレートがある施設の場合は、その後ろ（なければ先頭）に挿入
          this.master.shisetsu[shisetsu_kbn].include_tpl.daichou_rireki.splice(denkiIndex + 1, 0, this.TEIKI_PAT_TEMPLATE_PATH);
        }
        if (this.isHouteiTarget(Number(shisetsu_kbn))) {
          this.master.shisetsu[shisetsu_kbn].include_tpl.daichou_rireki.unshift(this.HOUTEI_TEMPLATE_PATH);
        }
        if (this.isHuzokubutsuTarget(Number(shisetsu_kbn))) {
          this.master.shisetsu[shisetsu_kbn].include_tpl.daichou_rireki.unshift(this.HUZOKUBUTSU_TEMPLATE_PATH);
        } 
      }


      // 点検データ
      if (this.isHuzokubutsuTarget(this.shisetsu.shisetsu_kbn)) {
        if (json.huzokubutsu.length == 0) {
          this.huzokubutsu = [];
        } else {
          this.huzokubutsu = json.huzokubutsu;
        }
      }

      // 電気通信結果を取得
      if (this.shisetsu.shisetsu_kbn == 2 || this.shisetsu.shisetsu_kbn == 6
        || this.shisetsu.shisetsu_kbn == 7 || this.shisetsu.shisetsu_kbn == 8
        || this.shisetsu.shisetsu_kbn == 9 || this.shisetsu.shisetsu_kbn == 10
        || this.shisetsu.shisetsu_kbn == 14 || this.shisetsu.shisetsu_kbn == 21) {
        this.chk_denki = [];
        if (json.chk_denki.length > 0) {
          this.chk_denki = json.chk_denki;
        }
        this.ele_url = json.ele_url;
      }

      // 点検補修履歴データ
      if (json.hosyuu_rireki.length == 0) {
        this.hosyuu = [];
        this.hosyuu.sno = this.sno;
      } else {
        this.hosyuu = json.hosyuu_rireki;
      }
      this.gdh_db_data = "";
      this.nextGdhIdx = json.next_gdh_idx;

      // 案内標識DBデータ
      if (json.gdh_db_data.length == 0) {
        this.gdh_db_data = [];
        this.gdh_db_data.sno = this.sno;
      } else {
        // データ取得
        this.gdh_db_data = JSON.parse(json.gdh_db_data[0].response_info);
        for (let i = 0; i < this.gdh_db_data.length; i++) {
          for (let j = 0; j < this.gdh_db_data[i].detail.length; j++) {
            // 西暦を和暦に変換
            // if (this.gdh_db_data[i].detail[j].yotei_nendo_yyyy) {
            //   var value = this.gdh_db_data[i].detail[j].yotei_nendo_yyyy + "/01/01";
            //   this.gdh_db_data[i].detail[j].yotei_nendo_yyyy = this.$filter('wareki')(value, "WWWW");
            // }
            // 高速道路ナンバリング表示
            if (this.gdh_db_data[i].detail[j].taisaku_kbn_cd == 4) {
              this.gdh_db_data[i].detail[j].taisaku_kbn = "高速道路ナンバリング";
            }
          }
        }
      }

      this.imageShow();
      this.$http({
        method: 'POST',
        url: 'api/index.php/ZumenAjax/getZumen',
        data: {
          zumen: {
            sno: this.shisetsu.sno,
          }
        }
      }).then((data) => {
        if (data.data.length) {
          this.zumen = data.data[0];
        } else {
          this.zumen = {};
        }
      });

      // 設定ありinitMap
      setTimeout(
        () => {
          this.initMap();

          // 案内標識からきた場合スクロール
          if (this.gdh_url) {
            this.anchorScroll('gdh_db');
          }

        }, 1000);
    });
  }

  // 初期処理
  initVariable() {
    // 変数の初期化
    this.text = "";
    this.shisetsuinfo = false;
    this.tenkenrireki = false;
    this.other = false;
    this.photos = [{}];

    // map絡み
    this.displayIndex = -1;
    this.map = null;
    this.projection3857 = new OpenLayers.Projection("EPSG:3857");
    this.projection4326 = new OpenLayers.Projection("EPSG:4326");
    this.feature = null;
    this.vectorLayer = null;
    // メッセージ配列
    this.err_message = [];
    // ○○中表示
    this.waitOverlay = false; // 保存中です
    this.waitUploadOverlay = false; // アップロード中のオーバーレイ（スピナー）の表示をコントロールするフラグ

    // 補修履歴入力欄初期化
    this.chk_year = null;
    this.chk_naiyou = "";
    this.hosyuu_year = null;
    this.hosyuu_naiyou = "";
    // 閲覧モード
    this.editable = false;
    this.patrol_types = {
      houtei: [],
      huzokubutsu: [],
      teiki_pat: [],
    };
    // 定数を設定
    this.constSet();
  }

  // 施設区分が附属物点検の実施対象のものかどうかを返す
  isHuzokubutsuTarget(shisetsuKbn) {
    if (this.patrol_types.huzokubutsu.indexOf(Number(shisetsuKbn)) > -1) {
      return true;
    }
    return false;
  }

  // 施設区分が法定点検の実施対象のものかどうかを返す
  isHouteiTarget(shisetsuKbn) {
    if (this.patrol_types.houtei.indexOf(Number(shisetsuKbn)) > -1) {
      return true;
    }
    return false;
  }

  // 施設区分が定期パトロールの実施対象のものかどうかを返す
  isTeikiPatTarget(shisetsuKbn) {
    if (this.patrol_types.teiki_pat.indexOf(Number(shisetsuKbn)) > -1) {
      return true;
    }
    return false;
  }

  // 定数を設定
  constSet() {
    // 設置年度の値
    // var date = new Date(); // 現在日付を取得
    // var year = date.getFullYear();
    // this.secchi_nendo = [];
    // for (var i = year; i > 1989; i--) {
    //   this.secchi_nendo.push({
    //     "year": i,
    //     "gengou": "H" + (i - 1988) + "年"
    //   });
    // }
    // this.secchi_nendo.push({
    //   "year": 1989,
    //   "gengou": "H元年"
    // });
    // // ADD 昭和を追加 20161024 -->
    // for (var i = 1988; i >= 1965; i--) {
    //   this.secchi_nendo.push({
    //     "year": i,
    //     "gengou": "S" + (i - 1925) + "年"
    //   });
    // }

    // 固定値を設定
    Object.defineProperties(this, {
      TEIKI_PAT_TEMPLATE_PATH: {
        value: 'views/daichou/tenken_teiki_patrol.html'
      },
      HOUTEI_TEMPLATE_PATH: {
        value: 'views/daichou/tenken_houtei.html'
      },
      HUZOKUBUTSU_TEMPLATE_PATH: {
        value: 'views/daichou/tenken_huzokubutsu.html'
      },
      DENKI_TEMPLATE_PATH: {
        value: 'views/daichou/tenken_denki.html'
      },
      TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH: {
        value: 'views/daichou/tenken_hosyuu_rireki.html'
      }
    })

    /***
     * num:数値項目名…数値項目を明示的に数値化
     * num_str:数値項目日本語名
     * pulldown:ng-modelがrowであるプルダウン項目名…rowを設定
     * date:日付項目名…YYYY/MM/DDに整形
     ***/
    this.master = {};
    // ※法定点検・附属物点検・定期パトロールのテンプレートの使用/不使用はマスタから取得するため、
    // この時点では情報を持っていないので、initのAPIの結果を受信してから追加する
    this.master.shisetsu = {
      "0": { // 共通
        num: ['sp', 'sp_to', 'encho'],
        num_str: ['測点(自)', '測点(至)', '延長'],
        pulldown: [],
        date: [],
      },
      "1": {
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/hyoushiki.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ],
          gdh_tplnm: 'views/daichou/gdh.html'
        }
      },
      "2": {
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/jyouhouban_denko.html',
          daichou_rireki: [
            this.DENKI_TEMPLATE_PATH,
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "3": { // 照明
        num: ['shichuu_kou', 'haba', 'ramp_num'],
        num_str: ['支柱高', '幅', 'ランプ数'],
        pulldown: [
          ['pole_kikaku_arr', 'pole_kikaku_cd', 'pole_kikaku_cd', 'pole_kikaku_row'],
          ['secchi_nendo', 'year', 'tougu_secchi_yyyy', 'tougu_secchi_row']
        ],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/syoumei.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "4": { // 防雪柵
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/bousetsu.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "5": { // スノーポール
        num: [],
        num_str: [],
        pulldown: [['secchi_nendo', 'year', 'yh_secchi_yyyy', 'secchi_row']],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/snowpole.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "6": {
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/kisyou_kanshi.html',
          daichou_rireki: [
            this.DENKI_TEMPLATE_PATH,
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "7": {
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/kisyou_jushin.html',
          daichou_rireki: [
            this.DENKI_TEMPLATE_PATH,
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "8": {
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/kisyou_chuukei.html',
          daichou_rireki: [
            this.DENKI_TEMPLATE_PATH,
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "9": { // 観測局
        num: [],
        num_str: [],
        pulldown: [['secchi_nendo', 'year', 'kisyouda_rgst_yyyy', 'kisyoudai_rgst_row']],
        date: ['usetsuryou_kei_kentei_dt', 'huukouhuusoku_kei_kentei_dt'],
        include_tpl: {
          daichou_tplnm: 'views/daichou/kisyou_kansoku.html',
          daichou_rireki: [
            this.DENKI_TEMPLATE_PATH,
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "10": {
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/kisyou_camera.html',
          daichou_rireki: [
            this.DENKI_TEMPLATE_PATH,
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "11": {
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/jyouhouban_ctype.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "12": {
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/syadanki.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "13": { // ドット線
        num: ['secchi_honsuu'],
        num_str: ['設置本数'],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/dot.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "14": { // トンネル
        num: ['syoumei_kisuu', 'push_button_num', 'hijou_tel_num', 'keihou_hyoujiban_num', 'tenmetsutou_num', 'syoukaki_num', 'yuudou_hyoujiban_num', 'lamp_su1', 'lamp_su2', 'lamp_su3', 'lamp_su4', 'lamp_su5', 'lamp_su6', 'lamp_su7', 'lamp_su8', 'lamp_su9', 'lamp_su10', 'lamp_su11', 'lamp_su12'],
        num_str: ['照明基数', '押ボタン数', '非常電話数', '警報表示板数', '点滅灯数', '消火器数', '誘導表示板数', '照明数1', '照明数2', '照明数3', '照明数4', '照明数5', '照明数6', '照明数7', '照明数8', '照明数9', '照明数10', '照明数11', '照明数12'],
        pulldown: [],
        date: ['musen_kyoka_dt'],
        include_tpl: {
          daichou_tplnm: 'views/daichou/tonneru.html',
          daichou_rireki: [
            this.DENKI_TEMPLATE_PATH,
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "15": { // 駐車公園
        num: ['jigyouhi', 'syadou_hosou_menseki', 'hodou_hosou_menseki', 'norimen_ryokuchi_menseki', 'tyuusyadaisuu_oogata', 'tyuusyadaisuu_hutsuu', 'kenjousya_dai', 'kenjousya_syou', 'shinsyousya_dai', 'shinsyousya_syou', 'syoumei_hashira_num', 'syoumei_kyuu_num', 'syokuju_kouboku', 'syokuju_tyuuteiboku'],
        num_str: ['事業費', '車道舗装面積', '歩道舗装面積', '法面・緑地面積', '駐車台数 大型車', '駐車台数 普通車', '健常者 大', '健常者 小', '身障者 大', '身障者 小', '照明柱数', '照明球数', '植樹 高木', '植樹 中低木'],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/chuusya.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "16": { // 緑化公園
        num: ['bangou', 'akimasu_menseki', 'kouboku_num1', 'kouboku_num2', 'kouboku_num3', 'kouboku_num4', 'kouboku_num5', 'kouboku_num6', 'kouboku_num7', 'kouboku_num8', 'kouboku_num9', 'kouboku_num10', 'tyuuteiboku_num1', 'tyuuteiboku_num2', 'tyuuteiboku_num3', 'tyuuteiboku_num4', 'tyuuteiboku_num5', 'tyuuteiboku_num6', 'tyuuteiboku_num7', 'tyuuteiboku_num8', 'tyuuteiboku_num9', 'tyuuteiboku_num10'],
        num_str: ['番号', '空枡面積', '高木1 本数', '高木2 本数', '高木3 本数', '高木4 本数', '高木5 本数', '高木6 本数', '高木7 本数', '高木8 本数', '高木9 本数', '高木10 本数', '中低木1 本数', '中低木2 本数', '中低木3 本数', '中低木4 本数', '中低木5 本数', '中低木6 本数', '中低木7 本数', '中低木8 本数', '中低木9 本数', '中低木10 本数'],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/ryokuka.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "17": {
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/rittai.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "18": { // 擁壁
        num: ['hekikou_kou', 'hekikou_tei'],
        num_str: ['壁高(高)', '壁高(低)'],
        pulldown: [['secchi_nendo', 'year', 'genkyou_nendo_yyyy', 'genkyou_nendo_row']],
        date: ['tuukoudeme_dt1', 'tuukoudeme_dt2', 'tuukoudeme_dt3'],
        include_tpl: {
          daichou_tplnm: 'views/daichou/youheki.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "19": { // 法面
        num: ['noridaka_kou', 'noridaka_tei', 'norimen_menseki'],
        num_str: ['法高(高)', '法高(低)', '法面面積'],
        pulldown: [['secchi_nendo', 'year', 'genkyou_nendo_yyyy', 'genkyou_nendo_row']],
        date: ['tuukoudeme_dt1', 'tuukoudeme_dt2', 'tuukoudeme_dt3'],
        include_tpl: {
          daichou_tplnm: 'views/daichou/norimen.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "20": { // 浸出装置
        num: ['j_jigyou_cost', 't_youryou_chijou', 't_youryou_chika', 'k_all_kadou_hour', 'y_kouka_entyou', 'y_kouka_fukuin'],
        num_str: ['縦断勾配', '曲線半径', '事業費', '容量地上分', '容量地下分', '総稼働時間', '効果延長', '効果幅員'],
        pulldown: [['secchi_nendo', 'year']],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/shinsyutsu.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "21": { // 浸出装置
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/roadheating.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH
          ]
        }
      },
      "22": { // 冠水警報表示
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/kansui_keihou.html',
          daichou_rireki: [
            this.DENKI_TEMPLATE_PATH,
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "23": { // トンネル警報表示
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/tonneru_keihou.html',
          daichou_rireki: [
            this.DENKI_TEMPLATE_PATH,
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "24": { // 橋梁
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/kyouryou.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "25": { // トンネル
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/tonneru2.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "26": { // 切土
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/kirido.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "27": { // 歩道
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/hodou.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "28": { // 落石崩壊
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/rakuseki_houkai.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "29": { // 横断歩道橋
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/oudanhodoukyou.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "30": { // シェッド等
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/shed.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "31": { // 大型カルバート
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/culvert.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "32": { // 岩盤崩壊
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/gamban_houkai.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "33": { // 急流河川
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/kyuuryuu_kasen.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "34": { // 盛土
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/morido.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "35": { // 道路標識（門型）
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/hyoushiki_mongata.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      },
      "36": { // 道路情報提供装置（門型）
        num: [],
        num_str: [],
        pulldown: [],
        date: [],
        include_tpl: {
          daichou_tplnm: 'views/daichou/jyouhouban_mongata.html',
          daichou_rireki: [
            this.TENKEN_HOSYUU_RIREKI_TEMPLATE_PATH,
          ]
        }
      }
    };
  }

  setInclude(shisetsu_kbn) {
    // 共通テンプレート
    this.tuushinkaisen_denki = 'views/daichou/tuushinkaisen_denki.html'; // 通信回線・電気タブ
    this.denki = 'views/daichou/denki.html'; // 電気タブ

    if (shisetsu_kbn === '20') {
      this.running_cost_tplnm = 'views/daichou/running_cost.html'; // ランニングコスト
      this.repair_cost_tplnm = 'views/daichou/repair_cost.html'; // 修理費
    }
  }

  /***
   * 日付フォーマット
   * DBから取得したtimestampを
   * yyyy-mm-ddで返却
   *
   * 空の場合、空を返却
   ***/
  formatYmd(dt) {
    if (!dt) {
      return "";
    }
    var d = new Date(dt);
    var format_dt = "";
    format_dt = d.getFullYear() + "-" + this.zeroPadding(Number(d.getMonth()) + 1, 2) + "-" + this.zeroPadding(Number(d.getDate()), 2);
    return format_dt;
  }

  /***
   *  ゼロパディング
   *  引数
   *    パディングする値
   *    桁数
   ***/
  zeroPadding(str, keta) {
    if (!str) {
      return "";
    }
    if (!Number(keta)) {
      return "";
    }
    var zero = "";
    var tmp = "";
    for (var i = 0; i < Number(keta); i++) {
      zero = zero + "0";
    }
    tmp = zero + str;
    return tmp.slice(-keta);
  }

  /*****************/
  /** マスタ設定群 ***/
  /*****************/
  // マスタセット
  setMst(json) {
    // 施設によって使用マスタが異なる
    if (this.shisetsu.shisetsu_kbn == 1) {
      this.setMstDH(json); // 道路標識
    } else if (this.shisetsu.shisetsu_kbn == 2) {
      this.setMstJD(json); // 道路情報提供装置
    } else if (this.shisetsu.shisetsu_kbn == 3) {
      this.setMstSS(json); // 道路照明施設
    } else if (this.shisetsu.shisetsu_kbn == 4) {
      this.setMstBS(json); // 防雪柵
    } else if (this.shisetsu.shisetsu_kbn == 5) {
      this.setMstYH(json); // スノーポール
    } else if (this.shisetsu.shisetsu_kbn == 6) {
      this.setMstKA(json); // 監視局
    } else if (this.shisetsu.shisetsu_kbn == 7) {
      this.setMstKB(json); // 受信局
    } else if (this.shisetsu.shisetsu_kbn == 8) {
      this.setMstKC(json); // 中継局
    } else if (this.shisetsu.shisetsu_kbn == 9) {
      this.setMstKD(json); // 観測局
    } else if (this.shisetsu.shisetsu_kbn == 10) {
      this.setMstKI(json); // カメラ
    } else if (this.shisetsu.shisetsu_kbn == 11) {
      this.setMstJH(json); // 情報板C型
    } else if (this.shisetsu.shisetsu_kbn == 12) {
      this.setMstSD(json); // 遮断機
    } else if (this.shisetsu.shisetsu_kbn == 13) {
      this.setMstDT(json); // ドット線
    } else if (this.shisetsu.shisetsu_kbn == 14) {
      this.setMstTT(json); // トンネル
    } else if (this.shisetsu.shisetsu_kbn == 15) {
      this.setMstCK(json); // 駐車公園
    } else if (this.shisetsu.shisetsu_kbn == 16) {
      this.setMstSK(json); // 緑化樹木
    } else if (this.shisetsu.shisetsu_kbn == 17) {
      this.setMstBH(json); // 立体横断
    } else if (this.shisetsu.shisetsu_kbn == 18) {
      this.setMstDY(json); // 擁壁
    } else if (this.shisetsu.shisetsu_kbn == 19) {
      this.setMstDN(json); // 法面
    } else if (this.shisetsu.shisetsu_kbn == 20) {
      this.setMstTS(json); // 浸出装置
    } else if (this.shisetsu.shisetsu_kbn == 21) {
      // ロードヒーティング
      this.setMstRH(json);
    } else if (this.shisetsu.shisetsu_kbn == 22) {
      this.setMstKK(json); // 冠水警報表示
    } else if (this.shisetsu.shisetsu_kbn == 23) {
      this.setMstTK(json); // トンネル警報表示
    } else if (this.shisetsu.shisetsu_kbn == 24) {
      this.setMstBR(json); // 橋梁
    } else if (this.shisetsu.shisetsu_kbn == 25) {
      this.setMstTU(json); // トンネル
    } else if (this.shisetsu.shisetsu_kbn == 26) {
      this.setMstDK(json); // 切土
    } else if (this.shisetsu.shisetsu_kbn == 27) {
      this.setMstHD(json); // 歩道
    } else if (this.shisetsu.shisetsu_kbn == 28) {
      this.setMstKR(json); // 落石崩壊
    } else if (this.shisetsu.shisetsu_kbn == 29) {
      this.setMstFB(json); // 横断歩道橋
    } else if (this.shisetsu.shisetsu_kbn == 30) {
      this.setMstSH(json); // シェッド等
    } else if (this.shisetsu.shisetsu_kbn == 31) {
      this.setMstCL(json); // 大型カルバート
    } else if (this.shisetsu.shisetsu_kbn == 32) {
      this.setMstKG(json); // 岩盤崩壊
    } else if (this.shisetsu.shisetsu_kbn == 33) {
      this.setMstKK(json); // 急流河川
    } else if (this.shisetsu.shisetsu_kbn == 34) {
      this.setMstDF(json); // 盛土
    } else if (this.shisetsu.shisetsu_kbn == 35) {
      this.setMstHM(json); // 道路標識（門型）
    } else if (this.shisetsu.shisetsu_kbn == 36) {
      this.setMstJM(json); // 道路情報提供装置（門型）
    }
  }

  // 道路標識
  setMstDH(json) {
    this.kousa_tanro_arr = json.kousa_tanro; // 交差単路
    this.hyoushiki_syu_arr = json.hyoushiki_syu; // 標識種別
    this.shichuu_houshiki_arr = json.shichuu_houshiki; // 支柱方式
    this.shichuu_kikaku_arr = json.shichuu_kikaku; // 支柱規格
  }

  // 道路情報提供装置
  setMstJD(json) {
    this.kousa_tanro_arr = json.kousa_tanro; // 交差単路
    this.keishiki_arr = json.keishiki; // 形式
    this.kiki_syu_arr = json.kiki_syu; // 機器種別
    this.koukyou_tandoku_arr = json.koukyou_tandoku; // 公共単独
    this.hyouji_shiyou_arr = json.hyouji_shiyou; // 表示仕様
  }

  // 道路照明施設
  setMstSS(json) {
    this.pole_kikaku_arr = json.pole_kikaku; // ポール規格
    this.tyoukou_umu_arr = json.tyoukou_umu; // 調光有無
    this.timer_umu_arr = json.timer_umu; // タイマー有無
  }

  // 防雪柵
  setMstBS(json) {
    this.sakusyu_arr = json.sakusyu; // 柵種
    this.saku_kbn_arr = json.saku_kbn; // 柵区分
    this.saku_keishiki_arr = json.saku_keishiki; // 柵形式
    this.kiso_keishiki_arr = json.kiso_keishiki; // 基礎形式
  }

  // スノーポール
  setMstYH(json) {
    this.hakkou_arr = json.hakkou; // 発光
  }

  // 監視局
  setMstKA(json) {
    this.unei_kbn_arr = json.unei_kbn; // 運営区分
  }

  // 受信局
  setMstKB(json) {
    this.unei_kbn_arr = json.unei_kbn; // 運営区分
  }

  // 中継局
  setMstKC(json) {
    this.unei_kbn_arr = json.unei_kbn; // 運営区分
  }

  // 観測局
  setMstKD(json) {
    this.unei_kbn_arr = json.unei_kbn; // 運営区分
  }

  // カメラ
  setMstKI(json) {
    this.unei_kbn_arr = json.unei_kbn; // 運営区分
  }

  // 情報板C型
  setMstJH(json) { }

  // 遮断機
  setMstSD(json) { }

  // ドット線
  setMstDT(json) { }

  // トンネル
  setMstTT(json) {
    this.toukyuu_arr = json.toukyuu; // トンネル等級
    this.shisetsu_renzoku_arr = json.shisetsu_renzoku; // 施設の連続
    this.hekimen_kbn_arr = json.hekimen_kbn; // 壁面区分
    this.kanki_shisetsu_arr = json.kanki_shisetsu; // 換気施設
    this.secchi_kasyo_j_arr = json.secchi_kasyo_j; // 設置個所縦断
    this.secchi_kasyo_o_arr = json.secchi_kasyo_o; // 設置個所横断
    this.syoumei_shisetsu_arr = json.syoumei_shisetsu; // 照明施設
    this.tuuhou_souchi_arr = json.tuuhou_souchi; // 通報装置
    this.hijou_keihou_souchi_arr = json.hijou_keihou_souchi; // 非常警報装置
    this.syouka_setsubi_arr = json.syouka_setsubi; // 消火設備
    this.sonota_setsubi_arr = json.sonota_setsubi; // その他設備
  }

  // 駐車公園
  setMstCK(json) {
    this.toire_katashiki_arr = json.toire_katashiki; // トイレ型式
    this.syoumei_dengen_arr = json.syoumei_dengen; // 照明電源
  }

  // 緑化樹木
  setMstSK(json) {
    this.kbn_c_arr = json.kbn_c; // 区分C
    this.ichi_c_arr = json.ichi_c; // 位置C
    this.tree_b_arr = json.tree_b; // 高木用
    this.tree_s_arr = json.tree_s; // 中低木用
  }

  // 立体横断
  setMstBH(json) { }

  // 擁壁
  setMstDY(json) { }

  // 法面
  setMstDN(json) { }

  // 浸出装置
  setMstTS(json) {
    this.endou_syu_arr = json.endou_syu; // 沿道種別
    this.jigyou_nm_arr = json.jigyou_nm; // 事業名
  }

  // TODO ロードヒーティング(未決定)
  setMstRH(json) {
    for (var key in json) {
      if (json.hasOwnProperty(key)) {
        if (key.match(/mst_/)) {
          // ロードヒーティング固有のマスタ
          let key2 = key.replace("mst_", "");
          this.master[key2] = json[key];
        } else if (key.match(/keishiki_kubun/)) {
          // 形式区分
          this.master[key] = json[key];
        }
      }
    }
  }

  // 冠水警報表示
  setMstKK(json) {
    this.kousa_tanro_arr = json.kousa_tanro; // 交差単路
    this.keishiki_arr = json.keishiki; // 形式
    this.kiki_syu_arr = json.kiki_syu; // 機器種別
    this.koukyou_tandoku_arr = json.koukyou_tandoku; // 公共単独
    this.hyouji_shiyou_arr = json.hyouji_shiyou; // 表示仕様
  }

  // トンネル警報表示
  setMstTK(json) {
    this.kousa_tanro_arr = json.kousa_tanro; // 交差単路
    this.keishiki_arr = json.keishiki; // 形式
    this.kiki_syu_arr = json.kiki_syu; // 機器種別
    this.koukyou_tandoku_arr = json.koukyou_tandoku; // 公共単独
    this.hyouji_shiyou_arr = json.hyouji_shiyou; // 表示仕様
  }

  // 橋梁
  setMstBR(json) {}

  // トンネル
  setMstTU(json) {}

  // 切土
  setMstDK(json) {}
  
  // 歩道
  setMstHD(json) {}
  
  // 落石崩壊
  setMstKR(json) {}
  
  // 横断歩道橋
  setMstFB(json) {}
  
  // シェッド等
  setMstSH(json) {}
  
  // 大型カルバート
  setMstCL(json) {}
  
  // 岩盤崩壊
  setMstKG(json) {}
  
  // 急流河川
  setMstKK(json) {}
  
  // 盛土
  setMstDF(json) {}
  
  // 道路標識（門型）
  setMstDHM(json) {}
  
  // 道路情報提供装置（門型）
  setMstJM(json) {}

  // 形式区分セット
  setKeishikiKubunArr(json) {
    this.keishiki_kubun_arr1 = json.keishiki_kubun1[0]; // 形式区分1
    this.keishiki_kubun_arr2 = json.keishiki_kubun2[0]; // 形式区分2
  }

  // 文字列を数値化
  // this.shisetsu下の項目が対象
  // ※ 桁が大きい値の指数を防ぐ
  // 引数:項目の配列
  chgNumShisetsu(arr) {
    for (var i = 0; i < arr.length; i++) {
      var item = arr[i];
      if (this.shisetsu[item]) {
        this.shisetsu[item] = Number(this.shisetsu[item]);
      }
    }
  }
  // this.daichou下の項目が対象
  // 引数:項目の配列
  chgNumDaichou(arr) {
    for (var i = 0; i < arr.length; i++) {
      var item = arr[i];
      if (this.daichou[item]) {
        this.daichou[item] = Number(this.daichou[item]);
      }
    }
  }

  // 日付を整形
  // this.daichou下の項目が対象
  // 引数:項目の配列
  formatYmdItem(arr) {
    for (var i = 0; i < arr.length; i++) {
      var item = arr[i];
      if (this.daichou[item]) {
        this.daichou[item] = this.formatYmd(this.daichou[item]);
      }
    }
  }

  // プルダウンの設定
  // 引数：マスタ配列名、マスタキー名、検索項目名、セット配列名を一つの配列にしたもの
  setPullDown(arr) {
    if (!arr.length) {
      return;
    }
    for (let i2 = 0; i2 < arr.length; i2++) {
      let param = arr[i2];

      let mst_arr_nm = param[0];
      let mst_key = param[1];
      let key = param[2];
      let target_arr_nm = param[3];
      if (this.daichou[key]) {
        var data = this[mst_arr_nm]; // マスタ
        for (var i = 0; i < data.length; i++) { // マスタループ
          var item = data[i];
          if (item[mst_key] == this.daichou[key]) { // マスタ項目と検索値を比較
            this.daichou[target_arr_nm] = item;
            break;
          }
        }
      }
    }

  }

  /**
   * 地図の初期化
   */
  initMap() {
    // map生成済みの場合は生成しない
    if (this.map) {
      return;
    }
    this.map = new OpenLayers.Map({
      div: 'map',
      projection: this.projection3857,
      displayProjection: this.projection4326
    });
    this.map.addLayer(new OpenLayers.Layer.XYZ("標準地図",
      "https://cyberjapandata.gsi.go.jp/xyz/std/${z}/${x}/${y}.png", {
      attribution: "<div style='text-align:right'><a href='https://www.gsi.go.jp/kikakuchousei/kikakuchousei40182.html' target='_blank'>国土地理院</a></div>",
      numZoomLevels: 19
    }));
    this.map.isValidZoomLevel = function (zoomLevel) {
      return ((zoomLevel != null) &&
        (zoomLevel >= 7) && // set min level here, could read from property
        (zoomLevel < this.getNumZoomLevels()));
    };
    // VectorLayerの初期化
    // VectorLayerの初期化
    this.vectorLayer = new OpenLayers.Layer.Vector("vector layer");
    this.vectorLayer.setVisibility(true);
    this.map.addLayers([this.vectorLayer]);

    // 範囲指定
    if (this.shisetsu.lat && this.shisetsu.lon) {
      // 施設の座標が設定されている場合
      this.setPoint(this.shisetsu.lon, this.shisetsu.lat);
      this.map.setCenter(new OpenLayers.LonLat(this.shisetsu.lon, this.shisetsu.lat).transform(this.projection4326, this.projection3857), 16);
    } else {
      this.map.zoomToExtent(new OpenLayers.Bounds(this.syucchoujo['lt_lon'], this.syucchoujo['lt_lat'], this.syucchoujo['rb_lon'], this.syucchoujo['rb_lat']).transform(this.projection4326, this.projection3857));
    }


    // スケールラインのコントローラ定義
    this.scaleLineControl = new OpenLayers.Control.ScaleLine({
      maxWidth: 150,
      bottomOutUnits: "",
      bottomInUnits: "",
      geodesic: true
    });
    this.map.addControls([this.scaleLineControl]);
    this.scaleLineControl.activate();

  }

  setPoint(lon, lat) {
    if (!this.feature) {
      this.feature = new OpenLayers.Feature.Vector(
        new OpenLayers.Geometry.Point(this.shisetsu.lon, this.shisetsu.lat)
          .transform(this.projection4326, this.projection3857));
      this.feature.style = {
        'fillColor': '#669933',
        'fillOpacity': 1,
        'strokeColor': '#aaee77',
        'strokeWidth': 3,
        'pointRadius': 8,
        'graphicWidth': 32,
        'graphicHeight': 32,
      };
      // アイコンの設定
      if (this.shisetsu.shisetsu_kbn == 1) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/dh_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 2) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/dj_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 3) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/ss_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 4) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/bs_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 5) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/yh_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 6) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/ka_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 7) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/kb_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 8) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/kc_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 9) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/kd_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 10) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/ki_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 11) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/jh_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 12) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/sd_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 13) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/dt_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 14) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/tt_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 15) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/ck_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 16) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/sk_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 17) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/bh_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 18) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/dy_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 19) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/dn_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 20) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/ts_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 21) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/rh_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 22) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/kk_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 23) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/tk_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 24) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/br_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 25) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/tu_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 26) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/dk_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 27) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/hd_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 28) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/kr_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 29) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/fb_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 30) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/sh_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 31) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/cl_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 34) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/df_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 32) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/kg_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 33) {
        // 「kk_1.gif」は冠水（廃止）に使われていたので「kk2_1.gif」を使用
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/kk2_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 35) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/hm_1.gif`;
      } else if (this.shisetsu.shisetsu_kbn == 36) {
        this.feature.style.externalGraphic = `images/icon/shisetsu_mng/jm_1.gif`;
      }
      this.vectorLayer.addFeatures([this.feature]);
    } else {
      this.feature.move(new OpenLayers.LonLat(lon, lat).transform(this.projection4326, this.projection3857));
    }
  }

  anchorScroll(anchor) {
    this.$location.hash(anchor);
    this.$anchorScroll();
    this.$location.url(this.$location.path());
    if (anchor == 'shisetsuinfo') {
      this.shisetsuinfo = false;
    } else if (anchor == 'tenkenrireki') {
      this.tenkenrireki = false;
    } else if (anchor == 'gdh_db') {
      this.gdh = false;
    } else if (anchor == 'other') {
      this.other = false;
    }
  }

  // ファイルのURLをエンコードする
  urlEncode(str) {
    return window.encodeURIComponent(str);
  }

  // ng-flowがファイルのアップロードを開始した際に呼ばれる
  filesSubmitted($flow) {
    console.log("filesSubmitted");
    this.waitUploadOverlay = true;
    $flow.upload()
  }

  /**
   * 法定点検のファイルがアップロードされたら実行される
   */
  async houteiFileUploadSuccess(chk_mng_no, $file, message, $flow, $index) {
    this.waitUploadOverlay = false;
    message = JSON.parse(message);
    
    // 拡張子のチェック
    const reg = /(.*)(?:\.([^.]+$))/;
    const ext = message.path.match(reg)[2].toUpperCase();
    if (!ext.match(/PDF/)) {
      await this.alert("ファイル選択エラー", "PDFファイルを選択してください");

      // 成功したときと同様に、失敗したときにもfilesの中身を空にしないと、
      // 以降ファイルをアップロードしてもこの関数が呼ばれなくなるので空にしておく
      $flow.files = [];
      return;
    }

    // 同名のファイルがアップロード済みでないか確認
    // （添付ファイルは施設ごとにサーバー上のディレクトリに保存されるため、同名のファイルは複数存在できない）
    for (const houtei of this.houtei) {
      for (const attachFile of houtei.attach_files) {
        if ($file.name == attachFile.file_name) {
          await this.alert("ファイル選択エラー", "既に同名のファイルがアップロードされています");
          
          // 成功したときと同様に、失敗したときにもfilesの中身を空にしないと、
          // 以降ファイルをアップロードしてもこの関数が呼ばれなくなるので空にしておく
          $flow.files = [];
          return;
        }
      }
    }

    for (let i = 0; i < this.houtei.length; i++) {
      // ファイルがアップロードされたchk_mng_noと一致する点検にファイルを追加する
      if(this.houtei[i].chk_mng_no == chk_mng_no) {
        const newAttachFile = {};
        newAttachFile.file_path = message.path;
        newAttachFile.file_name = $file.name;
        newAttachFile.updt_dt = moment().format("YYYY-MM-DD HH:mm");
        // ファイル削除時の特定ように仮のattachfile_noが必要なので、仮の番号を振る
        newAttachFile.attachfile_no = 'temp' + (this.getMaxTempAttachFileNo() + 1);

        this.houtei[i].attach_files.push(newAttachFile);
      }
    }
    $flow.files = [];
  }

  // 添付ファイルのうち、仮のattach_file_noの最大値を求める（仮のattach_file_noの生成用）
  getMaxTempAttachFileNo() {
    let maxNo = 0;
    const tempPattern = /^temp[0-9]+$/;
    // 各点検ごとの添付ファイルのうち、attachfile_noが「temp#」となっているものの数字のみを抜き出して配列とする
    for (const houtei of this.houtei) {
      for (const attachFile of houtei.attach_files) {
        if (String(attachFile.attachfile_no).match(tempPattern)) {
           // attachfile_noが「temp#」となっているものだけ、「temp#」から数字を切り出す
          const tempNo = attachFile.attachfile_no.replace('temp', '');
          maxNo = Math.max(maxNo, tempNo);
        }
      }
    }

    return maxNo;
  }

  /**
   * 法定点検のファイル削除ボタンを押すと呼ばれる
   * 該当するファイルを削除する
   */
  deleteHouteiFile(chk_mng_no, attachfile_no) {
    for (let i = 0; i < this.houtei.length; i++) {
      // ファイルがアップロードされたchk_mng_noと一致する点検から、attachfile_noが一致するものを除外する
      if(this.houtei[i].chk_mng_no == chk_mng_no) {
        this.houtei[i].attach_files = this.houtei[i].attach_files.filter(attachFile => attachFile.attachfile_no != attachfile_no);
      }
    }
  }

  /**
   * 法定点検のファイルのリンクをクリックした場合に呼ばれる。ファイルをダウンロードさせる
   * @param {*} url 
   * @param {*} fileName 
   */
  downloadFile(url,fileName){
    console.log("downloadFile("+url+","+fileName+")");

    // XMLHttpRequestオブジェクトを作成する
    const xhr = new XMLHttpRequest();
    xhr.open("GET", url , true);
    xhr.responseType = "blob"; // Blobオブジェクトとしてダウンロードする
    xhr.onload = function () {
        // ダウンロード完了後の処理を定義する
        const blob = xhr.response;
        if (window.navigator.msSaveBlob) {
          // IEとEdge
          window.navigator.msSaveBlob(blob, fileName);
        }
        else {
          // それ以外のブラウザ
          const objectURL = window.URL.createObjectURL(blob);
          const link = document.createElement("a");
          document.body.appendChild(link);
          link.href = objectURL;
          link.download = fileName;
          link.click();
          document.body.removeChild(link);
        }
    };
    // XMLHttpRequestオブジェクトの通信を開始する
    xhr.send();
  }

  // 定期パトロール画面に遷移する
  transitToTeikiPatrol() {
    // 画面遷移の確認ダイアログを無効にする
    window.onbeforeunload = null;
    // location.href = this.tpat_url + 'pc.html#';
    location.href = this.tpat_url + 'pc.html#/douro';
  }

  // 定期パトロールのExcelをダウンロードする
  downloadTeikiPatrolExcel(tenken_list_cd, tenken_list_name, tenken_list_detail_cd) {
    const url = location.origin + this.tpat_url + "api/index.php/Output/tenken_list?tenken_list_cd=" + tenken_list_cd + "&excel_list=[" + tenken_list_detail_cd + "]&disabled_shisetsu_list=[]";
    this.downloadFile(url, tenken_list_name + ".zip");// 定期パトロールに合わせてファイル名はtenken_list_nameを使用する
  }

  /**
   * アップロードされたら実行される
   */
  fileUploadSuccess1($file, message, $flow, $index) {
    console.log("!Uploaded!!!!");
    console.log($file);
    console.log(message);
    message = JSON.parse(message);
    this.zumen.sno = this.shisetsu.sno;
    this.zumen.zumen_id = 0;
    this.zumen.file_name = $file.name;
    this.zumen.path = message.path;
    this.zumen.lon = message.lon;
    this.zumen.lat = message.lat;
    this.zumen.exif_dt = message.date;
    this.zumen.description = "";
    $flow.files = [];
    // 図面有にする
    if (this.daichou.shisetsu_kbn == 18 || this.daichou.shisetsu_kbn == 19) {
      this.daichou.zumen_umu = 1;
    }
  }

  DeleteZumen() {
    this.zumen = {
      sno: this.shisetsu.sno
    };
    // 図面無にする
    if (this.daichou.shisetsu_kbn == 18 || this.daichou.shisetsu_kbn == 19) {
      this.daichou.zumen_umu = 2;
    }
  }

  /**
   * 法定点検の添付ファイルを保存する
   */
  saveHouteiAttach() {
    const attach_list = [];
    for (const houtei of this.houtei) {
      for (const attachFile of houtei.attach_files) {
        attach_list.push({
          chk_mng_no: houtei.chk_mng_no,
          file_path: attachFile.file_path,
          file_name: attachFile.file_name,
          comment: "",
          updt_dt: attachFile.updt_dt,
        });
      }
    }

    var url = "api/index.php/HouteiAttachController/saveAttach";
    this.$http({
      method: 'POST',
      url: url,
      data: {
        sno: this.sno,
        attach_list
      }
    });
  }

  save() {
    // 保存処理（confirm → OK）
    var message = '台帳を保存してよろしいですか？';
    this.confirm(message, 0).then(() => {
      // エラーメッセージ初期化
      this.err_message = [];
      // チェック処理
      this.chkData();
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
        return this.$q.reject("入力エラー");
      }
      // 保存中を出す
      this.waitOverlay = true;
      // 添付ファイルの保存
      //this.saveUpload();
      this.saveHouteiAttach();

      // 補修履歴をオブジェクト化
      //this.convHosyuuRireki();
      // 台帳の保存
      return this.$http({
        method: 'POST',
        url: 'api/index.php/FamEditAjax/saveShisetsuDaichou',
        data: {
          daichou: this.daichou,
          hosyuu: this.hosyuu,
          running_cost: this.running_cost_arr,
          repair_cost: this.repair_cost_arr
        }
      });
    }).then((data) => {
      return this.$http({
        method: 'POST',
        url: 'api/index.php/ZumenAjax/saveZumen',
        data: {
          zumen: this.zumen,
        }
      });
    }).then((data) => {
      return this.$http({
        method: 'POST',
        url: 'api/index.php/FamEditAjax/createDaichouExcel',
        data: {
          daichou: this.daichou,
        }
      });
    }).then((data) => {
      // 保存中を消す
      this.waitOverlay = false;
      return this.alert("完了メッセージ", "台帳の登録が完了しました");
    }).then((data) => {
      // メッセージ非表示
      this.windowUnlock();
      this.$window.location.reload(false);
    });
  }

  async deleteGdhData(gdh_idx) {
    // 削除確認
    if (await this.confirm('削除してよろしいですか？<br>(削除したデータは元に戻すことはできません)', 0)) {

      // 保存中を出す
      this.waitOverlay = true;

      // save処理
      var data = await this.$http({
        method: 'POST',
        url: 'api/index.php/GdhMainAjax/gdhDbDelete',
        data: {
          sno: this.sno,
          gdh_idx: gdh_idx,
        }
      });

      // 施設台帳更新
      await this.$http({
        method: 'POST',
        url: 'api/index.php/FamEditAjax/createDaichouExcel',
        data: {
          daichou: this.daichou,
        }
      });

      await this.alert('削除', '削除しました');

      // 保存中を解除
      this.waitOverlay = false;

      this.windowUnlock();
      location.reload();
    }
  }

  // チェック処理
  chkData() {
    // 必須チェック
    this.chkRequired();
    // 入力チェック
    this.chkInput();
    // 図面貼付チェック
    this.chkZumen();
  }

  // 必須チェック
  chkRequired() {
    // 確認時間
    // 正しい値が入っていない場合NULLになっている
    if (!this.daichou.create_dt) {
      this.err_message.push("台帳作成日は必須入力です。");
    }
    // 最終更新者追加START
    if (!this.daichou.update_account_nm) {
      this.err_message.push("更新者は必須入力です。");
    }
    // 最終更新者追加END
  }

  // 入力チェック
  chkInput() {
    // 数値チェック
    let shisetsu_kbn = this.shisetsu.shisetsu_kbn;
    if (this.master.shisetsu[shisetsu_kbn]) {
      this.chkNumberItem(this.master.shisetsu[shisetsu_kbn].num, this.master.shisetsu[shisetsu_kbn].num_str);
    }
  }

  chkNumberItem(arr, arr_str) {
    for (var i = 0; i < arr.length; i++) {
      var item = arr[i];
      var item_str = arr_str[i];
      if (this.daichou[item]) {
        if (!Number(this.daichou[item])) {
          this.err_message.push(item_str + "は数値項目です");
        }
      }
    }
  }

  // 図面リストのチェック
  chkZumen() {
    return true;
  }

  // 補修履歴をPOST前にOBJECT化
  convHosyuuRireki() {
    // 補修履歴がある場合は配列
    // 配列のままだとPOSTされないので変換
    var obj = {};
    for (var key in this.hosyuu) {
      var item = {};
      item[key] = this.hosyuu[key];
      for (var key2 in item[key2]) {
        obj[key][key2] = item[key2];
      }
    }
    this.hosyuu = obj;
  }

  // 基本情報編集
  editBaseInfo() {
    this.$location.path("/shisetsu_edit/" + this.shisetsu.sno + "/2/0");
  }

  addClickHosyuuRireki() {
    this.confirm('点検を追加しますか？', 0).then(() => {

      this.err_message = [];
      this.chkHosyuuRireki();
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
        return this.$q.reject("入力エラー");
      }

      var obj = {};
      obj.sno = this.sno;
      var max_idx = this.getMaxRirekiId();
      obj.hosyuu_rireki_id = Number(max_idx) + 1;
      // 点検年
      if (this.chk_year) {
        obj.check_nendo = this.chk_year.gengou;
        obj.check_yyyy = this.chk_year.year;
      }
      // 点検内容
      if (this.chk_naiyou) {
        obj.check_naiyou = this.chk_naiyou;
      }

      // 補修年
      if (this.hosyuu_year) {
        obj.repair_nendo = this.hosyuu_year.gengou;
        obj.repair_yyyy = this.hosyuu_year.year;
      }
      // 補修内容
      if (this.hosyuu_naiyou) {
        obj.repair_naiyou = this.hosyuu_naiyou;
      }
      this.hosyuu.push(obj);

      // 補修履歴入力欄初期化
      this.chk_year = null;
      this.chk_naiyou = "";
      this.hosyuu_year = null;
      this.hosyuu_naiyou = "";
    });
  }

  /*** ランニングコスト追加処理 ***/
  addClickRunningCost() {
    this.confirm('ランニングコストを追加しますか？', 0).then(() => {
      // 数値チェック
      this.err_message = [];
      this.chkRunningCost();
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
        return this.$q.reject("入力エラー");
      }
      var obj = {};
      obj.sno = this.sno;
      var max_idx = this.getMaxRunningCostId();
      obj.running_cost_id = Number(max_idx) + 1;
      // 年度
      if (this.r_nendo_row) {
        obj.nendo = this.r_nendo_row.gengou;
        obj.nendo_yyyy = this.r_nendo_row.year;
      }
      // 薬剤名
      if (this.yakuzai_nm) {
        obj.yakuzai_nm = this.yakuzai_nm;
      }
      // 散布量
      if (this.sanpu_ryou) {
        obj.sanpu_ryou = Math.round(this.sanpu_ryou * 100) / 100;
      }
      // 散布費
      if (this.sanpu_cost) {
        obj.sanpu_cost = Math.round(this.sanpu_cost * 100) / 100;
      }
      // 薬剤単価
      obj.yakuzai_tanka = "";
      if (this.sanpu_ryou && this.sanpu_cost) {
        // 散布量・散布費が両方入っている場合のみ計算
        obj.yakuzai_tanka = Math.round((Number(obj.sanpu_cost) / Number(obj.sanpu_ryou)) * 100) / 100;
      }
      // 電気代
      if (this.denki_cost) {
        obj.denki_cost = Math.round(this.denki_cost * 100) / 100;
      }
      // 契約電力
      if (this.keiyaku_denryoku) {
        obj.keiyaku_denryoku = Math.round(this.keiyaku_denryoku);
      }
      // 電力量
      if (this.denryoku_ryou) {
        obj.denryoku_ryou = Math.round(this.denryoku_ryou);
      }
      // ランニングコスト
      obj.calc_ranning_cost = "";
      if (this.sanpu_cost || this.denki_cost) {
        var s_cost = obj.sanpu_cost ? Number(obj.sanpu_cost) : 0;
        var d_cost = obj.denki_cost ? Number(obj.denki_cost) : 0;
        obj.calc_ranning_cost = Math.round((s_cost + d_cost) * 100) / 100; // 念のため整形
      }
      // 効果範囲面積
      if (this.kouka_hani_menseki) {
        obj.kouka_hani_menseki = Math.round(this.kouka_hani_menseki * 10) / 10;
      }
      // ㎡あたりの面積
      obj.area_per_cost = "";
      if (this.calc_ranning_cost != "" && this.kouka_hani_menseki) {
        // ランニングコスト・効果範囲面積が両方入っている場合のみ計算
        obj.area_per_cost = Math.round((Number(obj.calc_ranning_cost) / Number(obj.kouka_hani_menseki)) * 100) / 100;
      }
      // 摘要
      if (this.tekiyou) {
        obj.tekiyou = this.tekiyou;
      }

      this.running_cost_arr.push(obj);

      // ランニングコスト入力欄初期化
      this.r_nendo_row = null;
      this.yakuzai_nm = "";
      this.sanpu_ryou = "";
      this.sanpu_cost = "";
      this.denki_cost = "";
      this.keiyaku_denryoku = "";
      this.denryoku_ryou = "";
      this.kouka_hani_menseki = "";
      this.tekiyou = "";
    });
  }

  // 補修履歴チェック
  chkHosyuuRireki() {
    // 全項目、空の場合追加できないように
    if (!this.chk_year && this.chk_naiyou == "" && !this.hosyuu_year && this.hosyuu_naiyou == "") {
      this.err_message.push("全ての項目が未入力です");
    }
  }

  // ランニングコストチェック
  chkRunningCost() {
    // 全て未入力
    if (!this.r_nendo_row && this.yakuzai_nm == "" && this.sanpu_ryou == "" && this.sanpu_cost == "" && this.denki_cost == "" && this.keiyaku_denryoku == "" && this.denryoku_ryou == "" && this.kouka_hani_menseki == "" && this.tekiyou == "") {
      this.err_message.push("全ての項目が未入力です");
      // 全部からなので数値チェック不要
      return;
    }
    // 数値チェック
    this.chkRunningCostNum();
  }

  // ランニングコスト数値チェック
  chkRunningCostNum() {
    if (this.sanpu_ryou) {
      if (!Number(this.sanpu_ryou)) {
        this.err_message.push("散布量は数値項目です");
      }
    }
    if (this.sanpu_cost) {
      if (!Number(this.sanpu_cost)) {
        this.err_message.push("散布費は数値項目です");
      }
    }
    if (this.denki_cost) {
      if (!Number(this.denki_cost)) {
        this.err_message.push("電気代は数値項目です");
      }
    }
    if (this.keiyaku_denryoku) {
      if (!Number(this.keiyaku_denryoku)) {
        this.err_message.push("契約電力は数値項目です");
      }
    }
    if (this.denryoku_ryou) {
      if (!Number(this.denryoku_ryou)) {
        this.err_message.push("電力量は数値項目です");
      }
    }
    if (this.kouka_hani_menseki) {
      if (!Number(this.kouka_hani_menseki)) {
        this.err_message.push("効果範囲面積は数値項目です");
      }
    }
  }

  /*** 修理費追加処理 ***/
  addClicRepairCost() {
    this.confirm('修理費を追加しますか？', 0).then(() => {
      // 数値チェック
      this.err_message = [];
      this.chkRepairCost();
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
        return this.$q.reject("入力エラー");
      }
      var obj = {};
      obj.sno = this.sno;
      var max_idx = this.getMaxRunningCostId();
      obj.running_cost_id = Number(max_idx) + 1;
      // 年度
      if (this.s_nendo_row) {
        obj.nendo = this.s_nendo_row.gengou;
        obj.nendo_yyyy = this.s_nendo_row.year;
      }
      // 修理費
      if (this.repair_cost) {
        obj.repair_cost = Math.round(this.repair_cost * 100) / 100;
      }
      // 修理内容
      if (this.repair_naiyou) {
        obj.repair_naiyou = this.repair_naiyou;
      }
      this.repair_cost_arr.push(obj);
      // 修理費入力欄初期化
      this.s_nendo_row = null;
      this.repair_cost = "";
      this.repair_naiyou = "";
    });
  }

  // 修理費チェック
  chkRepairCost() {
    // 全て未入力
    if (!this.s_nendo_row && this.repair_cost == "" && this.repair_naiyou == "") {
      this.err_message.push("全ての項目が未入力です");
      // 全部からなので数値チェック不要
      return;
    }
    this.chkRepairCostNum();
  }

  // 修理費数値チェック
  chkRepairCostNum() {
    if (this.repair_cost) {
      if (!Number(this.repair_cost)) {
        this.err_message.push("修理費は数値項目です");
      }
    }
  }

  // 不明チェックボックスクリック時
  humeiClick() {
    if (this.chk_humei == true) {
      this.daichou.humei = 1;
    } else {
      this.daichou.humei = 0;
    }
  }

  // 未検定チェックボックスクリック時
  // $kbn 1:雨雪量計検定、2:風向風速計検定
  mikenteiClick(kbn) {
    if (kbn == 1) {
      if (this.chk_mikentei_u == true) {
        this.daichou.mikentei_u = 1;
      } else {
        this.daichou.mikentei_u = 0;
      }
    } else {
      if (this.chk_mikentei_h == true) {
        this.daichou.mikentei_h = 1;
      } else {
        this.daichou.mikentei_h = 0;
      }
    }
  }

  // 補修履歴の最大の履歴IDを取得
  getMaxRirekiId() {
    var max_tmp = 0;
    for (var i = 0; i < this.hosyuu.length; i++) {
      if (max_tmp < this.hosyuu[i].hosyuu_rireki_id) {
        max_tmp = this.hosyuu[i].hosyuu_rireki_id;
      }
    }
    return max_tmp;
  }

  // ランニングコストの最大のランニングコストIDを取得
  getMaxRunningCostId() {
    var max_tmp = 0;
    for (var i = 0; i < this.running_cost_arr.length; i++) {
      if (max_tmp < this.running_cost_arr[i].running_cost_id) {
        max_tmp = this.running_cost_arr[i].running_cost_id;
      }
    }
    return max_tmp;
  }

  // 修理費の最大の修理費IDを取得
  getMaxRepairCostId() {
    var max_tmp = 0;
    for (var i = 0; i < this.repair_cost_arr.length; i++) {
      if (max_tmp < this.repair_cost_arr[i].repair_cost_id) {
        max_tmp = this.repair_cost_arr[i].repair_cost_id;
      }
    }
    return max_tmp;
  }

  clickCheckbox(kbn) {
    if (kbn == 1) {
      // 降雪
      if (this.kousetsu) {
        this.daichou.c_kousetsu = 1;
      } else {
        this.daichou.c_kousetsu = 0;
      }
    } else if (kbn == 2) {
      // 外気温
      if (this.gaikion) {
        this.daichou.c_gaikion = 1;
      } else {
        this.daichou.c_gaikion = 0;
      }
    } else if (kbn == 3) {
      // 路温
      if (this.roon) {
        this.daichou.c_roon = 1;
      } else {
        this.daichou.c_roon = 0;
      }
    } else if (kbn == 4) {
      // 路面水分
      if (this.romen_suibun) {
        this.daichou.c_romen_suibun = 1;
      } else {
        this.daichou.c_romen_suibun = 0;
      }
    } else if (kbn == 5) {
      // センサ他
      if (this.sonota) {
        this.daichou.c_sonota = 1;
      } else {
        this.daichou.c_sonota = 0;
      }
    }
  }

  // モード変更
  chgMode() {

    /*
        // 29年度リリース対応
        // 基本情報が整うまで閲覧モードのみ
        this.alert('モード変更', '現在最新の施設情報をシステムに登録中のため、施設台帳は閲覧のみとなります');
        return;
    */

    if (this.editable) {
      this.confirm('変更があった場合、内容が破棄されます。よろしいですか？', 0).then(() => {
        // メッセージ非表示
        this.windowUnlock();
        this.$window.location.reload(false);
      });
    } else {
      // disp_idx変化なし
      this.editable = true;
      // 更新者を入力時には初期化し、必須入力とする。
      this.daichou.update_account_nm = '';
    }
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
        sno: this.shisetsu.sno,
        use_flg: [1, 2],
      }

    }).then((data) => {
      var zenkei_picture = data.data;
      this.zenkei_picture = [];
      zenkei_picture.data.forEach((val, key) => {
        if (val.struct_idx == 0 && val.del != 1) {
          this.zenkei_picture[val.use_flg] = val;
        }
      });

    });
  }

  location(link) {
    super.location(link, this.editable);
  }

  async locationGdh() {
    var flg = true;
    if (this.editable) {
      var res = await this.confirm('変更があった場合、内容が破棄されます。よろしいですか？', 0);
      if (!res) {
        flg = false;
      }
    }

    if (flg) {
      this.windowUnlock();
      var url = this.gdh_url + "#/search";
      this.$window.location.href = url;
    }
  }

  locationGdhMain(idx) {
    this.$window.location.href = "#/gdh_main/" + this.shisetsu.sno + "/" + idx;
  }

  /**
   * 画像の拡大図（モーダル）を開く
   */
  openModalGdhPhoto(path) {
    var data = {
      path: path
    };
    var modal = this.$uibModal.open({
      animation: true,
      templateUrl: 'views/modal_gdh_photo.html',
      controller: 'ModalGdhPhotoCtrl as modalGdhPhoto',
      size: "lg",
      resolve: {
        data: () => {
          return data;
        }
      }
    });
  }
}

let angModule = require('../app.js');
angModule.angApp.controller('FameditCtrl', ['$scope', '$http', '$location', '$uibModal', '$anchorScroll', '$routeParams', '$q', '$route', '$window', '$filter', function ($scope, $http, $location, $uibModal, $anchorScroll, $routeParams, $q, $route, $window, $filter) {
  return new FameditCtrl($scope, $http, $location, $uibModal, $anchorScroll, $routeParams, $q, $route, $window, $filter);
}]);
