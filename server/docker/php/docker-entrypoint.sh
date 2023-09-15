#!/bin/sh

set -e

if [ "${1#-}" != "$1" ]; then
	set -- php-fpm "$@"
fi

if [ "$1" = 'php-fpm' ] || [ "$1" = 'php' ]; then
  php cli.php --optimize-kernel

  echo "Waiting for database connection..."

  COUNT=60

  until [ $COUNT -eq 0 ] || RESULT=$(php cli.php --check-db 2>&1); do
    if [ $? -eq 0 ]; then
      break
    fi

    sleep 1

    COUNT=$((COUNT - 1))

    echo "Waiting to connect to the database, attempts left $COUNT"
  done

  if [ $COUNT -eq 0 ]; then
    echo "Database unavailable:"
    echo "$RESULT"
    exit 1
  fi

  echo "Waiting for amqp connection..."

  COUNT=60

  until [ $COUNT -eq 0 ] || RESULT=$(php cli.php --check-amqp 2>&1); do
    if [ $? -eq 0 ]; then
      break
    fi

    sleep 1

    COUNT=$((COUNT - 1))

    echo "Waiting to connect to the database, attempts left $COUNT"
  done

  if [ $COUNT -eq 0 ]; then
    echo "AMQP unavailable:"
    echo "$RESULT"
    exit 1
  fi

  php cli.php --init-db

  php cli.php --reindex
  php cli.php --install-crontabs

	setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX var
	setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX var

	/usr/bin/supervisord
fi

exec docker-php-entrypoint "$@"