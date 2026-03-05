# Use the official PHP image with Apache
FROM php:8.1-apache

# Copy the backend code to the container
COPY Backend/ /var/www/html/

# Install mysqli extension if not included
RUN docker-php-ext-install mysqli

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]