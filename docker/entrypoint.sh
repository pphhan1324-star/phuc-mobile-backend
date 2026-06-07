#!/bin/sh

# Đảm bảo quyền ghi vào lúc runtime
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

echo "⏳ Waiting for database..."
sleep 5

echo "🔧 Checking migration status..."
#php artisan migrate:status

echo "🔧 Running migrate and seed..."
php artisan migrate --force --seed

# reset db va chay lai seed
# php artisan migrate:fresh --seed --force

echo "📄 Publishing Swagger assets..."
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --force || true

# Copy swagger-ui dist files into public directory so edge/nginx can serve them
# with correct Content-Type instead of relying on the Laravel route.
echo "📂 Copying Swagger UI distribution into public/docs/asset..."
rm -rf public/docs/asset || true
mkdir -p public/docs/asset
cp -R vendor/swagger-api/swagger-ui/dist/* public/docs/asset/ || true


echo "📄 Generating Swagger docs..."
php artisan l5-swagger:generate

echo "🧹 Clearing caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

echo "⚡ Caching config + views..."
php artisan config:cache || true
php artisan view:cache || true

echo "🚀 Starting services..."
php-fpm -D
exec nginx -g "daemon off;"