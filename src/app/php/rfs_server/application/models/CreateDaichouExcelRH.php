<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("CreateDaichouExcel.php");

// ロードヒーティング登録用
class CreateDaichouExcelRH extends CreateDaichouExcel {

  protected static $daichou_info_arr = [
    'gno'=>'',
    'genkyou_num1'=>'',
    'genkyou_num2'=>'',
    'comment'=>'',
    'old_id'=>'',
    'endou_joukyou_nm'=>'',
    'did_syubetsu_nm'=>'',
    'endou_kuiki_nm'=>'',
    'endou_chiiki_nm'=>'',
    'bus_rosen_umu'=>'',
    'tsuugaku_ro_umu'=>'',
    'yukimichi_umu'=>'',
    'haba_syadou'=>'',
    'koubai_syadou'=>'',
    'hankei_r'=>'',
    'koutsuuryou_syadou'=>'',
    'chuusin_enchou_syadou'=>'',
    'nobe_enchyou_syadou'=>'',
    'menseki_syadou'=>'',
    'hosou_syubetsu_syadou'=>'',
    'haba_hodou'=>'',
    'koubai_hodou'=>'',
    'koutsuuryou_hodou'=>'',
    'chuusin_enchou_hodou'=>'',
    'nobe_enchyou_hodou'=>'',
    'menseki_hodou'=>'',
    'hosou_syubetsu_hodou'=>'',
    'morido_str'=>'',
    'kirido_str'=>'',
    'kyuu_curve_str'=>'',
    'kyouryou_syadou_str'=>'',
    'kosen_syadou_str'=>'',
    'tunnel_syadou_str'=>'',
    'under_syadou_str'=>'',
    'kousaten_syadou_str'=>'',
    'kyuukoubai_syadou_str'=>'',
    'fumikiri_syadou_str'=>'',
    'etc_syadou_str'=>'',
    'etc_comment_syadou'=>'',
    'kyouryou_hodou_str'=>'',
    'kosen_hodou_str'=>'',
    'hodoukyou_str'=>'',
    'tunnel_hodou_str'=>'',
    'under_hodou_str'=>'',
    'kousaten_hodou_str'=>'',
    'kyuukoubai_hodou_str'=>'',
    'fumikiri_houdou_str'=>'',
    'etc_hodou_str'=>'',
    'etc_comment_hodou'=>'',
    'netsugen_etc'=>'',
    'douryoku_etc'=>'',
    'syuunetsu_nm'=>'',
    'syuunetsu_etc'=>'',
    'hounetsu_nm'=>'',
    'hounetsu_etc'=>'',
    'unit_shiyou_umu'=>'',
    'unit_ichi_umu'=>'',
    'sencor_shiyou_umu'=>'',
    'sensor_ichi_umu'=>'',
    'seigyoban_shiyou_umu'=>'',
    'seigyoban_ichi_umu'=>'',
    'haisen_shiyou_umu'=>'',
    'haisen_ichi_umu'=>'',
    'hokuden_ichi_umu'=>'',
    'seibi_keii_syadou'=>'',
    'sentei_riyuu_syadou'=>'',
    'seibi_keii_hodou'=>'',
    'sentei_riyuu_hodou'=>'',
    'denryoku_keiyaku_syubetsu_nm'=>'',
    'hs_check'=>'',
    'comment_douroka'=>'',
    'comment_dogen'=>'',
    'haishi_jikou'=>'',
    'create_dt'=>'',
    'kyoutsuu1'=>'',
    'kyoutsuu2'=>'',
    'kyoutsuu3'=>'',
    'dokuji1'=>'',
    'dokuji2'=>'',
    'dokuji3'=>'',
    'bikou'=>'',
    'josetsu_kbn'=>'',
    'fukuin_syadou'=>'',
    'fukuin_hodou'=>''
  ];

  /**
  * コンストラクタ
  */
  public function __construct() {
    parent::__construct();
  }

