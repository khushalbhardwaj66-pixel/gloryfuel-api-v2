FROM php:8.2-apache

# Install PDO MySQL extension for TiDB
RUN docker-php-ext-install pdo pdo_mysql

# Copy your code to the server
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/

# Expose the port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
