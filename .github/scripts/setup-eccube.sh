#!/usr/bin/env bash
#
# 結合テスト用に、EC-CUBE 2 を取得してテスト対象の CLI を組み込み、DB を初期化する。
#
# 使い方:
#   .github/scripts/setup-eccube.sh <pgsql|mysql>
#
# 環境変数 (括弧内は既定値):
#   ECCUBE_REF  取得する EC-CUBE のタグまたはブランチ (eccube-2.25.0)
#   WORK_DIR    作業ディレクトリ (<CLI のルート>/build)。EC-CUBE は $WORK_DIR/ec-cube に置く
#   DBSERVER / DBPORT / DBNAME / DBUSER / DBPASS
#               EC-CUBE の eccube_install.sh にそのまま渡す DB 接続情報
#   PLATFORM_PHP
#               指定すると EC-CUBE の composer.json の config.platform.php をこの値にする。
#               CLI を動かす PHP と Composer を動かす PHP が違うとき (古い PHP での確認) に、
#               CLI を動かす PHP のバージョンを指定する
#
# 実行後、テストは ECCUBE_ROOT=$WORK_DIR/ec-cube を指定して実行する。
# GitHub Actions 上では ECCUBE_ROOT を $GITHUB_ENV に書き出す。
#
# 2 回目以降は EC-CUBE の取得を省略し、CLI の組み込みと DB の初期化だけをやり直す。

set -euo pipefail

DB_TYPE=${1:?"使い方: $0 <pgsql|mysql>"}
case "$DB_TYPE" in
    pgsql | mysql) ;;
    *)
        echo "DB の種類は pgsql か mysql を指定してください: $DB_TYPE" >&2
        exit 1
        ;;
esac

CLI_DIR=$(cd "$(dirname "$0")/../.." && pwd)
ECCUBE_REF=${ECCUBE_REF:-eccube-2.25.0}
WORK_DIR=${WORK_DIR:-$CLI_DIR/build}
ECCUBE_ROOT=$WORK_DIR/ec-cube
CLI_SRC=$WORK_DIR/cli-src

# EC-CUBE 本体の composer.json は ec-cube2/cli ^1.4.3 を要求するので、それを満たすバージョンを名乗らせる
CLI_VERSION=1.4.99

mkdir -p "$WORK_DIR"

echo "::group::EC-CUBE ${ECCUBE_REF} の取得"
if [ ! -d "$ECCUBE_ROOT/.git" ]; then
    git clone --depth 1 --branch "$ECCUBE_REF" https://github.com/EC-CUBE/ec-cube2.git "$ECCUBE_ROOT"
else
    echo "取得済みの $ECCUBE_ROOT を使います"
fi
echo "::endgroup::"

echo "::group::テスト対象の CLI をコピー"
# - path リポジトリをシンボリックリンクで組み込むと、Init::init() が realpath で自身の位置を解決したときに
#   元のソースの場所になってしまい、vendor ディレクトリを推定できない。そのため実体をコピーする。
# - CLI 側の vendor/ が含まれると、src/bootstrap.php が EC-CUBE の autoload より先に読み込んでしまう。
#   .gitignore で除外されるもの (vendor/ や build/) を含めないよう、git が把握しているファイルだけをコピーする
#   (コミット前の変更も含める)。
rm -rf "$CLI_SRC"
mkdir -p "$CLI_SRC"
git -C "$CLI_DIR" ls-files -z --cached --others --exclude-standard \
    | (cd "$CLI_DIR" && tar --null --ignore-failed-read -T - -cf -) \
    | tar -C "$CLI_SRC" -xf -
echo "::endgroup::"

