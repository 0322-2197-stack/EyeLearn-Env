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

# Configure Apache ServerName
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Create startup script that configures PORT at runtime
RUN echo '#!/bin/bash\n\
    set -e\n\
    \n\
    # Use Railway PORT or default to 80\n\
    PORT=${PORT:-80}\n\
    \n\
    echo "Configuring Apache to listen on port $PORT"\n\
    \n\
    # Update ports.conf\n\
    echo "Listen $PORT" > /etc/apache2/ports.conf\n\
    \n\
    # Update default site\n\
    sed -i "s/<VirtualHost \\*:.*>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf\n\
    \n\
    echo "Apache configured for port $PORT"\n\
    \n\
    # Start Apache in foreground\n\
    exec apache2-foreground\n\
    ' > /usr/local/bin/docker-entrypoint.sh

# Make startup script executable
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Use the startup script as entrypoint
CMD ["/usr/local/bin/docker-entrypoint.sh"]
