<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * H28年度点検集計モデル
 * 該当データを全て取得
 *
 * @access public
 * @package Model
 */
class SumHuzokubutsuModels extends CI_Model {

  protected $DB_rfs;  // rfsコネクション

  /**
    * コンストラクタ
    */
  public function __construct() {
    parent::__construct();
    $this->DB_rfs = $this->load->database('rfs',TRUE);
    if ($this->DB_rfs->conn_id === FALSE) {
      log_message('debug', 'データベースに接続されていません');
      return;
    }
  }

  /***
   * 点検データを取得する。
   * 1行1施設とし、点検データ(部材以下)がJSON形式でぶら下がる。
   *
   * 引数:$get
   *      shisetsu_kbn 施設区分
   *      nendo 選択年度
   *
   * 戻り値 $ret 取得データ配列
   ***/
  public function getHuzokubutsuAll($get){

    ini_set('memory_limit', '1024M');

    // 施設区分判定
    // SQL条件追加
    if ($get['shisetsu_kbn']==4) {
      // 防雪柵
      $where="AND c.struct_idx > 0";
    } else {
      // 防雪柵以外
      $where="";
    }

/*     $nendo=$get['nendo'];
    $nendo_plus = $nendo+1;
    $shisetsu_kbn=$get['shisetsu_kbn'];
 */

$shisetsu_kbn=$get['shisetsu_kbn'];
// ********************************************************************
// 20190109
// 点検が増えてきたため年度を復帰
// また、本庁権限でのみ使用する機能なため、全道分出力することとする
// ********************************************************************
if ($get['nendo']) {
  $nendo=$get['nendo'];
  $nendo_plus = $nendo+1;
  $target_start_dt = "{$nendo}-04-01";
  $target_end_dt = "{$nendo_plus}-04-01";
  $where_range=" AND (c.target_dt >= '{$target_start_dt}' AND c.target_dt < '{$target_end_dt}')";
} else {
  $where_range=" AND c.target_dt < '2016-04-01'";
}
// 年度を外し、出張所が選択されている場合は出張所単位
// 出張所が全ての場合建管単位で取るように修正
/* $where_range = "";
if ($this->session['mngarea']['syucchoujo_cd']==0) {
  $where_range = "AND dogen_cd = ".$this->session['mngarea']['dogen_cd'];
} else{
  $where_range = "AND syucchoujo_cd = ".$this->session['mngarea']['syucchoujo_cd'];
}
 */

$sql= <<<EOF
  WITH shisetsu AS (
    SELECT
        *
    FROM
      rfs_m_shisetsu
    WHERE
      shisetsu_kbn = $shisetsu_kbn
      AND (haishi is null OR haishi = '')
  --    $where_range
  )
  --, shisetsu AS (
  --  SELECT
  --      s_tmp1.*
  --  FROM
  --    s_tmp s_tmp1 JOIN (
  --      SELECT
  --          sno
  --        , max(shisetsu_ver) shisetsu_ver
  --      FROM
  --        s_tmp
  --      GROUP BY
  --        sno
  --    ) s_tmp2
  --      ON s_tmp1.sno = s_tmp2.sno
  --      AND s_tmp1.shisetsu_ver = s_tmp2.shisetsu_ver
  --)
  
  
  --, c_tmp_narrow AS (
  --  SELECT
  --      c.*
  --  FROM
  --    rfs_t_chk_main c JOIN shisetsu s
  --      ON c.sno = s.sno
  --  WHERE
  --    '$nendo-04-01' <= c.target_dt
  --    AND c.target_dt < '$nendo_plus-04-01'
  --    $where
  --)
  
--  , c_tmp_narrow AS ( 
--    SELECT
--        c.* 
--    FROM
--      rfs_t_chk_main c JOIN shisetsu s 
--        ON c.sno = s.sno
--        AND c.chk_times=0
--  ) 
, c_only_narrow AS ( 
  SELECT
      c.* 
  FROM
    shisetsu s JOIN rfs_t_chk_main c 
      ON s.sno = c.sno
  WHERE TRUE
    $where_range
    
) 
, c_tmp_narrow AS ( 
  SELECT
      c_tmp1.* 
  FROM
    c_only_narrow c_tmp1 JOIN ( 
      SELECT
          sno
        , max(chk_times) chk_times 
      FROM
        c_only_narrow 
      GROUP BY
        sno
    ) c_tmp2 
      ON c_tmp1.sno = c_tmp2.sno 
      AND c_tmp1.chk_times = c_tmp2.chk_times
) 
  , h_tmp_narrow AS (
    SELECT 
      * 
    FROM 
      rfs_t_chk_huzokubutsu 
--    WHERE 
--      phase IN (2,3,5)
  )
  , c_h_join AS (
    SELECT
        h.*
    FROM
    h_tmp_narrow h
      INNER JOIN c_tmp_narrow
        ON h.chk_mng_no = c_tmp_narrow.chk_mng_no
  )
  , c_b_join AS (
    SELECT
        b.*
    FROM
      rfs_t_chk_buzai b
      INNER JOIN c_tmp_narrow
        ON b.chk_mng_no = c_tmp_narrow.chk_mng_no
  )
  , c_tk_join AS (
    SELECT
        tk.*
    FROM
      rfs_t_chk_tenken_kasyo tk
      INNER JOIN c_tmp_narrow
        ON tk.chk_mng_no = c_tmp_narrow.chk_mng_no
  )
  , c_sn_join AS (
    SELECT
        sn.*
    FROM
      rfs_t_chk_sonsyou sn
      INNER JOIN c_tmp_narrow
        ON sn.chk_mng_no = c_tmp_narrow.chk_mng_no
  )
  , c_pct_join AS (
    SELECT
        pct.*
    FROM
      rfs_t_chk_picture pct
      INNER JOIN c_tmp_narrow
        ON pct.chk_mng_no = c_tmp_narrow.chk_mng_no
  )
  , h_tmp AS (
    SELECT
        h_tmp1.*
    FROM
      c_h_join h_tmp1 JOIN (
        SELECT
            chk_mng_no
          , max(rireki_no) rireki_no
        FROM
          c_h_join
        GROUP BY
          chk_mng_no
      ) h_tmp2
        ON h_tmp1.chk_mng_no = h_tmp2.chk_mng_no
        AND h_tmp1.rireki_no = h_tmp2.rireki_no
  )
  , b_tmp AS (
    SELECT
        b_tmp1.*
      , CASE
        WHEN b_tmp1.necessity_measures = 2
        THEN '否'
        ELSE '要'
        END necessity_measures_str
      , sj1.shisetsu_judge_nm check_buzai_judge_nm
      , sj1.shisetsu_judge_nm check_buzai_judge_nm2
      , sj2.shisetsu_judge_nm measures_buzai_judge_nm
    FROM
      c_b_join b_tmp1 JOIN (
        SELECT
            chk_mng_no
          , max(rireki_no) rireki_no
        FROM
          c_b_join
        GROUP BY
          chk_mng_no
      ) b_tmp2
        ON b_tmp1.chk_mng_no = b_tmp2.chk_mng_no
        AND b_tmp1.rireki_no = b_tmp2.rireki_no
      LEFT JOIN rfs_m_shisetsu_judge sj1
        ON b_tmp1.check_buzai_judge = sj1.shisetsu_judge
      LEFT JOIN rfs_m_shisetsu_judge sj2
        ON b_tmp1.measures_buzai_judge = sj2.shisetsu_judge
  )
  , tk_tmp AS (
    SELECT
        tk_tmp1.*
    FROM
      c_tk_join tk_tmp1 JOIN (
        SELECT
            chk_mng_no
          , max(rireki_no) rireki_no
        FROM
          c_tk_join
        GROUP BY
          chk_mng_no
      ) tk_tmp2
        ON tk_tmp1.chk_mng_no = tk_tmp2.chk_mng_no
        AND tk_tmp1.rireki_no = tk_tmp2.rireki_no
  )
  , sn_tmp_before AS (
    SELECT
        sn_tmp1.*
    FROM
      c_sn_join sn_tmp1 JOIN (
        SELECT
            chk_mng_no
          , max(rireki_no) rireki_no
        FROM
          c_sn_join
        GROUP BY
          chk_mng_no
      ) sn_tmp2
        ON sn_tmp1.chk_mng_no = sn_tmp2.chk_mng_no
        AND sn_tmp1.rireki_no = sn_tmp2.rireki_no
  )
  , sn_tmp AS (
    SELECT
        sn_row.chk_mng_no
      , jsonb_agg(
        ARRAY [ buzai_cd :: text , buzai_detail_cd ::text , tenken_kasyo_cd :: text , sonsyou_naiyou_cd :: text , CASE WHEN check_before = 0 THEN '未' WHEN check_before = 1 THEN 'a' WHEN check_before = 2 THEN 'c' WHEN check_before = 3 THEN 'e' ELSE '-' END , CASE WHEN measures_after = 0 THEN '未' WHEN measures_after = 1 THEN 'a' WHEN measures_after = 2 THEN 'c' WHEN measures_after = 3 THEN 'e' ELSE '-' END ]
        ORDER BY
          sn_row.chk_mng_no
          , sn_row.buzai_cd
          , sn_row.buzai_detail_cd
          , sn_row.tenken_kasyo_cd
          , sn_row.sonsyou_naiyou_cd
      ) sn_tmp_list
    FROM
      sn_tmp_before sn_row
    GROUP BY
      sn_row.chk_mng_no
  )
  , pic_tmp_1 AS (
    SELECT
        pic_tmp1.*
    FROM
      c_pct_join pic_tmp1 JOIN (
        SELECT
            chk_mng_no
          , buzai_cd
          , buzai_detail_cd
          , tenken_kasyo_cd
          , max(picture_cd) picture_cd
        FROM
          c_pct_join
        GROUP BY
          chk_mng_no
          , buzai_cd
          , buzai_detail_cd
          , tenken_kasyo_cd
      ) pic_tmp2
        ON pic_tmp1.chk_mng_no = pic_tmp2.chk_mng_no
        AND pic_tmp1.buzai_cd = pic_tmp2.buzai_cd
        AND pic_tmp1.buzai_detail_cd = pic_tmp2.buzai_detail_cd
        AND pic_tmp1.tenken_kasyo_cd = pic_tmp2.tenken_kasyo_cd
        AND pic_tmp1.picture_cd = pic_tmp2.picture_cd
    WHERE
      status = 0
  )
  , pic_tmp_2 AS (
    SELECT
        pic_tmp1.*
    FROM
      c_pct_join pic_tmp1 JOIN (
        SELECT
            chk_mng_no
          , buzai_cd
          , buzai_detail_cd
          , tenken_kasyo_cd
          , max(picture_cd) picture_cd
        FROM
          c_pct_join
        GROUP BY
          chk_mng_no
          , buzai_cd
          , buzai_detail_cd
          , tenken_kasyo_cd
      ) pic_tmp2
        ON pic_tmp1.chk_mng_no = pic_tmp2.chk_mng_no
        AND pic_tmp1.buzai_cd = pic_tmp2.buzai_cd
        AND pic_tmp1.buzai_detail_cd = pic_tmp2.buzai_detail_cd
        AND pic_tmp1.tenken_kasyo_cd = pic_tmp2.tenken_kasyo_cd
        AND pic_tmp1.picture_cd = pic_tmp2.picture_cd
    WHERE
      status = 1
  )
  , c_tmp AS (
    SELECT
        c_tmp_narrow.*
      , sn_tmp.sn_tmp_list sn_list
    FROM
      c_tmp_narrow LEFT JOIN sn_tmp
        ON c_tmp_narrow.chk_mng_no = sn_tmp.chk_mng_no
  )
  , tk_sn AS (
    SELECT
        tk_sn_tmp.chk_mng_no
      , tk_sn_tmp.buzai_cd
      , json_agg(tk_sn_tmp) tk_sn_list
    FROM
      (
        SELECT
            tk.*
          , CASE
            WHEN tk.taisyou_umu = 0
            THEN '有'
            ELSE '無'
            END taisyou_umu_str
          , CASE
            WHEN tk.check_status = 1
            THEN '済'
            ELSE '未'
            END check_status_str
          , sj1.shisetsu_judge_nm check_judge_nm
          , sj2.shisetsu_judge_nm measures_judge_nm
          , msn.sonsyou_naiyou_nm
          , p1.PATH path1
          , p2.PATH path2
          , p1.picture_nm picture_nm1
          , p2.picture_nm picture_nm2
        FROM
          tk_tmp tk
          LEFT JOIN pic_tmp_1 p1
            ON tk.chk_mng_no = p1.chk_mng_no
            AND tk.buzai_cd = p1.buzai_cd
            AND tk.buzai_detail_cd = p1.buzai_detail_cd
            AND tk.tenken_kasyo_cd = p1.tenken_kasyo_cd
          LEFT JOIN pic_tmp_2 p2
            ON tk.chk_mng_no = p2.chk_mng_no
            AND tk.buzai_cd = p2.buzai_cd
            AND tk.buzai_detail_cd = p2.buzai_detail_cd
            AND tk.tenken_kasyo_cd = p2.tenken_kasyo_cd
          LEFT JOIN rfs_m_sonsyou_naiyou msn
            ON tk.sonsyou_naiyou_cd = msn.sonsyou_naiyou_cd
          LEFT JOIN rfs_m_shisetsu_judge sj1
            ON tk.check_judge = sj1.shisetsu_judge
          LEFT JOIN rfs_m_shisetsu_judge sj2
            ON tk.measures_judge = sj2.shisetsu_judge
        ORDER BY
          tk.chk_mng_no
          , tk.buzai_cd
          , tk.buzai_detail_cd
          , tk.tenken_kasyo_cd
      ) tk_sn_tmp
    GROUP BY
      tk_sn_tmp.chk_mng_no
      , tk_sn_tmp.buzai_cd
  )
  , b_tk_sn AS (
    SELECT
        b_tk_sn_tmp.chk_mng_no
      , json_agg(b_tk_sn_tmp) b_tk_sn_tmp_list
    FROM
      (
        SELECT
            b_tmp.*
          , tk_sn.tk_sn_list
        FROM
          b_tmp
          LEFT JOIN tk_sn
            ON b_tmp.chk_mng_no = tk_sn.chk_mng_no
            AND b_tmp.buzai_cd = tk_sn.buzai_cd
        ORDER BY
          b_tmp.chk_mng_no
          , b_tmp.buzai_cd
      ) b_tk_sn_tmp
    GROUP BY
      b_tk_sn_tmp.chk_mng_no
  --  ORDER BY
  --    b_tk_sn_tmp.chk_mng_no
  )
  , bs_cnt AS (
    SELECT
        sno
      , count(*) - 1 bscnt
    FROM
      rfs_m_bousetsusaku_shichu
    GROUP BY
      sno
  --  ORDER BY
  --    sno
  )
  SELECT
      s.*
    , sk.shisetsu_kbn_nm
    , skei.shisetsu_keishiki_nm
    , d.dogen_mei
    , syu.syucchoujo_mei
    , r.rosen_nm
    , CASE
      WHEN s.lr = 0
      THEN 'L'
      WHEN s.lr = 1
      THEN 'R'
      WHEN s.lr = 2
      THEN 'C'
      WHEN s.lr = 3
      THEN 'LR'
      END lr_str
    , CASE
      WHEN s.substitute_road = 0
      THEN '有'
      WHEN s.substitute_road = 1
      THEN '無'
      ELSE '-'
      END substitute_road_str
    , CASE
      WHEN s.emergency_road = 1
      THEN '第1次'
      WHEN s.emergency_road = 2
      THEN '第2次'
      WHEN s.emergency_road = 3
      THEN '第3次'
      ELSE '-'
      END emergency_road_str
    , CASE
      WHEN s.motorway = 0
      THEN '自専道'
      WHEN s.motorway = 1
      THEN '一般道'
      ELSE '-'
      END motorway_str
    , c.chk_mng_no
    , c.chk_times
    , c.struct_idx
    , c.target_dt
    , c.sn_list
    , h.chk_mng_no h_chk_mng_no
    , h.chk_dt
    , h.chk_company
    , h.chk_person
    , h.investigate_dt
    , h.investigate_company
    , h.investigate_person
    , h.surface
    , CASE
      WHEN h.surface = 1
      THEN '亜鉛メッキ'
      WHEN h.surface = 2
      THEN '亜鉛メッキ＋塗装'
      WHEN h.surface = 3
      THEN 'さび止め＋塗装'
      END surface_str
    , h.part_notable_chk
    , h.reason_notable_chk
    , h.special_report
    , h.phase
    , h.check_shisetsu_judge
    , sj1.shisetsu_judge_nm check_shisetsu_judge_nm
    , sj1.shisetsu_judge_nm check_shisetsu_judge_nm2
    , h.syoken
    , h.update_dt
    , h.measures_shisetsu_judge
    , sj2.shisetsu_judge_nm measures_shisetsu_judge_nm
    , h.create_account
    , bs.struct_no_s
    , bs.struct_no_e
    , bs_c.bscnt
    , b_tk_sn.b_tk_sn_tmp_list b_tk_sn_list
  FROM
    shisetsu s JOIN c_tmp c
      ON s.sno = c.sno LEFT JOIN h_tmp h
        ON c.chk_mng_no = h.chk_mng_no LEFT JOIN b_tk_sn
          ON h.chk_mng_no = b_tk_sn.chk_mng_no
    LEFT JOIN rfs_m_shisetsu_kbn sk
      ON s.shisetsu_kbn = sk.shisetsu_kbn
    LEFT JOIN rfs_m_shisetsu_keishiki skei
      ON s.shisetsu_kbn = skei.shisetsu_kbn
      AND s.shisetsu_keishiki_cd = skei.shisetsu_keishiki_cd
    LEFT JOIN rfs_m_dogen d
      ON s.dogen_cd = d.dogen_cd
    LEFT JOIN rfs_m_syucchoujo syu
      ON s.syucchoujo_cd = syu.syucchoujo_cd
    LEFT JOIN rfs_m_rosen r
      ON s.rosen_cd = r.rosen_cd
    LEFT JOIN rfs_m_shisetsu_judge sj1
      ON h.check_shisetsu_judge = sj1.shisetsu_judge
    LEFT JOIN rfs_m_shisetsu_judge sj2
      ON h.measures_shisetsu_judge = sj2.shisetsu_judge
    LEFT JOIN rfs_m_bousetsusaku_shichu bs
      ON c.sno = bs.sno
      AND c.struct_idx = bs.struct_idx
    LEFT JOIN bs_cnt bs_c
      ON c.sno = bs_c.sno                           --ORDER BY
                                                    --  s.syucchoujo_cd
                                                    --  , s.shisetsu_cd
                                                    --  , c.struct_idx
EOF;
//log_message("debug",$sql);
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /***
   * 点検データの整形
   * 1施設の点検データをNDS提供EXCELの仕様に合わせ整形する。
   * 引数:$shisetsu_row
   *      含まれるデータ:施設基本情報
   *                  点検管理main情報
   *                  点検 施設情報
   *                  点検 部材以下の情報(JSON形式)
   * 戻り値 1行の配列データ
   ***/
  public function shapePerShisetsu($shisetsu_row) {
    //log_message("debug","shapePerShisetsu");

    $res = array();
    // データの展開
    foreach ($shisetsu_row as $key => $val) {
      // 部材以下のJSON形式データを配列化
      if ($key=="b_tk_sn_list") {
        $b_tk_sn=json_decode($val,true,JSON_NUMERIC_CHECK);
        //log_message("debug", print_r($b_tk_sn,true));
        if ($b_tk_sn){
          $this->setOrLaterBuzai($b_tk_sn, $res);
        }
      // 点検にぶら下がる全損傷
      } else if ($key=="sn_list") {
        $tenken_kasyo_all_sonsyou=json_decode($val,true);
        if ($tenken_kasyo_all_sonsyou) {
          $this->setAllSonsyou($tenken_kasyo_all_sonsyou, $res);
        }
      } else {
        // 施設と1対1の情報についてはこちらで保持
        $res[$key]=$val;
      }
    }
//確認用
    //log_message("debug", print_r($res,true));
    return $res;
  }

  /***
   * 部材以下データの整形
   * 1施設にぶら下がる部材以下データ配列をNDS提供EXCELの仕様に合わせ整形する。
   *
   * 引数:$data
   *      含まれるデータ:部材情報
   *                  部材にぶら下がる点検箇所配列
   *     $arr
   *      戻す値 NDS提供EXCELに合わせセットする
   *      整形した1行の配列データ
   ***/
  protected function setOrLaterBuzai($data, &$arr) {
    //log_message("debug","setOrLaterBuzai");

    // 部材ループ
    foreach ($data as $buzai) {
      $buzai_cd = $buzai['buzai_cd']; // 部材コード保持
//      log_message("debug","------------------------------------>部材コード:".$buzai_cd);

      // 部材内
      foreach ($buzai as $key => $val) {
        // 点検箇所配列
        if ($key=="tk_sn_list") {
          $this->setTenkenKasyo($val, $arr);
        } else {
          // 部材の情報
          $arr["${key}_${buzai_cd}"]=$val;
        }
      }
    }
  }

  /***
   * 全損傷データの整形
   * 1施設にぶら下がる全損傷データ配列をNDS提供EXCELの仕様に合わせ整形する。
   *
   * 引数:$data
   *      含まれるデータ:全損傷情報
   *     $arr
   *      戻す値 NDS提供EXCELに合わせセットする
   *      整形した1行の配列データ
   ***/
  protected function setAllSonsyou($data, &$arr){
    //log_message("debug","setAllSonsyou");
    foreach ($data as $item) {
//      $buzai_cd=$item['buzai_cd'];
//      $buzai_detail_cd=$item['buzai_detail_cd'];
//      $tenken_kasyo_cd=$item['tenken_kasyo_cd'];
//      $sonsyou_naiyou_cd=$item['sonsyou_naiyou_cd'];
//      $arr["check_before_str_${buzai_cd}_${buzai_detail_cd}_${tenken_kasyo_cd}_${sonsyou_naiyou_cd}"]=$item['check_before_str'];
//      $arr["measures_after_str_${buzai_cd}_${buzai_detail_cd}_${tenken_kasyo_cd}_${sonsyou_naiyou_cd}"]=$item['measures_after_str'];
      $buzai_cd=$item[0];
      $buzai_detail_cd=$item[1];
      $tenken_kasyo_cd=$item[2];
      $sonsyou_naiyou_cd=$item[3];
      $arr["check_before_str_${buzai_cd}_${buzai_detail_cd}_${tenken_kasyo_cd}_${sonsyou_naiyou_cd}"]=$item[4];
      $arr["measures_after_str_${buzai_cd}_${buzai_detail_cd}_${tenken_kasyo_cd}_${sonsyou_naiyou_cd}"]=$item[5];
    }
  }

  /***
   * 点検箇所および損傷データの整形
   * 1施設にぶら下がる点検箇所データ配列と全損傷をNDS提供EXCELの仕様に合わせ整形する。
   *
   * 引数:$data
   *      含まれるデータ:点検箇所情報
   *                  点検箇所毎の全損傷情報
   *     $arr
   *      戻す値 NDS提供EXCELに合わせセットする
   *      整形した1行の配列データ
   ***/
  protected function setTenkenKasyo($data, &$arr){

    $chk_sonsyou = "";        // 点検時健全性Ⅱ以上の損傷を保持
    $measure_sonsyou = "";    // 措置後健全性Ⅱ以上の損傷を保持
    $chk_pic_nm = "";         // 点検時健全性Ⅱ以上の写真名(番号のみ)を保持
    $measure_pic_nm = "";     // 措置後健全性Ⅱ以上の写真名(番号のみ)を保持

    // 損傷と写真は部材毎に集計
    $chk_sonsyous=array();
    $chk_pictures=array();
    $measure_sonsyous=array();
    $chk_sonsyou="";
    $chk_picture="";
    $measure_sonsyou="";
    $last_measures_dt = null;

    // 点検箇所ループ
    foreach ($data as $tenken_kasyo) {

      $buzai_cd=$tenken_kasyo['buzai_cd'];
      $buzai_detail_cd=$tenken_kasyo['buzai_detail_cd'];
      $tenken_kasyo_cd=$tenken_kasyo['tenken_kasyo_cd'];

      /*** 点検 ***/
      // 損傷・写真は健全性Ⅱ以上を列挙する(部材単位)
      if ($tenken_kasyo["check_judge"] >= 2) {
        $chk_sonsyou=$tenken_kasyo["sonsyou_naiyou_nm"];
        $chk_picture=$tenken_kasyo["picture_nm1"];
        // 保持配列に無ければセットする
        if (array_search($chk_sonsyou, $chk_sonsyous) === false) {
          array_push($chk_sonsyous,$chk_sonsyou);
        }
        if (array_search($chk_picture, $chk_pictures) === false) {
          array_push($chk_pictures,$chk_picture);
        }
      }
      /*** 措置後 ***/
      if ($tenken_kasyo["measures_judge"] >= 2) {
        $measure_sonsyou=$tenken_kasyo["sonsyou_naiyou_nm"];
        // 保持配列に無ければセットする
        if (array_search($measure_sonsyou, $measure_sonsyous) === false) {
          array_push($measure_sonsyous,$measure_sonsyou);
        }
      }
      /*** 措置日 ***/ // 最新を保持
      if ($tenken_kasyo["measures_dt"] && $tenken_kasyo["measures_dt"] > $last_measures_dt) {
        $last_measures_dt = $tenken_kasyo["measures_dt"];
      }
      // 点検箇所内ループ
      foreach ($tenken_kasyo as $key => $val) {
        $arr["${key}_${buzai_cd}_${buzai_detail_cd}_${tenken_kasyo_cd}"]=$val;
      }
    }

    // 損傷と写真番号について1絡むに「、」区切りでセット
    /*** 点検時損傷内容 ***/
    $tmp="";
    foreach ($chk_sonsyous as $chk_sonsyou) {
      if ($tmp!="") {
        $tmp.="、";
      }
      $tmp.=$chk_sonsyou;
    }
    $arr["check_sonsyou_naiyou_nm_${buzai_cd}"]=$tmp;
    //log_message("debug", print_r($tmp,true));
    /*** 点検時写真番号 ***/
    $tmp="";
    foreach ($chk_pictures as $chk_picture) {
      if ($tmp!="") {
        $tmp.="、";
      }
      $tmp.=$chk_picture;
    }
    $arr["picture_nm_${buzai_cd}"]=$tmp;
    //log_message("debug", print_r($tmp,true));
    /*** 措置後損傷内容 ***/
    $tmp="";
    foreach ($measure_sonsyous as $measure_sonsyou) {
      if ($tmp!="") {
        $tmp.="、";
      }
      $tmp.=$measure_sonsyou;
    }
    $arr["measures_sonsyou_naiyou_nm_${buzai_cd}"]=$tmp;
    //log_message("debug", print_r($tmp,true));
    /*** 最新の措置日 ***/
    $arr["measures_dt_${buzai_cd}"]=$last_measures_dt;
    //log_message("debug", print_r($tmp,true));
  }

  /**
   * CSVを出力する
   *
   * @param array $outarr
   */
  public function outputListCsvHead($file,$shisetsu_kbn) {
    log_message('debug', __METHOD__);
    $fields = [];
    try {
      // ヘッダー
      // 基本情報
      if ($shisetsu_kbn == 4) {
        // 防雪柵の場合は支柱番号を追加
        $export_csv_title_kihon = array('施設名','形式','管理番号','No.○/総記録枚数','開始','～','終了','路線番号','路線名','市町村','字番','緯度','経度','測点','横断区分','代替路の有無','緊急輸送道路','自専道or一般道','占用物件(名称)','建設管理部','出張所','点検実施年月日','点検員 会社名','点検員 氏名','調査実施年月日','調査員 会社名','調査員 氏名');
      } else {
        $export_csv_title_kihon = array('施設名','形式','管理番号','路線番号','路線名','市町村','字番','緯度','経度','測点','横断区分','代替路の有無','緊急輸送道路','自専道or一般道','占用物件(名称)','建設管理部','出張所','点検実施年月日','点検員 会社名','点検員 氏名','調査実施年月日','調査員 会社名','調査員 氏名');
      }

      // 損傷情報
      if ($shisetsu_kbn == 1) {  // 道路標識
        $export_csv_title_sonsyou = array('支柱 判定区分','支柱 損傷の種類','支柱 備考','支柱 措置後の判定区分','支柱 措置後の損傷の種類','支柱 措置及び判定実施年月日','横梁 判定区分','横梁 損傷の種類','横梁 備考','横梁 措置後の判定区分','横梁 措置後の損傷の種類','横梁 措置及び判定実施年月日','標識板 判定区分','標識板 損傷の種類','標識板 備考','標識板 措置後の判定区分','標識板 措置後の損傷の種類','標識板 措置及び判定実施年月日','基礎 判定区分','基礎 損傷の種類','基礎 備考','基礎 措置後の判定区分','基礎 措置後の損傷の種類','基礎 措置及び判定実施年月日','その他 判定区分','その他 損傷の種類','その他 備考','その他 措置後の判定区分','その他 措置後の損傷の種類','その他 措置及び判定実施年月日','附属物毎の健全性の診断 判定区分','附属物毎の健全性の診断 所見等','附属物毎の健全性の診断 再判定区分','附属物毎の健全性の診断 再判定実施年月日','全景写真 設置年月','全景写真 道路幅員(m)','支柱本体(Pph) 対象有無','支柱本体(Pph) 点検状況','支柱本体(Pph) き裂 点検時','支柱本体(Pph) き裂 措置後','支柱本体(Pph) 腐食 点検時','支柱本体(Pph) 腐食 措置後','支柱本体(Pph) 変形・欠損 点検時','支柱本体(Pph) 変形・欠損 措置後','支柱本体(Pph) その他 点検時','支柱本体(Pph) その他 措置後','支柱本体(Pph) 点検箇所の健全性の診断','支柱継手部(Ppj) 対象有無','支柱継手部(Ppj) 点検状況','支柱継手部(Ppj) き裂 点検時','支柱継手部(Ppj) き裂 措置後','支柱継手部(Ppj) ゆるみ・脱落 点検時','支柱継手部(Ppj) ゆるみ・脱落 措置後','支柱継手部(Ppj) 破断 点検時','支柱継手部(Ppj) 破断 措置後','支柱継手部(Ppj) 腐食 点検時','支柱継手部(Ppj) 腐食 措置後','支柱継手部(Ppj) 変形・欠損 点検時','支柱継手部(Ppj) 変形・欠損 措置後','支柱継手部(Ppj) その他 点検時','支柱継手部(Ppj) その他 措置後','支柱継手部(Ppj) 点検箇所の健全性の診断','支柱内部(Ppi) 対象有無','支柱内部(Ppi) 点検状況','支柱内部(Ppi) 腐食 点検時','支柱内部(Ppi) 腐食 措置後','支柱内部(Ppi) 滞水 点検時','支柱内部(Ppi) 滞水 措置後','支柱内部(Ppi) その他 点検時','支柱内部(Ppi) その他 措置後','支柱内部(Ppi) 点検箇所の健全性の診断','リブ取付溶接部(Pbr) 対象有無','リブ取付溶接部(Pbr) 点検状況','リブ取付溶接部(Pbr) き裂 点検時','リブ取付溶接部(Pbr) き裂 措置後','リブ取付溶接部(Pbr) 腐食 点検時','リブ取付溶接部(Pbr) 腐食 措置後','リブ取付溶接部(Pbr) 変形・欠損 点検時','リブ取付溶接部(Pbr) 変形・欠損 措置後','リブ取付溶接部(Pbr) その他 点検時','リブ取付溶接部(Pbr) その他 措置後','リブ取付溶接部(Pbr) 点検箇所の健全性の診断','柱・ベースプレート溶接部(Pbp) 対象有無','柱・ベースプレート溶接部(Pbp) 点検状況','柱・ベースプレート溶接部(Pbp) き裂 点検時','柱・ベースプレート溶接部(Pbp) き裂 措置後','柱・ベースプレート溶接部(Pbp) 腐食 点検時','柱・ベースプレート溶接部(Pbp) 腐食 措置後','柱・ベースプレート溶接部(Pbp) 変形・欠損 点検時','柱・ベースプレート溶接部(Pbp) 変形・欠損 措置後','柱・ベースプレート溶接部(Pbp) その他 点検時','柱・ベースプレート溶接部(Pbp) その他 措置後','柱・ベースプレート溶接部(Pbp) 点検箇所の健全性の診断','路面境界部(GL-0)(Pgl-0) 対象有無','路面境界部(GL-0)(Pgl-0) 点検状況','路面境界部(GL-0)(Pgl-0) き裂 点検時','路面境界部(GL-0)(Pgl-0) き裂 措置後','路面境界部(GL-0)(Pgl-0) 腐食 点検時','路面境界部(GL-0)(Pgl-0) 腐食 措置後','路面境界部(GL-0)(Pgl-0) 変形・欠損 点検時','路面境界部(GL-0)(Pgl-0) 変形・欠損 措置後','路面境界部(GL-0)(Pgl-0) その他 点検時','路面境界部(GL-0)(Pgl-0) その他 措置後','路面境界部(GL-0)(Pgl-0) 点検箇所の健全性の診断','路面境界部(GL-40)(Pgl-40) 対象有無','路面境界部(GL-40)(Pgl-40) 点検状況','路面境界部(GL-40)(Pgl-40) き裂 点検時','路面境界部(GL-40)(Pgl-40) き裂 措置後','路面境界部(GL-40)(Pgl-40) 腐食 点検時','路面境界部(GL-40)(Pgl-40) 腐食 措置後','路面境界部(GL-40)(Pgl-40) 変形・欠損 点検時','路面境界部(GL-40)(Pgl-40) 変形・欠損 措置後','路面境界部(GL-40)(Pgl-40) その他 点検時','路面境界部(GL-40)(Pgl-40) その他 措置後','路面境界部(GL-40)(Pgl-40) 点検箇所の健全性の診断','柱・基礎境界部(Ppb) 対象有無','柱・基礎境界部(Ppb) 点検状況','柱・基礎境界部(Ppb) き裂 点検時','柱・基礎境界部(Ppb) き裂 措置後','柱・基礎境界部(Ppb) 腐食 点検時','柱・基礎境界部(Ppb) 腐食 措置後','柱・基礎境界部(Ppb) 変形・欠損 点検時','柱・基礎境界部(Ppb) 変形・欠損 措置後','柱・基礎境界部(Ppb) その他 点検時','柱・基礎境界部(Ppb) その他 措置後','柱・基礎境界部(Ppb) 点検箇所の健全性の診断','電気設備用開口部本体(Phh) 対象有無','電気設備用開口部本体(Phh) 点検状況','電気設備用開口部本体(Phh) き裂 点検時','電気設備用開口部本体(Phh) き裂 措置後','電気設備用開口部本体(Phh) 腐食 点検時','電気設備用開口部本体(Phh) 腐食 措置後','電気設備用開口部本体(Phh) 変形・欠損 点検時','電気設備用開口部本体(Phh) 変形・欠損 措置後','電気設備用開口部本体(Phh) その他 点検時','電気設備用開口部本体(Phh) その他 措置後','電気設備用開口部本体(Phh) 点検箇所の健全性の診断','電気設備用開口部ボルト(Phb) 対象有無','電気設備用開口部ボルト(Phb) 点検状況','電気設備用開口部ボルト(Phb) き裂 点検時','電気設備用開口部ボルト(Phb) き裂 措置後','電気設備用開口部ボルト(Phb) ゆるみ・脱落 点検時','電気設備用開口部ボルト(Phb) ゆるみ・脱落 措置後','電気設備用開口部ボルト(Phb) 破断 点検時','電気設備用開口部ボルト(Phb) 破断 措置後','電気設備用開口部ボルト(Phb) 腐食 点検時','電気設備用開口部ボルト(Phb) 腐食 措置後','電気設備用開口部ボルト(Phb) 変形・欠損 点検時','電気設備用開口部ボルト(Phb) 変形・欠損 措置後','電気設備用開口部ボルト(Phb) その他 点検時','電気設備用開口部ボルト(Phb) その他 措置後','電気設備用開口部ボルト(Phb) 点検箇所の健全性の診断','支柱 対策の要否','支柱 部材の健全性の診断','支柱 判定に至るまでの考え方 1.外見上から判断できる原因','支柱 判定に至るまでの考え方 2.(前回点検からの)進行性','支柱 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','支柱 判定に至るまでの考え方 4.想定される補修方法等','横梁本体(Cbh) 対象有無','横梁本体(Cbh) 点検状況','横梁本体(Cbh) き裂 点検時','横梁本体(Cbh) き裂 措置後','横梁本体(Cbh) 腐食 点検時','横梁本体(Cbh) 腐食 措置後','横梁本体(Cbh) 変形・欠損 点検時','横梁本体(Cbh) 変形・欠損 措置後','横梁本体(Cbh) その他 点検時','横梁本体(Cbh) その他 措置後','横梁本体(Cbh) 点検箇所の健全性の診断','横梁取付部(Cbi) 対象有無','横梁取付部(Cbi) 点検状況','横梁取付部(Cbi) き裂 点検時','横梁取付部(Cbi) き裂 措置後','横梁取付部(Cbi) ゆるみ・脱落 点検時','横梁取付部(Cbi) ゆるみ・脱落 措置後','横梁取付部(Cbi) 破断 点検時','横梁取付部(Cbi) 破断 措置後','横梁取付部(Cbi) 腐食 点検時','横梁取付部(Cbi) 腐食 措置後','横梁取付部(Cbi) 変形・欠損 点検時','横梁取付部(Cbi) 変形・欠損 措置後','横梁取付部(Cbi) その他 点検時','横梁取付部(Cbi) その他 措置後','横梁取付部(Cbi) 点検箇所の健全性の診断','横梁継手部(Cbj) 対象有無','横梁継手部(Cbj) 横梁継手部(Cbj) 点検状況','横梁継手部(Cbj) き裂 点検時','横梁継手部(Cbj) き裂 措置後','横梁継手部(Cbj) ゆるみ・脱落 点検時','横梁継手部(Cbj) ゆるみ・脱落 措置後','横梁継手部(Cbj) 破断 点検時','横梁継手部(Cbj) 破断 措置後','横梁継手部(Cbj) 腐食 点検時','横梁継手部(Cbj) 腐食 措置後','横梁継手部(Cbj) 変形・欠損 点検時','横梁継手部(Cbj) 変形・欠損 措置後','横梁継手部(Cbj) その他 点検時','横梁継手部(Cbj) その他 措置後','横梁継手部(Cbj) 点検箇所の健全性の診断','横梁仕口溶接部(Cbw) 対象有無','横梁仕口溶接部(Cbw) 点検状況','横梁仕口溶接部(Cbw) き裂 点検時','横梁仕口溶接部(Cbw) き裂 措置後','横梁仕口溶接部(Cbw) 腐食 点検時','横梁仕口溶接部(Cbw) 腐食 措置後','横梁仕口溶接部(Cbw) 変形・欠損 点検時','横梁仕口溶接部(Cbw) 変形・欠損 措置後','横梁仕口溶接部(Cbw) その他 点検時','横梁仕口溶接部(Cbw) その他 措置後','横梁仕口溶接部(Cbw) 点検箇所の健全性の診断','横梁 対策の要否','横梁 部材の健全性の診断','横梁 判定に至るまでの考え方 1.外見上から判断できる原因','横梁 判定に至るまでの考え方 2.(前回点検からの)進行性','横梁 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','横梁 判定に至るまでの考え方 4.想定される補修方法等','標識板(Srs) 対象有無','標識板(Srs) 点検状況','標識板(Srs) き裂 点検時','標識板(Srs) き裂 措置後','標識板(Srs) ゆるみ・脱落 点検時','標識板(Srs) ゆるみ・脱落 措置後','標識板(Srs) 破断 点検時','標識板(Srs) 破断 措置後','標識板(Srs) 腐食 点検時','標識板(Srs) 腐食 措置後','標識板(Srs) 変形・欠損 点検時','標識板(Srs) 変形・欠損 措置後','標識板(Srs) その他 点検時','標識板(Srs) その他 措置後','標識板(Srs) 点検箇所の健全性の診断','標識板取付部(Srs) 対象有無','標識板取付部(Srs) 点検状況','標識板取付部(Srs) き裂 点検時','標識板取付部(Srs) き裂 措置後','標識板取付部(Srs) ゆるみ・脱落 点検時','標識板取付部(Srs) ゆるみ・脱落 措置後','標識板取付部(Srs) 破断 点検時','標識板取付部(Srs) 破断 措置後','標識板取付部(Srs) 腐食 点検時','標識板取付部(Srs) 腐食 措置後','標識板取付部(Srs) 変形・欠損 点検時','標識板取付部(Srs) 変形・欠損 措置後','標識板取付部(Srs) その他 点検時','標識板取付部(Srs) その他 措置後','標識板取付部(Srs) 点検箇所の健全性の診断','標識板 対策の要否','標識板 部材の健全性の診断','標識板 判定に至るまでの考え方 1.外見上から判断できる原因','標識板 判定に至るまでの考え方 2.(前回点検からの)進行性','標識板 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','標識板 判定に至るまでの考え方 4.想定される補修方法等','基礎コンクリート部(Bbc) 対象有無','基礎コンクリート部(Bbc) 点検状況','基礎コンクリート部(Bbc) ひびわれ 点検時','基礎コンクリート部(Bbc) ひびわれ 措置後','基礎コンクリート部(Bbc) うき・剥離 点検時','基礎コンクリート部(Bbc) うき・剥離 措置後','基礎コンクリート部(Bbc) 滞水 点検時','基礎コンクリート部(Bbc) 滞水 措置後','基礎コンクリート部(Bbc) その他 点検時','基礎コンクリート部(Bbc) その他 措置後','基礎コンクリート部(Bbc) 点検箇所の健全性の診断','アンカーボルト・ナット(Bab) 対象有無','アンカーボルト・ナット(Bab) 点検状況','アンカーボルト・ナット(Bab) き裂 点検時','アンカーボルト・ナット(Bab) き裂 措置後','アンカーボルト・ナット(Bab) ゆるみ・脱落 点検時','アンカーボルト・ナット(Bab) ゆるみ・脱落 措置後','アンカーボルト・ナット(Bab) 破断 点検時','アンカーボルト・ナット(Bab) 破断 措置後','アンカーボルト・ナット(Bab) 腐食 点検時','アンカーボルト・ナット(Bab) 腐食 措置後','アンカーボルト・ナット(Bab) 変形・欠損 点検時','アンカーボルト・ナット(Bab) 変形・欠損 措置後','アンカーボルト・ナット(Bab) その他 点検時','アンカーボルト・ナット(Bab) その他 措置後','アンカーボルト・ナット(Bab) 点検箇所の健全性の診断','基礎 対策の要否','基礎 部材の健全性の診断','基礎 判定に至るまでの考え方 1.外見上から判断できる原因','基礎 判定に至るまでの考え方 2.(前回点検からの)進行性','基礎 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','基礎 判定に至るまでの考え方 4.想定される補修方法等','道路管理者の添架標識等(Srs) 対象有無','道路管理者の添架標識等(Srs) 点検状況','道路管理者の添架標識等(Srs) き裂 点検時','道路管理者の添架標識等(Srs) き裂 措置後','道路管理者の添架標識等(Srs) ゆるみ・脱落 点検時','道路管理者の添架標識等(Srs) ゆるみ・脱落 措置後','道路管理者の添架標識等(Srs) 破断 点検時','道路管理者の添架標識等(Srs) 破断 措置後','道路管理者の添架標識等(Srs) 腐食 点検時','道路管理者の添架標識等(Srs) 腐食 措置後','道路管理者の添架標識等(Srs) 変形・欠損 点検時','道路管理者の添架標識等(Srs) 変形・欠損 措置後','道路管理者の添架標識等(Srs) その他 点検時','道路管理者の添架標識等(Srs) その他 措置後','道路管理者の添架標識等(Srs) 点検箇所の健全性の診断','道路管理者の添架標識等取付部(Srs) 対象有無','道路管理者の添架標識等取付部(Srs) 点検状況','道路管理者の添架標識等取付部(Srs) き裂 点検時','道路管理者の添架標識等取付部(Srs) き裂 措置後','道路管理者の添架標識等取付部(Srs) ゆるみ・脱落 点検時','道路管理者の添架標識等取付部(Srs) ゆるみ・脱落 措置後','道路管理者の添架標識等取付部(Srs) 破断 点検時','道路管理者の添架標識等取付部(Srs) 破断 措置後','道路管理者の添架標識等取付部(Srs) 腐食 点検時','道路管理者の添架標識等取付部(Srs) 腐食 措置後','道路管理者の添架標識等取付部(Srs) 変形・欠損 点検時','道路管理者の添架標識等取付部(Srs) 変形・欠損 措置後','道路管理者の添架標識等取付部(Srs) その他 点検時','道路管理者の添架標識等取付部(Srs) その他 措置後','道路管理者の添架標識等取付部(Srs) 点検箇所の健全性の診断','その他1 対象有無','その他1 点検状況','その他1 き裂 点検時','その他1 き裂 措置後','その他1 ゆるみ・脱落 点検時','その他1 ゆるみ・脱落 措置後','その他1 破断 点検時','その他1 破断 措置後','その他1 腐食 点検時','その他1 腐食 措置後','その他1 変形・欠損 点検時','その他1 変形・欠損 措置後','その他1 ひびわれ 点検時','その他1 ひびわれ 措置後','その他1 うき・剥離 点検時','その他1 うき・剥離 措置後','その他1 滞水 点検時','その他1 滞水 措置後','その他1 その他 点検時','その他1 その他 措置後','その他1 点検箇所の健全性の診断','その他2 対象有無','その他2 点検状況','その他2 き裂 点検時','その他2 き裂 措置後','その他2 ゆるみ・脱落 点検時','その他2 ゆるみ・脱落 措置後','その他2 破断 点検時','その他2 破断 措置後','その他2 腐食 点検時','その他2 腐食 措置後','その他2 変形・欠損 点検時','その他2 変形・欠損 措置後','その他2 ひびわれ 点検時','その他2 ひびわれ 措置後','その他2 うき・剥離 点検時','その他2 うき・剥離 措置後','その他2 滞水 点検時','その他2 滞水 措置後','その他2 その他 点検時','その他2 その他 措置後','その他2 点検箇所の健全性の診断','その他3 対象有無','その他3 点検状況','その他3 き裂 点検時','その他3 き裂 措置後','その他3 ゆるみ・脱落 点検時','その他3 ゆるみ・脱落 措置後','その他3 破断 点検時','その他3 破断 措置後','その他3 腐食 点検時','その他3 腐食 措置後','その他3 変形・欠損 点検時','その他3 変形・欠損 措置後','その他3 ひびわれ 点検時','その他3 ひびわれ 措置後','その他3 うき・剥離 点検時','その他3 うき・剥離 措置後','その他3 滞水 点検時','その他3 滞水 措置後','その他3 その他 点検時','その他3 その他 措置後','その他3 点検箇所の健全性の診断','その他 対策の要否','その他 部材の健全性の診断','その他 判定に至るまでの考え方 1.外見上から判断できる原因','その他 判定に至るまでの考え方 2.(前回点検からの)進行性','その他 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','その他 判定に至るまでの考え方 4.想定される補修方法等','附属物毎の健全性の診断','表面処理','点検できなかった部位','点検できなかった理由','その他特記事項');
      } else if ($shisetsu_kbn == 2) {  // 道路情報提供装置
        $export_csv_title_sonsyou = array('支柱 判定区分','支柱 損傷の種類','支柱 備考','支柱 措置後の判定区分','支柱 措置後の損傷の種類','支柱 措置及び判定実施年月日','横梁 判定区分','横梁 損傷の種類','横梁 備考','横梁 措置後の判定区分','横梁 措置後の損傷の種類','横梁 措置及び判定実施年月日','道路情報板 判定区分','道路情報板 損傷の種類','道路情報板 備考','道路情報板 措置後の判定区分','道路情報板 措置後の損傷の種類','道路情報板 措置及び判定実施年月日','基礎 判定区分','基礎 損傷の種類','基礎 備考','基礎 措置後の判定区分','基礎 措置後の損傷の種類','基礎 措置及び判定実施年月日','その他 判定区分','その他 損傷の種類','その他 備考','その他 措置後の判定区分','その他 措置後の損傷の種類','その他 措置及び判定実施年月日','附属物毎の健全性の診断 判定区分','附属物毎の健全性の診断 所見等','附属物毎の健全性の診断 再判定区分','附属物毎の健全性の診断 再判定実施年月日','全景写真 設置年月','全景写真 道路幅員(m)','支柱本体(Pph) 対象有無','支柱本体(Pph) 点検状況','支柱本体(Pph) き裂 点検時','支柱本体(Pph) き裂 措置後','支柱本体(Pph) 腐食 点検時','支柱本体(Pph) 腐食 措置後','支柱本体(Pph) 変形・欠損 点検時','支柱本体(Pph) 変形・欠損 措置後','支柱本体(Pph) その他 点検時','支柱本体(Pph) その他 措置後','支柱本体(Pph) 点検箇所の健全性の診断','支柱継手部(Ppj) 対象有無','支柱継手部(Ppj) 点検状況','支柱継手部(Ppj) き裂 点検時','支柱継手部(Ppj) き裂 措置後','支柱継手部(Ppj) ゆるみ・脱落 点検時','支柱継手部(Ppj) ゆるみ・脱落 措置後','支柱継手部(Ppj) 破断 点検時','支柱継手部(Ppj) 破断 措置後','支柱継手部(Ppj) 腐食 点検時','支柱継手部(Ppj) 腐食 措置後','支柱継手部(Ppj) 変形・欠損 点検時','支柱継手部(Ppj) 変形・欠損 措置後','支柱継手部(Ppj) その他 点検時','支柱継手部(Ppj) その他 措置後','支柱継手部(Ppj) 点検箇所の健全性の診断','支柱内部(Ppi) 対象有無','支柱内部(Ppi) 点検状況','支柱内部(Ppi) 腐食 点検時','支柱内部(Ppi) 腐食 措置後','支柱内部(Ppi) 滞水 点検時','支柱内部(Ppi) 滞水 措置後','支柱内部(Ppi) その他 点検時','支柱内部(Ppi) その他 措置後','支柱内部(Ppi) 点検箇所の健全性の診断','リブ取付溶接部(Pbr) 対象有無','リブ取付溶接部(Pbr) 点検状況','リブ取付溶接部(Pbr) き裂 点検時','リブ取付溶接部(Pbr) き裂 措置後','リブ取付溶接部(Pbr) 腐食 点検時','リブ取付溶接部(Pbr) 腐食 措置後','リブ取付溶接部(Pbr) 変形・欠損 点検時','リブ取付溶接部(Pbr) 変形・欠損 措置後','リブ取付溶接部(Pbr) その他 点検時','リブ取付溶接部(Pbr) その他 措置後','リブ取付溶接部(Pbr) 点検箇所の健全性の診断','柱・ベースプレート溶接部(Pbp) 対象有無','柱・ベースプレート溶接部(Pbp) 点検状況','柱・ベースプレート溶接部(Pbp) き裂 点検時','柱・ベースプレート溶接部(Pbp) き裂 措置後','柱・ベースプレート溶接部(Pbp) 腐食 点検時','柱・ベースプレート溶接部(Pbp) 腐食 措置後','柱・ベースプレート溶接部(Pbp) 変形・欠損 点検時','柱・ベースプレート溶接部(Pbp) 変形・欠損 措置後','柱・ベースプレート溶接部(Pbp) その他 点検時','柱・ベースプレート溶接部(Pbp) その他 措置後','柱・ベースプレート溶接部(Pbp) 点検箇所の健全性の診断','路面境界部(GL-0)(Pgl-0) 対象有無','路面境界部(GL-0)(Pgl-0) 点検状況','路面境界部(GL-0)(Pgl-0) き裂 点検時','路面境界部(GL-0)(Pgl-0) き裂 措置後','路面境界部(GL-0)(Pgl-0) 腐食 点検時','路面境界部(GL-0)(Pgl-0) 腐食 措置後','路面境界部(GL-0)(Pgl-0) 変形・欠損 点検時','路面境界部(GL-0)(Pgl-0) 変形・欠損 措置後','路面境界部(GL-0)(Pgl-0) その他 点検時','路面境界部(GL-0)(Pgl-0) その他 措置後','路面境界部(GL-0)(Pgl-0) 点検箇所の健全性の診断','路面境界部(GL-40)(Pgl-40) 対象有無','路面境界部(GL-40)(Pgl-40) 点検状況','路面境界部(GL-40)(Pgl-40) き裂 点検時','路面境界部(GL-40)(Pgl-40) き裂 措置後','路面境界部(GL-40)(Pgl-40) 腐食 点検時','路面境界部(GL-40)(Pgl-40) 腐食 措置後','路面境界部(GL-40)(Pgl-40) 変形・欠損 点検時','路面境界部(GL-40)(Pgl-40) 変形・欠損 措置後','路面境界部(GL-40)(Pgl-40) その他 点検時','路面境界部(GL-40)(Pgl-40) その他 措置後','路面境界部(GL-40)(Pgl-40) 点検箇所の健全性の診断','柱・基礎境界部(Ppb) 対象有無','柱・基礎境界部(Ppb) 点検状況','柱・基礎境界部(Ppb) き裂 点検時','柱・基礎境界部(Ppb) き裂 措置後','柱・基礎境界部(Ppb) 腐食 点検時','柱・基礎境界部(Ppb) 腐食 措置後','柱・基礎境界部(Ppb) 変形・欠損 点検時','柱・基礎境界部(Ppb) 変形・欠損 措置後','柱・基礎境界部(Ppb) その他 点検時','柱・基礎境界部(Ppb) その他 措置後','柱・基礎境界部(Ppb) 点検箇所の健全性の診断','電気開口部本体(Phh) 対象有無','電気開口部本体(Phh) 点検状況','電気開口部本体(Phh) き裂 点検時','電気開口部本体(Phh) き裂 措置後','電気開口部本体(Phh) 腐食 点検時','電気開口部本体(Phh) 腐食 措置後','電気開口部本体(Phh) 変形・欠損 点検時','電気開口部本体(Phh) 変形・欠損 措置後','電気開口部本体(Phh) その他 点検時','電気開口部本体(Phh) その他 措置後','電気開口部本体(Phh) 点検箇所の健全性の診断','電気開口部ボルト(Phb) 対象有無','電気開口部ボルト(Phb) 点検状況','電気開口部ボルト(Phb) き裂 点検時','電気開口部ボルト(Phb) き裂 措置後','電気開口部ボルト(Phb) ゆるみ・脱落 点検時','電気開口部ボルト(Phb) ゆるみ・脱落 措置後','電気開口部ボルト(Phb) 破断 点検時','電気開口部ボルト(Phb) 破断 措置後','電気開口部ボルト(Phb) 腐食 点検時','電気開口部ボルト(Phb) 腐食 措置後','電気開口部ボルト(Phb) 変形・欠損 点検時','電気開口部ボルト(Phb) 変形・欠損 措置後','電気開口部ボルト(Phb) その他 点検時','電気開口部ボルト(Phb) その他 措置後','電気開口部ボルト(Phb) 点検箇所の健全性の診断','支柱 対策の要否','支柱 部材の健全性の診断','支柱 判定に至るまでの考え方 1.外見上から判断できる原因','支柱 判定に至るまでの考え方 2.(前回点検からの)進行性','支柱 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','支柱 判定に至るまでの考え方 4.想定される補修方法等','横梁本体(Cbh) 対象有無','横梁本体(Cbh) 点検状況','横梁本体(Cbh) き裂 点検時','横梁本体(Cbh) き裂 措置後','横梁本体(Cbh) 腐食 点検時','横梁本体(Cbh) 腐食 措置後','横梁本体(Cbh) 変形・欠損 点検時','横梁本体(Cbh) 変形・欠損 措置後','横梁本体(Cbh) その他 点検時','横梁本体(Cbh) その他 措置後','横梁本体(Cbh) 点検箇所の健全性の診断','横梁取付部(Cbi) 対象有無','横梁取付部(Cbi) 点検状況','横梁取付部(Cbi) き裂 点検時','横梁取付部(Cbi) き裂 措置後','横梁取付部(Cbi) ゆるみ・脱落 点検時','横梁取付部(Cbi) ゆるみ・脱落 措置後','横梁取付部(Cbi) 破断 点検時','横梁取付部(Cbi) 破断 措置後','横梁取付部(Cbi) 腐食 点検時','横梁取付部(Cbi) 腐食 措置後','横梁取付部(Cbi) 変形・欠損 点検時','横梁取付部(Cbi) 変形・欠損 措置後','横梁取付部(Cbi) その他 点検時','横梁取付部(Cbi) その他 措置後','横梁取付部(Cbi) 点検箇所の健全性の診断','横梁継手部(Cbj) 対象有無','横梁継手部(Cbj) 横梁継手部(Cbj) 点検状況','横梁継手部(Cbj) き裂 点検時','横梁継手部(Cbj) き裂 措置後','横梁継手部(Cbj) ゆるみ・脱落 点検時','横梁継手部(Cbj) ゆるみ・脱落 措置後','横梁継手部(Cbj) 破断 点検時','横梁継手部(Cbj) 破断 措置後','横梁継手部(Cbj) 腐食 点検時','横梁継手部(Cbj) 腐食 措置後','横梁継手部(Cbj) 変形・欠損 点検時','横梁継手部(Cbj) 変形・欠損 措置後','横梁継手部(Cbj) その他 点検時','横梁継手部(Cbj) その他 措置後','横梁継手部(Cbj) 点検箇所の健全性の診断','横梁仕口溶接部(Cbw) 対象有無','横梁仕口溶接部(Cbw) 点検状況','横梁仕口溶接部(Cbw) き裂 点検時','横梁仕口溶接部(Cbw) き裂 措置後','横梁仕口溶接部(Cbw) 腐食 点検時','横梁仕口溶接部(Cbw) 腐食 措置後','横梁仕口溶接部(Cbw) 変形・欠損 点検時','横梁仕口溶接部(Cbw) 変形・欠損 措置後','横梁仕口溶接部(Cbw) その他 点検時','横梁仕口溶接部(Cbw) その他 措置後','横梁仕口溶接部(Cbw) 点検箇所の健全性の診断','横梁 対策の要否','横梁 部材の健全性の診断','横梁 判定に至るまでの考え方 1.外見上から判断できる原因','横梁 判定に至るまでの考え方 2.(前回点検からの)進行性','横梁 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','横梁 判定に至るまでの考え方 4.想定される補修方法等','道路情報板(Srs) 対象有無','道路情報板(Srs) 点検状況','道路情報板(Srs) き裂 点検時','道路情報板(Srs) き裂 措置後','道路情報板(Srs) ゆるみ・脱落 点検時','道路情報板(Srs) ゆるみ・脱落 措置後','道路情報板(Srs) 破断 点検時','道路情報板(Srs) 破断 措置後','道路情報板(Srs) 腐食 点検時','道路情報板(Srs) 腐食 措置後','道路情報板(Srs) 変形・欠損 点検時','道路情報板(Srs) 変形・欠損 措置後','道路情報板(Srs) その他 点検時','道路情報板(Srs) その他 措置後','道路情報板(Srs) 点検箇所の健全性の診断','道路情報取付部(Srs) 対象有無','道路情報取付部(Srs) 点検状況','道路情報取付部(Srs) き裂 点検時','道路情報取付部(Srs) き裂 措置後','道路情報取付部(Srs) ゆるみ・脱落 点検時','道路情報取付部(Srs) ゆるみ・脱落 措置後','道路情報取付部(Srs) 破断 点検時','道路情報取付部(Srs) 破断 措置後','道路情報取付部(Srs) 腐食 点検時','道路情報取付部(Srs) 腐食 措置後','道路情報取付部(Srs) 変形・欠損 点検時','道路情報取付部(Srs) 変形・欠損 措置後','道路情報取付部(Srs) その他 点検時','道路情報取付部(Srs) その他 措置後','道路情報取付部(Srs) 点検箇所の健全性の診断','道路情報板 対策の要否','道路情報板 部材の健全性の診断','道路情報板 判定に至るまでの考え方 1.外見上から判断できる原因','道路情報板 判定に至るまでの考え方 2.(前回点検からの)進行性','道路情報板 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','道路情報板 判定に至るまでの考え方 4.想定される補修方法等','基礎コンクリート部(Bbc) 対象有無','基礎コンクリート部(Bbc) 点検状況','基礎コンクリート部(Bbc) ひびわれ 点検時','基礎コンクリート部(Bbc) ひびわれ 措置後','基礎コンクリート部(Bbc) うき・剥離 点検時','基礎コンクリート部(Bbc) うき・剥離 措置後','基礎コンクリート部(Bbc) 滞水 点検時','基礎コンクリート部(Bbc) 滞水 措置後','基礎コンクリート部(Bbc) その他 点検時','基礎コンクリート部(Bbc) その他 措置後','基礎コンクリート部(Bbc) 点検箇所の健全性の診断','アンカーボルト・ナット(Bab) 対象有無','アンカーボルト・ナット(Bab) 点検状況','アンカーボルト・ナット(Bab) き裂 点検時','アンカーボルト・ナット(Bab) き裂 措置後','アンカーボルト・ナット(Bab) ゆるみ・脱落 点検時','アンカーボルト・ナット(Bab) ゆるみ・脱落 措置後','アンカーボルト・ナット(Bab) 破断 点検時','アンカーボルト・ナット(Bab) 破断 措置後','アンカーボルト・ナット(Bab) 腐食 点検時','アンカーボルト・ナット(Bab) 腐食 措置後','アンカーボルト・ナット(Bab) 変形・欠損 点検時','アンカーボルト・ナット(Bab) 変形・欠損 措置後','アンカーボルト・ナット(Bab) その他 点検時','アンカーボルト・ナット(Bab) その他 措置後','アンカーボルト・ナット(Bab) 点検箇所の健全性の診断','基礎 対策の要否','基礎 部材の健全性の診断','基礎 判定に至るまでの考え方 1.外見上から判断できる原因','基礎 判定に至るまでの考え方 2.(前回点検からの)進行性','基礎 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','基礎 判定に至るまでの考え方 4.想定される補修方法等','道路管理者の添架標識等(Srs) 対象有無','道路管理者の添架標識等(Srs) 点検状況','道路管理者の添架標識等(Srs) き裂 点検時','道路管理者の添架標識等(Srs) き裂 措置後','道路管理者の添架標識等(Srs) ゆるみ・脱落 点検時','道路管理者の添架標識等(Srs) ゆるみ・脱落 措置後','道路管理者の添架標識等(Srs) 破断 点検時','道路管理者の添架標識等(Srs) 破断 措置後','道路管理者の添架標識等(Srs) 腐食 点検時','道路管理者の添架標識等(Srs) 腐食 措置後','道路管理者の添架標識等(Srs) 変形・欠損 点検時','道路管理者の添架標識等(Srs) 変形・欠損 措置後','道路管理者の添架標識等(Srs) その他 点検時','道路管理者の添架標識等(Srs) その他 措置後','道路管理者の添架標識等(Srs) 点検箇所の健全性の診断','道路管理者の添架物標識等取付部(Srs) 対象有無','道路管理者の添架物標識等取付部(Srs) 点検状況','道路管理者の添架物標識等取付部(Srs) き裂 点検時','道路管理者の添架物標識等取付部(Srs) き裂 措置後','道路管理者の添架物標識等取付部(Srs) ゆるみ・脱落 点検時','道路管理者の添架物標識等取付部(Srs) ゆるみ・脱落 措置後','道路管理者の添架物標識等取付部(Srs) 破断 点検時','道路管理者の添架物標識等取付部(Srs) 破断 措置後','道路管理者の添架物標識等取付部(Srs) 腐食 点検時','道路管理者の添架物標識等取付部(Srs) 腐食 措置後','道路管理者の添架物標識等取付部(Srs) 変形・欠損 点検時','道路管理者の添架物標識等取付部(Srs) 変形・欠損 措置後','道路管理者の添架物標識等取付部(Srs) その他 点検時','道路管理者の添架物標識等取付部(Srs) その他 措置後','道路管理者の添架物標識等取付部(Srs) 点検箇所の健全性の診断','管理用のステップ・タラップ等 対象有無','管理用のステップ・タラップ等 点検状況','管理用のステップ・タラップ等 き裂 点検時','管理用のステップ・タラップ等 き裂 措置後','管理用のステップ・タラップ等 ゆるみ・脱落 点検時','管理用のステップ・タラップ等 ゆるみ・脱落 措置後','管理用のステップ・タラップ等 破断 点検時','管理用のステップ・タラップ等 破断 措置後','管理用のステップ・タラップ等 腐食 点検時','管理用のステップ・タラップ等 腐食 措置後','管理用のステップ・タラップ等 変形・欠損 点検時','管理用のステップ・タラップ等 変形・欠損 措置後','管理用のステップ・タラップ等 その他 点検時','管理用のステップ・タラップ等 その他 措置後','管理用のステップ・タラップ等 点検箇所の健全性の診断','その他1 対象有無','その他1 点検状況','その他1 き裂 点検時','その他1 き裂 措置後','その他1 ゆるみ・脱落 点検時','その他1 ゆるみ・脱落 措置後','その他1 破断 点検時','その他1 破断 措置後','その他1 腐食 点検時','その他1 腐食 措置後','その他1 変形・欠損 点検時','その他1 変形・欠損 措置後','その他1 ひびわれ 点検時','その他1 ひびわれ 措置後','その他1 うき・剥離 点検時','その他1 うき・剥離 措置後','その他1 滞水 点検時','その他1 滞水 措置後','その他1 その他 点検時','その他1 その他 措置後','その他1 点検箇所の健全性の診断','その他2 対象有無','その他2 点検状況','その他2 き裂 点検時','その他2 き裂 措置後','その他2 ゆるみ・脱落 点検時','その他2 ゆるみ・脱落 措置後','その他2 破断 点検時','その他2 破断 措置後','その他2 腐食 点検時','その他2 腐食 措置後','その他2 変形・欠損 点検時','その他2 変形・欠損 措置後','その他2 ひびわれ 点検時','その他2 ひびわれ 措置後','その他2 うき・剥離 点検時','その他2 うき・剥離 措置後','その他2 滞水 点検時','その他2 滞水 措置後','その他2 その他 点検時','その他2 その他 措置後','その他2 点検箇所の健全性の診断','その他 対策の要否','その他 部材の健全性の診断','その他 判定に至るまでの考え方 1.外見上から判断できる原因','その他 判定に至るまでの考え方 2.(前回点検からの)進行性','その他 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','その他 判定に至るまでの考え方 4.想定される補修方法等','附属物毎の健全性の診断','表面処理','点検できなかった部位','点検できなかった理由','その他特記事項');
      } else if ($shisetsu_kbn == 3) {  // 道路照明装置
        $export_csv_title_sonsyou = array('支柱 判定区分','支柱 損傷の種類','支柱 備考','支柱 措置後の判定区分','支柱 措置後の損傷の種類','支柱 措置及び判定実施年月日','灯具 判定区分','灯具 損傷の種類','灯具 備考','灯具 措置後の判定区分','灯具 措置後の損傷の種類','灯具 措置及び判定実施年月日','基礎 判定区分','基礎 損傷の種類','基礎 備考','基礎 措置後の判定区分','基礎 措置後の損傷の種類','基礎 措置及び判定実施年月日','その他 判定区分','その他 損傷の種類','その他 備考','その他 措置後の判定区分','その他 措置後の損傷の種類','その他 措置及び判定実施年月日','附属物毎の健全性の診断 判定区分','附属物毎の健全性の診断 所見等','附属物毎の健全性の診断 再判定区分','附属物毎の健全性の診断 再判定実施年月日','全景写真 設置年月','全景写真 道路幅員(m)','支柱本体(Pph) 対象有無','支柱本体(Pph) 点検状況','支柱本体(Pph) き裂 点検時','支柱本体(Pph) き裂 措置後','支柱本体(Pph) 腐食 点検時','支柱本体(Pph) 腐食 措置後','支柱本体(Pph) 変形・欠損 点検時','支柱本体(Pph) 変形・欠損 措置後','支柱本体(Pph) その他 点検時','支柱本体(Pph) その他 措置後','支柱本体(Pph) 点検箇所の健全性の診断','支柱内部(Ppi) 対象有無','支柱内部(Ppi) 点検状況','支柱内部(Ppi) 腐食 点検時','支柱内部(Ppi) 腐食 措置後','支柱内部(Ppi) 滞水 点検時','支柱内部(Ppi) 滞水 措置後','支柱内部(Ppi) その他 点検時','支柱内部(Ppi) その他 措置後','支柱内部(Ppi) 点検箇所の健全性の診断','支柱分岐部(Y型のみ)(Ppd) 対象有無','支柱分岐部(Y型のみ)(Ppd) 点検状況','支柱分岐部(Y型のみ)(Ppd) き裂 点検時','支柱分岐部(Y型のみ)(Ppd) き裂 措置後','支柱分岐部(Y型のみ)(Ppd) 腐食 点検時','支柱分岐部(Y型のみ)(Ppd) 腐食 措置後','支柱分岐部(Y型のみ)(Ppd) 変形・欠損 点検時','支柱分岐部(Y型のみ)(Ppd) 変形・欠損 措置後','支柱分岐部(Y型のみ)(Ppd) その他 点検時','支柱分岐部(Y型のみ)(Ppd) その他 措置後','支柱分岐部(Y型のみ)(Ppd) 点検箇所の健全性の診断','支柱継手部(Ppj) 対象有無','支柱継手部(Ppj) 点検状況','支柱継手部(Ppj) き裂 点検時','支柱継手部(Ppj) き裂 措置後','支柱継手部(Ppj) ゆるみ・脱落 点検時','支柱継手部(Ppj) ゆるみ・脱落 措置後','支柱継手部(Ppj) 破断 点検時','支柱継手部(Ppj) 破断 措置後','支柱継手部(Ppj) 腐食 点検時','支柱継手部(Ppj) 腐食 措置後','支柱継手部(Ppj) 変形・欠損 点検時','支柱継手部(Ppj) 変形・欠損 措置後','支柱継手部(Ppj) その他 点検時','支柱継手部(Ppj) その他 措置後','支柱継手部(Ppj) 点検箇所の健全性の診断','配線部分(Xwi) 対象有無','配線部分(Xwi) 点検状況','配線部分(Xwi) き裂 点検時','配線部分(Xwi) き裂 措置後','配線部分(Xwi) 腐食 点検時','配線部分(Xwi) 腐食 措置後','配線部分(Xwi) 変形・欠損 点検時','配線部分(Xwi) 変形・欠損 措置後','配線部分(Xwi) その他 点検時','配線部分(Xwi) その他 措置後','配線部分(Xwi) 点検箇所の健全性の診断','バンド部(Xbn) 対象有無','バンド部(Xbn) 点検状況','バンド部(Xbn) き裂 点検時','バンド部(Xbn) き裂 措置後','バンド部(Xbn) ゆるみ・脱落 点検時','バンド部(Xbn) ゆるみ・脱落 措置後','バンド部(Xbn) 破断 点検時','バンド部(Xbn) 破断 措置後','バンド部(Xbn) 腐食 点検時','バンド部(Xbn) 腐食 措置後','バンド部(Xbn) 変形・欠損 点検時','バンド部(Xbn) 変形・欠損 措置後','バンド部(Xbn) その他 点検時','バンド部(Xbn) その他 措置後','バンド部(Xbn) 点検箇所の健全性の診断','路面境界部(GL-0)(Pgl-0) 対象有無','路面境界部(GL-0)(Pgl-0) 点検状況','路面境界部(GL-0)(Pgl-0) き裂 点検時','路面境界部(GL-0)(Pgl-0) き裂 措置後','路面境界部(GL-0)(Pgl-0) 腐食 点検時','路面境界部(GL-0)(Pgl-0) 腐食 措置後','路面境界部(GL-0)(Pgl-0) 変形・欠損 点検時','路面境界部(GL-0)(Pgl-0) 変形・欠損 措置後','路面境界部(GL-0)(Pgl-0) その他 点検時','路面境界部(GL-0)(Pgl-0) その他 措置後','路面境界部(GL-0)(Pgl-0) 点検箇所の健全性の診断','路面境界部(GL-40)(Pgl-40) 対象有無','路面境界部(GL-40)(Pgl-40) 点検状況','路面境界部(GL-40)(Pgl-40) き裂 点検時','路面境界部(GL-40)(Pgl-40) き裂 措置後','路面境界部(GL-40)(Pgl-40) 腐食 点検時','路面境界部(GL-40)(Pgl-40) 腐食 措置後','路面境界部(GL-40)(Pgl-40) 変形・欠損 点検時','路面境界部(GL-40)(Pgl-40) 変形・欠損 措置後','路面境界部(GL-40)(Pgl-40) その他 点検時','路面境界部(GL-40)(Pgl-40) その他 措置後','路面境界部(GL-40)(Pgl-40) 点検箇所の健全性の診断','柱・基礎境界部(Ppb) 対象有無','柱・基礎境界部(Ppb) 点検状況','柱・基礎境界部(Ppb) き裂 点検時','柱・基礎境界部(Ppb) き裂 措置後','柱・基礎境界部(Ppb) 腐食 点検時','柱・基礎境界部(Ppb) 腐食 措置後','柱・基礎境界部(Ppb) 変形・欠損 点検時','柱・基礎境界部(Ppb) 変形・欠損 措置後','柱・基礎境界部(Ppb) その他 点検時','柱・基礎境界部(Ppb) その他 措置後','柱・基礎境界部(Ppb) 点検箇所の健全性の診断','リブ取付溶接部(Pbr) 対象有無','リブ取付溶接部(Pbr) 点検状況','リブ取付溶接部(Pbr) き裂 点検時','リブ取付溶接部(Pbr) き裂 措置後','リブ取付溶接部(Pbr) 腐食 点検時','リブ取付溶接部(Pbr) 腐食 措置後','リブ取付溶接部(Pbr) 変形・欠損 点検時','リブ取付溶接部(Pbr) 変形・欠損 措置後','リブ取付溶接部(Pbr) その他 点検時','リブ取付溶接部(Pbr) その他 措置後','リブ取付溶接部(Pbr) 点検箇所の健全性の診断','柱・ベースプレート溶接部(Pbp) 対象有無','柱・ベースプレート溶接部(Pbp) 点検状況','柱・ベースプレート溶接部(Pbp) き裂 点検時','柱・ベースプレート溶接部(Pbp) き裂 措置後','柱・ベースプレート溶接部(Pbp) 腐食 点検時','柱・ベースプレート溶接部(Pbp) 腐食 措置後','柱・ベースプレート溶接部(Pbp) 変形・欠損 点検時','柱・ベースプレート溶接部(Pbp) 変形・欠損 措置後','柱・ベースプレート溶接部(Pbp) その他 点検時','柱・ベースプレート溶接部(Pbp) その他 措置後','柱・ベースプレート溶接部(Pbp) 点検箇所の健全性の診断','電気設備用開口部本体(Phh) 対象有無','電気設備用開口部本体(Phh) 点検状況','電気設備用開口部本体(Phh) き裂 点検時','電気設備用開口部本体(Phh) き裂 措置後','電気設備用開口部本体(Phh) 腐食 点検時','電気設備用開口部本体(Phh) 腐食 措置後','電気設備用開口部本体(Phh) 変形・欠損 点検時','電気設備用開口部本体(Phh) 変形・欠損 措置後','電気設備用開口部本体(Phh) その他 点検時','電気設備用開口部本体(Phh) その他 措置後','電気設備用開口部本体(Phh) 点検箇所の健全性の診断','電気開口部ボルト(Phb) 対象有無','電気開口部ボルト(Phb) 点検状況','電気開口部ボルト(Phb) き裂 点検時','電気開口部ボルト(Phb) き裂 措置後','電気開口部ボルト(Phb) ゆるみ・脱落 点検時','電気開口部ボルト(Phb) ゆるみ・脱落 措置後','電気開口部ボルト(Phb) 破断 点検時','電気開口部ボルト(Phb) 破断 措置後','電気開口部ボルト(Phb) 腐食 点検時','電気開口部ボルト(Phb) 腐食 措置後','電気開口部ボルト(Phb) 変形・欠損 点検時','電気開口部ボルト(Phb) 変形・欠損 措置後','電気開口部ボルト(Phb) その他 点検時','電気開口部ボルト(Phb) その他 措置後','電気開口部ボルト(Phb) 点検箇所の健全性の診断','支柱 対策の要否','支柱 部材の健全性の診断','支柱 判定に至るまでの考え方 1.外見上から判断できる原因','支柱 判定に至るまでの考え方 2.(前回点検からの)進行性','支柱 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','支柱 判定に至るまでの考え方 4.想定される補修方法等','灯具(Sli) 対象有無','灯具(Sli) 点検状況','灯具(Sli) き裂 点検時','灯具(Sli) き裂 措置後','灯具(Sli) ゆるみ・脱落 点検時','灯具(Sli) ゆるみ・脱落 措置後','灯具(Sli) 破断 点検時','灯具(Sli) 破断 措置後','灯具(Sli) 腐食 点検時','灯具(Sli) 腐食 措置後','灯具(Sli) 変形・欠損 点検時','灯具(Sli) 変形・欠損 措置後','灯具(Sli) その他 点検時','灯具(Sli) その他 措置後','灯具(Sli) 点検箇所の健全性の診断','灯具取付部(Sli) 対象有無','灯具取付部(Sli) 点検状況','灯具取付部(Sli) き裂 点検時','灯具取付部(Sli) き裂 措置後','灯具取付部(Sli) ゆるみ・脱落 点検時','灯具取付部(Sli) ゆるみ・脱落 措置後','灯具取付部(Sli) 破断 点検時','灯具取付部(Sli) 破断 措置後','灯具取付部(Sli) 腐食 点検時','灯具取付部(Sli) 腐食 措置後','灯具取付部(Sli) 変形・欠損 点検時','灯具取付部(Sli) 変形・欠損 措置後','灯具取付部(Sli) その他 点検時','灯具取付部(Sli) その他 措置後','灯具取付部(Sli) 点検箇所の健全性の診断','灯具 対策の要否','灯具 部材の健全性の診断','灯具 判定に至るまでの考え方 1.外見上から判断できる原因','灯具 判定に至るまでの考え方 2.(前回点検からの)進行性','灯具 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','灯具 判定に至るまでの考え方 4.想定される補修方法等','基礎コンクリート部(Bbc) 対象有無','基礎コンクリート部(Bbc) 点検状況','基礎コンクリート部(Bbc) ひびわれ 点検時','基礎コンクリート部(Bbc) ひびわれ 措置後','基礎コンクリート部(Bbc) うき・剥離 点検時','基礎コンクリート部(Bbc) うき・剥離 措置後','基礎コンクリート部(Bbc) 滞水 点検時','基礎コンクリート部(Bbc) 滞水 措置後','基礎コンクリート部(Bbc) その他 点検時','基礎コンクリート部(Bbc) その他 措置後','基礎コンクリート部(Bbc) 点検箇所の健全性の診断','アンカーボルト・ナット(Bab) 対象有無','アンカーボルト・ナット(Bab) 点検状況','アンカーボルト・ナット(Bab) き裂 点検時','アンカーボルト・ナット(Bab) き裂 措置後','アンカーボルト・ナット(Bab) ゆるみ・脱落 点検時','アンカーボルト・ナット(Bab) ゆるみ・脱落 措置後','アンカーボルト・ナット(Bab) 破断 点検時','アンカーボルト・ナット(Bab) 破断 措置後','アンカーボルト・ナット(Bab) 腐食 点検時','アンカーボルト・ナット(Bab) 腐食 措置後','アンカーボルト・ナット(Bab) 変形・欠損 点検時','アンカーボルト・ナット(Bab) 変形・欠損 措置後','アンカーボルト・ナット(Bab) その他 点検時','アンカーボルト・ナット(Bab) その他 措置後','アンカーボルト・ナット(Bab) 点検箇所の健全性の診断','基礎 対策の要否','基礎 部材の健全性の診断','基礎 判定に至るまでの考え方 1.外見上から判断できる原因','基礎 判定に至るまでの考え方 2.(前回点検からの)進行性','基礎 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','基礎 判定に至るまでの考え方 4.想定される補修方法等','道路管理者の添架標識等(Srs) 対象有無','道路管理者の添架標識等(Srs) 点検状況','道路管理者の添架標識等(Srs) き裂 点検時','道路管理者の添架標識等(Srs) き裂 措置後','道路管理者の添架標識等(Srs) ゆるみ・脱落 点検時','道路管理者の添架標識等(Srs) ゆるみ・脱落 措置後','道路管理者の添架標識等(Srs) 破断 点検時','道路管理者の添架標識等(Srs) 破断 措置後','道路管理者の添架標識等(Srs) 腐食 点検時','道路管理者の添架標識等(Srs) 腐食 措置後','道路管理者の添架標識等(Srs) 変形・欠損 点検時','道路管理者の添架標識等(Srs) 変形・欠損 措置後','道路管理者の添架標識等(Srs) その他 点検時','道路管理者の添架標識等(Srs) その他 措置後','道路管理者の添架標識等(Srs) 点検箇所の健全性の診断','道路管理者の添架物標識等取付部(Srs) 対象有無','道路管理者の添架物標識等取付部(Srs) 点検状況','道路管理者の添架物標識等取付部(Srs) き裂 点検時','道路管理者の添架物標識等取付部(Srs) き裂 措置後','道路管理者の添架物標識等取付部(Srs) ゆるみ・脱落 点検時','道路管理者の添架物標識等取付部(Srs) ゆるみ・脱落 措置後','道路管理者の添架物標識等取付部(Srs) 破断 点検時','道路管理者の添架物標識等取付部(Srs) 破断 措置後','道路管理者の添架物標識等取付部(Srs) 腐食 点検時','道路管理者の添架物標識等取付部(Srs) 腐食 措置後','道路管理者の添架物標識等取付部(Srs) 変形・欠損 点検時','道路管理者の添架物標識等取付部(Srs) 変形・欠損 措置後','道路管理者の添架物標識等取付部(Srs) その他 点検時','道路管理者の添架物標識等取付部(Srs) その他 措置後','道路管理者の添架物標識等取付部(Srs) 点検箇所の健全性の診断','その他1 対象有無','その他1 点検状況','その他1 き裂 点検時','その他1 き裂 措置後','その他1 ゆるみ・脱落 点検時','その他1 ゆるみ・脱落 措置後','その他1 破断 点検時','その他1 破断 措置後','その他1 腐食 点検時','その他1 腐食 措置後','その他1 変形・欠損 点検時','その他1 変形・欠損 措置後','その他1 ひびわれ 点検時','その他1 ひびわれ 措置後','その他1 うき・剥離 点検時','その他1 うき・剥離 措置後','その他1 滞水 点検時','その他1 滞水 措置後','その他1 その他 点検時','その他1 その他 措置後','その他1 点検箇所の健全性の診断','その他2 対象有無','その他2 点検状況','その他2 き裂 点検時','その他2 き裂 措置後','その他2 ゆるみ・脱落 点検時','その他2 ゆるみ・脱落 措置後','その他2 破断 点検時','その他2 破断 措置後','その他2 腐食 点検時','その他2 腐食 措置後','その他2 変形・欠損 点検時','その他2 変形・欠損 措置後','その他2 ひびわれ 点検時','その他2 ひびわれ 措置後','その他2 うき・剥離 点検時','その他2 うき・剥離 措置後','その他2 滞水 点検時','その他2 滞水 措置後','その他2 その他 点検時','その他2 その他 措置後','その他2 点検箇所の健全性の診断','その他3 対象有無','その他3 点検状況','その他3 き裂 点検時','その他3 き裂 措置後','その他3 ゆるみ・脱落 点検時','その他3 ゆるみ・脱落 措置後','その他3 破断 点検時','その他3 破断 措置後','その他3 腐食 点検時','その他3 腐食 措置後','その他3 変形・欠損 点検時','その他3 変形・欠損 措置後','その他3 ひびわれ 点検時','その他3 ひびわれ 措置後','その他3 うき・剥離 点検時','その他3 うき・剥離 措置後','その他3 滞水 点検時','その他3 滞水 措置後','その他3 その他 点検時','その他3 その他 措置後','その他3 点検箇所の健全性の診断','その他 対策の要否','その他 部材の健全性の診断','その他 判定に至るまでの考え方 1.外見上から判断できる原因','その他 判定に至るまでの考え方 2.(前回点検からの)進行性','その他 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','その他 判定に至るまでの考え方 4.想定される補修方法等','附属物毎の健全性の診断','表面処理','点検できなかった部位','点検できなかった理由','その他特記事項');
      } else if ($shisetsu_kbn == 4) {  // 防雪柵
        $export_csv_title_sonsyou = array('支柱 判定区分','支柱 損傷の種類','支柱 備考','支柱 措置後の判定区分','支柱 措置後の損傷の種類','支柱 措置及び判定実施年月日','横材 判定区分','横材 損傷の種類','横材 備考','横材 措置後の判定区分','横材 措置後の損傷の種類','横材 措置及び判定実施年月日','防雪板 判定区分','防雪板 損傷の種類','防雪板 備考','防雪板 措置後の判定区分','防雪板 措置後の損傷の種類','防雪板 措置及び判定実施年月日','基礎 判定区分','基礎 損傷の種類','基礎 備考','基礎 措置後の判定区分','基礎 措置後の損傷の種類','基礎 措置及び判定実施年月日','その他 判定区分','その他 損傷の種類','その他 備考','その他 措置後の判定区分','その他 措置後の損傷の種類','その他 措置及び判定実施年月日','附属物毎の健全性の診断 判定区分','附属物毎の健全性の診断 所見等','附属物毎の健全性の診断 再判定区分','附属物毎の健全性の診断 再判定実施年月日','全景写真 設置年月','全景写真 道路幅員(m)','支柱本体(Pph) 対象有無','支柱本体(Pph) 点検状況','支柱本体(Pph) き裂 点検時','支柱本体(Pph) き裂 措置後','支柱本体(Pph) 破断 点検時','支柱本体(Pph) 破断 措置後','支柱本体(Pph) 腐食 点検時','支柱本体(Pph) 腐食 措置後','支柱本体(Pph) 変形・欠損 点検時','支柱本体(Pph) 変形・欠損 措置後','支柱本体(Pph) その他 点検時','支柱本体(Pph) その他 措置後','支柱本体(Pph) 点検箇所の健全性の診断','支柱継手部(Ppj) 対象有無','支柱継手部(Ppj) 点検状況','支柱継手部(Ppj) き裂 点検時','支柱継手部(Ppj) き裂 措置後','支柱継手部(Ppj) ゆるみ・脱落 点検時','支柱継手部(Ppj) ゆるみ・脱落 措置後','支柱継手部(Ppj) 破断 点検時','支柱継手部(Ppj) 破断 措置後','支柱継手部(Ppj) 腐食 点検時','支柱継手部(Ppj) 腐食 措置後','支柱継手部(Ppj) 変形・欠損 点検時','支柱継手部(Ppj) 変形・欠損 措置後','支柱継手部(Ppj) その他 点検時','支柱継手部(Ppj) その他 措置後','支柱継手部(Ppj) 点検箇所の健全性の診断','リブ取付溶接部(Pbr) 対象有無','リブ取付溶接部(Pbr) 点検状況','リブ取付溶接部(Pbr) き裂 点検時','リブ取付溶接部(Pbr) き裂 措置後','リブ取付溶接部(Pbr) 腐食 点検時','リブ取付溶接部(Pbr) 腐食 措置後','リブ取付溶接部(Pbr) 変形・欠損 点検時','リブ取付溶接部(Pbr) 変形・欠損 措置後','リブ取付溶接部(Pbr) その他 点検時','リブ取付溶接部(Pbr) その他 措置後','リブ取付溶接部(Pbr) 点検箇所の健全性の診断','柱・ベースプレート溶接部(Pbp) 対象有無','柱・ベースプレート溶接部(Pbp) 点検状況','柱・ベースプレート溶接部(Pbp) き裂 点検時','柱・ベースプレート溶接部(Pbp) き裂 措置後','柱・ベースプレート溶接部(Pbp) 腐食 点検時','柱・ベースプレート溶接部(Pbp) 腐食 措置後','柱・ベースプレート溶接部(Pbp) 変形・欠損 点検時','柱・ベースプレート溶接部(Pbp) 変形・欠損 措置後','柱・ベースプレート溶接部(Pbp) その他 点検時','柱・ベースプレート溶接部(Pbp) その他 措置後','柱・ベースプレート溶接部(Pbp) 点検箇所の健全性の診断','路面境界部(GL-0)(Pgl-0) 対象有無','路面境界部(GL-0)(Pgl-0) 点検状況','路面境界部(GL-0)(Pgl-0) き裂 点検時','路面境界部(GL-0)(Pgl-0) き裂 措置後','路面境界部(GL-0)(Pgl-0) 腐食 点検時','路面境界部(GL-0)(Pgl-0) 腐食 措置後','路面境界部(GL-0)(Pgl-0) 変形・欠損 点検時','路面境界部(GL-0)(Pgl-0) 変形・欠損 措置後','路面境界部(GL-0)(Pgl-0) その他 点検時','路面境界部(GL-0)(Pgl-0) その他 措置後','路面境界部(GL-0)(Pgl-0) 点検箇所の健全性の診断','路面境界部(GL-40)(Pgl-40) 対象有無','路面境界部(GL-40)(Pgl-40) 点検状況','路面境界部(GL-40)(Pgl-40) き裂 点検時','路面境界部(GL-40)(Pgl-40) き裂 措置後','路面境界部(GL-40)(Pgl-40) 腐食 点検時','路面境界部(GL-40)(Pgl-40) 腐食 措置後','路面境界部(GL-40)(Pgl-40) 変形・欠損 点検時','路面境界部(GL-40)(Pgl-40) 変形・欠損 措置後','路面境界部(GL-40)(Pgl-40) その他 点検時','路面境界部(GL-40)(Pgl-40) その他 措置後','路面境界部(GL-40)(Pgl-40) 点検箇所の健全性の診断','柱・基礎境界部(Ppb) 対象有無','柱・基礎境界部(Ppb) 点検状況','柱・基礎境界部(Ppb) き裂 点検時','柱・基礎境界部(Ppb) き裂 措置後','柱・基礎境界部(Ppb) 腐食 点検時','柱・基礎境界部(Ppb) 腐食 措置後','柱・基礎境界部(Ppb) 変形・欠損 点検時','柱・基礎境界部(Ppb) 変形・欠損 措置後','柱・基礎境界部(Ppb) その他 点検時','柱・基礎境界部(Ppb) その他 措置後','柱・基礎境界部(Ppb) 点検箇所の健全性の診断','支柱 対策の要否','支柱 部材の健全性の診断','支柱 判定に至るまでの考え方 1.外見上から判断できる原因','支柱 判定に至るまでの考え方 2.(前回点検からの)進行性','支柱 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','支柱 判定に至るまでの考え方 4.想定される補修方法等','横材本体(Cbh) 対象有無','横材本体(Cbh) 点検状況','横材本体(Cbh) き裂 点検時','横材本体(Cbh) き裂 措置後','横材本体(Cbh) 破断 点検時','横材本体(Cbh) 破断 措置後','横材本体(Cbh) 腐食 点検時','横材本体(Cbh) 腐食 措置後','横材本体(Cbh) 変形・欠損 点検時','横材本体(Cbh) 変形・欠損 措置後','横材本体(Cbh) その他 点検時','横材本体(Cbh) その他 措置後','横材本体(Cbh) 点検箇所の健全性の診断','横材取付部(Cbi) 対象有無','横材取付部(Cbi) 点検状況','横材取付部(Cbi) き裂 点検時','横材取付部(Cbi) き裂 措置後','横材取付部(Cbi) ゆるみ・脱落 点検時','横材取付部(Cbi) ゆるみ・脱落 措置後','横材取付部(Cbi) 破断 点検時','横材取付部(Cbi) 破断 措置後','横材取付部(Cbi) 腐食 点検時','横材取付部(Cbi) 腐食 措置後','横材取付部(Cbi) 変形・欠損 点検時','横材取付部(Cbi) 変形・欠損 措置後','横材取付部(Cbi) その他 点検時','横材取付部(Cbi) その他 措置後','横材取付部(Cbi) 点検箇所の健全性の診断','横材 対策の要否','横材 部材の健全性の診断','横材 判定に至るまでの考え方 1.外見上から判断できる原因','横材 判定に至るまでの考え方 2.(前回点検からの)進行性','横材 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','横材 判定に至るまでの考え方 4.想定される補修方法等','防雪板(Srs) 対象有無','防雪板(Srs) 点検状況','防雪板(Srs) き裂 点検時','防雪板(Srs) き裂 措置後','防雪板(Srs) ゆるみ・脱落 点検時','防雪板(Srs) ゆるみ・脱落 措置後','防雪板(Srs) 破断 点検時','防雪板(Srs) 破断 措置後','防雪板(Srs) 腐食 点検時','防雪板(Srs) 腐食 措置後','防雪板(Srs) 変形・欠損 点検時','防雪板(Srs) 変形・欠損 措置後','防雪板(Srs) その他 点検時','防雪板(Srs) その他 措置後','防雪板(Srs) 点検箇所の健全性の診断','防雪板取付部(Srs) 対象有無','防雪板取付部(Srs) 点検状況','防雪板取付部(Srs) き裂 点検時','防雪板取付部(Srs) き裂 措置後','防雪板取付部(Srs) ゆるみ・脱落 点検時','防雪板取付部(Srs) ゆるみ・脱落 措置後','防雪板取付部(Srs) 破断 点検時','防雪板取付部(Srs) 破断 措置後','防雪板取付部(Srs) 腐食 点検時','防雪板取付部(Srs) 腐食 措置後','防雪板取付部(Srs) 変形・欠損 点検時','防雪板取付部(Srs) 変形・欠損 措置後','防雪板取付部(Srs) その他 点検時','防雪板取付部(Srs) その他 措置後','防雪板取付部(Srs) 点検箇所の健全性の診断','防雪板 対策の要否','防雪板 部材の健全性の診断','防雪板 判定に至るまでの考え方 1.外見上から判断できる原因','防雪板 判定に至るまでの考え方 2.(前回点検からの)進行性','防雪板 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','防雪板 判定に至るまでの考え方 4.想定される補修方法等','基礎コンクリート部(Bbc) 対象有無','基礎コンクリート部(Bbc) 点検状況','基礎コンクリート部(Bbc) ひびわれ 点検時','基礎コンクリート部(Bbc) ひびわれ 措置後','基礎コンクリート部(Bbc) うき・剥離 点検時','基礎コンクリート部(Bbc) うき・剥離 措置後','基礎コンクリート部(Bbc) 滞水 点検時','基礎コンクリート部(Bbc) 滞水 措置後','基礎コンクリート部(Bbc) その他 点検時','基礎コンクリート部(Bbc) その他 措置後','基礎コンクリート部(Bbc) 点検箇所の健全性の診断','アンカーボルト・ナット(Bab) 対象有無','アンカーボルト・ナット(Bab) 点検状況','アンカーボルト・ナット(Bab) き裂 点検時','アンカーボルト・ナット(Bab) き裂 措置後','アンカーボルト・ナット(Bab) ゆるみ・脱落 点検時','アンカーボルト・ナット(Bab) ゆるみ・脱落 措置後','アンカーボルト・ナット(Bab) 破断 点検時','アンカーボルト・ナット(Bab) 破断 措置後','アンカーボルト・ナット(Bab) 腐食 点検時','アンカーボルト・ナット(Bab) 腐食 措置後','アンカーボルト・ナット(Bab) 変形・欠損 点検時','アンカーボルト・ナット(Bab) 変形・欠損 措置後','アンカーボルト・ナット(Bab) その他 点検時','アンカーボルト・ナット(Bab) その他 措置後','アンカーボルト・ナット(Bab) 点検箇所の健全性の診断','基礎 対策の要否','基礎 部材の健全性の診断','基礎 判定に至るまでの考え方 1.外見上から判断できる原因','基礎 判定に至るまでの考え方 2.(前回点検からの)進行性','基礎 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','基礎 判定に至るまでの考え方 4.想定される補修方法等','その他1 対象有無','その他1 点検状況','その他1 き裂 点検時','その他1 き裂 措置後','その他1 ゆるみ・脱落 点検時','その他1 ゆるみ・脱落 措置後','その他1 破断 点検時','その他1 破断 措置後','その他1 腐食 点検時','その他1 腐食 措置後','その他1 変形・欠損 点検時','その他1 変形・欠損 措置後','その他1 ひびわれ 点検時','その他1 ひびわれ 措置後','その他1 うき・剥離 点検時','その他1 うき・剥離 措置後','その他1 滞水 点検時','その他1 滞水 措置後','その他1 その他 点検時','その他1 その他 措置後','その他1 点検箇所の健全性の診断','その他2 対象有無','その他2 点検状況','その他2 き裂 点検時','その他2 き裂 措置後','その他2 ゆるみ・脱落 点検時','その他2 ゆるみ・脱落 措置後','その他2 破断 点検時','その他2 破断 措置後','その他2 腐食 点検時','その他2 腐食 措置後','その他2 変形・欠損 点検時','その他2 変形・欠損 措置後','その他2 ひびわれ 点検時','その他2 ひびわれ 措置後','その他2 うき・剥離 点検時','その他2 うき・剥離 措置後','その他2 滞水 点検時','その他2 滞水 措置後','その他2 その他 点検時','その他2 その他 措置後','その他2 点検箇所の健全性の診断','その他3 対象有無','その他3 点検状況','その他3 き裂 点検時','その他3 き裂 措置後','その他3 ゆるみ・脱落 点検時','その他3 ゆるみ・脱落 措置後','その他3 破断 点検時','その他3 破断 措置後','その他3 腐食 点検時','その他3 腐食 措置後','その他3 変形・欠損 点検時','その他3 変形・欠損 措置後','その他3 ひびわれ 点検時','その他3 ひびわれ 措置後','その他3 うき・剥離 点検時','その他3 うき・剥離 措置後','その他3 滞水 点検時','その他3 滞水 措置後','その他3 その他 点検時','その他3 その他 措置後','その他3 点検箇所の健全性の診断','その他4 対象有無','その他4 点検状況','その他4 き裂 点検時','その他4 き裂 措置後','その他4 ゆるみ・脱落 点検時','その他4 ゆるみ・脱落 措置後','その他4 破断 点検時','その他4 破断 措置後','その他4 腐食 点検時','その他4 腐食 措置後','その他4 変形・欠損 点検時','その他4 変形・欠損 措置後','その他4 ひびわれ 点検時','その他4 ひびわれ 措置後','その他4 うき・剥離 点検時','その他4 うき・剥離 措置後','その他4 滞水 点検時','その他4 滞水 措置後','その他4 その他 点検時','その他4 その他 措置後','その他4 点検箇所の健全性の診断','その他5 対象有無','その他5 点検状況','その他5 き裂 点検時','その他5 き裂 措置後','その他5 ゆるみ・脱落 点検時','その他5 ゆるみ・脱落 措置後','その他5 破断 点検時','その他5 破断 措置後','その他5 腐食 点検時','その他5 腐食 措置後','その他5 変形・欠損 点検時','その他5 変形・欠損 措置後','その他5 ひびわれ 点検時','その他5 ひびわれ 措置後','その他5 うき・剥離 点検時','その他5 うき・剥離 措置後','その他5 滞水 点検時','その他5 滞水 措置後','その他5 その他 点検時','その他5 その他 措置後','その他5 点検箇所の健全性の診断','その他 対策の要否','その他 部材の健全性の診断','その他 判定に至るまでの考え方 1.外見上から判断できる原因','その他 判定に至るまでの考え方 2.(前回点検からの)進行性','その他 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','その他 判定に至るまでの考え方 4.想定される補修方法等','附属物毎の健全性の診断','表面処理','点検できなかった部位','点検できなかった理由','その他特記事項');
      } else if ($shisetsu_kbn == 5) {  // 大型スノーポール
        $export_csv_title_sonsyou = array('本体 判定区分','本体 損傷の種類','本体 備考','本体 措置後の判定区分','本体 措置後の損傷の種類','本体 措置及び判定実施年月日','横梁 判定区分','横梁 損傷の種類','横梁 備考','横梁 措置後の判定区分','横梁 措置後の損傷の種類','横梁 措置及び判定実施年月日','矢羽根 判定区分','矢羽根 損傷の種類','矢羽根 備考','矢羽根 措置後の判定区分','矢羽根 措置後の損傷の種類','矢羽根 措置及び判定実施年月日','基礎 判定区分','基礎 損傷の種類','基礎 備考','基礎 措置後の判定区分','基礎 措置後の損傷の種類','基礎 措置及び判定実施年月日','その他 判定区分','その他 損傷の種類','その他 備考','その他 措置後の判定区分','その他 措置後の損傷の種類','その他 措置及び判定実施年月日','附属物毎の健全性の診断 判定区分','附属物毎の健全性の診断 所見等','附属物毎の健全性の診断 再判定区分','附属物毎の健全性の診断 再判定実施年月日','全景写真 設置年月','全景写真 道路幅員(m)','支柱本体(Pph) 対象有無','支柱本体(Pph) 点検状況','支柱本体(Pph) き裂 点検時','支柱本体(Pph) き裂 措置後','支柱本体(Pph) 腐食 点検時','支柱本体(Pph) 腐食 措置後','支柱本体(Pph) 変形・欠損 点検時','支柱本体(Pph) 変形・欠損 措置後','支柱本体(Pph) その他 点検時','支柱本体(Pph) その他 措置後','支柱本体(Pph) 点検箇所の健全性の診断','支柱継手部【ジョイントA】(Ppj) 対象有無','支柱継手部【ジョイントA】(Ppj) 点検状況','支柱継手部【ジョイントA】(Ppj) き裂 点検時','支柱継手部【ジョイントA】(Ppj) き裂 措置後','支柱継手部【ジョイントA】(Ppj) ゆるみ・脱落 点検時','支柱継手部【ジョイントA】(Ppj) ゆるみ・脱落 措置後','支柱継手部【ジョイントA】(Ppj) 破断 点検時','支柱継手部【ジョイントA】(Ppj) 破断 措置後','支柱継手部【ジョイントA】(Ppj) 腐食 点検時','支柱継手部【ジョイントA】(Ppj) 腐食 措置後','支柱継手部【ジョイントA】(Ppj) 変形・欠損 点検時','支柱継手部【ジョイントA】(Ppj) 変形・欠損 措置後','支柱継手部【ジョイントA】(Ppj) その他 点検時','支柱継手部【ジョイントA】(Ppj) その他 措置後','支柱継手部【ジョイントA】(Ppj) 点検箇所の健全性の診断','支柱継手部【ジョイントB】(Ppj) 対象有無','支柱継手部【ジョイントB】(Ppj) 点検状況','支柱継手部【ジョイントB】(Ppj) き裂 点検時','支柱継手部【ジョイントB】(Ppj) き裂 措置後','支柱継手部【ジョイントB】(Ppj) ゆるみ・脱落 点検時','支柱継手部【ジョイントB】(Ppj) ゆるみ・脱落 措置後','支柱継手部【ジョイントB】(Ppj) 破断 点検時','支柱継手部【ジョイントB】(Ppj) 破断 措置後','支柱継手部【ジョイントB】(Ppj) 腐食 点検時','支柱継手部【ジョイントB】(Ppj) 腐食 措置後','支柱継手部【ジョイントB】(Ppj) 変形・欠損 点検時','支柱継手部【ジョイントB】(Ppj) 変形・欠損 措置後','支柱継手部【ジョイントB】(Ppj) その他 点検時','支柱継手部【ジョイントB】(Ppj) その他 措置後','支柱継手部【ジョイントB】(Ppj) 点検箇所の健全性の診断','路面境界部(GL-0)(Pgl-0) 対象有無','路面境界部(GL-0)(Pgl-0) 点検状況','路面境界部(GL-0)(Pgl-0) き裂 点検時','路面境界部(GL-0)(Pgl-0) き裂 措置後','路面境界部(GL-0)(Pgl-0) 腐食 点検時','路面境界部(GL-0)(Pgl-0) 腐食 措置後','路面境界部(GL-0)(Pgl-0) 変形・欠損 点検時','路面境界部(GL-0)(Pgl-0) 変形・欠損 措置後','路面境界部(GL-0)(Pgl-0) その他 点検時','路面境界部(GL-0)(Pgl-0) その他 措置後','路面境界部(GL-0)(Pgl-0) 点検箇所の健全性の診断','路面境界部(GL-40)(Pgl-40) 対象有無','路面境界部(GL-40)(Pgl-40) 点検状況','路面境界部(GL-40)(Pgl-40) き裂 点検時','路面境界部(GL-40)(Pgl-40) き裂 措置後','路面境界部(GL-40)(Pgl-40) 腐食 点検時','路面境界部(GL-40)(Pgl-40) 腐食 措置後','路面境界部(GL-40)(Pgl-40) 変形・欠損 点検時','路面境界部(GL-40)(Pgl-40) 変形・欠損 措置後','路面境界部(GL-40)(Pgl-40) その他 点検時','路面境界部(GL-40)(Pgl-40) その他 措置後','路面境界部(GL-40)(Pgl-40) 点検箇所の健全性の診断','柱・基礎境界部(Ppb) 対象有無','柱・基礎境界部(Ppb) 点検状況','柱・基礎境界部(Ppb) き裂 点検時','柱・基礎境界部(Ppb) き裂 措置後','柱・基礎境界部(Ppb) 腐食 点検時','柱・基礎境界部(Ppb) 腐食 措置後','柱・基礎境界部(Ppb) 変形・欠損 点検時','柱・基礎境界部(Ppb) 変形・欠損 措置後','柱・基礎境界部(Ppb) その他 点検時','柱・基礎境界部(Ppb) その他 措置後','柱・基礎境界部(Ppb) 点検箇所の健全性の診断','リブ取付溶接部(Pbr) 対象有無','リブ取付溶接部(Pbr) 点検状況','リブ取付溶接部(Pbr) き裂 点検時','リブ取付溶接部(Pbr) き裂 措置後','リブ取付溶接部(Pbr) 腐食 点検時','リブ取付溶接部(Pbr) 腐食 措置後','リブ取付溶接部(Pbr) 変形・欠損 点検時','リブ取付溶接部(Pbr) 変形・欠損 措置後','リブ取付溶接部(Pbr) その他 点検時','リブ取付溶接部(Pbr) その他 措置後','リブ取付溶接部(Pbr) 点検箇所の健全性の診断','柱・ベースプレート溶接部(Pbp) 対象有無','柱・ベースプレート溶接部(Pbp) 点検状況','柱・ベースプレート溶接部(Pbp) き裂 点検時','柱・ベースプレート溶接部(Pbp) き裂 措置後','柱・ベースプレート溶接部(Pbp) 腐食 点検時','柱・ベースプレート溶接部(Pbp) 腐食 措置後','柱・ベースプレート溶接部(Pbp) 変形・欠損 点検時','柱・ベースプレート溶接部(Pbp) 変形・欠損 措置後','柱・ベースプレート溶接部(Pbp) その他 点検時','柱・ベースプレート溶接部(Pbp) その他 措置後','柱・ベースプレート溶接部(Pbp) 点検箇所の健全性の診断','本体 対策の要否','本体 部材の健全性の診断','本体 判定に至るまでの考え方 1.外見上から判断できる原因','本体 判定に至るまでの考え方 2.(前回点検からの)進行性','本体 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','本体 判定に至るまでの考え方 4.想定される補修方法等','横梁本体(Cbh) 対象有無','横梁本体(Cbh) 点検状況','横梁本体(Cbh) き裂 点検時','横梁本体(Cbh) き裂 措置後','横梁本体(Cbh) 腐食 点検時','横梁本体(Cbh) 腐食 措置後','横梁本体(Cbh) 変形・欠損 点検時','横梁本体(Cbh) 変形・欠損 措置後','横梁本体(Cbh) その他 点検時','横梁本体(Cbh) その他 措置後','横梁本体(Cbh) 点検箇所の健全性の診断','横梁取付部(Cbi) 対象有無','横梁取付部(Cbi) 点検状況','横梁取付部(Cbi) き裂 点検時','横梁取付部(Cbi) き裂 措置後','横梁取付部(Cbi) ゆるみ・脱落 点検時','横梁取付部(Cbi) ゆるみ・脱落 措置後','横梁取付部(Cbi) 破断 点検時','横梁取付部(Cbi) 破断 措置後','横梁取付部(Cbi) 腐食 点検時','横梁取付部(Cbi) 腐食 措置後','横梁取付部(Cbi) 変形・欠損 点検時','横梁取付部(Cbi) 変形・欠損 措置後','横梁取付部(Cbi) その他 点検時','横梁取付部(Cbi) その他 措置後','横梁取付部(Cbi) 点検箇所の健全性の診断','横梁継手部(Cbj) 対象有無','横梁継手部(Cbj) 点検状況','横梁継手部(Cbj) き裂 点検時','横梁継手部(Cbj) き裂 措置後','横梁継手部(Cbj) ゆるみ・脱落 点検時','横梁継手部(Cbj) ゆるみ・脱落 措置後','横梁継手部(Cbj) 破断 点検時','横梁継手部(Cbj) 破断 措置後','横梁継手部(Cbj) 腐食 点検時','横梁継手部(Cbj) 腐食 措置後','横梁継手部(Cbj) 変形・欠損 点検時','横梁継手部(Cbj) 変形・欠損 措置後','横梁継手部(Cbj) その他 点検時','横梁継手部(Cbj) その他 措置後','横梁継手部(Cbj) 点検箇所の健全性の診断','横梁仕口溶接部(Cbw) 対象有無','横梁仕口溶接部(Cbw) 点検状況','横梁仕口溶接部(Cbw) き裂 点検時','横梁仕口溶接部(Cbw) き裂 措置後','横梁仕口溶接部(Cbw) 腐食 点検時','横梁仕口溶接部(Cbw) 腐食 措置後','横梁仕口溶接部(Cbw) 変形・欠損 点検時','横梁仕口溶接部(Cbw) 変形・欠損 措置後','横梁仕口溶接部(Cbw) その他 点検時','横梁仕口溶接部(Cbw) その他 措置後','横梁仕口溶接部(Cbw) 点検箇所の健全性の診断','横梁 対策の要否','横梁 部材の健全性の診断','横梁 判定に至るまでの考え方 1.外見上から判断できる原因','横梁 判定に至るまでの考え方 2.(前回点検からの)進行性','横梁 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','横梁 判定に至るまでの考え方 4.想定される補修方法等','矢羽根(Srs) 対象有無','矢羽根(Srs) 点検状況','矢羽根(Srs) き裂 点検時','矢羽根(Srs) き裂 措置後','矢羽根(Srs) ゆるみ・脱落 点検時','矢羽根(Srs) ゆるみ・脱落 措置後','矢羽根(Srs) 破断 点検時','矢羽根(Srs) 破断 措置後','矢羽根(Srs) 腐食 点検時','矢羽根(Srs) 腐食 措置後','矢羽根(Srs) 変形・欠損 点検時','矢羽根(Srs) 変形・欠損 措置後','矢羽根(Srs) その他 点検時','矢羽根(Srs) その他 措置後','矢羽根(Srs) 点検箇所の健全性の診断','矢羽根取付部(Srs) 対象有無','矢羽根取付部(Srs) 点検状況','矢羽根取付部(Srs) き裂 点検時','矢羽根取付部(Srs) き裂 措置後','矢羽根取付部(Srs) ゆるみ・脱落 点検時','矢羽根取付部(Srs) ゆるみ・脱落 措置後','矢羽根取付部(Srs) 破断 点検時','矢羽根取付部(Srs) 破断 措置後','矢羽根取付部(Srs) 腐食 点検時','矢羽根取付部(Srs) 腐食 措置後','矢羽根取付部(Srs) 変形・欠損 点検時','矢羽根取付部(Srs) 変形・欠損 措置後','矢羽根取付部(Srs) その他 点検時','矢羽根取付部(Srs) その他 措置後','矢羽根取付部(Srs) 点検箇所の健全性の診断','矢羽根 対策の要否','矢羽根 部材の健全性の診断','矢羽根 判定に至るまでの考え方 1.外見上から判断できる原因','矢羽根 判定に至るまでの考え方 2.(前回点検からの)進行性','矢羽根 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','矢羽根 判定に至るまでの考え方 4.想定される補修方法等','基礎コンクリート部(Bbc) 対象有無','基礎コンクリート部(Bbc) 点検状況','基礎コンクリート部(Bbc) ひびわれ 点検時','基礎コンクリート部(Bbc) ひびわれ 措置後','基礎コンクリート部(Bbc) うき・剥離 点検時','基礎コンクリート部(Bbc) うき・剥離 措置後','基礎コンクリート部(Bbc) 滞水 点検時','基礎コンクリート部(Bbc) 滞水 措置後','基礎コンクリート部(Bbc) その他 点検時','基礎コンクリート部(Bbc) その他 措置後','基礎コンクリート部(Bbc) 点検箇所の健全性の診断','アンカーボルト・ナット(Bab) 対象有無','アンカーボルト・ナット(Bab) 点検状況','アンカーボルト・ナット(Bab) き裂 点検時','アンカーボルト・ナット(Bab) き裂 措置後','アンカーボルト・ナット(Bab) ゆるみ・脱落 点検時','アンカーボルト・ナット(Bab) ゆるみ・脱落 措置後','アンカーボルト・ナット(Bab) 破断 点検時','アンカーボルト・ナット(Bab) 破断 措置後','アンカーボルト・ナット(Bab) 腐食 点検時','アンカーボルト・ナット(Bab) 腐食 措置後','アンカーボルト・ナット(Bab) 変形・欠損 点検時','アンカーボルト・ナット(Bab) 変形・欠損 措置後','アンカーボルト・ナット(Bab) その他 点検時','アンカーボルト・ナット(Bab) その他 措置後','アンカーボルト・ナット(Bab) 点検箇所の健全性の診断','基礎 対策の要否','基礎 部材の健全性の診断','基礎 判定に至るまでの考え方 1.外見上から判断できる原因','基礎 判定に至るまでの考え方 2.(前回点検からの)進行性','基礎 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','基礎 判定に至るまでの考え方 4.想定される補修方法等','道路管理者の添架標識等(Srs) 対象有無','道路管理者の添架標識等(Srs) 点検状況','道路管理者の添架標識等(Srs) き裂 点検時','道路管理者の添架標識等(Srs) き裂 措置後','道路管理者の添架標識等(Srs) ゆるみ・脱落 点検時','道路管理者の添架標識等(Srs) ゆるみ・脱落 措置後','道路管理者の添架標識等(Srs) 破断 点検時','道路管理者の添架標識等(Srs) 破断 措置後','道路管理者の添架標識等(Srs) 腐食 点検時','道路管理者の添架標識等(Srs) 腐食 措置後','道路管理者の添架標識等(Srs) 変形・欠損 点検時','道路管理者の添架標識等(Srs) 変形・欠損 措置後','道路管理者の添架標識等(Srs) その他 点検時','道路管理者の添架標識等(Srs) その他 措置後','道路管理者の添架標識等(Srs) 点検箇所の健全性の診断','道路管理者の添架物標識等取付部(Srs) 対象有無','道路管理者の添架物標識等取付部(Srs) 点検状況','道路管理者の添架物標識等取付部(Srs) き裂 点検時','道路管理者の添架物標識等取付部(Srs) き裂 措置後','道路管理者の添架物標識等取付部(Srs) ゆるみ・脱落 点検時','道路管理者の添架物標識等取付部(Srs) ゆるみ・脱落 措置後','道路管理者の添架物標識等取付部(Srs) 破断 点検時','道路管理者の添架物標識等取付部(Srs) 破断 措置後','道路管理者の添架物標識等取付部(Srs) 腐食 点検時','道路管理者の添架物標識等取付部(Srs) 腐食 措置後','道路管理者の添架物標識等取付部(Srs) 変形・欠損 点検時','道路管理者の添架物標識等取付部(Srs) 変形・欠損 措置後','道路管理者の添架物標識等取付部(Srs) その他 点検時','道路管理者の添架物標識等取付部(Srs) その他 措置後','道路管理者の添架物標識等取付部(Srs) 点検箇所の健全性の診断','その他1 対象有無','その他1 点検状況','その他1 き裂 点検時','その他1 き裂 措置後','その他1 ゆるみ・脱落 点検時','その他1 ゆるみ・脱落 措置後','その他1 破断 点検時','その他1 破断 措置後','その他1 腐食 点検時','その他1 腐食 措置後','その他1 変形・欠損 点検時','その他1 変形・欠損 措置後','その他1 ひびわれ 点検時','その他1 ひびわれ 措置後','その他1 うき・剥離 点検時','その他1 うき・剥離 措置後','その他1 滞水 点検時','その他1 滞水 措置後','その他1 その他 点検時','その他1 その他 措置後','その他1 点検箇所の健全性の診断','その他2 対象有無','その他2 点検状況','その他2 き裂 点検時','その他2 き裂 措置後','その他2 ゆるみ・脱落 点検時','その他2 ゆるみ・脱落 措置後','その他2 破断 点検時','その他2 破断 措置後','その他2 腐食 点検時','その他2 腐食 措置後','その他2 変形・欠損 点検時','その他2 変形・欠損 措置後','その他2 ひびわれ 点検時','その他2 ひびわれ 措置後','その他2 うき・剥離 点検時','その他2 うき・剥離 措置後','その他2 滞水 点検時','その他2 滞水 措置後','その他2 その他 点検時','その他2 その他 措置後','その他2 点検箇所の健全性の診断','その他3 対象有無','その他3 点検状況','その他3 き裂 点検時','その他3 き裂 措置後','その他3 ゆるみ・脱落 点検時','その他3 ゆるみ・脱落 措置後','その他3 破断 点検時','その他3 破断 措置後','その他3 腐食 点検時','その他3 腐食 措置後','その他3 変形・欠損 点検時','その他3 変形・欠損 措置後','その他3 ひびわれ 点検時','その他3 ひびわれ 措置後','その他3 うき・剥離 点検時','その他3 うき・剥離 措置後','その他3 滞水 点検時','その他3 滞水 措置後','その他3 その他 点検時','その他3 その他 措置後','その他3 点検箇所の健全性の診断','その他 対策の要否','その他 部材の健全性の診断','その他 判定に至るまでの考え方 1.外見上から判断できる原因','その他 判定に至るまでの考え方 2.(前回点検からの)進行性','その他 判定に至るまでの考え方 3.耐久性・耐久力へ与える影響','その他 判定に至るまでの考え方 4.想定される補修方法等','附属物毎の健全性の診断','表面処理','点検できなかった部位','点検できなかった理由','その他特記事項');
      }

        $export_csv_title = array_merge($export_csv_title_kihon, $export_csv_title_sonsyou);
      foreach( $export_csv_title as $key => $val ){
        $export_csv_title_arr[] = mb_convert_encoding($val, 'SJIS', 'UTF-8');
      }
      //$csv_out .= implode(",",$export_csv_title_arr)."\n";
      fputcsv($file, $export_csv_title_arr);

      // 出力する項目
      // 基本情報
      if ($shisetsu_kbn == 4) {
        // 防雪柵の場合は支柱インデックスを追加
        $fields_kihon = array('shisetsu_kbn_nm',
                              'shisetsu_keishiki_nm',
                              'shisetsu_cd',
                              'struct_idx/bscnt',
                              'struct_no_s',
                              '～',
                              'struct_no_e',
                              'rosen_cd',
                              'rosen_nm',
                              'shityouson',
                              'azaban',
                              'lat',
                              'lon',
                              'sp',
                              'lr_str',
                              'substitute_road_str',
                              'emergency_road_str',
                              'motorway_str',
                              'senyou',
                              'dogen_mei',
                              'syucchoujo_mei',
                              'chk_dt',
                              'chk_company',
                              'chk_person',
                              'investigate_dt',
                              'investigate_company',
                              'investigate_person');
      } else {
      $fields_kihon = array('shisetsu_kbn_nm',
                            'shisetsu_keishiki_nm',
                            'shisetsu_cd',
                            'rosen_cd',
                            'rosen_nm',
                            'shityouson',
                            'azaban',
                            'lat',
                            'lon',
                            'sp',
                            'lr_str',
                            'substitute_road_str',
                            'emergency_road_str',
                            'motorway_str',
                            'senyou',
                            'dogen_mei',
                            'syucchoujo_mei',
                            'chk_dt',
                            'chk_company',
                            'chk_person',
                            'investigate_dt',
                            'investigate_company',
                            'investigate_person');
      }
      // 附属物毎の健全性の診断
      $fields_huzokubutsu = array('check_shisetsu_judge_nm',
                            'syoken',
                            'measures_shisetsu_judge_nm',
                            'update_dt');
      // 附属物毎の健全性の診断2個目
      $fields_huzokubutsu2 = array('check_shisetsu_judge_nm2');
      // 全景写真
      $fields_zenkei = array('secchi',
                             'fukuin');
      // 様式その4
      $fields_youshiki4 = array('surface_str',
                             'part_notable_chk',
                             'reason_notable_chk',
                             'special_report');
      // 損傷情報
      $result = $this->getSonsyouCd($shisetsu_kbn);

      $fields_buzai_default = array('check_buzai_judge_nm_' => 1,
                            'check_sonsyou_naiyou_nm_' => 1,
                            'picture_nm_' => 1,
                            'measures_buzai_judge_nm_' => 1,
                            'measures_sonsyou_naiyou_nm_' => 1,
                            'measures_dt_' => 1);
      $fields_taisyou_default = array('taisyou_umu_str_' => 3,
                                      'check_status_str_' => 3);
      $fields_sonsyou_default = array('check_before_str_' => 4,
                                      'measures_after_str_' => 4);
      $fields_chk_judge_default = array('check_judge_nm_' => 3);
      $necessity_measures_default = array('necessity_measures_str_' => 1,
                                          'check_buzai_judge_nm2_' => 1);
      $fields_hantei_default = array('hantei1_' => 1,
                             'hantei2_' => 1,
                             'hantei3_' => 1,
                             'hantei4_' => 1);
      $fields_buzai = array();
      $fields_taisyou = array();
      $fields_sonsyou = array();
      $fields_chk_judge = array();
      $fields_hantei = array();
      foreach( $result as $buzai ){
//        log_message('info', "部材コード-------------->${buzai['buzai_cd']}\n");
        $buzai_cd = $buzai['buzai_cd'];

        foreach( $buzai['buzai_detail_info'] as $buzai_detail ){
          $buzai_detail_cd = $buzai_detail['buzai_detail_cd'];

          foreach( $buzai_detail['tenken_kasyo_info'] as $tenken_kasyo ){
//            log_message('info', "点検箇所コード-------------->${tenken_kasyo['tenken_kasyo_cd']}\n");
            $tenken_kasyo_cd = $tenken_kasyo['tenken_kasyo_cd'];

            foreach( $tenken_kasyo['sonsyou_info'] as $s_key => $s_val ){
              $sonsyou_naiyou_cd = $s_val['sonsyou_naiyou_cd'];
              $mode_array = array(1 => $buzai_cd,
                                  2 => $buzai_cd . '_' . $buzai_detail_cd,
                                  3 => $buzai_cd . '_' . $buzai_detail_cd . '_' . $tenken_kasyo_cd,
                                  4 => $buzai_cd . '_' . $buzai_detail_cd . '_' . $tenken_kasyo_cd . '_' . $sonsyou_naiyou_cd);
              // 損傷(対象有無、点検状況)
              foreach( $fields_taisyou_default as $key => $val ){
                $key = $key . $mode_array[$val];
                $fields_sonsyou[$key] = $key;
              }
              // 損傷(点検時、措置後)
              foreach( $fields_sonsyou_default as $key => $val ){
                $key = $key . $mode_array[$val];
                $fields_sonsyou[$key] = $key;
              }
              // 部材の健全性診断(判定区分、損傷の種類、備考、措置後の判定区分、措置後の損傷の種類、措置及び判定実施年月日)
              foreach( $fields_buzai_default as $key => $val ){
                $key = $key . $mode_array[$val];
                $fields_buzai[$key] = $key;
              }
            }
            // 点検箇所の健全性の診断
            foreach( $fields_chk_judge_default as $key => $val ){
              $key = $key . $mode_array[$val];
              $fields_sonsyou[$key] = $key;
            }

          }
        }
        // 対策の要否、部材の健全性の診断
        foreach( $necessity_measures_default as $key => $val ){
          $key = $key . $mode_array[$val];
          $fields_sonsyou[$key] = $key;
        }
        // 判定
        foreach( $fields_hantei_default as $key => $val ){
          $key = $key . $mode_array[$val];
          $fields_sonsyou[$key] = $key;
        }
      }

      $fields = array_merge($fields_buzai, $fields_huzokubutsu, $fields_zenkei, $fields_sonsyou, $fields_huzokubutsu2, $fields_youshiki4);
      $fields_sonsyou_result = array();
      // 項目名にフィールド名をセット
      foreach( $fields as $key => $val ){
        array_push($fields_sonsyou_result, $val);
      }
      $fields = array_merge($fields_kihon, $fields_sonsyou_result);
    } catch (RecordNotFoundException $e) {
      $this->error($e->getMessage());
    }
    return $fields;
  }


  public function outputListCsvRowData($file,$fields,$item){
    $export_arr = array();
    foreach( $fields as $field ) {
      if($field == 'struct_idx/bscnt') {
        $export_arr[$field] = mb_convert_encoding($item['struct_idx'] . '/' . $item['bscnt'], 'SJIS', 'UTF-8');
      } else if($field == '～') {
        $export_arr[$field] = mb_convert_encoding('～', 'SJIS', 'UTF-8');
      } else if( !isset($item[$field]) ){
        $export_arr[$field]="";
      } else if( preg_match('/judge/',$field) ){
        $export_arr[$field] = mb_convert_encoding($item[$field], 'SJIS-WIN', 'UTF-8');
      } else {
        $export_arr[$field] = mb_convert_encoding($item[$field], 'SJIS', 'UTF-8');
      }
    }
    fputcsv($file, $export_arr);
  }
  /**
    * 部材コード、部材詳細コード、点検箇所コード、損傷内容コードの取得
    *   引数のshisetsu_kbnから各コードを取得する。
    *
    * @param integer shisetsu_kbn
    * @return array 各コード情報
  */
  protected function getSonsyouCd($shisetsu_kbn){
    log_message('debug', __METHOD__);

    $sql= <<<EOF
SELECT
  shisetsu_kbn
  , json_agg(to_jsonb(b_row) - 'shisetsu_kbn') buzai_info
FROM
  (
    SELECT
      shisetsu_kbn
      , buzai_cd
      , json_agg(to_jsonb(bd_row) - 'shisetsu_kbn' - 'buzai_cd') buzai_detail_info
    FROM
      (
        SELECT
          shisetsu_kbn
          , buzai_cd
          , buzai_detail_cd
          , json_agg(
            to_jsonb(tk_row) - 'shisetsu_kbn' - 'buzai_cd' - 'buzai_detail_cd'
          ) tenken_kasyo_info
        FROM
          (
            SELECT
              shisetsu_kbn
              , buzai_cd
              , buzai_detail_cd
              , tenken_kasyo_cd
              , json_agg(
                to_jsonb(s_row) - 'shisetsu_kbn' - 'buzai_cd' - 'buzai_detail_cd' - 'tenken_kasyo_cd' ORDER BY sonsyou_naiyou_cd
              ) sonsyou_info
            FROM
              (
                SELECT
                  *
                FROM
                  rfs_m_chk_sonsyou
                WHERE
                  shisetsu_kbn = $shisetsu_kbn
              ) s_row
            GROUP BY
              shisetsu_kbn
              , buzai_cd
              , buzai_detail_cd
              , tenken_kasyo_cd
            ORDER BY
              shisetsu_kbn
              , buzai_cd
              , buzai_detail_cd
              , tenken_kasyo_cd
          ) tk_row
        GROUP BY
          shisetsu_kbn
          , buzai_cd
          , buzai_detail_cd
        ORDER BY
          shisetsu_kbn
          , buzai_cd
          , buzai_detail_cd
      ) bd_row
    GROUP BY
      shisetsu_kbn
      , buzai_cd
    ORDER BY
      shisetsu_kbn
      , buzai_cd
  ) b_row
GROUP BY
  shisetsu_kbn
ORDER BY
  shisetsu_kbn
EOF;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return json_decode($result[0]['buzai_info'],true);
  }
}
