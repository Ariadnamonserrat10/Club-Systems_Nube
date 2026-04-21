FROM php:8.1-apache

# Cambiar Apache al puerto 8080 que requiere Cloud Run
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf && \
    sed -i 's/:80>/:8080>/g' /etc/apache2/sites-enabled/000-default.conf

COPY Backend/ /var/www/html/

RUN docker-php-ext-install mysqli

EXPOSE 8080

CMD ["apache2-foreground"]
