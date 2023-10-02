FROM php:8.1-fpm-alpine

ARG TZ

WORKDIR /srv/app

COPY --from=mlocati/php-extension-installer:latest --link /usr/bin/install-php-extensions /usr/local/bin/

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
    ;

COPY --link docker/supervisor/* /etc/supervisor.d/

COPY --link docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

COPY --from=composer/composer:2-bin --link /composer /usr/bin/composer

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