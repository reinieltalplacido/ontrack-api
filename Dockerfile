FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers \
    && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

# Configure PHP limits for base64 file uploads (up to 15MB raw -> ~20MB base64) and PH timezone
RUN { \
        echo "upload_max_filesize = 32M"; \
        echo "post_max_size = 32M"; \
        echo "memory_limit = 256M"; \
        echo "date.timezone = Asia/Manila"; \
    } > /usr/local/etc/php/conf.d/ontrack.ini

# Configure Apache vhost template supporting both /api/... and /ontrack-api/api/...
RUN { \
        echo '<VirtualHost *:__PORT__>'; \
        echo '    DocumentRoot /var/www/html'; \
        echo '    Alias /ontrack-api /var/www/html'; \
        echo ''; \
        echo '    <Directory /var/www/html>'; \
        echo '        Options -Indexes +FollowSymLinks'; \
        echo '        AllowOverride All'; \
        echo '        Require all granted'; \
        echo '    </Directory>'; \
        echo ''; \
        echo '    <Directory /var/www/html/uploads>'; \
        echo '        Options -Indexes'; \
        echo '        Header set Access-Control-Allow-Origin "*"'; \
        echo '        <FilesMatch "\.ph(p[0-9]?|tml)$">'; \
        echo '            Require all denied'; \
        echo '        </FilesMatch>'; \
        echo '    </Directory>'; \
        echo ''; \
        echo '    <FilesMatch "^\.">'; \
        echo '        Require all denied'; \
        echo '    </FilesMatch>'; \
        echo ''; \
        echo '    ErrorLog ${APACHE_LOG_DIR}/error.log'; \
        echo '    CustomLog ${APACHE_LOG_DIR}/access.log combined'; \
        echo '</VirtualHost>'; \
    } > /etc/apache2/sites-available/ontrack.conf.template

WORKDIR /var/www/html
COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads/tasks /var/www/html/uploads/reports \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 8080

CMD ["sh", "-c", "mkdir -p /var/www/html/uploads/tasks /var/www/html/uploads/reports && chown -R www-data:www-data /var/www/html/uploads && chmod -R 775 /var/www/html/uploads && printf 'Listen %s\n' \"${PORT:-80}\" > /etc/apache2/ports.conf && sed \"s/__PORT__/${PORT:-80}/g\" /etc/apache2/sites-available/ontrack.conf.template > /etc/apache2/sites-available/000-default.conf && exec apache2-foreground"]
