FROM php:8.1-apache

# Установка расширений PHP
RUN docker-php-ext-install pdo pdo_mysql && \
    docker-php-ext-enable pdo_mysql

# Включение mod_rewrite для Apache
RUN a2enmod rewrite

# Копирование PHP-скрипта
COPY search.php /var/www/html/
COPY index.php /var/www/html/
COPY php.ini /usr/local/etc/php/

# Настройка прав
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
