<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("FamEditModel.php");

// 台帳:防雪柵装置登録用
class FamEditModelBS extends FamEditModel {

  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
  }

  /***
   *  台帳:防雪柵装置の更新
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  public function saveDaichou($daichou) {
    log_message('info', "FamEditModelBS->saveDaichou");
    $this->setDaichouCommon($daichou); // 共通
    $this->setItem($daichou); // 防雪柵固有
    $sql= <<<SQL
            insert into rfs_t_daichou_bs (
              sno
              , daityou_no
              , zu_no
              , sekkei_sokudo
              , sakusyu_cd
              , saku_kbn_cd
              , saku_keishiki_cd
              , bikou_kikaku
              , kiso_keishiki_cd
              , kiso_keijou
              , sekou_company
              , i_maker_nm
              , i_katashiki
              , i_span_len
              , i_span_num
              , i_sakukou
              , t_maker_nm
              , t_katashiki
              , t_span_len
              , t_span_num
              , t_sakukou
              , haishi_dt_ryuu
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
              , {$daichou['daityou_no']}
              , {$daichou['zu_no']}
              , {$daichou['sekkei_sokudo']}
              , {$daichou['sakusyu_cd']}
              , {$daichou['saku_kbn_cd']}
              , {$daichou['saku_keishiki_cd']}
              , {$daichou['bikou_kikaku']}
              , {$daichou['kiso_keishiki_cd']}
              , {$daichou['kiso_keijou']}
              , {$daichou['sekou_company']}
              , {$daichou['i_maker_nm']}
              , {$daichou['i_katashiki']}
              , {$daichou['i_span_len']}
              , {$daichou['i_span_num']}
              , {$daichou['i_sakukou']}
              , {$daichou['t_maker_nm']}
              , {$daichou['t_katashiki']}
              , {$daichou['t_span_len']}
              , {$daichou['t_span_num']}
              , {$daichou['t_sakukou']}
              , {$daichou['haishi_dt_ryuu']}
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
            ON CONFLICT ON CONSTRAINT rfs_t_daichou_bs_pkey
            DO UPDATE SET
             daityou_no = {$daichou['daityou_no']}
              ,zu_no = {$daichou['zu_no']}
              ,sekkei_sokudo = {$daichou['sekkei_sokudo']}
              ,sakusyu_cd = {$daichou['sakusyu_cd']}
              ,saku_kbn_cd = {$daichou['saku_kbn_cd']}
              ,saku_keishiki_cd = {$daichou['saku_keishiki_cd']}
              ,bikou_kikaku = {$daichou['bikou_kikaku']}
              ,kiso_keishiki_cd = {$daichou['kiso_keishiki_cd']}
              ,kiso_keijou = {$daichou['kiso_keijou']}
              ,sekou_company = {$daichou['sekou_company']}
              ,i_maker_nm = {$daichou['i_maker_nm']}
              ,i_katashiki = {$daichou['i_katashiki']}
              ,i_span_len = {$daichou['i_span_len']}
              ,i_span_num = {$daichou['i_span_num']}
              ,i_sakukou = {$daichou['i_sakukou']}
              ,t_maker_nm = {$daichou['t_maker_nm']}
              ,t_katashiki = {$daichou['t_katashiki']}
              ,t_span_len = {$daichou['t_span_len']}
              ,t_span_num = {$daichou['t_span_num']}
              ,t_sakukou = {$daichou['t_sakukou']}
              ,haishi_dt_ryuu = {$daichou['haishi_dt_ryuu']}
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
    $daichou['daityou_no']=$this->chkItem($daichou,'daityou_no',2);
    $daichou['zu_no']=$this->chkItem($daichou,'zu_no',2);
    $daichou['sekkei_sokudo']=$this->chkItem($daichou,'sekkei_sokudo',2);
    $daichou['sakusyu_cd']=$this->chkItem($daichou,'sakusyu_cd',1);
    $daichou['saku_kbn_cd']=$this->chkItem($daichou,'saku_kbn_cd',1);
    $daichou['saku_keishiki_cd']=$this->chkItem($daichou,'saku_keishiki_cd',1);
    $daichou['bikou_kikaku']=$this->chkItem($daichou,'bikou_kikaku',2);
    $daichou['kiso_keishiki_cd']=$this->chkItem($daichou,'kiso_keishiki_cd',1);
    $daichou['kiso_keijou']=$this->chkItem($daichou,'kiso_keijou',2);
    $daichou['sekou_company']=$this->chkItem($daichou,'sekou_company',2);
    $daichou['i_maker_nm']=$this->chkItem($daichou,'i_maker_nm',2);
    $daichou['i_katashiki']=$this->chkItem($daichou,'i_katashiki',2);
    $daichou['i_span_len']=$this->chkItem($daichou,'i_span_len',2);
    $daichou['i_span_num']=$this->chkItem($daichou,'i_span_num',2);
    $daichou['i_sakukou']=$this->chkItem($daichou,'i_sakukou',2);
    $daichou['t_maker_nm']=$this->chkItem($daichou,'t_maker_nm',2);
    $daichou['t_katashiki']=$this->chkItem($daichou,'t_katashiki',2);
    $daichou['t_span_len']=$this->chkItem($daichou,'t_span_len',2);
    $daichou['t_span_num']=$this->chkItem($daichou,'t_span_num',2);
    $daichou['t_sakukou']=$this->chkItem($daichou,'t_sakukou',2);
    $daichou['haishi_dt_ryuu']=$this->chkItem($daichou,'haishi_dt_ryuu',2);
  }
}
