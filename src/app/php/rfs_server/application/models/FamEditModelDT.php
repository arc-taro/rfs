<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("FamEditModel.php");

// 台帳:ドット線登録用
class FamEditModelDT extends FamEditModel {

  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
  }

  /***
   *  台帳:ドット線の更新
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  public function saveDaichou($daichou) {
    log_message('info', "FamEditModelDT->saveDaichou");
    $this->setDaichouCommon($daichou); // 共通
    $this->setItem($daichou); // ドット線固有
    $sql= <<<SQL
            insert into rfs_t_daichou_dt (
              sno
              , kousa_rosen_nm
              , kankatsu_keisatsusyo
              , secchi_honsuu
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
              , {$daichou['kousa_rosen_nm']}
              , {$daichou['kankatsu_keisatsusyo']}
              , {$daichou['secchi_honsuu']}
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
            ON CONFLICT ON CONSTRAINT rfs_t_daichou_dt_pkey
            DO UPDATE SET
              kousa_rosen_nm = {$daichou['kousa_rosen_nm']}
              ,kankatsu_keisatsusyo = {$daichou['kankatsu_keisatsusyo']}
              ,secchi_honsuu = {$daichou['secchi_honsuu']}
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
      $daichou['kousa_rosen_nm']=$this->chkItem($daichou, 'kousa_rosen_nm',2);
      $daichou['kankatsu_keisatsusyo']=$this->chkItem($daichou, 'kankatsu_keisatsusyo',2);
      $daichou['secchi_honsuu']=$this->chkItem($daichou, 'secchi_honsuu',1);
  }
}
