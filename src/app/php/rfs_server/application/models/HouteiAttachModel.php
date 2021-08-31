<?php

/**
 * 添付ファイル用のモデル
 *
 * @access public
 * @package Model
 */
class HouteiAttachModel extends CI_Model
{

  protected $rfs;
  /**
   * コンストラクタ
   *
   * model SchCheckを初期化する。
   */
  public function __construct()
  {
    parent::__construct();
    $this->rfs = $this->load->database('rfs', true);
    if ($this->rfs->conn_id === FALSE) {
      log_message('debug', 'データベースに接続されていません');
      return;
    }
  }

  public function saveAttach($sno, $attach_list)
  {
    // トランザクション開始
    $this->rfs->trans_begin();

    // ファイルが削除される場合もあるため、既にあるレコードを一旦削除
    $sql = <<<SQL
    DELETE
    FROM rfs_t_houtei_attachfile
    WHERE
      sno = $sno
SQL;
    log_message("DEBUG", $sql);
    $query = $this->rfs->query($sql);

    for ($i = 0; $i < count($attach_list); $i++) {
      // 一時ファイルの場合は本来のパスに移動させる
      if (preg_match('/upload\/temp\/.*/', $attach_list[$i]['file_path'], $m)) {
        // 添付ファイルのコピー
        $ext = pathinfo($attach_list[$i]['file_path'], PATHINFO_EXTENSION);
        $filename = $attach_list[$i]['file_name'];
        $path = "{$this->config->config['houtei_attach_path']}$sno/";
        $server_path = $this->config->config['www_path'] . $path;
        //「$directory_path」で指定されたディレクトリが存在するか確認
        if (!is_dir($server_path)) {
          //存在しないときの処理（「$directory_path」で指定されたディレクトリを作成する）
          mkdir($server_path, 0777, true);
        }
        $src_file = $this->config->config['www_path'] . $attach_list[$i]['file_path'];
        $dist_file = $server_path . $filename;
        log_message('info', '$src_file=' . $src_file);
        log_message('info', '$dist_file=' . $dist_file);
        copy($src_file, $dist_file);
        $attach_list[$i]['file_path'] = $path . $filename;
      }

      $attach_list[$i]["file_type"] = mime_content_type($this->config->config['www_path'] . $attach_list[$i]["file_path"]);
      $attach_list[$i]["file_size"] = filesize($this->config->config['www_path'] . $attach_list[$i]["file_path"]);

      $sql = <<<SQL
INSERT INTO
  rfs_t_houtei_attachfile(
    sno
    , chk_mng_no
    , comment
    , file_type
    , file_size
    , file_path
    , updt_dt
  )
  values(
    $sno,
    '{$attach_list[$i]['chk_mng_no']}',
    '{$attach_list[$i]['comment']}',
    '{$attach_list[$i]['file_type']}',
    {$attach_list[$i]['file_size']},
    '{$attach_list[$i]['file_path']}',
    '{$attach_list[$i]['updt_dt']}'
  )
SQL;
      $query = $this->rfs->query($sql);
    }
    $this->rfs->trans_complete();
    return;
  }
}
