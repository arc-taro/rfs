<?php

/**
 * 画像用のモデル
 *
 * @access public
 * @package Model
 */
class PictureModel extends CI_Model {

  protected static $picture_nm_str = [
    '',
    '①',
    '②',
    '③',
    '④',
    '⑤',
    '⑥',
    '⑦',
    '⑧',
    '⑨',
    '⑩',
    '⑪',
    '⑫',
    '⑬',
    '⑭',
    '⑮',
    '⑯',
    '⑰',
    '⑱',
    '⑲',
    '⑳'
  ];

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

  /**
   * 画像を本保存する。
   * @param $file_name 仮のファイル名（パス込）
   * @param $param クライアントからのパラメータ
   *        $param['mode'] 'chk_picture':rfs_t_chk_pictureに登録、'zenkei_picture':rfs_t_zenkei_pictureに登録
   *        $param['chk_mnh_no'] chk_mng_no
   *        $param['rireki_no'] rireki_no
   *        $param['buzai_cd'] buzai_cd
   *        $param['buzai_detail_cd'] buzai_detail_cd
   *        $param['tenken_kasyo_cd'] tenken_kasyo_cd
   *        $param['status'] status
   *        $param['query_cd'] クライアントと共通で使用する一時的キー（クライアントが画像を要求した時にサーバ側から発行する）
   */
  public function save_picture($file_name, $param) {
    $sno = $param['sno'];
    $chk_mng_no = $param['chk_mng_no'];
    $rireki_no = $param['rireki_no'];
    $buzai_cd = $param['buzai_cd'];
    $buzai_detail_cd = $param['buzai_detail_cd'];
    $tenken_kasyo_cd = $param['tenken_kasyo_cd'];
    $status = $param['status'];
    $query_cd = $param['query_cd'];

    // 新しい画像のpicture_cdを取得する。（ついでにsyucchoujo_cdも取得する）
    $sql = <<<EOF
            select
              rfs_t_chk_main.chk_mng_no,
              rfs_m_shisetsu.syucchoujo_cd,
              case
                when max(picture_cd) is null
                then 1
                else max(picture_cd)+1
              end as new_picture_cd
            from
              rfs_t_chk_main
            join
              rfs_m_shisetsu
            on
            rfs_m_shisetsu.sno = rfs_t_chk_main.sno and
            rfs_m_shisetsu.sno = rfs_t_chk_main.sno

            left join
              rfs_t_chk_picture
            on rfs_t_chk_main.chk_mng_no = rfs_t_chk_picture.chk_mng_no

            where
                rfs_t_chk_main.chk_mng_no = $chk_mng_no

            group by
            rfs_t_chk_main.chk_mng_no,
            rfs_m_shisetsu.syucchoujo_cd

EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

    $new_picture_cd = $result[0]["new_picture_cd"];
    $syucchoujo_cd = $result[0]["syucchoujo_cd"];

    // 画像ファイルの移動
    $path = "images/photos/tenken/$syucchoujo_cd/$sno/$chk_mng_no/$new_picture_cd.jpg";
    $server_path = $this->config->config['www_path'] . $path;
    //「$directory_path」で指定されたディレクトリが存在するか確認
    if (!is_dir(pathinfo($server_path)["dirname"])) {
      //存在しないときの処理（「$directory_path」で指定されたディレクトリを作成する）
      mkdir(pathinfo($server_path)["dirname"], 0755, true);
    }
    log_message("info", "\n $file_name \n $server_path \n");
    rename($file_name, $server_path);
    $exif = $this->get_exif($server_path);
    $this->resize_image($server_path, $server_path);

    $sql = <<<SQL
        select
          row_number() over(
            order by
              mb.buzai_cd
              , mbd.buzai_detail_cd
              , mtk.tenken_kasyo_cd) as seq_no
          , mb.buzai_cd
          , mbd.buzai_detail_cd
          , mtk.tenken_kasyo_cd
        from
          rfs_m_buzai mb
          left join (
            select
              ms.shisetsu_kbn
              , tcm.chk_mng_no
            from
              rfs_m_shisetsu ms
              left join rfs_t_chk_main tcm
                on tcm.sno = ms.sno
            where
              tcm.chk_mng_no = $chk_mng_no
            ) ms
            on ms.shisetsu_kbn = mb.shisetsu_kbn
          left join
            rfs_m_buzai_detail mbd
            on mbd.shisetsu_kbn = ms.shisetsu_kbn
            and mbd.buzai_cd = mb.buzai_cd
          left join
            rfs_m_tenken_kasyo mtk
            on mtk.shisetsu_kbn = ms.shisetsu_kbn
            and mtk.buzai_cd = mb.buzai_cd
            and mtk.buzai_detail_cd = mbd.buzai_detail_cd
        where
          ms.chk_mng_no = $chk_mng_no
SQL;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

    for ($i = 0; count($result) > $i; $i++) {
      if ($result[$i]["buzai_cd"] != $buzai_cd) {
        continue;
      }
      if ($result[$i]["buzai_detail_cd"] != $buzai_detail_cd) {
        continue;
      }
      if ($result[$i]["tenken_kasyo_cd"] != $tenken_kasyo_cd) {
        continue;
      }
      $seq_no = $result[$i]["seq_no"];
      break;
    }

    // ファイル名を"写真①"の形に変換
    $picture_nm = '写真';
    if (21 > $seq_no) {
      $picture_nm .= self::get_const('picture_nm_str', $seq_no);
    } else {
      $picture_nm .= intval($seq_no);
    }
    log_message('info', "\n $picture_nm \n");

    // DBへ登録
    $sql = <<<SQL
            insert into rfs_t_chk_picture (
              chk_mng_no
              , picture_cd
              , buzai_cd
              , buzai_detail_cd
              , tenken_kasyo_cd
              , picture_nm
              , file_nm
              , path
              , up_dt
              , lat
              , lon
              , rireki_no
              , status
              , del
            ) values(
              $chk_mng_no
              ,$new_picture_cd
              ,$buzai_cd
              ,$buzai_detail_cd
              ,$tenken_kasyo_cd
              ,'$picture_nm'
              ,'$new_picture_cd.jpg'
              ,'$path'
              ,now()
              ,{$exif['lat']}
              ,{$exif['lon']}
              , $rireki_no
              , $status
              , $query_cd
            )
SQL;
    $query = $this->DB_rfs->query($sql);

  }

