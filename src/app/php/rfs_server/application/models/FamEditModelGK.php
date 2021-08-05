<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("FamEditModel.php");

// 台帳:カルテ点検（落石・崩壊、急流河川）登録用
class FamEditModelGK extends FamEditModel {

  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
  }

  /***
   *  台帳:カルテ点検（落石・崩壊、急流河川）の更新
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  public function saveDaichou($daichou) {
    log_message('info', "FamEditModelGK->saveDaichou");
    $this->setDaichouCommon($daichou); // 施設区分に関わらず共通のデータ
    // $this->setItem($daichou); // この施設区分に固有のもの
    $sql= <<<SQL
            insert into rfs_t_daichou_gk (
              sno
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
            ON CONFLICT ON CONSTRAINT rfs_t_daichou_gk_pkey
            DO UPDATE SET
              bikou = {$daichou['bikou']}
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

//   protected function setItem(&$daichou) {
//     $daichou['daityou_no']=$this->chkItem($daichou,'daityou_no',2);
//     $daichou['zu_no']=$this->chkItem($daichou,'zu_no',2);
//     $daichou['sekkei_sokudo']=$this->chkItem($daichou,'sekkei_sokudo',2);
//     $daichou['sakusyu_cd']=$this->chkItem($daichou,'sakusyu_cd',1);
//     $daichou['saku_kbn_cd']=$this->chkItem($daichou,'saku_kbn_cd',1);
//     $daichou['saku_keishiki_cd']=$this->chkItem($daichou,'saku_keishiki_cd',1);
//     $daichou['bikou_kikaku']=$this->chkItem($daichou,'bikou_kikaku',2);
//     $daichou['kiso_keishiki_cd']=$this->chkItem($daichou,'kiso_keishiki_cd',1);
//     $daichou['kiso_keijou']=$this->chkItem($daichou,'kiso_keijou',2);
//     $daichou['sekou_company']=$this->chkItem($daichou,'sekou_company',2);
//     $daichou['i_maker_nm']=$this->chkItem($daichou,'i_maker_nm',2);
//     $daichou['i_katashiki']=$this->chkItem($daichou,'i_katashiki',2);
//     $daichou['i_span_len']=$this->chkItem($daichou,'i_span_len',2);
//     $daichou['i_span_num']=$this->chkItem($daichou,'i_span_num',2);
//     $daichou['i_sakukou']=$this->chkItem($daichou,'i_sakukou',2);
//     $daichou['t_maker_nm']=$this->chkItem($daichou,'t_maker_nm',2);
//     $daichou['t_katashiki']=$this->chkItem($daichou,'t_katashiki',2);
//     $daichou['t_span_len']=$this->chkItem($daichou,'t_span_len',2);
//     $daichou['t_span_num']=$this->chkItem($daichou,'t_span_num',2);
//     $daichou['t_sakukou']=$this->chkItem($daichou,'t_sakukou',2);
//     $daichou['haishi_dt_ryuu']=$this->chkItem($daichou,'haishi_dt_ryuu',2);
//   }
}
