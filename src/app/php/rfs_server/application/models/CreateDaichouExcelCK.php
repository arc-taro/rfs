<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("CreateDaichouExcel.php");

// 駐車公園登録用
class CreateDaichouExcelCK extends CreateDaichouExcel {

    protected static $daichou_info_arr = [
        'create_dt'=>'',
        'genkyou_ichi_no'=>'',
        'yosan_himoku'=>'',
        'jigyouhi'=>'',
        'tyuusyadaisuu_oogata'=>'',
        'tyuusyadaisuu_hutsuu'=>'',
        'syadou_hosou_kousei'=>'',
        'syadou_hosou_menseki'=>'',
        'hodou_hosou_kousei'=>'',
        'hodou_hosou_menseki'=>'',
        'norimen_ryokuchi_menseki'=>'',
        'syoumeitou_pole_kikaku'=>'',
        'ramp_syu'=>'',
        'syoumei_dengen'=>'',
        'syoumei_hashira_num'=>'',
        'syoumei_kyuu_num'=>'',
        'toire_katashiki'=>'',
        'toire_suigen'=>'',
        'kenjousya_dai'=>'',
        'shinsyousya_dai'=>'',
        'riyou_kanou_kikan'=>'',
        'kenjousya_syou'=>'',
        'shinsyousya_syou'=>'',
        'azumaya_str'=>'',
        'kousyuu_tel_str'=>'',
        'bench_str'=>'',
        'tbl_str'=>'',
        'clock_str'=>'',
        'syokuju_kouboku'=>'',
        'syokuju_tyuuteiboku'=>'',
        'annai_hyoushiki_str'=>'',
        'kankou_annaiban_str'=>'',
        'keikan_kankoushisetsu'=>'',
        'barrierfree_seibi'=>'',
        'haishi_riyuu'=>'',
        'tk_kaisen_syu'=>'',
        'tk_kaisen_kyori'=>'',
        'tk_kaisen_id_str'=>'',
        'tk_kaisen_kyakuban_str'=>'',
        'tk_getsugaku'=>'',
        'tk_setsuzoku_moto'=>'',
        'tk_setsuzoku_saki'=>'',
        'tk_waribiki'=>'',
        'd_hokuden_kyakuban'=>'',
        'd_keiyaku_houshiki'=>'',
        'd_hikikomi'=>'',
        'd_denki_dai'=>'',
        'd_denki_ryou'=>'',
        'kyoutsuu1'=>'',
        'kyoutsuu2'=>'',
        'kyoutsuu3'=>'',
        'dokuji1'=>'',
        'dokuji2'=>'',
        'dokuji3'=>'',
        'bikou'=>''
    ];

    /**
    * コンストラクタ
    */
    public function __construct() {
        parent::__construct();
    }

    /***
    *  駐車公園Excelの作成
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

        $hosyuu_info = $this->getHosyuuRireki($sno);  // 補修情報を取得する
        $this->setMap($base_info, $daichou_info); // mapをセットする

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
genkyou_ichi_no
, yosan_himoku
, jigyouhi
, tyuusyadaisuu_oogata
, tyuusyadaisuu_hutsuu
, syadou_hosou_kousei
, syadou_hosou_menseki
, hodou_hosou_kousei
, hodou_hosou_menseki
, norimen_ryokuchi_menseki
, syoumeitou_pole_kikaku
, ramp_syu
, den.syoumei_dengen
, syoumei_hashira_num
, syoumei_kyuu_num
, toire_katashiki
, toire_suigen
, kenjousya_dai
, kenjousya_syou
, shinsyousya_dai
, shinsyousya_syou
, riyou_kanou_kikan
, CASE WHEN azumaya = 1 THEN '有'
  WHEN azumaya = 2 THEN '無'
  WHEN azumaya = 0 THEN '不明' END azumaya_str
, CASE WHEN kousyuu_tel = 1 THEN '有'
  WHEN kousyuu_tel = 2 THEN '無'
  WHEN kousyuu_tel = 0 THEN '不明' END kousyuu_tel_str
, CASE WHEN bench = 1 THEN '有'
  WHEN bench = 2 THEN '無'
  WHEN bench = 0 THEN '不明' END bench_str
, CASE WHEN tbl = 1 THEN '有'
  WHEN tbl = 2 THEN '無'
  WHEN tbl = 0 THEN '不明' END tbl_str
, CASE WHEN clock = 1 THEN '有'
  WHEN clock = 2 THEN '無'
  WHEN clock = 0 THEN '不明' END clock_str
, syokuju_kouboku
, syokuju_tyuuteiboku
, CASE WHEN annai_hyoushiki = 1 THEN '有'
  WHEN annai_hyoushiki = 2 THEN '無'
  WHEN annai_hyoushiki = 0 THEN '不明' END annai_hyoushiki_str
, CASE WHEN kankou_annaiban = 1 THEN '有'
  WHEN kankou_annaiban = 2 THEN '無'
  WHEN kankou_annaiban = 0 THEN '不明' END kankou_annaiban_str
, keikan_kankoushisetsu
, barrierfree_seibi
, haishi_riyuu
, tk_kaisen_syu
, tk_kaisen_kyori
, tk_kaisen_id
, tk_kaisen_kyakuban
, tk_getsugaku
, tk_setsuzoku_moto
, tk_setsuzoku_saki
, tk_waribiki
, d_hokuden_kyakuban
, d_keiyaku_houshiki
, d_hikikomi
, d_denki_dai
, d_denki_ryou
, kyoutsuu1
, kyoutsuu2
, kyoutsuu3
, dokuji1
, dokuji2
, dokuji3
, bikou
, to_char(create_dt,'YYYY-MM-DD') create_dt
, ' ' || tk_kaisen_id tk_kaisen_id_str
, ' ' || tk_kaisen_kyakuban tk_kaisen_kyakuban_str
EOF;

        $join= <<<EOF
  LEFT JOIN rfs_m_toire_katashiki toi
  ON $daichou_tbl.toire_katashiki_cd = toi.toire_katashiki_cd

  LEFT JOIN rfs_m_syoumei_dengen den
  ON $daichou_tbl.syoumei_dengen_cd = den.syoumei_dengen_cd
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

        return $result;
    }

}
