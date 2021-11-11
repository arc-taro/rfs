<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("BaseController.php");

/**
    コントローラー名：FameditAjax
    概要：道路施設台帳画面から呼び出されるコントローラー
**/
class FamEditAjax extends BaseController {

  /**
   * コンストラクタ
   */
  public function __construct() {
    parent::__construct();
  }

  public function initEditMain(){
    log_message('info', "initEditMain");

    $sno=$this->get['sno']; // snoは必ずある

    // 施設種別選定の必要があるためまず基本情報を取得
    $this->load->model('SchCommon');
    $shisetsu = $this->SchCommon->getShisetsuDetail($sno);
    // 戻り値に設定
    $result['shisetsu']=$shisetsu;

    // 施設区分
    $shisetsu_kbn=$shisetsu[0]['shisetsu_kbn'];
    // 形式区分コード
    $keishiki_kubun_cd1="";
    $keishiki_kubun_cd2="";
    $keishiki_kubun_cd1=$shisetsu[0]['keishiki_kubun_cd1'];
    $keishiki_kubun_cd2=$shisetsu[0]['keishiki_kubun_cd2'];

//    $r=print_r($shisetsu, true);
//    log_message("debug", "---shisetsu------------------------->$r\n");
//    $r=print_r($shisetsu_kbn, true);
//    log_message("debug", "---shisetsu_kbn------------------------->$r\n");

    // マスタの取得
    $this->getMst($shisetsu_kbn, $keishiki_kubun_cd1, $keishiki_kubun_cd2, $result);

    // 出張所データも必要(Map用)
    $syucchoujo_cd=$shisetsu[0]['syucchoujo_cd'];
    $syucchoujo = $this->SchCommon->getSyucchoujo($syucchoujo_cd);
    // 戻り値に設定
    $result['syucchoujo']=$syucchoujo;

    // 施設区分マスタ-台帳テーブル名取得
    $shisetsu_kbn_arr = array();
    $shisetsu_kbns=$this->SchCommon->getMstSimple('rfs_m_shisetsu_kbn');
    $tbl_nm="";
    for ($i=0;$i<count($shisetsu_kbns);$i++) {
      $item = $shisetsu_kbns[$i];
      if ($item['shisetsu_kbn']==$shisetsu_kbn) {
        // 台帳テーブル名
        $tbl_nm=$item['daityou_tbl'];
        break;
      }
    }
    // 台帳取得
    $this->load->model('FamEditModel');
    $daityou = $this->FamEditModel->getDaityou($sno, $tbl_nm);

    // UPD 20200108 hirano 数値じゃまずい項目、全ての施設に影響するため、この項目のみ対応する
    if ($daityou) {
      if (isset($daityou[0]['d_hokuden_kyakuban']) && $daityou[0]['d_hokuden_kyakuban']) {
        $daityou[0]['d_hokuden_kyakuban'] = $daityou[0]['d_hokuden_kyakuban']." ";
      } else {
        $daityou[0]['d_hokuden_kyakuban'] = " ";
      }
    }

    // 戻り値に設定
    $result['daichou']=$daityou;

    // 追加 update_busyo__cdに対する名称がimmからなのでJOINできない。
    // １件なのでここで改めて取得
    $result['busyo_row']='';
    if ($daityou) {
      if ($daityou[0]['update_busyo_cd']) {
        //log_message("debug","update_busyo_cdあったよ");
        $busyo_row=$this->SchCommon->getBusyoRow($daityou[0]['update_busyo_cd']);
        $result['busyo_row']=$busyo_row[0];
      }
    }

    // 補修履歴の取得
    $hosyuu_rireki = $this->FamEditModel->getHosyuuRireki($sno);
    // 戻り値に設定
    $result['hosyuu_rireki']=$hosyuu_rireki;

    // 案内標識DBデータの取得
    $gdh_db_data = $this->FamEditModel->getGdhDBData($sno);
    $result['gdh_db_data']=$gdh_db_data;

    // 設置年度の取得
    $yyyy=date("Y");
    $wareki_list = $this->SchCommon->getWarekiList(1965,$yyyy,"desc");
    $result['wareki_list']=$wareki_list;

    // 浸出装置の場合ランニングコストと修理費を取得
    if ($shisetsu_kbn==20) {
      // ランニングコストの取得
      $running_cost = $this->FamEditModel->getRunningCost($sno);
      // 計算が必要な項目をセット
      for ($i=0;$i<count($running_cost);$i++) {
        // 薬剤単価(散布費/散布量)
        if ($running_cost[$i]['sanpu_ryou'] && $running_cost[$i]['sanpu_cost']) {
          $running_cost[$i]['yakuzai_tanka']=round($running_cost[$i]['sanpu_cost']/$running_cost[$i]['sanpu_ryou']*100)/100;
        }
        // ランニングコスト(散布費+電気代)
        if ($running_cost[$i]['sanpu_cost'] || $running_cost[$i]['denki_cost']) {
          $s_cost=$running_cost[$i]['sanpu_cost']?(float)$running_cost[$i]['sanpu_cost']:0;
          $d_cost=$running_cost[$i]['denki_cost']?(float)$running_cost[$i]['denki_cost']:0;
          $running_cost[$i]['calc_ranning_cost']=$s_cost+$d_cost;
        }
        // ㎡当たり面接コスト(ランニングコスト/効果範囲面積)
        if ($running_cost[$i]['calc_ranning_cost'] && $running_cost[$i]['kouka_hani_menseki']) {
          $running_cost[$i]['area_per_cost']=round($running_cost[$i]['calc_ranning_cost']/$running_cost[$i]['kouka_hani_menseki']*100)/100;
        }
      }

      // 戻り値に設定
      $result['running_cost_arr']=$running_cost;
      // 修理費の取得
      $repair_cost = $this->FamEditModel->getRepairCost($sno);
      // 戻り値に設定
      $result['repair_cost_arr']=$repair_cost;
    }

    // 施設区分と各種点検の組み合わせ一覧を取得
    $result['patrol_types'] = $this->getPatrolTypeLists();

    /*************************/
    /*** 電気通信施設点検取得 ***/
    /*************************/
    if ($shisetsu_kbn==2 || $shisetsu_kbn==6 || $shisetsu_kbn==7 || $shisetsu_kbn==8 || $shisetsu_kbn==9 || $shisetsu_kbn==10 || $shisetsu_kbn==14 || $shisetsu_kbn==21) {
      $chk_denki = $this->FamEditModel->getChkDenki($sno);
      // 戻り値に設定
      $result['chk_denki']=$chk_denki;
      $result["ele_url"]=$this->config->config['ele_url'];
    }

    /********************/
    /*** 附属物点検取得 ***/
    /********************/
    if (in_array($shisetsu_kbn, $result['patrol_types']['huzokubutsu'])) {
      $huzokubutsu = $this->FamEditModel->getHuzokubutsu($sno, $shisetsu_kbn);
      // 戻り値に設定
      $result['huzokubutsu']=$huzokubutsu;
    }

    /********************/
    /*** 定期パトロール取得 ***/
    /********************/
    $result['teiki_patrol'] = $this->FamEditModel->getTeikiPatrol($sno);
    // 定期パトロールのURLも渡す
    $result['tpat_url'] = $this->config->config['tpat_url'];

    /********************/
    /*** 法定点検取得 ***/
    /********************/
    $result['houtei'] = $this->FamEditModel->getHouteiTenken($sno);

//    $r=print_r($result, true);
//    log_message("debug", "---result------------------------->$r\n");

    // 検索画面での設定が必要なので返却する
    $this->json = json_encode($result,JSON_NUMERIC_CHECK);
    $this->output->set_content_type('application/json')->set_output($this->json);
  }

