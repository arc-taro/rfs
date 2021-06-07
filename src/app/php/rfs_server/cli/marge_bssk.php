<?php
// php -d "memory_limit=512M" calc_kukan_link.php
//
// Excelを出力するプログラム。
//

const URL = 'https://www.mainte-managementsys-hokkaido.info/rfs/api/index.php/CliController/merge_to_bssk_parent?';

// 処理
main($argc,$argv);
exit(0);

// メイン関数
function main($argc,$argv){

  if($argc != 2){
    echo "php marge_bssk.php [csv_file_name] \n";
    return;
  }

  try {
    $filepath=$argv[1];
    echo "$filepath \n";

    if (($handle = fopen($filepath, "r")) !== false) {
      // ヘッダ読み込み
      $line = fgetcsv($handle, 1000, ",");
      $head = $line;

      // 処理
      while (($line = fgetcsv($handle, 1000, ",")) !== false) {
        $records[] = $line;
        $param = [];
        for($i = 0 ; $i < count($line);$i++){
          $param[] = "{$head[$i]}={$line[$i]}";
        }
        $url = URL.implode('&',$param);
        echo "$url\n";
        file_get_contents($url);
      }
      fclose($handle);
    }

  } catch (RecordNotFoundException $e) {
    echo $e->getMessage();
  }
}
