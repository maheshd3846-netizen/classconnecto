FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application files to the Apache document root
COPY . /var/www/html/

# Make the uploads directory writable
RUN mkdir -p /var/www/html/uploads/ && chmod -R 777 /var/www/html/uploads/

# Expose port 80
EXPOSE 80
