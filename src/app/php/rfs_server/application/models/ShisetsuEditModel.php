<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 施設基本情報登録に関するModel
 *
 * @access public
 * @package Model
 */
class ShisetsuEditModel extends CI_Model {

  protected $DB_rfs;

  /**
   * コンストラクタ
   *
   * model SchCheckを初期化する。
   */
  public function __construct() {
    parent::__construct();
    $this->DB_rfs = $this->load->database('rfs',TRUE);
    if ($this->DB_rfs->conn_id === FALSE) {
      log_message('debug', '道路附帯施設管理システムデータベースに接続されていません');
      return;
    }
  }

  /**
     * 防雪柵の検索
     *   引数のsnoから該当の防雪柵データを取得する。
     *
     * @param  sno
     * @return array 防雪柵データ(子データ)
     */
  public function getBousetsusaku($sno) {
    log_message('debug', 'getBousetsusaku');

    $sql= <<<EOF
SELECT
  *
FROM
  rfs_m_bousetsusaku_shichu
WHERE
  sno = $sno
ORDER BY
  struct_idx
EOF;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    /*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result;
  }

  /**
     *  施設管理番号キー登録
     *
     *  施設基本情報をPOSTの中身で保存する。
     *  保存するのはキー、施設区分と管理者
     *  新規の場合は重複チェックを行い重複が無ければ
     *  追加処理を行う。
     *  更新時は、重複チェックを行わずUPSERTを行う
     *
     *  引数：$post
     *          管理者
     *          入力基本情報
     *
     *  戻り値：0 正常
     *        -1 重複
     */
  public function insShisetsuData($post) {

    log_message('debug', "insShisetsuData");

    $shisetsu_cd=$post['data']['shisetsu_cd'];

    $ret=$this->overlapShisetsuCd($shisetsu_cd);
    if ($ret==-1) {
      // 重複
      return -1;
    }else{
      // 施設有重複なし
      $shisetsu_ver=$ret;
    }

    // 追加に使用するsnoを取得する=>seq使用
    $sno = $this->getNextSno();

    // トランザクション
    $this->DB_rfs->trans_start();
    // 本体
    $param=$this->insShisetsu($post, $shisetsu_ver, $sno);
    // 防雪柵の場合
    if($post['data']['shisetsu_kbn']==4){
      if ($this->insFstBousetsusaku($param, $sno)!=0) {
        $this->DB_rfs->trans_rollback();
      }
    }
    $this->DB_rfs->trans_complete();
    return $param;

  }

  /**
     * 現在のshisetsu_verが最大の施設を返却する
     *
     * 与えられたキーの施設で最大のshisetsu_verのレコードを返却する。
     *
     * @param   $post <br>
     *          使用するのは、shisetsu_cd
     *
     * @return row
     *
     */
  public function getShisetsuVer($shisetsu_cd) {

    log_message('debug', 'getShisetsuVer');

    // 施設コードが無い場合はチェックしない
    if ($shisetsu_cd=="") {
      return -1;
    }


    $sql= <<<EOF
SELECT
*
FROM
rfs_m_shisetsu
WHERE
shisetsu_cd='$shisetsu_cd'
ORDER BY
shisetsu_ver DESC
limit 1
EOF;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    $r = print_r($result, true);
    //        log_message('debug', "sql=$sql");
    log_message('debug', "return:".$r);
    return $result;
  }

  /**
     * 次回の追加にて使用すべきsnoを返却する
     *
     * SEQのNEXTVAL 変数を戻すため
     *
     *
     * @return sno
     *
     */
  public function getNextSno() {

    log_message('debug', 'getNextSno');

    $sql= <<<EOF
        SELECT NEXTVAL('rfs_m_shisetsu_sno_seq') sno;
EOF;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

    //$r = print_r($result, true);
    //        log_message('debug', "sql=$sql");
    //log_message('debug', "return:".$r);

    return $result[0]['sno'];
  }

  /**
     *
     *  施設の登録(施設区分、管理者)
     *
     *  引数のPOSTから施設の基本情報を保存する。
     *
     *  引数:$POST
     *      $shisetsu_ver 登録する施設バージョン
     *      $sno 戻り値として必要なので
     *
     *  戻り値 arr INSERT文設定配列
     */
  protected function insShisetsu($post,$shisetsu_ver, $sno) {

    $data = $post['data'];

    // 建管・出張所は登録時は選択出張所
    $arr['dogen_cd']=$this->session['mngarea']['dogen_cd'];
    $arr['syucchoujo_cd']=$this->session['mngarea']['syucchoujo_cd'];
    $arr['shisetsu_cd']=$data['shisetsu_cd'];
    $arr['shisetsu_ver']=$shisetsu_ver;
    $arr['shisetsu_kbn']=$data['shisetsu_kbn'];
    $arr['sno']=$sno;

    $shisetsu_cd = pg_escape_literal($arr['shisetsu_cd']);

    $sql= <<<SQL
            insert into rfs_m_shisetsu (
              sno
              , shisetsu_cd
              , shisetsu_ver
              , shisetsu_kbn
              , dogen_cd
              , syucchoujo_cd
            )
            values(
              $sno
              , $shisetsu_cd
              , ${arr['shisetsu_ver']}
              , ${arr['shisetsu_kbn']}
              , ${arr['dogen_cd']}
              , ${arr['syucchoujo_cd']}
            );
SQL;

    $arr['status']=0;
    //log_message('debug', "sql=$sql");
    $this->DB_rfs->query($sql);
    return $arr;
  }

  /**
     *
     *  防雪柵の登録（初期登録struct_id=0）
     *
     *  引数のPOSTから施設の防雪柵情報を保存する。
     *
     */
  protected function insFstBousetsusaku($param, $sno){

    $shisetsu_cd = pg_escape_literal($param['shisetsu_cd']);

    $sql= <<<SQL
            insert into rfs_m_bousetsusaku_shichu (
              sno
              , shisetsu_cd
              , shisetsu_ver
              , struct_idx
              , struct_no_s
              , struct_no_e
              , sp
              , kp
            )
            values(
            $sno
              , $shisetsu_cd
              , ${param['shisetsu_ver']}
              , 0
              , 0
              , 0
              , 0
              , 0
            );
SQL;

    //        log_message('debug', "sql=$sql");
    $this->DB_rfs->query($sql);
    return 0;
  }

  /**
   *  施設保存処理
   *
   *  施設(基本情報)をPOSTの中身で保存する。
   *  新規の場合は重複チェックを行い重複が無ければ
   *  追加処理を行う。
   *  更新時は、重複チェックを行わずUPSERTを行う
   *
   *  補足…施設コード変更可に伴い、編集時のチェックを追加した。
   *      DB登録と入力値に変化が見られた場合、コードのチェックを行う。
   *      入力施設コードが既にあった場合登録不可。
   *      廃止に関しては、常に最新のVER.しか触れないので、
   *      特に過去データ(古いバージョン)をチェックする必要はなし。
   *      また、変更に際してはshisetsu_verもセットで変更する必要がある。
   *
   *  引数:$post
   *          入力基本情報
   *      $shisetsu
   *          登録されている施設情報
   *
   *  戻り値:0 正常
   *        -1 重複
   *        -999 その他のエラー
   */
  public function setShisetsu($post,$shisetsu) {
    log_message('debug', "set_shisetsu");

    // 施設コードの変化をチェック(DB登録内容と、入力内容で値に変化がある)
    $chg_shisetsu_cd=false;
    if ($post['data']['shisetsu_cd']!=$shisetsu[0]['shisetsu_cd']) {
      $ret=$this->overlapShisetsuCd($post['data']['shisetsu_cd']);
      // 重複チェック
      if ($ret==-1) {
        // 重複
        return -1;
      }else{
        // 重複無
        $shisetsu_ver=$ret;
      }
      // $postのshisetsu_verの上書き(shisetsu_cdは入力値でOK)
      $post['data']['shisetsu_ver']=$shisetsu_ver;
      $chg_shisetsu_cd=true;
    }

    // トランザクション
    $this->DB_rfs->trans_start();

    // 本体
    $res=$this->rgstShisetsu($post);
    if ($res!=0) {
      return $res;
    }
    // 防雪柵の場合
    if($post['data']['shisetsu_kbn']==4){
      $bs = $post['data']['bousetsusaku'];

      // 点検票がぶら下がっていない場合のみ
      // 防雪柵の削除を行えるようにしたので、
      // DELETE INSERTを行うようにする。
      /*$r = print_r($data, true);
      log_message('debug', "bs:".$r);
*/

      // 防雪柵削除
      $res=$this->delBousetsusaku($post['data']['sno']);
      for ($i=0;$i<count($bs);$i++){
        // 支柱番号FROM、TOと測点のみがセットされているので、
        // 他の項目を設定
        $bs[$i]['sno']=$post['data']['sno'];
        $bs[$i]['shisetsu_cd']=$post['data']['shisetsu_cd'];
        $bs[$i]['shisetsu_ver']=$post['data']['shisetsu_ver'];
        $data=$bs[$i];
/*
        $r = print_r($data, true);
        log_message('debug', "bs:".$r);
*/
        $res=$this->insBousetsusaku($data);
        if ($res!=0) {
          return $res;
        }
      }
    }
    // 施設コードに変更があった場合は、施設にぶら下がっている写真も変更対象
    // ※その他はsnoで紐付いている。
    if ($chg_shisetsu_cd==true) {
      // 全景写真の施設コード、パス、ファイル名を変更
      $res=$this->editZenkei($post['data']['syucchoujo_cd'],$post['data']['sno'],$post['data']['shisetsu_cd'],$post['data']['shisetsu_ver']);
      if ($res!=0) {
        // エラー発生
        return $res;
      }
    }


    $this->DB_rfs->trans_complete();
    return 0;
  }

  /**
   *  施設コードの重複チェック
   *
   *  引数の施設コード最大のVerを検索し、
   *  廃止だった場合重複とする。
   *  廃止ではない場合、登録可能でVERをカウントするため、
   *  戻り値として、
   *  重複:1、施設データなし:0、施設はあるが重複無し:-1
   *  で返却する
   *
   *  引数:$shisetsu_cd
   *          施設コード
   *
   *  戻り値:0 施設データなし:0
   *        -1 重複
   *        shisetsu_ver 施設データあり、重複なし。現在の(shisetsu_ver+1)返却
   */

  protected function overlapShisetsuCd($shisetsu_cd){
    log_message('debug', "overlapShisetsuCd");
    // verが最大の施設を検索
    $res=$this->getShisetsuVer($shisetsu_cd);
    // ver最大施設の廃止年が入っていなければ重複となる
    if ($res) {
      if ($res[0]['haishi']=='' || $res[0]['haishi']==null) {
        // 廃止が無いので重複
        return -1;
      }
      // 廃止になっているので、登録可
      return (int)$res[0]['shisetsu_ver']+1;
    }else{
      // レコードなし
      return 0;
    }
  }

  /**
   *
   *  施設の登録
   *
   *  引数のPOSTから施設の基本情報を保存する。
   *  新規はあり得ない=UPDATEのみ(路線は必須なので最低一つは更新がある)
   *
   */
  protected function rgstShisetsu($post) {
    log_message('debug', "rgstShisetsu");
/*
    $r=print_r($post,true);
    log_message("debug","------------------------------------------------------>".$r."\n");
*/
    $data = $post['data'];

    // 施設コードが変更可になったので保存対象にする。
    // 施設コードが変更になった時はshisetsu_verもセットで変更する必要がある
    $shisetsu_cd=$data['shisetsu_cd'];
    $shisetsu_ver=$data['shisetsu_ver'];

    $data['shisetsu_keishiki_cd']=$this->chkItem($data, 'shisetsu_keishiki_cd',1);
    // 路線は必須 附属物以外必須ではなくなった
    if(isset($data['rrow'])){
      if ($data['rrow']['rosen_cd']) {
        $rosen_cd=$data['rrow']['rosen_cd'];
      }else{
        $rosen_cd="null";
      }
    }else{
      $rosen_cd="null";
    }
    $data['shityouson']=$this->chkItem($data, 'shityouson',2);
    $data['azaban']=$this->chkItem($data, 'azaban',2);
    if (isset($data['lat'])) {
      $lat = $data['lat'] ? $data['lat'] : "null";
    } else {
      $lat = "null";
    }
    if (isset($data['lon'])) {
      $lon = $data['lon'] ? $data['lon'] : "null";
    } else {
      $lon = "null";
    }
    $data['substitute_road']=$this->chkItem($data, 'substitute_road',1);
    $data['emergency_road']=$this->chkItem($data, 'emergency_road',1);
    $data['motorway']=$this->chkItem($data, 'motorway',1);
    $data['senyou']=$this->chkItem($data, 'senyou',2);
    if(isset($data['secchi_row'])){
      $secchi_data=$data['secchi_row'];
      $secchi = pg_escape_literal($secchi_data['gengou']);
      $secchi_yyyy = $secchi_data['year'];
    }else{
      // プルダウンの場合は、値無しの場合は値無しで登録
      $secchi = "null";
      $secchi_yyyy = "null";
    }
    if(isset($data['haishi_row'])){
      $haishi_data=$data['haishi_row'];
      $haishi = pg_escape_literal($haishi_data['gengou']);
      $haishi_yyyy = $haishi_data['year'];
    }else{
      // プルダウンの場合は、値無しの場合は値無しで登録
      $haishi = "null";
      $haishi_yyyy = "null";
    }
    $data['fukuin']=$this->chkItem($data, 'fukuin',2);
    $data['sp']=$this->chkItem($data, 'sp',1);
    $data['sp_to']=$this->chkItem($data, 'sp_to',1);
    $data['lr']=$this->chkItem($data, 'lr',1);
    $data['ud']=$this->chkItem($data, 'ud',1);
    $data['kyouyou_kbn']=$this->chkItem($data, 'kyouyou_kbn',1);
    $data['koutsuuryou_day']=$this->chkItem($data, 'koutsuuryou_day',1);
    $data['koutsuuryou_oogata']=$this->chkItem($data, 'koutsuuryou_oogata',1);
    $data['koutsuuryou_hutuu']=$this->chkItem($data, 'koutsuuryou_hutuu',1);
    $data['koutsuuryou_12']=$this->chkItem($data, 'koutsuuryou_12',1);
    $data['name']=$this->chkItem($data, 'name',2);
    $data['keishiki_kubun_cd1']=$this->chkItem($data, 'keishiki_kubun_cd1',1);
    $data['keishiki_kubun_cd2']=$this->chkItem($data, 'keishiki_kubun_cd2',1);
    $data['encho']=$this->chkItem($data, 'encho',1);
    $data['seiri_no']=$this->chkItem($data, 'seiri_no',2);
    $sno = $data['sno'];
/*
    $r = print_r($data, true);
    log_message('debug', "data:".$r);
*/
    $sql= <<<SQL
            UPDATE
              rfs_m_shisetsu
            SET
              shisetsu_cd = '${shisetsu_cd}'
              , shisetsu_ver = $shisetsu_ver
              , shisetsu_keishiki_cd = ${data['shisetsu_keishiki_cd']}
              , rosen_cd = $rosen_cd
              , shityouson = ${data['shityouson']}
              , azaban= ${data['azaban']}
              , lat= $lat
              , lon= $lon
              , substitute_road = ${data['substitute_road']}
              , emergency_road = ${data['emergency_road']}
              , motorway = ${data['motorway']}
              , senyou = ${data['senyou']}
              , secchi = $secchi
              , secchi_yyyy = $secchi_yyyy
              , haishi = $haishi
              , haishi_yyyy = $haishi_yyyy
              , fukuin = ${data['fukuin']}
              , sp = ${data['sp']}
              , sp_to = ${data['sp_to']}
              , lr = ${data['lr']}
              , ud = ${data['ud']}
              , kyouyou_kbn = ${data['kyouyou_kbn']}
              , koutsuuryou_day = ${data['koutsuuryou_day']}
              , koutsuuryou_oogata = ${data['koutsuuryou_oogata']}
              , koutsuuryou_hutuu = ${data['koutsuuryou_hutuu']}
              , koutsuuryou_12 = ${data['koutsuuryou_12']}
              , name = ${data['name']}
              , keishiki_kubun_cd1 = ${data['keishiki_kubun_cd1']}
              , keishiki_kubun_cd2 = ${data['keishiki_kubun_cd2']}
              , encho = ${data['encho']}
              , seiri_no = ${data['seiri_no']}
            WHERE
              sno = $sno;
SQL;

//             log_message('debug', "sql=$sql");
    $this->DB_rfs->query($sql);
    return 0;

  }

  /***
   *  登録用項目チェック
   *    登録項目がある場合その値(文字列の場合は登録用文字列)、
   *    無い場合は数値項目はNULLを、文字列項目は空文字を返却
   *
   *  引数
   *    $obj 項目格納オブジェクト
   *    $key 項目キー
   *    $kbn 1:数値項目、2:文字列項目
   ***/
  protected function chkItem($obj, $key, $kbn){
    if ($kbn==1) {
      // 数値項目
      $obj[$key]=isset($obj[$key])?$obj[$key]:"null";
    }else if ($kbn==2) {
      $obj[$key]=isset($obj[$key])?pg_escape_literal($obj[$key]):"null";
    }
    return $obj[$key];
  }

  /**
     *
     *  防雪柵の削除
     *
     *  引数のsnoの施設に対して、
     *
     */
  protected function delBousetsusaku($sno) {

    $sql= <<<SQL
DELETE
FROM
  rfs_m_bousetsusaku_shichu
WHERE
  sno = $sno
SQL;

    //        log_message('debug', "sql=$sql");
    $this->DB_rfs->query($sql);
    return 0;
  }

  /**
     *
     *  防雪柵の更新
     *
     *  引数のPOSTから防雪柵の基本情報を保存する。
     *  念のためUPSERT
     *
     */
  protected function insBousetsusaku($data) {

    if(!isset($data['struct_no_s'])){
      $data['struct_no_s'] = 'null';
    }
    if(!isset($data['struct_no_e'])){
      $data['struct_no_e'] = 'null';
    }
    if(!isset($data['sp'])){
      $data['sp'] = 'null';
    }
    if(!isset($data['kp'])){
      $data['kp'] = 'null';
    }
    $data['shisetsu_cd'] = pg_escape_literal($data['shisetsu_cd']);

    $sql= <<<SQL
            insert into public.rfs_m_bousetsusaku_shichu (
              sno
              , shisetsu_cd
              , shisetsu_ver
              , struct_idx
              , struct_no_s
              , struct_no_e
              , sp
              , kp
            )
            values(
              ${data['sno']}
              , ${data['shisetsu_cd']}
              , ${data['shisetsu_ver']}
              , ${data['struct_idx']}
              , ${data['struct_no_s']}
              , ${data['struct_no_e']}
              , ${data['sp']}
              , ${data['kp']}
            )
            ON CONFLICT ON CONSTRAINT rfs_m_bousetsusaku_shichu_pkey
            DO UPDATE SET
              struct_no_s = ${data['struct_no_s']}
              , struct_no_e = ${data['struct_no_e']}
              ,sp = ${data['sp']}
              ,kp =  ${data['kp']}
SQL;

    //        log_message('debug', "sql=$sql");
    $this->DB_rfs->query($sql);
    return 0;
  }

  /**
   *
   *  点検データの有無調査
   *
   *  引数のsnoから該当の施設が点検データに存在するかチェックする
   *
   */
  public function getChkTargetExist($sno) {

    $sql= <<<EOF
SELECT
    CAST(
    EXISTS (SELECT * FROM rfs_t_chk_main WHERE sno = $sno) as integer
  ) exist
EOF;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
/*
    log_message('debug', "sql=$sql");
    $r = print_r($result, true);
    log_message('debug', "result=$r");
*/
    return $result[0]['exist'];
  }

  /***
   *
   * 全景写真の変更
   *
   * 全景写真データの変更を行う。
   * 全景写真は、当初施設コードで紐付いていたため。
   *
   *   変更内容
   *   ・DBの施設コードの変更
   *   ・DBのfile_nm、pathに施設コードが入っている場合新施設コードでreplaced
   *   ・移動先にデータがある場合は、ファイル名を変える(ファイル名にsnoを付ける)
   *   ・移動が完了したら、DBデータUPDATE
   *
   ***/
  protected function editZenkei($syucchoujo_cd, $sno, $new_shisetsu_cd, $ver) {

    // 全景写真取得
    $zenkei=$this->getZenkei($sno);

    // データが無い場合処理終了(基本ない事ない)
    if (count($zenkei)==0) {
      return 0;
    }

    // 全てのデータが修正対象
    for ($i=0;$i<count($zenkei);$i++) {
      $item=$zenkei[$i];
      // 全景写真の施設コードとセットする施設コードが同じ場合はスルー
      // 画面で入力されている値で、写真の施設コードが保存されているため、
      // 実際に施設に登録されている施設コードとの比較が必要
      if ($item['shisetsu_cd'] == $new_shisetsu_cd) {
        continue;
      }
      // ファイル名をパスから取得
      $path_arr=explode("/", $item['path']);
      $file_nm=$path_arr[count($path_arr)-1];
      $old_full_path = $this->config->config['www_path'].$item['path']; // 現行フルパス
      $new_path = "images/photos/zenkei/$syucchoujo_cd/$new_shisetsu_cd/$file_nm"; // 新パス
      $new_file_nm=$new_path; // 新ファイル名=新パス
      $new_full_path = $this->config->config['www_path'].$new_path; // 新フルパス
      // 移動元が無ければDB削除
      if (file_exists($old_full_path)) {
        // 移動先のファイル確認
        if (file_exists($new_full_path)) {
          // ある場合はファイル名の頭にsnoを付けて保存する→全景写真の張り替えが発生した場合に通常の命名に従うことになる
          $new_path = "images/photos/zenkei/$syucchoujo_cd/$new_shisetsu_cd/$sno"."_"."$file_nm"; // 新パス
          $new_full_path = $this->config->config['www_path'].$new_path; // 新フルパス書き換え
        } else {
          // ファイルが無いので新しくセットする
          // ディレクトリが無い場合は作成する
          if(!is_dir( pathinfo( $new_full_path )["dirname"])){
            mkdir(pathinfo( $new_full_path )["dirname"], 0777,true);
          }
        }
        rename($old_full_path ,$new_full_path); // ファイル移動
        // 登録内容を更新
        $item['shisetsu_cd']=$new_shisetsu_cd;
        $item['shisetsu_ver']=$ver;
        $item['file_nm']=$new_path;
        $item['path']=$new_path;
        // DB書き換え
        $this->updZenkei($item);
      } else {
        // DBから削除
        $this->delZenkei($item);
      }
    }
    return 0;
  }

  /***
   *
   * 全景写真取得
   *  該当の施設をsnoで全景写真取得
   *
   * @return array
   *
   */
  public function getZenkei($sno) {
    log_message('debug', 'getZenkei');

    $sql= <<<EOF
SELECT
  sno
  , shisetsu_cd
  , struct_idx
  , zenkei_picture_cd
  , file_nm
  , path
FROM
  rfs_t_zenkei_picture
WHERE
  sno=$sno
ORDER BY
  struct_idx
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /***
   *
   * 全景写真更新
   * 引数のsno,struct_idxをキーにして、
   * 施設コード、施設バージョン、ファイル名、パスを更新する。
   *
   */
  public function updZenkei($zenkei) {
    log_message('debug', 'updZenkei');

    $sql= <<<EOF
UPDATE
  rfs_t_zenkei_picture
SET
  shisetsu_cd = '${zenkei['shisetsu_cd']}'
  , shisetsu_ver = ${zenkei['shisetsu_ver']}
  , file_nm = '${zenkei['file_nm']}'
  , path = '${zenkei['path']}'
WHERE
  sno = ${zenkei['sno']}
  AND struct_idx = ${zenkei['struct_idx']}
  AND zenkei_picture_cd = ${zenkei['zenkei_picture_cd']}
EOF;
    log_message('debug', "updSQL------------------------------------->".$sql."\n");
    $query = $this->DB_rfs->query($sql);
  }

  /***
   *
   * 全景写真削除
   * 引数のsno,struct_idxをキーにして、
   * 全景写真データを削除
   *
   */
  public function delZenkei($zenkei) {
    log_message('debug', 'delZenkei');

    $sql= <<<EOF
DELETE
FROM
  rfs_t_zenkei_picture
WHERE
  sno = ${zenkei['sno']}
  AND struct_idx = ${zenkei['struct_idx']}
  AND zenkei_picture_cd = ${zenkei['zenkei_picture_cd']}
EOF;
    log_message('debug', "delSQL------------------------------------->".$sql."\n");
    $query = $this->DB_rfs->query($sql);
  }


}
