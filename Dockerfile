# Custom WordPress image with WP-CLI and Certbot
FROM wordpress:latest

# Install dependencies
RUN apt-get update && apt-get install -y \
    certbot \
    python3-certbot-apache \
    less \
    && rm -rf /var/lib/apt/lists/*

# Install WP-CLI
RUN curl -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
    chmod +x /usr/local/bin/wp

# Enable Apache modules for SSL and rewrite
RUN a2enmod ssl rewrite

# Add custom Apache configuration for HTTP to HTTPS redirect
RUN echo '<VirtualHost *:80>' > /etc/apache2/sites-available/000-default.conf && \
    echo '    ServerName dev.epicmarks.com' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    ServerAlias www.dev.epicmarks.com' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    RewriteEngine On' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    RewriteCond %{HTTPS} off' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]' >> /etc/apache2/sites-available/000-default.conf && \
    echo '</VirtualHost>' >> /etc/apache2/sites-available/000-default.conf

# Expose HTTP and HTTPS ports
EXPOSE 80 443

# Use the default WordPress entrypoint
CMD ["apache2-foreground"]
