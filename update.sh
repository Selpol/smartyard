#/bin/bash

cd /opt/rbt

git pull

cd /opt/rbt/server

composer install --no-dev --optimize-autoloader

php cli.php kernel:optimize

php cli.php db:init
php cli.php role:init

php cli.php cron:uninstall
php cli.php cron:install

supervisorctl restart all