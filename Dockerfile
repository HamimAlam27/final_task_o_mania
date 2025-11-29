FROM php:8.2-fpm

# Install dependencies: nginx, supervisor, envsubst (gettext), and cleanup
RUN apt-get update \
	&& apt-get install -y --no-install-recommends \
	   nginx \
	   supervisor \
	   gettext-base \
	&& docker-php-ext-install mysqli pdo pdo_mysql \
	&& apt-get clean \
	&& rm -rf /var/lib/apt/lists/*

# Copy project files
COPY . /var/www/html

# Copy nginx template and supervisor config and start script
COPY docker/nginx/default.render.conf.template /etc/nginx/conf.d/default.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Set working directory
WORKDIR /var/www/html

# Expose the port that the container will listen on (Render sets $PORT at runtime)
EXPOSE 8080

# Use the start script to render nginx config and run supervisord
CMD ["/usr/local/bin/start.sh"]