  /***
   *  マスタの取得
   *    各施設区分のマスタを取得する
   *    ※全施設について、基本情報で使用するkeishiki_kubun_cd1、
   *    keishiki_kubun_cd2の対応名称も取得する
   *
   *    引数:$shisetsu_kbn 施設区分
   *        $arr 返却配列
   */
  protected function getMst($shisetsu_kbn, $keishiki_kubun_cd1, $keishiki_kubun_cd2, &$arr) {

    // 形式区分=施設によって変わるためフラグで管理
    // 1:cd1、2:cd1&cd2
    $keishiki_kubun_num = 0;

    // シンプルマスタの取得はこの設定を元に行う
    // $tbl:取得テーブル、$val:戻り値設定添字
    $tbl[1]=['rfs_m_kousa_tanro','rfs_m_hyoushiki_syu','rfs_m_shichuu_houshiki','rfs_m_shichuu_kikaku'];
    $val[1]=['kousa_tanro','hyoushiki_syu','shichuu_houshiki','shichuu_kikaku'];
    $tbl[2]=['rfs_m_kousa_tanro','rfs_m_keishiki','rfs_m_kiki_syu','rfs_m_koukyou_tandoku','rfs_m_hyouji_shiyou'];
    $val[2]=['kousa_tanro','keishiki','kiki_syu','koukyou_tandoku','hyouji_shiyou'];
    $tbl[3]=['rfs_m_pole_kikaku','rfs_m_tyoukou_umu','rfs_m_timer_umu'];
    $val[3]=['pole_kikaku','tyoukou_umu','timer_umu'];
    $tbl[4]=['rfs_m_sakusyu','rfs_m_saku_kbn','rfs_m_saku_keishiki','rfs_m_kiso_keishiki'];
    $val[4]=['sakusyu','saku_kbn','saku_keishiki','kiso_keishiki'];
    $tbl[5]=['rfs_m_hakkou'];
    $val[5]=['hakkou'];
    $tbl[6]=[];
    $val[6]=[];
    $tbl[7]=[];
    $val[7]=[];
    $tbl[8]=[];
    $val[8]=[];
    $tbl[9]=[];
    $val[9]=[];
    $tbl[10]=[];
    $val[10]=[];
    $tbl[11]=[];
    $val[11]=[];
    $tbl[12]=[];
    $val[12]=[];
    $tbl[13]=[];
    $val[13]=[];
    $tbl[14]=['rfs_m_toukyuu','rfs_m_shisetsu_renzoku','rfs_m_hekimen_kbn','rfs_m_kanki_shisetsu','rfs_m_syoumei_shisetsu','rfs_m_tuuhou_souchi','rfs_m_hijou_keihou_souchi','rfs_m_syouka_setsubi','rfs_m_sonota_setsubi'];
    $val[14]=['toukyuu','shisetsu_renzoku','hekimen_kbn','kanki_shisetsu','syoumei_shisetsu','tuuhou_souchi','hijou_keihou_souchi','syouka_setsubi','sonota_setsubi'];
    $tbl[15]=['rfs_m_toire_katashiki','rfs_m_syoumei_dengen'];
    $val[15]=['toire_katashiki','syoumei_dengen'];
    $tbl[16]=['rfs_m_kbn_c', 'rfs_m_ichi_c'];
    $val[16]=['kbn_c','ichi_c'];
    $tbl[17]=[];
    $val[17]=[];
    $tbl[18]=[];
    $val[18]=[];
    $tbl[19]=[];
    $val[19]=[];
    $tbl[20]=['rfs_m_endou_syu','rfs_m_jigyou_nm'];
    $val[20]=['endou_syu','jigyou_nm'];
    $tbl[21]=['rfs_m_rh_umu',
              'rfs_m_rh_endou_joukyou',
              'rfs_m_rh_endou_chiiki',
              'rfs_m_rh_endou_kuiki',
              'rfs_m_rh_did_syubetsu',
              'rfs_m_rh_syuunetsu',
              'rfs_m_rh_hounetsu',
              'rfs_m_rh_denryoku_keiyaku_syubetsu'];
    $val[21]=['mst_umu',
              'mst_endou_joukyou',
              'mst_endou_chiiki',
              'mst_endou_kuiki',
              'mst_did_syubetsu',
              'mst_syuunetsu',
              'mst_hounetsu',
              'mst_denryoku_keiyaku_syubetsu'];
    $tbl[24]=[];
    $val[24]=[];
    $tbl[25]=[];
    $val[25]=[];
    $tbl[26]=[];
    $val[26]=[];
    $tbl[27]=[];
    $val[27]=[];
    $tbl[28]=[];
    $val[28]=[];
    $tbl[29]=[];
    $val[29]=[];
    $tbl[30]=[];
    $val[30]=[];
    $tbl[31]=[];
    $val[31]=[];
    $tbl[32]=[];
    $val[32]=[];
    $tbl[33]=[];
    $val[33]=[];
    $tbl[34]=[];
    $val[34]=[];
    $tbl[35]=[];
    $val[35]=[];
    $tbl[36]=[];
    $val[36]=[];

    // シンプルマスタはこちら
    for($i = 0 ; $i < count($tbl[$shisetsu_kbn]);$i++){
      $arr[$val[$shisetsu_kbn][$i]] = $this->SchCommon->getMstSimple($tbl[$shisetsu_kbn][$i]);
    }

    // シンプルマスタ以外
    if ($shisetsu_kbn == 1) { /*** 道路標識 ***/
      // 形式区分なし
    } else if ($shisetsu_kbn == 2) { /*** 情報板電光 ***/
      // 形式区分なし
    } else if ($shisetsu_kbn == 3) { /*** 照明 ***/
      // 形式区分なし
    } else if ($shisetsu_kbn == 4) { /*** 防雪柵 ***/
      // 形式区分なし
    } else if ($shisetsu_kbn == 5) { /*** スノーポール ***/
      // 形式区分なし
    } else if ($shisetsu_kbn == 6) { /*** 監視局 ***/
      $unei_kbn = $this->SchCommon->getUneiKbn($shisetsu_kbn); // 運営区分
      $arr['unei_kbn']=$unei_kbn;
      // 形式区分なし
    } else if ($shisetsu_kbn == 7) { /*** 受信局 ***/
      $unei_kbn = $this->SchCommon->getUneiKbn($shisetsu_kbn); // 運営区分
      $arr['unei_kbn']=$unei_kbn;
      // 形式区分なし
    } else if ($shisetsu_kbn == 8) { /*** 中継局 ***/
      $unei_kbn = $this->SchCommon->getUneiKbn($shisetsu_kbn); // 運営区分
      $arr['unei_kbn']=$unei_kbn;
      // 形式区分なし
    } else if ($shisetsu_kbn == 9) { /*** 観測局 ***/
      $unei_kbn = $this->SchCommon->getUneiKbn($shisetsu_kbn); // 運営区分
      $arr['unei_kbn']=$unei_kbn;
      // 形式区分なし
    } else if ($shisetsu_kbn == 10) { /*** カメラ ***/
      $unei_kbn = $this->SchCommon->getUneiKbn($shisetsu_kbn); // 運営区分
      $arr['unei_kbn']=$unei_kbn;
      $keishiki_kubun_num=1; // 形式区分1つ
      // 形式区分なし
    } else if ($shisetsu_kbn == 11) { /*** 情報板C型 ***/
      $keishiki_kubun_num=1; // 形式区分1つ
    } else if ($shisetsu_kbn == 12) { /*** 遮断機 ***/
      // 形式区分なし
    } else if ($shisetsu_kbn == 13) { /*** ドット線 ***/
      // 形式区分なし
    } else if ($shisetsu_kbn == 14) { /*** トンネル（道路トンネル非常用装置/その他） ***/
      // 設置個所縦断
      $secchi_kasyo_j = $this->SchCommon->getSecchiKasyo(1);
      $arr['secchi_kasyo_j']=$secchi_kasyo_j;
      // 設置個所横断
      $secchi_kasyo_o = $this->SchCommon->getSecchiKasyo(2);
      $arr['secchi_kasyo_o']=$secchi_kasyo_o;
      $keishiki_kubun_num=1; // 形式区分1つ
    } else if ($shisetsu_kbn == 15) { /*** 駐車公園 ***/
      $keishiki_kubun_num=1; // 形式区分1つ
    } else if ($shisetsu_kbn == 16) { /*** 緑化樹木 ***/
      // 高木
      $tree_b = $this->SchCommon->getTree(1);
      $arr['tree_b']=$tree_b;
      // 中低木
      $tree_s = $this->SchCommon->getTree(2);
      $arr['tree_s']=$tree_s;
    } else if ($shisetsu_kbn == 17) { /*** 立体横断 ***/
      $keishiki_kubun_num=1; // 形式区分1つ
    } else if ($shisetsu_kbn == 18) { /*** 壁面 ***/
      $keishiki_kubun_num=2; // 形式区分2つ
    } else if ($shisetsu_kbn == 19) { /*** 法面 ***/
      $keishiki_kubun_num=2; // 形式区分2つ
    } else if ($shisetsu_kbn == 20) { /*** 浸出装置 ***/
      $keishiki_kubun_num=2; // 形式区分2つ
    } else if ($shisetsu_kbn == 21) { /*** ロードヒーティング ***/
      $keishiki_kubun_num=2; // 形式区分2つ
    } else if ($shisetsu_kbn == 24) { /*** 橋梁 ***/
      // TODO: 詳細不明
    } else if ($shisetsu_kbn == 25) { /*** トンネル ***/
      // TODO: 詳細不明
    } else if ($shisetsu_kbn == 26) { /*** 切土 ***/
      // TODO: 詳細不明
    } else if ($shisetsu_kbn == 27) { /*** 歩道 ***/
      // TODO: 詳細不明
    } else if ($shisetsu_kbn == 28) { /*** 落石崩壊 ***/
      // TODO: 詳細不明
    } else if ($shisetsu_kbn == 29) { /*** 横断歩道橋 ***/
      // TODO: 詳細不明
    } else if ($shisetsu_kbn == 30) { /*** シェッド等 ***/
      // TODO: 詳細不明
    } else if ($shisetsu_kbn == 31) { /*** 大型カルバート ***/
      // TODO: 詳細不明
    } else if ($shisetsu_kbn == 32) { /*** 岩盤崩壊 ***/
      // TODO: 詳細不明
    } else if ($shisetsu_kbn == 33) { /*** 急流河川 ***/
      // TODO: 詳細不明
    } else if ($shisetsu_kbn == 34) { /*** 盛土 ***/
      // TODO: 詳細不明
    } else if ($shisetsu_kbn == 35) { /*** 道路標識（門型） ***/
      // TODO: 詳細不明
    } else if ($shisetsu_kbn == 36) { /*** 道路情報提供装置（門型） ***/
      // TODO: 詳細不明
    }

    // 形式区分の設定
    if ($keishiki_kubun_num==1) {
      // 1つ
      if(!$keishiki_kubun_cd1) {
        $tmp=array();
        array_push($tmp, array('syubetsu_title'=>'-','keishiki_kubun'=>'-'));
        $arr['keishiki_kubun1']=$tmp;
      }else{
        $keishiki_kubun = $this->SchCommon->getKeishikiKubunsRec($shisetsu_kbn,1,$keishiki_kubun_cd1);
        $arr['keishiki_kubun1']=$keishiki_kubun;
      }
      $tmp=array();
      array_push($tmp, array('syubetsu_title'=>'-','keishiki_kubun'=>'-'));
      $arr['keishiki_kubun2']=$tmp;
    } else if ($keishiki_kubun_num==2) {
      // 2つ
      if(!$keishiki_kubun_cd1) {
        $tmp=array();
        array_push($tmp, array('syubetsu_title'=>'-','keishiki_kubun'=>'-'));
        $arr['keishiki_kubun1']=$tmp;
      }else{
        $keishiki_kubun = $this->SchCommon->getKeishikiKubunsRec($shisetsu_kbn,1,$keishiki_kubun_cd1);
        $arr['keishiki_kubun1']=$keishiki_kubun;
      }
      if(!$keishiki_kubun_cd2) {
        $arr['keishiki_kubun2']=array('syubetsu_title'=>'-','keishiki_kubun'=>'-');
      }else{
        $keishiki_kubun = $this->SchCommon->getKeishikiKubunsRec($shisetsu_kbn,2,$keishiki_kubun_cd2);
        $arr['keishiki_kubun2']=$keishiki_kubun;
      }
    } else {
      $tmp=array();
      array_push($tmp, array('syubetsu_title'=>'-','keishiki_kubun'=>'-'));
      // そもそもなし
      $arr['keishiki_kubun1']=$tmp;
      $arr['keishiki_kubun2']=$tmp;
    }

  }

