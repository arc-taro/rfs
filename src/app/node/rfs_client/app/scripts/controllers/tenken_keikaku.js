"use strict";

var BaseCtrl = require("./base.js");

class TenkenKeikakuCtrl extends BaseCtrl {
  constructor(
    $scope,
    $http,
    $location,
    $uibModal,
    $anchorScroll,
    $routeParams,
    $q,
    $route,
    $compile,
    $window
  ) {
    super({
      $location: $location,
      $scope: $scope,
      $http: $http,
      $q: $q
    });

    // Angularの初期化
    this.$scope = $scope;
    this.$http = $http;
    this.$uibModal = $uibModal;
    this.$location = $location;
    this.$anchorScroll = $anchorScroll;
    this.$route = $route;
    this.$q = $q;
    this.$compile = $compile;

    // 初期設定
    this.initVariable();

    // 案内標識DBの戻り先URLを削除
    localStorage.removeItem("RTN_URL");

    // ログイン情報
    super
      .start(this.$http)
      .then(() => {
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
          url: 'api/index.php/TenkenKeikakuAjax/initTenkenKeikaku',
          params: {
            dogen_cd: this.mng_dogen_cd,
            syucchoujo_cd: this.mng_syucchoujo_cd,
            syozoku_cd: this.ath_syozoku_cd
          }
        });
      })
      .then(data => {
        const json = data.data;

        // 和暦リスト
        this.secchi_nendo = json.wareki_list;

        // 10年後まで含めた和暦リスト（secchi_nendoと別用途なので分ける）
        this.wareki_List_future = json.wareki_list_future;

        // チェックボックスを表示する年数
        this.tenken_keikaku_year_span = json.tenken_keikaku_year_span;

        // 初期表示用のヘッダーを設定
        this.keikaku_nendo_headers = [];
        // 今年度の西暦
        const thisYear = moment().add(-3, 'months').year();
        for (let i = 0; i < this.tenken_keikaku_year_span; i++) {
          // 今年から10年分、1年毎に和暦リストから取得してヘッダーの表示値とする
          const wareki = _.find(this.wareki_List_future, wareki => wareki.year == (thisYear + i));
          this.keikaku_nendo_headers.push(wareki.wareki_ryaku);
        }

        // 建管_出張所データ抽出
        this.dogen_syucchoujo_dat = JSON.parse(
          json.dogen_syucchoujo[0].dogen_row
        );
        // 施設区分
        this.shisetsu_kbn_dat = json.shisetsu_kbn;
        this.srch.shisetsu_kbn_all_cnt = this.shisetsu_kbn_dat.length;

        // 電気通信URL
        this.ele_url = json.ele_url;

        // 路線
        this.rosen_dat = json.rosen;

        // 建管と出張所の関係をセット
        // 建管未選択
        if (this.mng_dogen_cd == 0) {
          // 未選択時は先頭にする
          const data_d = this.dogen_syucchoujo_dat.dogen_info[0];
          this.dogen = data_d;
          this.dogen.dogen_cd = this.dogen.dogen_cd;
          // 出張所初期化
          this.syucchoujo = {};
          this.syucchoujo.syucchoujo_cd = 0;

          for (let l = 0; l < data_d.syucchoujo_row.syucchoujo_info.length; l++) {
            const data_s = data_d.syucchoujo_row.syucchoujo_info[l];
            if (data_s.syucchoujo_cd == this.mng_syucchoujo_cd) {
              this.syucchoujo = data_s;
            }
          }
        } else {
          // 選択時は該当の建管をセット
          for (let k = 0; k < this.dogen_syucchoujo_dat.dogen_info.length; k++) {
            const data_d = this.dogen_syucchoujo_dat.dogen_info[k];
            if (data_d.dogen_cd == this.mng_dogen_cd) {
              this.dogen = data_d;
              this.syucchoujo = {};
              this.syucchoujo.syucchoujo_cd = 0;
              for (
                let l = 0;
                l < data_d.syucchoujo_row.syucchoujo_info.length;
                l++
              ) {
                const data_s = data_d.syucchoujo_row.syucchoujo_info[l];
                // 維持管理で選択された出張所をセット
                if (data_s.syucchoujo_cd == this.mng_syucchoujo_cd) {
                  this.syucchoujo = data_s;
                }
              }
            }
          }
        }
        return this.mngarea_update(this.$http, this.dogen.dogen_cd, this.syucchoujo.syucchoujo_cd);
      }).then(() => {
        // mngarea上書き
        this.mng_dogen_cd = this.session.mngarea.dogen_cd;
        this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
      })
      .finally(() => {

        // 路線リストを生成
        this.filterRosen = this.filterRosens();
        this.srch.rosen_all_cnt = this.filterRosen.length;

        this.waitLoadOverlay = false; // 読込み中のスピナーを消す
      });
  }

  // 初期変数設定
  initVariable() {
    // 検索最大値
    this.max_srch_cnt = 100;

    this.shisetsukind = false;

    this.keikaku_list = null;
    this.keikaku_nendo_headers = [];


    // 施設検索の検索条件
    this.srch = {};

    // プルダウンチェックボックス絡み
    this.extraSettings = {
      externalIdProp: "",
      buttonClasses: "btn btn-default btn-xs btn-block",
      scrollable: true,
      scrollableHeight: "350px"
    };
    this.translationTexts = {
      checkAll: "全て",
      uncheckAll: "全て外す",
      buttonDefaultText: "選択",
      dynamicButtonTextSuffix: "個選択"
    };
    this.srch.shisetsu_kbn_dat_model = [];
    this.srch.rosen_dat_model = [];

    // 数値項目配列
    this.numItem = [
      "rosen_cd",
      "sp_to",
    ];

    this.keikaku_nendo_headers = [];

    // 点検計画のヘッダーのcolspanを設定するための値
    // 一旦ここで設定するが、最終的にはinit時にサーバー側から取得する
    this.tenken_keikaku_year_span = 10;

    setTimeout(() => {
      $("#sync-table2").on("scroll", evt => {
        $("#sync-table1").scrollTop($(evt.target).scrollTop());
      });
    }, 500);
  }
  
  // 検索条件の整理
  arrangeSearchCondition() {
    // 入力→削除した要素の空文字を削除
    if (this.srch.shisetsu_cd != null) {
      if (this.srch.shisetsu_cd.length == 0) {
        delete this.srch.shisetsu_cd; // 施設管理番号
      }
    }

    if (this.srch.sp_from != null) {
      if (this.srch.sp_from.length == 0) {
        delete this.srch.sp_from; // 測点（前）
      }
    }

    if (this.srch.sp_to != null) {
      if (this.srch.sp_to.length == 0) {
        delete this.srch.sp_to; // 測点（後）
      }
    }
  }

  // 点検施設検索
  srchTenkenShisetsu() {
    this.waitLoadOverlay = true;
    this.clearResult();

    // 検索条件の整理
    this.arrangeSearchCondition();

    this.$http({
      method: "POST",
      url: "api/index.php/TenkenKeikakuAjax/srchTenkenShisetsu",
      data: {
        dogen_cd: this.dogen.dogen_cd,
        syucchoujo_cd: this.syucchoujo ? this.syucchoujo.syucchoujo_cd : 0,
        srch: this.srch
      }
    })
      .then(data => {
        const json = data.data;

        // 件数をチェック
        const srch_cnt = json.cnt;
        let message;

        // 100件を超えている場合、表示・取得をクリアし再絞込みしてもらう
        if (srch_cnt > this.max_srch_cnt) {
          this.clearResult();
          delete data.data;
          message =
            "検索結果が" + String(this.max_srch_cnt) + "件を越えています<br>";
          message += "（" + String(srch_cnt) + "件）<br><br>";
          message += "検索条件を設定し、対象データを絞り込んでください<br>";
          return this.alert("検索", message);
        } else if (srch_cnt === 0) {
          message = "該当するデータがありません";
          this.jyoukenkensaku = false;
          this.naiyoukensaku = true;
          this.clearResult();
          delete data.data;
          return this.alert("検索", message);
        } else {
          this.keikaku_list = json.shisetsu_info;

          // 防雪柵で子レコードがない場合は附属物点検の入力ができるようにフラグを設定する
          for (let i = 0; i < this.keikaku_list.length; i++) {
            if (this.keikaku_list[i].shisetsu_kbn != 4) {
              continue;
            }
            // 子レコードがあるかどうかをセット
            this.keikaku_list[i].children_exists = true;
            const currentStructIdx = this.keikaku_list[i].struct_idx;
            if (i < this.keikaku_list.length - 1) {
              const currentSno = this.keikaku_list[i].sno;
              const nextSno = this.keikaku_list[i + 1].sno;
              if (currentStructIdx == 0) {
                // 親の行の場合で次の行のsnoが異なる場合は子レコードがないと判断
                if (currentSno != nextSno) {
                  this.keikaku_list[i].children_exists = false;
                }
              }
            } else {
              if (currentStructIdx == 0) {
                // 最後に親が来た場合は子レコードがないと判断
                this.keikaku_list[i].children_exists = false;
              }
            }
          }
          
          // ng-repeatする要素（各行とチェックボックスのマス）には一意の値が必要なので振る
          // 行には「row_<施設番号>_<支柱インデックス>」、
          // チェックボックスには「chkbox_<施設番号>_<支柱インデックス>_<点検の種別>_<年度（西暦）>」とする
          for (let iRow = 0; iRow < this.keikaku_list.length; iRow++) {
            const keikaku = this.keikaku_list[iRow];
            // 行のID
            this.keikaku_list[iRow].id = "row_" + keikaku.sno + "_" + keikaku.struct_idx;
            if (keikaku.houtei_plans) {
              // 法定点検の各マスのID
              for (let iHoutei = 0; iHoutei < keikaku.houtei_plans.length; iHoutei++) {
                const plan = keikaku.houtei_plans[iHoutei];
                this.keikaku_list[iRow].houtei_plans[iHoutei].id = "chkbox_" + keikaku.sno + "_" + keikaku.struct_idx + "_houtei_" + plan.year;
              }
            }
            if (keikaku.huzokubutsu_plans) {
              // 附属物点検の各マスのID
              for (let iHuzokubutsu = 0; iHuzokubutsu < keikaku.huzokubutsu_plans.length; iHuzokubutsu++) {
                const plan = keikaku.huzokubutsu_plans[iHuzokubutsu];
                this.keikaku_list[iRow].huzokubutsu_plans[iHuzokubutsu].id = "chkbox_" + keikaku.sno + "_" + keikaku.struct_idx + "_huzokubutsu_" + plan.year;
              }
            }
            if (keikaku.teiki_pat_plans) {
              // 定期パトの各マスのID
              for (let iTeiki = 0; iTeiki < keikaku.teiki_pat_plans.length; iTeiki++) {
                const plan = keikaku.teiki_pat_plans[iTeiki];
                this.keikaku_list[iRow].teiki_pat_plans[iTeiki].id = "chkbox_" + keikaku.sno + "_" + keikaku.struct_idx + "_teiki_pat_" + plan.year;
              }
            }
          }

          this.srch_cnt = srch_cnt;
          this.changeNumItem(); // 数値項目を数値化

          // 検索実行時、条件を指定して検索を閉じる
          this.jyoukenkensaku = true;
          this.naiyoukensaku = false;

          message = "";
          message =
            srch_cnt +
            "件の結果を取得しました";
          return this.alert("検索", message);
        }
      })
      .finally(() => {
        this.waitLoadOverlay = false;
      });
  }

  // 法定点検のチェックボックスが無効かどうかを返す
  isHouteiDisabled(keikaku, annualPlan) {
    if (!keikaku.houtei_flag) {
      // 法定点検を実施しない施設の場合は無効
      return true;
    }
    if (annualPlan.patrol_done) {
      // パトロール実施済みの場合は無効
      return true;
    }
    return false;
  }

  // 附属物点検のチェックボックスが無効かどうかを返す
  isHuzokubutsuDisabled(keikaku, annualPlan) {
    if (!keikaku.huzokubutsu_flag) {
      // 附属物点検を実施しない施設の場合は無効
      return true;
    }
    if (annualPlan.patrol_done) {
      // パトロール実施済みの場合は無効
      return true;
    }
    if (keikaku.shisetsu_kbn == 4) {
      // 防雪柵の場合
      if (keikaku.struct_idx == 0) {
        // 親の行の場合
        if (keikaku.children_exists) {
          // 子（支柱インデックスごと）の行が存在する場合は無効（附属物点検は支柱インデックスごとに設定するため）
          return true;
        }
      }
    }
    return false;
  }
  
  // 定期パトロールのチェックボックスが無効かどうかを返す
  isTeikiPatDisabled(keikaku, annualPlan) {
    if (!keikaku.teiki_pat_flag) {
      // 定期パトを実施しない施設の場合は無効
      return true;
    }
    if (annualPlan.patrol_done) {
      // パトロール実施済みの場合は無効
      return true;
    }
    if (keikaku.shisetsu_kbn == 4) {
      // 防雪柵の場合
      if (keikaku.struct_idx > 0) {
        // 支柱インデックスの行の場合は無効（定期パトはまとめて設定するため）
        return true;
      }
    }
    return false;
  }

  // 法定点検のチェックボックスを操作した際に呼ばれる。法定点検をtrueにした際に対となる定期パトをfalseにする
  onHouteiPlanChanged(keikaku, year, newValue) {
    if (!newValue) {
      // オフにした場合は何もしない
      return;
    }
    if (!keikaku.teiki_pat_flag) {
      // 定期パトを実行しない施設の場合は何もしない
      return;
    }
    // teiki_pat_plansから対になるデータを見つけ出してチェックボックスの値をセットする関数を実行する
    this.keikaku_list = _.map(this.keikaku_list, 
      eachKeikaku => {
        if (eachKeikaku.sno == keikaku.sno && eachKeikaku.struct_idx == keikaku.struct_idx) {
          // snoとstruct_idxが一致する行のみを変更し、それ以外の行は何もしない
          eachKeikaku.teiki_pat_plans = _.map(eachKeikaku.teiki_pat_plans, plan => {
            // 年が一致し、パトロール実施済みでないもののみを変更する
            if (plan.year == year && !plan.patrol_done) {
              plan.planned = false;
            }
            return plan; 
          });
        }
        return eachKeikaku;
      });
  }
  // 点検計画のチェックボックスを操作した際に呼ばれる。附属物点検をtrueにした際に対となる定期パトをfalseにする
  onHuzokubutsuPlanChanged(keikaku, year, newValue) {
    if (!newValue) {
      // オフにした場合は何もしない
      return;
    }
    if (!keikaku.teiki_pat_flag) {
      // 定期パトを実行しない施設の場合は何もしない
      return;
    }
    // teiki_pat_plansに対して対象のデータを見つけ出してチェックボックスの値をセットする関数を実行する
    this.keikaku_list = _.map(this.keikaku_list, 
      eachKeikaku => {
        if (keikaku.shisetsu_kbn != 4) {
          // 防雪柵以外の場合は他と同様に同じ行のチェックボックスを対象とする
          if (eachKeikaku.sno == keikaku.sno && eachKeikaku.struct_idx == keikaku.struct_idx) {
            // snoとstruct_idxが一致する行のみを変更し、それ以外の行は何もしない
            eachKeikaku.teiki_pat_plans = _.map(eachKeikaku.teiki_pat_plans, plan => {
              // 年が一致し、パトロール実施済みでないもののみを変更する
              if (plan.year == year && !plan.patrol_done) {
                plan.planned = false;
              }
              return plan; 
            });
          }
        } else {
          // 防雪柵の場合は防雪柵の親データ(struct_idx == 0)を対象とする
          if (eachKeikaku.sno == keikaku.sno && eachKeikaku.struct_idx == 0) {
            eachKeikaku.teiki_pat_plans = _.map(eachKeikaku.teiki_pat_plans, plan => {
              // 年が一致し、パトロール実施済みでないもののみを変更する
              if (plan.year == year && !plan.patrol_done) {
                plan.planned = false;
              }
              return plan; 
            });
          }
        }
        return eachKeikaku;
      });
  }
  // 点検計画のチェックボックスを操作した際に呼ばれる。附属物点検をtrueにした際に対となる定期パトをfalseにする
  onTeikiPatPlanChanged(keikaku, year, newValue) {
    if (!newValue) {
      // オフにした場合は何もしない
      return;
    }
    if (keikaku.houtei_flag) {
      // 法定点検を実施する施設の場合はhoutei_plansに対して対象のデータを見つけ出してチェックボックスの値をセットする関数を実行する
      this.keikaku_list = _.map(this.keikaku_list, eachKeikaku => {
        if (eachKeikaku.sno == keikaku.sno && eachKeikaku.struct_idx == keikaku.struct_idx) {
          // snoとstruct_idxが一致する行のみを変更し、それ以外の行は何もしない
          eachKeikaku.houtei_plans = _.map(eachKeikaku.houtei_plans, plan => {
            // 年が一致し、パトロール実施済みでないもののみを変更する
            if (plan.year == year && !plan.patrol_done) {
              plan.planned = false;
            }
            return plan; 
          });
        }
        return eachKeikaku;
      });
    } else if (keikaku.huzokubutsu_flag) {
      // 附属物点検を実施する施設の場合はhuzokubutsu_plansに対して対象のデータを見つけ出してチェックボックスの値をセットする関数を実行する
      this.keikaku_list = _.map(this.keikaku_list, eachKeikaku => {
        if (keikaku.shisetsu_kbn != 4) {
          // 防雪柵以外の場合は他と同様に同じ行のチェックボックスを対象とする
          if (eachKeikaku.sno == keikaku.sno && eachKeikaku.struct_idx == keikaku.struct_idx) {
            // snoとstruct_idxが一致する行のみを変更し、それ以外の行は何もしない
            eachKeikaku.huzokubutsu_plans = _.map(eachKeikaku.huzokubutsu_plans, plan => {
              // 年が一致し、パトロール実施済みでないもののみを変更する
              if (plan.year == year && !plan.patrol_done) {
                plan.planned = false;
              }
              return plan; 
            });
          }
        } else {
          if (keikaku.children_exists) {
            // 防雪柵の場合で支柱インデックスの行がある場合は附属物点検の同じsnoでstruct_idxが1以上のものを変更する
            if (eachKeikaku.sno == keikaku.sno && eachKeikaku.struct_idx > 0) {
              // snoが一致する支柱インデックスの行のみを変更し、それ以外の行は何もしない
              eachKeikaku.huzokubutsu_plans = _.map(eachKeikaku.huzokubutsu_plans, plan => {
                // 年が一致し、パトロール実施済みでないもののみを変更する
                if (plan.year == year && !plan.patrol_done) {
                  plan.planned = false;
                }
                return plan; 
              });
            }
          } else {
            // 防雪柵で支柱インデックスの行が無い場合は同じ行の附属物点検を変更する
            if (eachKeikaku.sno == keikaku.sno && eachKeikaku.struct_idx == keikaku.struct_idx) {
              // snoとstruct_idxが一致する行のみを変更し、それ以外の行は何もしない
              eachKeikaku.huzokubutsu_plans = _.map(eachKeikaku.huzokubutsu_plans, plan => {
                // 年が一致し、パトロール実施済みでないもののみを変更する
                if (plan.year == year && !plan.patrol_done) {
                  plan.planned = false;
                }
                return plan; 
              });
            }
          }
        }
        return eachKeikaku;
      });
    }
  }

  saveKeikaku() {
    const message = "点検計画を保存してよろしいですか？";
    this.confirm(message, 0).then(() => {

      // 保存中を出す
      this.waitOverlay = true;

      const shisetsuList = _(this.keikaku_list)
        .map(keikaku => {
          return {
            sno: keikaku.sno,
            shisetsu_kbn: keikaku.shisetsu_kbn,
            struct_idx: keikaku.struct_idx
          }
        })
        .value();

      let savingHouteiPlans = [];
      let savingHuzokubutsuPlans = [];
      let savingTeikiPatPlans = [];

      // それぞれの計画について、plannedがtrueのものだけ抜き出す。
      const convertPlansToSaveData = (keikaku, plans) => {
        return _(plans)
          .filter(plan => plan.planned)
          .map(plan => {
            return {
              sno: keikaku.sno,
              shisetsu_kbn: keikaku.shisetsu_kbn,
              struct_idx: keikaku.struct_idx,
              year: plan.year
            }
          })
          .value();
      }

      for (const keikaku of this.keikaku_list) {
        const houteiPlans = convertPlansToSaveData(keikaku, keikaku.houtei_plans);
        savingHouteiPlans = savingHouteiPlans.concat(houteiPlans);

        const huzokubutsuPlans = convertPlansToSaveData(keikaku, keikaku.huzokubutsu_plans);
        savingHuzokubutsuPlans = savingHuzokubutsuPlans.concat(huzokubutsuPlans);

        const teikiPatPlans = convertPlansToSaveData(keikaku, keikaku.teiki_pat_plans);
        savingTeikiPatPlans = savingTeikiPatPlans.concat(teikiPatPlans)
      }

      const targetYearStart = moment().add(-3, 'months').year();
      const targetYearEnd = targetYearStart + this.tenken_keikaku_year_span - 1;
      
      // 点検計画の保存
      return this.$http({
        method: 'POST',
        url: 'api/index.php/TenkenKeikakuAjax/saveTenkenKeikaku',
        data: {
          houtei_plans: savingHouteiPlans,
          huzokubutsu_plans: savingHuzokubutsuPlans,
          teiki_pat_plans: savingTeikiPatPlans,
          target_year_start: targetYearStart,
          target_year_end: targetYearEnd,
          shisetsu_list: shisetsuList,
        }
      }).then((data) => {
        // 保存中を消す
        this.waitOverlay = false;
        if (data.data.result_cd == 200) {
          return this.alert("完了メッセージ", "点検計画の登録が完了しました");
        } else {
          return this.alert("完了メッセージ", "点検計画の登録に失敗しました");
        }
      }).then((data) => {
        // メッセージ非表示
        this.windowUnlock();
        this.$window.location.reload(false);
      });
    });
  }

  // 選択建管/出張所でフィルタ
  // 呼ばれるタイミングは、オープン時/建管/出張所変更時
  filterRosens() {
    this.srch.rosen_dat_model = [];
    // 出張所選択
    if (this.syucchoujo.syucchoujo_cd != 0) {
      // 出張所フィルタ
      const syucchoujo_cd = this.syucchoujo.syucchoujo_cd;
      return this.rosen_dat.filter(function(value, index) {
        if (syucchoujo_cd == value.syucchoujo_cd) {
          return true;
        }
        return false;
      });
    } else {
      // 建管フィルタ
      const dogen_cd = this.dogen.dogen_cd;
      return this.rosen_dat.filter(function(value, index) {
        if (dogen_cd == value.dogen_cd) {
          return true;
        }
        return false;
      });
    }
  }

  // 異常有無欄のセルのスタイルを生成する
  getIjouUmuCellStyle(latestTeikiPat) {
    const result = {};
    if (!latestTeikiPat) {
      // 直近の定期パトが無ければ設定しない
      return result;
    }
    if (latestTeikiPat.ijou_list_count) {
      result['backgroundColor'] = '#FF0000';
      result['color'] = "#FFFFFF";
    } else {
      result['backgroundColor'] = '#90CAF9';
      result['color'] = "#FFFFFF";
    }
    return result;
  }

  // 健全性欄のセルのスタイルを生成する（健全性によって背景色が変わるため）
  getCheckShisetsuJudgeCellStyle(latestHuzokubutsu) {
    const result = {};
    if (!latestHuzokubutsu) {
      return result;
    }
    switch (latestHuzokubutsu.check_shisetsu_judge) {
      case "1":
        result['backgroundColor'] = '#0000FF';
        result['color'] = "#FFFFFF";
        break;
      case "2":
        result['backgroundColor'] = "#00B050";
        result['color'] = "#FFFFFF";
        break;
      case "3":
        result['backgroundColor'] = "#FFC000";
        result['color'] = "#FFFFFF";
        break;
    }
    return result;
  }

  // 検索項目の全てリセット
  allReset() {
    delete this.srch.shisetsu_cd; // 施設管理番号
    delete this.srch.secchi_nendo_from; // 設置年度（前）
    delete this.srch.secchi_nendo_to; // 設置年度（後）
    delete this.srch.sp_from; // 測点（前）
    delete this.srch.sp_to; // 測点（後）

    // 設置年度に不明を含めるチェックボックスをクリア
    this.srch.include_secchi_null = false;

    // マルチセレクトクリア
    this.multiselect_clear();

    // 検索結果、ソート条件クリア
    this.clearResult();

    this.alert("検索", "リセットしました");
  }

  // multiselectクリア
  multiselect_clear() {
    this.srch.shisetsu_kbn_dat_model = [];
    this.srch.substitute_road_dat_model = [];
    this.srch.emergency_road_dat_model = [];
    this.srch.kyouyou_kbn_dat_model = [];
    this.srch.rosen_dat_model = [];
  }

  // 建管変更時処理
  // 本庁権限のみ
  // 1.mngarea更新
  // 2.検索初期化
  chgDogen() {
    // 検索結果無
    if (this.keikaku_list == null) {
      this.chgKanriNoSrch(1);
      // セッション上書き
      this.mngarea_update(
        this.$http,
        this.dogen.dogen_cd,
        this.syucchoujo.syucchoujo_cd
      ).then(() => {
        // mngarea上書き
        this.mng_dogen_cd = this.session.mngarea.dogen_cd;
        this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
      });
      // 検索結果有
    } else {
      this.confirm("検索結果を初期化してよろしいですか？", 0)
        .then(() => {
          // 結果クリア
          this.clearResult();

          // 条件表示変更
          this.jyoukenkensaku = false;
          this.naiyoukensaku = true;

          // 保存用
          this.keep_dogen = this.dogen;
          this.keep_syucchoujo = this.syucchoujo;
          this.syucchoujo = {};
          this.syucchoujo.syucchoujo_cd = 0;
        })
        .catch(() => {
          // キャンセル時は戻す
          this.dogen = this.keep_dogen;
          this.syucchoujo = this.keep_syucchoujo;
        })
        .finally(() => {
          // 路線
          this.filterRosen = this.filterRosens();
          this.srch.rosen_all_cnt = this.filterRosen.length;
          // セッション上書き
          this.mngarea_update(
            this.$http,
            this.dogen.dogen_cd,
            this.syucchoujo.syucchoujo_cd
          ).then(() => {
            // mngarea上書き
            this.mng_dogen_cd = this.session.mngarea.dogen_cd;
            this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
          });
        });
    }
  }

  // 出張所変更時処理
  chgSyucchoujo() {
    // 検索結果が存在しない
    if (this.keikaku_list == null) {
      this.chgKanriNoSrch(2);
      // セッション上書き
      this.mngarea_update(
        this.$http,
        this.dogen.dogen_cd,
        this.syucchoujo.syucchoujo_cd
      ).then(() => {
        // mngarea上書き
        this.mng_dogen_cd = this.session.mngarea.dogen_cd;
        this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
      });
      // 検索結果有
    } else {
      this.confirm("検索結果を初期化してよろしいですか？", 0)
        .then(() => {
          // 結果クリア
          this.clearResult();

          // 条件表示変更
          this.jyoukenkensaku = false;
          this.naiyoukensaku = true;
          // 保存用
          this.keep_syucchoujo = this.syucchoujo;
        })
        .catch(() => {
          // キャンセル時は戻す
          this.syucchoujo = this.keep_syucchoujo;
        })
        .finally(() => {
          // 路線
          this.filterRosen = this.filterRosens();
          this.srch.rosen_all_cnt = this.filterRosen.length;
          // セッション上書き
          this.mngarea_update(
            this.$http,
            this.dogen.dogen_cd,
            this.syucchoujo.syucchoujo_cd
          ).then(() => {
            // mngarea上書き
            this.mng_dogen_cd = this.session.mngarea.dogen_cd;
            this.mng_syucchoujo_cd = this.session.mngarea.syucchoujo_cd;
          });
        });
    }
  }

  // 建管、出張所変更時、検索結果無しの振る舞い用
  // kbn:1 建管
  //     2 出張所
  chgKanriNoSrch(kbn) {
    // 建管
    if (kbn == 1) {
      // 建管変更時は出張所全選択
      if (
        this.ath_syozoku_cd == 1 ||
        this.ath_syozoku_cd == 2 ||
        this.ath_syozoku_cd == 10001
      ) {
        this.syucchoujo = {};
        this.syucchoujo.syucchoujo_cd = 0;
      }
      
      // 保存用（キャンセル時に戻す時用）
      this.keep_dogen = this.dogen;
      this.keep_syucchoujo = this.syucchoujo;
      // 路線
      this.filterRosen = this.filterRosens();
      this.srch.rosen_all_cnt = this.filterRosen.length;
      // 出張所
    } else {
      // 他出張所選択から全てを選択した時の対応
      if (!this.syucchoujo) {
        this.syucchoujo = {};
        this.syucchoujo.syucchoujo_cd = 0;
      }

      // 保存用（キャンセル時に戻す時用）
      this.keep_syucchoujo = this.syucchoujo;
      // 路線
      this.filterRosen = this.filterRosens();
      this.srch.rosen_all_cnt = this.filterRosen.length;
    }
  }

  // 検索結果の削除
  clearResult() {
    // 検索結果の削除
    this.keikaku_list = null;
  }

  changeNumItem() {
    for (let i = 0; i < this.keikaku_list.length; i++) {
      for (let j = 0; j < this.numItem.length; j++) {
        if (this.keikaku_list[i][this.numItem[j]]) {
          if (Number(this.keikaku_list[i][this.numItem[j]])) {
            this.keikaku_list[i][this.numItem[j]] = Number(
              this.keikaku_list[i][this.numItem[j]]
            );
          }
        }
      }
    }
  }
}

let angModule = require("../app.js");
angModule.angApp.controller("TenkenKeikakuCtrl", [
  "$scope",
  "$http",
  "$location",
  "$uibModal",
  "$anchorScroll",
  "$routeParams",
  "$q",
  "$route",
  "$compile",
  "$window",
  function(
    $scope,
    $http,
    $location,
    $uibModal,
    $anchorScroll,
    $routeParams,
    $q,
    $route,
    $compile,
    $window
  ) {
    return new TenkenKeikakuCtrl(
      $scope,
      $http,
      $location,
      $uibModal,
      $anchorScroll,
      $routeParams,
      $q,
      $route,
      $compile,
      $window
    );
  }
]);
