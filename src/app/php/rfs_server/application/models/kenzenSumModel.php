<?php

/**
 * 健全性集計のモデル
 *
 * @access public
 * @package Model
 */
class KenzenSumModel extends CI_Model {

  protected $DB_rfs;
  /**
   * コンストラクタ
   *
   * model SchCheckを初期化する。
   */
  public function __construct() {
    parent::__construct();
    $this->DB_rfs = $this->load->database('rfs', TRUE);
    if ($this->DB_rfs->conn_id === FALSE) {
      log_message('debug', 'データベースに接続されていません');
      return;
    }
  }

  public function getArrayResultWithCast($query) {
    $result = $query->result('array');

    $fields = $query->field_data();
    foreach ($result as $r => $row) {
      $c = 0;
//      foreach ($row as $header => $value) {

      for ($c = 0; $c < count($fields); $c++) {
        // fix variables types according to what is expected from
        // the database, as CodeIgniter get all as string.

        // $c = column index (starting from 0)
        // $r = row index (starting from 0)
        // $header = column name
        // $result[$r][$header] = that's the value to fix. Must
        //                        reference like this because settype
        //                        uses a pointer as param

        $field = $fields[$c];

        switch ($field->type) {

        case 'int2':
        case 'int4':
        case 'int8':
          if ($result[$r][$field->name] == null) {
            settype($result[$r][$field->name], 'integer');
            $result[$r][$field->name] = null;
          } else {
            settype($result[$r][$field->name], 'integer');
          }
          break;

        case 'numeric':
        case 'float4':
        case 'float8':
          if ($result[$r][$field->name] == null) {
            settype($result[$r][$field->name], 'float');
            $result[$r][$field->name] = null;
          } else {
            settype($result[$r][$field->name], 'float');
          }
          break;

        }
        //log_message('debug', "{$field->name}:{$field->type}");

//        $c = $c + 1;
      }
    }

    return $result;
  }

  /**
   * $paramで与えられた連想配列をすべてエスケープする。
   * @param $escapeParam 連想配列 key:要素名 , value:text,like,int,float,array,ntext,nint,nfloatのいずれか
   */
  public function escapeParam($param, $escapeParam) {
    $result = array();
    foreach ($escapeParam as $param_name => $param_type) {
      if (!isset($param[$param_name])) {
        // 定義されなかった時のデフォルト値
        if ($param_type == "text" || $param_type == "like") {
          $result[$param_name] = '';
        }
        if ($param_type == "int") {
          $result[$param_name] = -1;
        }
        if ($param_type == "ntext" || $param_type == "nfloat" || $param_type == "nint") {
          // ntextなどはnullに変換する
          $result[$param_name] = 'null';
        }
      } else {
        // エスケープ
        if ($param_type == "text" || $param_type == "ntext") {
          $result[$param_name] = $this->DB_rfs->escape($param[$param_name]);
        } else if ($param_type == "int" || $param_type == "nint") {
          $result[$param_name] = $this->DB_rfs->escape($param[$param_name]);
        } else if ($param_type == "float" || $param_type == "nfloat") {
          $result[$param_name] = $this->DB_rfs->escape($param[$param_name]);
        } else if ($param_type == "like") {
          $result[$param_name] = $this->DB_rfs->escape_like_str($param[$param_name]);
        } else if ($param_type == "array") {
          for ($i = 0; $i < count($param[$param_name]); $i++) {
            $result[$param_name][$i] = $this->DB_rfs->escape($param[$param_name][$i]);
          }
          $result[$param_name] = implode(",", $result[$param_name]);
        } else if ($param_type == "object") {
          $result[$param_name] = $param[$param_name];
        }
      }

    }
    return $result;
  }

  public function getShisetsuList($params) {

    $params = $this->escapeParam($params, array(
      "shisetsu_kbn" => "nint",
    ));

    $sql = <<<SQL
        SELECT
          rfs_m_shisetsu.dogen_cd
          , rfs_m_shisetsu.syucchoujo_cd
          , rfs_m_shisetsu.rosen_cd
          , rfs_m_shisetsu.shisetsu_kbn
          , rfs_m_dogen.dogen_mei
          , rfs_m_syucchoujo.syucchoujo_mei
          , rfs_m_rosen.rosen_nm
          , rfs_m_shisetsu_kbn.shisetsu_kbn_nm
          , count(*) cnt
        FROM
          rfs_m_shisetsu
          NATURAL JOIN rfs_m_shisetsu_huzokubutsu
          INNER JOIN rfs_m_dogen
            ON rfs_m_shisetsu.dogen_cd = rfs_m_dogen.dogen_cd
          INNER JOIN rfs_m_syucchoujo
            ON rfs_m_shisetsu.syucchoujo_cd = rfs_m_syucchoujo.syucchoujo_cd
          INNER JOIN rfs_m_rosen
            ON rfs_m_shisetsu.rosen_cd = rfs_m_rosen.rosen_cd
          LEFT JOIN rfs_m_shisetsu_kbn
            ON rfs_m_shisetsu.shisetsu_kbn = rfs_m_shisetsu_kbn.shisetsu_kbn
        WHERE
          rfs_m_shisetsu.shisetsu_kbn = {$params["shisetsu_kbn"]}
          AND (rfs_m_shisetsu.dogen_cd =  {$this->session['mngarea']['dogen_cd']} OR 0 = {$this->session['mngarea']['dogen_cd']} )
          AND (rfs_m_shisetsu.syucchoujo_cd =  {$this->session['mngarea']['syucchoujo_cd']} OR 0 = {$this->session['mngarea']['syucchoujo_cd']} )
        GROUP BY
          rfs_m_shisetsu.dogen_cd
          , rfs_m_shisetsu.syucchoujo_cd
          , rfs_m_shisetsu.rosen_cd
          , rfs_m_shisetsu.shisetsu_kbn
          , rfs_m_dogen.dogen_mei
          , rfs_m_syucchoujo.syucchoujo_mei
          , rfs_m_rosen.rosen_nm
          , rfs_m_shisetsu_kbn.shisetsu_kbn_nm
        ORDER BY
          rfs_m_shisetsu.dogen_cd
          , rfs_m_shisetsu.syucchoujo_cd
          , rfs_m_shisetsu.rosen_cd
          , rfs_m_shisetsu.shisetsu_kbn
SQL;

    $query = $this->DB_rfs->query($sql);

    $result = $this->getArrayResultWithCast($query);
    return $result;
  }

