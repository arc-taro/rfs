<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("FamEditModel.php");

// 台帳:ロードヒーティング登録用
class FamEditModelRH extends FamEditModel {

  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
  }

  /***
   *  台帳:ロードヒーティングの更新
   *
   *  引数
   *    $daichou 入力台帳
   ***/
  public function saveDaichou($daichou) {
    log_message('info', "FamEditModelRh->saveDaichou");
    $this->setDaichouCommon($daichou); // 共通
    $this->setItem($daichou); // ロードヒーティング固有
    $sql= <<<SQL
            insert
            into public.rfs_t_daichou_rh(
              sno
              , gno
              , endou_joukyou_cd
              , did_syubetsu_cd
              , endou_kuiki_cd
              , endou_chiiki_cd
              , bus_rosen
              , tsuugaku_ro
              , yukimichi
              , josetsu_kbn
              , genkyou_num1
              , genkyou_num2
              , haba_syadou
              , haba_hodou
              , koubai_syadou
              , koubai_hodou
              , hankei_r
              , chuusin_enchou_syadou
              , nobe_enchyou_syadou
              , fukuin_syadou
              , menseki_syadou
              , hosou_syubetsu_syadou
              , chuusin_enchou_hodou
              , nobe_enchyou_hodou
              , fukuin_hodou
              , menseki_hodou
              , hosou_syubetsu_hodou
              , koutsuuryou_syadou
              , koutsuuryou_hodou
              , morido
              , kirido
              , kyuu_curve
              , under_syadou
              , under_hodou
              , kyuukoubai_syadou
              , kyuukoubai_hodou
              , fumikiri_syadou
              , fumikiri_houdou
              , kousaten_syadou
              , kousaten_hodou
              , hodoukyou
              , tunnel_syadou
              , tunnel_hodou
              , heimen_syadou
              , heimen_hodou
              , kyouryou_syadou
              , kyouryou_hodou
              , kosen_syadou
              , kosen_hodou
              , etc_syadou
              , etc_hodou
              , etc_comment_syadou
              , etc_comment_hodou
              , netsugen_etc
              , douryoku_etc
              , syuunetsu_cd
              , syuunetsu_etc
              , hounetsu_cd
              , hounetsu_etc
              , denryoku_keiyaku_syubetsu_cd
              , seibi_keii_syadou
              , sentei_riyuu_syadou
              , seibi_keii_hodou
              , sentei_riyuu_hodou
              , haishi_jikou
              , unit_shiyou
              , unit_ichi
              , sencor_shiyou
              , sensor_ichi
              , seigyoban_shiyou
              , seigyoban_ichi
              , haisen_shiyou
              , haisen_ichi
              , hokuden_ichi
              , check1
              , check2
              , old_id
              , comment
              , hs_check
              , comment_douroka
              , comment_dogen
              , dhs
              , dctr
              , bundenban
              , boira
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
            ) values (
              {$daichou['sno']}
              , {$daichou['gno']}
              , {$daichou['endou_joukyou_cd']}
              , {$daichou['did_syubetsu_cd']}
              , {$daichou['endou_kuiki_cd']}
              , {$daichou['endou_chiiki_cd']}
              , {$daichou['bus_rosen']}
              , {$daichou['tsuugaku_ro']}
              , {$daichou['yukimichi']}
              , {$daichou['josetsu_kbn']}
              , {$daichou['genkyou_num1']}
              , {$daichou['genkyou_num2']}
              , {$daichou['haba_syadou']}
              , {$daichou['haba_hodou']}
              , {$daichou['koubai_syadou']}
              , {$daichou['koubai_hodou']}
              , {$daichou['hankei_r']}
              , {$daichou['chuusin_enchou_syadou']}
              , {$daichou['nobe_enchyou_syadou']}
              , {$daichou['fukuin_syadou']}
              , {$daichou['menseki_syadou']}
              , {$daichou['hosou_syubetsu_syadou']}
              , {$daichou['chuusin_enchou_hodou']}
              , {$daichou['nobe_enchyou_hodou']}
              , {$daichou['fukuin_hodou']}
              , {$daichou['menseki_hodou']}
              , {$daichou['hosou_syubetsu_hodou']}
              , {$daichou['koutsuuryou_syadou']}
              , {$daichou['koutsuuryou_hodou']}
              , {$daichou['morido']}
              , {$daichou['kirido']}
              , {$daichou['kyuu_curve']}
              , {$daichou['under_syadou']}
              , {$daichou['under_hodou']}
              , {$daichou['kyuukoubai_syadou']}
              , {$daichou['kyuukoubai_hodou']}
              , {$daichou['fumikiri_syadou']}
              , {$daichou['fumikiri_houdou']}
              , {$daichou['kousaten_syadou']}
              , {$daichou['kousaten_hodou']}
              , {$daichou['hodoukyou']}
              , {$daichou['tunnel_syadou']}
              , {$daichou['tunnel_hodou']}
              , {$daichou['heimen_syadou']}
              , {$daichou['heimen_hodou']}
              , {$daichou['kyouryou_syadou']}
              , {$daichou['kyouryou_hodou']}
              , {$daichou['kosen_syadou']}
              , {$daichou['kosen_hodou']}
              , {$daichou['etc_syadou']}
              , {$daichou['etc_hodou']}
              , {$daichou['etc_comment_syadou']}
              , {$daichou['etc_comment_hodou']}
              , {$daichou['netsugen_etc']}
              , {$daichou['douryoku_etc']}
              , {$daichou['syuunetsu_cd']}
              , {$daichou['syuunetsu_etc']}
              , {$daichou['hounetsu_cd']}
              , {$daichou['hounetsu_etc']}
              , {$daichou['denryoku_keiyaku_syubetsu_cd']}
              , {$daichou['seibi_keii_syadou']}
              , {$daichou['sentei_riyuu_syadou']}
              , {$daichou['seibi_keii_hodou']}
              , {$daichou['sentei_riyuu_hodou']}
              , {$daichou['haishi_jikou']}
              , {$daichou['unit_shiyou']}
              , {$daichou['unit_ichi']}
              , {$daichou['sencor_shiyou']}
              , {$daichou['sensor_ichi']}
              , {$daichou['seigyoban_shiyou']}
              , {$daichou['seigyoban_ichi']}
              , {$daichou['haisen_shiyou']}
              , {$daichou['haisen_ichi']}
              , {$daichou['hokuden_ichi']}
              , {$daichou['check1']}
              , {$daichou['check2']}
              , {$daichou['old_id']}
              , {$daichou['comment']}
              , {$daichou['hs_check']}
              , {$daichou['comment_douroka']}
              , {$daichou['comment_dogen']}
              , {$daichou['dhs']}
              , {$daichou['dctr']}
              , {$daichou['bundenban']}
              , {$daichou['boira']}
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
            ON CONFLICT ON CONSTRAINT rfs_t_daichou_rh_pkc
            DO UPDATE SET
               gno = {$daichou['gno']}
              , endou_joukyou_cd = {$daichou['endou_joukyou_cd']}
              , did_syubetsu_cd = {$daichou['did_syubetsu_cd']}
              , endou_kuiki_cd = {$daichou['endou_kuiki_cd']}
              , endou_chiiki_cd = {$daichou['endou_chiiki_cd']}
              , bus_rosen = {$daichou['bus_rosen']}
              , tsuugaku_ro = {$daichou['tsuugaku_ro']}
              , yukimichi = {$daichou['yukimichi']}
              , josetsu_kbn = {$daichou['josetsu_kbn']}
              , genkyou_num1 = {$daichou['genkyou_num1']}
              , genkyou_num2 = {$daichou['genkyou_num2']}
              , haba_syadou = {$daichou['haba_syadou']}
              , haba_hodou = {$daichou['haba_hodou']}
              , koubai_syadou = {$daichou['koubai_syadou']}
              , koubai_hodou = {$daichou['koubai_hodou']}
              , hankei_r = {$daichou['hankei_r']}
              , chuusin_enchou_syadou = {$daichou['chuusin_enchou_syadou']}
              , nobe_enchyou_syadou = {$daichou['nobe_enchyou_syadou']}
              , fukuin_syadou = {$daichou['fukuin_syadou']}
              , menseki_syadou = {$daichou['menseki_syadou']}
              , hosou_syubetsu_syadou = {$daichou['hosou_syubetsu_syadou']}
              , chuusin_enchou_hodou = {$daichou['chuusin_enchou_hodou']}
              , nobe_enchyou_hodou = {$daichou['nobe_enchyou_hodou']}
              , fukuin_hodou = {$daichou['fukuin_hodou']}
              , menseki_hodou = {$daichou['menseki_hodou']}
              , hosou_syubetsu_hodou = {$daichou['hosou_syubetsu_hodou']}
              , koutsuuryou_syadou = {$daichou['koutsuuryou_syadou']}
              , koutsuuryou_hodou = {$daichou['koutsuuryou_hodou']}
              , morido = {$daichou['morido']}
              , kirido = {$daichou['kirido']}
              , kyuu_curve = {$daichou['kyuu_curve']}
              , under_syadou = {$daichou['under_syadou']}
              , under_hodou = {$daichou['under_hodou']}
              , kyuukoubai_syadou = {$daichou['kyuukoubai_syadou']}
              , kyuukoubai_hodou = {$daichou['kyuukoubai_hodou']}
              , fumikiri_syadou = {$daichou['fumikiri_syadou']}
              , fumikiri_houdou = {$daichou['fumikiri_houdou']}
              , kousaten_syadou = {$daichou['kousaten_syadou']}
              , kousaten_hodou = {$daichou['kousaten_hodou']}
              , hodoukyou = {$daichou['hodoukyou']}
              , tunnel_syadou = {$daichou['tunnel_syadou']}
              , tunnel_hodou = {$daichou['tunnel_hodou']}
              , heimen_syadou = {$daichou['heimen_syadou']}
              , heimen_hodou = {$daichou['heimen_hodou']}
              , kyouryou_syadou = {$daichou['kyouryou_syadou']}
              , kyouryou_hodou = {$daichou['kyouryou_hodou']}
              , kosen_syadou = {$daichou['kosen_syadou']}
              , kosen_hodou = {$daichou['kosen_hodou']}
              , etc_syadou = {$daichou['etc_syadou']}
              , etc_hodou = {$daichou['etc_hodou']}
              , etc_comment_syadou = {$daichou['etc_comment_syadou']}
              , etc_comment_hodou = {$daichou['etc_comment_hodou']}
              , netsugen_etc = {$daichou['netsugen_etc']}
              , douryoku_etc = {$daichou['douryoku_etc']}
              , syuunetsu_cd = {$daichou['syuunetsu_cd']}
              , syuunetsu_etc = {$daichou['syuunetsu_etc']}
              , hounetsu_cd = {$daichou['hounetsu_cd']}
              , hounetsu_etc = {$daichou['hounetsu_etc']}
              , denryoku_keiyaku_syubetsu_cd = {$daichou['denryoku_keiyaku_syubetsu_cd']}
              , seibi_keii_syadou = {$daichou['seibi_keii_syadou']}
              , sentei_riyuu_syadou = {$daichou['sentei_riyuu_syadou']}
              , seibi_keii_hodou = {$daichou['seibi_keii_hodou']}
              , sentei_riyuu_hodou = {$daichou['sentei_riyuu_hodou']}
              , haishi_jikou = {$daichou['haishi_jikou']}
              , unit_shiyou = {$daichou['unit_shiyou']}
              , unit_ichi = {$daichou['unit_ichi']}
              , sencor_shiyou = {$daichou['sencor_shiyou']}
              , sensor_ichi = {$daichou['sensor_ichi']}
              , seigyoban_shiyou = {$daichou['seigyoban_shiyou']}
              , seigyoban_ichi = {$daichou['seigyoban_ichi']}
              , haisen_shiyou = {$daichou['haisen_shiyou']}
              , haisen_ichi = {$daichou['haisen_ichi']}
              , hokuden_ichi = {$daichou['hokuden_ichi']}
              , check1 = {$daichou['check1']}
              , check2 = {$daichou['check2']}
              , old_id = {$daichou['old_id']}
              , comment = {$daichou['comment']}
              , hs_check = {$daichou['hs_check']}
              , comment_douroka = {$daichou['comment_douroka']}
              , comment_dogen = {$daichou['comment_dogen']}
              , dhs = {$daichou['dhs']}
              , dctr = {$daichou['dctr']}
              , bundenban = {$daichou['bundenban']}
              , boira = {$daichou['boira']}
              ,bikou = {$daichou['bikou']}
              ,kyoutsuu1 = {$daichou['kyoutsuu1']}
              ,kyoutsuu2 = {$daichou['kyoutsuu2']}
              ,kyoutsuu3 = {$daichou['kyoutsuu3']}
              ,dokuji1 = {$daichou['dokuji1']}
              ,dokuji2 = {$daichou['dokuji2']}
              ,dokuji3 = {$daichou['dokuji3']}
              ,create_dt = {$daichou['create_dt']}
              , update_dt = NOW()
              , update_account = {$daichou['update_account']}
              --最終更新者追加START
              , update_busyo_cd = {$daichou['update_busyo_cd']}
              , update_account_nm = {$daichou['update_account_nm']}
              --最終更新者追加END
SQL;
//    log_message('debug', "sql=$sql");
    $this->DB_rfs->query($sql);
  }

  protected function setItem(&$daichou) {
    $daichou['gno'] = $this->chkItem($daichou,'gno',2);
    $daichou['endou_joukyou_cd'] = $this->chkItem($daichou,'endou_joukyou_cd',1);
    $daichou['did_syubetsu_cd'] = $this->chkItem($daichou,'did_syubetsu_cd',1);
    $daichou['endou_kuiki_cd'] = $this->chkItem($daichou,'endou_kuiki_cd',1);
    $daichou['endou_chiiki_cd'] = $this->chkItem($daichou,'endou_chiiki_cd',1);
    $daichou['bus_rosen'] = $this->chkItem($daichou,'bus_rosen',2);
    $daichou['tsuugaku_ro'] = $this->chkItem($daichou,'tsuugaku_ro',2);
    $daichou['yukimichi'] = $this->chkItem($daichou,'yukimichi',2);
    $daichou['josetsu_kbn'] = $this->chkItem($daichou,'josetsu_kbn',1);
    $daichou['genkyou_num1'] = $this->chkItem($daichou,'genkyou_num1',2);
    $daichou['genkyou_num2'] = $this->chkItem($daichou,'genkyou_num2',2);
    $daichou['haba_syadou'] = $this->chkItem($daichou,'haba_syadou',2);
    $daichou['haba_hodou'] = $this->chkItem($daichou,'haba_hodou',2);
    $daichou['koubai_syadou'] = $this->chkItem($daichou,'koubai_syadou',2);
    $daichou['koubai_hodou'] = $this->chkItem($daichou,'koubai_hodou',2);
    $daichou['hankei_r'] = $this->chkItem($daichou,'hankei_r',2);
    $daichou['chuusin_enchou_syadou'] = $this->chkItem($daichou,'chuusin_enchou_syadou',2);
    $daichou['nobe_enchyou_syadou'] = $this->chkItem($daichou,'nobe_enchyou_syadou',2);
    $daichou['fukuin_syadou'] = $this->chkItem($daichou,'fukuin_syadou',2);
    $daichou['menseki_syadou'] = $this->chkItem($daichou,'menseki_syadou',2);
    $daichou['hosou_syubetsu_syadou'] = $this->chkItem($daichou,'hosou_syubetsu_syadou',2);
    $daichou['chuusin_enchou_hodou'] = $this->chkItem($daichou,'chuusin_enchou_hodou',2);
    $daichou['nobe_enchyou_hodou'] = $this->chkItem($daichou,'nobe_enchyou_hodou',2);
    $daichou['fukuin_hodou'] = $this->chkItem($daichou,'fukuin_hodou',2);
    $daichou['menseki_hodou'] = $this->chkItem($daichou,'menseki_hodou',2);
    $daichou['hosou_syubetsu_hodou'] = $this->chkItem($daichou,'hosou_syubetsu_hodou',2);
    $daichou['koutsuuryou_syadou'] = $this->chkItem($daichou,'koutsuuryou_syadou',2);
    $daichou['koutsuuryou_hodou'] = $this->chkItem($daichou,'koutsuuryou_hodou',2);
    $daichou['morido'] = $this->chkItem($daichou,'morido',3);
    $daichou['kirido'] = $this->chkItem($daichou,'kirido',3);
    $daichou['kyuu_curve'] = $this->chkItem($daichou,'kyuu_curve',3);
    $daichou['under_syadou'] = $this->chkItem($daichou,'under_syadou',3);
    $daichou['under_hodou'] = $this->chkItem($daichou,'under_hodou',3);
    $daichou['kyuukoubai_syadou'] = $this->chkItem($daichou,'kyuukoubai_syadou',3);
    $daichou['kyuukoubai_hodou'] = $this->chkItem($daichou,'kyuukoubai_hodou',3);
    $daichou['fumikiri_syadou'] = $this->chkItem($daichou,'fumikiri_syadou',3);
    $daichou['fumikiri_houdou'] = $this->chkItem($daichou,'fumikiri_houdou',3);
    $daichou['kousaten_syadou'] = $this->chkItem($daichou,'kousaten_syadou',3);
    $daichou['kousaten_hodou'] = $this->chkItem($daichou,'kousaten_hodou',3);
    $daichou['hodoukyou'] = $this->chkItem($daichou,'hodoukyou',3);
    $daichou['tunnel_syadou'] = $this->chkItem($daichou,'tunnel_syadou',3);
    $daichou['tunnel_hodou'] = $this->chkItem($daichou,'tunnel_hodou',3);
    $daichou['heimen_syadou'] = $this->chkItem($daichou,'heimen_syadou',2);
    $daichou['heimen_hodou'] = $this->chkItem($daichou,'heimen_hodou',2);
    $daichou['kyouryou_syadou'] = $this->chkItem($daichou,'kyouryou_syadou',3);
    $daichou['kyouryou_hodou'] = $this->chkItem($daichou,'kyouryou_hodou',3);
    $daichou['kosen_syadou'] = $this->chkItem($daichou,'kosen_syadou',3);
    $daichou['kosen_hodou'] = $this->chkItem($daichou,'kosen_hodou',3);
    $daichou['etc_syadou'] = $this->chkItem($daichou,'etc_syadou',3);
    $daichou['etc_hodou'] = $this->chkItem($daichou,'etc_hodou',3);
    $daichou['etc_comment_syadou'] = $this->chkItem($daichou,'etc_comment_syadou',2);
    $daichou['etc_comment_hodou'] = $this->chkItem($daichou,'etc_comment_hodou',2);
    $daichou['netsugen_etc'] = $this->chkItem($daichou,'netsugen_etc',2);
    $daichou['douryoku_etc'] = $this->chkItem($daichou,'douryoku_etc',2);
    $daichou['syuunetsu_cd'] = $this->chkItem($daichou,'syuunetsu_cd',1);
    $daichou['syuunetsu_etc'] = $this->chkItem($daichou,'syuunetsu_etc',2);
    $daichou['hounetsu_cd'] = $this->chkItem($daichou,'hounetsu_cd',1);
    $daichou['hounetsu_etc'] = $this->chkItem($daichou,'hounetsu_etc',2);
    $daichou['denryoku_keiyaku_syubetsu_cd'] = $this->chkItem($daichou,'denryoku_keiyaku_syubetsu_cd',1);
    $daichou['seibi_keii_syadou'] = $this->chkItem($daichou,'seibi_keii_syadou',2);
    $daichou['sentei_riyuu_syadou'] = $this->chkItem($daichou,'sentei_riyuu_syadou',2);
    $daichou['seibi_keii_hodou'] = $this->chkItem($daichou,'seibi_keii_hodou',2);
    $daichou['sentei_riyuu_hodou'] = $this->chkItem($daichou,'sentei_riyuu_hodou',2);
    $daichou['haishi_jikou'] = $this->chkItem($daichou,'haishi_jikou',2);
    $daichou['unit_shiyou'] = $this->chkItem($daichou,'unit_shiyou',2);
    $daichou['unit_ichi'] = $this->chkItem($daichou,'unit_ichi',2);
    $daichou['sencor_shiyou'] = $this->chkItem($daichou,'sencor_shiyou',2);
    $daichou['sensor_ichi'] = $this->chkItem($daichou,'sensor_ichi',2);
    $daichou['seigyoban_shiyou'] = $this->chkItem($daichou,'seigyoban_shiyou',2);
    $daichou['seigyoban_ichi'] = $this->chkItem($daichou,'seigyoban_ichi',2);
    $daichou['haisen_shiyou'] = $this->chkItem($daichou,'haisen_shiyou',2);
    $daichou['haisen_ichi'] = $this->chkItem($daichou,'haisen_ichi',2);
    $daichou['hokuden_ichi'] = $this->chkItem($daichou,'hokuden_ichi',2);
    $daichou['check1'] = $this->chkItem($daichou,'check1',2);
    $daichou['check2'] = $this->chkItem($daichou,'check2',2);
    $daichou['old_id'] = $this->chkItem($daichou,'old_id',2);
    $daichou['comment'] = $this->chkItem($daichou,'comment',2);
    $daichou['hs_check'] = $this->chkItem($daichou,'hs_check',2);
    $daichou['comment_douroka'] = $this->chkItem($daichou,'comment_douroka',2);
    $daichou['comment_dogen'] = $this->chkItem($daichou,'comment_dogen',2);
    $daichou['dhs'] = $this->chkItem($daichou,'dhs',2);
    $daichou['dctr'] = $this->chkItem($daichou,'dctr',2);
    $daichou['bundenban'] = $this->chkItem($daichou,'bundenban',2);
    $daichou['boira'] = $this->chkItem($daichou,'boira',1);
    $daichou['bikou'] = $this->chkItem($daichou,'bikou',1);
    $daichou['kyoutsuu1'] = $this->chkItem($daichou,'kyoutsuu1',1);
    $daichou['kyoutsuu2'] = $this->chkItem($daichou,'kyoutsuu2',1);
    $daichou['kyoutsuu3'] = $this->chkItem($daichou,'kyoutsuu3',1);
    $daichou['dokuji1'] = $this->chkItem($daichou,'dokuji1',1);
    $daichou['dokuji2'] = $this->chkItem($daichou,'dokuji2',1);
    $daichou['dokuji3'] = $this->chkItem($daichou,'dokuji3',1);
    $daichou['create_dt'] = $this->chkItem($daichou,'create_dt',1);
    $daichou['create_account'] = $this->chkItem($daichou,'create_account',1);
    $daichou['update_account'] = $this->chkItem($daichou,'update_account',1);


  }
}
