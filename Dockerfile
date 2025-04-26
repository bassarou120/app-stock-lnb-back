# Use a minimal base image
FROM php:8.2-apache-bullseye

# Install system dependencies and clean up
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev libpq-dev git libpng-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /var/cache/apt/archives/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install PHP extensions
RUN docker-php-ext-install gd  pdo_pgsql zip bcmath

# Configure PHP settings
#COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Configure Apache DocumentRoot
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Install Composer as root
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Create a non-root user
RUN useradd -m lnb
USER lnb
WORKDIR /var/www/html

# Copy application code
COPY --chown=lnb:lnb . /var/www/html
COPY --chown=lnb:lnb .env.preprod  /var/www/html/.env
# Install project dependencies
RUN composer install --no-dev --optimize-autoloader

# Set file permissions
RUN chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

USER root
# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER lnb
# Run the entrypoint script
ENTRYPOINT ["entrypoint.sh"]

# Start Apache in the foreground
CMD ["apache2-foreground"]