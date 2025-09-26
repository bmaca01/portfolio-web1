# Docker Development Container Plan for Web 1.0 Portfolio Site

## Overview
This plan provides a complete Docker-based development environment that mirrors the production Ubuntu 24.04 server hosting the Web 1.0 portfolio site. The containerized environment ensures development/production parity while maintaining ease of local development.

## 1. Docker Architecture

### Approach: Single Container with Supervisor
We'll use a single Docker container running all required services (Nginx, PHP-FPM) managed by supervisor. This approach:
- Simplifies local development setup
- Mirrors the production "single server" architecture
- Maintains service interdependencies similar to production
- Allows easy port mapping and volume mounting

### Base Image Selection
- **Primary**: `ubuntu:24.04` - Exact production parity
- **Alternative**: `ubuntu:noble` (24.04 LTS alias)
- Rationale: While Alpine would be lighter, Ubuntu ensures identical package versions and behaviors

### Directory Structure
```
web1-site1/
├── docker/
│   ├── Dockerfile
│   ├── nginx/
│   │   └── web1-site1.conf
│   ├── php/
│   │   └── www.conf
│   ├── supervisor/
│   │   └── supervisord.conf
│   └── scripts/
│       └── docker-entrypoint.sh
├── docker-compose.yml
├── .dockerignore
└── website/
    └── [existing site files]
```

## 2. Service Configuration

### Dockerfile
```dockerfile
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
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Create required directories matching production
RUN mkdir -p /var/www/web1-site1 \
    /var/lib/web1-site1-counter/ips \
    /home/deploy \
    /var/run/php \
    /var/log/supervisor

# Create www-data user if not exists and set proper permissions
RUN id -u www-data &>/dev/null || useradd -r -s /bin/false www-data

# Copy configuration files
COPY nginx/web1-site1.conf /etc/nginx/sites-available/web1-site1
COPY php/www.conf /etc/php/8.3/fpm/pool.d/www.conf
COPY supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY scripts/docker-entrypoint.sh /usr/local/bin/

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
```

### Nginx Configuration
```nginx
# docker/nginx/web1-site1.conf
server {
    listen 8084 default_server;
    listen [::]:8084 default_server;

    root /var/www/web1-site1;
    index index.html index.htm;

    server_name _;

    # Security headers for iframe compatibility
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        try_files $uri $uri/ =404;
    }

    # PHP processing for counter directory
    location ~ ^/counter/.*\.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Block access to sensitive files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~ /counter/counter\.txt$ {
        deny all;
    }

    location ~ /counter/ips/ {
        deny all;
    }

    # Serve GIF images with proper MIME type
    location ~ \.(gif|jpg|jpeg|png|ico)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Error pages
    error_page 404 /404.html;
    error_page 500 502 503 504 /50x.html;

    # Logs (development mode - more verbose)
    access_log /var/log/nginx/web1-site1.access.log;
    error_log /var/log/nginx/web1-site1.error.log debug;
}
```

### PHP-FPM Configuration
```ini
# docker/php/www.conf
[www]
user = www-data
group = www-data
listen = /var/run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500

; Enable error logging for development
php_flag[display_errors] = on
php_admin_value[error_log] = /var/log/php8.3-fpm.log
php_admin_flag[log_errors] = on

; Set session path
php_value[session.save_path] = /var/lib/php/sessions

; Security settings matching production
php_admin_value[expose_php] = Off
php_admin_value[allow_url_fopen] = Off
php_admin_value[allow_url_include] = Off
```

### Supervisor Configuration
```ini
# docker/supervisor/supervisord.conf
[supervisord]
nodaemon=true
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid

[program:php-fpm]
command=/usr/sbin/php-fpm8.3 -F
autostart=true
autorestart=true
priority=5
stdout_logfile=/var/log/supervisor/php-fpm.log
stderr_logfile=/var/log/supervisor/php-fpm.error.log

[program:nginx]
command=/usr/sbin/nginx -g 'daemon off;'
autostart=true
autorestart=true
priority=10
stdout_logfile=/var/log/supervisor/nginx.log
stderr_logfile=/var/log/supervisor/nginx.error.log
```

