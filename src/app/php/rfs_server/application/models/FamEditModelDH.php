<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("FamEditModel.php");

// 台帳:道路標識登録用
class FamEditModelDH extends FamEditModel {

  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
  }

  /***
   *  台帳:道路標識の更新
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  public function saveDaichou($daichou) {
    log_message('info', "FamEditModelDH->saveDaichou");
    $this->setDaichouCommon($daichou); // 共通
    $this->setItem($daichou); // 道路標識固有
    $this->setDaichouDenki($daichou); // 電気
    $sql= <<<SQL
            insert into rfs_t_daichou_dh (
              sno
              , kousa_tanro_cd
              , syurui_bangou
              , kousa_rosen
              , hyoushiki_syu_cd
              , ryuui_jikou
              , ban_sunpou
              , ban_moji_size
              , ban_zaishitsu
              , ban_hansya_syoumei
              , ban_no_hyouki_umu
              , ban_tagengo_umu
              , ban_kyouka1
              , ban_kyouka2
              , shichuu_houshiki_cd
              , shichuu_houshiki_bikou
              , shichuu_kikaku_cd
              , shichuu_kikaku_bikou
              , shichuu_tosou
              , shichuu_kiso_keishiki
              , shichuu_sunpou
              , hyoushikityuu_no
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
              , {$daichou['syurui_bangou']}
              , {$daichou['kousa_rosen']}
              , {$daichou['hyoushiki_syu_cd']}
              , {$daichou['ryuui_jikou']}
              , {$daichou['ban_sunpou']}
              , {$daichou['ban_moji_size']}
              , {$daichou['ban_zaishitsu']}
              , {$daichou['ban_hansya_syoumei']}
              , {$daichou['ban_no_hyouki_umu']}
              , {$daichou['ban_tagengo_umu']}
              , {$daichou['ban_kyouka1']}
              , {$daichou['ban_kyouka2']}
              , {$daichou['shichuu_houshiki_cd']}
              , {$daichou['shichuu_houshiki_bikou']}
              , {$daichou['shichuu_kikaku_cd']}
              , {$daichou['shichuu_kikaku_bikou']}
              , {$daichou['shichuu_tosou']}
              , {$daichou['shichuu_kiso_keishiki']}
              , {$daichou['shichuu_sunpou']}
              , {$daichou['hyoushikityuu_no']}
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
            ON CONFLICT ON CONSTRAINT rfs_t_daichou_dh_pkey
            DO UPDATE SET
              kousa_tanro_cd = {$daichou['kousa_tanro_cd']}
              ,syurui_bangou = {$daichou['syurui_bangou']}
              ,kousa_rosen = {$daichou['kousa_rosen']}
              ,hyoushiki_syu_cd = {$daichou['hyoushiki_syu_cd']}
              ,ryuui_jikou = {$daichou['ryuui_jikou']}
              ,ban_sunpou = {$daichou['ban_sunpou']}
              ,ban_moji_size = {$daichou['ban_moji_size']}
              ,ban_zaishitsu = {$daichou['ban_zaishitsu']}
              ,ban_hansya_syoumei = {$daichou['ban_hansya_syoumei']}
              ,ban_no_hyouki_umu = {$daichou['ban_no_hyouki_umu']}
              ,ban_tagengo_umu = {$daichou['ban_tagengo_umu']}
              ,ban_kyouka1 = {$daichou['ban_kyouka1']}
              ,ban_kyouka2 = {$daichou['ban_kyouka2']}
              ,shichuu_houshiki_cd = {$daichou['shichuu_houshiki_cd']}
              ,shichuu_houshiki_bikou = {$daichou['shichuu_houshiki_bikou']}
              ,shichuu_kikaku_cd = {$daichou['shichuu_kikaku_cd']}
              ,shichuu_kikaku_bikou = {$daichou['shichuu_kikaku_bikou']}
              ,shichuu_tosou = {$daichou['shichuu_tosou']}
              ,shichuu_kiso_keishiki = {$daichou['shichuu_kiso_keishiki']}
              ,shichuu_sunpou = {$daichou['shichuu_sunpou']}
              ,hyoushikityuu_no = {$daichou['hyoushikityuu_no']}
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
    $daichou['kousa_tanro_cd']=$this->chkItem($daichou,'kousa_tanro_cd',1);
    $daichou['syurui_bangou']=$this->chkItem($daichou,'syurui_bangou',2);
    $daichou['kousa_rosen']=$this->chkItem($daichou,'kousa_rosen',2);
    $daichou['hyoushiki_syu_cd']=$this->chkItem($daichou,'hyoushiki_syu_cd',1);
    $daichou['ryuui_jikou']=$this->chkItem($daichou,'ryuui_jikou',2);
    $daichou['ban_sunpou']=$this->chkItem($daichou,'ban_sunpou',2);
    $daichou['ban_moji_size']=$this->chkItem($daichou,'ban_moji_size',2);
    $daichou['ban_zaishitsu']=$this->chkItem($daichou,'ban_zaishitsu',2);
    $daichou['ban_hansya_syoumei']=$this->chkItem($daichou,'ban_hansya_syoumei',2);
    $daichou['ban_no_hyouki_umu']=$this->chkItem($daichou,'ban_no_hyouki_umu',1);
    $daichou['ban_tagengo_umu']=$this->chkItem($daichou,'ban_tagengo_umu',1);
    $daichou['ban_kyouka1']=$this->chkItem($daichou,'ban_kyouka1',2);
    $daichou['ban_kyouka2']=$this->chkItem($daichou,'ban_kyouka2',2);
    $daichou['shichuu_houshiki_cd']=$this->chkItem($daichou,'shichuu_houshiki_cd',1);
    $daichou['shichuu_houshiki_bikou']=$this->chkItem($daichou,'shichuu_houshiki_bikou',2);
    $daichou['shichuu_kikaku_cd']=$this->chkItem($daichou,'shichuu_kikaku_cd',1);
    $daichou['shichuu_kikaku_bikou']=$this->chkItem($daichou,'shichuu_kikaku_bikou',2);
    $daichou['shichuu_tosou']=$this->chkItem($daichou,'shichuu_tosou',2);
    $daichou['shichuu_kiso_keishiki']=$this->chkItem($daichou,'shichuu_kiso_keishiki',2);
    $daichou['shichuu_sunpou']=$this->chkItem($daichou,'shichuu_sunpou',2);
    $daichou['hyoushikityuu_no']=$this->chkItem($daichou,'hyoushikityuu_no',2);
  }
}