  /**
   * 画像を本保存する。
   * @param $file_name 仮のファイル名（パス込）
   * @param $param クライアントからのパラメータ
   *        $param['mode'] 'chk_picture':rfs_t_chk_pictureに登録、'zenkei_picture':rfs_t_zenkei_pictureに登録
   *        $param['sno'] sno
   *        $param['query_cd'] クライアントと共通で使用する一時的キー（クライアントが画像を要求した時にサーバ側から発行する）
   */
  public function save_picture_zenkei($file_name, $param) {
    $sno = $param['sno'];
    $shisetsu_cd = $param['shisetsu_cd'];
    $shisetsu_ver = $param['shisetsu_ver'];
    $struct_idx = $param['struct_idx'];
    $query_cd = $param['query_cd'];
    $use_flg = $param["use_flg"];
    $description = $param["description"];
    $exif_dt = $param["exif_dt"];
    if (!isset($description)) {
      $description = '';
    }

    // 新しい画像のpicture_cdを取得する。（ついでにsyucchoujo_cdも取得する）
    $sql = <<<EOF
        select
        *
        from
        (
        select
          rfs_m_shisetsu.sno
          , rfs_m_shisetsu.shisetsu_cd
          , rfs_m_shisetsu.shisetsu_ver
          , rfs_m_shisetsu.syucchoujo_cd
          , case
            when rfs_m_bousetsusaku_shichu.struct_idx is null
            then 0
            else rfs_m_bousetsusaku_shichu.struct_idx
            end as struct_idx
          , case
            when max(zenkei_picture_cd) is null
            then 1
            else max(zenkei_picture_cd) + 1
            end as new_picture_cd
        from
          rfs_m_shisetsu
          left join rfs_m_bousetsusaku_shichu
            on rfs_m_shisetsu.sno = rfs_m_bousetsusaku_shichu.sno
            and rfs_m_shisetsu.shisetsu_cd = rfs_m_bousetsusaku_shichu.shisetsu_cd
            and rfs_m_shisetsu.shisetsu_ver = rfs_m_bousetsusaku_shichu.shisetsu_ver
          left join rfs_t_zenkei_picture
            on rfs_m_shisetsu.sno = rfs_t_zenkei_picture.sno
            and rfs_m_shisetsu.shisetsu_cd = rfs_t_zenkei_picture.shisetsu_cd
            and rfs_m_shisetsu.shisetsu_ver = rfs_t_zenkei_picture.shisetsu_ver
            and $struct_idx = rfs_t_zenkei_picture.struct_idx
        group by
          rfs_m_shisetsu.sno
          , rfs_m_shisetsu.shisetsu_cd
          , rfs_m_shisetsu.shisetsu_ver
          , rfs_m_bousetsusaku_shichu.struct_idx
          , rfs_m_shisetsu.syucchoujo_cd
        ) as result
        where
            result.sno = $sno
            and result.struct_idx = $struct_idx
EOF;
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

//        $r = print_r($result, true);
    //        log_message('debug', "save_picture_zenkei_sql=$sql");
    //        log_message('debug', "result=$r");

    // 新規で始めて登録する場合、レコードが無い=基本情報の登録時のみなので、
    // 基本情報新規作成時は、出張所コードをGETで渡すように編集
    if ($result[0]) {
      $new_picture_cd = $result[0]["new_picture_cd"];
      $syucchoujo_cd = $result[0]["syucchoujo_cd"];
    } else {
      $new_picture_cd = 1;
      $syucchoujo_cd = $param["syucchoujo_cd"];
    }

    // 画像ファイルの移動
    $path = "images/photos/zenkei/$syucchoujo_cd/$shisetsu_cd/$struct_idx-$new_picture_cd.jpg";
    $server_path = $this->config->config['www_path'] . $path;
    //「$directory_path」で指定されたディレクトリが存在するか確認
    if (!is_dir(pathinfo($server_path)["dirname"])) {
      //存在しないときの処理（「$directory_path」で指定されたディレクトリを作成する）
      mkdir(pathinfo($server_path)["dirname"], 0777, true);
    }
    log_message("info", "\n $file_name \n $server_path \n");
    rename($file_name, $server_path);
    $exif = $this->get_exif($server_path);

    /*log_message("info","EXIF TEST---------------------------");
    log_message("info",var_export($exif,true));*/

    $this->resize_image($server_path, $server_path);
    // ファイル名を"全景①"の形に変換
    $picture_nm = '全景';
    if (21 > $new_picture_cd) {
      $picture_nm .= self::get_const('picture_nm_str', $new_picture_cd);
    } else {
      $picture_nm .= intval($new_picture_cd);
    }
    if (!isset($exif_dt)) {
      $exif_dt = $exif['date'];
    }
    if (!isset($use_flg)) {
      $use_flg = 1;
    }
    // DBへ登録
    $sql = <<<SQL
            insert into rfs_t_zenkei_picture (
                sno
              , shisetsu_cd
              , shisetsu_ver
              , struct_idx
              , zenkei_picture_cd
              , picture_nm
              , file_nm
              , path
              , up_dt
              , lat
              , lon
              , use_flg
              , del
              , exif_dt
              , description
            ) values(
              $sno
              ,'$shisetsu_cd'
              ,$shisetsu_ver
              ,$struct_idx
              ,$new_picture_cd
              ,'$picture_nm.jpg'
              ,'$path'
              ,'$path'
              ,now()
              ,{$exif['lat']}
              ,{$exif['lon']}
              ,{$use_flg}
              , $query_cd
              , '$exif_dt'
              , '$description'
            )
SQL;
//        log_message('debug', "sql = " . $sql);
    $query = $this->DB_rfs->query($sql);
  }