### Docker Entrypoint Script
```bash
#!/bin/bash
# docker/scripts/docker-entrypoint.sh

set -e

echo "Starting Web 1.0 Portfolio Development Container..."

# Fix permissions if needed (for volume mounts)
if [ -d "/var/www/web1-site1" ]; then
    echo "Setting permissions for web directory..."
    chown -R www-data:www-data /var/www/web1-site1
    find /var/www/web1-site1 -type f -exec chmod 644 {} \;
    find /var/www/web1-site1 -type d -exec chmod 755 {} \;
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
```

## 3. Development Workflow

### Docker Compose Configuration
```yaml
# docker-compose.yml
version: '3.8'

services:
  web1-site1:
    build:
      context: ./docker
      dockerfile: Dockerfile
    container_name: web1-site1-dev
    ports:
      - "8084:8084"  # Match production port
    volumes:
      # Mount website files for hot-reloading
      - ./website:/var/www/web1-site1:rw
      # Persist counter data across container restarts
      - counter_data:/var/lib/web1-site1-counter
      # Optional: Mount logs for debugging
      - ./logs/nginx:/var/log/nginx
      - ./logs/php:/var/log/php8.3-fpm.log
    environment:
      - ENVIRONMENT=development
      - TZ=America/New_York
    restart: unless-stopped
    networks:
      - web1_network

volumes:
  counter_data:
    driver: local

networks:
  web1_network:
    driver: bridge
```

### .dockerignore File
```
# .dockerignore
.git
.gitignore
*.md
.DS_Store
node_modules
.env
.env.*
logs/
.claude/
.github/
*.swp
*.swo
*~
```

## 4. Production Parity Checklist

### File Structure Matching
- ✅ Web root at `/var/www/web1-site1/`
- ✅ Counter persistence at `/var/lib/web1-site1-counter/`
- ✅ PHP-FPM socket at `/var/run/php/php8.3-fpm.sock`
- ✅ Nginx listening on port 8084

### Service Versions
- ✅ Ubuntu 24.04 base image
- ✅ Nginx (latest from Ubuntu 24.04 repos)
- ✅ PHP 8.3-FPM
- ✅ Same file permissions (644/755)
- ✅ www-data user/group ownership

### Security Configuration
- ✅ Nginx security headers
- ✅ Block access to sensitive files
- ✅ PHP security settings matching production

## 5. Setup Instructions

### Prerequisites
- Docker Desktop or Docker Engine installed
- Docker Compose installed (usually included with Docker Desktop)
- Git for cloning the repository

### Initial Setup

1. **Create Docker directories and files:**
```bash
# From repository root
mkdir -p docker/{nginx,php,supervisor,scripts}

# Create all configuration files as shown above
# Use your preferred editor to create each file
```

2. **Build the Docker image:**
```bash
# Build using docker-compose
docker-compose build

# OR build directly with Docker
docker build -t web1-site1-dev ./docker
```

3. **Start the development container:**
```bash
# Using docker-compose (recommended)
docker-compose up -d

# OR run directly with Docker
docker run -d \
  --name web1-site1-dev \
  -p 8084:8084 \
  -v $(pwd)/website:/var/www/web1-site1:rw \
  -v web1_counter_data:/var/lib/web1-site1-counter \
  web1-site1-dev
```

4. **Verify the container is running:**
```bash
# Check container status
docker-compose ps

# View logs
docker-compose logs -f

# Test the website
curl http://localhost:8084
```

### Development Commands

```bash
# Start container
docker-compose up -d

# Stop container
docker-compose down

# Restart container (after config changes)
docker-compose restart

# View logs
docker-compose logs -f

# Enter container shell
docker-compose exec web1-site1 bash

# Run commands inside container
docker-compose exec web1-site1 nginx -t  # Test nginx config
docker-compose exec web1-site1 php -v    # Check PHP version

# Reset counter for testing
docker-compose exec web1-site1 bash -c 'echo "0" > /var/lib/web1-site1-counter/counter.txt'

# View counter value
docker-compose exec web1-site1 cat /var/lib/web1-site1-counter/counter.txt
```

### Testing Procedures

