# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.
Last Updated: 2025-09-26

## Project Overview

This is a Web 1.0 style portfolio website that captures the authentic design patterns of 1990s/early 2000s personal websites. The site uses table-based layouts, animated GIFs, and period-appropriate HTML/CSS styling.

### Current Implementation Status (2025-09-25)
- **Visitor Counter**: FULLY IMPLEMENTED AND DEPLOYED
  - PHP script (`/website/counter/counter.php`) complete with persistent storage solution
  - Counter data stored at `/var/lib/web1-site1-counter/counter.txt` to survive deployments
  - Digit images (0-9.gif) successfully created at 15x20 pixels (green LCD-style on black background)
  - Iframe width fixed from 100px to 120px to properly display all digits
  - Security files in place (`.htaccess` for Apache compatibility, `.gitignore` for counter.txt)
  - File-based storage with file locking for race condition prevention
  - Located in `/website/counter/` directory with `ips/` subdirectory for IP tracking
- **Index.html**: Fully updated with table-based iframe integration
  - HTML 4.01 Transitional doctype with authentic table layouts
  - Counter iframe styled with period-appropriate table positioning (width: 120px)
  - Background color changed to dark blue (#17263c)
  - Maintains pure static HTML nature while adding dynamic counter via iframe
- **Server Configuration**: PHP 8.3-FPM and Nginx configured and running
  - Nginx configured to process PHP in `/counter/` directory
  - PHP-FPM socket at `/var/run/php/php8.3-fpm.sock`
  - Counter persistence directory: `/var/lib/web1-site1-counter/` (requires manual setup)
- **ImageMagick Issue Resolved**: Must use `magick` command instead of `convert` for ImageMagick 7
- **Architecture Decisions**:
  - No build process required - direct HTML editing
  - Table-based layouts for authentic Web 1.0 positioning (no CSS Grid/Flexbox)
  - File-based counter storage instead of database for simplicity
  - Counter data stored outside web directory to persist across deployments
  - No JavaScript used - pure server-side PHP for counter functionality

## System Environment

- **Development Machine**: Debian Linux (6.12.48+deb13-amd64)
- **Repository Location**: `/home/abc/repos/web1-site1`
- **Remote Server**: Ubuntu 24.04 LTS (hostname: bahay)
- **SSH Access**: `ssh t1.benjmacaro.dev` (requires sudo password)
- **Domain**: benjmacaro.dev (routed through Cloudflare Tunnel)

## Architecture

### Core Design Philosophy
- **Pure Web 1.0 Implementation**: Strict adherence to 1990s web standards and patterns
- **Static-First Approach**: HTML pages with server-side PHP only for visitor counter
- **Table-Based Layouts**: All positioning via nested tables - no CSS Grid/Flexbox
- **Deprecated HTML Tags**: Intentional use of `<font>`, `<center>`, `<marquee>`, `<blink>`
- **No Build Process**: Direct HTML editing without compilation or transpilation

### Infrastructure Architecture
- **Web Server**: Nginx on Ubuntu 24.04 LTS (listening on localhost:8084)
- **PHP Runtime**: PHP 8.3-FPM via Unix socket (`/var/run/php/php8.3-fpm.sock`)
- **Routing**: Cloudflare Tunnel (no exposed ports) routes benjmacaro.dev to local services
- **CI/CD Pipeline**: Webhook-based deployment (GitHub → Cloudflare → webhook:9000 → deploy script)
- **Security Model**: Defense in depth with service isolation, strict permissions, and tunnel-only access

### Component Architecture
- **Static HTML Pages**: 7 main pages (index, home, about, portfolio, skills, guestbook, links, contact)
- **Visitor Counter Module**: PHP-based with file storage, IP deduplication, and iframe integration
- **Asset Organization**: Structured directories for images (backgrounds, buttons, counters, dividers, GIFs)
- **Media Support**: MIDI files for background music, custom cursors for enhanced UX
- **Deployment System**: Pull-based with atomic updates via rsync `--delete`

## Feature Integration Matrix

| Feature | Status | Depends On | Used By | Documentation |
|---------|--------|------------|---------|---------------|
| Docker Dev Environment | Implemented | Ubuntu 24.04, Nginx, PHP 8.3 | Local Development | container-implementation.md |
| Visitor Counter | Implemented | PHP 8.3-FPM, Nginx | Homepage (index.html) | counter-implementation.md |
| Webhook Deployment | Implemented | Cloudflare Tunnel, GitHub | All deployments | webhook-implementation.md |
| Portfolio Site Design | In Progress | - | All pages | portfolio-plan.md |

## Key Commands

### Local Development

#### Docker Container Setup (2025-09-25)
```bash
# Start development container (runs on port 8084)
docker compose up -d

# Stop container
docker compose down

# View logs
docker compose logs -f

# Enter container shell
docker compose exec web1-site1 bash

# Check container status
docker compose ps

# Rebuild after config changes
docker compose down && docker compose build && docker compose up -d
```

#### Direct File Editing
```bash
# Preview HTML files directly by opening in browser
# No build process required - pure static HTML

# Check local repository status
git status
git log --oneline -10

# View website files
ls -la website/
```

### Remote Server Management (via SSH)
```bash
# Connect to server
ssh t1.benjmacaro.dev

# Service status checks
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status webhook.service
sudo systemctl status cloudflared

# View logs
sudo journalctl -u webhook.service -n 50 --follow
sudo journalctl -u nginx -n 50
tail -f /home/deploy/deploy-web1-site1.log

# Manual deployment (if needed)
sudo -u deploy /home/deploy/deploy-web1-site1.sh

# Check deployed files
ls -la /var/www/web1-site1/
```

### Deployment Pipeline
```bash
# Deployment happens automatically via webhook on push to main branch
# Flow: GitHub push → Webhook → Cloudflare Tunnel → webhook service (port 9000) → deploy script

# Verify webhook delivery in GitHub:
# Settings → Webhooks → Recent Deliveries

# Test webhook locally:
curl -X POST http://127.0.0.1:9000/hooks/deploy-web1-site1
```

## Project Structure

### Repository Files
```
/home/abc/repos/web1-site1/
├── website/                  # All deployable site content
│   ├── index.html           # Splash page with ASCII art and ENTER button
│   ├── home.html           # Main homepage with navigation menu
│   ├── about.html          # Personal information and interests
│   ├── portfolio.html      # Project showcase in table grid
│   ├── skills.html         # Technical skills and competencies
│   ├── guestbook.html      # Visitor comments and feedback form
│   ├── links.html          # Web ring and affiliate sites
│   ├── contact.html        # Contact information and social media
│   ├── images/              # Visual assets organized by type
│   │   ├── counter/        # Digit images (0-9.gif, 15x20px LCD-style)
│   │   ├── backgrounds/    # Tiled background patterns
│   │   ├── buttons/        # 88x31 web buttons and badges
│   │   ├── dividers/       # Horizontal rule replacements
│   │   └── gifs/           # Animated decorative elements
│   ├── assets/              # Non-image media files
│   │   ├── cursors/        # Custom cursor files (.cur, .ani)
│   │   └── midi/           # Background music files
│   └── counter/             # PHP visitor counter module (COMPLETE)
│       ├── counter.php      # Main counter script with file locking
│       ├── .htaccess       # Apache security rules
│       ├── .gitignore      # Excludes dynamic files from Git
│       └── ips/            # IP tracking for unique visitors
├── .claude/                 # Claude Code configuration
│   └── settings.local.json  # Local permissions and settings
├── .github/                # Reserved for GitHub metadata
├── docker/                 # Docker configuration files (added 2025-09-25)
│   ├── Dockerfile          # Ubuntu 24.04 base image matching production
│   ├── nginx/              # Nginx server configuration
│   ├── php/                # PHP-FPM pool configuration
│   ├── supervisor/         # Process management configuration
│   └── scripts/            # Container initialization scripts
├── docker-compose.yml      # Docker orchestration configuration
├── .dockerignore          # Excludes files from Docker build context
├── CLAUDE.md              # This file - system documentation
├── portfolio-plan.md          # Detailed Web 1.0 design specifications
├── container-implementation.md # Docker development environment (implemented 2025-09-25)
├── webhook-implementation.md   # Webhook-based CI/CD deployment system
├── counter-implementation.md   # PHP visitor counter with persistent storage
└── README.md             # Basic repository readme
```

### Server Deployment Location
```
/var/www/web1-site1/        # Production website files (deployed via rsync)
/home/deploy/               # Deployment scripts and logs
├── web1-site1/            # Git repository clone for deployment
├── deploy-web1-site1.sh   # Main deployment script
└── deploy-web1-site1.log  # Deployment history and errors
```

## Important Context

### 1. Web 1.0 Design Philosophy
- **Authentic 1990s aesthetics**: Use deprecated HTML tags (`<font>`, `<center>`, `<marquee>`)
- **Table-based layouts**: No CSS Grid or Flexbox - authentic table positioning for all elements
- **HTML 4.01 Transitional**: Pure HTML without modern DOCTYPE, maintains backwards compatibility
- **Inline styles**: Period-appropriate, no external CSS frameworks
- **Animated GIFs**: Essential for authentic Web 1.0 experience
- **Visitor counter**: PHP-based with retro LCD digit display
  - Custom GIF digits (15x20 pixels, green on black)
  - File-based storage with file locking for concurrency
  - Unique visitor tracking via IP addresses
  - iframe integration maintains static HTML while adding dynamic content
  - No JavaScript - pure server-side implementation

### 2. Deployment Pipeline

**Automated Flow:**
1. Developer pushes to `main` branch on GitHub
2. GitHub webhook fires to `webhook.benjmacaro.dev`
3. Cloudflare Tunnel routes to local webhook service (port 9000)
4. Webhook service executes `/home/deploy/deploy-web1-site1.sh`
5. Deploy script performs:
   - Git pull from GitHub repository
   - Rsync files to `/var/www/web1-site1/` with `--delete` flag
   - Sets proper permissions (644 for files, 755 for dirs)
   - Logs all actions to `/home/deploy/deploy-web1-site1.log`

**Important Deployment Behavior:**
- **rsync --delete**: Removes any files in production that aren't in the repository
- **Files in .gitignore get deleted**: Counter data, logs, etc. are removed on each deployment
- **Solution for persistence**: Store dynamic data outside `/var/www/web1-site1/`
- **Counter persistence**: Uses `/var/lib/web1-site1-counter/` directory (survives deployments)

**Key Components:**
- **Deploy user**: uid=1001, member of www-data group
- **Repository clone**: `/home/deploy/web1-site1/` (for pulling updates)
- **Production files**: `/var/www/web1-site1/` (served by Nginx)
- **Persistent data**: `/var/lib/web1-site1-counter/` (counter storage)
- **Webhook config**: `/etc/webhook/hooks.json` (defines webhook endpoints)

### 3. Deployment Boundary

**Critical rsync Command:**
```bash
rsync -av --delete "$REPO_DIR/website/" "$WEB_DIR/"
# Expands to: rsync -av --delete /home/deploy/web1-site1/website/ /var/www/web1-site1/
```

**The Deployment Boundary Explained:**
The trailing slash on `website/` is crucial - it tells rsync to copy **only the contents** of the website directory, not the directory itself. This creates a clear separation between development and production files:

**Files That Deploy to Production** (`/var/www/web1-site1/`):
- ✅ `website/index.html` → `/var/www/web1-site1/index.html`
- ✅ `website/counter/` → `/var/www/web1-site1/counter/`
- ✅ `website/images/` → `/var/www/web1-site1/images/`
- ✅ All content inside `/website/` directory

**Files That Stay Development-Only** (never deployed):
- ❌ `docker/` - Docker development environment
- ❌ `docker-compose.yml` - Local container orchestration
- ❌ `.dockerignore` - Docker build exclusions
- ❌ `CLAUDE.md` - System documentation
- ❌ `*.md` files - Documentation and plans
- ❌ `.claude/` - Claude Code configuration
- ❌ Any file at repository root outside `website/`

**Security Benefits:**
- Development tools never reach production
- Documentation and configuration files stay private
- Clear separation of concerns (tooling vs content)
- Reduced attack surface on production server

### 4. Security Architecture

**Network Security:**
- **No exposed ports**: All traffic through Cloudflare Tunnel
- **Nginx**: Listens only on localhost:8084
- **Webhook**: Listens only on 127.0.0.1:9000
- **SSH**: Available via `t1.benjmacaro.dev` (Cloudflare Tunnel)

**File Security:**
- Website files: 644 (readable by all, writable by owner)
- Directories: 755 (traversable by all, writable by owner)
- Counter data: 666 (writable by PHP process)
- Ownership: `www-data:www-data` for web files

### 5. Service Configuration

**Nginx Configuration** (`/etc/nginx/sites-available/web1-site1`):
- Document root: `/var/www/web1-site1/`
- PHP-FPM integration for `/counter/` directory
- Security headers for iframe embedding
- Blocks access to sensitive files (counter.txt, .git, etc.)

**PHP 8.3-FPM**:
- Socket: `/var/run/php/php8.3-fpm.sock`
- User: www-data
- Configured for visitor counter functionality

**Webhook Service**:
- Package: `webhook` (installed via apt)
- Config: `/etc/webhook/hooks.json`
- Systemd service: `webhook.service`
- Executes with deploy user permissions

### 6. Server Environment Details
- **OS**: Ubuntu 24.04.3 LTS (Noble Numbat)
- **Hostname**: bahay
- **Web Server**: Nginx with PHP 8.3-FPM
- **Tunnel**: Cloudflare Tunnel (cloudflared service)
- **Monitoring**: systemd journals and custom deploy logs

## Development Workflow Framework

Our standard feature development follows this lifecycle:

### 1. Planning Phase
- User provides feature description
- Collaborative discussion to finalize requirements
- Create `<feature>-plan.md` with comprehensive plan
- Define success criteria and test cases upfront

### 2. Implementation Phase
- Use plan as living document
- Update continuously with:
  - Implementation decisions
  - System-specific behaviors
  - Discovered dependencies
  - Troubleshooting steps
  - Integration points

### 3. Testing Phase
- Execute predefined test cases
- Document test results in plan
- Add any discovered edge cases

### 4. Completion Phase
- Run `/update-claude` to capture learnings
- Run `/update-memory` for session-specific insights (if available)
- Rename `<feature>-plan.md` to `<feature>-implementation.md`
- Update feature integration matrix

### Standardized Plan Template
```markdown
# [Feature]-plan.md

## Status: [Planning|In Progress|Testing|Complete]
## Created: [Date]
## Last Updated: [Date]

## Feature Overview
[Brief description and business value]

## Success Criteria
- [ ] Specific measurable outcomes
- [ ] Test scenarios that must pass
- [ ] Performance requirements

## Dependencies & Prerequisites
- Required system components
- External services
- Configuration needs

## Implementation Plan
[Detailed steps]

## Architecture Decisions
[Key choices and rationale]

## Integration Points
[How this interacts with other components]

## Testing Strategy
[Test cases defined upfront]

## Rollback Plan
[How to safely revert if needed]

## Implementation Log
[Real-time updates during development]

## Gotchas & Troubleshooting
[Document as discovered]
```

### Documentation Standards
- Keep plans atomic (one feature per file)
- Use consistent section headings
- Document "why" not just "what"
- Include rollback procedures
- Capture both successes and failures
- Maintain feature integration tracking

### Git Commit Conventions
- Plan creation: `git commit -m "feat: add [feature]-plan.md"`
- Implementation: `git commit -m "feat: implement [feature]"`
- Testing: `git commit -m "test: verify [feature] implementation"`
- Completion: `git commit -m "docs: finalize [feature]-implementation.md"`

## Development Guidelines

### Code Standards
- Maintain Web 1.0 design patterns (tables, inline styles, deprecated tags)
- Keep all content static - PHP only for visitor counter
- Use HTML 4.01 Transitional DOCTYPE consistently
- Prefer inline styles over external CSS files
- Images should be compressed/dithered for authentic quality

### Naming Conventions
- **HTML Files**: Lowercase, dash-separated (e.g., `index.html`, `guestbook.html`)
- **Image Files**: Lowercase with underscores for multi-word names
- **Directories**: Lowercase, no spaces, semantic naming
- **PHP Variables**: camelCase for local variables, UPPERCASE for constants
- **Counter Files**: Descriptive names (e.g., `counter.txt`, not `data.txt`)

### Development Workflow
1. **Local Testing**: Preview HTML files directly in browser
2. **Git Commits**: Simple, concise messages without emojis
3. **Push to Main**: Triggers automatic deployment via webhook
4. **Verification**: Check deployment logs via SSH if needed

### Technical Notes
- **ImageMagick 7**: Use `magick` command instead of `convert`
- **SSH Operations**: Manual intervention required for sudo commands
- **Deployment Trigger**: Any push to `/website/` directory
- **Browser Testing**: Ensure compatibility with basic HTML rendering
- **Counter Testing**: Use curl to verify PHP endpoint: `curl http://localhost:8084/counter/counter.php`

## Visitor Counter Implementation Details

### Counter Architecture
- **Storage Method**: File-based (`counter.txt`) with PHP's `flock()` for concurrency control
- **Persistent Storage Location**: `/var/lib/web1-site1-counter/counter.txt` (survives deployments)
- **Display Method**: Individual GIF digits assembled dynamically
- **Integration**: iframe in static HTML (width: 120px to show all digits)
- **Starting Value**: 0 (can be manually edited in counter.txt if desired)

### Counter Files
```
website/counter/
├── counter.php         # Main PHP script - modified to use /var/lib storage
├── .htaccess          # Security rules (denies direct access to .txt files)
└── .gitignore         # Excludes counter.txt from version control

website/images/counter/
├── 0.gif through 9.gif  # Individual digit images (15x20 pixels, green LCD style)

/var/lib/web1-site1-counter/  # Server persistent storage (requires manual setup)
├── counter.txt         # Stores current count (plain text integer)
└── ips/               # Directory for IP tracking files (24-hour unique visitor detection)
```

### Counter Setup on Server
```bash
# One-time manual setup required (needs sudo):
sudo mkdir -p /var/lib/web1-site1-counter/ips
sudo chown -R www-data:www-data /var/lib/web1-site1-counter/
sudo chmod 755 /var/lib/web1-site1-counter/
sudo chmod 755 /var/lib/web1-site1-counter/ips/
echo "0" | sudo tee /var/lib/web1-site1-counter/counter.txt
sudo chmod 666 /var/lib/web1-site1-counter/counter.txt
```

### Counter Behavior
- Increments once per unique IP per 24 hours
- Creates IP tracking files in `ips/` directory (e.g., `192_168_1_1.txt`)
- Automatically cleans up IP files older than 24 hours
- Uses file locking to prevent race conditions
- Returns transparent 1x1 GIF with counter images overlaid
- Data persists across deployments (stored outside web directory)
- Security: Blocks direct access to counter.txt via .htaccess

## Deployment Troubleshooting

### Common Issues and Solutions

#### 1. Rsync Permission Denied
**Symptoms**: Deploy script fails with "Permission denied" during rsync
**Root Cause**: Deploy user lacks write access to web directory
**Solutions**:
```bash
# Option 1: Change ownership to deploy user
sudo chown -R deploy:www-data /var/www/web1-site1

# Option 2: Add deploy to www-data group
sudo usermod -a -G www-data deploy

# Option 3: Configure sudoers for rsync
sudo visudo
# Add: deploy ALL=(ALL) NOPASSWD: /usr/bin/rsync -av --delete /home/deploy/web1-site1/website/ /var/www/web1-site1/
```

#### 2. Webhook Not Triggering
**Diagnostic Steps**:
```bash
# Check webhook service
sudo systemctl status webhook.service
sudo journalctl -u webhook.service -n 50

# Test webhook endpoint
curl -X POST http://127.0.0.1:9000/hooks/deploy-web1-site1

# Verify Cloudflare Tunnel routing
sudo cloudflared tunnel list
sudo cloudflared tunnel route dns [tunnel-name] webhook.benjmacaro.dev
```
**GitHub Checks**:
- Repository Settings → Webhooks → Recent Deliveries
- Verify webhook URL: `https://webhook.benjmacaro.dev/hooks/deploy-web1-site1`
- Check secret token matches server configuration

#### 3. PHP Counter Not Working
**Symptoms**: Counter shows as broken image or doesn't increment
**Checks**:
```bash
# Verify PHP-FPM is running
sudo systemctl status php8.3-fpm

# Test PHP processing
curl http://127.0.0.1:8084/counter/counter.php

# Check counter file permissions
ls -la /var/www/web1-site1/counter/counter.txt
# Should be 666 or writable by www-data

# Fix permissions if needed
sudo chmod 666 /var/www/web1-site1/counter/counter.txt
sudo chown www-data:www-data /var/www/web1-site1/counter/counter.txt

# Verify digit images are present
ls -la /var/www/web1-site1/images/counter/
# Should show 0.gif through 9.gif
```

#### 4. Deploy Script Hanging
**Symptoms**: Deployment starts but never completes
**Root Cause**: Lock file not cleared from previous run
**Fix**:
```bash
# Remove stale lock file
rm -f /tmp/deploy-web1-site1.lock

# Check for zombie git processes
ps aux | grep git
# Kill if necessary: kill -9 [PID]
```

#### 5. Counter Resets After Deployment
**Symptoms**: Counter goes back to 0 after each git push
**Root Cause**: rsync --delete removes counter.txt from web directory
**Solution**: Counter now uses `/var/lib/web1-site1-counter/` for persistent storage

**Verification**:
```bash
# Check if persistent storage exists
ls -la /var/lib/web1-site1-counter/

# If not, run the one-time setup:
sudo mkdir -p /var/lib/web1-site1-counter/ips
sudo chown -R www-data:www-data /var/lib/web1-site1-counter/
sudo chmod 755 /var/lib/web1-site1-counter/
sudo chmod 755 /var/lib/web1-site1-counter/ips/

# Restore previous counter value if known:
echo "YOUR_NUMBER" | sudo tee /var/lib/web1-site1-counter/counter.txt
sudo chmod 666 /var/lib/web1-site1-counter/counter.txt
```

#### 6. Changes Not Appearing After Push
**Diagnostic Flow**:
1. Verify GitHub webhook delivered (check GitHub webhook history)
2. Check webhook service received request:
   ```bash
   sudo journalctl -u webhook.service --since "10 minutes ago"
   ```
3. Check deployment log:
   ```bash
   tail -f /home/deploy/deploy-web1-site1.log
   ```
4. Verify files were updated:
   ```bash
   ls -la /var/www/web1-site1/ | head
   # Check timestamps match recent deployment
   ```
5. Clear browser cache and reload

### Quick Health Check Script
```bash
#!/bin/bash
# Save as /home/deploy/health-check.sh

echo "=== Deployment Pipeline Health Check ==="
echo
echo "1. Services Status:"
systemctl is-active nginx && echo "  ✓ Nginx: Running" || echo "  ✗ Nginx: Not running"
systemctl is-active php8.3-fpm && echo "  ✓ PHP-FPM: Running" || echo "  ✗ PHP-FPM: Not running"
systemctl is-active webhook && echo "  ✓ Webhook: Running" || echo "  ✗ Webhook: Not running"
systemctl is-active cloudflared && echo "  ✓ Cloudflare Tunnel: Running" || echo "  ✗ Tunnel: Not running"

echo
echo "2. File Permissions:"
ls -ld /var/www/web1-site1/
[ -w "/var/www/web1-site1/" ] && echo "  ✓ Web directory writable" || echo "  ✗ Web directory not writable"

echo
echo "3. Last Deployment:"
tail -n 5 /home/deploy/deploy-web1-site1.log | grep "==="

echo
echo "4. Webhook Endpoint Test:"
curl -s -o /dev/null -w "  HTTP Status: %{http_code}\n" http://127.0.0.1:9000/hooks/deploy-web1-site1
```

## Security Best Practices

1. **Regular Updates**:
   ```bash
   sudo apt update && sudo apt upgrade
   sudo snap refresh cloudflared
   ```

2. **Monitor Access Logs**:
   ```bash
   sudo tail -f /var/log/nginx/access.log
   sudo fail2ban-client status
   ```

3. **Backup Configuration**:
   ```bash
   # Backup critical configs
   sudo tar -czf /home/deploy/backup-configs-$(date +%Y%m%d).tar.gz \
     /etc/nginx/sites-available/web1-site1 \
     /etc/webhook/ \
     /home/deploy/*.sh
   ```

## Architectural Patterns & Design Decisions

### 1. File-Based Storage Pattern
The visitor counter uses file-based storage instead of a database, following these principles:
- **Simplicity Over Complexity**: No database dependencies or connection management
- **Atomic Operations**: PHP's `flock()` ensures thread-safe file access
- **Deployment Persistence**: Storage location (`/var/lib/web1-site1-counter/`) survives rsync operations
- **Self-Healing**: Counter automatically initializes if file is missing

### 2. Iframe Integration Pattern
The counter uses an iframe to maintain static HTML while adding dynamic content:
```html
<iframe src="counter/counter.php" width="120" height="20" frameborder="0" scrolling="no">
```
- Preserves pure HTML nature of main pages
- Allows PHP processing without converting entire site to PHP
- Width carefully tuned to 120px to display all 6 digits

### 3. Pull-Based Deployment Pattern
The CI/CD pipeline uses a pull model for security:
- Server never accepts pushed code directly
- Webhook only triggers a git pull operation
- Deploy user has limited permissions (can't modify system files)
- Lock file prevents race conditions during concurrent webhook triggers

### 4. Service Isolation Pattern
Each service runs in isolation for defense in depth:
- Nginx: localhost:8084 (not exposed externally)
- Webhook: 127.0.0.1:9000 (localhost only)
- PHP-FPM: Unix socket (no network exposure)
- All external access via Cloudflare Tunnel

### 5. Asset Organization Pattern
Images follow a categorical structure:
```
images/
├── counter/      # Functional images (digit GIFs)
├── backgrounds/  # Decorative tiles
├── buttons/      # Interactive elements
├── dividers/     # Content separators
└── gifs/         # Animated decorations
```

## Non-Obvious Behaviors & Gotchas

### 1. Counter Reset on Deployment (SOLVED)
**Problem**: Initial implementation stored counter.txt in `/var/www/web1-site1/counter/`, which gets deleted by rsync `--delete`
**Solution**: Counter now uses `/var/lib/web1-site1-counter/` for persistent storage
**Setup Required**: Manual one-time setup with proper permissions (see Counter Setup section)

### 2. ImageMagick Version Incompatibility
**Problem**: Ubuntu 24.04 ships with ImageMagick 7, which changed the `convert` command to `magick`
**Solution**: All ImageMagick commands must use `magick` instead of `convert`
**Example**: `magick -size 15x20 xc:black -fill green -draw "text 3,15 '0'" 0.gif`

### 3. PHP Processing Scope
**Gotcha**: Nginx only processes PHP in `/counter/` directory, not site-wide
**Reason**: Security - limits PHP execution to specific functionality
**Config Location**: `/etc/nginx/sites-available/web1-site1`

### 4. Deployment Script Lock File
**Location**: `/tmp/deploy-web1-site1.lock`
**Purpose**: Prevents concurrent deployments
**Common Issue**: Stale lock file after failed deployment
**Fix**: `rm -f /tmp/deploy-web1-site1.lock`

### 5. Permissions After Deployment
**Issue**: Deploy user needs write access to web directory
**Current Solution**: Deploy user is member of www-data group
**File Permissions**: 644 for files, 755 for directories
**Ownership**: `www-data:www-data` for all web files

### 6. Claude Code Agent Architecture
**Configuration Directory**: `.claude/`
- **settings.local.json**: Grants permissions for SSH, Git, screenshots, and web fetching
- **agents/**: Specialized AI agents for different domains
  - `linux-systems-architect.md`: Server administration and deployment
  - `web1-developer.md`: Web 1.0 development patterns
- **commands/**: Custom slash commands for common operations
  - `update-claude.md`: Documentation updates
  - `investigate.md`: Architectural analysis

**Permission Model**:
- Explicit allow list for dangerous operations (SSH, Git push, etc.)
- Domain-restricted web fetching (github.com, linkedin.com)
- Screenshot reading capability for UI debugging

### 7. .htaccess in Nginx Environment
**Note**: `.htaccess` files are Apache-specific but included for compatibility
**Nginx Alternative**: Security rules must be implemented in Nginx config
**Current Status**: Counter directory protection implemented in both formats

### 8. Docker Development Environment (Added 2025-09-25)
**Container Configuration**:
- **Base Image**: Ubuntu 24.04 for production parity
- **Services**: Nginx + PHP 8.3-FPM managed by Supervisor
- **Port**: 8084 (matches production)
- **Volumes**: Website files bind-mounted for hot-reloading
- **Counter Storage**: Persistent Docker volume survives container restarts

**PHP-FPM Configuration Issues Resolved**:
- **Problem**: Initial PHP-FPM config had comment on line 1 causing parse errors
- **Solution**: Removed comment, PHP-FPM starts successfully
- **Error Log Path**: Changed from single file to directory mount (`/var/log/php-fpm/`)

**Development Mode Permissions**:
- **Issue**: Docker's entrypoint was changing file ownership to www-data, preventing local editing
- **Solution**: Modified entrypoint to skip permission changes when `ENVIRONMENT=development`
- **Behavior**: In dev mode, preserves host filesystem ownership for editing
- **Restoration**: If files already changed, run: `sudo chown -R $(whoami):$(whoami) website/`

**Docker Commands Not Using Hyphen**:
- **Note**: Use `docker compose` not `docker-compose` (compose v2 syntax)
- **Warning**: "version" attribute in docker-compose.yml is obsolete but harmless

## Additional Resources

- **Project Documentation**:
  - `/portfolio-plan.md` - Complete Web 1.0 design specifications (in progress)
  - `/webhook-implementation.md` - Webhook-based CI/CD deployment system
  - `/counter-implementation.md` - PHP visitor counter with persistent storage
  - `/container-implementation.md` - Docker development environment setup

- **External References**:
  - [Cloudflare Tunnel Documentation](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/)
  - [Webhook Package Documentation](https://github.com/adnanh/webhook)
  - [Nginx PHP-FPM Configuration](https://www.nginx.com/resources/wiki/start/topics/examples/phpfcgi/)
  - [ImageMagick 7 Documentation](https://imagemagick.org/script/command-line-processing.php)