  /***
  *  ロードヒーティングExcelの作成
  *
  *  引数
  *    $daichou 入力台帳
  ***/
  /**
  * Excel 出力の共通処理
  *
  * @param integer $sno
  */
  protected function editDaichouData($sno) {
    log_message('debug', __METHOD__);

    $base_info = $this->getShisetsuInfo($sno)[0]; // 基本情報を取得する
    $this->chgCoord($base_info);
    $this->getShisetsuKbnId($base_info);  // 施設区分IDを取得する
    $daichou_info = $this->getDaichouInfo($sno,$base_info['shisetsu_kbn'],$base_info['daityou_tbl']); // 台帳データを取得する

    // 台帳データがない場合、デフォルト値をセット
    if(!$daichou_info){
      $daichou_info= array_merge(self::$daichou_base_info_arr, self::$daichou_info_arr);
    }

//    $huzokubutsu_info = $this->getHuzokubutsuRireki($base_info);  // 付属物点検情報を取得する

    $hosyuu_info = $this->getHosyuuRireki($sno);  // 補修情報を取得する
    $this->setMap($base_info, $daichou_info); // mapをセットする

//    $params = array_merge($base_info, $daichou_info, $hosyuu_info,$huzokubutsu_info);
    $params = array_merge($base_info, $daichou_info, $hosyuu_info);

    // Excel作成
    $this->createSheet();

    if (in_array('施設台帳様式その1', $this->included_sheets)) {
      // メインのシートを作る
      $this->editSheet('施設台帳様式その1', $params);
    }

    if (in_array('施設台帳様式その2', $this->included_sheets)) {
      // 図面のシートを作る
      $this->editSheet('施設台帳様式その2', $params);
    }

    // Excelパスを作成
    $this->setExcelPath($base_info);

  }

