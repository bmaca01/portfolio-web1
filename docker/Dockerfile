# docker/Dockerfile
FROM ubuntu:24.04

# Prevent interactive prompts during package installation
ENV DEBIAN_FRONTEND=noninteractive
ENV LANG=C.UTF-8

# Install required packages
RUN apt-get update && apt-get install -y \
    nginx \
    php8.3-fpm \
    php8.3-cli \
    php8.3-gd \
    supervisor \
    curl \
    vim \
    nodejs \
    npm \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install browser-sync globally for hot-reloading
RUN npm install -g browser-sync

# Create required directories matching production
RUN mkdir -p /var/www/web1-site1 \
    /var/lib/web1-site1-counter/ips \
    /home/deploy \
    /var/run/php \
    /var/log/supervisor

# Create www-data user if not exists and set proper permissions
RUN id -u www-data &>/dev/null || useradd -r -s /bin/false www-data

# Add www-data to supplementary groups for better dev compatibility
# This allows www-data to read files owned by various UIDs in dev mode
RUN usermod -a -G users www-data

# Copy configuration files
COPY nginx/web1-site1.conf /etc/nginx/sites-available/web1-site1
COPY php/www.conf /etc/php/8.3/fpm/pool.d/www.conf
COPY supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY scripts/docker-entrypoint.sh /usr/local/bin/

# Copy browser-sync configuration
RUN mkdir -p /etc/browser-sync
COPY bs-config.js /etc/browser-sync/bs-config.js

# Enable nginx site
RUN ln -sf /etc/nginx/sites-available/web1-site1 /etc/nginx/sites-enabled/ \
    && rm -f /etc/nginx/sites-enabled/default

# Set permissions for counter storage
RUN chown -R www-data:www-data /var/lib/web1-site1-counter \
    && chmod 755 /var/lib/web1-site1-counter \
    && chmod 755 /var/lib/web1-site1-counter/ips

# Initialize counter file
RUN echo "0" > /var/lib/web1-site1-counter/counter.txt \
    && chmod 666 /var/lib/web1-site1-counter/counter.txt \
    && chown www-data:www-data /var/lib/web1-site1-counter/counter.txt

# Make entrypoint executable
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port 8084 to match production
EXPOSE 8084

# Set working directory
WORKDIR /var/www/web1-site1

# Use entrypoint script
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]