  /***
   * 施設台帳保存
   *
   * 台帳、補修履歴を保存する
   *
   ***/
  public function saveShisetsuDaichou() {
    log_message('info', "saveShisetsuDaichou");

    $daichou = $this->post['daichou'];

    // 施設区分によって保存内容が異なる
    if ($daichou['shisetsu_kbn']==1) { // 道路標識
      $this->load->model("FamEditModelDH");
      $model = $this->FamEditModelDH;
    } else if ($daichou['shisetsu_kbn']==2) { // 情報板電光
      $this->load->model("FamEditModelJD");
      $model = $this->FamEditModelJD;
    } else if ($daichou['shisetsu_kbn']==3) { // 照明
      $this->load->model("FamEditModelSS");
      $model = $this->FamEditModelSS;
    } else if ($daichou['shisetsu_kbn']==4) { // 防雪柵
      $this->load->model("FamEditModelBS");
      $model = $this->FamEditModelBS;
    } else if ($daichou['shisetsu_kbn']==5) { // スノーポール
      $this->load->model("FamEditModelYH");
      $model = $this->FamEditModelYH;
    } else if ($daichou['shisetsu_kbn']==6) { // 監視局
      $this->load->model("FamEditModelKA");
      $model = $this->FamEditModelKA;
    } else if ($daichou['shisetsu_kbn']==7) { // 受信局
      $this->load->model("FamEditModelKB");
      $model = $this->FamEditModelKB;
    } else if ($daichou['shisetsu_kbn']==8) { // 中継局
      $this->load->model("FamEditModelKC");
      $model = $this->FamEditModelKC;
    } else if ($daichou['shisetsu_kbn']==9) { // 観測局
      $this->load->model("FamEditModelKD");
      $model = $this->FamEditModelKD;
    } else if ($daichou['shisetsu_kbn']==10) { // カメラ
      $this->load->model("FamEditModelKI");
      $model = $this->FamEditModelKI;
    } else if ($daichou['shisetsu_kbn']==11) { // 情報板C型
      $this->load->model("FamEditModelJH");
      $model = $this->FamEditModelJH;
    } else if ($daichou['shisetsu_kbn']==12) { // 遮断機
      $this->load->model("FamEditModelSD");
      $model = $this->FamEditModelSD;
    } else if ($daichou['shisetsu_kbn']==13) { // ドット線
      $this->load->model("FamEditModelDT");
      $model = $this->FamEditModelDT;
    } else if ($daichou['shisetsu_kbn']==14) { // トンネル
      $this->load->model("FamEditModelTT");
      $model = $this->FamEditModelTT;
    } else if ($daichou['shisetsu_kbn']==15) { // 駐車公園
      $this->load->model("FamEditModelCK");
      $model = $this->FamEditModelCK;
    } else if ($daichou['shisetsu_kbn']==16) { // 緑化公園
      $this->load->model("FamEditModelSK");
      $model = $this->FamEditModelSK;
    } else if ($daichou['shisetsu_kbn']==17) { // 立体横断
      $this->load->model("FamEditModelBH");
      $model = $this->FamEditModelBH;
    } else if ($daichou['shisetsu_kbn']==18) { // 擁壁
      $this->load->model("FamEditModelDY");
      $model = $this->FamEditModelDY;
    } else if ($daichou['shisetsu_kbn']==19) { // 法面
      $this->load->model("FamEditModelDN");
      $model = $this->FamEditModelDN;
    } else if ($daichou['shisetsu_kbn']==20) { // 浸出装置
      $this->load->model("FamEditModelTS");
      $model = $this->FamEditModelTS;
    } else if ($daichou['shisetsu_kbn']==21) { // ロードヒーティング
      $this->load->model("FamEditModelRH");
      $model = $this->FamEditModelRH;
    } else if ($daichou['shisetsu_kbn']==24) { // 橋梁 
      $this->load->model("FamEditModelBR");
      $model = $this->FamEditModelBR;
    } else if ($daichou['shisetsu_kbn']==25) { // トンネル 
      $this->load->model("FamEditModelTN");
      $model = $this->FamEditModelTN;
    } else if ($daichou['shisetsu_kbn']==26) { // 切土 
      $this->load->model("FamEditModelKF");
      $model = $this->FamEditModelKF;
    } else if ($daichou['shisetsu_kbn']==27) { // 歩道 
      $this->load->model("FamEditModelHD");
      $model = $this->FamEditModelHD;
    } else if ($daichou['shisetsu_kbn']==28) { // 落石崩壊 
      $this->load->model("FamEditModelGK");
      $model = $this->FamEditModelGK;
    } else if ($daichou['shisetsu_kbn']==29) { // 横断歩道橋 
      $this->load->model("FamEditModelOH");
      $model = $this->FamEditModelOH;
    } else if ($daichou['shisetsu_kbn']==30) { // シェッド等 
      $this->load->model("FamEditModelSH");
      $model = $this->FamEditModelSH;
    } else if ($daichou['shisetsu_kbn']==31) { // 大型カルバート 
      $this->load->model("FamEditModelOK");
      $model = $this->FamEditModelOK;
    } else if ($daichou['shisetsu_kbn']==32) { // 岩盤崩壊 
      $this->load->model("FamEditModelGH");
      $model = $this->FamEditModelGH;
    } else if ($daichou['shisetsu_kbn']==33) { // 急流河川 
      $this->load->model("FamEditModelKK");
      $model = $this->FamEditModelKK;
    } else if ($daichou['shisetsu_kbn']==34) { // 盛土 
      $this->load->model("FamEditModelMT");
      $model = $this->FamEditModelMT;
    } else if ($daichou['shisetsu_kbn']==35) { // 道路標識（門型） 
      $this->load->model("FamEditModelDM");
      $model = $this->FamEditModelDM;
    } else if ($daichou['shisetsu_kbn']==36) { // 道路情報提供装置（門型） 
      $this->load->model("FamEditModelJM");
      $model = $this->FamEditModelJM;
    }

    $model->saveShisetsuDaichou($this->post);

  }

