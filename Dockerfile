# Use official PHP 8.3 Apache image
FROM php:8.3-apache

# Enable Apache mod_rewrite (if needed for pretty URLs)
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy all project files
COPY . /var/www/html/

# Set recommended PHP settings
RUN echo "display_errors=On" > /usr/local/etc/php/conf.d/display_errors.ini \
    && echo "error_reporting=E_ALL" > /usr/local/etc/php/conf.d/error_reporting.ini

# Set permissions (optional, for development)
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache (default CMD) 