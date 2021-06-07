<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 道路施設管理システム検索に関するモデル
 * 返却は配列
 *
 * @access public
 * @package Model
 */
class FamMainModel extends CI_Model {

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

  /**
   * 施設検索
   *  先に件数を求める
   *
   *  廃止の扱い
   *   ここでは、廃止の施設も検索できる
   *   集計では、廃止は含めないので、件数に差異が生じることもある
   *
   * 引数：画面の入力検索項目
   *  shisetsu_cd 施設コード
   *  secchi_from 設置年度FROM
   *  secchi_to 設置年度TO
   *  secchi_null 設置年度不明
   *  sp_from 測点FROM
   *  sp_to 測点TO
   *  shityouson  市町村
   *  azaban  字番
   *  shisetsu_kbn array 施設区分
   *  substitute_road array 代替路の有無
   *  emergency_road array 緊急輸送道路
   *  kyouyou_kbn array 供用区分
   *  rosen array 路線
   * 戻り値：施設データarray
   */
  public function srchShisetsuNum($condition) {
    log_message('debug', 'srchShisetsuNum');

    // 条件を作成
    $where_dogen_cd="";
    $where_syucchoujo_cd="";
    $where_shisetsu_cd="";
    $where_secchi="";
    $where_sp="";
    $where_shityouson="";
    $where_azaban="";
    $where_shisetsu_kbn="";
    $where_substitute_road="";
    $where_emergency_road="";
    $where_kyouyou_kbn="";
    $where_rosen="";

    /* 201902追加 照明の場合のみの施設台帳データ検索用 */
    $where_touchuu="";

    /*******************/
    /***   条件設定   ***/
    /*******************/
    /***
     * 建管コード
     ***/
    $where_dogen_cd = " AND s1.dogen_cd = ".$condition['dogen_cd']." ";

    /***
     * 出張所コード
     ***/
    if ($condition['syucchoujo_cd']!=0) {
      $where_syucchoujo_cd = " AND s1.syucchoujo_cd = ".$condition['syucchoujo_cd']." ";
    }
    /***
     * 施設コード
     ***/
    if (isset($condition['shisetsu_cd'])) {
      $where_shisetsu_cd = " AND s1.shisetsu_cd LIKE '%".$condition['shisetsu_cd']."%' ";
    }
    /***
     * 設置年度
     ***/
    /*** どちらも入っている場合 ***/
    if (isset($condition['secchi_from']) && isset($condition['secchi_to'])) {
      // 大小関係
      if ((int)($condition['secchi_from']) < (int)($condition['secchi_to'])) {
        // fromの方が小さい
        if (isset($condition['secchi_null'])) {
          // 不明を含む
          $where_secchi = " AND ((".$condition['secchi_from']." <= s1.secchi_yyyy AND s1.secchi_yyyy <= ".$condition['secchi_to'].") OR s1.secchi_yyyy IS NULL) ";
        } else {
          $where_secchi = " AND (".$condition['secchi_from']." <= s1.secchi_yyyy AND s1.secchi_yyyy <= ".$condition['secchi_to'].") ";
        }
      } else if ((int)($condition['secchi_from']) === (int)($condition['secchi_to'])) {
        // 同じ場合
        if (isset($condition['secchi_null'])) {
          // 不明を含む
          $where_secchi = " AND (s1.secchi_yyyy = ".$condition['secchi_from']." OR s1.secchi_yyyy IS NULL) ";
        }else{
          $where_secchi = " AND s1.secchi_yyyy = ".$condition['secchi_from']." ";
        }
      } else {
        // fromの方が大きい
        if (isset($condition['secchi_null'])) {
          // 不明を含む
          $where_secchi = " AND ((".$condition['secchi_to']." <= s1.secchi_yyyy AND s1.secchi_yyyy <= ".$condition['secchi_from'].") OR s1.secchi_yyyy IS NULL) ";
        } else {
          $where_secchi = " AND (".$condition['secchi_to']." <= s1.secchi_yyyy AND s1.secchi_yyyy <= ".$condition['secchi_from'].") ";
        }
      }

    /*** どちらも入っていない ***/
    } else if (!isset($condition['secchi_from']) && !isset($condition['secchi_to'])) {
      if (isset($condition['secchi_null'])) {
        // 不明を含む
        $where_secchi = " AND s1.secchi_yyyy IS NULL ";
      }
    /*** FROMのみ入っている場合 ***/
    } else if (!isset($condition['secchi_to'])) {
      if (isset($condition['secchi_null'])) {
        // 不明を含む
        $where_secchi = " AND (".$condition['secchi_from']." <= s1.secchi_yyyy OR s1.secchi_yyyy IS NULL) ";
      } else {
        $where_secchi = " AND ".$condition['secchi_from']." <= s1.secchi_yyyy ";
      }
    /*** TOのみ入っている場合 ***/
    } else {
      if (isset($condition['secchi_null'])) {
        // 不明を含む
        $where_secchi = " AND (s1.secchi_yyyy <= ".$condition['secchi_to']." OR s1.secchi_yyyy IS NULL) ";
      } else {
        $where_secchi = " AND s1.secchi_yyyy <= ".$condition['secchi_to']." ";
      }
    }

    /***
     * 測点
     ***/
    /*** どちらも入っている場合 ***/
    if (isset($condition['sp_from']) && isset($condition['sp_to'])) {
      // 大小関係
      if ((int)($condition['sp_from']) < (int)($condition['sp_to'])) {
        // fromの方が小さい
        $where_sp = " AND ((".$condition['sp_from']." <= s1.sp AND s1.sp <= ".$condition['sp_to'].") OR (".$condition['sp_from']." <= s1.sp_to AND s1.sp_to <= ".$condition['sp_to'].")) ";
      } else if ((int)($condition['sp_from']) === (int)($condition['sp_to'])) {
        // 同じ場合
        $where_sp = " AND (s1.sp = ".$condition['sp_from']." OR s1.sp_to = ".$condition['sp_from'].") ";
      } else {
        // fromの方が大きい
        $where_sp = " AND ((".$condition['sp_to']." <= s1.sp AND s1.sp <= ".$condition['sp_from'].") OR (".$condition['sp_to']." <= s1.sp_to AND s1.sp_to <= ".$condition['sp_from'].")) ";
      }
    /*** どちらも入っていない ***/
    } else if (!isset($condition['sp_from']) && !isset($condition['sp_to'])) {
      //セットなし
    /*** FROMのみ入っている場合 ***/
    } else if (!isset($condition['sp_to'])) {
      $where_sp = " AND (".$condition['sp_from']." <= s1.sp OR ".$condition['sp_from']." <= s1.sp_to) ";
    /*** TOのみ入っている場合 ***/
    } else {
      $where_sp = " AND (s1.sp <= ".$condition['sp_to']." OR s1.sp_to <= ".$condition['sp_to'].") ";
    }

    /***
     * 市町村
     ***/
    if (isset($condition['shityouson'])) {
      $where_shityouson = " AND s1.shityouson LIKE '%".$condition['shityouson']."%' ";
    }

    /***
     * 字番
     ***/
    if (isset($condition['azaban'])) {
      $where_azaban = " AND s1.azaban LIKE '%".$condition['azaban']."%' ";
    }

    /***
     * 施設区分
     ***/
    if (isset($condition['shisetsu_kbn'])) {
      $where_shisetsu_kbn = " AND s1.shisetsu_kbn IN (";
      for ($i=0;$i<count($condition['shisetsu_kbn']);$i++) {
        if ($i>0) {
          $where_shisetsu_kbn .= ", ";
        }
        $where_shisetsu_kbn .= $condition['shisetsu_kbn'][$i];
      }
      $where_shisetsu_kbn .= ")";
    }

    /***
     * 代替路の有無
     ***/
    if (isset($condition['substitute_road'])) {
      $where_substitute_road = " AND s1.substitute_road IN (";
      for ($i=0;$i<count($condition['substitute_road']);$i++) {
        if ($i>0) {
          $where_substitute_road .= ", ";
        }
        $where_substitute_road .= $condition['substitute_road'][$i];
      }
      $where_substitute_road .= ")";
    }

    /***
     * 緊急輸送道路
     ***/
    if (isset($condition['emergency_road'])) {
      $where_emergency_road = " AND s1.emergency_road IN (";
      for ($i=0;$i<count($condition['emergency_road']);$i++) {
        if ($i>0) {
          $where_emergency_road .= ", ";
        }
        $where_emergency_road .= $condition['emergency_road'][$i];
      }
      $where_emergency_road .= ")";
    }

    /***
     * 供用区分
     ***/
    if (isset($condition['kyouyou_kbn'])) {
      // 未入力のみの場合
      if (count($condition['kyouyou_kbn']==1) && $condition['kyouyou_kbn'][0]==-2) {
        $where_kyouyou_kbn = " AND s1.kyouyou_kbn IS NULL ";
      } else {
        $where_kyouyou_kbn = " AND s1.kyouyou_kbn IN (";
        $noinput=false;
        for ($i=0;$i<count($condition['kyouyou_kbn']);$i++) {
          if ($condition['kyouyou_kbn'][$i]==-2) {
            $noinput=true;
            continue;
          }
          if ($i>0) {
            $where_kyouyou_kbn .= ", ";
          }
          $where_kyouyou_kbn .= $condition['kyouyou_kbn'][$i];
        }
        $where_kyouyou_kbn .= ")";

        // 未入力が含まれている場合
        if ($noinput==true) {
          $where_kyouyou_kbn = "(".$where_kyouyou_kbn." OR s1.kyouyou_kbn IS NULL) ";
        }
      }
    }

    /***
     * 路線コード
     ***/
    if (isset($condition['rosen'])) {
      $where_rosen = " AND s1.rosen_cd IN (";
      for ($i=0;$i<count($condition['rosen']);$i++) {
        if ($i>0) {
          $where_rosen .= ", ";
        }
        $where_rosen .= $condition['rosen'][$i];
      }
      $where_rosen .= ")";
    }

    /***
     * 灯柱番号
     ***/
    /* 201902追加 照明のみの施設台帳条件追加 */
    if (isset($condition['shisetsu_kbn']) && isset($condition['touchuu_no'])) {
      for ($i=0;$i<count($condition['shisetsu_kbn']);$i++) {
        if ($condition['shisetsu_kbn'][$i]==3 && $condition['touchuu_no']!="") {  // 照明&&灯柱ありの場合
          $touchuu_no=$condition['touchuu_no'];
          $where_touchuu = "AND other LIKE '%{$touchuu_no}%'";
          break;
        }
      }
    }

    /*************************/
    /***   条件設定ここまで   ***/
    /*************************/

/*    $sql= <<<EOF
SELECT
    count(s.sno) cnt
FROM
  (
    SELECT
        s1.*
    FROM
      rfs_m_shisetsu s1 JOIN (
        SELECT
            shisetsu_cd
          , max(shisetsu_ver) shisetsu_ver
        FROM
          rfs_m_shisetsu
        GROUP BY
          shisetsu_cd
      ) s2
        ON s1.shisetsu_cd = s2.shisetsu_cd
        AND s1.shisetsu_ver = s2.shisetsu_ver
    WHERE TRUE
    $where_dogen_cd
    $where_syucchoujo_cd
    $where_shisetsu_cd
    $where_secchi
    $where_sp
    $where_shityouson
    $where_azaban
    $where_shisetsu_kbn
    $where_substitute_road
    $where_emergency_road
    $where_kyouyou_kbn
    $where_rosen
  ) s
WHERE
TRUE
--  s.shisetsu_ver = (
--    SELECT
--        shisetsu_ver
--    FROM
--      rfs_m_shisetsu
--    WHERE
--      shisetsu_cd = s.shisetsu_cd
--    ORDER BY
--      shisetsu_ver DESC
--    LIMIT
--      1
--  )
EOF;
*/
$sql= <<<EOF
WITH shisetsu AS (
  SELECT
      s1.*
  FROM
    rfs_m_shisetsu s1 JOIN (
      SELECT
          shisetsu_cd
        , max(shisetsu_ver) shisetsu_ver
      FROM
        rfs_m_shisetsu
      GROUP BY
        shisetsu_cd
    ) s2
      ON s1.shisetsu_cd = s2.shisetsu_cd
      AND s1.shisetsu_ver = s2.shisetsu_ver
  WHERE TRUE
  $where_dogen_cd
  $where_syucchoujo_cd
  $where_shisetsu_cd
  $where_secchi
  $where_sp
  $where_shityouson
  $where_azaban
  $where_shisetsu_kbn
  $where_substitute_road
  $where_emergency_road
  $where_kyouyou_kbn
  $where_rosen
), 
shisetsu_join_ss AS (
  SELECT 
    shisetsu.*
    , '' other
  FROM 
    shisetsu
  WHERE 
    shisetsu_kbn <> 3
UNION 
  SELECT
    shisetsu.*
    , daichou_ss.toutyuu_no other
  FROM
    shisetsu
  LEFT JOIN 
    rfs_t_daichou_ss daichou_ss
  ON 
    shisetsu.sno = daichou_ss.sno
  WHERE 
  shisetsu_kbn = 3
)
SELECT 
  count(*) cnt
FROM 
shisetsu_join_ss
WHERE TRUE
  $where_touchuu
EOF;
//    log_message('debug', "sql=$sql");
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
//    $r = print_r($result, true);
//    log_message('debug', "result=$r");
    return $result[0]['cnt'];
  }

