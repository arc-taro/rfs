<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require("SchMst.php");





/******************************************
********** ShisetsuEdit.phpへ移行 **********
**********  二次リリース後削除予定  **********
******************************************/









/**
 * 施設の検索を行う
 * 返却は配列
 *
 * @access public
 * @package Model
 */
class EditShisetsu extends CI_Model {

    /**
    * コンストラクタ
    *
    * model SchCheckを初期化する。
    */
    public function __construct() {
        parent::__construct();
        $this->DB_rfs = $this->load->database('rfs',TRUE);
        if ($this->DB_rfs->conn_id === FALSE) {
            log_message('debug', 'データベースに接続されていません');
            return;
        }
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
     *        -1 パラメータ異常
     *        >0 重複エラー
     */
    public function set_shisetsu_id($post) {

        log_message('debug', "set_shisetsu_id");

        // 重複チェック
        $res=$this->get_shisetsu_max_ver($post);
        // パラメータ異常
        if ($res==-1){
            return $res;
        }

        // 先頭レコードの廃止年が入っていなければ重複
        if ($res) {
            if ($res[0]['haishi']=='' || $res[0]['haishi']==null) {
                return 1;
            }else{
                // 廃止になっているので、shisetsu_verは+1
                $shisetsu_ver=$res[0]['shisetsu_ver']+1;
            }
        }else{
            // レコードなし
            $shisetsu_ver=0;
        }

        // 追加に使用するsnoを取得する
        $sno = $this->get_next_sno($post);

        // トランザクション
        $this->DB_rfs->trans_start();
        // 本体
        $param=$this->ins_shisetsu($post, $shisetsu_ver, $sno);
        // 防雪柵の場合
        if($post['data']['shisetsu_kbn_row']['shisetsu_kbn']==4){
            if ($this->ins_bousetsusaku($param, $sno)!=0) {
                $this->DB_rfs->trans_rollback();
            }
        }
        $this->DB_rfs->trans_complete();
        return $param;

    }

    /**
     *  施設保存処理
     *
     *  施設（基本情報）をPOSTの中身で保存する。
     *  新規の場合は重複チェックを行い重複が無ければ
     *  追加処理を行う。
     *  更新時は、重複チェックを行わずUPSERTを行う
     *
     *  引数：$post
     *          kbn:0 新規、1:更新
     *          入力基本情報
     *
     *  戻り値：0 正常
     *        -1 パラメータ異常
     *        >0 重複エラー
     */
    public function set_shisetsu($post) {

        log_message('debug', "set_shisetsu");

/*
        // 新規登録時は重複チェック
        if ($post['kbn']==0) {
            $res=$this->chk_shisetsu_duplication($post);
            if ($res!=0) {
                return $res;
            }
            // 新規時は、キーの更新のみ

        }
*/

        // トランザクション
        $this->DB_rfs->trans_start();
        // 本体
        $res=$this->edit_shisetsu($post);
        if ($res!=0) {
            return $res;
        }

/*
        if ($post['kbn']==0) {
            $shisetsu_ver=0;
        }else{
        $shisetsu_ver=$post['shisetsu_ver'];
*/

        // 防雪柵の場合
        if($post['data']['shisetsu_kbn']==4){
            $bs = $post['data']['bs_row']['bs_info'];

            // Index0は更新しない
            for ($i=1;$i<count($bs);$i++){
                $bs[$i]['sno']=$post['data']['sno'];
                $bs[$i]['shisetsu_cd']=$post['data']['shisetsu_cd'];
                $bs[$i]['shisetsu_ver']=$post['data']['shisetsu_ver'];

                $data=$bs[$i];

//                $r = print_r($data, true);
//                log_message('debug', "bs:".$r);

                $res=$this->edit_bousetsusaku($data);
                if ($res!=0) {
                    return $res;
                }
            }

        }

        $this->DB_rfs->trans_complete();
        return 0;

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
    public function get_shisetsu_max_ver($post) {

        log_message('debug', 'get_shisetsu_max_ver');

        // 施設コードが無い場合はチェックしない
        if ($post['data']['shisetsu_cd']=="") {
            return -1;
        }

        $shisetsu_cd=$post['data']['shisetsu_cd'];

        $sql= <<<EOF
select
*
from
rfs_m_shisetsu
where
shisetsu_cd='$shisetsu_cd'
order by
shisetsu_ver desc
EOF;

        $query = $this->DB_rfs->query($sql);
        $result = $query->result('array');

        //$r = print_r($result, true);
        //        log_message('debug', "sql=$sql");
        //log_message('debug', "return:".$r);

        return $result;
    }

    /**
     * 次回の追加にて使用すべきsnoを返却する
     *
     * 施設で最大のsno + 1の値を返却する。
     *
     * @param   $post <br>
     *
     * @return row
     *
     */
    public function get_next_sno($post) {

        log_message('debug', 'get_next_sno');

        $sql= <<<EOF
        select
          case when max(sno) is null then
            0
          else
            max(sno) + 1
          end as sno
        from
          rfs_m_shisetsu
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
     *  施設の登録（キーと施設区分、管理者）
     *
     *  引数のPOSTから施設の基本情報を保存する。
     *
     */
    protected function ins_shisetsu($post,$shisetsu_ver, $sno) {

        $data = $post['data'];

        // dataにはない項目
        $arr['dogen_cd']=$post['dogen_cd'];
        $arr['syucchoujo_cd']=$post['syucchoujo_cd'];
        $arr['shisetsu_cd']=$data['shisetsu_cd'];
        $arr['shisetsu_ver']=$shisetsu_ver;
        $arr['shisetsu_kbn']=$data['shisetsu_kbn_row']['shisetsu_kbn'];

        $arr['sno']=$sno;

        $shisetsu_cd = pg_escape_literal($arr['shisetsu_cd']);

        $sql= <<<SQL
            insert into rfs_m_shisetsu (
              shisetsu_cd
              , shisetsu_ver
              , shisetsu_kbn
              , dogen_cd
              , syucchoujo_cd
            )
            values(
              $shisetsu_cd
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
    protected function ins_bousetsusaku($param, $sno){

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
     *
     *  施設の登録
     *
     *  引数のPOSTから施設の基本情報を保存する。
     *
     */
     protected function edit_shisetsu($post) {

        $data = $post['data'];

        // dataにはない項目
        $data['dogen_cd']=$post['dogen_cd'];
        $data['syucchoujo_cd']=$post['syucchoujo_cd'];

        // 施設区分、形式
        $data['shisetsu_kbn']=$data['shisetsu_kbn_row']['shisetsu_kbn'];
        // 形式
        $data['shisetsu_keishiki_cd'] = $data['keishiki_row']['shisetsu_keishiki_cd'];

        // 設置年度
        if(!isset($data['secchi_row'])){
            $data['secchi']='';
            $data['secchi_yyyy'] = 'null';
        }else{
            $data['secchi'] = $data['secchi_row']['gengou'];
            $data['secchi_yyyy'] = $data['secchi_row']['year'];
        }

        // 設置年度
        if(!isset($data['haishi_row'])){
            $data['haishi']='';
            $data['haishi_yyyy'] = 'null';
        }else{
            $data['haishi'] = $data['haishi_row']['gengou'];
            $data['haishi_yyyy'] = $data['haishi_row']['year'];
        }

        // 路線
        $data['rosen_cd'] = $data['rrow']['rosen_cd'];

        if(!isset($data['shityouson'])){
            $data['shityouson'] = '';
        }
        if(!isset($data['azaban'])){
            $data['azaban'] = '';
        }
        if(!isset($data['lat'])){
            $data['lat'] = 'null';
        }
        if(!isset($data['lon'])){
            $data['lon'] = 'null';
        }
        if(!isset($data['substitute_road'])){
            $data['substitute_road'] = 'null';
        }
        if(!isset($data['emergency_road'])){
            $data['emergency_road'] = 'null';
        }
        if(!isset($data['motorway'])){
            $data['motorway'] = 'null';
        }
        if(!isset($data['senyou'])){
            $data['senyou'] = '';
        }
        if(!isset($data['secchi'])){
            $data['secchi'] = '';
        }
        if(!isset($data['haishi'])){
            $data['haishi'] = '';
        }
        if(!isset($data['fukuin'])){
            $data['fukuin'] = '';
        }
        if(!isset($data['sp'])){
            $data['sp'] = 'null';
        }
        if(!isset($data['kp'])){
            $data['kp'] = 'null';
        }
        if(!isset($data['lr'])){
            $data['lr'] = 'null';
        }
        if(!isset($data['secchi_yyyy'])){
            $data['secchi_yyyy'] = '';
        }
        if(!isset($data['haishi_yyyy'])){
            $data['haishi_yyyy'] = '';
        }

        $data['sno'] = $data['sno'];
        $data['shisetsu_cd'] = pg_escape_literal($data['shisetsu_cd']);
        $data['shityouson'] = pg_escape_literal($data['shityouson']);
        $data['azaban'] = pg_escape_literal($data['azaban']);
        $data['senyou'] = pg_escape_literal($data['senyou']);
        $data['secchi'] = pg_escape_literal($data['secchi']);
        $data['haishi'] = pg_escape_literal($data['haishi']);
        $data['fukuin'] = pg_escape_literal($data['fukuin']);
/*
        $r = print_r($data, true);
        log_message('debug', "data:".$r);
*/
        $sql= <<<SQL
            insert into public.rfs_m_shisetsu (
              sno
              , shisetsu_cd
              , shisetsu_ver
              , shisetsu_kbn
              , shisetsu_keishiki_cd
              , rosen_cd
              , shityouson
              , azaban
              , lat
              , lon
              , dogen_cd
              , syucchoujo_cd
              , substitute_road
              , emergency_road
              , motorway
              , senyou
              , secchi
              , haishi
              , fukuin
              , sp
              , kp
              , lr
              , secchi_yyyy
              , haishi_yyyy
            )
            values(
              ${data['sno']}
              , ${data['shisetsu_cd']}
              , ${data['shisetsu_ver']}
              , ${data['shisetsu_kbn']}
              , ${data['shisetsu_keishiki_cd']}
              , ${data['rosen_cd']}
              , ${data['shityouson']}
              , ${data['azaban']}
              , ${data['lat']}
              , ${data['lon']}
              , ${data['dogen_cd']}
              , ${data['syucchoujo_cd']}
              , ${data['substitute_road']}
              , ${data['emergency_road']}
              , ${data['motorway']}
              , ${data['senyou']}
              , ${data['secchi']}
              , ${data['haishi']}
              , ${data['fukuin']}
              , ${data['sp']}
              , ${data['kp']}
              , ${data['lr']}
              , ${data['secchi_yyyy']}
              , ${data['haishi_yyyy']}
            )
            ON CONFLICT ON CONSTRAINT rfs_m_shisetsu_pkey
            DO UPDATE SET
              shisetsu_kbn = ${data['shisetsu_kbn']}
              , shisetsu_keishiki_cd = ${data['shisetsu_keishiki_cd']}
              ,rosen_cd = ${data['rosen_cd']}
              ,shityouson =  ${data['shityouson']}
              ,azaban =  ${data['azaban']}
              ,lat =  ${data['lat']}
              ,lon =  ${data['lon']}
              ,dogen_cd =  ${data['dogen_cd']}
              ,syucchoujo_cd =  ${data['syucchoujo_cd']}
              ,substitute_road =  ${data['substitute_road']}
              ,emergency_road =  ${data['emergency_road']}
              ,motorway =  ${data['motorway']}
              ,senyou =  ${data['senyou']}
              ,secchi =  ${data['secchi']}
              ,haishi =  ${data['haishi']}
              ,fukuin =  ${data['fukuin']}
              ,sp =  ${data['sp']}
              ,kp =  ${data['kp']}
              ,lr =  ${data['lr']}
              ,secchi_yyyy =  ${data['secchi_yyyy']}
              ,haishi_yyyy =  ${data['haishi_yyyy']}
SQL;

//         log_message('debug', "sql=$sql");
         $this->DB_rfs->query($sql);
         return 0;

    }

    /**
     *
     *  防雪柵の更新
     *
     *  引数のPOSTから防雪柵の基本情報を保存する。
     *
     */
    protected function edit_bousetsusaku($data) {

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

        $data['sno'] = $data['sno'];
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

}
