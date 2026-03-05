FROM php:8.2-apache

# 1. Install system dependencies for Composer/Twig
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql zip

# 2. Apache configuration
RUN a2enmod headers rewrite ssl
COPY apache/vhosts.conf /etc/apache2/sites-available/000-default.conf
COPY apache/entrypoint.sh /entrypoint.sh

# 3. Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN chmod +x /entrypoint.sh

# Important: We don't COPY the source code here because 
# the docker-compose volume handles it for development.

EXPOSE 80 443
ENTRYPOINT ["/entrypoint.sh"]