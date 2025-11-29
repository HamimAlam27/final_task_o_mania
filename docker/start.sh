#!/usr/bin/env bash
set -e

# Render provides $PORT. Provide a default if not set for local/dev.
: ${PORT:=8080}

# Render nginx template substitution: replace ${PORT} in template
if [ -f /etc/nginx/conf.d/default.conf.template ]; then
  envsubst '${PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf
fi

# Ensure www-data owns files
chown -R www-data:www-data /var/www/html || true

# Start supervisord in foreground (configured to run php-fpm and nginx)
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
