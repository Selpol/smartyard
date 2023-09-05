#/bin/bash

cd /opt/rbt

git pull

cd /opt/rbt/server

composer install --no-dev --optimize-autoloader

php cli.php --reindex
php cli.php --optimize-kernel

supervisorctl restart all