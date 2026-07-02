FROM php:8.2-apache

# Install PDO MySQL extension and other common extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip

# Enable Apache rewrite (if needed)
RUN a2enmod rewrite

# Copy application files
WORKDIR /var/www/html
COPY . /var/www/html

# Ensure correct permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

CMD ["apache2-foreground"]