  /**
   * 施設検索
   *  検索項目は整頓されていて、入力されていない条件は引数に入っていない事
   *
   *  廃止の扱い
   *   ここでは、廃止の施設も検索できる
   *   集計では、廃止は含めないので、件数に差異が生じることもある
   *
   * 引数：画面の入力検索項目
   *  shisetsu_cd 施設コード
   *  secchi_from 設置年度FROM
   *  secchi_to 設置年度TO
   *  secchi_null 設置年度不明
   *  sp_from 測点FROM
   *  sp_to 測点TO
   *  shityouson  市町村
   *  azaban  字番
   *  shisetsu_kbn array 施設区分
   *  substitute_road array 代替路の有無
   *  emergency_road array 緊急輸送道路
   *  kyouyou_kbn array 供用区分
   *  rosen array 路線
   * 戻り値：施設データarray
   */
  public function srchShisetsu($condition) {
    log_message('debug', 'srchShisetsu');

    // 条件を作成
    $where_dogen_cd="";
    $where_syucchoujo_cd="";
    $where_shisetsu_cd="";
    $where_secchi="";
    $where_sp="";
    $where_shityouson="";
    $where_azaban="";
    $where_shisetsu_kbn="";
    $where_substitute_road="";
    $where_emergency_road="";
    $where_kyouyou_kbn="";
    $where_rosen="";

    /* 201902追加 照明の場合のみの施設台帳データ検索用 */
    $where_touchuu="";
    
    /*******************/
    /***   条件設定   ***/
    /*******************/
    /***
     * 建管コード
     ***/
    $where_dogen_cd = " AND s1.dogen_cd = ".$condition['dogen_cd']." ";

    /***
     * 出張所コード
     ***/
    if ($condition['syucchoujo_cd']!=0) {
      $where_syucchoujo_cd = " AND s1.syucchoujo_cd = ".$condition['syucchoujo_cd']." ";
    }
    /***
     * 施設コード
     ***/
    if (isset($condition['shisetsu_cd'])) {
      $where_shisetsu_cd = " AND s1.shisetsu_cd LIKE '%".$condition['shisetsu_cd']."%' ";
    }
    /***
     * 設置年度
     ***/
    /*** どちらも入っている場合 ***/
    if (isset($condition['secchi_from']) && isset($condition['secchi_to'])) {
      // 大小関係
      if ((int)($condition['secchi_from']) < (int)($condition['secchi_to'])) {
        // fromの方が小さい
        if (isset($condition['secchi_null'])) {
          // 不明を含む
          $where_secchi = " AND ((".$condition['secchi_from']." <= s1.secchi_yyyy AND s1.secchi_yyyy <= ".$condition['secchi_to'].") OR s1.secchi_yyyy IS NULL) ";
        } else {
          $where_secchi = " AND (".$condition['secchi_from']." <= s1.secchi_yyyy AND s1.secchi_yyyy <= ".$condition['secchi_to'].") ";
        }
      } else if ((int)($condition['secchi_from']) === (int)($condition['secchi_to'])) {
        // 同じ場合
        if (isset($condition['secchi_null'])) {
          // 不明を含む
          $where_secchi = " AND (s1.secchi_yyyy = ".$condition['secchi_from']." OR s1.secchi_yyyy IS NULL) ";
        }else{
          $where_secchi = " AND s1.secchi_yyyy = ".$condition['secchi_from']." ";
        }
      } else {
        // fromの方が大きい
        if (isset($condition['secchi_null'])) {
          // 不明を含む
          $where_secchi = " AND ((".$condition['secchi_to']." <= s1.secchi_yyyy AND s1.secchi_yyyy <= ".$condition['secchi_from'].") OR s1.secchi_yyyy IS NULL) ";
        } else {
          $where_secchi = " AND (".$condition['secchi_to']." <= s1.secchi_yyyy AND s1.secchi_yyyy <= ".$condition['secchi_from'].") ";
        }
      }

      /*** どちらも入っていない ***/
    } else if (!isset($condition['secchi_from']) && !isset($condition['secchi_to'])) {
      if (isset($condition['secchi_null'])) {
        // 不明を含む
        $where_secchi = " AND s1.secchi_yyyy IS NULL ";
      }
      /*** FROMのみ入っている場合 ***/
    } else if (!isset($condition['secchi_to'])) {
      if (isset($condition['secchi_null'])) {
        // 不明を含む
        $where_secchi = " AND (".$condition['secchi_from']." <= s1.secchi_yyyy OR s1.secchi_yyyy IS NULL) ";
      } else {
        $where_secchi = " AND ".$condition['secchi_from']." <= s1.secchi_yyyy ";
      }
      /*** TOのみ入っている場合 ***/
    } else {
      if (isset($condition['secchi_null'])) {
        // 不明を含む
        $where_secchi = " AND (s1.secchi_yyyy <= ".$condition['secchi_to']." OR s1.secchi_yyyy IS NULL) ";
      } else {
        $where_secchi = " AND s1.secchi_yyyy <= ".$condition['secchi_to']." ";
      }
    }

    /***
     * 測点
     ***/
    /*** どちらも入っている場合 ***/
    if (isset($condition['sp_from']) && isset($condition['sp_to'])) {
      // 大小関係
      if ((int)($condition['sp_from']) < (int)($condition['sp_to'])) {
        // fromの方が小さい
        $where_sp = " AND ((".$condition['sp_from']." <= s1.sp AND s1.sp <= ".$condition['sp_to'].") OR (".$condition['sp_from']." <= s1.sp_to AND s1.sp_to <= ".$condition['sp_to'].")) ";
      } else if ((int)($condition['sp_from']) === (int)($condition['sp_to'])) {
        // 同じ場合
        $where_sp = " AND (s1.sp = ".$condition['sp_from']." OR s1.sp_to = ".$condition['sp_from'].") ";
      } else {
        // fromの方が大きい
        $where_sp = " AND ((".$condition['sp_to']." <= s1.sp AND s1.sp <= ".$condition['sp_from'].") OR (".$condition['sp_to']." <= s1.sp_to AND s1.sp_to <= ".$condition['sp_from'].")) ";
      }
      /*** どちらも入っていない ***/
    } else if (!isset($condition['sp_from']) && !isset($condition['sp_to'])) {
      //セットなし
      /*** FROMのみ入っている場合 ***/
    } else if (!isset($condition['sp_to'])) {
      $where_sp = " AND (".$condition['sp_from']." <= s1.sp OR ".$condition['sp_from']." <= s1.sp_to) ";
      /*** TOのみ入っている場合 ***/
    } else {
      $where_sp = " AND (s1.sp <= ".$condition['sp_to']." OR s1.sp_to <= ".$condition['sp_to'].") ";
    }

    /***
     * 市町村
     ***/
    if (isset($condition['shityouson'])) {
      $where_shityouson = " AND s1.shityouson LIKE '%".$condition['shityouson']."%' ";
    }

    /***
     * 字番
     ***/
    if (isset($condition['azaban'])) {
      $where_azaban = " AND s1.azaban LIKE '%".$condition['azaban']."%' ";
    }

    /***
     * 施設区分
     ***/
    if (isset($condition['shisetsu_kbn'])) {
      $where_shisetsu_kbn = " AND s1.shisetsu_kbn IN (";
      for ($i=0;$i<count($condition['shisetsu_kbn']);$i++) {
        if ($i>0) {
          $where_shisetsu_kbn .= ", ";
        }
        $where_shisetsu_kbn .= $condition['shisetsu_kbn'][$i];
      }
      $where_shisetsu_kbn .= ")";
    }

    /***
     * 代替路の有無
     ***/
    if (isset($condition['substitute_road'])) {
      $where_substitute_road = " AND s1.substitute_road IN (";
      for ($i=0;$i<count($condition['substitute_road']);$i++) {
        if ($i>0) {
          $where_substitute_road .= ", ";
        }
        $where_substitute_road .= $condition['substitute_road'][$i];
      }
      $where_substitute_road .= ")";
    }

    /***
     * 緊急輸送道路
     ***/
    if (isset($condition['emergency_road'])) {
      $where_emergency_road = " AND s1.emergency_road IN (";
      for ($i=0;$i<count($condition['emergency_road']);$i++) {
        if ($i>0) {
          $where_emergency_road .= ", ";
        }
        $where_emergency_road .= $condition['emergency_road'][$i];
      }
      $where_emergency_road .= ")";
    }

    /***
     * 供用区分
     ***/
    if (isset($condition['kyouyou_kbn'])) {
      // 未入力のみの場合
      if (count($condition['kyouyou_kbn']==1) && $condition['kyouyou_kbn'][0]==-2) { $where_kyouyou_kbn = " AND s1.kyouyou_kbn IS NULL ";
      } else {
        $where_kyouyou_kbn = " AND s1.kyouyou_kbn IN (";
        $noinput=false;
        for ($i=0;$i<count($condition['kyouyou_kbn']);$i++) {
          if ($condition['kyouyou_kbn'][$i]==-2) {
            $noinput=true;
            continue;
          }
          if ($i>0) {
            $where_kyouyou_kbn .= ", ";
          }
          $where_kyouyou_kbn .= $condition['kyouyou_kbn'][$i];
        }
        $where_kyouyou_kbn .= ")";

        // 未入力が含まれている場合
        if ($noinput==true) {
          $where_kyouyou_kbn = "(".$where_kyouyou_kbn." OR s1.kyouyou_kbn IS NULL) ";
        }
      }
    }

    /***
     * 路線コード
     ***/
    if (isset($condition['rosen'])) {
      $where_rosen = " AND s1.rosen_cd IN (";
      for ($i=0;$i<count($condition['rosen']);$i++) {
        if ($i>0) {
          $where_rosen .= ", ";
        }
        $where_rosen .= $condition['rosen'][$i];
      }
      $where_rosen .= ")";
    }

    /***
     * 灯柱番号
     ***/
    /* 201902追加 照明のみの施設台帳条件追加 */
    if (isset($condition['shisetsu_kbn'])) {
      for ($i=0;$i<count($condition['shisetsu_kbn']);$i++) {
        if ($condition['shisetsu_kbn'][$i]==3 && $condition['touchuu_no']!="") {  // 照明&&灯柱ありの場合
          $touchuu_no=$condition['touchuu_no'];
          $where_touchuu = "AND other LIKE '%{$touchuu_no}%'";
          break;
        }
      }
    }

    /*************************/
    /***   条件設定ここまで   ***/
    /*************************/

    $sql= <<<EOF
SELECT
    s.sno
  , s.shisetsu_kbn
  , sk.shisetsu_kbn_nm
  , s.shisetsu_keishiki_cd
  , skei.shisetsu_keishiki_nm
  , s.name
  , s.shisetsu_cd
  , s.rosen_cd
  , r.rosen_nm
  , s.sp
  , s.sp_to
  , s.encho
  , s.fukuin
  , CASE
    WHEN s.shityouson is not NULL AND s.azaban is not NULL
    THEN (s.shityouson || s.azaban)
    WHEN s.shityouson is NULL AND s.azaban is NULL
    THEN ''
    WHEN s.shityouson is not NULL AND s.azaban is NULL
    THEN s.shityouson
    ELSE s.azaban
    END address
  , s.kyouyou_kbn
  , CASE
    WHEN s.kyouyou_kbn = 0
    THEN '休止'
    WHEN s.kyouyou_kbn = 1
    THEN '供用'
    WHEN s.kyouyou_kbn = 2
    THEN '一部休止'
    END kyouyou_kbn_str
  , s.secchi
  , s.secchi_yyyy
  , s.haishi
  , s.haishi_yyyy
  , s.lr
  , CASE
    WHEN s.lr = 0
    THEN 'L'
    WHEN s.lr = 1
    THEN 'R'
    WHEN s.lr = 2
    THEN 'C'
    WHEN s.lr = 3
    THEN 'LR'
    ELSE '-'
    END lr_str
  , s.ud
  , CASE
    WHEN s.ud = 0
    THEN '上'
    WHEN s.ud = 1
    THEN '下'
    WHEN s.ud = 2
    THEN '上下'
    END ud_str
  , s.koutsuuryou_day
  , s.koutsuuryou_12
  , s.koutsuuryou_hutuu
  , s.koutsuuryou_oogata
  , s.substitute_road
  , CASE
    WHEN s.substitute_road = 0
    THEN '有'
    WHEN s.substitute_road = 1
    THEN '無'
    ELSE '-'
    END substitute_road_str
  , s.emergency_road
  , CASE
    WHEN s.emergency_road = 1
    THEN '第1次'
    WHEN s.emergency_road = 2
    THEN '第2次'
    WHEN s.emergency_road = 3
    THEN '第3次'
    ELSE '-'
    END emergency_road_str
  , s.motorway
  , CASE
    WHEN s.motorway = 0
    THEN '自専道'
    WHEN s.motorway = 1
    THEN '一般道'
    ELSE '-'
    END motorway_str
  , s.senyou
  , s.dogen_cd
  , d.dogen_mei
  , s.syucchoujo_cd
  , syu.syucchoujo_mei
  , s.keishiki_kubun_cd1
  , kk1.keishiki_kubun keishiki_kubun1
  , s.keishiki_kubun_cd2
  , kk2.keishiki_kubun keishiki_kubun2
  , s.lat
  , s.lon
  , true as chkexcel
FROM
  (
    SELECT
        s1.*
    FROM
      rfs_m_shisetsu s1 JOIN (
        SELECT
            shisetsu_cd
          , max(shisetsu_ver) shisetsu_ver
        FROM
          rfs_m_shisetsu
        GROUP BY
          shisetsu_cd
      ) s2
        ON s1.shisetsu_cd = s2.shisetsu_cd
        AND s1.shisetsu_ver = s2.shisetsu_ver
    WHERE TRUE
    $where_dogen_cd
    $where_syucchoujo_cd
    $where_shisetsu_cd
    $where_secchi
    $where_sp
    $where_shityouson
    $where_azaban
    $where_shisetsu_kbn
    $where_substitute_road
    $where_emergency_road
    $where_kyouyou_kbn
    $where_rosen
  ) s
  LEFT JOIN rfs_m_shisetsu_kbn sk
    ON s.shisetsu_kbn = sk.shisetsu_kbn
  LEFT JOIN rfs_m_shisetsu_keishiki skei
    ON s.shisetsu_kbn = skei.shisetsu_kbn
    AND s.shisetsu_keishiki_cd = skei.shisetsu_keishiki_cd
  LEFT JOIN rfs_m_rosen r
    ON s.rosen_cd = r.rosen_cd
  LEFT JOIN rfs_m_syucchoujo syu
    ON s.syucchoujo_cd = syu.syucchoujo_cd
  LEFT JOIN rfs_m_dogen d
    ON syu.dogen_cd = d.dogen_cd
  LEFT JOIN (
    SELECT
        *
    FROM
      rfs_m_keishiki_kubun
    WHERE
      syubetsu = 1
  ) kk1
    ON s.shisetsu_kbn = kk1.shisetsu_kbn
    AND s.keishiki_kubun_cd1 = kk1.keishiki_kubun_cd
  LEFT JOIN (
    SELECT
        *
    FROM
      rfs_m_keishiki_kubun
    WHERE
      syubetsu = 2
  ) kk2
    ON s.shisetsu_kbn = kk2.shisetsu_kbn
    AND s.keishiki_kubun_cd2 = kk2.keishiki_kubun_cd
WHERE
TRUE
--  s.shisetsu_ver = (
--    SELECT
--        shisetsu_ver
--    FROM
--      rfs_m_shisetsu
--    WHERE
--      shisetsu_cd = s.shisetsu_cd
--    ORDER BY
--      shisetsu_ver DESC
--    LIMIT
--      1
--  )
ORDER BY 
    s.rosen_cd
    ,s.sp
EOF;
$sql= <<<EOF
WITH shisetsu AS (
  SELECT
      s1.*
  FROM
    rfs_m_shisetsu s1 JOIN (
      SELECT
          shisetsu_cd
        , max(shisetsu_ver) shisetsu_ver
      FROM
        rfs_m_shisetsu
      GROUP BY
        shisetsu_cd
    ) s2
      ON s1.shisetsu_cd = s2.shisetsu_cd
      AND s1.shisetsu_ver = s2.shisetsu_ver
  WHERE TRUE
  $where_dogen_cd
  $where_syucchoujo_cd
  $where_shisetsu_cd
  $where_secchi
  $where_sp
  $where_shityouson
  $where_azaban
  $where_shisetsu_kbn
  $where_substitute_road
  $where_emergency_road
  $where_kyouyou_kbn
  $where_rosen
), 
shisetsu_join_ss AS (
  SELECT 
    shisetsu.*
    , '' other
  FROM 
    shisetsu
  WHERE 
    shisetsu_kbn <> 3
UNION 
  SELECT
    shisetsu.*
    , daichou_ss.toutyuu_no other
  FROM
    shisetsu
  LEFT JOIN 
    rfs_t_daichou_ss daichou_ss
  ON 
    shisetsu.sno = daichou_ss.sno
  WHERE 
  shisetsu_kbn = 3
)
SELECT
    s.sno
  , s.shisetsu_kbn
  , sk.shisetsu_kbn_nm
  , s.shisetsu_keishiki_cd
  , skei.shisetsu_keishiki_nm
  , s.name
  , s.shisetsu_cd
  , other
  , s.rosen_cd
  , r.rosen_nm
  , s.sp
  , s.sp_to
  , s.encho
  , s.fukuin
  , CASE
    WHEN s.shityouson is not NULL AND s.azaban is not NULL
    THEN (s.shityouson || s.azaban)
    WHEN s.shityouson is NULL AND s.azaban is NULL
    THEN ''
    WHEN s.shityouson is not NULL AND s.azaban is NULL
    THEN s.shityouson
    ELSE s.azaban
    END address
  , s.kyouyou_kbn
  , CASE
    WHEN s.kyouyou_kbn = 0
    THEN '休止'
    WHEN s.kyouyou_kbn = 1
    THEN '供用'
    WHEN s.kyouyou_kbn = 2
    THEN '一部休止'
    END kyouyou_kbn_str
  , s.secchi
  , s.secchi_yyyy
  , s.haishi
  , s.haishi_yyyy
  , s.lr
  , CASE
    WHEN s.lr = 0
    THEN 'L'
    WHEN s.lr = 1
    THEN 'R'
    WHEN s.lr = 2
    THEN 'C'
    WHEN s.lr = 3
    THEN 'LR'
    ELSE '-'
    END lr_str
  , s.ud
  , CASE
    WHEN s.ud = 0
    THEN '上'
    WHEN s.ud = 1
    THEN '下'
    WHEN s.ud = 2
    THEN '上下'
    END ud_str
  , s.koutsuuryou_day
  , s.koutsuuryou_12
  , s.koutsuuryou_hutuu
  , s.koutsuuryou_oogata
  , s.substitute_road
  , CASE
    WHEN s.substitute_road = 0
    THEN '有'
    WHEN s.substitute_road = 1
    THEN '無'
    ELSE '-'
    END substitute_road_str
  , s.emergency_road
  , CASE
    WHEN s.emergency_road = 1
    THEN '第1次'
    WHEN s.emergency_road = 2
    THEN '第2次'
    WHEN s.emergency_road = 3
    THEN '第3次'
    ELSE '-'
    END emergency_road_str
  , s.motorway
  , CASE
    WHEN s.motorway = 0
    THEN '自専道'
    WHEN s.motorway = 1
    THEN '一般道'
    ELSE '-'
    END motorway_str
  , s.senyou
  , s.dogen_cd
  , d.dogen_mei
  , s.syucchoujo_cd
  , syu.syucchoujo_mei
  , s.keishiki_kubun_cd1
  , kk1.keishiki_kubun keishiki_kubun1
  , s.keishiki_kubun_cd2
  , kk2.keishiki_kubun keishiki_kubun2
  , s.lat
  , s.lon
  , true as chkexcel
FROM
  shisetsu_join_ss s
  LEFT JOIN rfs_m_shisetsu_kbn sk
    ON s.shisetsu_kbn = sk.shisetsu_kbn
  LEFT JOIN rfs_m_shisetsu_keishiki skei
    ON s.shisetsu_kbn = skei.shisetsu_kbn
    AND s.shisetsu_keishiki_cd = skei.shisetsu_keishiki_cd
  LEFT JOIN rfs_m_rosen r
    ON s.rosen_cd = r.rosen_cd
  LEFT JOIN rfs_m_syucchoujo syu
    ON s.syucchoujo_cd = syu.syucchoujo_cd
  LEFT JOIN rfs_m_dogen d
    ON syu.dogen_cd = d.dogen_cd
  LEFT JOIN (
    SELECT
        *
    FROM
      rfs_m_keishiki_kubun
    WHERE
      syubetsu = 1
  ) kk1
    ON s.shisetsu_kbn = kk1.shisetsu_kbn
    AND s.keishiki_kubun_cd1 = kk1.keishiki_kubun_cd
  LEFT JOIN (
    SELECT
        *
    FROM
      rfs_m_keishiki_kubun
    WHERE
      syubetsu = 2
  ) kk2
    ON s.shisetsu_kbn = kk2.shisetsu_kbn
    AND s.keishiki_kubun_cd2 = kk2.keishiki_kubun_cd
WHERE TRUE
  $where_touchuu
--  s.shisetsu_ver = (
--    SELECT
--        shisetsu_ver
--    FROM
--      rfs_m_shisetsu
--    WHERE
--      shisetsu_cd = s.shisetsu_cd
--    ORDER BY
--      shisetsu_ver DESC
--    LIMIT
--      1
--  )
ORDER BY 
    s.rosen_cd
    ,s.sp

EOF;
log_message('debug', "sql=$sql");
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

    //    log_message('debug', "sql=$sql");
    //    $r = print_r($result, true);
    //    log_message('debug', "result=$r");

    return $result;
  }

}
