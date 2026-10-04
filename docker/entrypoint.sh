#!/bin/sh
set -e

# Pastikan folder storage dan cache tersedia dengan permission yang benar
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/storage/app/public \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Generate symlink storage jika belum ada
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link || true
fi

# Jalankan perintah container (php-fpm, worker, scheduler, dll)
exec "$@"
