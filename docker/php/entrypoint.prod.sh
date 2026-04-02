#!/bin/sh
set -e

echo "[entrypoint] Copie des assets publics des bundles..."
php bin/console assets:install public --no-debug

echo "[entrypoint] Exécution des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --no-debug

echo "[entrypoint] Préchauffage du cache..."
php bin/console cache:warmup --no-debug

echo "[entrypoint] Démarrage de PHP-FPM..."
exec php-fpm
