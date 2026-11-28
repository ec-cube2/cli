# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 概要

`ec-cube2/cli` — EC-CUBE 2 系を Symfony Console で操作する CLI (`bin/eccube`)。EC-CUBE プロジェクトに Composer パッケージとして組み込んで使う (EC-CUBE 本体の `composer.json` が `ec-cube2/cli ^1.4.3` を要求している)。単体では動かず、実行時に EC-CUBE の `require.php` を読み込んで本体のクラス・定数・DB 接続をそのまま使う。

## コマンド

```bash
composer update

# EC-CUBE 本体なしで実行できるテスト (DI コンテナの組み立てとコマンド登録)
vendor/bin/phpunit --testsuite structure

# 結合テスト: EC-CUBE を build/ec-cube に取得して CLI を組み込み、eccube_install.sh で DB を初期化
DBSERVER=127.0.0.1 DBPORT=5432 .github/scripts/setup-eccube.sh pgsql   # または mysql
ECCUBE_ROOT=$PWD/build/ec-cube vendor/bin/phpunit --testsuite integration

# 単一テスト
ECCUBE_ROOT=$PWD/build/ec-cube vendor/bin/phpunit tests/Integration/MemberTest.php --filter testCreate

# 古い EC-CUBE / PHP: PHPUnit は新しい PHP で動かし、CLI と EC-CUBE だけを ECCUBE_PHP で動かす
WORK_DIR=$PWD/build/213 ECCUBE_VERSION=2.13.5.1 PLATFORM_PHP=5.6.40 \
  DBSERVER=127.0.0.1 DBPORT=5432 .github/scripts/setup-eccube.sh pgsql
ECCUBE_PHP=/usr/bin/php5.6 ECCUBE_ROOT=$PWD/build/213/ec-cube vendor/bin/phpunit --testsuite integration
```

```bash
# Web サーバーの書き込み権限を絞った環境での結合テスト (Docker。#20)。setup-eccube.sh の後に実行する
PHP_VERSION=8.4 .github/docker/restricted/run.sh --testdox-text build/testdox.txt
.github/docker/restricted/run.sh down   # setup-eccube.sh のやり直しや build/ の削除の前に必ず実行する
```

