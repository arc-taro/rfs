<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("FamEditModel.php");

// 台帳:浸出装置登録用
class FamEditModelTS extends FamEditModel {

  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
  }

  /***
   *  台帳:浸出装置の更新
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  public function saveDaichou($daichou) {
    log_message('info', "FamEditModelTS->saveDaichou");
    $this->setDaichouCommon($daichou); // 共通
    $this->setItem($daichou); // 浸出装置固有
    $this->setDaichouDenki($daichou); // 電気
    $sql= <<<SQL
            insert into rfs_t_daichou_ts (
              sno
              , dj_old_id
              , dj_syadou_haba
              , dj_rokata_haba
              , dj_hodou_haba
              , dj_juudan_koubai
              , dj_kyokusen_hankei
              , dj_kouzou
              , dj_dourojoukyou_bikou
              , endou_syu_cd
              , e_chimoku
              , e_syokusei_sta
              , e_haisui_syori
              , j_maker_nm
              , j_enkaku_sousa
              , jigyou_nm_cd
              , j_jigyou_cost
              , j_bikou
              , c_kousetsu
              , c_gaikion
              , c_roon
              , c_romen_suibun
              , c_sonota
              , c_sonota_input
              , t_youryou_chijou
              , t_youryou_chika
              , k_all_kadou_hour
              , k_naiyou
              , y_kouka_entyou
              , y_kouka_fukuin
              , d_hokuden_kyakuban
              , d_keiyaku_houshiki
              , d_kaisen_id
              , d_kaisen_kyakuban
              , d_hikikomi
              , d_denki_dai
              , d_denki_ryou
              , bikou
              , kyoutsuu1
              , kyoutsuu2
              , kyoutsuu3
              , dokuji1
              , dokuji2
              , dokuji3
              , create_dt
              , create_account
              , update_dt
              , update_account
              --最終更新者追加START
              , update_busyo_cd
              , update_account_nm
              --最終更新者追加END
            )values(
              {$daichou['sno']}
              , {$daichou['dj_old_id']}
              , {$daichou['dj_syadou_haba']}
              , {$daichou['dj_rokata_haba']}
              , {$daichou['dj_hodou_haba']}
              , {$daichou['dj_juudan_koubai']}
              , {$daichou['dj_kyokusen_hankei']}
              , {$daichou['dj_kouzou']}
              , {$daichou['dj_dourojoukyou_bikou']}
              , {$daichou['endou_syu_cd']}
              , {$daichou['e_chimoku']}
              , {$daichou['e_syokusei_sta']}
              , {$daichou['e_haisui_syori']}
              , {$daichou['j_maker_nm']}
              , {$daichou['j_enkaku_sousa']}
              , {$daichou['jigyou_nm_cd']}
              , {$daichou['j_jigyou_cost']}
              , {$daichou['j_bikou']}
              , {$daichou['c_kousetsu']}
              , {$daichou['c_gaikion']}
              , {$daichou['c_roon']}
              , {$daichou['c_romen_suibun']}
              , {$daichou['c_sonota']}
              , {$daichou['c_sonota_input']}
              , {$daichou['t_youryou_chijou']}
              , {$daichou['t_youryou_chika']}
              , {$daichou['k_all_kadou_hour']}
              , {$daichou['k_naiyou']}
              , {$daichou['y_kouka_entyou']}
              , {$daichou['y_kouka_fukuin']}
              , {$daichou['d_hokuden_kyakuban']}
              , {$daichou['d_keiyaku_houshiki']}
              , {$daichou['d_kaisen_id']}
              , {$daichou['d_kaisen_kyakuban']}
              , {$daichou['d_hikikomi']}
              , {$daichou['d_denki_dai']}
              , {$daichou['d_denki_ryou']}
              , {$daichou['bikou']}
              , {$daichou['kyoutsuu1']}
              , {$daichou['kyoutsuu2']}
              , {$daichou['kyoutsuu3']}
              , {$daichou['dokuji1']}
              , {$daichou['dokuji2']}
              , {$daichou['dokuji3']}
              , {$daichou['create_dt']}
              , {$daichou['create_account']}
              , NOW()
              , {$daichou['update_account']}
              --最終更新者追加START
              , {$daichou['update_busyo_cd']}
              , {$daichou['update_account_nm']}
              --最終更新者追加END
            )
            ON CONFLICT ON CONSTRAINT rfs_t_daichou_ts_pkey
            DO UPDATE SET
              dj_old_id = {$daichou['dj_old_id']}
              ,dj_syadou_haba = {$daichou['dj_syadou_haba']}
              ,dj_rokata_haba = {$daichou['dj_rokata_haba']}
              ,dj_hodou_haba = {$daichou['dj_hodou_haba']}
              ,dj_juudan_koubai = {$daichou['dj_juudan_koubai']}
              ,dj_kyokusen_hankei = {$daichou['dj_kyokusen_hankei']}
              ,dj_kouzou = {$daichou['dj_kouzou']}
              ,dj_dourojoukyou_bikou = {$daichou['dj_dourojoukyou_bikou']}
              ,endou_syu_cd = {$daichou['endou_syu_cd']}
              ,e_chimoku = {$daichou['e_chimoku']}
              ,e_syokusei_sta = {$daichou['e_syokusei_sta']}
              ,e_haisui_syori = {$daichou['e_haisui_syori']}
              ,j_maker_nm = {$daichou['j_maker_nm']}
              ,j_enkaku_sousa = {$daichou['j_enkaku_sousa']}
              ,jigyou_nm_cd = {$daichou['jigyou_nm_cd']}
              ,j_jigyou_cost = {$daichou['j_jigyou_cost']}
              ,j_bikou = {$daichou['j_bikou']}
              ,c_kousetsu = {$daichou['c_kousetsu']}
              ,c_gaikion = {$daichou['c_gaikion']}
              ,c_roon = {$daichou['c_roon']}
              ,c_romen_suibun = {$daichou['c_romen_suibun']}
              ,c_sonota = {$daichou['c_sonota']}
              ,c_sonota_input = {$daichou['c_sonota_input']}
              ,t_youryou_chijou = {$daichou['t_youryou_chijou']}
              ,t_youryou_chika = {$daichou['t_youryou_chika']}
              ,k_all_kadou_hour = {$daichou['k_all_kadou_hour']}
              ,k_naiyou = {$daichou['k_naiyou']}
              ,y_kouka_entyou = {$daichou['y_kouka_entyou']}
              ,y_kouka_fukuin = {$daichou['y_kouka_fukuin']}
              ,d_hokuden_kyakuban = {$daichou['d_hokuden_kyakuban']}
              ,d_keiyaku_houshiki = {$daichou['d_keiyaku_houshiki']}
              ,d_kaisen_id = {$daichou['d_kaisen_id']}
              ,d_kaisen_kyakuban = {$daichou['d_kaisen_kyakuban']}
              ,d_hikikomi = {$daichou['d_hikikomi']}
              ,d_denki_dai = {$daichou['d_denki_dai']}
              ,d_denki_ryou = {$daichou['d_denki_ryou']}
              ,bikou = {$daichou['bikou']}
              ,kyoutsuu1 = {$daichou['kyoutsuu1']}
              ,kyoutsuu2 = {$daichou['kyoutsuu2']}
              ,kyoutsuu3 = {$daichou['kyoutsuu3']}
              ,dokuji1 = {$daichou['dokuji1']}
              ,dokuji2 = {$daichou['dokuji2']}
              ,dokuji3 = {$daichou['dokuji3']}
              ,create_dt = {$daichou['create_dt']}
              ,update_dt = NOW()
              ,update_account = {$daichou['update_account']}
              --最終更新者追加START
              ,update_busyo_cd = {$daichou['update_busyo_cd']}
              ,update_account_nm = {$daichou['update_account_nm']}
              --最終更新者追加END
SQL;
    //log_message('debug', "sql=$sql");
    $this->DB_rfs->query($sql);
  }

  protected function setItem(&$daichou) {
    $daichou['dj_old_id']=$this->chkItem($daichou,'dj_old_id',2);
    $daichou['dj_syadou_haba']=$this->chkItem($daichou,'dj_syadou_haba',2);
    $daichou['dj_rokata_haba']=$this->chkItem($daichou,'dj_rokata_haba',2);
    $daichou['dj_hodou_haba']=$this->chkItem($daichou,'dj_hodou_haba',2);
    $daichou['dj_juudan_koubai']=$this->chkItem($daichou,'dj_juudan_koubai',2);
    $daichou['dj_kyokusen_hankei']=$this->chkItem($daichou,'dj_kyokusen_hankei',2);
    $daichou['dj_kouzou']=$this->chkItem($daichou,'dj_kouzou',2);
    $daichou['dj_dourojoukyou_bikou']=$this->chkItem($daichou,'dj_dourojoukyou_bikou',2);
    $daichou['endou_syu_cd']=$this->chkItem($daichou,'endou_syu_cd',1);
    $daichou['e_chimoku']=$this->chkItem($daichou,'e_chimoku',2);
    $daichou['e_syokusei_sta']=$this->chkItem($daichou,'e_syokusei_sta',2);
    $daichou['e_haisui_syori']=$this->chkItem($daichou,'e_haisui_syori',2);
    $daichou['j_maker_nm']=$this->chkItem($daichou,'j_maker_nm',2);
    $daichou['j_enkaku_sousa']=$this->chkItem($daichou,'j_enkaku_sousa',1);
    $daichou['jigyou_nm_cd']=$this->chkItem($daichou,'jigyou_nm_cd',1);
    $daichou['j_jigyou_cost']=$this->chkItem($daichou,'j_jigyou_cost',1);
    $daichou['j_bikou']=$this->chkItem($daichou,'j_bikou',2);
    $daichou['c_kousetsu']=isset($daichou['c_kousetsu'])?($daichou['c_kousetsu']==1?1:"null"):"null";
    $daichou['c_gaikion']=isset($daichou['c_gaikion'])?($daichou['c_gaikion']==1?1:"null"):"null";
    $daichou['c_roon']=isset($daichou['c_roon'])?($daichou['c_roon']==1?1:"null"):"null";
    $daichou['c_romen_suibun']=isset($daichou['c_romen_suibun'])?($daichou['c_romen_suibun']==1?1:"null"):"null";
    $daichou['c_sonota']=isset($daichou['c_sonota'])?($daichou['c_sonota']==1?1:"null"):"null";
    $daichou['c_sonota_input']=$this->chkItem($daichou,'c_sonota_input',2);
    $daichou['t_youryou_chijou']=$this->chkItem($daichou,'t_youryou_chijou',1);
    $daichou['t_youryou_chika']=$this->chkItem($daichou,'t_youryou_chika',1);
    $daichou['k_all_kadou_hour']=$this->chkItem($daichou,'k_all_kadou_hour',1);
    $daichou['k_naiyou']=$this->chkItem($daichou,'k_naiyou',2);
    $daichou['y_kouka_entyou']=$this->chkItem($daichou,'y_kouka_entyou',1);
    $daichou['y_kouka_fukuin']=$this->chkItem($daichou,'y_kouka_fukuin',1);
  }

  /***
   *  コスト登録
   *
   *  引数
   *    $running_cost ランニングコスト
   *    $repair_cost 修理費
   ***/
  public function saveCost($running_cost, $repair_cost) {
    log_message('info', "saveCost");
    // 共にデータが無い場合は終了
    if (count($running_cost) == 0 && count($repair_cost) == 0) {
      return;
    }

    // ランニングコスト登録
    $this->saveRunningCost($running_cost);
    // 修理費登録
    $this->saveRepairCost($repair_cost);
  }

  /***
   *  ランニングコスト登録
   *
   *  引数
   *    $running_cost ランニングコスト
   ***/
  protected function saveRunningCost($running_cost) {
    // 該当データが無い場合は終了
    if (count($running_cost) == 0) {
      return;
    }
    // 該当補修履歴の削除
    $this->delRunningCost($running_cost[0]['sno']);
    // データの整備
    $cnt=0;
    for ($i=0;$i<count($running_cost);$i++) {
      // delflgが1の場合は、登録から除外
      if (isset($running_cost[$i]['delflg'])) {
        if ($running_cost[$i]['delflg']==1) {
          continue;
        }
      }
      $cnt++;
      $setArr=array();
      // 登録内容をセット
      $setArr['sno']=$running_cost[$i]['sno'];
      $setArr['running_cost_id']=$cnt;
      $setArr['nendo']=$this->chkItem($running_cost[$i], 'nendo',2);
      $setArr['nendo_yyyy']=$this->chkItem($running_cost[$i], 'nendo_yyyy',1);
      $setArr['yakuzai_nm']=$this->chkItem($running_cost[$i], 'yakuzai_nm',2);
      $setArr['sanpu_ryou']=$this->chkItem($running_cost[$i], 'sanpu_ryou',1);
      $setArr['sanpu_cost']=$this->chkItem($running_cost[$i], 'sanpu_cost',1);
      $setArr['denki_cost']=$this->chkItem($running_cost[$i], 'denki_cost',1);
      $setArr['keiyaku_denryoku']=$this->chkItem($running_cost[$i], 'keiyaku_denryoku',1);
      $setArr['denryoku_ryou']=$this->chkItem($running_cost[$i], 'denryoku_ryou',1);
      $setArr['kouka_hani_menseki']=$this->chkItem($running_cost[$i], 'kouka_hani_menseki',1);
      $setArr['tekiyou']=$this->chkItem($running_cost[$i], 'tekiyou',2);
      // 補修履歴登録
      $this->insRunningCost($setArr);
    }

  }

  /***
   *  ランニングコストの削除
   *    引数snoのランニングコストを全て削除する
   *
   *  引数
   *    $sno 施設システム内番号
   ***/
  protected function delRunningCost($sno) {
    log_message('info', "delRunningCost");
    $sql= <<<EOF
DELETE FROM
  rfs_t_running_cost
WHERE
  sno = $sno
EOF;
    $query = $this->DB_rfs->query($sql);
  }

  /***
   *  ランニングコストの登録
   *    引数の1レコードを登録する
   *
   *  引数
   *    $running_cost ランニングコスト1レコード
   ***/
  protected function insRunningCost($running_cost) {
    log_message('info', "insRunningCost");
    $sql= <<<EOF
INSERT
INTO rfs_t_running_cost(
  sno
  , running_cost_id
  , nendo
  , nendo_yyyy
  , yakuzai_nm
  , sanpu_ryou
  , sanpu_cost
  , denki_cost
  , keiyaku_denryoku
  , denryoku_ryou
  , kouka_hani_menseki
  , tekiyou
) VALUES (
  ${running_cost['sno']}
  , ${running_cost['running_cost_id']}
  , ${running_cost['nendo']}
  , ${running_cost['nendo_yyyy']}
  , ${running_cost['yakuzai_nm']}
  , ${running_cost['sanpu_ryou']}
  , ${running_cost['sanpu_cost']}
  , ${running_cost['denki_cost']}
  , ${running_cost['keiyaku_denryoku']}
  , ${running_cost['denryoku_ryou']}
  , ${running_cost['kouka_hani_menseki']}
  , ${running_cost['tekiyou']}
);
EOF;
    $query = $this->DB_rfs->query($sql);
  }

  /***
   *  修理費登録
   *
   *  引数
   *    $repair_cost ランニングコスト
   ***/
  protected function saveRepairCost($repair_cost) {
    // 該当データが無い場合は終了
    if (count($repair_cost) == 0) {
      return;
    }
    // 修理費の削除
    $this->delRepairCost($repair_cost[0]['sno']);
    // データの整備
    $cnt=0;
    for ($i=0;$i<count($repair_cost);$i++) {
      // delflgが1の場合は、登録から除外
      if (isset($repair_cost[$i]['delflg'])) {
        if ($repair_cost[$i]['delflg']==1) {
          continue;
        }
      }
      $cnt++;
      $setArr=array();
      // 登録内容をセット
      $setArr['sno']=$repair_cost[$i]['sno'];
      $setArr['repair_cost_id']=$cnt;
      $setArr['nendo']=$this->chkItem($repair_cost[$i], 'nendo',2);
      $setArr['nendo_yyyy']=$this->chkItem($repair_cost[$i], 'nendo_yyyy',1);
      $setArr['repair_cost']=$this->chkItem($repair_cost[$i], 'repair_cost',1);
      $setArr['repair_naiyou']=$this->chkItem($repair_cost[$i], 'repair_naiyou',2);
      // 補修履歴登録
      $this->insRepairCost($setArr);
    }
  }

  /***
   *  修理費の削除
   *    引数snoの修理費を全て削除する
   *
   *  引数
   *    $sno 施設システム内番号
   ***/
  protected function delRepairCost($sno) {
    log_message('info', "delRepairCost");
    $sql= <<<EOF
DELETE FROM
  rfs_t_repair_cost
WHERE
  sno = $sno
EOF;
    $query = $this->DB_rfs->query($sql);
  }

  /***
   *  修理費の登録
   *    引数の1レコードを登録する
   *
   *  引数
   *    $repair_cost ランニングコスト1レコード
   ***/
  protected function insRepairCost($repair_cost) {
    log_message('info', "insRepairCost");
    $sql= <<<EOF
INSERT
INTO rfs_t_repair_cost(
  sno
  , repair_cost_id
  , nendo
  , nendo_yyyy
  , repair_cost
  , repair_naiyou
) VALUES (
  ${repair_cost['sno']}
  , ${repair_cost['repair_cost_id']}
  , ${repair_cost['nendo']}
  , ${repair_cost['nendo_yyyy']}
  , ${repair_cost['repair_cost']}
  , ${repair_cost['repair_naiyou']}
);
EOF;
    $query = $this->DB_rfs->query($sql);
  }

}
