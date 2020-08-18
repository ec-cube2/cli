# EC-CUBE2 CLI

EC-CUBE2 を CLI で管理できるようになります。  


## Installation / Usage

```
$ composer install ec-cube2/cli
```

```
$ ./vendor/bin/eccube
Available commands:
  help              Displays help for a command
  list              Lists commands
  info              EC-CUBE情報
 module
  module:list       モジュール一覧
  module:info       モジュール情報
  module:install    モジュールインストール
  module:uninstall  モジュールアンインストール
  module:update     モジュールアップデート
 plugin
  plugin:list       プラグイン一覧
  plugin:info       プラグイン情報
  plugin:install    プラグインインストール
  plugin:uninstall  プラグインアンインストール
  plugin:enable     プラグイン有効化
  plugin:disable    プラグイン無効化
  plugin:update     プラグインアップデート
```

```
$ ./vendor/bin/eccube plugin:install PlginName
```

```
$ ./vendor/bin/eccube module:install mdl_name
```

## Requirements

PHP 5.3.3 以上



## Author

Tsuyoshi Tsurushima


## License

EC-CUBE2 CLI は LGPL-3.0 でライセンスされています。 - LICENSE ファイルに詳細の記載があります。  
EC-CUBE2 CLI is licensed under the LGPL-3.0 License - see the LICENSE file for details.