  /**
  * 台帳の検索
  *   引数のsnoから台帳情報を取得する。
  *
  * @param integer sno
  * @param integer shisetsu_kbn
  * @return array 台帳情報
  */
  protected function getDaichouInfo($sno, $shisetsu_kbn,$daichou_tbl){
    log_message('debug', 'getDaichouInfo');

    $fields="";
    $join="";

    $fields= <<<EOF
gno
, ej.endou_joukyou_cd
, endou_joukyou_nm
, ds.did_syubetsu_cd
, did_syubetsu_nm
, ek.endou_kuiki_cd
, endou_kuiki_nm
, ec.endou_chiiki_nm
, endou_chiiki_nm
, bus_rosen
, u1.umu_nm bus_rosen_umu
, tsuugaku_ro
, u2.umu_nm tsuugaku_ro_umu
, yukimichi
, u3.umu_nm yukimichi_umu
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
, CASE
  WHEN morido = 't' THEN '有'
  WHEN morido = 'f' THEN '無'
  END morido_str
, kirido
, CASE
  WHEN kirido = 't' THEN '有'
  WHEN kirido = 'f' THEN '無'
  END kirido_str
, kyuu_curve
, CASE
  WHEN kyuu_curve = 't' THEN '有'
  WHEN kyuu_curve = 'f' THEN '無'
  END kyuu_curve_str
, under_syadou
, CASE
  WHEN under_syadou = 't' THEN '有'
  WHEN under_syadou = 'f' THEN '無'
  END under_syadou_str
, under_hodou
, CASE
  WHEN under_hodou = 't' THEN '有'
  WHEN under_hodou = 'f' THEN '無'
  END under_hodou_str
, kyuukoubai_syadou
, CASE
  WHEN kyuukoubai_syadou = 't' THEN '有'
  WHEN kyuukoubai_syadou = 'f' THEN '無'
  END kyuukoubai_syadou_str
, kyuukoubai_hodou
, CASE
  WHEN kyuukoubai_hodou = 't' THEN '有'
  WHEN kyuukoubai_hodou = 'f' THEN '無'
  END kyuukoubai_hodou_str
, fumikiri_syadou
, CASE
  WHEN fumikiri_syadou = 't' THEN '有'
  WHEN fumikiri_syadou = 'f' THEN '無'
  END fumikiri_syadou_str
, fumikiri_houdou
, CASE
  WHEN fumikiri_houdou = 't' THEN '有'
  WHEN fumikiri_houdou = 'f' THEN '無'
  END fumikiri_houdou_str
, kousaten_syadou
, CASE
  WHEN kousaten_syadou = 't' THEN '有'
  WHEN kousaten_syadou = 'f' THEN '無'
  END kousaten_syadou_str
, kousaten_hodou
, CASE
  WHEN kousaten_hodou = 't' THEN '有'
  WHEN kousaten_hodou = 'f' THEN '無'
  END kousaten_hodou_str
, hodoukyou
, CASE
  WHEN hodoukyou = 't' THEN '有'
  WHEN hodoukyou = 'f' THEN '無'
  END hodoukyou_str
, tunnel_syadou
, CASE
  WHEN tunnel_syadou = 't' THEN '有'
  WHEN tunnel_syadou = 'f' THEN '無'
  END tunnel_syadou_str
, tunnel_hodou
, CASE
  WHEN tunnel_hodou = 't' THEN '有'
  WHEN tunnel_hodou = 'f' THEN '無'
  END tunnel_hodou_str
, heimen_syadou
, CASE
  WHEN heimen_syadou = 't' THEN '有'
  WHEN heimen_syadou = 'f' THEN '無'
  END heimen_syadou_str
, heimen_hodou
, CASE
  WHEN heimen_hodou = 't' THEN '有'
  WHEN heimen_hodou = 'f' THEN '無'
  END heimen_hodou_str
, kyouryou_syadou
, CASE
  WHEN kyouryou_syadou = 't' THEN '有'
  WHEN kyouryou_syadou = 'f' THEN '無'
  END kyouryou_syadou_str
, kyouryou_hodou
, CASE
  WHEN kyouryou_hodou = 't' THEN '有'
  WHEN kyouryou_hodou = 'f' THEN '無'
  END kyouryou_hodou_str
, kosen_syadou
, CASE
  WHEN kosen_syadou = 't' THEN '有'
  WHEN kosen_syadou = 'f' THEN '無'
  END kosen_syadou_str
, kosen_hodou
, CASE
  WHEN kosen_hodou = 't' THEN '有'
  WHEN kosen_hodou = 'f' THEN '無'
  END kosen_hodou_str
, etc_syadou
, CASE
  WHEN etc_syadou = 't' THEN '有'
  WHEN etc_syadou = 'f' THEN '無'
  END etc_syadou_str
, etc_hodou
, CASE
  WHEN etc_hodou = 't' THEN '有'
  WHEN etc_hodou = 'f' THEN '無'
  END etc_hodou_str
, etc_comment_syadou
, etc_comment_hodou
, netsugen_etc
, douryoku_etc
, s.syuunetsu_cd
, syuunetsu_nm
, syuunetsu_etc
, h.hounetsu_cd
, hounetsu_nm
, hounetsu_etc
, dks.denryoku_keiyaku_syubetsu_cd
, denryoku_keiyaku_syubetsu_nm
, seibi_keii_syadou
, sentei_riyuu_syadou
, seibi_keii_hodou
, sentei_riyuu_hodou
, haishi_jikou
, unit_shiyou
, u5.umu_nm unit_shiyou_umu
, unit_ichi
, u6.umu_nm unit_ichi_umu
, sencor_shiyou
, u7.umu_nm sencor_shiyou_umu
, sensor_ichi
, u8.umu_nm sensor_ichi_umu
, seigyoban_shiyou
, u9.umu_nm seigyoban_shiyou_umu
, seigyoban_ichi
, u10.umu_nm seigyoban_ichi_umu
, haisen_shiyou
, u11.umu_nm haisen_shiyou_umu
, haisen_ichi
, u12.umu_nm haisen_ichi_umu
, hokuden_ichi
, u13.umu_nm hokuden_ichi_umu
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
, kyoutsuu1
, kyoutsuu2
, kyoutsuu3
, dokuji1
, dokuji2
, dokuji3
, bikou
, to_char(create_dt,'YYYY.MM.DD') create_dt

EOF;

    $join= <<<EOF
LEFT JOIN
  rfs_m_rh_endou_joukyou ej
ON
  $daichou_tbl.endou_joukyou_cd = ej.endou_joukyou_cd
LEFT JOIN
  rfs_m_rh_did_syubetsu ds
ON
  $daichou_tbl.did_syubetsu_cd = ds.did_syubetsu_cd
LEFT JOIN
  rfs_m_rh_endou_kuiki ek
ON
  $daichou_tbl.endou_kuiki_cd = ek.endou_kuiki_cd
LEFT JOIN
  rfs_m_rh_endou_chiiki ec
ON
  $daichou_tbl.endou_chiiki_cd = ec.endou_chiiki_cd
LEFT JOIN
  rfs_m_rh_umu u1
ON
  $daichou_tbl.bus_rosen = u1.umu_cd
LEFT JOIN
  rfs_m_rh_umu u2
ON
  $daichou_tbl.tsuugaku_ro = u2.umu_cd
LEFT JOIN
  rfs_m_rh_umu u3
ON
  $daichou_tbl.yukimichi = u3.umu_cd
LEFT JOIN
  rfs_m_rh_umu u4
ON
  $daichou_tbl.josetsu_kbn = u4.umu_cd
LEFT JOIN
  rfs_m_rh_syuunetsu s
ON
  $daichou_tbl.syuunetsu_cd = s.syuunetsu_cd
LEFT JOIN
  rfs_m_rh_hounetsu h
ON
  $daichou_tbl.hounetsu_cd = h.hounetsu_cd
LEFT JOIN
  rfs_m_rh_denryoku_keiyaku_syubetsu dks
ON
  $daichou_tbl.denryoku_keiyaku_syubetsu_cd = dks.denryoku_keiyaku_syubetsu_cd
LEFT JOIN
  rfs_m_rh_umu u5
ON
  CAST($daichou_tbl.unit_shiyou AS integer) = u5.umu_cd
LEFT JOIN
  rfs_m_rh_umu u6
ON
  CAST($daichou_tbl.unit_ichi AS integer) = u6.umu_cd
LEFT JOIN
  rfs_m_rh_umu u7
ON
  CAST($daichou_tbl.sencor_shiyou AS integer) = u7.umu_cd
LEFT JOIN
  rfs_m_rh_umu u8
ON
  CAST($daichou_tbl.sensor_ichi AS integer) = u8.umu_cd
LEFT JOIN
  rfs_m_rh_umu u9
ON
  CAST($daichou_tbl.seigyoban_shiyou AS integer) = u9.umu_cd
LEFT JOIN
  rfs_m_rh_umu u10
ON
  CAST($daichou_tbl.seigyoban_ichi AS integer) = u10.umu_cd
LEFT JOIN
  rfs_m_rh_umu u11
ON
  CAST($daichou_tbl.haisen_shiyou AS integer) = u11.umu_cd
LEFT JOIN
  rfs_m_rh_umu u12
ON
  CAST($daichou_tbl.haisen_ichi AS integer) = u12.umu_cd
LEFT JOIN
  rfs_m_rh_umu u13
ON
  CAST($daichou_tbl.hokuden_ichi AS integer) = u13.umu_cd
EOF;

    $sql= <<<EOF
SELECT
  $fields
FROM
  $daichou_tbl
  $join
WHERE
  $daichou_tbl.sno = $sno
EOF;

    $query = $this->rfs->query($sql);
    $result = null;
    if(isset($query->result('array')[0])){
      $result = $query->result('array')[0];
    }

/*log_message('debug', "sql=$sql");
$r = print_r($result, true);
log_message('debug', "result=$r");*/

    return $result;

  }

}
