<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("FamEditModel.php");

// 台帳:緑化樹木登録用
class FamEditModelSK extends FamEditModel {

  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
  }

  /***
   *  台帳:緑化樹木の更新
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  public function saveDaichou($daichou) {
    log_message('info', "FamEditModelSK->saveDaichou");
    $this->setDaichouCommon($daichou); // 共通
    $this->setItem($daichou); // 緑化公園固有
    $sql= <<<SQL
            insert into rfs_t_daichou_sk (
              sno
              , bangou
              , kbn_c_cd
              , ichi_c_cd
              , tyuusya_kouen_tou
              , akimasu_menseki
              , kouboku_jumoku_cd1
              , kouboku_num1
              , kouboku_jumoku_cd2
              , kouboku_num2
              , kouboku_jumoku_cd3
              , kouboku_num3
              , kouboku_jumoku_cd4
              , kouboku_num4
              , kouboku_jumoku_cd5
              , kouboku_num5
              , kouboku_jumoku_cd6
              , kouboku_num6
              , kouboku_jumoku_cd7
              , kouboku_num7
              , kouboku_jumoku_cd8
              , kouboku_num8
              , kouboku_jumoku_cd9
              , kouboku_num9
              , kouboku_jumoku_cd10
              , kouboku_num10
              , tyuuteiboku_jumoku_cd1
              , tyuuteiboku_num1
              , tyuuteiboku_jumoku_cd2
              , tyuuteiboku_num2
              , tyuuteiboku_jumoku_cd3
              , tyuuteiboku_num3
              , tyuuteiboku_jumoku_cd4
              , tyuuteiboku_num4
              , tyuuteiboku_jumoku_cd5
              , tyuuteiboku_num5
              , tyuuteiboku_jumoku_cd6
              , tyuuteiboku_num6
              , tyuuteiboku_jumoku_cd7
              , tyuuteiboku_num7
              , tyuuteiboku_jumoku_cd8
              , tyuuteiboku_num8
              , tyuuteiboku_jumoku_cd9
              , tyuuteiboku_num9
              , tyuuteiboku_jumoku_cd10
              , tyuuteiboku_num10
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
              , {$daichou['bangou']}
              , {$daichou['kbn_c_cd']}
              , {$daichou['ichi_c_cd']}
              , {$daichou['tyuusya_kouen_tou']}
              , {$daichou['akimasu_menseki']}
              , {$daichou['kouboku_jumoku_cd1']}
              , {$daichou['kouboku_num1']}
              , {$daichou['kouboku_jumoku_cd2']}
              , {$daichou['kouboku_num2']}
              , {$daichou['kouboku_jumoku_cd3']}
              , {$daichou['kouboku_num3']}
              , {$daichou['kouboku_jumoku_cd4']}
              , {$daichou['kouboku_num4']}
              , {$daichou['kouboku_jumoku_cd5']}
              , {$daichou['kouboku_num5']}
              , {$daichou['kouboku_jumoku_cd6']}
              , {$daichou['kouboku_num6']}
              , {$daichou['kouboku_jumoku_cd7']}
              , {$daichou['kouboku_num7']}
              , {$daichou['kouboku_jumoku_cd8']}
              , {$daichou['kouboku_num8']}
              , {$daichou['kouboku_jumoku_cd9']}
              , {$daichou['kouboku_num9']}
              , {$daichou['kouboku_jumoku_cd10']}
              , {$daichou['kouboku_num10']}
              , {$daichou['tyuuteiboku_jumoku_cd1']}
              , {$daichou['tyuuteiboku_num1']}
              , {$daichou['tyuuteiboku_jumoku_cd2']}
              , {$daichou['tyuuteiboku_num2']}
              , {$daichou['tyuuteiboku_jumoku_cd3']}
              , {$daichou['tyuuteiboku_num3']}
              , {$daichou['tyuuteiboku_jumoku_cd4']}
              , {$daichou['tyuuteiboku_num4']}
              , {$daichou['tyuuteiboku_jumoku_cd5']}
              , {$daichou['tyuuteiboku_num5']}
              , {$daichou['tyuuteiboku_jumoku_cd6']}
              , {$daichou['tyuuteiboku_num6']}
              , {$daichou['tyuuteiboku_jumoku_cd7']}
              , {$daichou['tyuuteiboku_num7']}
              , {$daichou['tyuuteiboku_jumoku_cd8']}
              , {$daichou['tyuuteiboku_num8']}
              , {$daichou['tyuuteiboku_jumoku_cd9']}
              , {$daichou['tyuuteiboku_num9']}
              , {$daichou['tyuuteiboku_jumoku_cd10']}
              , {$daichou['tyuuteiboku_num10']}
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
            ON CONFLICT ON CONSTRAINT rfs_t_daichou_sk_pkey
            DO UPDATE SET
              bangou = {$daichou['bangou']}
              ,kbn_c_cd = {$daichou['kbn_c_cd']}
              ,ichi_c_cd = {$daichou['ichi_c_cd']}
              ,tyuusya_kouen_tou = {$daichou['tyuusya_kouen_tou']}
              ,akimasu_menseki = {$daichou['akimasu_menseki']}
              ,kouboku_jumoku_cd1 = {$daichou['kouboku_jumoku_cd1']}
              ,kouboku_num1 = {$daichou['kouboku_num1']}
              ,kouboku_jumoku_cd2 = {$daichou['kouboku_jumoku_cd2']}
              ,kouboku_num2 = {$daichou['kouboku_num2']}
              ,kouboku_jumoku_cd3 = {$daichou['kouboku_jumoku_cd3']}
              ,kouboku_num3 = {$daichou['kouboku_num3']}
              ,kouboku_jumoku_cd4 = {$daichou['kouboku_jumoku_cd4']}
              ,kouboku_num4 = {$daichou['kouboku_num4']}
              ,kouboku_jumoku_cd5 = {$daichou['kouboku_jumoku_cd5']}
              ,kouboku_num5 = {$daichou['kouboku_num5']}
              ,kouboku_jumoku_cd6 = {$daichou['kouboku_jumoku_cd6']}
              ,kouboku_num6 = {$daichou['kouboku_num6']}
              ,kouboku_jumoku_cd7 = {$daichou['kouboku_jumoku_cd7']}
              ,kouboku_num7 = {$daichou['kouboku_num7']}
              ,kouboku_jumoku_cd8 = {$daichou['kouboku_jumoku_cd8']}
              ,kouboku_num8 = {$daichou['kouboku_num8']}
              ,kouboku_jumoku_cd9 = {$daichou['kouboku_jumoku_cd9']}
              ,kouboku_num9 = {$daichou['kouboku_num9']}
              ,kouboku_jumoku_cd10 = {$daichou['kouboku_jumoku_cd10']}
              ,kouboku_num10 = {$daichou['kouboku_num10']}
              ,tyuuteiboku_jumoku_cd1 = {$daichou['tyuuteiboku_jumoku_cd1']}
              ,tyuuteiboku_num1 = {$daichou['tyuuteiboku_num1']}
              ,tyuuteiboku_jumoku_cd2 = {$daichou['tyuuteiboku_jumoku_cd2']}
              ,tyuuteiboku_num2 = {$daichou['tyuuteiboku_num2']}
              ,tyuuteiboku_jumoku_cd3 = {$daichou['tyuuteiboku_jumoku_cd3']}
              ,tyuuteiboku_num3 = {$daichou['tyuuteiboku_num3']}
              ,tyuuteiboku_jumoku_cd4 = {$daichou['tyuuteiboku_jumoku_cd4']}
              ,tyuuteiboku_num4 = {$daichou['tyuuteiboku_num4']}
              ,tyuuteiboku_jumoku_cd5 = {$daichou['tyuuteiboku_jumoku_cd5']}
              ,tyuuteiboku_num5 = {$daichou['tyuuteiboku_num5']}
              ,tyuuteiboku_jumoku_cd6 = {$daichou['tyuuteiboku_jumoku_cd6']}
              ,tyuuteiboku_num6 = {$daichou['tyuuteiboku_num6']}
              ,tyuuteiboku_jumoku_cd7 = {$daichou['tyuuteiboku_jumoku_cd7']}
              ,tyuuteiboku_num7 = {$daichou['tyuuteiboku_num7']}
              ,tyuuteiboku_jumoku_cd8 = {$daichou['tyuuteiboku_jumoku_cd8']}
              ,tyuuteiboku_num8 = {$daichou['tyuuteiboku_num8']}
              ,tyuuteiboku_jumoku_cd9 = {$daichou['tyuuteiboku_jumoku_cd9']}
              ,tyuuteiboku_num9 = {$daichou['tyuuteiboku_num9']}
              ,tyuuteiboku_jumoku_cd10 = {$daichou['tyuuteiboku_jumoku_cd10']}
              ,tyuuteiboku_num10 = {$daichou['tyuuteiboku_num10']}
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
    $daichou['bangou']=$this->chkItem($daichou,'bangou',1);
    $daichou['kbn_c_cd']=$this->chkItem($daichou,'kbn_c_cd',1);
    $daichou['ichi_c_cd']=$this->chkItem($daichou,'ichi_c_cd',1);
    $daichou['tyuusya_kouen_tou']=$this->chkItem($daichou,'tyuusya_kouen_tou',2);
    $daichou['akimasu_menseki']=$this->chkItem($daichou,'akimasu_menseki',1);
    // 10行
    for ($i=1;$i<=10;$i++) {

      $daichou['kouboku_jumoku_cd'.$i]=$daichou['kouboku_jumoku_cd'][$i];
      $daichou['kouboku_num'.$i]=$daichou['kouboku_num'][$i];
      $daichou['tyuuteiboku_jumoku_cd'.$i]=$daichou['tyuuteiboku_jumoku_cd'][$i];
      $daichou['tyuuteiboku_num'.$i]=$daichou['tyuuteiboku_num'][$i];
      $daichou['kouboku_jumoku_cd'.$i]=$this->chkItem($daichou,'kouboku_jumoku_cd'.$i,1);
      $daichou['kouboku_num'.$i]=$this->chkItem($daichou,'kouboku_num'.$i,1);
      $daichou['tyuuteiboku_jumoku_cd'.$i]=$this->chkItem($daichou,'tyuuteiboku_jumoku_cd'.$i,1);
      $daichou['tyuuteiboku_num'.$i]=$this->chkItem($daichou,'tyuuteiboku_num'.$i,1);
    }
  }
}
