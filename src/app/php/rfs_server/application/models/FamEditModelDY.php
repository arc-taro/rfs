<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("FamEditModel.php");

// 台帳:擁壁登録用
class FamEditModelDY extends FamEditModel {

  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
  }

  /***
   *  台帳:擁壁の更新
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  public function saveDaichou($daichou) {
    log_message('info', "RgstDaichouDY->saveDaichou");
    $this->setDaichouCommon($daichou); // 共通
    $this->setItem($daichou); // 擁壁固有
    $sql= <<<SQL
            insert into rfs_t_daichou_dy (
              sno
              , daityou_no
              , genkyou_nendo
              , genkyou_nendo_yyyy
              , genkyou_no
              , bouten_kanri_no
              , bikou_kouhou
              , hekikou_kou
              , hekikou_tei
              , hekimen_koubai
              , ichi_from_center
              , sekou_company
              , sekkei_consul
              , zumen_umu
              , kouzou_sekisansyo_umu
              , cad_umu
              , tuukoudeme_dt1
              , naiyou1
              , tuukoudeme_dt2
              , naiyou2
              , tuukoudeme_dt3
              , naiyou3
              , tokki_jikou
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
              , {$daichou['genkyou_nendo']}
              , {$daichou['genkyou_nendo_yyyy']}
              , {$daichou['genkyou_no']}
              , {$daichou['bouten_kanri_no']}
              , {$daichou['bikou_kouhou']}
              , {$daichou['hekikou_kou']}
              , {$daichou['hekikou_tei']}
              , {$daichou['hekimen_koubai']}
              , {$daichou['ichi_from_center']}
              , {$daichou['sekou_company']}
              , {$daichou['sekkei_consul']}
              , {$daichou['zumen_umu']}
              , {$daichou['kouzou_sekisansyo_umu']}
              , {$daichou['cad_umu']}
              , {$daichou['tuukoudeme_dt1']}
              , {$daichou['naiyou1']}
              , {$daichou['tuukoudeme_dt2']}
              , {$daichou['naiyou2']}
              , {$daichou['tuukoudeme_dt3']}
              , {$daichou['naiyou3']}
              , {$daichou['tokki_jikou']}
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
            ON CONFLICT ON CONSTRAINT rfs_t_daichou_dy_pkey
            DO UPDATE SET
              daityou_no = {$daichou['daityou_no']}
              ,genkyou_nendo = {$daichou['genkyou_nendo']}
              ,genkyou_nendo_yyyy = {$daichou['genkyou_nendo_yyyy']}
              ,genkyou_no = {$daichou['genkyou_no']}
              ,bouten_kanri_no = {$daichou['bouten_kanri_no']}
              ,bikou_kouhou = {$daichou['bikou_kouhou']}
              ,hekikou_kou = {$daichou['hekikou_kou']}
              ,hekikou_tei = {$daichou['hekikou_tei']}
              ,hekimen_koubai = {$daichou['hekimen_koubai']}
              ,ichi_from_center = {$daichou['ichi_from_center']}
              ,sekou_company = {$daichou['sekou_company']}
              ,sekkei_consul = {$daichou['sekkei_consul']}
              ,zumen_umu = {$daichou['zumen_umu']}
              ,kouzou_sekisansyo_umu = {$daichou['kouzou_sekisansyo_umu']}
              ,cad_umu = {$daichou['cad_umu']}
              ,tuukoudeme_dt1 = {$daichou['tuukoudeme_dt1']}
              ,naiyou1 = {$daichou['naiyou1']}
              ,tuukoudeme_dt2 = {$daichou['tuukoudeme_dt2']}
              ,naiyou2 = {$daichou['naiyou2']}
              ,tuukoudeme_dt3 = {$daichou['tuukoudeme_dt3']}
              ,naiyou3 = {$daichou['naiyou3']}
              ,tokki_jikou = {$daichou['tokki_jikou']}
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
    if (isset($daichou['genkyou_nendo_row'])) {
      if (count($daichou['genkyou_nendo_row'])>0) {
        $daichou['genkyou_nendo']=pg_escape_literal($daichou['genkyou_nendo_row']['gengou']);
        $daichou['genkyou_nendo_yyyy']=$daichou['genkyou_nendo_row']['year'];
      }else{
        $daichou['genkyou_nendo']="null";
        $daichou['genkyou_nendo_yyyy']="null";
      }
    }else{
      $daichou['genkyou_nendo']="null";
      $daichou['genkyou_nendo_yyyy']="null";
    }
    $daichou['genkyou_no']=$this->chkItem($daichou,'genkyou_no',2);
    $daichou['bouten_kanri_no']=$this->chkItem($daichou,'bouten_kanri_no',2);
    $daichou['bikou_kouhou']=$this->chkItem($daichou,'bikou_kouhou',2);
    $daichou['hekikou_kou']=$this->chkItem($daichou,'hekikou_kou',1);
    $daichou['hekikou_tei']=$this->chkItem($daichou,'hekikou_tei',1);
    $daichou['hekimen_koubai']=$this->chkItem($daichou,'hekimen_koubai',2);
    $daichou['ichi_from_center']=$this->chkItem($daichou,'ichi_from_center',2);
    $daichou['sekou_company']=$this->chkItem($daichou,'sekou_company',2);
    $daichou['sekkei_consul']=$this->chkItem($daichou,'sekkei_consul',2);
    $daichou['zumen_umu']=$this->chkItem($daichou,'zumen_umu',1);
    $daichou['kouzou_sekisansyo_umu']=$this->chkItem($daichou,'kouzou_sekisansyo_umu',1);
    $daichou['cad_umu']=$this->chkItem($daichou,'cad_umu',1);
    $daichou['tuukoudeme_dt1']=$this->chkItem($daichou,'tuukoudeme_dt1',2);
    $daichou['naiyou1']=$this->chkItem($daichou,'naiyou1',2);
    $daichou['tuukoudeme_dt2']=$this->chkItem($daichou,'tuukoudeme_dt2',2);
    $daichou['naiyou2']=$this->chkItem($daichou,'naiyou2',2);
    $daichou['tuukoudeme_dt3']=$this->chkItem($daichou,'tuukoudeme_dt3',2);
    $daichou['naiyou3']=$this->chkItem($daichou,'naiyou3',2);
    $daichou['tokki_jikou']=$this->chkItem($daichou,'tokki_jikou',2);
    $daichou['haishi_dt_ryuu']=$this->chkItem($daichou,'haishi_dt_ryuu',2);
  }
}