  /**
   * 点検写真を出力
   */
  function get_picture($param) {
    $chk_mng_no = $param['chk_mng_no'];
    $rireki_no = $param['rireki_no'];
    if (isset($param['query_cd'])) {
      $query_cd = $param['query_cd'];
      $result["query_cd"] = $query_cd;
    } else {
      $query_cd = 0;
      // 2-10000の間でランダムにcdを振る（0は削除しない、1はクライアント側で削除）
      $result["query_cd"] = rand(2, 10000);
    }

    $sql = <<<SQL
        select *
        from
            rfs_t_chk_picture
        where
            chk_mng_no = $chk_mng_no and
            rireki_no = $rireki_no and
            (del = $query_cd or del = 0 or del is null)
        order by picture_cd
SQL;
    $query = $this->DB_rfs->query($sql);
    $result["data"] = $query->result('array');
    return $result;
  }

  /**
   * 全景写真を出力
   */
  function get_picture_zenkei($param) {
    $sno = $param['sno'];
//      $shisetsu_cd =$param['shisetsu_cd'];
    //        $shisetsu_ver =$param['shisetsu_ver'];

    $struct_idx_where = "";
    if (isset($param['struct_idx'])) {
      $struct_idx = $param['struct_idx'];
      $struct_idx_where = "and struct_idx = $struct_idx ";
    }
    if (isset($param['query_cd'])) {
      $query_cd = $param['query_cd'];
      $result["query_cd"] = $query_cd;
    } else {
      $query_cd = 0;
      // 2-10000の間でランダムにcdを振る（0は削除しない、1はクライアント側で削除）
      $result["query_cd"] = rand(2, 10000);
    }
    // use_flgの判定
    if (!isset($param['use_flg'])) {
      $param['use_flg'] = [0, 1];
    }
    $use_flg = implode(",", $param['use_flg']);

    $sql = <<<SQL
        select *
        from
            rfs_t_zenkei_picture
        where
            sno = $sno
            $struct_idx_where
            and (del = $query_cd or del = 0 or del is null)
            and use_flg in ($use_flg)
        order by zenkei_picture_cd
SQL;
    $query = $this->DB_rfs->query($sql);
    $result["data"] = $query->result('array');

//        $r = print_r($result, true);
    //        log_message('debug', "return:".$r);

    return $result;
  }

