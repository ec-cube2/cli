# Web サーバーの書き込み権限を絞った環境で CLI を確かめるためのイメージ (ec-cube2/cli#20)。
#
# - Apache + mod_php を www-data で動かす (php:<ver>-apache の既定)。
# - CLI と PHPUnit は SSH ユーザーに見立てた site で動かす。site には sudo を持たせない。
#   site の uid はホストのユーザーに合わせる (バインドマウントしたファイルをホスト側でも扱えるようにするため)。
#
# 使い方は run.sh を参照。
ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-apache

ARG SITE_UID=1000

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libpq-dev libpng-dev libjpeg-dev libfreetype6-dev libzip-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" pgsql mysqli gd zip; \
    rm -rf /var/lib/apt/lists/*

# opcache が有効だと、一度コンパイルした PHP ファイルは読み直されないので、Web サーバーが読めなくなっても気付けない。
# CLI が書いたファイルを Web サーバーが読めるかを確かめるため、無効にする
RUN printf 'date.timezone = Asia/Tokyo\nmemory_limit = 512M\nopcache.enable = 0\n' > /usr/local/etc/php/conf.d/zz-cli-test.ini

# ホストのユーザーと uid が同じグループ・ユーザーがイメージにあれば (www-data の 33 など) 作れないので、その場合は失敗させる
RUN useradd --uid "${SITE_UID}" --user-group --create-home --shell /bin/bash site

# DocumentRoot と待ち受けるポートは環境変数で渡す (APACHE_DOCUMENT_ROOT / APACHE_PORT)。
# EC-CUBE の .htaccess を効かせるため AllowOverride All にする
RUN set -eux; \
    a2enmod rewrite; \
    sed -ri 's/^Listen 80$/Listen ${APACHE_PORT}/' /etc/apache2/ports.conf; \
    sed -ri -e 's/<VirtualHost \*:80>/<VirtualHost *:${APACHE_PORT}>/' \
        -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf; \
    printf '<Directory ${APACHE_DOCUMENT_ROOT}>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        > /etc/apache2/conf-available/eccube.conf; \
    a2enconf eccube
