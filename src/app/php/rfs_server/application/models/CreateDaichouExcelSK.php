<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require("CreateDaichouExcel.php");

// 緑化樹木登録用
class CreateDaichouExcelSK extends CreateDaichouExcel {

    protected static $daichou_info_arr = [
        'create_dt'=>'',
        'bangou'=>'',
        'kbn_c'=>'',
        'ichi_c'=>'',
        'tyuusya_kouen_tou'=>'',
        'akimasu_menseki'=>'',
        'kouboku_jumoku_nm1'=>'',
        'kouboku_jumoku_nm2'=>'',
        'kouboku_jumoku_nm3'=>'',
        'kouboku_jumoku_nm4'=>'',
        'kouboku_jumoku_nm5'=>'',
        'kouboku_jumoku_nm6'=>'',
        'kouboku_jumoku_nm7'=>'',
        'kouboku_jumoku_nm8'=>'',
        'kouboku_jumoku_nm9'=>'',
        'kouboku_jumoku_nm10'=>'',
        'tyuuteiboku_jumoku_nm1'=>'',
        'tyuuteiboku_jumoku_nm2'=>'',
        'tyuuteiboku_jumoku_nm3'=>'',
        'tyuuteiboku_jumoku_nm4'=>'',
        'tyuuteiboku_jumoku_nm5'=>'',
        'tyuuteiboku_jumoku_nm6'=>'',
        'tyuuteiboku_jumoku_nm7'=>'',
        'tyuuteiboku_jumoku_nm8'=>'',
        'tyuuteiboku_jumoku_nm9'=>'',
        'tyuuteiboku_jumoku_nm10'=>'',
        'kouboku_num1'=>'',
        'tyuuteiboku_num1'=>'',
        'kouboku_num2'=>'',
        'tyuuteiboku_num2'=>'',
        'kouboku_num3'=>'',
        'tyuuteiboku_num3'=>'',
        'kouboku_num4'=>'',
        'tyuuteiboku_num4'=>'',
        'kouboku_num5'=>'',
        'tyuuteiboku_num5'=>'',
        'kouboku_num6'=>'',
        'tyuuteiboku_num6'=>'',
        'kouboku_num7'=>'',
        'tyuuteiboku_num7'=>'',
        'kouboku_num8'=>'',
        'tyuuteiboku_num8'=>'',
        'kouboku_num9'=>'',
        'tyuuteiboku_num9'=>'',
        'kouboku_num10'=>'',
        'tyuuteiboku_num10'=>'',
        'tmp_kouboku_num1'=>'',
        'tmp_tyuuteiboku_num1'=>'',
        'tmp_kouboku_num2'=>'',
        'tmp_tyuuteiboku_num2'=>'',
        'tmp_kouboku_num3'=>'',
        'tmp_tyuuteiboku_num3'=>'',
        'tmp_kouboku_num4'=>'',
        'tmp_tyuuteiboku_num4'=>'',
        'tmp_kouboku_num5'=>'',
        'tmp_tyuuteiboku_num5'=>'',
        'tmp_kouboku_num6'=>'',
        'tmp_tyuuteiboku_num6'=>'',
        'tmp_kouboku_num7'=>'',
        'tmp_tyuuteiboku_num7'=>'',
        'tmp_kouboku_num8'=>'',
        'tmp_tyuuteiboku_num8'=>'',
        'tmp_kouboku_num9'=>'',
        'tmp_tyuuteiboku_num9'=>'',
        'tmp_kouboku_num10'=>'',
        'tmp_tyuuteiboku_num10'=>'',
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
    *  緑化樹木Excelの作成
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

        // 高木、中低木の合計本数を取得する
        $daichou_info['kouboku_num_total'] = 0;
        $daichou_info['tyuuteiboku_num_total'] = 0;
        for($i = 1; $i <= 10; $i++){
            if (!$daichou_info["kouboku_num$i"]) {
                $daichou_info["tmp_kouboku_num$i"] = 0;
            } else {
                $daichou_info["tmp_kouboku_num$i"] = $daichou_info["kouboku_num$i"];
            }
            if (!$daichou_info["tyuuteiboku_num$i"]) {
                $daichou_info["tmp_tyuuteiboku_num$i"] = 0;
            } else {
                $daichou_info["tmp_tyuuteiboku_num$i"] = $daichou_info["tyuuteiboku_num$i"];
            }
            $daichou_info['kouboku_num_total'] += $daichou_info["tmp_kouboku_num$i"];
            $daichou_info['tyuuteiboku_num_total'] += $daichou_info["tmp_tyuuteiboku_num$i"];
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
bangou
, kb.kbn_c
, ic.ichi_c
, tyuusya_kouen_tou
, akimasu_menseki
, tr1.jumoku_nm kouboku_jumoku_nm1
, kouboku_num1
, tr2.jumoku_nm kouboku_jumoku_nm2
, kouboku_num2
, tr3.jumoku_nm kouboku_jumoku_nm3
, kouboku_num3
, tr4.jumoku_nm kouboku_jumoku_nm4
, kouboku_num4
, tr5.jumoku_nm kouboku_jumoku_nm5
, kouboku_num5
, tr6.jumoku_nm kouboku_jumoku_nm6
, kouboku_num6
, tr7.jumoku_nm kouboku_jumoku_nm7
, kouboku_num7
, tr8.jumoku_nm kouboku_jumoku_nm8
, kouboku_num8
, tr9.jumoku_nm kouboku_jumoku_nm9
, kouboku_num9
, tr10.jumoku_nm kouboku_jumoku_nm10
, kouboku_num10
, trt1.jumoku_nm tyuuteiboku_jumoku_nm1
, tyuuteiboku_num1
, trt2.jumoku_nm tyuuteiboku_jumoku_nm2
, tyuuteiboku_num2
, trt3.jumoku_nm tyuuteiboku_jumoku_nm3
, tyuuteiboku_num3
, trt4.jumoku_nm tyuuteiboku_jumoku_nm4
, tyuuteiboku_num4
, trt5.jumoku_nm tyuuteiboku_jumoku_nm5
, tyuuteiboku_num5
, trt6.jumoku_nm tyuuteiboku_jumoku_nm6
, tyuuteiboku_num6
, trt7.jumoku_nm tyuuteiboku_jumoku_nm7
, tyuuteiboku_num7
, trt8.jumoku_nm tyuuteiboku_jumoku_nm8
, tyuuteiboku_num8
, trt9.jumoku_nm tyuuteiboku_jumoku_nm9
, tyuuteiboku_num9
, trt10.jumoku_nm tyuuteiboku_jumoku_nm10
, tyuuteiboku_num10
, kyoutsuu1
, kyoutsuu2
, kyoutsuu3
, dokuji1
, dokuji2
, dokuji3
, bikou
, to_char(create_dt,'YYYY-MM-DD') create_dt
EOF;

        $join= <<<EOF
    LEFT JOIN rfs_m_kbn_c kb
    ON $daichou_tbl.kbn_c_cd = kb.kbn_c_cd

    LEFT JOIN rfs_m_ichi_c ic
    ON $daichou_tbl.ichi_c_cd = ic.ichi_c_cd

    LEFT JOIN rfs_m_tree tr1
    ON $daichou_tbl.kouboku_jumoku_cd1 = tr1.jumoku_cd AND tr1.jumoku_syu = 1

    LEFT JOIN rfs_m_tree tr2
    ON $daichou_tbl.kouboku_jumoku_cd2 = tr2.jumoku_cd AND tr2.jumoku_syu = 1

    LEFT JOIN rfs_m_tree tr3
    ON $daichou_tbl.kouboku_jumoku_cd3 = tr3.jumoku_cd AND tr3.jumoku_syu = 1

    LEFT JOIN rfs_m_tree tr4
    ON $daichou_tbl.kouboku_jumoku_cd4 = tr4.jumoku_cd AND tr4.jumoku_syu = 1

    LEFT JOIN rfs_m_tree tr5
    ON $daichou_tbl.kouboku_jumoku_cd5 = tr5.jumoku_cd AND tr5.jumoku_syu = 1

    LEFT JOIN rfs_m_tree tr6
    ON $daichou_tbl.kouboku_jumoku_cd6 = tr6.jumoku_cd AND tr6.jumoku_syu = 1

    LEFT JOIN rfs_m_tree tr7
    ON $daichou_tbl.kouboku_jumoku_cd7 = tr7.jumoku_cd AND tr7.jumoku_syu = 1

    LEFT JOIN rfs_m_tree tr8
    ON $daichou_tbl.kouboku_jumoku_cd8 = tr8.jumoku_cd AND tr8.jumoku_syu = 1

    LEFT JOIN rfs_m_tree tr9
    ON $daichou_tbl.kouboku_jumoku_cd9 = tr9.jumoku_cd AND tr9.jumoku_syu = 1

    LEFT JOIN rfs_m_tree tr10
    ON $daichou_tbl.kouboku_jumoku_cd10 = tr10.jumoku_cd AND tr10.jumoku_syu = 1

    LEFT JOIN rfs_m_tree trt1
    ON $daichou_tbl.tyuuteiboku_jumoku_cd1 = trt1.jumoku_cd AND trt1.jumoku_syu = 2

    LEFT JOIN rfs_m_tree trt2
    ON $daichou_tbl.tyuuteiboku_jumoku_cd2 = trt2.jumoku_cd AND trt2.jumoku_syu = 2

    LEFT JOIN rfs_m_tree trt3
    ON $daichou_tbl.tyuuteiboku_jumoku_cd3 = trt3.jumoku_cd AND trt3.jumoku_syu = 2

    LEFT JOIN rfs_m_tree trt4
    ON $daichou_tbl.tyuuteiboku_jumoku_cd4 = trt4.jumoku_cd AND trt4.jumoku_syu = 2

    LEFT JOIN rfs_m_tree trt5
    ON $daichou_tbl.tyuuteiboku_jumoku_cd5 = trt5.jumoku_cd AND trt5.jumoku_syu = 2

    LEFT JOIN rfs_m_tree trt6
    ON $daichou_tbl.tyuuteiboku_jumoku_cd6 = trt6.jumoku_cd AND trt6.jumoku_syu = 2

    LEFT JOIN rfs_m_tree trt7
    ON $daichou_tbl.tyuuteiboku_jumoku_cd7 = trt7.jumoku_cd AND trt7.jumoku_syu = 2

    LEFT JOIN rfs_m_tree trt8
    ON $daichou_tbl.tyuuteiboku_jumoku_cd8 = trt8.jumoku_cd AND trt8.jumoku_syu = 2

    LEFT JOIN rfs_m_tree trt9
    ON $daichou_tbl.tyuuteiboku_jumoku_cd9 = trt9.jumoku_cd AND trt9.jumoku_syu = 2

    LEFT JOIN rfs_m_tree trt10
    ON $daichou_tbl.tyuuteiboku_jumoku_cd10 = trt10.jumoku_cd AND trt10.jumoku_syu = 2
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