  function save_fix_picture($param) {
    $chk_mng_no = $param['chk_mng_no'];
    $rireki_no = $param['rireki_no'];
    $data = $param['data'];
    for ($i = 0; $i < count($data); $i++) {
      $sql = <<<SQL
            update
                rfs_t_chk_picture
            set del = {$data[$i]['del']}
            where
                chk_mng_no = {$data[$i]['chk_mng_no']} and
                rireki_no = {$data[$i]['rireki_no']} and
                picture_cd = {$data[$i]['picture_cd']}
SQL;
      $query = $this->DB_rfs->query($sql);

    }
    // ごみの検索(delが0以外)
    $sql = <<<SQL
        select *
        from
            rfs_t_chk_picture
        where
            chk_mng_no = $chk_mng_no and
            rireki_no = $rireki_no and
            del != 0 and
            del is not null
SQL;
    $query = $this->DB_rfs->query($sql);
    $data = $query->result('array');

    // ファイルの削除
    for ($i = 0; $i < count($data); $i++) {
      $path = $data[$i]['path'];
      $server_path = $this->config->config['www_path'] . $path;
      unlink($server_path);
    }

    // ごみを削除
    $sql = <<<SQL
        delete
        from
            rfs_t_chk_picture
        where
            chk_mng_no = $chk_mng_no and
            rireki_no = $rireki_no and
            del != 0 and
            del is not null
SQL;
    $query = $this->DB_rfs->query($sql);
  }

