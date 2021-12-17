<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Userのモデル

 * @access public
 * @package Model
 */
class RfsTChkHuzokubutsuModel extends CI_Model {

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
  // protected $DB_imm;

  public function __construct() {
    parent::__construct();
    // rfs
    $this->DB_rfs = $this->load->database('rfs',TRUE);
    if ($this->DB_rfs->conn_id === FALSE) {
      log_message('debug', 'データベースに接続されていません');
      return;
    }
    // imm
    // $this->DB_imm = $this->load->database('imm',TRUE);
    // if ($this->DB_imm->conn_id === FALSE) {
    //   log_message('debug', '維持管理システムデータベースに接続されていません');
    //   return;
    // }
  }

  public function get_rfs_t_chk_huzokubutsu($data) {
    // $sno = $data['sno'];
    $rfsMShisetsuDatas = $data["rfsMShisetsuData"];

    $results = [];
    foreach ($rfsMShisetsuDatas as $rfsMShisetsuData) {
      if ($rfsMShisetsuData["chk_times"] > 0) {
        $result = $this->getRfsTChkHuzokubutsuBefore($rfsMShisetsuData["sno"]);
        if (count($result) > 0) {
          $results[] = $result[0];
        }
      }
    }
    // log_message('debug', print_r($results, true));
    return $results;
  }

  public function getRfsTChkHuzokubutsuBefore($sno) {

    $sql = <<<SQL
SELECT
 rfs_t_chk_main.chk_mng_no,
 rfs_t_chk_main.sno,
 rfs_t_chk_main.chk_times,
 rfs_t_chk_main.struct_idx,
 rfs_t_chk_main.target_dt,
 rfs_t_chk_main.busyo_cd,
 rfs_t_chk_main.kumiai_cd,
 rfs_t_chk_huzokubutsu.rireki_no,
 rfs_t_chk_huzokubutsu.chk_dt,
 rfs_t_chk_huzokubutsu.chk_company,
 rfs_t_chk_huzokubutsu.chk_person,
 rfs_t_chk_huzokubutsu.investigate_dt,
 rfs_t_chk_huzokubutsu.investigate_company,
 rfs_t_chk_huzokubutsu.investigate_person,
 rfs_t_chk_huzokubutsu.surface,
 rfs_t_chk_huzokubutsu.part_notable_chk,
 rfs_t_chk_huzokubutsu.reason_notable_chk,
 rfs_t_chk_huzokubutsu.special_report,
 rfs_t_chk_huzokubutsu.phase,
 rfs_t_chk_huzokubutsu.check_shisetsu_judge,
 rfs_t_chk_huzokubutsu.syoken,
 rfs_t_chk_huzokubutsu.update_dt,
 rfs_t_chk_huzokubutsu.measures_shisetsu_judge,
 rfs_t_chk_huzokubutsu.create_account
FROM
 rfs_t_chk_main
 INNER JOIN rfs_t_chk_huzokubutsu
 ON rfs_t_chk_main.chk_mng_no = rfs_t_chk_huzokubutsu.chk_mng_no
 AND rfs_t_chk_main.sno={$sno}
 AND rfs_t_chk_main.chk_times=(SELECT MAX(chk_times)-1 FROM rfs_t_chk_main WHERE sno={$sno})
 AND rfs_t_chk_huzokubutsu.rireki_no=(SELECT MAX(rfs_t_chk_huzokubutsu.rireki_no) FROM rfs_t_chk_main INNER JOIN rfs_t_chk_huzokubutsu ON rfs_t_chk_main.chk_mng_no = rfs_t_chk_huzokubutsu.chk_mng_no AND sno={$sno})
SQL;

    // log_message('debug', $sql);
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    // log_message('debug', print_r($result, true));
    return $result;
  }

  public function get_rfs_t_chk_huzokubutsu2($data) {
    // $chk_mng_no = $data['chk_mng_no'];
    $rfsMShisetsuDatas = $data["rfsMShisetsuData"];
    // $chk_mng_no_in = '';
    // foreach ($rfsMShisetsuDatas as $rfsMShisetsuData) {
      // $chk_mng_no_in .= $rfsMShisetsuData['chk_mng_no'] . ',';
    // }
    // if (strlen($chk_mng_no_in) != 0) {
      // $chk_mng_no_in = substr($chk_mng_no_in, 0, strlen($chk_mng_no_in) - 1);
    // }
    // log_message('debug', $chk_mng_no_in);

    $results = [];
    foreach ($rfsMShisetsuDatas as $rfsMShisetsuData) {
      $result = $this->getRfsTChkHuzokubutsu($rfsMShisetsuData["chk_mng_no"]);
      if (count($result) > 0) {
        $results[] = $result[0];
      }
    }
    // log_message('debug', print_r($results, true));
    return $results;
  }

