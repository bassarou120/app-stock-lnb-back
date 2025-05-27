#!/bin/sh

# Run migrations and seeds
php artisan migrate --force
php artisan db:seed --force

# Passport
#php artisan passport:install --force
php artisan passport:client --personal
#php artisan passport:keys --force


php artisan passport:keys


chmod 755 /var/www/html/storage/oauth-*

chmod 755 -R /var/www/html/storage/
php artisan  storage:link



# Set permissions
chmod 755 /var/www/html/.env
chown -R lnb:lnb /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/files/* /var/www/html/.env
# Execute the CMD
exec "$@"