  function save_fix_picture_zenkei($param) {
    // 全景写真の処理
    $sno = $param['sno'];
    $struct_idx_where = "";
/*
if(isset($param['struct_idx'])){
$struct_idx = $param['struct_idx'];
$struct_idx_where = "and struct_idx = $struct_idx ";
}
 */
    $data = $param['zenkei_picture_data'];
    for ($i = 0; $i < count($data); $i++) {
      if (!isset($data[$i]['description'])) {
        $data[$i]['description'] = '';
      }
      if (!isset($data[$i]['exif_dt'])) {
        $data[$i]['exif_dt'] = '';
      }

      $sql = <<<SQL
                update rfs_t_zenkei_picture
                    set del = {$data[$i]['del']}
                    , description = '{$data[$i]['description']}'
                    , exif_dt = '{$data[$i]['exif_dt']}'
                where
                    sno = $sno
                    -- $struct_idx_where
                    and use_flg = {$data[$i]['use_flg']}
                    and shisetsu_ver = {$data[$i]['shisetsu_ver']}
                    and struct_idx = {$data[$i]['struct_idx']}
                    and zenkei_picture_cd = {$data[$i]['zenkei_picture_cd']}
SQL;
      log_message('debug', "sql = " . $sql);
      $query = $this->DB_rfs->query($sql);

      // Note: 20191111 haramoto
      // 全景写真から緯度経度を保存する
      // 点検票の保存時でも施設の情報を更新したいらしいが、施設の更新は点検票の範疇を超えているため、仕方なくここに入れることにする
      if($data[$i]['lon'] && $data[$i]['lat']){
          $sql = <<<SQL
              UPDATE rfs_m_shisetsu 
                  SET
                  (lon, lat) = ( 
                      SELECT
                      coalesce(lon, {$data[$i]['lon']}) lon
                      , coalesce(lat, {$data[$i]['lat']}) lat 
                      FROM
                      rfs_m_shisetsu 
                      WHERE
                      sno = $sno
                  ) 
                  WHERE
                  sno = $sno
                  AND lon is null
SQL;
          $query = $this->DB_rfs->query($sql);
      }
  }
  // ごみの検索(delが0以外)
  $sql= <<<SQL
        select *
        from
            rfs_t_zenkei_picture
        where
            sno = $sno
            $struct_idx_where
            and del != 0
            and del is not null
SQL;
    $query = $this->DB_rfs->query($sql);
    $data = $query->result('array');

    // ファイルの削除
    for ($i = 0; $i < count($data); $i++) {
      $path = $data[$i]['path'];
      $server_path = $this->config->config['www_path'] . $path;
      unlink($server_path);
    }

    // ごみを削除
    $sql = <<<SQL
        delete
        from
            rfs_t_zenkei_picture
        where
            sno = $sno
            -- $struct_idx_where
            and del != 0
            and del is not null
SQL;
    $query = $this->DB_rfs->query($sql);
  }

  function get_gs_title_list($param) {
    $this->DB_imm = $this->load->database('imm', TRUE);

    $dogen_cd = $param['dogen_cd'];
    $syucchoujo_cd = $param['syucchoujo_cd'];

    if ($syucchoujo_cd == 0) {
      $where = "dogen_cd = $dogen_cd ";
    } else {
      $where = "syucchoujo_cd = $syucchoujo_cd ";
    }
    $sql = <<<SQL
       select
            *
        from
            gs_t_title
        where
            $where
        and (now() - interval '7 day') <= cast(updt_dt as timestamp)
        ORDER BY updt_dt desc
SQL;

//        $r = print_r($result, true);
    //        log_message('debug', "sql=$sql");
    //        log_message('debug', "result=$r");

    $query = $this->DB_imm->query($sql);
    $result["data"] = $query->result('array');
    return $result;
  }

  function get_gs_picture_list($param) {
    $this->DB_imm = $this->load->database('imm', TRUE);

    $dogen_cd = $param['dogen_cd'];
    $syucchoujo_cd = $param['syucchoujo_cd'];

    if ($syucchoujo_cd == 0) {
      $where = "dogen_cd = $dogen_cd";
    } else {
      $where = "syucchoujo_cd = $syucchoujo_cd";
    }
    $sql = <<<SQL
        select
            *
        from
            gs_t_pictures
        where
            $where and
            title_cd = {$param['title_cd']}
        ORDER BY picture_cd desc
        --limit 10
SQL;
    $query = $this->DB_imm->query($sql);
    $result["data"] = $query->result('array');
    for ($i = 0; $i < count($result["data"]); $i++) {
      $result["data"][$i]["src"] = $this->config->config['www_imm_path'] . $result["data"][$i]["path"];
    }
    return $result;
  }

