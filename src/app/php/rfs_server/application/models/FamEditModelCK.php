<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("FamEditModel.php");

// 台帳:駐車公園登録用
class FamEditModelCK extends FamEditModel {

  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
  }

  /***
   *  台帳:駐車公園の更新
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  public function saveDaichou($daichou) {
    log_message('info', "FamEditModelCK->saveDaichou");
    $this->setDaichouCommon($daichou); // 共通
    $this->setItem($daichou); // 駐車公園固有
    $this->setDaichouTsuushin($daichou); // 通信回線
    $this->setDaichouDenki($daichou); // 電気
    $sql= <<<SQL
            insert into rfs_t_daichou_ck (
              sno
              , genkyou_ichi_no
              , yosan_himoku
              , jigyouhi
              , syadou_hosou_kousei
              , syadou_hosou_menseki
              , hodou_hosou_kousei
              , hodou_hosou_menseki
              , norimen_ryokuchi_menseki
              , tyuusyadaisuu_oogata
              , tyuusyadaisuu_hutsuu
              , toire_katashiki_cd
              , toire_suigen
              , kenjousya_dai
              , kenjousya_syou
              , shinsyousya_dai
              , shinsyousya_syou
              , riyou_kanou_kikan
              , syoumeitou_pole_kikaku
              , ramp_syu
              , syoumei_dengen_cd
              , syoumei_hashira_num
              , syoumei_kyuu_num
              , azumaya
              , kousyuu_tel
              , bench
              , tbl
              , clock
              , syokuju_kouboku
              , syokuju_tyuuteiboku
              , annai_hyoushiki
              , kankou_annaiban
              , keikan_kankoushisetsu
              , barrierfree_seibi
              , haishi_riyuu
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
              , {$daichou['genkyou_ichi_no']}
              , {$daichou['yosan_himoku']}
              , {$daichou['jigyouhi']}
              , {$daichou['syadou_hosou_kousei']}
              , {$daichou['syadou_hosou_menseki']}
              , {$daichou['hodou_hosou_kousei']}
              , {$daichou['hodou_hosou_menseki']}
              , {$daichou['norimen_ryokuchi_menseki']}
              , {$daichou['tyuusyadaisuu_oogata']}
              , {$daichou['tyuusyadaisuu_hutsuu']}
              , {$daichou['toire_katashiki_cd']}
              , {$daichou['toire_suigen']}
              , {$daichou['kenjousya_dai']}
              , {$daichou['kenjousya_syou']}
              , {$daichou['shinsyousya_dai']}
              , {$daichou['shinsyousya_syou']}
              , {$daichou['riyou_kanou_kikan']}
              , {$daichou['syoumeitou_pole_kikaku']}
              , {$daichou['ramp_syu']}
              , {$daichou['syoumei_dengen_cd']}
              , {$daichou['syoumei_hashira_num']}
              , {$daichou['syoumei_kyuu_num']}
              , {$daichou['azumaya']}
              , {$daichou['kousyuu_tel']}
              , {$daichou['bench']}
              , {$daichou['tbl']}
              , {$daichou['clock']}
              , {$daichou['syokuju_kouboku']}
              , {$daichou['syokuju_tyuuteiboku']}
              , {$daichou['annai_hyoushiki']}
              , {$daichou['kankou_annaiban']}
              , {$daichou['keikan_kankoushisetsu']}
              , {$daichou['barrierfree_seibi']}
              , {$daichou['haishi_riyuu']}
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
            ON CONFLICT ON CONSTRAINT rfs_t_daichou_ck_pkey
            DO UPDATE SET
              genkyou_ichi_no = {$daichou['genkyou_ichi_no']}
              ,yosan_himoku = {$daichou['yosan_himoku']}
              ,jigyouhi = {$daichou['jigyouhi']}
              ,syadou_hosou_kousei = {$daichou['syadou_hosou_kousei']}
              ,syadou_hosou_menseki = {$daichou['syadou_hosou_menseki']}
              ,hodou_hosou_kousei = {$daichou['hodou_hosou_kousei']}
              ,hodou_hosou_menseki = {$daichou['hodou_hosou_menseki']}
              ,norimen_ryokuchi_menseki = {$daichou['norimen_ryokuchi_menseki']}
              ,tyuusyadaisuu_oogata = {$daichou['tyuusyadaisuu_oogata']}
              ,tyuusyadaisuu_hutsuu = {$daichou['tyuusyadaisuu_hutsuu']}
              ,toire_katashiki_cd = {$daichou['toire_katashiki_cd']}
              ,toire_suigen = {$daichou['toire_suigen']}
              ,kenjousya_dai = {$daichou['kenjousya_dai']}
              ,kenjousya_syou = {$daichou['kenjousya_syou']}
              ,shinsyousya_dai = {$daichou['shinsyousya_dai']}
              ,shinsyousya_syou = {$daichou['shinsyousya_syou']}
              ,riyou_kanou_kikan = {$daichou['riyou_kanou_kikan']}
              ,syoumeitou_pole_kikaku = {$daichou['syoumeitou_pole_kikaku']}
              ,ramp_syu = {$daichou['ramp_syu']}
              ,syoumei_dengen_cd = {$daichou['syoumei_dengen_cd']}
              ,syoumei_hashira_num = {$daichou['syoumei_hashira_num']}
              ,syoumei_kyuu_num = {$daichou['syoumei_kyuu_num']}
              ,azumaya = {$daichou['azumaya']}
              ,kousyuu_tel = {$daichou['kousyuu_tel']}
              ,bench = {$daichou['bench']}
              ,tbl = {$daichou['tbl']}
              ,clock = {$daichou['clock']}
              ,syokuju_kouboku = {$daichou['syokuju_kouboku']}
              ,syokuju_tyuuteiboku = {$daichou['syokuju_tyuuteiboku']}
              ,annai_hyoushiki = {$daichou['annai_hyoushiki']}
              ,kankou_annaiban = {$daichou['kankou_annaiban']}
              ,keikan_kankoushisetsu = {$daichou['keikan_kankoushisetsu']}
              ,barrierfree_seibi = {$daichou['barrierfree_seibi']}
              ,haishi_riyuu = {$daichou['haishi_riyuu']}
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
      $daichou['genkyou_ichi_no']=$this->chkItem($daichou, 'genkyou_ichi_no',2);
      $daichou['yosan_himoku']=$this->chkItem($daichou, 'yosan_himoku',2);
      $daichou['jigyouhi']=$this->chkItem($daichou, 'jigyouhi',1);
      $daichou['syadou_hosou_kousei']=$this->chkItem($daichou, 'syadou_hosou_kousei',2);
      $daichou['syadou_hosou_menseki']=$this->chkItem($daichou, 'syadou_hosou_menseki',1);
      $daichou['hodou_hosou_kousei']=$this->chkItem($daichou, 'hodou_hosou_kousei',2);
      $daichou['hodou_hosou_menseki']=$this->chkItem($daichou, 'hodou_hosou_menseki',1);
      $daichou['norimen_ryokuchi_menseki']=$this->chkItem($daichou, 'norimen_ryokuchi_menseki',1);
      $daichou['tyuusyadaisuu_oogata']=$this->chkItem($daichou, 'tyuusyadaisuu_oogata',1);
      $daichou['tyuusyadaisuu_hutsuu']=$this->chkItem($daichou, 'tyuusyadaisuu_hutsuu',1);
      $daichou['toire_katashiki_cd']=$this->chkItem($daichou, 'toire_katashiki_cd',1);
      $daichou['toire_suigen']=$this->chkItem($daichou, 'toire_suigen',2);
      $daichou['kenjousya_dai']=$this->chkItem($daichou, 'kenjousya_dai',1);
      $daichou['kenjousya_syou']=$this->chkItem($daichou, 'kenjousya_syou',1);
      $daichou['shinsyousya_dai']=$this->chkItem($daichou, 'shinsyousya_dai',1);
      $daichou['shinsyousya_syou']=$this->chkItem($daichou, 'shinsyousya_syou',1);
      $daichou['riyou_kanou_kikan']=$this->chkItem($daichou, 'riyou_kanou_kikan',2);
      $daichou['syoumeitou_pole_kikaku']=$this->chkItem($daichou, 'syoumeitou_pole_kikaku',2);
      $daichou['ramp_syu']=$this->chkItem($daichou, 'ramp_syu',2);
      $daichou['syoumei_dengen_cd']=$this->chkItem($daichou, 'syoumei_dengen_cd',1);
      $daichou['syoumei_hashira_num']=$this->chkItem($daichou, 'syoumei_hashira_num',1);
      $daichou['syoumei_kyuu_num']=$this->chkItem($daichou, 'syoumei_kyuu_num',1);
      $daichou['azumaya']=$this->chkItem($daichou, 'azumaya',1);
      $daichou['kousyuu_tel']=$this->chkItem($daichou, 'kousyuu_tel',1);
      $daichou['bench']=$this->chkItem($daichou, 'bench',1);
      $daichou['tbl']=$this->chkItem($daichou, 'tbl',1);
      $daichou['clock']=$this->chkItem($daichou, 'clock',1);
      $daichou['syokuju_kouboku']=$this->chkItem($daichou, 'syokuju_kouboku',1);
      $daichou['syokuju_tyuuteiboku']=$this->chkItem($daichou, 'syokuju_tyuuteiboku',1);
      $daichou['annai_hyoushiki']=$this->chkItem($daichou, 'annai_hyoushiki',1);
      $daichou['kankou_annaiban']=$this->chkItem($daichou, 'kankou_annaiban',1);
      $daichou['keikan_kankoushisetsu']=$this->chkItem($daichou, 'keikan_kankoushisetsu',2);
      $daichou['barrierfree_seibi']=$this->chkItem($daichou, 'barrierfree_seibi',2);
      $daichou['haishi_riyuu']=$this->chkItem($daichou, 'haishi_riyuu',2);
  }
}
