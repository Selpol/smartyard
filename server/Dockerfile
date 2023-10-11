FROM php:8.2-fpm-alpine

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
        openssh \
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

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --link docker/supervisor/* /etc/supervisor.d/

COPY --link docker/php/conf.d/app.ini $PHP_INI_DIR/conf.d/
COPY --link docker/php/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/

COPY --link docker/php/php-fpm.d/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

COPY --link docker/php/docker-healthcheck.sh /usr/local/bin/docker-healthcheck
RUN chmod +x /usr/local/bin/docker-healthcheck

HEALTHCHECK --interval=10s --timeout=3s --retries=3 CMD ["docker-healthcheck"]

COPY --link docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

COPY --from=composer/composer:2-bin --link /composer /usr/bin/composer

COPY --link composer.* ./

RUN --mount=type=secret,id=composer_auth,dst=/srv/app/auth.json set -eux; \
    if [ -f composer.json ]; then \
		composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress; \
		composer clear-cache; \
    fi

COPY --link  . ./
RUN rm -Rf docker/

RUN set -eux; \
	mkdir -p var/cache var/log var/log/task; \
    if [ -f composer.json ]; then \
		composer dump-autoload --classmap-authoritative --no-dev; \
		sync; \
    fi

RUN cp /usr/share/zoneinfo/$TZ /etc/localtime