  /**
   * オリジナル画像を別ファイルに保存してから、画像を圧縮する
  @param dst_file_name : ソースファイル　圧縮されて保存される
  @param src_file_name : オリジナル画像を保存するファイル名
   */
  public function resize_image($dst_filename, $src_filename) {
    try {

      require_once APPPATH . 'third_party/pel/autoload.php';
      $input_jpeg = new \lsolesen\pel\PelJpeg($src_filename);
      /* PelJpeg objectの内部のイメージバイトでイメージリソースを生成 */
      $src_img = ImageCreateFromString($input_jpeg->getBytes());
      $width = ImagesX($src_img);
      $height = ImagesY($src_img);

      /* リサイズの計算 */
      $min_width = 1024; // 幅の最低サイズ
      $min_height = 1024; // 高さの最低サイズ

      if ($width >= $min_width | $height >= $min_height) {
        if ($width == $height) {
          $new_width = $min_width;
          $new_height = $min_height;
        } else if ($width > $height) { //横長の場合
          $new_width = $min_width;
          $new_height = $height * ($min_width / $width);
        } else if ($width < $height) { //縦長の場合
          $new_width = $width * ($min_height / $height);
          $new_height = $min_height;
        }

        /* イメージのリサイズ */
        $dst_img = ImageCreateTrueColor($new_width, $new_height);
        ImageCopyResampled($dst_img, $src_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        /* JPEG data から PelJpeg object の生成 */
        $output_jpeg = new \lsolesen\pel\PelJpeg($dst_img);

        /* 元のファイルからEXIFデータを取得 */
        $exif = $input_jpeg->getExif();

        /* EXIFデータが存在すれば、リサイズされるデータに設定 */
        if ($exif != null) {
          $output_jpeg->setExif($exif);
        }
        /* リサイズ先ファイルに書き込む */
        file_put_contents($dst_filename, $output_jpeg->getBytes());
      }
    } catch (Excepstion $e) {
      // リサイズに失敗したら、そのままコピー
      copy($src_filename, $dst_filename);
    }

  }

  /**
   * Exif情報の取得
   */
  public function get_exif($file) {

    // 日付の取得
    $exif = @exif_read_data($file);

    $date = false; //date("Y/m/d H:i:s", filemtime($file));
    $dat = array();

    if (isset($exif) && isset($exif['DateTimeOriginal'])) {
      $date2 = $exif['DateTimeOriginal'];
      $r1 = explode(':', $date2);
      if (is_array($r1)) {
        if (count($r1) != 2) {
          $r2 = explode(' ', $date2);
          if (is_array($r2) && count($r2) == 2) {
            $r2[0] = str_replace(':', '/', $r2[0]);
            $date = implode(' ', $r2);
          }
        } else {
          $date = $date2;
        }
      }
    }
    if ($date && $date > "2000/01/01") {
      $dat['date'] = $date;
    } else {
      $dat['date'] = "";
    }

    // 緯度経度の取得
    $dat['lat'] = 0;
    $dat['lon'] = 0;

    $exif = @exif_read_data($file, 0, true);
/*
log_message("info","EXIF TEST2-----------------------------------------------");
log_message("info",$file);
log_message("info",var_export($exif,true));
 */

    if (isset($exif['GPS']) && isset($exif['GPS']['GPSLatitude']) && isset($exif['GPS']['GPSLongitude'])) {
      $lat = $this->gps_degree($exif['GPS']['GPSLatitude'], $exif['GPS']['GPSLatitudeRef']);
      $lng = $this->gps_degree($exif['GPS']['GPSLongitude'], $exif['GPS']['GPSLongitudeRef']);

      if ($lat && $lng) {
        $dat['lat'] = $lat;
        $dat['lon'] = $lng;
      }
    }
    return $dat;
  }

  public function gps_degree($gps, $ref) {
    if (count($gps) != 3) {
      return false;
    }

    if ($ref == 'S' or $ref == 'W') {
      $pm = -1;
    } else {
      $pm = 1;
    }
    $degree = $this->fraction2number($gps[0]);
    $minute = $this->fraction2number($gps[1]);
    $second = $this->fraction2number($gps[2]);
    return ($degree + $minute / 60.0 + $second / 3600.0) * $pm;
  }

  public function fraction2number($value) {
    try {
      $fraction = explode('/', $value);
      if (count($fraction) != 2) {
        return 0;
      }

      if ((double) $fraction[1] == 0) {
        $fraction[1] = 1;
      }

      return (double) $fraction[0] / (double) $fraction[1];
    } catch (Exception $ex) {
    }
    return 0;
  }

  /**
   * 定数を取得
   *
   * null なら空文字列を返す
   *
   * @param string|string[] $constant 静的プロパティ名または配列
   * @param int $value 配列キー
   * @return string
   */
  protected static function get_const($constant, $value) {
    if ($value === null) {
      return '';
    }

    if (is_array($constant)) {
      $ary = $constant;
    } else {
      $ary = self::${$constant};
    }

    return $ary[$value];
  }

  /**
   * 参考写真を保存する
   */
  public function save_fix_sankou_photo($photo_list, $list_cd = null) {
    if ($list_cd == null) {
      return;
    }

    // トランザクション開始
    $this->DB_rfs->trans_begin();

    // list_cdで保存されている写真を一度全削除
    // 実際の写真は削除データと引数のデータを比較して
    // 削除されているデータだけを削除しなければならないので、今はごみとしてのこりますわ。
    $sql = <<<SQL
    DELETE
    FROM
      t_pictures
    WHERE
      list_cd = '$list_cd'
SQL;
    $query = $this->DB_rfs->query($sql);

    for ($i = 0; $i < count($photo_list); $i++) {
      if (preg_match('/upload\/temp\/.*/', $photo_list[$i]['path'], $m)) {
        // 画像ファイルのコピー
        $photo_list[$i]['list_cd'] = $list_cd;
        $ext = pathinfo($photo_list[$i]['path'], PATHINFO_EXTENSION);
        $filename = "{$photo_list[$i]['list_cd']}_{$photo_list[$i]['owner_cd']}.${ext}";
        $path = "images/photos/sankou/{$this->config->config['image_path']}sankou/{$photo_list[$i]['list_cd']}/";
        $server_path = $this->config->config['www_path'] . $path;

        //「$directory_path」で指定されたディレクトリが存在するか確認
        if (!is_dir($server_path)) {
          //存在しないときの処理（「$directory_path」で指定されたディレクトリを作成する）
          mkdir($server_path, 0755, true);
        }
        $src_file = $this->config->config['www_path'] . $photo_list[$i]['path'];
        $dist_file = $server_path . $filename;
        copy($src_file, $dist_file);
        $photo_list[$i]['path'] = $path . $filename;
      }

      $photo_list[$i] = $this->escapeParam($photo_list[$i], array(
        "list_cd" => "ntext",
        "path" => "ntext",
        "exif_dt" => "ntext",
        "crt_dt" => "ntext",
        "description" => "ntext",
        "owner_cd" => "nint",
        "exif_out" => "nint",
      ));

      $sql = $this->upsertHelper(
        "t_pictures",
        "t_pictures_list_cd_unique",
        $photo_list[$i],
        [
          "list_cd",
          "path",
          "exif_dt",
          "crt_dt",
          "description",
          "owner_cd",
          "exif_out",
        ]
      );

      // SQLの実行
      $query = $this->DB_rfs->query($sql);

    }
    $this->DB_rfs->trans_complete();
    return;
  }

  /**
   * 参考写真を取得する
   */
  public function get_sankou_photo($list_cd) {
    if ($list_cd == null) {
      return;
    }

    $sql = <<<SQL
      SELECT
        picture_cd
        , list_cd
        , path
        , exif_dt
        , crt_dt
        , description
        , owner_cd
        , exif_out
      FROM
        t_pictures
      WHERE
        list_cd = '$list_cd'
      ORDER BY cast(owner_cd as int)
SQL;

    $query = $this->DB_rfs->query($sql);
    return $this->getArrayResultWithCast($query);
  }
}
