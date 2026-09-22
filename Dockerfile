FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libonig-dev ca-certificates \
    && docker-php-ext-install pdo_pgsql mbstring \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/calculadora
COPY . .
COPY deploy/apache.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/start.sh /usr/local/bin/start-calculadora
RUN chmod +x /usr/local/bin/start-calculadora \
    && chown -R www-data:www-data storage \
    && printf 'expose_php=Off\ndisplay_errors=Off\nlog_errors=On\n' > /usr/local/etc/php/conf.d/production.ini
EXPOSE 10000
CMD ["start-calculadora"]
