<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * モデルのベース

 * @access public
 * @package Model
 */
class BaseModel extends CI_Model {
  /**
   * コンストラクタ
   *
   */
  public function __construct()
  {
    parent::__construct();
    $this->DB_imm = $this->load->database('imm', true);
    if ($this->DB_imm->conn_id === false) {
      log_message('debug', 'データベースに接続されていません');
      return;
    }
  }

  public function getArrayResultWithCast($query)
  {
    $result = $query->result('array');

    $fields = $query->field_data();
    foreach ($result as $r => $row) {
      $c = 0;

      for ($c = 0; $c < count($fields); $c++) {
        $field = $fields[$c];

        switch ($field->type) {
        case 'bool':
          if ($result[$r][$field->name] == null) {
            settype($result[$r][$field->name], 'boolean');
            $result[$r][$field->name] = null;
          } else {
            if ($result[$r][$field->name] == 'f') {
              $result[$r][$field->name] = false;
            }
            settype($result[$r][$field->name], 'boolean');
          }
          break;

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
      }
    }

    return $result;
  }

  /**
   * $paramで与えられた連想配列をすべてエスケープする。
   * @param $escapeParam 連想配列 key:要素名 , value:text,like,int,float,array,ntext,nint,nfloatのいずれか
   */
  public function escapeParam($param, $escapeParam)
  {
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
          $result[$param_name] = $this->DB_imm->escape($param[$param_name]);
        } else if ($param_type == "int" || $param_type == "nint") {
          $result[$param_name] = $this->DB_imm->escape($param[$param_name]);
        } else if ($param_type == "float" || $param_type == "nfloat") {
          $result[$param_name] = $this->DB_imm->escape($param[$param_name]);
        } else if ($param_type == "like") {
          $result[$param_name] = $this->DB_imm->escape_like_str($param[$param_name]);
        } else if ($param_type == "array") {
          for ($i = 0; $i < count($param[$param_name]); $i++) {
            $result[$param_name][$i] = $this->DB_imm->escape($param[$param_name][$i]);
          }
          $result[$param_name] = implode(",", $result[$param_name]);
        } else if ($param_type == "object") {
          $result[$param_name] = $param[$param_name];
        }
      }

    }
    return $result;
  }

  /**
   * Upsert用のSQLを生成する。
   */
  protected function upsertHelper($table_nm, $conflict_key, $data, $param_arr) {

    $insert_val_arr = [];
    $update_set_arr = [];

    for ($i = 0; $i < count($param_arr); $i++) {
      $insert_val_arr[$i] = $data[$param_arr[$i]];
      $update_set_arr[$i] = "{$param_arr[$i]} = {$data[$param_arr[$i]]}";
    }

    $insert_col = implode(",", $param_arr);
    $insert_val = implode(",", $insert_val_arr);
    $update_set = implode(",", $update_set_arr);

    $sql = <<<EOF
INSERT
INTO $table_nm (
  $insert_col
)
VALUES (
  $insert_val
)
  ON CONFLICT
    ON CONSTRAINT $conflict_key DO UPDATE
SET
  $update_set
EOF;

    return $sql;
  }
}
