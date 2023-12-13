FROM php:8.2-fpm-alpine

ARG TZ

WORKDIR /srv/app

COPY --from=mlocati/php-extension-installer:2.1.68@sha256:99eccef3accf521aeae7443d173953ba21c844866b1bb918e9a04dae372515b4 --link /usr/bin/install-php-extensions /usr/local/bin/

RUN apk update && apk add --update --no-cache \
		acl \
		fcgi \
		file \
		gettext \
		git \
        supervisor \
        ffmpeg \
        tzdata \
	;

RUN set -eux; \
    install-php-extensions \
		apcu \
		intl \
		opcache \
		zip \
        pdo \
        pdo_pgsql \
        sockets \
        pgsql \
        redis \
        mongodb \
        yaml \
        zip \
        curl \
        mbstring \
        openssl \
        pcntl \
        gd \
    ;

COPY --link docker/supervisor/* /etc/supervisor.d/

CMD ["php-fpm"]

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

COPY --from=composer/composer:2-bin@sha256:c397e38391a19abccfef248015d4dacb2ea10aa50abe1e9b0c9ed3423bae0fc1 --link /composer /usr/bin/composer

RUN set -eux; \
    if [ -f composer.json ]; then \
		composer install --prefer-dist --no-autoloader --no-scripts --no-progress; \
		composer clear-cache; \
    fi

RUN set -eux; \
	mkdir -p var/cache var/log var/log/task; \
    if [ -f composer.json ]; then \
		composer dump-autoload; \
		sync; \
    fi

RUN cp /usr/share/zoneinfo/$TZ /etc/localtime