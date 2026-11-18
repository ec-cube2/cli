# EC-CUBE2 CLI

EC-CUBE2 を CLI で管理できるようになります。  


## Installation

```
$ composer require ec-cube2/cli
```

動作に必要なもの: PHP 5.5.9 以上、Symfony 3.4 / 4.4 / 5.4 (EC-CUBE 2.13 / 2.17 / 2.25 で確認しています)。

EC-CUBE 2.17.x のプロジェクトは `composer.json` の `config.platform.php` が `5.4.16` になっています。
そのままでは依存関係を解決できないので、`config.platform.php` を実際に動かす PHP のバージョン (5.5.9 以上) に変えてからインストールしてください。

```
$ composer config platform.php 5.6.40
$ composer require ec-cube2/cli --update-with-all-dependencies
```

## Usage

```
$ ./vendor/bin/eccube
Available commands:
  help                     Displays help for a command
  info                     EC-CUBE情報
  install                  インストール
  list                     Lists commands
 backup
  backup:create            バックアップ作成
  backup:delete            バックアップ削除
  backup:list              バックアップ一覧
  backup:restore           バックアップリストア
 cache
  cache:clear              キャッシュクリア
 install
  install:agreement        ソフトウェア使用許諾書
  install:config           インストール 設定
  install:copy-image       インストール 画像コピー
  install:create-sequence  インストール シーケンス作成
  install:create-table     インストール テーブル作成
  install:drop-sequence    インストール シーケンス削除
  install:drop-table       インストール テーブル削除
  install:insert-data      インストール 初期データ作成
  install:send-info        インストール インストール情報送信
  install:set-base-info    インストール 店舗情報設定
 member
  member:create            メンバー作成
  member:delete            メンバー削除
  member:disable           メンバー無効化
  member:enable            メンバー有効化
  member:set-password      メンバーパスワード設定
 module
  module:info              モジュール情報
  module:install           モジュールインストール
  module:list              モジュール一覧
  module:uninstall         モジュールアンインストール
  module:update            モジュールアップデート
 ownersstore
  ownersstore:list         オーナーズストア一覧
  ownersstore:set-key      オーナーズストア認証キー設定
 parameter
  parameter:get            パラメーター表示
  parameter:set            パラメーター設定
 plugin
  plugin:disable           プラグイン無効化
  plugin:enable            プラグイン有効化
  plugin:info              プラグイン情報
  plugin:install           プラグインインストール
  plugin:list              プラグイン一覧
  plugin:uninstall         プラグインアンインストール
  plugin:update            プラグインアップデート
 template
  template:mobile:get      モバイルテンプレートコード表示
  template:mobile:set      モバイルテンプレートコード設定
  template:pc:get          PCテンプレートコード表示
  template:pc:set          PCテンプレートコード設定
  template:smartphone:get  スマートフォンテンプレートコード表示
  template:smartphone:set  スマートフォンテンプレートコード設定
 util
  util:random-string       ランダムな文字列を生成
 zip
  zip:delete               郵便番号データ削除
  zip:download             郵便番号CSVダウンロード
  zip:info                 郵便番号情報
  zip:update               郵便番号更新
```


## Requirements

PHP 5.3.3 以上


## Info

それぞれのコマンドが利用する機能は `Eccube2\Util` にまとめています。

追加機能の要望やバグ報告は Issue からお願いします。


## Development

テストは PHP 7.2.5 以上で実行します。

```
$ composer update

# EC-CUBE 本体なしで実行できるテスト (Application の起動とコマンドの登録)
$ vendor/bin/phpunit --testsuite structure
```

結合テストは EC-CUBE 2 に CLI を組み込み、コマンドを別プロセスで実行します。
DB (PostgreSQL または MySQL) を用意してから、次のように実行します。

```
# EC-CUBE を build/ec-cube に取得して CLI を組み込み、eccube_install.sh で DB を初期化する
$ DBSERVER=127.0.0.1 DBPORT=5432 .github/scripts/setup-eccube.sh pgsql

$ ECCUBE_ROOT=$PWD/build/ec-cube vendor/bin/phpunit --testsuite integration
```

結合テストは DB を作り直すので、再実行するときはセットアップからやり直してください。
接続情報などの環境変数は `.github/scripts/setup-eccube.sh` の冒頭を参照してください。

### 古い EC-CUBE / PHP での確認

EC-CUBE 2.13 / 2.17 と古い PHP での確認では、PHPUnit は新しい PHP で実行し、CLI と EC-CUBE だけを古い PHP で動かします。
環境変数 `ECCUBE_PHP` に、CLI を動かす PHP のコマンドを指定します。
Composer は新しい PHP で動かすので、`PLATFORM_PHP` に CLI を動かす PHP のバージョンを指定して、依存関係をそのバージョンに合わせます。

```
$ WORK_DIR=$PWD/build/213 ECCUBE_REF=eccube-2.13.5-p2 PLATFORM_PHP=5.6.40 \
    DBSERVER=127.0.0.1 DBPORT=5432 .github/scripts/setup-eccube.sh pgsql

$ ECCUBE_PHP=/usr/bin/php5.6 ECCUBE_ROOT=$PWD/build/213/ec-cube vendor/bin/phpunit --testsuite integration
```

- EC-CUBE 2.13 の MDB2 は PostgreSQL 12 で削除された `pg_attrdef.adsrc` を参照するので、PostgreSQL 11 以前を使います。
- EC-CUBE 2.13 は接続時に MySQL 5.7.5 で削除された `storage_engine` を設定するので、MySQL 5.6 以前を使います。
- `ECCUBE_PHP` には Docker で動かす PHP を呼ぶスクリプトも指定できます (EC-CUBE のディレクトリを同じパスでマウントし、DB にはホストのネットワークで接続します)。
