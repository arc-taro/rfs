# 道路施設管理システムの開発環境構築手順について

# 1. 各種ソフトウェアをインストールする

1. [Docker Desktop for Windows or Mac](https://www.docker.com/products/docker-desktop)をインストールしてください。
※参照 https://qiita.com/zaki-lknr/items/db99909ba1eb27803456
2. rfs フォルダを docker が稼働するディレクトリに配置します。
3. `rfs/src/app/node/`配下に gitlab から`rfs_client`プロジェクトを`git clone`してください。
4. `rfs/src/app/php/`配下に gitlab から`rfs_server`プロジェクトを`git clone`してください。

# 2. 設定ファイル変更

以下ファイルを変更してください。
※ファイル編集を nano でおこなっていますが、環境に合わせて読み替えてください

```
$ cd rfs/src/app/php/rfs_server/application/config/development
$ nano database.php
```

database.php

```
<?php defined('BASEPATH') or exit('No direct script access allowed');

$active_group = 'rfs';
$active_record = true;

$db['rfs']['hostname'] = 'rfs_postgres_server'; ←★★★ここを変更★★★
$db['rfs']['username'] = 'postgres';
$db['rfs']['password'] = 'postgres';
$db['rfs']['database'] = 'rfs';
$db['rfs']['dbdriver'] = 'postgre';
$db['rfs']['dbprefix'] = '';
$db['rfs']['pconnect'] = false;
$db['rfs']['db_debug'] = true;
$db['rfs']['cache_on'] = false;
$db['rfs']['cachedir'] = '';
$db['rfs']['char_set'] = 'utf8';
$db['rfs']['dbcollat'] = 'utf8_general_ci';
$db['rfs']['swap_pre'] = '';
$db['rfs']['autoinit'] = true;
$db['rfs']['stricton'] = false;
$db['rfs']['port'] = 5432;

$db['imm']['hostname'] = 'rfs_postgres_server'; ←★★★ここを変更★★★
$db['imm']['username'] = 'postgres';
$db['imm']['password'] = 'postgres';
$db['imm']['database'] = 'imm';
$db['imm']['dbdriver'] = 'postgre';
$db['imm']['dbprefix'] = '';
$db['imm']['pconnect'] = false;
$db['imm']['db_debug'] = true;
$db['imm']['cache_on'] = false;
$db['imm']['cachedir'] = '';
$db['imm']['char_set'] = 'utf8';
$db['imm']['dbcollat'] = 'utf8_general_ci';
$db['imm']['swap_pre'] = '';
$db['imm']['autoinit'] = true;
$db['imm']['stricton'] = false;
$db['imm']['port'] = 5432;
```

```
$ cd rfs/src/app/php/rfs_server/application/config/development
$ nano config.php
```

config.php

```
$config['base_url'] = 'http://localhost:8080/rfs/'; ←★★★ここを変更★★★
$config['www_path'] = '/var/www/html/'; ←★★★ここを変更★★★
$config['www_imm_path'] = 'http://localhost:8080/imm3/html/'; ←★★★ここを変更★★★
$config['back_imm_url'] = 'http://localhost:8080/imm3/html//rmm/home'; ←★★★ここを変更★★★
$config['www_ele_path'] = "/ele/"; ←★★★ここを変更★★★
$config['attach_path'] = 'images/photos/gdh/'; ←★★★ここを変更★★★
```

# 3. docker の起動

各サーバを起動します。
※初回は本システムに必要な DB の作成やマスタデータ等の登録を行います。

```
// docker-composeファイルが存在するディレクトリに移動
$ cd rfs

// 初回起動時
$ docker-compose up --build -d

// 2回目以降
$ docker-compose up -d

//リスタート
docker-compose restart

```

※初回起動時は以下のログが出力されていれば DB の初期インポートが完了しています。

ログ確認コマンド

```
$ docker-compose logs -f

app_node_1   |         + 48 hidden modulesがでるまで待つ
```

ログ内容(DB の初期インポートが完了時のログ)

```
postgres_1   | PostgreSQL Database directory appears to contain a database; Skipping initialization
postgres_1   |
postgres_1   | 2021-05-21 10:45:36.458 JST [1] LOG:  starting PostgreSQL 13.1 (Debian 13.1-1.pgdg100+1) on x86_64-pc-linux-gnu, compiled by gcc (Debian 8.3.0-6) 8.3.0, 64-bit
postgres_1   | 2021-05-21 10:45:36.459 JST [1] LOG:  listening on IPv4 address "0.0.0.0", port 5432
postgres_1   | 2021-05-21 10:45:36.459 JST [1] LOG:  listening on IPv6 address "::", port 5432
postgres_1   | 2021-05-21 10:45:36.463 JST [1] LOG:  listening on Unix socket "/var/run/postgresql/.s.PGSQL.5432"
postgres_1   | 2021-05-21 10:45:36.472 JST [28] LOG:  database system was interrupted; last known up at 2021-05-21 10:42:56 JST
postgres_1   | 2021-05-21 10:45:40.321 JST [28] LOG:  database system was not properly shut down; automatic recovery in progress
postgres_1   | 2021-05-21 10:45:40.436 JST [28] LOG:  redo starts at 1/7B059070
```

※終了する場合は以下のコマンドを実行してください

```
// 通常
$ docker-compose down

// 次回 up 時にDBを初期化したい場合
$ docker-compose down -v
```

# 4. WEB ブラウザでのアクセス

ログイン画面 ※道路施設管理システムを操作するために必要

```
http://localhost:8080/login/
```

道路施設管理システム

```
http://localhost:8080/rfs/#/
```

DB 管理画面(pgadmin)

```
http://localhost:8000/login?next=%2Fbrowser%2F

// ログインID
pgadmin
// パスワード
pgadmin
// DB接続情報
ホスト名：rfs_postgres_server
winのDBソフトからつなぐときのホスト名：localhost
ポート番号：5432
管理用データベース：rfs
ユーザ名：postgres
パスワード：postgres
```

※使用しているポートが重複する等があれば以下ファイルの各サービスのポートを適宜変更してください。

```
rfs/docker-compose.yml
```


<ARC構築メモ>
・docker-compose配下のイメージやコンテナ、ボリューム等を全て削除する場合のコマンド
docker-compose down --rmi all --volumes --remove-orphans

・docker上に存在する未使用のキャッシュ等を全て削除する場合のコマンド（上記より強力。起動中のものは削除されないため、docker-compose downの状態で実行する）
docker system prune -a

・初期DBの生成を再度行いたい場合
ホストマシンのpostgres/pgdata内のファイルを全て削除する（DBのデータはホストマシン上のファイルとして永続化されているため、ボリュームやキャッシュを削除するだけではデータは消えず、コンテナ起動時に初期化が行われない）

・money型のテーブルのCOPYコマンドでエラーになる（インポートが途中で止まる）
→localeがja_JP.UTF-8になっていなかったため、日本円のデータがinsertできなくなっていた
pgroutingにja_JP.UTF-8の設定を追加したイメージをビルドするように修正して解決
https://terurou.hateblo.jp/entry/2016/11/27/193139

<修正手順>
1. rfsフォルダ直下にpgroutingjaフォルダを作成
2. 下記内容を記載したDockerfileを作成（日本語に対応したpgroutingのイメージを作成するためのDockerfile）
--ここから--
FROM pgrouting/pgrouting:13-3.0-master
RUN localedef -i ja_JP -c -f UTF-8 -A /usr/share/locale/locale.alias ja_JP.UTF-8
ENV LANG ja_JP.utf8
--ここまで--
3. docker-compose.ymlのpostgresの項目を下記のとおり修正
postgres.imageの項目を削除もしくはコメントアウトし、下記を書き込む（pgroutingの公式イメージではなく、上で作成したpgroutingjaのイメージを使用する）
    build: pgroutingja
4. postgres.environment.POSTGRES_INITDB_ARGSの項目を下記のように修正（localeをja_JP.UTF-8に設定して起動する）
POSTGRES_INITDB_ARGS: "--encoding=UTF-8 --locale=ja_JP.UTF-8"

<ソース修正後に行う処理>
docker-compose restart
docker-compose logs -f


<phpのログの場所>
.\rfs\src\app\php\rfs_server\application\logs\

<ソースコードの変更がブラウザリロードで反映されない>
  WindowsでDockerを用いて開発を行う場合、WSL上にLinux環境を用意し、そこにソースを配置して実行するのが推奨されているとのこと。
  https://ja.stackoverflow.com/questions/75657/vscode%E3%81%AE%E6%8B%A1%E5%BC%B5%E6%A9%9F%E8%83%BDremote-containers%E3%82%92%E7%94%A8%E3%81%84%E3%81%9Fdocker%E4%B8%8A%E3%81%AE%E9%96%8B%E7%99%BA%E3%81%A7-react%E3%81%8C%E3%83%AA%E3%82%A2%E3%83%AB%E3%82%BF%E3%82%A4%E3%83%A0%E3%81%A7%E5%8F%8D%E6%98%A0%E3%81%95%E3%82%8C%E3%81%AA%E3%81%84

<!-- 変更を監視してビルドするような設定が抜けている。
参考: https://karukichi-blog.netlify.app/blogs/docker-webpack-dev-server
<修正手順>
src\app\node\rfs_client\webpack.config.jsを開く
module.exportsのオブジェクトのトップ（例えば17行目）に下記を追加する
  watch: true,
  // 追加
  watchOptions: {
    // 最初の変更からここで設定した期間に行われた変更は1度の変更の中で処理が行われる
    aggregateTimeout: 200,
    // ポーリングの間隔
    poll: 1000
  },


※参考: 無理やり解消する方法
原因: app_nodeコンテナ上では、Node.jsのパッケージであるcpxによってソースコードの変更を監視し、変更があるとそのファイルをappディレクトリの中身をwwwディレクトリにコピーし、webpackが変更を検出してビルドするという処理が設定されているが、なぜかcpxでhtmlファイルのコピーが動いていない

→そこで、この処理をWindows（ホストマシン）側で行わせる。Windows側のrfs_clientフォルダとコンテナ内のappディレクトリは共有されているため、Windows側で変更の検知とビルドを行ってしまえば、コンテナ上にも反映される。
参考: https://teratail.com/questions/294209

<手順>※Windows上で行う
Node.jsがインストールされていること
コマンドプロンプトもしくはPowerShellでrfs_clientフォルダに移動する
npm install -g browser-sync
npm install
npm run watch -->


  WSLを用いた環境を構築する手順
  （WSL2はDockerインストール時に構築するよう警告が出るので、既に構築されているはず）

　  1. WSLへのUbuntuのインストール
      1-1. Microsoft Storeアプリを起動し、「Ubuntu 20.04LTS」を探してインストール

      1-2. スタートメニューから「Ubuntu 20.04LTS」を起動
        ※「WslRegisterDistribution failed with error: 0x80070057」というエラーが出て起動に失敗する場合は下記を試す（Windowsの再起動が2回必要）
        　・PowerShellを管理者で起動
        　・下記コマンドを実行してWSLを一度無効化（再起動の確認が出るので再起動する）
        　　$ Disable-WindowsOptionalFeature -Online -FeatureName Microsoft-Windows-Subsystem-Linux
        　・再度PowerShellを管理者で起動して下記コマンドを実行して再度有効化（再起動の確認が出るので再起動する）
        　　$ Enable-WindowsOptionalFeature -Online -FeatureName Microsoft-Windows-Subsystem-Linux
        ※↑エラー対応ここまで

      1-1-3. Ubuntuを起動すると初回はユーザー名とパスワードの設定を求められるので適当に設定する（例えばubuntuなど）

    2. Dockerのインストールと初期設定
      以下、下記記事より抜粋
      https://qiita.com/amenoyoya/items/ca9210593395dbfc8531
      https://qiita.com/amenoyoya/items/41a2334cbc1facb87864

      2-1, ubuntsuのバージョンアップ作業
        参考サイト
          https://kledgeb.blogspot.com/2020/05/wsl-200-wsllinuxwsl.html
        windows power shellを管理者権限で起動する。
        > wsl --set-version ubuntu-20.04 2
        
        > wsl -l -v
          NAME                   STATE           VERSION
        * docker-desktop-data    Running         2
          Ubuntu-20.04           Running         2	←ここが2になることを確認
          docker-desktop         Running         2
        
        
      2-2. Docker (Community Edition) インストール
        $ curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -
        $ sudo add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu focal stable"
        $ sudo apt update && sudo apt install -y docker-ce
        ## dockerデーモン起動
        $ sudo service docker start

        $ sudo mkdir -p /sys/fs/cgroup/systemd
        $ sudo mount -t cgroup -o none,name=systemd cgroup /sys/fs/cgroup/systemd

        # docker-compose 導入
        $ sudo curl -L https://github.com/docker/compose/releases/download/1.26.0/docker-compose-`uname -s`-`uname -m` -o /usr/local/bin/docker-compose
        $ sudo chmod +x /usr/local/bin/docker-compose

        # Dockerを sudo なしで実行可能に
        ## ※ カレントユーザーをdockerグループに所属させた上で docker.sock へのグループ書き込み権限を付与すればよい
        $ sudo gpasswd -a $USER docker
        $ sudo chgrp docker /var/run/docker.sock
        $ sudo service docker restart

        # 一度ログアウトしないと反映されないため、一旦 exit
        $ exit


      2-3. Ubuntuの起動時にDockerサービスの起動と必要なディレクトリのマウントを行うように設定

        # /sbin/mount -a 実行時に rc ファイルシステムをマウントするように設定
        $ echo 'none none rc defaults 0 0' | sudo tee -a /etc/fstab

        # => これにより起動時に /sbin/mount.rc ファイルが呼び出されるようになる

        # /sbin/mount.rc ファイルを実行可能スクリプトとして作成
        $ echo '#!/bin/bash' | sudo tee /sbin/mount.rc
        $ sudo chmod +x /sbin/mount.rc

        # /sbin/mount.rc に実行権限が付与されているか確認
        $ ll /sbin/mount.rc
        →下記のように出力されることを確認
        -rwxr-xr-x 1 root root 12 11月  7 18:27 /sbin/mount.rc*

        # service docker start を /sbin/mount.rc に追記
        $ echo 'service docker start' | sudo tee -a /sbin/mount.rc

        # WSL2 には cgroup 用ディレクトリがデフォルトで作られていないため、以下もスタートアップスクリプトに登録しておく
        ## これをしておかないと Docker でプロセスのグループ化が必要になったときにエラーが起きる
        $ echo 'mkdir -p /sys/fs/cgroup/systemd && mount -t cgroup -o none,name=systemd cgroup /sys/fs/cgroup/systemd' | sudo tee -a /sbin/mount.rc

        # スタートアップスクリプト確認
        $ sudo cat /sbin/mount.rc
        →下記のように出力されることを確認
        #!/bin/bash
        service docker start
        mkdir -p /sys/fs/cgroup/systemd && mount -t cgroup -o none,name=systemd cgroup /sys/fs/cgroup/systemd

    3. Ubuntuへのソースコードの配置（転送）
      3-1. Windowsのエクスプローラーのフォルダパス欄に「\\wsl$」と入力するとWSL上の仮想マシン一覧（?）が表示されるので、「Ubuntu-20.04」に入るとUbuntuのディレクトリをエクスプローラーで表示・操作できる。

      3-2. /home/ubuntu/rfsディレクトリ（←ユーザー名が"ubuntu"の場合）などにソースコード（rfsフォルダ丸ごと）を配置する。

    4. Visual Studio Code（VSCode）でのリモート開発
      参考: https://qiita.com/EBIHARA_kenji/items/12c7a452429d79006450

      4-1. VSCode上で拡張機能「Remote - WSL」をインストールする
      　4-1-1. 画面左の拡張機能ボタンをクリック、もしくはCtrl+Shift+Xで拡張機能メニューを表示する
      　4-1-2. 検索欄に「WSL」等と入力して「Remote - WSL」を探し、インストールボタンをクリックする

      4-2. Ubuntuのコンソールでrfsのディレクトリに移動する

      4-3. 下記を実行するとWindows上でVSCodeが起動し、Ubuntu上に配置したソースコードが表示されるので、これを編集する
        $ code .

      4-4. VSCodeのターミナル（「ターミナル」メニュー→新しいターミナル）もしくはUbuntuのコンソールでDockerコンテナを起動する
        $ docker-compose up -d

      4-5. WindowsのDocker上に直接開発環境を構築した場合と同様、下記URLでアクセスできる
        ログイン画面
        http://localhost:8080/login/

        道路施設管理システム（起動してからアクセスできるようになるまでしばらくかかる。）
        http://localhost:8080/rfs/#/

--------------------
docker内のexcelsディレクトリのパーミッション設定
ubuntsuを起動
docker exec -it rfs_app_php_container sh
cd /var/www/html/
chmod -R 777 excels

※コンテナの名前はdocker-compose.ymlのcontainer_nameを参照

--------------------
＜次回起動時＞
1.visual studio codeでrfs環境を呼び出し、ターミナルを起動する。
2.ターミナルで下記のコマンドを入力
docker-compose down
docker-compose up -d
3.http://localhost:8080/login/ を開く
4.3.が開けた場合、少し待った後（上のログ参照）
 http://localhost:8080/rfs/#/
を開いて完了
5.3.が開けない場合、ターミナルでdocker-compose down
6.コマンドプロンプトを開く
7.wsl --shutdown
8.色々再起動が始まり、2.からやり直す。


--------------------
＜ビルド手順＞
前提条件: 開発環境が整って動作できる状態になっていること。
1. dockerコンテナ群を起動する
$ docker-compose up -d

※コンテナの起動時にビルドが走るので、恐らくこの時点でビルドは完了しているが、念のため手動でビルドコマンドを実行する。

2. AngularJSのコンテナに入る
$ docker exec -it rfs_app_node_container sh
3. ログイン後のディレクトリ（/app）の内容を確認
# ls
Gruntfile.js        README.md           app                 bower.json          node_modules        package-lock.json   package.json        sum_shisetsu1.json  test                webpack.config.js   www
4. wwwディレクトリを削除（念のため）
# sudo rm -R www
5. ビルドを実行する
# npm run build
6. ビルドされたファイル群がコンテナの/app/wwwディレクトリに格納されるので、このディレクトリを共有しているホスト側のディレクトリ（src/app/node/rfs_client/www）からファイル群を取り出す。