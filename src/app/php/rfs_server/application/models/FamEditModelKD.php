<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("FamEditModel.php");

// 台帳:観測局登録用
class FamEditModelKD extends FamEditModel {

  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
  }

  /***
   *  台帳:観測局の更新
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  public function saveDaichou($daichou) {
    log_message('info', "FamEditModelKD->saveDaichou");
    $this->setDaichouCommon($daichou); // 共通
    $this->setItem($daichou); // 観測局固有
    $this->setDaichouTsuushin($daichou); // 通信回線
    $this->setDaichouDenki($daichou); // 電気
    $sql= <<<SQL
            insert into rfs_t_daichou_kd (
              sno
              , unei_kbn_cd
              , kisyoudai_rgst
              , kisyouda_rgst_yyyy
              , humei
              , musen_nm
              , musen_menkyo_no
              , kion_kei
              , huukouhuusoku_kei
              , usetsuryou_kei
              , romenondo_kei
              , shitei_kei
              , sekisetsushin_kei
              , romensekisetsushin_kei
              , romentouketsu_kei
              , usetsuryou_kei_kentei_dt
              , mikentei_u
              , huukouhuusoku_kei_kentei_dt
              , mikentei_h
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
              , {$daichou['unei_kbn_cd']}
              , {$daichou['kisyoudai_rgst']}
              , {$daichou['kisyouda_rgst_yyyy']}
              , {$daichou['humei']}
              , {$daichou['musen_nm']}
              , {$daichou['musen_menkyo_no']}
              , {$daichou['kion_kei']}
              , {$daichou['huukouhuusoku_kei']}
              , {$daichou['usetsuryou_kei']}
              , {$daichou['romenondo_kei']}
              , {$daichou['shitei_kei']}
              , {$daichou['sekisetsushin_kei']}
              , {$daichou['romensekisetsushin_kei']}
              , {$daichou['romentouketsu_kei']}
              , {$daichou['usetsuryou_kei_kentei_dt']}
              , {$daichou['mikentei_u']}
              , {$daichou['huukouhuusoku_kei_kentei_dt']}
              , {$daichou['mikentei_h']}
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
            ON CONFLICT ON CONSTRAINT rfs_t_daichou_kd_pkey
            DO UPDATE SET
               unei_kbn_cd = {$daichou['unei_kbn_cd']}
              ,kisyoudai_rgst = {$daichou['kisyoudai_rgst']}
              ,kisyouda_rgst_yyyy = {$daichou['kisyouda_rgst_yyyy']}
              ,humei = {$daichou['humei']}
              ,musen_nm = {$daichou['musen_nm']}
              ,musen_menkyo_no = {$daichou['musen_menkyo_no']}
              ,kion_kei = {$daichou['kion_kei']}
              ,huukouhuusoku_kei = {$daichou['huukouhuusoku_kei']}
              ,usetsuryou_kei = {$daichou['usetsuryou_kei']}
              ,romenondo_kei = {$daichou['romenondo_kei']}
              ,shitei_kei = {$daichou['shitei_kei']}
              ,sekisetsushin_kei = {$daichou['sekisetsushin_kei']}
              ,romensekisetsushin_kei = {$daichou['romensekisetsushin_kei']}
              ,romentouketsu_kei = {$daichou['romentouketsu_kei']}
              ,usetsuryou_kei_kentei_dt = {$daichou['usetsuryou_kei_kentei_dt']}
              ,mikentei_u = {$daichou['mikentei_u']}
              ,huukouhuusoku_kei_kentei_dt = {$daichou['huukouhuusoku_kei_kentei_dt']}
              ,mikentei_h = {$daichou['mikentei_h']}
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
    $daichou['unei_kbn_cd']=$this->chkItem($daichou, 'unei_kbn_cd',1);
    $daichou['humei']=$this->chkItem($daichou, 'humei',1);
    if ($daichou['humei']==1) {
      $daichou['kisyoudai_rgst']="null";
      $daichou['kisyouda_rgst_yyyy']="null";
    }else{
      if (isset($daichou['kisyoudai_rgst_row'])) {
        if (count($daichou['kisyoudai_rgst_row'])>0) {
          $daichou['kisyoudai_rgst']=pg_escape_literal($daichou['kisyoudai_rgst_row']['gengou']);
          $daichou['kisyouda_rgst_yyyy']=$daichou['kisyoudai_rgst_row']['year'];
        }else{
          $daichou['kisyoudai_rgst']="null";
          $daichou['kisyouda_rgst_yyyy']="null";
        }
      }else{
        $daichou['kisyoudai_rgst']="null";
        $daichou['kisyouda_rgst_yyyy']="null";
      }
    }
    $daichou['musen_nm']=$this->chkItem($daichou, 'musen_nm',2);
    $daichou['musen_menkyo_no']=$this->chkItem($daichou, 'musen_menkyo_no',2);
    $daichou['kion_kei']=$this->chkItem($daichou, 'kion_kei',1);
    $daichou['huukouhuusoku_kei']=$this->chkItem($daichou, 'huukouhuusoku_kei',1);
    $daichou['usetsuryou_kei']=$this->chkItem($daichou, 'usetsuryou_kei',1);
    $daichou['romenondo_kei']=$this->chkItem($daichou, 'romenondo_kei',1);
    $daichou['shitei_kei']=$this->chkItem($daichou, 'shitei_kei',1);
    $daichou['sekisetsushin_kei']=$this->chkItem($daichou, 'sekisetsushin_kei',1);
    $daichou['romensekisetsushin_kei']=$this->chkItem($daichou, 'romensekisetsushin_kei',1);
    $daichou['romentouketsu_kei']=$this->chkItem($daichou, 'romentouketsu_kei',1);
    $daichou['mikentei_u']=$this->chkItem($daichou, 'mikentei_u',1);
    if ($daichou['mikentei_u']==1) {
      $daichou['usetsuryou_kei_kentei_dt']="null";
    }else{
      $daichou['usetsuryou_kei_kentei_dt']=$this->chkItem($daichou, 'usetsuryou_kei_kentei_dt',2);
    }
    $daichou['mikentei_h']=$this->chkItem($daichou, 'mikentei_h',1);
    if ($daichou['mikentei_h']==1) {
      $daichou['huukouhuusoku_kei_kentei_dt']="null";
    }else{
      $daichou['huukouhuusoku_kei_kentei_dt']=$this->chkItem($daichou, 'huukouhuusoku_kei_kentei_dt',2);
    }
  }
}
