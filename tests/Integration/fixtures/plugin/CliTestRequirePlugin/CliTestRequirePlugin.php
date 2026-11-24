<?php

// mdl_paypal_express のヘルパーと同じく、ロード時に HTTP_Request のファイルを読み込む。
require_once DATA_REALDIR . 'module/HTTP/Request.php';

/**
 * ec-cube2/cli の結合テスト用プラグイン。
 *
 * install / uninstall / enable / disable は SC_Plugin_Base の既定の実装 (何もしない) を使う。
 */
class CliTestRequirePlugin extends SC_Plugin_Base
{
}