  public function getRfsTChkHuzokubutsu($chk_mng_no) {

      $sql = <<<SQL
SELECT
 rfs_t_chk_main.chk_mng_no,
 rfs_t_chk_main.sno,
 rfs_t_chk_main.chk_times,
 rfs_t_chk_main.struct_idx,
 rfs_t_chk_main.target_dt,
 rfs_t_chk_main.busyo_cd,
 rfs_t_chk_main.kumiai_cd,
 rfs_t_chk_huzokubutsu.rireki_no,
 rfs_t_chk_huzokubutsu.chk_dt,
 rfs_t_chk_huzokubutsu.chk_company,
 rfs_t_chk_huzokubutsu.chk_person,
 rfs_t_chk_huzokubutsu.investigate_dt,
 rfs_t_chk_huzokubutsu.investigate_company,
 rfs_t_chk_huzokubutsu.investigate_person,
 rfs_t_chk_huzokubutsu.surface,
 rfs_t_chk_huzokubutsu.part_notable_chk,
 rfs_t_chk_huzokubutsu.reason_notable_chk,
 rfs_t_chk_huzokubutsu.special_report,
 rfs_t_chk_huzokubutsu.phase,
 rfs_t_chk_huzokubutsu.check_shisetsu_judge,
 rfs_t_chk_huzokubutsu.syoken,
 rfs_t_chk_huzokubutsu.update_dt,
 rfs_t_chk_huzokubutsu.measures_shisetsu_judge,
 rfs_t_chk_huzokubutsu.create_account
FROM
 rfs_t_chk_main
 INNER JOIN rfs_t_chk_huzokubutsu
 ON rfs_t_chk_main.chk_mng_no=rfs_t_chk_huzokubutsu.chk_mng_no
 AND rfs_t_chk_main.chk_mng_no={$chk_mng_no}
 AND rfs_t_chk_huzokubutsu.rireki_no=(SELECT MAX(rfs_t_chk_huzokubutsu.rireki_no) FROM rfs_t_chk_main INNER JOIN rfs_t_chk_huzokubutsu ON rfs_t_chk_main.chk_mng_no = rfs_t_chk_huzokubutsu.chk_mng_no AND rfs_t_chk_main.chk_mng_no={$chk_mng_no})
SQL;

    // log_message('debug', $sql);
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');
    // log_message('debug', print_r($result, true));
    return $result;
  }