echo "::group::EC-CUBE に CLI を組み込む"
cd "$ECCUBE_ROOT"
if [ -n "${PLATFORM_PHP:-}" ]; then
    composer config platform.php "$PLATFORM_PHP"
    # EC-CUBE の require-dev (テスト用) は CLI のテストに要らない。2.17 の composer.lock にある
    # fzaninotto/faker や php5friends/phpunit48 は PHP 8 を許さないので、残すと PHP 8.0 で依存を解決できない
    if [ -f composer.json ]; then
        mapfile -t DEV_PACKAGES < <(jq -r '."require-dev" // {} | keys[]' composer.json)
        if [ "${#DEV_PACKAGES[@]}" -gt 0 ]; then
            composer remove --dev --no-update "${DEV_PACKAGES[@]}"
        fi
    fi
fi
# vendor の場所は 2.17 以降が data/vendor、2.13 がプロジェクト直下の vendor
VENDOR_DIR=$(composer config vendor-dir)
composer config repositories.cli-under-test \
    "{\"type\": \"path\", \"url\": \"${CLI_SRC}\", \"options\": {\"symlink\": false, \"versions\": {\"ec-cube2/cli\": \"${CLI_VERSION}\"}}}"
# path リポジトリはソースが変わってもバージョンが同じだと入れ直さないため、既存のコピーを消してから入れる
rm -rf "${VENDOR_DIR}/ec-cube2/cli"
composer require --no-interaction --no-progress --update-with-all-dependencies "ec-cube2/cli:${CLI_VERSION}"
composer show ec-cube2/cli symfony/console symfony/dependency-injection symfony/event-dispatcher symfony/config symfony/yaml 2>/dev/null \
    | grep -E '^(name|versions)' || true
echo "::endgroup::"

echo "::group::DB の初期化 (EC-CUBE の eccube_install.sh)"
# CLI のテストとは独立に、EC-CUBE 公式の手順でインストール済みの状態を作る。
# data/config/config.php もここで生成される。
# eccube_install.sh の中の ${...} を文字どおりに扱うため、以下はシングルクォートで書いている
# shellcheck disable=SC2016
if ! grep -q 'DBPORT=${DBPORT:-' eccube_install.sh; then
    # 2.13 の eccube_install.sh は、PostgreSQL に sudo -u postgres で接続し、ポートを固定し、
    # MySQL 5.7.5 で削除された storage_engine を設定する。2.17 で直された箇所と同じように書き換える
    git checkout -- eccube_install.sh
    # shellcheck disable=SC2016
    sed -i \
        -e 's/sudo -u ${PGUSER} //g' \
        -e 's/^\( *\)DBPORT=5432$/\1DBPORT=${DBPORT:-5432}/' \
        -e 's/^\( *\)DBPORT=3306$/\1DBPORT=${DBPORT:-3306}/' \
        -e '/^ *DBSERVER="127.0.0.1"$/d' \
        -e 's/${PSQL} -U/${PSQL} -h ${DBSERVER} -p ${DBPORT} -U/g' \
        -e 's/${DROPDB} ${DBNAME}/${DROPDB} -h ${DBSERVER} -p ${DBPORT} -U ${DBUSER} ${DBNAME}/' \
        -e 's/${CREATEDB} -U/${CREATEDB} -h ${DBSERVER} -p ${DBPORT} -U/' \
        -e 's/${MYSQL} -u/${MYSQL} -h ${DBSERVER} -P ${DBPORT} -u/g' \
        -e 's/SET SESSION storage_engine/SET SESSION default_storage_engine/' \
        eccube_install.sh
    git diff --stat -- eccube_install.sh
fi
if [ "$DB_TYPE" = pgsql ]; then
    # 2.17 以降の eccube_install.sh は自分で設定する。2.13 のために先に渡しておく
    export PGPASSWORD=${PGPASSWORD:-${DBPASS:-password}}
fi
bash ./eccube_install.sh "$DB_TYPE"
echo "::endgroup::"

if [ -n "${GITHUB_ENV:-}" ]; then
    echo "ECCUBE_ROOT=${ECCUBE_ROOT}" >> "$GITHUB_ENV"
fi

echo "ECCUBE_ROOT=${ECCUBE_ROOT}"