1. **Basic Functionality Test:**
```bash
# Test main page loads
curl -I http://localhost:8084/

# Test PHP counter works
curl http://localhost:8084/counter/counter.php

# Check counter incremented
docker-compose exec web1-site1 cat /var/lib/web1-site1-counter/counter.txt
```

2. **Hot-Reload Test:**
```bash
# Edit any HTML file in website/
echo "<!-- Test Comment -->" >> website/index.html

# Refresh browser - changes should appear immediately
# No container restart needed
```

3. **Counter Persistence Test:**
```bash
# Note current counter value
docker-compose exec web1-site1 cat /var/lib/web1-site1-counter/counter.txt

# Restart container
docker-compose restart

# Verify counter value persisted
docker-compose exec web1-site1 cat /var/lib/web1-site1-counter/counter.txt
```

### IDE Integration

#### VS Code with Docker Extension
1. Install "Docker" extension by Microsoft
2. Install "Remote - Containers" extension
3. Right-click on running container → "Attach Visual Studio Code"
4. Edit files directly inside container with full IntelliSense

#### DevContainer Configuration (Optional)
Create `.devcontainer/devcontainer.json`:
```json
{
  "name": "Web1 Site1 Dev",
  "dockerComposeFile": "../docker-compose.yml",
  "service": "web1-site1",
  "workspaceFolder": "/var/www/web1-site1",
  "customizations": {
    "vscode": {
      "extensions": [
        "bmewburn.vscode-intelephense-client",
        "nginx.nginx",
        "redhat.vscode-yaml"
      ],
      "settings": {
        "terminal.integrated.defaultProfile.linux": "bash"
      }
    }
  },
  "forwardPorts": [8084],
  "postCreateCommand": "echo 'Container ready for development!'"
}
```

## 6. Optional Enhancements

### Environment-Specific Configuration

Create `.env` file for environment variables:
```bash
# .env
ENVIRONMENT=development
COUNTER_RESET=false
NGINX_PORT=8084
PHP_DISPLAY_ERRORS=on
PHP_ERROR_REPORTING=E_ALL
```

Update docker-compose.yml to use env file:
```yaml
services:
  web1-site1:
    env_file: .env
```

### Mock Webhook Endpoint for Testing

Add webhook simulator to Dockerfile:
```dockerfile
# Install webhook package for testing deployments
RUN apt-get update && apt-get install -y webhook
```

Add webhook configuration:
```json
# docker/webhook/hooks.json
[
  {
    "id": "deploy-web1-site1",
    "execute-command": "/home/deploy/mock-deploy.sh",
    "command-working-directory": "/home/deploy"
  }
]
```

Create mock deployment script:
```bash
#!/bin/bash
# docker/scripts/mock-deploy.sh
echo "[$(date)] Mock deployment triggered" >> /home/deploy/deploy.log
echo "Simulating git pull..."
echo "Simulating rsync..."
echo "Deployment complete!"
```

### Development vs Production Modes

Create mode-specific PHP configurations:
```bash
# Development mode (docker/php/development.ini)
display_errors = On
error_reporting = E_ALL
log_errors = On

# Production mode (docker/php/production.ini)
display_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
log_errors = On
```

### Automated Testing Script

Create `docker/scripts/test.sh`:
```bash
#!/bin/bash
# Automated testing script for container

echo "Running Web 1.0 Site Tests..."

# Test 1: Nginx configuration
echo -n "Testing Nginx configuration... "
nginx -t 2>/dev/null && echo "PASS" || echo "FAIL"

# Test 2: PHP-FPM running
echo -n "Testing PHP-FPM... "
pgrep php-fpm > /dev/null && echo "PASS" || echo "FAIL"

# Test 3: Website accessible
echo -n "Testing website response... "
curl -s -o /dev/null -w "%{http_code}" http://localhost:8084 | grep -q "200" && echo "PASS" || echo "FAIL"

# Test 4: Counter functionality
echo -n "Testing counter... "
BEFORE=$(cat /var/lib/web1-site1-counter/counter.txt)
curl -s http://localhost:8084/counter/counter.php > /dev/null
AFTER=$(cat /var/lib/web1-site1-counter/counter.txt)
[ "$AFTER" -gt "$BEFORE" ] && echo "PASS" || echo "FAIL"

# Test 5: File permissions
echo -n "Testing file permissions... "
[ $(stat -c %a /var/www/web1-site1) = "755" ] && echo "PASS" || echo "FAIL"

echo "Tests complete!"
```

