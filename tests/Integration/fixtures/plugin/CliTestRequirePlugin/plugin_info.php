<?php

/**
 * ec-cube2/cli の結合テスト用プラグイン (HTTP/Request.php を require_once する)。
 */
class plugin_info
{
    public static $PLUGIN_CODE = 'CliTestRequirePlugin';
    public static $PLUGIN_NAME = 'CLI テスト用プラグイン (HTTP_Request)';
    public static $CLASS_NAME = 'CliTestRequirePlugin';
    public static $PLUGIN_VERSION = '1.0.0';
    public static $COMPLIANT_VERSION = '2.13';
    public static $AUTHOR = 'ec-cube2/cli';
    public static $DESCRIPTION = 'ロード時に data/module/HTTP/Request.php を require_once する、ec-cube2/cli の結合テスト用プラグイン';
}
