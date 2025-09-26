#!/bin/bash
# docker/scripts/docker-entrypoint.sh

set -e

echo "Starting Web 1.0 Portfolio Development Container..."

# Only change permissions in production mode
# In development, preserve host filesystem permissions for editing
if [ "$ENVIRONMENT" != "development" ]; then
    # Fix permissions if needed (for volume mounts)
    if [ -d "/var/www/web1-site1" ]; then
        echo "Setting permissions for web directory (production mode)..."
        chown -R www-data:www-data /var/www/web1-site1
        find /var/www/web1-site1 -type f -exec chmod 644 {} \;
        find /var/www/web1-site1 -type d -exec chmod 755 {} \;
    fi
else
    echo "Development mode: Preserving host filesystem permissions for editing..."
    # Just ensure the web server can read the files (they should already be readable)
    # Don't change ownership to allow host user to edit files
fi

# Ensure counter storage directory exists with correct permissions
if [ ! -d "/var/lib/web1-site1-counter" ]; then
    echo "Creating counter storage directory..."
    mkdir -p /var/lib/web1-site1-counter/ips
fi

# Initialize or preserve counter file
if [ ! -f "/var/lib/web1-site1-counter/counter.txt" ]; then
    echo "Initializing counter file..."
    echo "0" > /var/lib/web1-site1-counter/counter.txt
fi

# Set counter permissions
chown -R www-data:www-data /var/lib/web1-site1-counter
chmod 755 /var/lib/web1-site1-counter
chmod 755 /var/lib/web1-site1-counter/ips
chmod 666 /var/lib/web1-site1-counter/counter.txt

# Create PHP session directory if it doesn't exist
mkdir -p /var/lib/php/sessions
chown -R www-data:www-data /var/lib/php/sessions
chmod 733 /var/lib/php/sessions

# Test nginx configuration
nginx -t

echo "Container initialization complete. Starting services..."

# Execute CMD
exec "$@"