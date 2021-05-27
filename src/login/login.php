<?php
require_once 'db.php';

try {
  $db = new DB();

  if (isset($_POST['account'])) {
    $account = pg_escape_string($_POST['account']);
  }

  if (isset($_POST['pass'])) {
    $pass = pg_escape_string($_POST['pass']);
  }

  $sql = <<<SQL
SELECT
  ac_m_account.account_cd,
  ac_m_account.user_id,
  ac_m_account.password ,
  ac_m_busyo.busyo_cd ,
  ac_m_busyo.dogen_cd ,
  ac_m_busyo.syucchoujo_cd ,
  ac_m_busyo.syozoku_cd ,
  ac_m_busyo.syozoku_sub_cd ,
  ac_m_syozoku.syozoku_mei
FROM
  ac_m_account
  NATURAL JOIN ac_m_busyo
  NATURAL JOIN ac_m_syozoku
where
    user_id = '{$account}'
    AND password = '{$pass}'
SQL;

  $result = $db->query($sql);

  if (count($result) == 0) {
    // ログインに失敗
    header('Location: index.html');
    exit;
  }

  session_start(); //セッションスタート
  $_SESSION["ath"] = $result[0];
  $_SESSION["mngarea"]["syucchoujo_cd"] = $result[0]["syucchoujo_cd"];
  $_SESSION["mngarea"]["dogen_cd"] = $result[0]["dogen_cd"];

  header('Location: logined.html');

} catch (Exception $e) {
  header('Location: index.html');
  exit;
}