  /***
   * 台帳Excel作成
   *
   ***/
  public function createDaichouExcel() {
    log_message('info', "createDaichouExcel");

    $daichou = $this->post['daichou'];

    // 施設区分によって保存内容が異なる
    if ($daichou['shisetsu_kbn']==1) { // 道路標識
      $this->load->model("CreateDaichouExcelDH");
      $model_excel = $this->CreateDaichouExcelDH;
    } else if ($daichou['shisetsu_kbn']==2) { // 情報板電光
      $this->load->model("CreateDaichouExcelJD");
      $model_excel = $this->CreateDaichouExcelJD;
    } else if ($daichou['shisetsu_kbn']==3) { // 照明
      $this->load->model("CreateDaichouExcelSS");
      $model_excel = $this->CreateDaichouExcelSS;
    } else if ($daichou['shisetsu_kbn']==4) { // 防雪柵
      $this->load->model("CreateDaichouExcelBS");
      $model_excel = $this->CreateDaichouExcelBS;
    } else if ($daichou['shisetsu_kbn']==5) { // スノーポール
      $this->load->model("CreateDaichouExcelYH");
      $model_excel = $this->CreateDaichouExcelYH;
    } else if ($daichou['shisetsu_kbn']==6) { // 監視局
      $this->load->model("CreateDaichouExcelKA");
      $model_excel = $this->CreateDaichouExcelKA;
    } else if ($daichou['shisetsu_kbn']==7) { // 受信局
      $this->load->model("CreateDaichouExcelKB");
      $model_excel = $this->CreateDaichouExcelKB;
    } else if ($daichou['shisetsu_kbn']==8) { // 中継局
      $this->load->model("CreateDaichouExcelKC");
      $model_excel = $this->CreateDaichouExcelKC;
    } else if ($daichou['shisetsu_kbn']==9) { // 観測局
      $this->load->model("CreateDaichouExcelKD");
      $model_excel = $this->CreateDaichouExcelKD;
    } else if ($daichou['shisetsu_kbn']==10) { // カメラ
      $this->load->model("CreateDaichouExcelKI");
      $model_excel = $this->CreateDaichouExcelKI;
    } else if ($daichou['shisetsu_kbn']==11) { // 情報板C型
      $this->load->model("CreateDaichouExcelJH");
      $model_excel = $this->CreateDaichouExcelJH;
    } else if ($daichou['shisetsu_kbn']==12) { // 遮断機
      $this->load->model("CreateDaichouExcelSD");
      $model_excel = $this->CreateDaichouExcelSD;
    } else if ($daichou['shisetsu_kbn']==13) { // ドット線
      $this->load->model("CreateDaichouExcelDT");
      $model_excel = $this->CreateDaichouExcelDT;
    } else if ($daichou['shisetsu_kbn']==14) { // トンネル
      $this->load->model("CreateDaichouExcelTT");
      $model_excel = $this->CreateDaichouExcelTT;
    } else if ($daichou['shisetsu_kbn']==15) { // 駐車公園
      $this->load->model("CreateDaichouExcelCK");
      $model_excel = $this->CreateDaichouExcelCK;
    } else if ($daichou['shisetsu_kbn']==16) { // 緑化公園
      $this->load->model("CreateDaichouExcelSK");
      $model_excel = $this->CreateDaichouExcelSK;
    } else if ($daichou['shisetsu_kbn']==17) { // 立体横断
      $this->load->model("CreateDaichouExcelBH");
      $model_excel = $this->CreateDaichouExcelBH;
    } else if ($daichou['shisetsu_kbn']==18) { // 擁壁
      $this->load->model("CreateDaichouExcelDY");
      $model_excel = $this->CreateDaichouExcelDY;
    } else if ($daichou['shisetsu_kbn']==19) { // 法面
      $this->load->model("CreateDaichouExcelDN");
      $model_excel = $this->CreateDaichouExcelDN;
    } else if ($daichou['shisetsu_kbn']==20) { // 浸出装置
      $this->load->model("CreateDaichouExcelTS");
      $model_excel = $this->CreateDaichouExcelTS;
      $running_cost = $this->getExcelRunningCost($daichou['sno']);
      $model_excel->setRunningCost($running_cost);
      $repair_cost = $this->getExcelRepairCost($daichou['sno']);
      $model_excel->setRepairCost($repair_cost);
    } else if ($daichou['shisetsu_kbn']==21) { // ロードヒーティング
      $this->load->model("CreateDaichouExcelRH");
      $model_excel = $this->CreateDaichouExcelRH;
    }

    // Excelの作成
    if ($model_excel) { // コーディング途中だとこけるので
      $model_excel->outputDaichouData($daichou['sno']);
    }

  }

