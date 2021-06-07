<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("FamEditModel.php");

// 台帳:照明登録用
class FamEditModelSS extends FamEditModel {

  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
  }

  /***
   *  台帳:道路照明の更新
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  public function saveDaichou($daichou) {
    log_message('info', "FamEditModelSS->saveDaichou");
    $this->setDaichouCommon($daichou); // 共通
    $this->setItem($daichou); // 照明固有
    $this->setDaichouDenki($daichou); // 電気
    $sql= <<<SQL
            insert into rfs_t_daichou_ss (
              sno
              , toutyuu_no
              , secchi_bui
              , kousa_rosen
              , shichuu_kou
              , haba
              , pole_kikaku_cd
              , pole_kikaku_bikou
              , syadou_ramp1
              , syadou_syoutou1
              , syadou_ramp2
              , syadou_syoutou2
              , hodou_ramp1
              , hodou_syoutou1
              , hodou_ramp2
              , hodou_syoutou2
              , ramp_num
              , tyoukou_umu_cd
              , timer_umu_cd
              , syoutou
              , secchi_gyousya
              , hodou_syoumei_payer
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
              , tougu_secchi
              , tougu_secchi_yyyy
            )values(
              {$daichou['sno']}
              , {$daichou['toutyuu_no']}
              , {$daichou['secchi_bui']}
              , {$daichou['kousa_rosen']}
              , {$daichou['shichuu_kou']}
              , {$daichou['haba']}
              , {$daichou['pole_kikaku_cd']}
              , {$daichou['pole_kikaku_bikou']}
              , {$daichou['syadou_ramp1']}
              , {$daichou['syadou_syoutou1']}
              , {$daichou['syadou_ramp2']}
              , {$daichou['syadou_syoutou2']}
              , {$daichou['hodou_ramp1']}
              , {$daichou['hodou_syoutou1']}
              , {$daichou['hodou_ramp2']}
              , {$daichou['hodou_syoutou2']}
              , {$daichou['ramp_num']}
              , {$daichou['tyoukou_umu_cd']}
              , {$daichou['timer_umu_cd']}
              , {$daichou['syoutou']}
              , {$daichou['secchi_gyousya']}
              , {$daichou['hodou_syoumei_payer']}
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
              , {$daichou['tougu_secchi']}
              , {$daichou['tougu_secchi_yyyy']}
            )
            ON CONFLICT ON CONSTRAINT rfs_t_daichou_ss_pkey
            DO UPDATE SET
              toutyuu_no = {$daichou['toutyuu_no']}
              ,secchi_bui = {$daichou['secchi_bui']}
              ,kousa_rosen = {$daichou['kousa_rosen']}
              ,shichuu_kou = {$daichou['shichuu_kou']}
              ,haba = {$daichou['haba']}
              ,pole_kikaku_cd = {$daichou['pole_kikaku_cd']}
              ,pole_kikaku_bikou = {$daichou['pole_kikaku_bikou']}
              ,syadou_ramp1 = {$daichou['syadou_ramp1']}
              ,syadou_syoutou1 = {$daichou['syadou_syoutou1']}
              ,syadou_ramp2 = {$daichou['syadou_ramp2']}
              ,syadou_syoutou2 = {$daichou['syadou_syoutou2']}
              ,hodou_ramp1 = {$daichou['hodou_ramp1']}
              ,hodou_syoutou1 = {$daichou['hodou_syoutou1']}
              ,hodou_ramp2 = {$daichou['hodou_ramp2']}
              ,hodou_syoutou2 = {$daichou['hodou_syoutou2']}
              ,ramp_num = {$daichou['ramp_num']}
              ,tyoukou_umu_cd = {$daichou['tyoukou_umu_cd']}
              ,timer_umu_cd = {$daichou['timer_umu_cd']}
              ,syoutou = {$daichou['syoutou']}
              ,secchi_gyousya = {$daichou['secchi_gyousya']}
              ,hodou_syoumei_payer = {$daichou['hodou_syoumei_payer']}
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
              , tougu_secchi = {$daichou['tougu_secchi']}
              , tougu_secchi_yyyy = {$daichou['tougu_secchi_yyyy']}
SQL;
    //log_message('debug', "sql=$sql");
    $this->DB_rfs->query($sql);
  }

  protected function setItem(&$daichou) {
    $daichou['toutyuu_no']=$this->chkItem($daichou, 'toutyuu_no',2);
    $daichou['secchi_bui']=$this->chkItem($daichou, 'secchi_bui',2);
    $daichou['kousa_rosen']=$this->chkItem($daichou, 'kousa_rosen',2);
    $daichou['shichuu_kou']=$this->chkItem($daichou, 'shichuu_kou',1);
    $daichou['haba']=$this->chkItem($daichou, 'haba',1);
    $daichou['pole_keishiki_cd']=$this->chkItem($daichou, 'pole_keishiki_cd',1);
    if (isset($daichou['pole_kikaku_row'])) {
      if (count($daichou['pole_kikaku_row'])>0) {
        $daichou['pole_kikaku_cd']=$daichou['pole_kikaku_row']['pole_kikaku_cd'];
      }else{
        $daichou['pole_kikaku_cd']="null";
      }
    }else{
      $daichou['pole_kikaku_cd']="null";
    }

    if (isset($daichou['tougu_secchi_row'])) {
      if (count($daichou['tougu_secchi_row'])>0) {
        $daichou['tougu_secchi']=pg_escape_literal($daichou['tougu_secchi_row']['gengou']);
        $daichou['tougu_secchi_yyyy']=$daichou['tougu_secchi_row']['year'];
      }else{
        $daichou['tougu_secchi']="null";
        $daichou['tougu_secchi_yyyy']="null";
      }
    }else{
      $daichou['tougu_secchi']="null";
      $daichou['tougu_secchi_yyyy']="null";
    }

    $daichou['pole_kikaku_bikou']=$this->chkItem($daichou, 'pole_kikaku_bikou',2);
    $daichou['syadou_ramp1']=$this->chkItem($daichou, 'syadou_ramp1',2);
    $daichou['syadou_syoutou1']=$this->chkItem($daichou, 'syadou_syoutou1',2);
    $daichou['syadou_ramp2']=$this->chkItem($daichou, 'syadou_ramp2',2);
    $daichou['syadou_syoutou2']=$this->chkItem($daichou, 'syadou_syoutou2',2);
    $daichou['hodou_ramp1']=$this->chkItem($daichou, 'hodou_ramp1',2);
    $daichou['hodou_syoutou1']=$this->chkItem($daichou, 'hodou_syoutou1',2);
    $daichou['hodou_ramp2']=$this->chkItem($daichou, 'hodou_ramp2',2);
    $daichou['hodou_syoutou2']=$this->chkItem($daichou, 'hodou_syoutou2',2);
    $daichou['ramp_num']=$this->chkItem($daichou, 'ramp_num',1);
    $daichou['tyoukou_umu_cd']=$this->chkItem($daichou, 'tyoukou_umu_cd',1);
    $daichou['timer_umu_cd']=$this->chkItem($daichou, 'timer_umu_cd',1);
    $daichou['syoutou']=$this->chkItem($daichou, 'syoutou',1);
    $daichou['secchi_gyousya']=$this->chkItem($daichou, 'secchi_gyousya',2);
    $daichou['hodou_syoumei_payer']=$this->chkItem($daichou, 'hodou_syoumei_payer',2);
  }




}
