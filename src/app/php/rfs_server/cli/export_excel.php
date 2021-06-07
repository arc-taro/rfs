<?php
// php -d "memory_limit=512M" calc_kukan_link.php
//
// Excelを出力するプログラム。
//

require_once 'db.php';

const INPUT_EXCEL_PATH = '../../';
const OUTPUT_EXCEL_PATH = './output_excel_dir';

// 処理
main();
exit(0);

// メイン関数
function main(){

  if(!file_exists(OUTPUT_EXCEL_PATH)){
    mkdir(OUTPUT_EXCEL_PATH, 0777, true);
  }
  try {
    $db = new DB();
    $db->init();
    $sql = <<<SQL
WITH shisetsu AS (
  SELECT
    s_tmp1.*
  FROM
    rfs_m_shisetsu s_tmp1 JOIN (
      SELECT
        sno
        , max(shisetsu_ver) shisetsu_ver
      FROM
        rfs_m_shisetsu
      GROUP BY
        sno
    ) s_tmp2
      ON s_tmp1.sno = s_tmp2.sno
      AND s_tmp1.shisetsu_ver = s_tmp2.shisetsu_ver
)
, sn_tmp AS (
  SELECT
    sn_row.chk_mng_no
    , json_agg(to_jsonb(sn_row)) sn_tmp_list
  FROM
    (
      SELECT
        sn_tmp1.*
        , CASE
          WHEN sn_tmp1.check_before = 0
          THEN '未'
          WHEN sn_tmp1.check_before = 1
          THEN 'a'
          WHEN sn_tmp1.check_before = 2
          THEN 'c'
          WHEN sn_tmp1.check_before = 3
          THEN 'e'
          ELSE '-'
          END check_before_str
        , CASE
          WHEN sn_tmp1.measures_after = 0
          THEN '未'
          WHEN sn_tmp1.measures_after = 1
          THEN 'a'
          WHEN sn_tmp1.measures_after = 2
          THEN 'c'
          WHEN sn_tmp1.measures_after = 3
          THEN 'e'
          ELSE '-'
          END measures_after_str
      FROM
        rfs_t_chk_sonsyou sn_tmp1 JOIN (
          SELECT
            chk_mng_no
            , max(rireki_no) rireki_no
          FROM
            rfs_t_chk_tenken_kasyo
          GROUP BY
            chk_mng_no
        ) sn_tmp2
          ON sn_tmp1.chk_mng_no = sn_tmp2.chk_mng_no
          AND sn_tmp1.rireki_no = sn_tmp2.rireki_no
      ORDER BY
        sn_tmp1.chk_mng_no
        , sn_tmp1.buzai_cd
        , sn_tmp1.buzai_detail_cd
        , sn_tmp1.tenken_kasyo_cd
        , sn_tmp1.sonsyou_naiyou_cd
    ) sn_row
  GROUP BY
    sn_row.chk_mng_no
)
, c_tmp AS (
  SELECT
    c_tmp1.*
    , sn.sn_tmp_list sn_list
  FROM
    rfs_t_chk_main c_tmp1
    LEFT JOIN sn_tmp sn
      ON c_tmp1.chk_mng_no = sn.chk_mng_no
  WHERE
    '2016-04-01' <= c_tmp1.target_dt
    AND c_tmp1.target_dt < '2017-04-01'
)
, h_tmp AS (
  SELECT
    h_tmp1.*
  FROM
    rfs_t_chk_huzokubutsu h_tmp1 JOIN (
      SELECT
        chk_mng_no
        , max(rireki_no) rireki_no
      FROM
        rfs_t_chk_huzokubutsu
      GROUP BY
        chk_mng_no
    ) h_tmp2
      ON h_tmp1.chk_mng_no = h_tmp2.chk_mng_no
      AND h_tmp1.rireki_no = h_tmp2.rireki_no
)
, b_tmp AS (
  SELECT
    b_tmp1.*
    , sj1.shisetsu_judge_nm check_buzai_judge_nm
    , sj2.shisetsu_judge_nm measures_buzai_judge_nm
  FROM
    rfs_t_chk_buzai b_tmp1 JOIN (
      SELECT
        chk_mng_no
        , max(rireki_no) rireki_no
      FROM
        rfs_t_chk_buzai
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
    rfs_t_chk_tenken_kasyo tk_tmp1 JOIN (
      SELECT
        chk_mng_no
        , max(rireki_no) rireki_no
      FROM
        rfs_t_chk_tenken_kasyo
      GROUP BY
        chk_mng_no
    ) tk_tmp2
      ON tk_tmp1.chk_mng_no = tk_tmp2.chk_mng_no
      AND tk_tmp1.rireki_no = tk_tmp2.rireki_no
)
, pic_tmp_1 AS (
  SELECT
    pic_tmp1.*
  FROM
    rfs_t_chk_picture pic_tmp1 JOIN (
      SELECT
        chk_mng_no
        , buzai_cd
        , buzai_detail_cd
        , tenken_kasyo_cd
        , max(picture_cd) picture_cd
      FROM
        rfs_t_chk_picture
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
    rfs_t_chk_picture pic_tmp1 JOIN (
      SELECT
        chk_mng_no
        , buzai_cd
        , buzai_detail_cd
        , tenken_kasyo_cd
        , max(picture_cd) picture_cd
      FROM
        rfs_t_chk_picture
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
          ELSE ''
          END check_status_str
        , sj1.shisetsu_judge_nm check_judge_nm
        , sj2.shisetsu_judge_nm measures_judge_nm
        , msn.sonsyou_naiyou_nm
        , p1.path path1
        , p2.path path2
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
  ORDER BY
    b_tk_sn_tmp.chk_mng_no
)
, res AS (
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
    , h.syoken
    , h.update_dt
    , h.measures_shisetsu_judge
    , sj2.shisetsu_judge_nm measures_shisetsu_judge_nm
    , h.create_account
    , b_tk_sn.b_tk_sn_tmp_list b_tk_sn_list
  FROM
    shisetsu s JOIN c_tmp c
      ON s.sno = c.sno JOIN h_tmp h
      ON c.chk_mng_no = h.chk_mng_no JOIN b_tk_sn
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
  ORDER BY
    s.syucchoujo_cd
    , s.shisetsu_cd
    , c.struct_idx
)
select
  rfs_t_chk_excel.*
from
  res join rfs_t_chk_excel
    on res.chk_mng_no = rfs_t_chk_excel.chk_mng_no

SQL;
    $result = $db->query($sql);

    for($i = 0; $i < count($result); $i++){
      $file_path = $result[$i]['file_path'];
      $file_nm = $result[$i]['file_nm'];
      $file_path_nm = INPUT_EXCEL_PATH . $file_path . $file_nm;
      copy($file_path_nm, OUTPUT_EXCEL_PATH."/".$file_nm);
      echo $file_path_nm;
      echo $i."/".count($result)."\n";
    }
    $db->close();
  } catch (RecordNotFoundException $e) {
    echo $e->getMessage();
  }
}
