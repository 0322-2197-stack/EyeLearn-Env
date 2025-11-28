# EyeLearn Docker Configuration for Railway
FROM php:8.1-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure Apache to use PORT environment variable (Railway requirement)
RUN echo "Listen \${PORT:-80}" > /etc/apache2/ports.conf && \
    sed -i 's/80/${PORT}/' /etc/apache2/sites-available/000-default.conf

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Create startup script that sets PORT
RUN echo '#!/bin/bash\n\
    export PORT=${PORT:-80}\n\
    sed -i "s/Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf\n\
    sed -i "s/<VirtualHost \*:.*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf\n\
    apache2-foreground' > /start.sh && chmod +x /start.sh

# Start Apache with dynamic PORT support
CMD ["/start.sh"]
