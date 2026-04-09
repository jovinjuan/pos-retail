#!/bin/sh
set -e

# Fix permissions on mounted volume
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Run PHP-FPM
exec php-fpm