  public function getShisetsuJudge($params) {

    $params = $this->escapeParam($params, array(
      "shisetsu_kbn" => "nint",
    ));

    $sql = <<<SQL
        SELECT
          --  chk_mng_no
          --  , chk_times
          --  , rireki_no
          --  , sno
          rfs_m_shisetsu.shisetsu_kbn
          , rfs_m_shisetsu.syucchoujo_cd
          , rfs_m_shisetsu.rosen_cd
          , rfs_m_buzai.buzai_cd
          , COALESCE(measures_buzai_judge, check_buzai_judge) judge
          , count(*)
        FROM
        rfs_m_shisetsu
        INNER JOIN rfs_m_shisetsu_kbn
        ON rfs_m_shisetsu.shisetsu_kbn = rfs_m_shisetsu_kbn.shisetsu_kbn
        INNER JOIN (
          SELECT
            shisetsu_cd
            , max(shisetsu_ver) shisetsu_ver
          FROM
            rfs_m_shisetsu
          GROUP BY
            shisetsu_cd
        ) rfs_m_shisetsu_current
          ON rfs_m_shisetsu.shisetsu_cd = rfs_m_shisetsu_current.shisetsu_cd
          AND rfs_m_shisetsu.shisetsu_ver = rfs_m_shisetsu_current.shisetsu_ver
        INNER JOIN rfs_t_chk_main
          ON rfs_m_shisetsu.sno = rfs_t_chk_main.sno
        INNER JOIN (
          SELECT
            sno
            , max(chk_times) chk_times
          FROM
            rfs_t_chk_main
          GROUP BY
            sno
        ) rfs_t_chk_main_current
          ON rfs_t_chk_main.sno = rfs_t_chk_main_current.sno
          AND rfs_t_chk_main.chk_times = rfs_t_chk_main_current.chk_times
        INNER JOIN rfs_t_chk_huzokubutsu
          ON rfs_t_chk_main.chk_mng_no = rfs_t_chk_huzokubutsu.chk_mng_no
        INNER JOIN (
          SELECT
            chk_mng_no
            , max(rireki_no) rireki_no
          FROM
            rfs_t_chk_huzokubutsu
          GROUP BY
            chk_mng_no
        ) rfs_t_chk_huzokubutsu_current
          ON rfs_t_chk_huzokubutsu.chk_mng_no = rfs_t_chk_huzokubutsu_current.chk_mng_no
          AND rfs_t_chk_huzokubutsu.rireki_no = rfs_t_chk_huzokubutsu_current.rireki_no
        INNER JOIN rfs_t_chk_buzai
          ON rfs_t_chk_huzokubutsu.chk_mng_no = rfs_t_chk_buzai.chk_mng_no
          AND rfs_t_chk_huzokubutsu.rireki_no = rfs_t_chk_buzai.rireki_no
        RIGHT JOIN rfs_m_buzai
          ON rfs_m_buzai.buzai_cd = rfs_t_chk_buzai.buzai_cd
          AND rfs_m_buzai.shisetsu_kbn = rfs_m_shisetsu.shisetsu_kbn
        WHERE
          rfs_t_chk_main.struct_idx = 0
          AND rfs_m_shisetsu.shisetsu_kbn = {$params["shisetsu_kbn"]}
          AND (rfs_m_shisetsu.dogen_cd =  {$this->session['mngarea']['dogen_cd']} OR 0 = {$this->session['mngarea']['dogen_cd']} )
          AND (rfs_m_shisetsu.syucchoujo_cd =  {$this->session['mngarea']['syucchoujo_cd']} OR 0 = {$this->session['mngarea']['syucchoujo_cd']} )
        GROUP BY
          rfs_m_shisetsu.rosen_cd
          , rfs_m_shisetsu.syucchoujo_cd
          , rfs_m_shisetsu.shisetsu_kbn
          , rfs_m_buzai.buzai_cd
          , COALESCE(measures_buzai_judge, check_buzai_judge)
        ORDER BY
          rfs_m_shisetsu.syucchoujo_cd
          , rfs_m_shisetsu.rosen_cd
          , rfs_m_shisetsu.shisetsu_kbn
          , rfs_m_buzai.buzai_cd

SQL;

    $query = $this->DB_rfs->query($sql);

    $result = $this->getArrayResultWithCast($query);
    return $result;
  }
}