- 結合テストは DB を作り直すので、再実行時は `setup-eccube.sh` からやり直す。2 回目以降の `setup-eccube.sh` は EC-CUBE の取得を省略し、CLI の再コピー・組み込みと DB 初期化だけを行う。
- `setup-eccube.sh` は未コミットの変更も含めて git 管理下のファイルだけを `build/cli-src` にコピーし、path リポジトリ (`symlink: false`、バージョン `1.4.99` を名乗らせる) として組み込む。シンボリックリンクにすると `Init::init()` が `realpath` で vendor を推定できず、CLI 側の `vendor/` が混ざると EC-CUBE の autoload より先に読まれてしまうため。
- EC-CUBE の取得方法 (詳細は `setup-eccube.sh` の冒頭):
  - `ECCUBE_VERSION` (既定 `2.25.0.1`): [ec-cube2/ec-cube2](https://github.com/ec-cube2/ec-cube2) を `composer create-project` する。ec-cube2/ec-cube2 は本家のタグにインストール手順のパッチを当てたもの (2.13.5.1 / 2.17.2.1 / 2.25.0.1)。2.13 の `eccube_install.sh` が別ホストの DB に入れられない問題はパッケージ側で直してあるので、CLI 側で書き換えない。
  - `ECCUBE_REF`: 指定すると本家 EC-CUBE/ec-cube2 のブランチ / タグを `git clone` する (本家 master の確認用。2.17 以降のみ)。
  - `ECCUBE_REPOSITORY`: 公開前の ec-cube2/ec-cube2 のブランチを試すときに、create-project の `--repository` に渡す (`ECCUBE_VERSION=dev-<ブランチ>` と組み合わせる)。
- `PLATFORM_PHP` を指定すると、EC-CUBE の `config.platform.php` をその版にし、EC-CUBE の require-dev を外してから CLI を組み込む (Composer を動かす PHP と CLI を動かす PHP が違うとき用)。
- DB 接続は `DBSERVER` / `DBPORT` / `DBNAME` / `DBUSER` / `DBPASS` で指定する。
- lint / 静的解析の設定はない。

## CI (`.github/workflows/tests.yml`)

- `structure`: PHP 7.2〜8.4 × Symfony 4.4 / 5.4 のマトリクス。`composer update --with "symfony/*:<ver>.*"` でバージョンを固定する。Symfony 4.4 は EOL アドバイザリで Composer が拒否するため `COMPOSER_NO_SECURITY_BLOCKING=1` を付けている。
- `boot-without-dev`: `--no-dev` でインストールして `php bin/eccube list` が起動するか (利用者環境と同じ条件)。
- `integration`: PHP 7.4 / 8.1 / 8.4 × PostgreSQL 16 / MySQL 8.4 × EC-CUBE 2.25.0.1、加えて本家 master (`ECCUBE_REF=master`) × PHP 8.4。失敗時は `build/ec-cube/data/logs` が artifact に上がる。
- `legacy`: EC-CUBE 2.13.5.1 (PHP 5.5 / 5.6) と 2.17.2.1 (PHP 5.6〜8.0) × PostgreSQL 10 / MySQL 5.6・5.7。`setup-php` を 2 回使い、1 回目の PHP のパスを `ECCUBE_PHP`、版を `PLATFORM_PHP` に記録して、PHPUnit と Composer は 2 回目の PHP 8.4 で動かす。組み合わせの制約は `tests.yml` のコメントにある。特に次の点に注意:
  - setup-php は、実際には読み込まれていない拡張も「Enabled」と表示することがある (PHP 5.6 の ext/mysql)。そのため `Check PHP binaries` で `extension_loaded()` を確認している。
  - PHP 5.5 では Composer 2.2 LTS を使う (2.3 以降が生成する autoload は PHP 5.6 未満で動かない)。
  - サービスコンテナの MySQL は起動引数を渡せないので、文字コードはサーバーの既定 (MySQL 5.6 は latin1) になる。
- `restricted-permissions`: Web サーバーの書き込み権限を絞った環境で CLI が動くか (#20)。EC-CUBE 2.25.0.1 × PHP 8.4 × PostgreSQL 16 / MySQL 8.4 と、2.17.2.1 × PHP 7.4 × PostgreSQL 10。
  - EC-CUBE の取得と DB の初期化は runner の `setup-eccube.sh` で行い、`.github/docker/restricted/run.sh` が `php:<ver>-apache` のコンテナ (`network_mode: host`、checkout をホストと同じパスでマウント) の中で、root で `restrict-permissions.sh`、`www-data` で Apache、`site` (uid はホストのユーザーと同じ) で PHPUnit を動かす。
  - 権限モデル: docroot は `site` 所有の `u=rwX,g=rX,o=rX`。`www-data` が書き込めるのは `ECCUBE_WRITABLE_DIRS` (`run.sh` で定義) の 5 つのディレクトリだけ。
  - `vendor/` は runner の Composer で解決したものをコンテナでそのまま使うので、runner の PHP とコンテナの PHP (`PHP_VERSION`) を揃える。`PLATFORM_PHP` も記録している (2.17 の `config.platform.php` が 5.4.16 のため)。
  - 環境変数 `ECCUBE_RESTRICTED_PERMISSIONS=1` のとき、`IntegrationTestCase::eccube()` は実行のたびに、出力の権限エラー (`data/logs/` の警告は除く)、所有者と mode (`www-data` が書き換えられる o+w のファイルも問題にする)、トップページの表示 (`ECCUBE_WEB_URL`。`InstallTest` は `checksWebPage()` で外している) を確かめる。問題があったテストと、この環境で失敗したテストは incomplete に置き換えるので、ジョブは緑のまま、この環境で動かないものが testdox に一覧として出る (理由は `--verbose` で出る)。CLI の不具合そのものは `integration` ジョブで確かめる。
  - **CLI は権限エラーを出力しない。** `Init::setErrorHandler()` は E_ERROR / E_USER_ERROR 以外を握りつぶすので、ファイルを書けなくてもコマンドは `[OK]` で終わる (`parameter:set` はキャッシュが古いまま成功する)。そのため、テストではコマンドの結果のファイル (`mtb_constants.php`、`data/cache/*.serial`、`templates_c`) を確かめる。EC-CUBE 本体のエラーハンドラーは警告を HTML で標準出力に出す (`runEccubePhp()` のとき)。
  - `data/logs/` に書けないことは `RestrictedPermissionsTest::testLogging` で 1 か所だけ記録する。`RestrictedPermissionsTest` は CLI を実行する前の状態 (権限の前提とトップページ) を確かめるので、結合テストの最初に置いている。
  - 権限を揃えた後の `build/ec-cube` には `www-data` (uid 33) 所有のファイルが残り、ホストのユーザーでは消せない。手元では `run.sh down` で所有者を戻す。

## アーキテクチャ

### 起動とコマンド登録

`bin/eccube` → `src/bootstrap.php` (自身の `vendor/autoload.php`、なければ設置先の `../../../autoload.php` を読む) → `Eccube2\Console\Application`。

`Application` のコンストラクタが Symfony DI の `ContainerBuilder` を組み立て、`config/services.yaml` の resource 指定で `src/Eccube2/Command/*` を自動登録し、`AddConsoleCommandPass` で遅延ロードする。コマンド名は各クラスの `protected static $defaultName`。

- **拡張ポイント**: `Application::appendConfigPath()` / `prependConfigPath()` で別ディレクトリの `services.yaml` を追加読み込みでき、外部からコマンドやイベントリスナー (`RegisterListenersPass`) を足せる。
- コンストラクタは `define('SAFE', true)` を実行し静的プロパティ `$configPaths` に追記するため、**1 プロセスで 1 回しか生成できない** (Structure テストはインスタンスを共有している)。

### EC-CUBE 本体への依存 (`Eccube2\Init`)

- 各コマンドは `initialize()` で `Init::init()` を呼んでから処理する。`Init::init()` は自身の設置場所 (`<root>/vendor/` または `<root>/data/vendor/`) から EC-CUBE のルートを推定し、`html/require.php` (html ディレクトリなし構成なら `require.php`) を `require_once` する。**EC-CUBE はグローバル定数と状態を持つので、同一プロセスで初期化は 1 回だけ**。
- `_Ex` クラスのファイルは `Init::requireClassEx('page_extends/...')` で読む。EC-CUBE 2.25 では `page_extends/` の大半の `_Ex` ファイルが削除されオートローダーが生成するため、ファイルが存在する場合だけ読み込む (2.17 以前との互換)。
- `Init::setErrorHandler()` は `E_ERROR` / `E_USER_ERROR` だけを例外に変換する。

### Command と Util の分担

- `src/Eccube2/Command/<Group>/<Name>Command.php`: 入出力のみ。オプション未指定時は `SymfonyStyle` の `ask` / `choice` / `askHidden` で対話入力する。`execute()` は終了コード (int) を返す (Symfony 5.4 で必須)。
- `src/Eccube2/Util/*Util.php`: 実処理。多くは EC-CUBE 管理画面のページクラス (`LC_Page_Admin_*_Ex`) や `SC_Query_Ex` / `SC_DB_MasterData_Ex` などを直接インスタンス化して流用する。新しいコマンドもまず該当する管理画面のページクラスのロジックを流用できるか確認する。
- イベント発行は `EventDispatcherUtil::dispatch()` を使う (Symfony 4.3 で `dispatch()` の引数順が変わり 5.0 で旧形式が削除されたため)。現状のイベントは `install.insert_data` / `install.after`。

### 互換性の制約

- `composer.json` の要件は **PHP >= 5.5.9、Symfony ^3.4|^4.4|^5.4**。Symfony 2.8 は外した (v1.3.0 から Symfony 3.4 の command loader と YAML の resource を使っており、2.8 では `AddConsoleCommandPass` が無く起動しない)。
- `src/` は PHP 5.5 で動く書き方を守る。戻り値型・スカラー型宣言・`??` は使わない。配列は既存コードに合わせて `array()` で書く。
- `tests/` は PHP 7.2.5 以上前提 (PHPUnit 8.5 / 9.6 両対応。`phpunit.xml.dist` も両方で読める書式)。
- 対応 DB は PostgreSQL と MySQL (MySQL 8 の予約語 `RANK` などは `InstallUtil` でクォートしている)。

## テストの構成

- `tests/Structure/`: EC-CUBE なしで検証できる範囲。`ApplicationTest::EXPECTED_COMMANDS` は README の Usage と対応しているので、**コマンドを追加・削除したら README の Usage とこのリストを両方更新する**。
- `tests/Integration/`: `IntegrationTestCase::eccube()` が、`ECCUBE_ROOT` の `data/vendor` (2.17 以降) か `vendor` (2.13) にある `ec-cube2/cli/bin/eccube` を別プロセスで実行し、終了コードと出力 (`CommandResult`) を検証する。
  - `$stdin` を渡すと対話プロンプトへの回答になり、`null` なら `--no-interaction` が付く。
  - PHP は環境変数 `ECCUBE_PHP` のコマンドで起動し、未設定ならテストを実行している PHP を使う。PHP のエラー表示は標準エラー出力に回される。
  - `ECCUBE_ROOT` が未設定ならスキップする。
- 本家 master × PostgreSQL では、`BackupTest::testRestore` が EC-CUBE 本体の不具合 (EC-CUBE/ec-cube2#1484) で失敗する。この失敗に限り incomplete として扱っている。
- 結合テストは `phpunit.xml.dist` にファイルを順番に列挙しており、DB を作り直す `InstallTest` が最後。新しい結合テストファイルはこのリストに追加する。
