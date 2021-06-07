<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("FamEditModel.php");

// 台帳:情報板提供装置登録用
class FamEditModelJD extends FamEditModel {

  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
  }

  /***
   *  台帳:情報板提供装置の更新
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  public function saveDaichou($daichou) {
    log_message('info', "FamEditModelJD->saveDaichou");
    $this->setDaichouCommon($daichou); // 共通
    $this->setItem($daichou); // 情報板_電光固有
    $this->setDaichouTsuushin($daichou); // 通信回線
    $this->setDaichouDenki($daichou); // 電気
    $sql= <<<SQL
            insert into rfs_t_daichou_jd (
              sno
              , kousa_tanro_cd
              , kousa_rosen
              , rosen_genkyou
              , keishiki_cd
              , kiki_syu_cd
              , koukyou_tandoku_cd
              , hyouji_shiyou_cd
              , maker_nm
              , secchi_gyousya
              , tk_kaisen_syu
              , tk_kaisen_kyori
              , tk_kaisen_id
              , tk_kaisen_kyakuban
              , tk_setsuzoku_moto
              , tk_setsuzoku_saki
              , tk_getsugaku
              , tk_waribiki
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
              , {$daichou['kousa_tanro_cd']}
              , {$daichou['kousa_rosen']}
              , {$daichou['rosen_genkyou']}
              , {$daichou['keishiki_cd']}
              , {$daichou['kiki_syu_cd']}
              , {$daichou['koukyou_tandoku_cd']}
              , {$daichou['hyouji_shiyou_cd']}
              , {$daichou['maker_nm']}
              , {$daichou['secchi_gyousya']}
              , {$daichou['tk_kaisen_syu']}
              , {$daichou['tk_kaisen_kyori']}
              , {$daichou['tk_kaisen_id']}
              , {$daichou['tk_kaisen_kyakuban']}
              , {$daichou['tk_setsuzoku_moto']}
              , {$daichou['tk_setsuzoku_saki']}
              , {$daichou['tk_getsugaku']}
              , {$daichou['tk_waribiki']}
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
            ON CONFLICT ON CONSTRAINT rfs_t_daichou_jd_pkey
            DO UPDATE SET
              kousa_tanro_cd = {$daichou['kousa_tanro_cd']}
              ,kousa_rosen = {$daichou['kousa_rosen']}
              ,rosen_genkyou = {$daichou['rosen_genkyou']}
              ,keishiki_cd = {$daichou['keishiki_cd']}
              ,kiki_syu_cd = {$daichou['kiki_syu_cd']}
              ,koukyou_tandoku_cd = {$daichou['koukyou_tandoku_cd']}
              ,hyouji_shiyou_cd = {$daichou['hyouji_shiyou_cd']}
              ,maker_nm = {$daichou['maker_nm']}
              ,secchi_gyousya = {$daichou['secchi_gyousya']}
              ,tk_kaisen_syu = {$daichou['tk_kaisen_syu']}
              ,tk_kaisen_kyori = {$daichou['tk_kaisen_kyori']}
              ,tk_kaisen_id = {$daichou['tk_kaisen_id']}
              ,tk_kaisen_kyakuban = {$daichou['tk_kaisen_kyakuban']}
              ,tk_setsuzoku_moto = {$daichou['tk_setsuzoku_moto']}
              ,tk_setsuzoku_saki = {$daichou['tk_setsuzoku_saki']}
              ,tk_getsugaku = {$daichou['tk_getsugaku']}
              ,tk_waribiki = {$daichou['tk_waribiki']}
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
    $daichou['kousa_tanro_cd']=$this->chkItem($daichou, 'kousa_tanro_cd',1);
    $daichou['kousa_rosen']=$this->chkItem($daichou, 'kousa_rosen',2);
    $daichou['rosen_genkyou']=$this->chkItem($daichou, 'rosen_genkyou',2);
    $daichou['keishiki_cd']=$this->chkItem($daichou, 'keishiki_cd',1);
    $daichou['kiki_syu_cd']=$this->chkItem($daichou, 'kiki_syu_cd',1);
    $daichou['koukyou_tandoku_cd']=$this->chkItem($daichou, 'koukyou_tandoku_cd',1);
    $daichou['hyouji_shiyou_cd']=$this->chkItem($daichou, 'hyouji_shiyou_cd',1);
    $daichou['maker_nm']=$this->chkItem($daichou, 'maker_nm',2);
    $daichou['secchi_gyousya']=$this->chkItem($daichou, 'secchi_gyousya',2);
  }
}
