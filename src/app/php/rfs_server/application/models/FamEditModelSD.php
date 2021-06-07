<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("FamEditModel.php");

// 台帳:遮断機登録用
class FamEditModelSD extends FamEditModel {

  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
  }

  /***
   *  台帳:遮断機の更新
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  public function saveDaichou($daichou) {
    log_message('info', "FamEditModelSD->saveDaichou");
    $this->setDaichouCommon($daichou); // 共通
    $this->setItem($daichou); // 遮断機固有
    $this->setDaichouDenki($daichou); // 電気
    $sql= <<<SQL
            insert into rfs_t_daichou_sd (
              sno
              , yokokuhyoushiki_umu
              , jouhouban_umu
              , anzentou_syoumeitou
              , syadanji_riyuu
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
              , {$daichou['yokokuhyoushiki_umu']}
              , {$daichou['jouhouban_umu']}
              , {$daichou['anzentou_syoumeitou']}
              , {$daichou['syadanji_riyuu']}
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
            ON CONFLICT ON CONSTRAINT rfs_t_daichou_sd_pkey
            DO UPDATE SET
              yokokuhyoushiki_umu = {$daichou['yokokuhyoushiki_umu']}
              ,jouhouban_umu = {$daichou['jouhouban_umu']}
              ,anzentou_syoumeitou = {$daichou['anzentou_syoumeitou']}
              ,syadanji_riyuu = {$daichou['syadanji_riyuu']}
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
      $daichou['yokokuhyoushiki_umu']=$this->chkItem($daichou, 'yokokuhyoushiki_umu',1);
      $daichou['jouhouban_umu']=$this->chkItem($daichou, 'jouhouban_umu',1);
      $daichou['anzentou_syoumeitou']=$this->chkItem($daichou, 'anzentou_syoumeitou',2);
      $daichou['syadanji_riyuu']=$this->chkItem($daichou, 'syadanji_riyuu',2);
  }
}
