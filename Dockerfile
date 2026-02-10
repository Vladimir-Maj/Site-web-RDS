FROM php:8.2-apache

# Install extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable headers (Required for CORS) and rewrite
RUN a2enmod headers rewrite

# Copy your config
COPY apache/vhosts.conf /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html