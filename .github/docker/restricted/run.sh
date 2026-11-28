#!/usr/bin/env bash
#
# Web サーバーの書き込み権限を絞った環境で、結合テストを実行する (ec-cube2/cli#20)。
#
# 使い方 (CLI のチェックアウトのトップで実行する):
#   .github/scripts/setup-eccube.sh <pgsql|mysql>   # 先に EC-CUBE を用意して DB を初期化する
#   .github/docker/restricted/run.sh [PHPUnit の引数...]
#   .github/docker/restricted/run.sh down            # 所有者を戻してコンテナを止める
#
# コンテナの中で次のように動かす。
#   - root     : restrict-permissions.sh で所有者と mode を揃える
#   - www-data : Apache (http://localhost:$WEB_PORT/、DocumentRoot は $ECCUBE_ROOT/html)
#   - site     : PHPUnit (テストから起動する CLI も site で動く)。uid はホストのユーザーと同じ
#
# 環境変数 (括弧内は既定値):
#   ECCUBE_ROOT  EC-CUBE のルート (<CLI のルート>/build/ec-cube)
#   PHP_VERSION  コンテナの PHP の版 (ホストの PHP の major.minor)。
#                vendor/ はホストの Composer で解決したものをそのまま使うので、ホストの PHP と揃える
#   WEB_PORT     Apache が待ち受けるポート (8000。eccube_install.sh の HTTP_URL の既定値に合わせている)
#
# 権限を揃えたあとの build/ec-cube には www-data (uid 33) 所有のファイルがあり、ホストのユーザーでは消せない。
# setup-eccube.sh をやり直す前や build/ を消す前に `run.sh down` を実行する。

set -euo pipefail

HERE=$(cd "$(dirname "$0")" && pwd)
CLI_DIR=$(cd "$HERE/../../.." && pwd)
ECCUBE_ROOT=${ECCUBE_ROOT:-$CLI_DIR/build/ec-cube}
PHP_VERSION=${PHP_VERSION:-$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')}
WEB_PORT=${WEB_PORT:-8000}
SITE_UID=$(id -u)
# Web サーバーが書き込めるディレクトリ。restrict-permissions.sh と IntegrationTestCase の両方がこの値を使う
ECCUBE_WRITABLE_DIRS='html/upload data/Smarty/templates_c data/cache data/upload data/logs'

if [ ! -f "$ECCUBE_ROOT/html/require.php" ]; then
    echo "EC-CUBE がありません: $ECCUBE_ROOT (先に .github/scripts/setup-eccube.sh を実行してください)" >&2
    exit 1
fi
ECCUBE_ROOT=$(cd "$ECCUBE_ROOT" && pwd)

export CLI_DIR ECCUBE_ROOT PHP_VERSION WEB_PORT SITE_UID ECCUBE_WRITABLE_DIRS

compose() {
    docker compose -f "$HERE/compose.yaml" -p ec-cube2-cli-restricted "$@"
}

if [ "${1:-}" = down ]; then
    if [ -n "$(compose ps -q web 2>/dev/null)" ]; then
        compose exec -T -u root web chown -R "${SITE_UID}:${SITE_UID}" "$ECCUBE_ROOT"
    fi
    compose down
    exit 0
fi

echo "::group::イメージのビルド (PHP ${PHP_VERSION})"
compose build
echo "::endgroup::"

echo "::group::所有者と mode を揃えて Apache を起動"
compose up -d --force-recreate
compose exec -T -u root web bash "$HERE/restrict-permissions.sh"
# Apache が応答するまで待つ (応答の中身はテストで確かめる)。
# Docker Desktop では、ホストから network_mode: host のコンテナのポートに届かないことがあるので、コンテナの中から確かめる
for _ in $(seq 1 30); do
    if compose exec -T web curl -s -m 5 -o /dev/null "http://localhost:${WEB_PORT}/"; then
        break
    fi
    sleep 1
done
compose exec -T web php -v
echo "::endgroup::"

compose exec -T -u site web vendor/bin/phpunit --testsuite integration "$@"
