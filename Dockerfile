FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN a2enmod headers rewrite ssl

COPY apache/vhosts.conf /etc/apache2/sites-available/000-default.conf

COPY apache/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80 443

ENTRYPOINT ["/entrypoint.sh"]
