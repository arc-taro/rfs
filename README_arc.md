# 道路施設管理システムの開発環境構築手順について

# 1. 各種ソフトウェアをインストールする

1. [Docker Desktop for Windows or Mac](https://www.docker.com/products/docker-desktop)をインストールしてください。
2. rfs フォルダを docker が稼働するディレクトリに配置します。
3.  `rfs/src/app/node/`配下に gitlab から`rfs_client`プロジェクトを`git clone`してください。
4. `rfs/src/app/php/`配下に gitlab から`rfs_server`プロジェクトを`git clone`してください。

# 1.5 WSL環境に開発環境を構築する（Windowsの場合、必要に応じて）

Windows上でDockerを使用する場合、WSL環境と呼ばれる仮想Linux環境を構築し、そこにコンテナ群を構築すると開発が楽に進められます。

Windows上に直接Dockerのコンテナ群を構築しても開発はできますが、ファイルを変更しても即座に画面に変更が反映されず、変更のたびに再起動が必要で手間がかかります。

## 1.  WSLへのUbuntuのインストール

####　1-1. Ubuntuのインストール
Microsoft Storeアプリを起動し、「Ubuntu 20.04LTS」を探してインストールします。
####1-2. Ubuntuの起動
Windowsのスタートメニューから「Ubuntu 20.04LTS」を起動します。
　※「WslRegisterDistribution failed with error: 0x80070057」というエラーが出て起動に失敗する場合は下記を試す（Windowsの再起動が2回必要）
　　1. PowerShellを管理者で起動
　　2. ・下記コマンドを実行してWSLを一度無効化（再起動の確認が出るので再起動する）
　　```
　　$ Disable-WindowsOptionalFeature -Online -FeatureName　　Microsoft-Windows-Subsystem-Linux
    ```
　　3. 再度PowerShellを管理者で起動して下記コマンドを実行して再度有効化（再起動の確認が出るので再起動する）
　　```
　　$ Enable-WindowsOptionalFeature -Online -FeatureNameMicrosoft-Windows-Subsystem-Linux
　　```
####1-3. Ubuntuの初期設定
Ubuntuを起動すると初回はユーザー名とパスワードの設定を求められるので適当に設定します（例えばubuntuなど）

## 2. Dockerのインストールと初期設定
以下、下記記事より抜粋
https://qiita.com/amenoyoya/items/ca9210593395dbfc8531
https://qiita.com/amenoyoya/items/41a2334cbc1facb87864

####2-1. Ubuntuで使用するWSLのバージョンアップ作業
参考サイト
https://kledgeb.blogspot.com/2020/05/wsl-200-wsllinuxwsl.html
Windows PowerShellを管理者権限で起動し、下記のコマンドを実行します
```
> wsl --set-version ubuntu-20.04 2
```
下記コマンドの出力内容を確認します
```        
> wsl -l -v
  NAME                   STATE           VERSION
* docker-desktop-data    Running         2
  Ubuntu-20.04           Running         2	←ここが2になることを確認
  docker-desktop         Running         2
```
        
####2-2. Docker (Community Edition) インストール

以下のコマンドを順に実行します（#はコメント）
```
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
## ※ カレントユーザーをdockerグループに所属させた上で dockersock へのグループ書き込み権限を付与すればよい
$ sudo gpasswd -a $USER docker
$ sudo chgrp docker /var/run/docker.sock
$ sudo service docker restart
# 一度ログアウトしないと反映されないため、一旦 exit
$ exit
```
###2-3. Ubuntuの起動時にDockerサービスの起動と必要なディレクトリのマウントを行うように設定
以下のコマンドを順に実行します（#はコメント）
```
# /sbin/mount -a 実行時に rc ファイルシステムをマウントするうに設定
$ echo 'none none rc defaults 0 0' | sudo tee -a /etc/fstab
# => これにより起動時に /sbin/mount.rc ファイルが呼び出されようになる
# /sbin/mount.rc ファイルを実行可能スクリプトとして作成
$ echo '#!/bin/bash' | sudo tee /sbin/mount.rc
$ sudo chmod +x /sbin/mount.rc
# /sbin/mount.rc に実行権限が付与されているか確認
$ ll /sbin/mount.rc
→下記のように出力されることを確認
-rwxr-xr-x 1 root root 12 11月  7 18:27 /sbin/mount.rc*
# service docker start を /sbin/mount.rc に追記
$ echo 'service docker start' | sudo tee -a /sbin/mount.rc
# WSL2 には cgroup 用ディレクトリがデフォルトで作られていないめ、以下もスタートアップスクリプトに登録しておく
## これをしておかないと Docker でプロセスのグループ化が必要にったときにエラーが起きる
$ echo 'mkdir -p /sys/fs/cgroup/systemd && mount -t cgroup-o none,name=systemd cgroup /sys/fs/cgroup/systemd' | sudotee -a /sbin/mount.rc
# スタートアップスクリプト確認
$ sudo cat /sbin/mount.rc
→下記のように出力されることを確認
#!/bin/bash
service docker start
mkdir -p /sys/fs/cgroup/systemd && mount -t cgroup -o nonename=systemd cgroup /sys/fs/cgroup/systemd
```
###3. Ubuntuへのソースコードの配置（転送）
#### 3-1. エクスプローラーでのUbuntuへの入り方
Windowsのエクスプローラーのフォルダパス欄に「\\wsl$」と入力するとWSL上の仮想マシン一覧（?）が表示されるので、「Ubuntu-20.04」に入るとUbuntuのディレクトリをエクスプローラーで表示・操作できます。

#### 3-2. ソースコードの配置
/home/ubuntu/rfsディレクトリ（←ユーザー名が"ubuntu"の場合）などにソースコード（rfsフォルダ丸ごと）を配置する。

### 4. Visual Studio Code（VSCode）でのリモート開発
参考: https://qiita.com/EBIHARA_kenji/items12c7a452429d79006450
####4-1. VSCode上で拡張機能「Remote - WSL」をインストールする
1. 画面左の拡張機能ボタンをクリック、もしくはCtrl+Shift+X拡張機能メニューを表示する
2. 検索欄に「WSL」等と入力して「Remote - WSL」を探し、イストールボタンをクリックする
####4-2. Ubuntuのコンソールでrfsのディレクトリに移動する
####4-3. 下記を実行するとWindows上でVSCodeが起動し、Ubuntu上に配置たソースコードが表示されるので、これを編集する
```
  $ code .
```
####4-4. VSCodeのターミナル（「ターミナル」メニュー→新しいターミル）もしくはUbuntuのコンソールでDockerコンテナを起動する
```
  $ docker-compose up -d
```

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

AngularJSのコンテナ内での起動ポートを5000から8080に変更します。

rfs/src/app/node/rfs_client/package.json

変更前
```
"watch:serve": "browser-sync start --server 'www' --files 'www' --port 5000",
```

変更後
```
"watch:serve": "browser-sync start --server 'www' --files 'www' --port 8080",
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
```

※初回起動時は以下のログが出力されていれば DB の初期インポートが完了しています。

ログ確認コマンド

```
$ docker-compose logs -f
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

// DB接続情報
ホスト名：rfs_postgres_server
(winのDBソフトからつなぐときのホスト名：localhost)
ポート番号：5432
管理用データベース：rfs
ユーザ名：postgres
パスワード：postgres
```

※使用しているポートが重複する等があれば以下ファイルの各サービスのポートを適宜変更してください。

```
rfs/docker-compose.yml
```

#5. トラブルシューティング

#### docker-compose配下のイメージやコンテナ、ボリューム等を全て削除したい
```
$ docker-compose down --rmi all --volumes --remove-orphans
```

#### docker上に存在する未使用のキャッシュ等を全て削除したい
上記より強力。起動中のものは削除されないため、docker-compose downの状態で実行する
```
$ docker system prune -a
```

#### 初期DBの生成を再度行いたい
ホストマシンのpostgres/pgdata内のファイルを全て削除する（DBのデータはホストマシン上のファイルとして永続化されているため、ボリュームやキャッシュを削除するだけではデータは消えず、コンテナ起動時に初期化が行われない）

#### money型のテーブルのCOPYコマンドでエラーになる（インポートが途中で止まる）
→localeがja_JP.UTF-8になっていなかったため、日本円のデータがinsertできなくなっていた
pgroutingにja_JP.UTF-8の設定を追加したイメージをビルドするように修正して解決
https://terurou.hateblo.jp/entry/2016/11/27/193139

<修正手順>
1. rfsフォルダ直下にpgroutingjaフォルダを作成
2. 下記内容を記載したDockerfileを作成（日本語に対応したpgroutingのイメージを作成するためのDockerfile）
```
FROM pgrouting/pgrouting:13-3.0-master
RUN localedef -i ja_JP -c -f UTF-8 -A /usr/share/locale/locale.alias ja_JP.UTF-8
ENV LANG ja_JP.utf8
```
3. docker-compose.ymlのpostgresの項目を下記のとおり修正
postgres.imageの項目を削除もしくはコメントアウトし、下記を書き込む（pgroutingの公式イメージではなく、上で作成したpgroutingjaのイメージを使用する）
```
build: pgroutingja
```
4. postgres.environment.POSTGRES_INITDB_ARGSの項目を下記のように修正（localeをja_JP.UTF-8に設定して起動する）
```
POSTGRES_INITDB_ARGS: "--encoding=UTF-8 --locale=ja_JP.UTF-8"
```

<ソース修正後に行う処理>
```
docker-compose restart
docker-compose logs -f
```

#### PHPのログを確認したい
ホストマシンのrfs/src/app/php/rfs_server/application/ディレクトリがPHPのコンテナ内と共有されているので、ホストマシンのrfs/src/app/php/rfs_server/application/logsディレクトリにログファイルが保存されます。

#6. ビルド手順
前提条件: 開発環境が整って動作できる状態になっていること。
#####6-1. dockerコンテナ群を起動する
```
$ docker-compose up -d
```

※コンテナの起動時にビルドが走るので、恐らくこの時点でビルドは完了しているが、念のため手動でビルドコマンドを実行する。

2. AngularJSのコンテナに入る
```
$ docker exec -it rfs_app_node_container sh
```
3. ログイン後のディレクトリ（/app）の内容を確認
```
# ls
Gruntfile.js        README.md           app                 bower.json          node_modules        package-lock.json   package.json        sum_shisetsu1.json  test                webpack.config.js   www
```
4. wwwディレクトリを削除（念のため）
```
# sudo rm -R www
```
5. ビルドを実行する
```
# npm run build
```
6. ビルドされたファイル群がコンテナの/app/wwwディレクトリに格納されるので、このディレクトリを共有しているホスト側のディレクトリ（src/app/node/rfs_client/www）からファイル群を取り出す。