  /**
   *
   * Excel用ランニングコストの取得
   *  snoにぶら下がるランニングコストを取得(最新の5件)
   *
   * 引数:sno
   * 戻り値:array
   */
  protected function getExcelRunningCost($sno){
    $this->load->model('FamEditModel');

    // 最新のランニングコストをExcelの列数分取得する
    $running_cost = array_slice($this->FamEditModel->getRunningCost($sno), -5, 5);

    for ($i=0;$i<5;$i++) {
      $running_cost["running_cost_id$i"]=isset($running_cost[$i]['running_cost_id'])? $running_cost[$i]['running_cost_id'] : '';
      $running_cost["nendo$i"]=isset($running_cost[$i]['nendo'])? $running_cost[$i]['nendo'] : '-';
      $running_cost["nendo_yyyy$i"]=isset($running_cost[$i]['nendo_yyyy'])? $running_cost[$i]['nendo_yyyy'] : '';
      $running_cost["yakuzai_nm$i"]=isset($running_cost[$i]['yakuzai_nm'])? $running_cost[$i]['yakuzai_nm'] : '';
      $running_cost["sanpu_ryou$i"]=isset($running_cost[$i]['sanpu_ryou'])? $running_cost[$i]['sanpu_ryou'] : '';
      $running_cost["sanpu_cost$i"]=isset($running_cost[$i]['sanpu_cost'])? $running_cost[$i]['sanpu_cost'] : '';
      $running_cost["denki_cost$i"]=isset($running_cost[$i]['denki_cost'])? $running_cost[$i]['denki_cost'] : '';
      $running_cost["keiyaku_denryoku$i"]=isset($running_cost[$i]['keiyaku_denryoku'])? $running_cost[$i]['keiyaku_denryoku'] : '';
      $running_cost["tekiyou$i"]=isset($running_cost[$i]['tekiyou'])? $running_cost[$i]['tekiyou'] : '';
      $running_cost["denryoku_ryou$i"]=isset($running_cost[$i]['denryoku_ryou'])? $running_cost[$i]['denryoku_ryou'] : '';

      // 薬剤単価(散布費/散布量)
      if (isset($running_cost[$i]['sanpu_ryou']) && isset($running_cost[$i]['sanpu_cost'])) {
        $running_cost["yakuzai_tanka$i"]=round($running_cost[$i]['sanpu_cost']/$running_cost[$i]['sanpu_ryou']*100)/100;
      } else {
        $running_cost["yakuzai_tanka$i"]='';
      }

      // ランニングコスト(散布費+電気代)
      if (isset($running_cost[$i]['sanpu_cost']) || isset($running_cost[$i]['denki_cost'])) {
        $s_cost=$running_cost[$i]['sanpu_cost']?(float)$running_cost[$i]['sanpu_cost']:0;
        $d_cost=$running_cost[$i]['denki_cost']?(float)$running_cost[$i]['denki_cost']:0;
        $running_cost["calc_running_cost$i"]=$s_cost+$d_cost;
      } else {
        $running_cost["calc_running_cost$i"]='';
      }

      // ㎡当たり面接コスト(ランニングコスト/効果範囲面積)
      if (isset($running_cost["calc_running_cost$i"]) && isset($running_cost[$i]['kouka_hani_menseki'])) {
        $running_cost["area_per_cost$i"]=round($running_cost["calc_running_cost$i"]/$running_cost[$i]['kouka_hani_menseki']*100)/100;
      } else {
        $running_cost["area_per_cost$i"]='';
      }
    }
    return $running_cost;
  }

  /**
   *
   * Excel用修理費の取得
   *  snoにぶら下がる修理費を取得(最新の5件)
   *
   * 引数:sno
   * 戻り値:array
   */
  protected function getExcelRepairCost($sno){
    $this->load->model('FamEditModel');
    // 最新の修理費をExcelの列数分取得する
    $repair_cost = array_slice($this->FamEditModel->getRepairCost($sno), -5, 5);
    for ($i=0;$i<5;$i++) {
      $repair_cost["s_repair_nendo$i"]=isset($repair_cost[$i]['nendo'])? $repair_cost[$i]['nendo'] : '-';
      $repair_cost["s_repair_cost$i"]=isset($repair_cost[$i]['repair_cost'])? $repair_cost[$i]['repair_cost'] : '';
      $repair_cost["s_repair_naiyou$i"]=isset($repair_cost[$i]['repair_naiyou'])? $repair_cost[$i]['repair_naiyou'] : '';
    }
    return $repair_cost;
  }

}
