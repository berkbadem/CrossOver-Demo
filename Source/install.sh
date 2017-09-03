#!/bin/bash

# -------------------
# Crossover.dev installation script
# -------------------


if [ ! -f "composer.phar" ]; then
    curl -s http://getcomposer.org/installer | /usr/bin/php
fi

echo "Prepping for installation:"

# Install dependencies
echo "Running composer install..."
/usr/bin/php composer.phar install --no-dev --optimize-autoloader

echo "Clearing cache..."
/usr/bin/php app/console cache:clear --env=prod

echo "Installing assets..."
/usr/bin/php app/console assets:install --env=prod

echo "Updating database..."
/usr/bin/php app/console doctrine:database:drop --force
/usr/bin/php app/console doctrine:database:create
/usr/bin/php app/console doctrine:schema:update --force

if [ ! -f "phpunit.phar" ]; then
	curl https://phar.phpunit.de/phpunit-5.7.21.phar > phpunit.phar
fi

echo "Installation complate:"


