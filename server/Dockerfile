FROM php:8.2-fpm-alpine

ARG TZ
ARG COMPOSER_AUTH

ENV COMPOSER_AUTH=$COMPOSER_AUTH

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

COPY --from=composer/composer:2-bin@sha256:c397e38391a19abccfef248015d4dacb2ea10aa50abe1e9b0c9ed3423bae0fc1 --link /composer /usr/bin/composer

COPY --link composer.* ./

RUN composer install --no-dev --no-scripts --no-autoloader --no-progress --no-interaction

COPY --link  . ./
RUN rm -Rf docker/

RUN set -eux; \
	mkdir -p var/cache var/log var/log/task; \
    if [ -f composer.json ]; then \
		composer dump-autoload --classmap-authoritative --no-dev; \
		sync; \
    fi

RUN cp /usr/share/zoneinfo/$TZ /etc/localtime