#!/bin/sh

set -e

if [ "${1#-}" != "$1" ]; then
	set -- php-fpm "$@"
fi

if [ "$1" = 'php-fpm' ] || [ "$1" = 'php' ]; then
  php cli.php --optimize-kernel

  echo "Ожидание подключения к базе данных..."

  COUNT=60

  until [ $COUNT -eq 0 ] || RESULT=$(php cli.php --check-db 2>&1); do
    if [ $? -eq 0 ]; then
      break
    fi

    sleep 1

    COUNT=$((COUNT - 1))

    echo "Ожидание подключения к базе данных, осталось попыток $COUNT"
  done

  if [ $COUNT -eq 0 ]; then
    echo "База данных не доступна:"
    echo "$RESULT"
    exit 1
  fi

  php cli.php --init-db

  php cli.php --reindex
  php cli.php --install-crontabs

	setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX var
	setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX var
fi

exec docker-php-entrypoint "$@"