### Quick Start Makefile

Create `Makefile` in repository root:
```makefile
# Makefile for Web 1.0 Site Development

.PHONY: build up down restart logs shell test clean

build:
	docker-compose build

up:
	docker-compose up -d
	@echo "Site running at http://localhost:8084"

down:
	docker-compose down

restart:
	docker-compose restart

logs:
	docker-compose logs -f

shell:
	docker-compose exec web1-site1 bash

test:
	docker-compose exec web1-site1 /docker/scripts/test.sh

clean:
	docker-compose down -v
	rm -rf logs/*

reset-counter:
	docker-compose exec web1-site1 bash -c 'echo "0" > /var/lib/web1-site1-counter/counter.txt'
	@echo "Counter reset to 0"

backup-counter:
	docker-compose exec web1-site1 cat /var/lib/web1-site1-counter/counter.txt > counter_backup.txt
	@echo "Counter value backed up to counter_backup.txt"

restore-counter:
	@test -f counter_backup.txt || (echo "No backup file found" && exit 1)
	docker-compose exec web1-site1 bash -c 'echo "$(shell cat counter_backup.txt)" > /var/lib/web1-site1-counter/counter.txt'
	@echo "Counter restored from backup"
```

## Troubleshooting

### Common Issues and Solutions

1. **Port 8084 Already in Use**
   ```bash
   # Find process using port
   lsof -i :8084

   # Change port in docker-compose.yml
   ports:
     - "8085:8084"  # Use different host port
   ```

2. **Permission Denied Errors**
   ```bash
   # Reset permissions from host
   sudo chown -R $(whoami):$(whoami) ./website

   # Fix in container
   docker-compose exec web1-site1 chown -R www-data:www-data /var/www/web1-site1
   ```

3. **Counter Not Incrementing**
   ```bash
   # Check PHP errors
   docker-compose exec web1-site1 tail -f /var/log/php8.3-fpm.log

   # Verify counter file permissions
   docker-compose exec web1-site1 ls -la /var/lib/web1-site1-counter/
   ```

4. **Changes Not Reflecting**
   ```bash
   # Clear browser cache
   # OR test with curl
   curl -H "Cache-Control: no-cache" http://localhost:8084/
   ```

## Performance Optimization

### Docker Build Caching
```dockerfile
# Optimize Dockerfile for better caching
# Place least-changing items first
FROM ubuntu:24.04

# System packages (rarely change)
RUN apt-get update && apt-get install -y [packages]

# Configuration files (occasional changes)
COPY nginx/ /etc/nginx/

# Application code (frequent changes)
COPY website/ /var/www/web1-site1/
```

### Volume Performance (macOS/Windows)
```yaml
# Use delegated consistency for better performance
volumes:
  - ./website:/var/www/web1-site1:delegated
```

## Security Considerations

### Development-Only Settings
- PHP error display enabled for debugging
- Verbose logging enabled
- No HTTPS/TLS (use production Cloudflare Tunnel for that)
- Simplified authentication (no webhook secrets)

### Production Deployment
This container is for **development only**. For production:
- Use the existing server with Cloudflare Tunnel
- Maintain webhook authentication
- Keep production security headers
- Use proper TLS termination

## Summary

This Docker setup provides:
- ✅ Complete production parity with Ubuntu 24.04
- ✅ Hot-reloading for rapid development
- ✅ Persistent visitor counter
- ✅ Identical file structure and permissions
- ✅ Easy startup with single command
- ✅ IDE integration support
- ✅ Testing and debugging tools

To get started:
```bash
# Clone repo and enter directory
git clone [repository-url]
cd web1-site1

# Create Docker configuration files as documented above
# Then start development
docker-compose up -d

# Open browser to http://localhost:8084
```

The container will automatically sync changes to HTML/PHP files, maintain counter state across restarts, and provide a development experience identical to production.