  public function set_rfs_t_chk_huzokubutsu($data) {
    $rfsMShisetsuData = $data["rfsMShisetsuData"];
    $rfsTChkHuzokubutsuData = $data["rfsTChkHuzokubutsuData"];

    $chk_mng_no = $rfsMShisetsuData['chk_mng_no'];
    $rireki_no = $rfsTChkHuzokubutsuData['rireki_no'];
    $chk_company = $rfsTChkHuzokubutsuData['chk_company'];
    $chk_person = $rfsTChkHuzokubutsuData['chk_person'];
    $phase = $rfsTChkHuzokubutsuData['phase'];

    $this->DB_rfs->trans_start();

    $sql = <<<EOF
SELECT
 *
FROM
 rfs_t_chk_huzokubutsu
WHERE
 chk_mng_no={$chk_mng_no}
EOF;
    //log_message('debug', $sql);
    $query = $this->DB_rfs->query($sql);
    $result = $query->result('array');

    if (empty($result)) {
      $sql = <<<EOF
INSERT
INTO rfs_t_chk_huzokubutsu (
 chk_mng_no, rireki_no, chk_company, chk_person, phase
)
VALUES (
 {$chk_mng_no}, {$rireki_no}, '{$chk_company}', '{$chk_person}', {$phase}
)
EOF;
      // log_message('debug', $sql);
      $query = $this->DB_rfs->query($sql);
    }
    else {
      $sql = <<<EOF
UPDATE
 rfs_t_chk_huzokubutsu
SET
 chk_mng_no={$chk_mng_no}, rireki_no={$rireki_no}, chk_company='{$chk_company}', chk_person='{$chk_person}', phase={$phase}
WHERE
 chk_mng_no={$chk_mng_no}
EOF;
      // log_message('debug', $sql);
      $query = $this->DB_rfs->query($sql);
    }
  
    if ($this->DB_rfs->trans_status() === FALSE) {
      $this->DB_rfs->trans_rollback();
      return false;
    }

    $this->DB_rfs->trans_complete();
  }

  
  public function set_rfs_t_chk_huzokubutsu2($data) {
    $_rfsMShisetsuData = $data["rfsMShisetsuData"];
    $_rfsTChkHuzokubutsuData = $data["rfsTChkHuzokubutsuData"];
    $_pictureData = $data["pictureData"];

    $this->DB_rfs->trans_start();

log_message("debug","--------------data---------------->");
log_message("debug",print_r($data,true));

    foreach ($_rfsMShisetsuData as $rfsMShisetsuData) {
      foreach ($_rfsTChkHuzokubutsuData as $rfsTChkHuzokubutsuData) {
        if ($rfsMShisetsuData["chk_mng_no"] == $rfsTChkHuzokubutsuData["chk_mng_no"]) {

		  // picture_cdの最大を保持
	      $max_picture_cd = $this->getMaxPictureCd($rfsMShisetsuData["chk_mng_no"]);
        $chk_mng_no = $rfsTChkHuzokubutsuData["chk_mng_no"];
        $rireki_no = $rfsTChkHuzokubutsuData['rireki_no'];
        foreach ($_pictureData as $pictureData) {
            if ($rfsMShisetsuData["shisetsu_cd"] == $pictureData["shisetsu_cd"]) {
              $chk_mng_no = $rfsMShisetsuData["chk_mng_no"];
              $sno = $rfsMShisetsuData["sno"];
              $struct_idx = $rfsMShisetsuData["struct_idx"];

              $shisetsu_cd = $rfsMShisetsuData["shisetsu_cd"];
              $shisetsu_ver = $rfsMShisetsuData["shisetsu_ver"];
              //$zenkei_picture_cd = intval($pictureData["id"]);
			  $zenkei_picture_cd = 1; // 1固定にして上書きする
              $picture_cd = intval($pictureData["id"]);

              $keys = explode('-', $pictureData["key"]);
              $buzai_cd = $keys[2]; 
              $buzai_detail_cd = $keys[3];
              $tenken_kasyo_cd = $keys[4];

              if ($pictureData["type"] == 1) {
/*                $picture_nm = "全景".$pictureData["id"].".jpg";
                if ($pictureData["id"] < 21) {
                  $picture_nm = "全景".$picture_nm_str[$pictureData["id"]].".jpg";
                }
*/
                $picture_nm = "全景".self::get_const('picture_nm_str', $zenkei_picture_cd);
                $file_nm = "images/photos/zenkei/".$rfsMShisetsuData['syucchoujo_cd']."/".$sno."/".$rfsMShisetsuData['chk_mng_no']."/".$pictureData["key"].".jpg";
                $path = "images/photos/zenkei/".$rfsMShisetsuData['syucchoujo_cd']."/".$sno."/".$rfsMShisetsuData['chk_mng_no']."/".$pictureData["key"].".jpg";
        
                $base64 = $pictureData["img_base64"];
                $pathKey = ["photos", "zenkei", $rfsMShisetsuData['syucchoujo_cd'], $sno, $rfsMShisetsuData['chk_mng_no']];
                $fileName = $pictureData["key"];
                // $location = [$pictureData["latitude"], $pictureData["longitude"]];
                $location = null;
//                log_message('debug', $pathKey);
        
                $sql = <<<EOF
SELECT
 *
FROM
 rfs_t_zenkei_picture
WHERE
 sno={$sno} AND struct_idx={$struct_idx} AND zenkei_picture_cd = {$zenkei_picture_cd} AND use_flg=1
EOF;
//                log_message('debug', $sql);
                $query = $this->DB_rfs->query($sql);
                $result = $query->result('array');
                    
                if (empty($result)) {
                $sql = <<<EOF
INSERT
INTO rfs_t_zenkei_picture (
 sno, 
 shisetsu_cd, 
 shisetsu_ver, 
 struct_idx, 
 zenkei_picture_cd, 
 picture_nm, 
 file_nm, 
 path, 
 up_dt,
 use_flg
)
VALUES (
 {$sno}, 
 '{$shisetsu_cd}', 
 {$shisetsu_ver}, 
 {$struct_idx}, 
 {$zenkei_picture_cd}, 
 '{$picture_nm}', 
 '{$file_nm}', 
 '{$path}', 
 now(),
 1
)
EOF;
                // log_message('debug', $sql);
                $query = $this->DB_rfs->query($sql);
              }
              else {
                $sql = <<<EOF
UPDATE
 rfs_t_zenkei_picture
SET
-- sno={$sno}, 
-- shisetsu_cd='{$shisetsu_cd}', 
-- shisetsu_ver={$shisetsu_ver}, 
-- struct_idx={$struct_idx}, 
-- zenkei_picture_cd={$zenkei_picture_cd}, 
 picture_nm='{$picture_nm}', 
 file_nm='{$picture_nm}', 
 path='{$picture_nm}', 
 up_dt=now()
WHERE
 sno={$sno} AND struct_idx={$struct_idx} AND zenkei_picture_cd={$zenkei_picture_cd} AND use_flg=1
EOF;
//                  log_message('debug', $sql);
                  $query = $this->DB_rfs->query($sql);
                }
        
                $this->saveImage($base64, $pathKey, $fileName, $location);
              }
              else {
                // type=2: tenken

/*
                $picture_nm = "写真".$pictureData['id'].".jpg";
				//$picture_nm = "写真".$pictureData['id'].".jpg";
                if ($pictureData['id'] < 21) {
                  $picture_nm = "写真".$picture_nm_str[$pictureData['id']].".jpg";
                }
                $file_nm = "images/photos/tenken/".$rfsMShisetsuData['syucchoujo_cd']."/".$sno."/".$rfsMShisetsuData['chk_mng_no']."/".$pictureData["key"].".jpg";
                $path = "images/photos/tenken/".$rfsMShisetsuData['syucchoujo_cd']."/".$sno."/".$rfsMShisetsuData['chk_mng_no']."/".$pictureData["key"].".jpg";
        
                $base64 = $pictureData["img_base64"];
                $pathKey = ["photos", "tenken", $rfsMShisetsuData['syucchoujo_cd'], $sno, $rfsMShisetsuData['chk_mng_no']];
                $fileName = $pictureData["key"];
                // $location = [$pictureData["latitude"], $pictureData["longitude"]];
                $location = null;
*/

                $sql = <<<EOF
SELECT
 *
FROM
 rfs_t_chk_picture
WHERE
 chk_mng_no={$chk_mng_no} 
--AND picture_cd={$picture_cd} 
AND buzai_cd={$buzai_cd} AND buzai_detail_cd={$buzai_detail_cd} AND tenken_kasyo_cd={$tenken_kasyo_cd}
EOF;
//                log_message('debug', $sql);
                $query = $this->DB_rfs->query($sql);
                $result = $query->result('array');
            
                if (!empty($result)) {
                $sql = <<<EOF
UPDATE
 rfs_t_chk_picture
SET
del=1
WHERE
chk_mng_no={$chk_mng_no} 
--AND picture_cd={$picture_cd} 
AND buzai_cd={$buzai_cd} AND buzai_detail_cd={$buzai_detail_cd} AND tenken_kasyo_cd={$tenken_kasyo_cd}
EOF;

                //log_message('debug', $sql);
                $query = $this->DB_rfs->query($sql);
                }
/*
                $sql = <<<EOF
SELECT
 MAX(picture_cd)+1
FROM
 rfs_t_chk_picture
WHERE
 chk_mng_no={$chk_mng_no}
EOF;
                log_message('debug', $sql);
                $query = $this->DB_rfs->query($sql);
                $result = $query->result('array');

                log_message('debug', "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa");
                log_message('debug', $max_picture_cd);
                if (intval($result[0]) != 0) {
                  $max_picture_cd = intval($result[0]);
                }
                log_message('debug', $max_picture_cd);
*/
				$max_picture_cd ++;

                $picture_nm = "写真";
                if ($max_picture_cd < 21) {
				  $picture_nm .= self::get_const('picture_nm_str', $max_picture_cd);
                  //$picture_nm = "写真".$max_picture_cd.".jpg";
                } else {
                  $picture_nm .= intval($max_picture_cd);
			    }
				$status = $pictureData["status"];
/*				$picture_nm = "写真".$pictureData['id'].".jpg";
                if ($pictureData['id'] < 21) {
                  $picture_nm = "写真".$picture_nm_str[$pictureData['id']].".jpg";
                }
*/
				/*
                $file_nm = "images/photos/tenken/".$rfsMShisetsuData['syucchoujo_cd']."/".$sno."/".$rfsMShisetsuData['chk_mng_no']."/".$pictureData["key"].".jpg";
                $path = "images/photos/tenken/".$rfsMShisetsuData['syucchoujo_cd']."/".$sno."/".$rfsMShisetsuData['chk_mng_no']."/".$pictureData["key"].".jpg";
*/
                $file_nm = $max_picture_cd.".jpg";
                $path = "images/photos/tenken/".$rfsMShisetsuData['syucchoujo_cd']."/".$sno."/".$rfsMShisetsuData['chk_mng_no']."/".$max_picture_cd.".jpg";
        
                $base64 = $pictureData["img_base64"];
                $pathKey = ["photos", "tenken", $rfsMShisetsuData['syucchoujo_cd'], $sno, $rfsMShisetsuData['chk_mng_no']];
                //$fileName = $pictureData["key"];
				$fileName=$max_picture_cd;
                //$location = [$pictureData["latitude"], $pictureData["longitude"]];
                //$location = null;
				$lat=0;
                if($pictureData["latitude"]){
					$lat=$pictureData["latitude"];
				}
				$lon=0;
                if($pictureData["longitude"]){
					$lon=$pictureData["longitude"];
				}

                $sql = <<<EOF
INSERT
INTO rfs_t_chk_picture (
 chk_mng_no, 
 picture_cd, 
 buzai_cd, 
 buzai_detail_cd, 
 tenken_kasyo_cd, 
 picture_nm, 
 file_nm, 
 path, 
 up_dt,
 lat,
 lon,
 rireki_no,
 status, 
 del
)
VALUES (
 {$chk_mng_no}, 
 {$max_picture_cd},
 {$buzai_cd}, 
 {$buzai_detail_cd}, 
 {$tenken_kasyo_cd}, 
 '{$picture_nm}', 
 '{$file_nm}', 
 '{$path}', 
 now(),
 {$lat},
 {$lon},
 {$rireki_no},
 {$status},
 0
)
EOF;
//                log_message('debug', $sql);                
                $query = $this->DB_rfs->query($sql);

                $this->saveImage($base64, $pathKey, $fileName, $location);
              }
            }
          }
          // 写真のdel=1を削除
          $this->delPicture($chk_mng_no,$rireki_no);
        }
      }
    }

    if ($this->DB_rfs->trans_status() === FALSE) {
      $this->DB_rfs->trans_rollback();
      return false;
    }

    $this->DB_rfs->trans_complete();
  }

