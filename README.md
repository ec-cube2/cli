# EC-CUBE2 CLI

EC-CUBE2 を CLI で管理できるようになります。  


## Installation / Usage

```
$ composer install eccube2/cli
```

```
$ ./vendor/bin/eccube
Available commands:
  help              Displays help for a command
  list              Lists commands
 module
  module:list       
  module:info    
  module:install    
  module:uninstall  
  module:update     
 plugin
  plugin:list    
  plugin:info    
  plugin:disable    
  plugin:enable     
  plugin:install    
  plugin:uninstall  
  plugin:update
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

EC-CUBE Plugin / Module Composer Installer は LGPL-3.0 でライセンスされています。 - LICENSE ファイルに詳細の記載があります。  
EC-CUBE Plugin / Module Composer Installer is licensed under the LGPL-3.0 License - see the LICENSE file for details.
