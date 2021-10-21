<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("FamEditModel.php");

// 台帳:トンネル登録用
class FamEditModelTT extends FamEditModel {

  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
  }

  /***
   *  台帳:トンネルの更新
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  public function saveDaichou($daichou) {
    log_message('info', "FamEditModelTT->saveDaichou");
    $this->setDaichouCommon($daichou); // 共通
    $this->setItem($daichou); // トンネル固有
    $this->setDaichouTsuushin($daichou); // 通信回線
    $this->setDaichouDenki($daichou); // 電気
    $sql= <<<SQL
            insert into rfs_t_daichou_tt (
              sno
              , toukyuu_cd
              , shisetsu_renzoku_cd
              , kussaku
              , hekimen_kbn_cd
              , romen_syu
              , tenkenkou
              , kanshi_camera
              , kanki_shisetsu_cd
              , bousuiban
              , secchi_kasyo_juudan_cd
              , secchi_kasyo_oudan_cd
              , syoumei_shisetsu_cd
              , syoumei_kisuu
              , tuuhou_souchi_cd
              , push_button_num
              , hijou_tel_num
              , hijou_keihou_souchi_cd
              , keihou_hyoujiban_num
              , tenmetsutou_num
              , syouka_setsubi_cd
              , syoukaki_num
              , syoukasen
              , sonota_setsubi_cd
              , yuudou_hyoujiban_num
              , kasai_kenchiki
              , musen_tsuushin_setsubi
              , radio_re_housou_setsubi
              , warikomi_housou
              , radio_re_musenkyoka_num
              , musen_kyoka_dt
              , humei
              , jieisen_denki
              , jieisen_tsuushin
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
              , mabiki_umu
              , taiou_dt
              , lamp_syu1
              , lamp_su1
              , lamp_syu2
              , lamp_su2
              , lamp_syu3
              , lamp_su3
              , lamp_syu4
              , lamp_su4
              , lamp_syu5
              , lamp_su5
              , lamp_syu6
              , lamp_su6
              , lamp_syu7
              , lamp_su7
              , lamp_syu8
              , lamp_su8
              , lamp_syu9
              , lamp_su9
              , lamp_syu10
              , lamp_su10
              , lamp_syu11
              , lamp_su11
              , lamp_syu12
              , lamp_su12
            )values(
              {$daichou['sno']}
              , {$daichou['toukyuu_cd']}
              , {$daichou['shisetsu_renzoku_cd']}
              , {$daichou['kussaku']}
              , {$daichou['hekimen_kbn_cd']}
              , {$daichou['romen_syu']}
              , {$daichou['tenkenkou']}
              , {$daichou['kanshi_camera']}
              , {$daichou['kanki_shisetsu_cd']}
              , {$daichou['bousuiban']}
              , {$daichou['secchi_kasyo_juudan_cd']}
              , {$daichou['secchi_kasyo_oudan_cd']}
              , {$daichou['syoumei_shisetsu_cd']}
              , {$daichou['syoumei_kisuu']}
              , {$daichou['tuuhou_souchi_cd']}
              , {$daichou['push_button_num']}
              , {$daichou['hijou_tel_num']}
              , {$daichou['hijou_keihou_souchi_cd']}
              , {$daichou['keihou_hyoujiban_num']}
              , {$daichou['tenmetsutou_num']}
              , {$daichou['syouka_setsubi_cd']}
              , {$daichou['syoukaki_num']}
              , {$daichou['syoukasen']}
              , {$daichou['sonota_setsubi_cd']}
              , {$daichou['yuudou_hyoujiban_num']}
              , {$daichou['kasai_kenchiki']}
              , {$daichou['musen_tsuushin_setsubi']}
              , {$daichou['radio_re_housou_setsubi']}
              , {$daichou['warikomi_housou']}
              , {$daichou['radio_re_musenkyoka_num']}
              , {$daichou['musen_kyoka_dt']}
              , {$daichou['humei']}
              , {$daichou['jieisen_denki']}
              , {$daichou['jieisen_tsuushin']}
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
              , {$daichou['mabiki_umu']}
              , {$daichou['taiou_dt']}
              , {$daichou['lamp_syu1']}
              , {$daichou['lamp_su1']}
              , {$daichou['lamp_syu2']}
              , {$daichou['lamp_su2']}
              , {$daichou['lamp_syu3']}
              , {$daichou['lamp_su3']}
              , {$daichou['lamp_syu4']}
              , {$daichou['lamp_su4']}
              , {$daichou['lamp_syu5']}
              , {$daichou['lamp_su5']}
              , {$daichou['lamp_syu6']}
              , {$daichou['lamp_su6']}
              , {$daichou['lamp_syu7']}
              , {$daichou['lamp_su7']}
              , {$daichou['lamp_syu8']}
              , {$daichou['lamp_su8']}
              , {$daichou['lamp_syu9']}
              , {$daichou['lamp_su9']}
              , {$daichou['lamp_syu10']}
              , {$daichou['lamp_su10']}
              , {$daichou['lamp_syu11']}
              , {$daichou['lamp_su11']}
              , {$daichou['lamp_syu12']}
              , {$daichou['lamp_su12']}
            )
            ON CONFLICT ON CONSTRAINT rfs_t_daichou_tt_pkey
            DO UPDATE SET
              toukyuu_cd = {$daichou['toukyuu_cd']}
              ,shisetsu_renzoku_cd = {$daichou['shisetsu_renzoku_cd']}
              ,kussaku = {$daichou['kussaku']}
              ,hekimen_kbn_cd = {$daichou['hekimen_kbn_cd']}
              ,romen_syu = {$daichou['romen_syu']}
              ,tenkenkou = {$daichou['tenkenkou']}
              ,kanshi_camera = {$daichou['kanshi_camera']}
              ,kanki_shisetsu_cd = {$daichou['kanki_shisetsu_cd']}
              ,bousuiban = {$daichou['bousuiban']}
              ,secchi_kasyo_juudan_cd = {$daichou['secchi_kasyo_juudan_cd']}
              ,secchi_kasyo_oudan_cd = {$daichou['secchi_kasyo_oudan_cd']}
              ,syoumei_shisetsu_cd = {$daichou['syoumei_shisetsu_cd']}
              ,syoumei_kisuu = {$daichou['syoumei_kisuu']}
              ,tuuhou_souchi_cd = {$daichou['tuuhou_souchi_cd']}
              ,push_button_num = {$daichou['push_button_num']}
              ,hijou_tel_num = {$daichou['hijou_tel_num']}
              ,hijou_keihou_souchi_cd = {$daichou['hijou_keihou_souchi_cd']}
              ,keihou_hyoujiban_num = {$daichou['keihou_hyoujiban_num']}
              ,tenmetsutou_num = {$daichou['tenmetsutou_num']}
              ,syouka_setsubi_cd = {$daichou['syouka_setsubi_cd']}
              ,syoukaki_num = {$daichou['syoukaki_num']}
              ,syoukasen = {$daichou['syoukasen']}
              ,sonota_setsubi_cd = {$daichou['sonota_setsubi_cd']}
              ,yuudou_hyoujiban_num = {$daichou['yuudou_hyoujiban_num']}
              ,kasai_kenchiki = {$daichou['kasai_kenchiki']}
              ,musen_tsuushin_setsubi = {$daichou['musen_tsuushin_setsubi']}
              ,radio_re_housou_setsubi = {$daichou['radio_re_housou_setsubi']}
              ,warikomi_housou = {$daichou['warikomi_housou']}
              ,radio_re_musenkyoka_num = {$daichou['radio_re_musenkyoka_num']}
              ,musen_kyoka_dt = {$daichou['musen_kyoka_dt']}
              ,humei = {$daichou['humei']}
              ,jieisen_denki = {$daichou['jieisen_denki']}
              ,jieisen_tsuushin = {$daichou['jieisen_tsuushin']}
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
              ,mabiki_umu = {$daichou['mabiki_umu']}
              ,taiou_dt = {$daichou['taiou_dt']}
              ,lamp_syu1 = {$daichou['lamp_syu1']}
              ,lamp_su1 = {$daichou['lamp_su1']}
              ,lamp_syu2 = {$daichou['lamp_syu2']}
              ,lamp_su2 = {$daichou['lamp_su2']}
              ,lamp_syu3 = {$daichou['lamp_syu3']}
              ,lamp_su3 = {$daichou['lamp_su3']}
              ,lamp_syu4 = {$daichou['lamp_syu4']}
              ,lamp_su4 = {$daichou['lamp_su4']}
              ,lamp_syu5 = {$daichou['lamp_syu5']}
              ,lamp_su5 = {$daichou['lamp_su5']}
              ,lamp_syu6 = {$daichou['lamp_syu6']}
              ,lamp_su6 = {$daichou['lamp_su6']}
              ,lamp_syu7 = {$daichou['lamp_syu7']}
              ,lamp_su7 = {$daichou['lamp_su7']}
              ,lamp_syu8 = {$daichou['lamp_syu8']}
              ,lamp_su8 = {$daichou['lamp_su8']}
              ,lamp_syu9 = {$daichou['lamp_syu9']}
              ,lamp_su9 = {$daichou['lamp_su9']}
              ,lamp_syu10 = {$daichou['lamp_syu10']}
              ,lamp_su10 = {$daichou['lamp_su10']}
              ,lamp_syu11 = {$daichou['lamp_syu11']}
              ,lamp_su11 = {$daichou['lamp_su11']}
              ,lamp_syu12 = {$daichou['lamp_syu12']}
              ,lamp_su12 = {$daichou['lamp_su12']}
SQL;
    //log_message('debug', "sql=$sql");
    $this->DB_rfs->query($sql);
  }

  protected function setItem(&$daichou) {
      $daichou['toukyuu_cd']=$this->chkItem($daichou, 'toukyuu_cd',1);
      $daichou['shisetsu_renzoku_cd']=$this->chkItem($daichou, 'shisetsu_renzoku_cd',1);
      $daichou['kussaku']=$this->chkItem($daichou, 'kussaku',2);
      $daichou['hekimen_kbn_cd']=$this->chkItem($daichou, 'hekimen_kbn_cd',1);
      $daichou['romen_syu']=$this->chkItem($daichou, 'romen_syu',1);
      $daichou['tenkenkou']=$this->chkItem($daichou, 'tenkenkou',1);
      $daichou['kanshi_camera']=$this->chkItem($daichou, 'kanshi_camera',1);
      $daichou['kanki_shisetsu_cd']=$this->chkItem($daichou, 'kanki_shisetsu_cd',1);
      $daichou['bousuiban']=$this->chkItem($daichou, 'bousuiban',1);
      $daichou['secchi_kasyo_juudan_cd']=$this->chkItem($daichou, 'secchi_kasyo_juudan_cd',1);
      $daichou['secchi_kasyo_oudan_cd']=$this->chkItem($daichou, 'secchi_kasyo_oudan_cd',1);
      $daichou['syoumei_shisetsu_cd']=$this->chkItem($daichou, 'syoumei_shisetsu_cd',1);
      $daichou['syoumei_kisuu']=$this->chkItem($daichou, 'syoumei_kisuu',1);
      $daichou['push_button_num']=$this->chkItem($daichou, 'push_button_num',1);
      $daichou['tuuhou_souchi_cd']=$this->chkItem($daichou, 'tuuhou_souchi_cd',1);
      $daichou['hijou_tel_num']=$this->chkItem($daichou, 'hijou_tel_num',1);
      $daichou['hijou_keihou_souchi_cd']=$this->chkItem($daichou, 'hijou_keihou_souchi_cd',1);
      $daichou['keihou_hyoujiban_num']=$this->chkItem($daichou, 'keihou_hyoujiban_num',1);
      $daichou['tenmetsutou_num']=$this->chkItem($daichou, 'tenmetsutou_num',1);
      $daichou['syouka_setsubi_cd']=$this->chkItem($daichou, 'syouka_setsubi_cd',1);
      $daichou['syoukaki_num']=$this->chkItem($daichou, 'syoukaki_num',1);
      $daichou['sonota_setsubi_cd']=$this->chkItem($daichou, 'sonota_setsubi_cd',1);
      $daichou['yuudou_hyoujiban_num']=$this->chkItem($daichou, 'yuudou_hyoujiban_num',1);
      $daichou['syoukasen']=$this->chkItem($daichou, 'syoukasen',1);
      $daichou['kasai_kenchiki']=$this->chkItem($daichou, 'kasai_kenchiki',1);
      $daichou['musen_tsuushin_setsubi']=$this->chkItem($daichou, 'musen_tsuushin_setsubi',1);
      $daichou['radio_re_housou_setsubi']=$this->chkItem($daichou, 'radio_re_housou_setsubi',1);
      $daichou['warikomi_housou']=$this->chkItem($daichou, 'warikomi_housou',1);
      $daichou['radio_re_musenkyoka_num']=$this->chkItem($daichou, 'radio_re_musenkyoka_num',2);
      $daichou['humei']=$this->chkItem($daichou, 'humei',1);
      if ($daichou['humei']==1) {
        $daichou['musen_kyoka_dt']="null";
      }else{
        $daichou['musen_kyoka_dt']=$this->chkItem($daichou, 'musen_kyoka_dt',2);
      }
      $daichou['jieisen_denki']=$this->chkItem($daichou, 'jieisen_denki',1);
      $daichou['jieisen_tsuushin']=$this->chkItem($daichou, 'jieisen_tsuushin',1);
      $daichou['mabiki_umu']=$this->chkItem($daichou, 'mabiki_umu', 1);
      $daichou['taiou_dt']=$this->chkItem($daichou, 'taiou_dt', 2);
      $daichou['lamp_syu1']=$this->chkItem($daichou, 'lamp_syu1', 2);
      $daichou['lamp_su1']=$this->chkItem($daichou, 'lamp_su1', 1);
      $daichou['lamp_syu2']=$this->chkItem($daichou, 'lamp_syu2', 2);
      $daichou['lamp_su2']=$this->chkItem($daichou, 'lamp_su2', 1);
      $daichou['lamp_syu3']=$this->chkItem($daichou, 'lamp_syu3', 2);
      $daichou['lamp_su3']=$this->chkItem($daichou, 'lamp_su3', 1);
      $daichou['lamp_syu4']=$this->chkItem($daichou, 'lamp_syu4', 2);
      $daichou['lamp_su4']=$this->chkItem($daichou, 'lamp_su4', 1);
      $daichou['lamp_syu5']=$this->chkItem($daichou, 'lamp_syu5', 2);
      $daichou['lamp_su5']=$this->chkItem($daichou, 'lamp_su5', 1);
      $daichou['lamp_syu6']=$this->chkItem($daichou, 'lamp_syu6', 2);
      $daichou['lamp_su6']=$this->chkItem($daichou, 'lamp_su6', 1);
      $daichou['lamp_syu7']=$this->chkItem($daichou, 'lamp_syu7', 2);
      $daichou['lamp_su7']=$this->chkItem($daichou, 'lamp_su7', 1);
      $daichou['lamp_syu8']=$this->chkItem($daichou, 'lamp_syu8', 2);
      $daichou['lamp_su8']=$this->chkItem($daichou, 'lamp_su8', 1);
      $daichou['lamp_syu9']=$this->chkItem($daichou, 'lamp_syu9', 2);
      $daichou['lamp_su9']=$this->chkItem($daichou, 'lamp_su9', 1);
      $daichou['lamp_syu10']=$this->chkItem($daichou, 'lamp_syu10', 2);
      $daichou['lamp_su10']=$this->chkItem($daichou, 'lamp_su10', 1);
      $daichou['lamp_syu11']=$this->chkItem($daichou, 'lamp_syu11', 2);
      $daichou['lamp_su11']=$this->chkItem($daichou, 'lamp_su11', 1);
      $daichou['lamp_syu12']=$this->chkItem($daichou, 'lamp_syu12', 2);
      $daichou['lamp_su12']=$this->chkItem($daichou, 'lamp_su12', 1);
  }
}