  private function getMaxPictureCd($chk_mng_no) {

      $sql = <<<EOF
SELECT
      COALESCE(MAX(picture_cd), 0) max_picture_cd 
  FROM
    rfs_t_chk_picture 
  WHERE
    chk_mng_no={$chk_mng_no}
EOF;
     $query = $this->DB_rfs->query($sql);
     $result = $query->result('array');
     return $result[0]['max_picture_cd'];
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

  private function delPicture($chk_mng_no, $rireki_no) {
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

  public function saveImage($base64, $pathKey, $fileName, $location = null) {
    // tmpファイル作成
    $img = base64_decode(preg_replace("/data:[^,]+,/i", '', $base64));
    $fp  = tmpfile();
    fwrite($fp, $img);
    fflush($fp);
    $tmp_path = stream_get_meta_data($fp)['uri'];

    // jpg画像かの確認
    if (exif_imagetype($tmp_path) !== IMAGETYPE_JPEG) {
      fclose($fp);
      return false;
    }

    // 座標埋め込み
    if ($location) {
      list($latitude, $longitude) = explode(',', $location);
      $this->addGpsInfo($tmp_path, $tmp_path, $latitude, $longitude);
    }

    // 保存先のPath
    // $path = $this->createImagePath($pathKey, $realPath = true);
    $path = $this->config->config['image_real_path'] . implode('/', $pathKey) . '/';
    
//    log_message('debug', $path);
    // ディレクトリがなければ作成
    is_dir($path) or mkdir($path, 0777, true);
    $path = $path . $fileName . '.jpg';
    // tmpファイルをコピー
    if (!copy($tmp_path, $path)) {
      fclose($fp);
      return false;
    }

    // tmp削除
    fclose($fp);

    // return $this->createImagePath($pathKey, $realPath = false) . $fileName . '.jpg';
    return $path . $fileName . '.jpg';
  }

  
  private function createImagePath($pathKey, $realPath = false) {
    $this->config->load('config');
    // return ($realPath ? $this->config->config['image_real_path'] : '') . implode('/', $pathKey) . '/';
    return $this->config->config['image_real_path'] . implode('/', $pathKey) . '/';
  }

  private function convertDecimalToDMS($degree) {
    if ($degree > 180 || $degree < -180) {
      return null;
    }

    $degree = abs($degree);

    $seconds = $degree * 3600;

    $degrees = floor($degree);
    $seconds -= $degrees * 3600;

    $minutes = floor($seconds / 60);
    $seconds -= $minutes * 60;

    $seconds = round($seconds * 100, 0);

    return [
      [
        $degrees,
        1,
      ],
      [
        $minutes,
        1,
      ],
      [
        $seconds,
        100,
      ],
    ];
  }
  
  private function addGpsInfo($input, $output, $latitude, $longitude) {
    $jpeg = new PelJpeg($input);

    $exif = new PelExif();
    $jpeg->setExif($exif);

    $tiff = new PelTiff();
    $exif->setTiff($tiff);

    $ifd0 = new PelIfd(PelIfd::IFD0);
    $tiff->setIfd($ifd0);

    $gpsIfd = new PelIfd(PelIfd::GPS);
    $ifd0->addSubIfd($gpsIfd);

    $gpsIfd->addEntry(new PelEntryByte(PelTag::GPS_VERSION_ID, 2, 2, 0, 0));

    list($hours, $minutes, $seconds) = $this->convertDecimalToDMS($latitude);

    $latitudeRef = ($latitude < 0) ? 'S' : 'N';

    $gpsIfd->addEntry(new PelEntryAscii(PelTag::GPS_LATITUDE_REF, $latitudeRef));
    $gpsIfd->addEntry(new PelEntryRational(PelTag::GPS_LATITUDE, $hours, $minutes, $seconds));

    list($hours, $minutes, $seconds) = $this->convertDecimalToDMS($longitude);
    $longitudeRef                    = ($longitude < 0) ? 'W' : 'E';

    $gpsIfd->addEntry(new PelEntryAscii(PelTag::GPS_LONGITUDE_REF, $longitudeRef));
    $gpsIfd->addEntry(new PelEntryRational(PelTag::GPS_LONGITUDE, $hours, $minutes, $seconds));

    file_put_contents($output, $jpeg->getBytes());
  }

  private function createShisetsuCd($daityouTbl, $rosenCd, $sno) {
    //区分アルファベット大文字2文字+00+路線コード+-+snoでお願いします。
    return strtoupper(substr($daityouTbl, -2)) . '00' . $rosenCd . '-' . $sno;
  }

  public function releaseTenkenLists($tenken_list_cds)
  {
    $this->db->select('teiki_patrol.tenken_lists.*')
            ->where_in('tenken_lists.tenken_list_cd', $tenken_list_cds);
    $tenken_list = $this->selectResult();

    if (count($tenken_list) === 0) {
      // 既にPC版から点検リストが削除済みであれば何もしない
      return true;
    }

    $this->db->trans_start();
    $this->db->where_in('tenken_list_cd', $tenken_list_cds);
    $this->db->update($this->table_name, ['downloaded_by' => self::DOWNLOADED_BY_DELETED]);
    $this->db->trans_complete();
    return !$this->db->trans_status() === FALSE;
  }

/***
 *
 * 附属物点検内容を保存する
 *   POST DATA
 *     rfsChkBuzaiData オブジェクト 対象の部材データ
 *
 ***/
  public function setRfsTChkHuzokubutsu($post) {
    // 未送信データを保存
    $not_edit=array();
	// 送信データを抽出
    $huzokubutsu_arr = $post['rfsTChkHuzokubutsuData'];
    for ($i=0;$i<count($huzokubutsu_arr);$i++) {
      $huzokubutsu=$huzokubutsu_arr[$i];
      // 削除依頼施設は廃止登録する（この後の処理は通常通り行う）
      $this->delShisetsu($huzokubutsu);
      // サーバデータチェック
	  $ret=$this->chkSvrData($huzokubutsu);
	  if ($ret["not_edit_id"]>0) {
        $huzokubutsu["not_edit_id"]=$ret["not_edit_id"];
        $huzokubutsu["not_edit_str"]=$ret["not_edit_str"];
		array_push($not_edit,$huzokubutsu);
        continue;
      }
	  // 登録処理
	  $this->upsertHuzokubutsu($huzokubutsu);
    }
	return $not_edit;
  }

/***
 *
 * 施設削除対象を確認し削除する
 *   huzokubutsu オブジェクト 附属物データ
 *   施設削除の場合、del_request_dtに日付が入る
 *   この値を用い、廃止登録を行う
 *   
 ***/
  public function delShisetsu($huzokubutsu) {
    // 削除要求日付を確認する
	  if (!isset($huzokubutsu['del_request_dt'])) {
      return;
    }

    // 削除要求日付があるが内容が空
	  // if ($huzokubutsu['del_request_dt']='') {
      // return;
    // }

    $chk_mng_no = $this->DB_rfs->escape($huzokubutsu['chk_mng_no']);
    // 削除要求日付が空の場合は施設有
    // $del_request_dt = $this->DB_rfs->escape($huzokubutsu['del_request_dt']);
    $del_request_dt = $huzokubutsu['del_request_dt'];
    // log_message('debug', 'del_request_dt');
    // log_message('debug', $del_request_dt);
    if (!$del_request_dt) {

      $sql = <<<EOF
UPDATE rfs_m_shisetsu 
  SET
    haishi = null
    , haishi_yyyy = null
  WHERE
    sno = ( 
      SELECT
            sno 
        FROM
          rfs_t_chk_main 
        WHERE
          chk_mng_no = {$chk_mng_no}
    );
EOF;

    }
    else {

      $sql = <<<EOF
UPDATE rfs_m_shisetsu 
  SET
    haishi = ( 
      SELECT
          wareki 
        FROM
          v_wareki_seireki 
        WHERE
          seireki = (
            CAST( 
              to_char(CAST('{$del_request_dt}' AS TIMESTAMP), 'YYYY') AS INTEGER
            )
          )
    ) 
    , haishi_yyyy = ( 
      CAST( 
        to_char(CAST('{$del_request_dt}' AS TIMESTAMP), 'YYYY') AS INTEGER
      )
    ) 
    , kyouyou_kbn = 0 
  WHERE
    sno = ( 
      SELECT
            sno 
        FROM
          rfs_t_chk_main 
        WHERE
          chk_mng_no = {$chk_mng_no}
    );
EOF;

    }

    // log_message('debug', $sql);
    $query = $this->DB_rfs->query($sql);
  }

/***
 *
 * 附属物情報を保存する
 *   huzokubutsu オブジェクト 附属物データ
 *   戻り値
 *   $ret
 *　　　not_edit_id:not_edit_str
 *      0：正常
 *      1：PC点検確定済
 *      2：上書き確認
 *      3：削除施設
 ***/
  public function chkSvrData($huzokubutsu) {
	$ret=array();
    $ret["not_edit_id"]=0;
    $ret["not_edit_str"]="正常";
	$shisetsu = $this->getShisetsuFromChkMngNo($huzokubutsu['chk_mng_no']);	// 施設データを取得
    // 削除施設
    if ($shisetsu['haishi_yyyy']!=null) {
	    $ret["not_edit_id"]=3;
	    $ret["not_edit_str"]="削除施設";
		return $ret;
    }
	$tenken = $this->getHuzokubutsu($huzokubutsu['chk_mng_no']);			// 点検データを取得
    // 点検データなし ⇒ 点検一発目とか削除後
    if ($tenken==null) {
		return $ret;
	}

	// rireki_noが変わっていない 更新日がサーバーの方が新しい場合は上書き確認
	if ($tenken['rireki_no']==$huzokubutsu['rireki_no']) {
/* rfsTChkHuzokubutsuDataにupdate_dtと比較できるものが無い 同期時のupdate_dtを送信頂く予定
      if ($tenken['update_dt']>$huzokubutsu['']) {
	    $ret["not_edit_id"]=2;
	    $ret["not_edit_str"]="上書き確認";
      }
*/
      return $ret;
    }
/*************************************************************************************************/
/* サーバー側が履歴番号低いことはなし（削除時のみ） これ以降は必ず履歴番号がサーバーの方が大きい */
/*************************************************************************************************/

    // PC点検確定済または上書き確認（履歴番号が小さい場合もあるので大小は無視）
    if ($tenken['phase']==1) {
	    $ret["not_edit_id"]=2;
	    $ret["not_edit_str"]="上書き確認";
        return $ret;
    } else {
        // 点検中以外は、点検が確定済なので全部こちら
	    $ret["not_edit_id"]=1;
	    $ret["not_edit_str"]="PC点検確定済";
        return $ret;
    }
  }

/***
 *
 * 附属物情報を取得する
 *   chk_mng_no 点検管理番号
 *
 ***/
  public function getHuzokubutsu($chk_mng_no) {
      $sql = <<<EOF
SELECT
      * 
  FROM
    rfs_t_chk_huzokubutsu 
  WHERE
    chk_mng_no = $chk_mng_no 
  ORDER BY
    rireki_no DESC 
  LIMIT
    1
EOF;
     $query = $this->DB_rfs->query($sql);
     $result = $query->result('array');
     if (empty($result)) {
       return null;
	 }
     return $result[0];
  }

/***
 *
 * 附属物情報を保存する
 *   huzokubutsu オブジェクト 附属物データ
 *
 ***/
  public function upsertHuzokubutsu($huzokubutsu) {
	  $huzokubutsu['update_dt'] = date("Y/m/d H:i:s");
      // 初見を自動設定
      if ($huzokubutsu['check_shisetsu_judge']==1) {
        $huzokubutsu['syoken'] = '現時点で健全である（健全性Ⅰ）';
      } else if ($huzokubutsu['check_shisetsu_judge']==2) {
        $huzokubutsu['syoken'] = '軽微な損傷のため、特に問題ないが措置を講ずることが望ましい（健全性Ⅱ）';
      } else if ($huzokubutsu['check_shisetsu_judge']==3) {
        $huzokubutsu['syoken'] = '構造物の機能に支障が生じる可能性があるため、早期に措置を講ずべき（健全性Ⅲ）';
      } else if ($huzokubutsu['check_shisetsu_judge']==4) {
        $huzokubutsu['syoken'] = '構造物の機能に支障が生じているため、緊急に措置を講ずべき（健全性Ⅳ）';
	  } else {
        $huzokubutsu['syoken'] = 'その他（PCにて詳細を入力）';
      }

      $huzokubutsu = $this->escapeParam($huzokubutsu, array(
            "chk_mng_no" => "nint",
            "rireki_no" => "nint",
            "chk_dt" => "ntext",
            "chk_company" => "ntext",
            "chk_person" => "ntext",
            "phase" => "nint",
            "check_shisetsu_judge" => "nint",
            "syoken" => "ntext",
            "update_dt" => "ntext",
            "measures_shisetsu_judge" => "nint",
            "create_account" => "nint"
        ));
	  $sql = $this->upsertHelper(
            "public.rfs_t_chk_huzokubutsu",
            "rfs_t_chk_huzokubutsu_pkey",
            $huzokubutsu,
            [
	            "chk_mng_no",
	            "rireki_no",
	            "chk_dt",
	            "chk_company",
	            "chk_person",
	            "phase",
	            "check_shisetsu_judge",
                "syoken",
	            "update_dt",
	            "measures_shisetsu_judge",
	            "create_account"
            ]
        );
//        log_message('debug', $sql);
      $query = $this->DB_rfs->query($sql);
  }

/***
 *
 * 写真を保存する
 *   POST DATA
 *     pictureData オブジェクト 対象の写真データ
 *
 ***/
  public function setRfsTChkPicture($post) {
	// 送信データを抽出
    $picture_arr = $post['pictureData'];
    for ($i=0;$i<count($picture_arr);$i++) {
      $picture = $picture_arr[$i];
      if ($picture['type']==1) { // 全景
        $this->editZenkei($picture);
      }else{ // 点検
        $this->editTenken($picture);
      }
    }
  }

/***
 *
 * 全景写真を保存する
 *     picture 配列 
 ***/
  public function editZenkei($picture) {
	// DB登録
    $param=$this->editZenkeiData($picture);
	// ファイル登録
  	$this->saveImageFile($param["img_base64"],$param["path_dir"],$param["file_nm"]);
  }

/***
 *
 * 全景写真を保存する
 *     picture 配列 
 ***/
  public function editTenken($picture) {
	// DB登録
    $param = $this->editTenkenData($picture);
	// ファイル登録
  	$this->saveImageFile($param["img_base64"],$param["path_dir"],$param["file_nm"]);
  }

/***
 *
 * 全景写真のデータを保存する
 *     picture 配列 
 ***/
  public function editZenkeiData($picture) {
	// 施設情報取得
    $shisetsu=$this->getShisetsu($picture['sno']);
    $picture['shisetsu_ver']=$shisetsu['shisetsu_ver'];
	// 最大のzenkei_picture_cd取得
    $max_zenkei_picture_cd=$this->getMaxZenkeiPictureCd($picture['sno'],$picture['struct_idx']);
    $picture['zenkei_picture_cd']=$max_zenkei_picture_cd+1;
    $picture['use_flg'] = 1;
    $picture['del'] = 0;
	$picture_nm = "全景".$zenkei_picture_cd;
	if ($zenkei_picture_cd<=20) {
		$picture_nm = "全景".self::get_const('picture_nm_str', $zenkei_picture_cd);
    }
    $picture['picture_nm']=$picture_nm;
    $picture['file_nm'] = $picture['shisetsu_cd']."-".$picture['struct_idx']."-".$picture['zenkei_picture_cd'].".jpg";
    $picture['path'] = "images/photos/zenkei/".$shisetsu['syucchoujo_cd']."/".$picture['sno']."/".$picture['file_nm'];
    $picture['path_dir'] = "images/photos/zenkei/".$shisetsu['syucchoujo_cd']."/".$picture['sno']."/";
    $picture['up_dt'] = date("Y/m/d H:i:s");
    $picture['lat'] = $picture['latitude'];
    $picture['lon'] = $picture['longitude'];
    // use_flg=1の該当データを削除する
	$this->delZenkeiPicture($picture['sno'],$picture['struct_idx'],1);
	$sql_picture = $this->escapeParam($picture, array(
	    "sno" => "nint",
	    "shisetsu_cd" => "ntext",
	    "shisetsu_ver" => "nint",
	    "struct_idx" => "nint",
	    "zenkei_picture_cd" => "nint",
	    "picture_nm" => "ntext",
	    "file_nm" => "ntext",
	    "path" => "ntext",
	    "up_dt" => "ntext",
	    "lat" => "nfloat",
	    "lon" => "nfloat",
	    "use_flg" => "nint",
	    "del" => "nint",
	    "exif_dt" => "ntext",
	));
	$sql = $sql = $this->upsertHelper(
	    "public.rfs_t_zenkei_picture",
	    "rfs_t_zenkei_picture_pkey",
	    $sql_picture,
	    [
		    "sno",
		    "shisetsu_cd",
		    "shisetsu_ver",
		    "struct_idx",
		    "zenkei_picture_cd",
		    "picture_nm",
		    "file_nm",
		    "path",
		    "up_dt",
		    "lat",
		    "lon",
		    "use_flg",
		    "del",
		    "exif_dt"
	    ]
	);
	// log_message('debug', $sql);
	$query = $this->DB_rfs->query($sql);
    return $picture;
  }

/***
 *
 * 点検/措置写真のデータを保存する
 *     picture 配列 
 ***/
  public function editTenkenData($picture) {
	
	$key_arr = explode("-", $picture['key']);
	$picture['chk_mng_no']=$key_arr[0];
	$picture['rireki_no']=$key_arr[1];
	$picture['buzai_cd']=$key_arr[2];
	$picture['buzai_detail_cd']=$key_arr[3];
	$picture['tenken_kasyo_cd']=$key_arr[4];
	// 施設情報取得
    $shisetsu=$this->getShisetsuFromChkMngNo($picture['chk_mng_no']);

	// 該当の写真のdelに1立て
    $this->updDel($picture);
    $max_picture_cd = $this->getMaxPictureCd($picture['chk_mng_no']);
    $picture['picture_cd']=$max_picture_cd+1;
	$picture_nm = "写真";
	if ($max_picture_cd < 21) {
	  $picture_nm .= self::get_const('picture_nm_str', $picture['picture_cd']);
	} else {
	  $picture_nm .= intval($picture['picture_cd']);
	}
	$picture['picture_nm'] = $picture_nm;
    $picture['file_nm'] = $picture['picture_cd'].".jpg";
    $picture['path'] = "images/photos/tenken/".$shisetsu['syucchoujo_cd']."/".$shisetsu['sno']."/".$picture['chk_mng_no']."/".$picture['file_nm'];
    $picture['path_dir'] = "images/photos/tenken/".$shisetsu['syucchoujo_cd']."/".$shisetsu['sno']."/".$picture['chk_mng_no']."/";
    $picture['up_dt'] = date("Y/m/d H:i:s");
    $picture['lat'] = $picture['latitude'];
    $picture['lon'] = $picture['longitude'];
    $picture['del'] = 0;

	$sql_picture = $this->escapeParam($picture, array(
	    "chk_mng_no" => "nint",
	    "picture_cd" => "nint",
	    "buzai_cd" => "nint",
	    "buzai_detail_cd" => "nint",
	    "tenken_kasyo_cd" => "nint",
	    "picture_nm" => "ntext",
	    "file_nm" => "ntext",
	    "path" => "ntext",
	    "up_dt" => "ntext",
	    "lat" => "nfloat",
	    "lon" => "nfloat",
	    "rireki_no" => "nint",
	    "status" => "nint",
	    "del" => "nint"
	));
	$sql = $this->upsertHelper(
	    "public.rfs_t_chk_picture",
	    "rfs_t_chk_picture_pkey",
	    $sql_picture,
	    [
		    "chk_mng_no",
		    "picture_cd",
		    "buzai_cd",
		    "buzai_detail_cd",
		    "tenken_kasyo_cd",
		    "picture_nm",
		    "file_nm",
		    "path",
		    "up_dt",
		    "lat",
		    "lon",
		    "rireki_no",
		    "status",
		    "del"
	    ]
	);
	// log_message('debug', $sql);
	$query = $this->DB_rfs->query($sql);

	// 写真のdel=1を削除
	$this->delPicture($picture['chk_mng_no'],$picture['rireki_no']);
	return $picture;
  }

/***
 *
 * 写真を保存する
 *     
 *     $base64 写真Base64文字列
 *     $path_dir images以下のdir
 *     $file_nm ファイル名
 *
 ***/
  public function saveImageFile($base64, $path_dir, $file_nm) {
    // tmpファイル作成
    $img = base64_decode(preg_replace("/data:[^,]+,/i", '', $base64));
    $fp  = tmpfile();
    fwrite($fp, $img);
    fflush($fp);
    $tmp_path = stream_get_meta_data($fp)['uri'];
    // jpg画像かの確認
    if (exif_imagetype($tmp_path) !== IMAGETYPE_JPEG) {
      fclose($fp);
      return false;
    }
    // 保存先のPath
    $path = $this->config->config['image_real_path'] . $path_dir;
    // ディレクトリがなければ作成
    is_dir($path) or mkdir($path, 0777, true);
    // tmpファイルをコピー
    if (!copy($tmp_path, $path.$file_nm)) {
      fclose($fp);
      return false;
    }
    // tmp削除
    fclose($fp);
  }

/***
 *
 * 施設情報を取得する
 * 引数：sno
 ***/
  private function getShisetsu($sno) {

      $sql = <<<EOF
SELECT
	*
  FROM
    rfs_m_shisetsu
  WHERE
    sno=$sno;
EOF;
     $query = $this->DB_rfs->query($sql);
     $result = $query->result('array');
     return $result[0];
  }

/***
 *
 * 施設情報を取得する（点検管理番号から）
 * 引数：chk_mng_no
 ***/
  private function getShisetsuFromChkMngNo($chk_mng_no) {

      $sql = <<<EOF
SELECT
      * 
  FROM
    rfs_m_shisetsu 
  WHERE
    sno = ( 
      SELECT
            sno 
        FROM
          rfs_t_chk_main 
        WHERE
          chk_mng_no = $chk_mng_no
    );
EOF;
     $query = $this->DB_rfs->query($sql);
     $result = $query->result('array');
     return $result[0];
  }

/***
 *
 * 全景写真の最大zenkei_picture_cdを取得する
 * 引数：sno,struct_idx
 ***/
  private function getMaxZenkeiPictureCd($sno,$struct_idx) {

      $sql = <<<EOF
SELECT
      COALESCE(MAX(zenkei_picture_cd), 0) max_zenkei_picture_cd 
  FROM
    rfs_t_zenkei_picture 
  WHERE
    sno=$sno
    AND struct_idx=$struct_idx;
EOF;
     $query = $this->DB_rfs->query($sql);
     $result = $query->result('array');
     return $result[0]['max_zenkei_picture_cd'];
  }

/***
 *
 * 全景写真の最大zenkei_picture_cdを取得する
 * 引数：sno,struct_idx,use_flg
 ***/
  private function delZenkeiPicture($sno,$struct_idx,$use_flg) {

      $sql = <<<EOF
DELETE 
  FROM
    rfs_t_zenkei_picture 
  WHERE
    sno=$sno
    AND struct_idx=$struct_idx
    AND use_flg=$use_flg;
EOF;
     $query = $this->DB_rfs->query($sql);
  }

/***
 * 該当の写真が既にあった場合delをセット
 * 引数：写真情報
 ***/
  private function updDel($picture) {
      $sql = <<<EOF
SELECT
      * 
  FROM
    rfs_t_chk_picture 
  WHERE
    chk_mng_no = {$picture["chk_mng_no"]} 
    AND buzai_cd = {$picture["buzai_cd"]} 
    AND buzai_detail_cd = {$picture["buzai_detail_cd"]}
    AND tenken_kasyo_cd = {$picture["tenken_kasyo_cd"]}
EOF;
     $query = $this->DB_rfs->query($sql);

     if (!empty($result)) {
       $sql = <<<EOF
UPDATE 
  rfs_t_chk_picture 
  SET
    del = 1 
  WHERE
    chk_mng_no = {$picture["chk_mng_no"]} 
    AND buzai_cd = {$picture["buzai_cd"]} 
    AND buzai_detail_cd = {$picture["buzai_detail_cd"]}
    AND tenken_kasyo_cd = {$picture["tenken_kasyo_cd"]}
EOF;
	  //log_message('debug', $sql);
	  $query = $this->DB_rfs->query($sql);
	}
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

}
