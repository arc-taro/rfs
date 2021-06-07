<?php

/**
 * 画像用のモデル
 *
 * @access public
 * @package Model
 */
class ZumenModel extends CI_Model {

  protected $DB_rfs;
  /**
     * コンストラクタ
     *
     * model SchCheckを初期化する。
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
   * 図面を取得する。
   */
  public function getZumen($param){

    $sql= <<<SQL
      SELECT
        sno
        , zumen_id
        , path
        , exif_dt
        , update_dt
        , description
      FROM
        rfs_t_zumen
      WHERE
        sno = {$param['sno']}
      ORDER BY cast(zumen_id as int)
SQL;

    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    return $result;
  }

  /**
   * 図面を保存する。
   */
  public function saveZumen($param){
    if(!isset($param['sno'])){
      return;
    }else{
      $param['sno'] = pg_escape_string( $param['sno']);
    }

    // トランザクション開始
    $this->DB_rfs->trans_begin();

    $sql = <<<SQL
        DELETE
        FROM rfs_t_zumen
        WHERE
          sno={$param["sno"]}
SQL;
    log_message("DEBUG",$sql);
    $query = $this->DB_rfs->query($sql);

    if(!isset($param['path'])){
      $this->DB_rfs->trans_complete();
      return ;
    }
      if(preg_match('/upload\/temp\/.*/', $param['path'], $m)){
        // syucchoujo_cdの取得
        $sql = "SELECT * FROM rfs_m_shisetsu where sno={$param["sno"]} limit 1";
        $query = $this->DB_rfs->query($sql);
        $data = $query->result('array');
        $syucchoujo_cd = $data[0]["syucchoujo_cd"];

        // 画像ファイルのコピー
        $ext = pathinfo($param['path'], PATHINFO_EXTENSION);
        $filename = "{$param['sno']}_{$param['zumen_id']}.${ext}";

        $path = "images/photos/zumen/$syucchoujo_cd/{$param['sno']}/";
        $server_path = $this->config->config['www_path'].$path;
        //「$directory_path」で指定されたディレクトリが存在するか確認
        if(!is_dir($server_path)){
          //存在しないときの処理（「$directory_path」で指定されたディレクトリを作成する）
          mkdir($server_path, 0777,true);
        }
        $src_file = $this->config->config['www_path'].$param['path'];
        $dist_file = $server_path.$filename;
        copy( $src_file, $dist_file);
        $param['path'] = $path.$filename;
      }

      $sql= <<<SQL
          INSERT INTO
            rfs_t_zumen(
              sno
              , zumen_id
              , path
              , exif_dt
              , update_dt
              , description
            )
            values(
              {$param['sno']},
              {$param['zumen_id']},
              '{$param['path']}',
              '{$param['exif_dt']}',
              now(),
              '{$param['description']}'
            )
SQL;
    $query = $this->DB_rfs->query($sql);

    $this->DB_rfs->trans_complete();
    return ;
  }

  /**
   * 9号様式の写真を取得する。
   */
  public function getForm9Picture($houkokusyo_cd){
    $sql= <<<SQL
      SELECT
        picture_cd
        , list_cd
        , path
        , exif_dt
        , crt_dt
        , description
        , owner_cd
      FROM
        t_pictures
      WHERE
        list_cd = 'KASEN9_$houkokusyo_cd'
      ORDER BY cast(owner_cd as int)
SQL;

    $query = $this->DB_imm->query($sql);
    $result = $query->result('array');
    return $result;
  }


}
