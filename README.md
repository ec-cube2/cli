# EC-CUBE2 CLI

EC-CUBE2 を CLI で管理できるようになります。  


## Installation / Usage

```
$ composer install ec-cube2/cli
```

```
$ ./vendor/bin/eccube
Available commands:
  help                     Displays help for a command
  info                     EC-CUBE情報
  list                     Lists commands
 backup
  backup:create            バックアップ作成
  backup:delete            バックアップ削除
  backup:list              バックアップ一覧
  backup:restore           バックアップリストア
 cache
  cache:clear              キャッシュクリア
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
 zip
  zip:delete               郵便番号データ削除
  zip:download             郵便番号CSVダウンロード
  zip:info                 郵便番号情報
  zip:update               郵便番号更新
```

```
$ ./vendor/bin/eccube plugin:install PlginName
```

```
$ ./vendor/bin/eccube module:install mdl_name
```

## Requirements

PHP 5.3.3 以上


## Info

それぞれのコマンドが利用する機能は `Eccube2\Util` にまとめています。

追加機能の要望やバグ報告は Issue からお願いします。


## Author

Tsuyoshi Tsurushima


## License

EC-CUBE2 CLI は LGPL-3.0 でライセンスされています。 - LICENSE ファイルに詳細の記載があります。  
EC-CUBE2 CLI is licensed under the LGPL-3.0 License - see the LICENSE file for details.
