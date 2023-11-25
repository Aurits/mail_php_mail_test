# Use an official PHP runtime as a parent image
FROM php:8.2.4-apache

# Set the working directory to /var/www/html
WORKDIR /var/www/html

# Copy composer.lock and composer.json to the working directory
COPY composer.lock composer.json /var/www/html/

# Install any needed packages
RUN apt-get update && \
    apt-get install -y git unzip && \
    docker-php-ext-install pdo pdo_mysql

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Run composer install to install dependencies
RUN composer install --no-scripts --no-autoloader

# Copy the rest of the application code to the working directory
COPY . /var/www/html/

# Run composer install with autoloader optimization
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage

# Make port 80 available to the world outside this container
EXPOSE 80

# Define environment variables
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Enable Apache modules and set the document root
RUN a2enmod rewrite && \
    sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Start Apache
CMD ["apache2-foreground"]
