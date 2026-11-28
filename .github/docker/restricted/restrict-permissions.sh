#!/usr/bin/env bash
#
# EC-CUBE の所有者と mode を、Web サーバーの書き込み権限を絞った構成にする (ec-cube2/cli#20)。
# コンテナの中で root として実行する (run.sh から呼ぶ)。
#
# - docroot 全体は SSH ユーザー (site) の所有で u=rwX,g=rX,o=rX
# - Web サーバー (www-data) が書き込めるのは ECCUBE_WRITABLE_DIRS のディレクトリだけ (所有者を www-data にする)
#
# 環境変数:
#   ECCUBE_ROOT          EC-CUBE のルート
#   ECCUBE_WRITABLE_DIRS Web サーバーが書き込めるディレクトリ (ECCUBE_ROOT からの相対パスを空白区切り)

set -euo pipefail

ROOT=${ECCUBE_ROOT:?ECCUBE_ROOT を指定してください}
read -r -a WRITABLE_DIRS <<< "${ECCUBE_WRITABLE_DIRS:?ECCUBE_WRITABLE_DIRS を指定してください}"

# セットアップ (eccube_install.sh) のときのログは、この構成での結果と混ざらないよう消す
rm -f "$ROOT"/data/logs/*.log

chown -R site:site "$ROOT"
chmod -R u=rwX,g=rX,o=rX "$ROOT"

for dir in "${WRITABLE_DIRS[@]}"; do
    mkdir -p "$ROOT/$dir"
    chown -R www-data:www-data "$ROOT/$dir"
done

echo "所有者と mode を揃えました: $ROOT"
for dir in "${WRITABLE_DIRS[@]}"; do
    stat -c '  %U:%G %A %n' "$ROOT/$dir"
done
