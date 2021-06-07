<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("FamEditModel.php");

// 台帳:大型スノーポール登録用
class FamEditModelYH extends FamEditModel {

  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
  }

  /***
   *  台帳:大型スノーポールの更新
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  public function saveDaichou($daichou) {
    log_message('info', "FamEditModelYH->saveDaichou");
    $this->setDaichouCommon($daichou); // 共通
    $this->setItem($daichou); // スノーポール固有
    $this->setDaichouDenki($daichou); // 電気
    $sql= <<<SQL
            insert into rfs_t_daichou_yh (
              sno
              , kanri_no
              , katashiki
              , pole_keishiki
              , yh_type
              , seizou_company
              , yh_maker
              , hakkou_cd
              , yh_secchi
              , yh_secchi_yyyy
              , dengen
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
              , {$daichou['kanri_no']}
              , {$daichou['katashiki']}
              , {$daichou['pole_keishiki']}
              , {$daichou['yh_type']}
              , {$daichou['seizou_company']}
              , {$daichou['yh_maker']}
              , {$daichou['hakkou_cd']}
              , {$daichou['yh_secchi']}
              , {$daichou['yh_secchi_yyyy']}
              , {$daichou['dengen']}
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
            ON CONFLICT ON CONSTRAINT rfs_t_daichou_yh_pkey
            DO UPDATE SET
               kanri_no = {$daichou['kanri_no']}
              ,katashiki = {$daichou['katashiki']}
              ,pole_keishiki = {$daichou['pole_keishiki']}
              ,yh_type = {$daichou['yh_type']}
              ,seizou_company = {$daichou['seizou_company']}
              ,yh_maker = {$daichou['yh_maker']}
              ,hakkou_cd = {$daichou['hakkou_cd']}
              ,yh_secchi = {$daichou['yh_secchi']}
              ,yh_secchi_yyyy = {$daichou['yh_secchi_yyyy']}
              ,dengen = {$daichou['dengen']}
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
    $daichou['kanri_no']=$this->chkItem($daichou, 'kanri_no',2);
    $daichou['katashiki']=$this->chkItem($daichou, 'katashiki',2);
    $daichou['pole_keishiki']=$this->chkItem($daichou, 'pole_keishiki',2);
    $daichou['yh_type']=$this->chkItem($daichou, 'yh_type',2);
    $daichou['seizou_company']=$this->chkItem($daichou, 'seizou_company',2);
    $daichou['yh_maker']=$this->chkItem($daichou, 'yh_maker',2);
    $daichou['hakkou_cd']=$this->chkItem($daichou, 'hakkou_cd',1);
    if (isset($daichou['secchi_row'])) {
      if (count($daichou['secchi_row'])>0) {
        $daichou['yh_secchi']=pg_escape_literal($daichou['secchi_row']['gengou']);
        $daichou['yh_secchi_yyyy']=$daichou['secchi_row']['year'];
      }else{
        $daichou['yh_secchi']="null";
        $daichou['yh_secchi_yyyy']="null";
      }
    }else{
      $daichou['yh_secchi']="null";
      $daichou['yh_secchi_yyyy']="null";
    }
    $daichou['dengen']=$this->chkItem($daichou, 'dengen',2);
  }
}
