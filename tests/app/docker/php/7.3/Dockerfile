FROM php:7.3-cli

WORKDIR /app

RUN apt-get update && \
    apt-get -y install \
        sudo \
        git \
        libsodium-dev \
        libzip-dev \
        libicu-dev \
        libpq-dev \
        zlib1g-dev \
        libssh2-1-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng-dev \
        --no-install-recommends

# Required extension
RUN pecl install apcu && \
    docker-php-ext-enable apcu && \
    git clone https://github.com/php/pecl-networking-ssh2.git /usr/src/php/ext/ssh2 && \
    docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ && \
    docker-php-ext-install -j$(nproc) \
        intl \
        opcache \
        mbstring \
        zip \
        ssh2 \
        pdo \
        pdo_pgsql \
        pgsql \
        bcmath \
        gd \
        sodium \
        sockets \
    && \
    apt-mark manual libpq5

COPY composer.json /app/composer.json

# Cleanup to keep the images size small
RUN apt-get purge -y \
        icu-devtools \
        libicu-dev \
    && \
    apt-get autoremove -y && \
    rm -r /var/lib/apt/lists/*

RUN curl -L -o /tmp/composer-setup.php https://getcomposer.org/installer && \
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    rm /tmp/composer-setup.php && \
    usermod -u 1000 www-data && \
    mkdir /var/www/.composer && \
    chown -R www-data:1000 /var/www/.composer && \
    mkdir /app/vendor && \
    chown -R www-data:1000 /app && \
    sudo -u www-data composer install --no-scripts --no-autoloader --prefer-dist && \
    find /app -type d | grep .git/ | xargs rm -rf

COPY . /app

RUN composer dump-autoload --optimize && \
    composer run-script post-install-cmd && \
    rm -f ./auth.json && \
    rm $(which composer) && \
    rm -r ~/.composer && \
    rm ./vendor/bower-asset/punycode/tests/data.js

ENTRYPOINT ["tests/app/docker/php/entrypoint.sh"]