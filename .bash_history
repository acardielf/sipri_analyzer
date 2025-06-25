XDEBUG_SESSION=1 PHP_IDE_CONFIG="serverName=symfony" php bin/console sipri:ex 1
XDEBUG_SESSION=1 PHP_IDE_CONFIG="serverName=symfony" php bin/console sipri:ex 1
php bin/console cache:clear
php bin/console cache:warmup
XDEBUG_SESSION=1 PHP_IDE_CONFIG="serverName=symfony" php bin/console sipri:ex 1
XDEBUG_SESSION=1 PHP_IDE_CONFIG="serverName=symfony" php bin/console sipri:del 1
composer install
XDEBUG_SESSION=1 PHP_IDE_CONFIG="serverName=symfony" php bin/console sipri:get 1
XDEBUG_SESSION=1 PHP_IDE_CONFIG="serverName=symfony" php bin/console sipri:ex 1
XDEBUG_SESSION=1 PHP_IDE_CONFIG="serverName=symfony" php bin/console sipri:ex 1
XDEBUG_SESSION=1 PHP_IDE_CONFIG="serverName=symfony" php bin/console sipri:ex 1
XDEBUG_SESSION=1 PHP_IDE_CONFIG="serverName=symfony" php bin/console sipri:ex 2
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
XDEBUG_SESSION=1 PHP_IDE_CONFIG="serverName=symfony" php bin/console sipri:ex 1
XDEBUG_SESSION=1 PHP_IDE_CONFIG="serverName=symfony" php bin/console sipri:get 1
php bin/console sipri:ex 1
for i in {1..388}; do php bin/console sipri:ex "$i" done  
for i in {1..388}; do php bin/console sipri:ex "$i" done  ; 
for i in {1..388} do php bin/console sipri:ex "$i"; done 
for i in {1..388}; do php bin/console sipri:ex "$i"; done
php bin/console sipri:del 82
php bin/console sipri:del 83
php bin/console sipri:del 84
php -i | grep "memory"
php -i | grep 'memory_limit'
exit
