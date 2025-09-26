# Webhook-Based CI/CD Pipeline

## Overview

Since SSH through Cloudflare Tunnel is proving difficult for GitHub Actions, we'll implement a webhook-based deployment where GitHub notifies your home server of changes, and the server pulls the latest updates immediately.

## Why Webhook + Local Listener?

This approach provides:
- **Instant deployments** - No waiting for cron intervals
- **Secure** - Server only pulls, never accepts pushed code
- **Reliable** - Simple git pull mechanism
- **Auditable** - All deployments are logged
- **No SSH needed** - Works perfectly with Cloudflare Tunnel

## Architecture Overview

```
GitHub Repository → Push Event → GitHub Webhook → Cloudflare Tunnel →
Local Webhook Listener → Execute Deploy Script → Git Pull → Update Website
```

## Implementation: Webhook + Local Listener

We'll use a lightweight webhook receiver that listens for GitHub push events and triggers deployment.

### Step 1: Install Webhook Package on Server

First, we'll install the webhook package that will listen for GitHub webhooks:

```bash
# Update package list
sudo apt-get update

# Install webhook
sudo apt-get install -y webhook

# Verify installation
webhook --version
```

### Step 2: Create Deployment Script

Create `/home/deploy/deploy-web1-site1.sh`:

```bash
#!/bin/bash

# Configuration
REPO_DIR="/home/deploy/web1-site1"
WEB_DIR="/var/www/web1-site1"
LOG_FILE="/home/deploy/deploy-web1-site1.log"
LOCKFILE="/tmp/deploy-web1-site1.lock"

# Function to log messages
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Check if another deployment is running
if [ -f "$LOCKFILE" ]; then
    log "Another deployment is already in progress. Skipping."
    exit 0
fi

# Create lock file
touch "$LOCKFILE"
trap "rm -f $LOCKFILE" EXIT

log "=== Starting deployment ==="
log "Triggered by: GitHub Webhook"

# Navigate to repository directory
cd "$REPO_DIR" || { log "ERROR: Cannot cd to $REPO_DIR"; exit 1; }

# Store current commit
OLD_COMMIT=$(git rev-parse HEAD)

# Fetch and pull latest changes
log "Fetching latest changes..."
git fetch origin main >> "$LOG_FILE" 2>&1

# Check if there are new commits
LOCAL=$(git rev-parse HEAD)
REMOTE=$(git rev-parse origin/main)

if [ "$LOCAL" = "$REMOTE" ]; then
    log "Already up to date. No deployment needed."
    exit 0
fi

# Pull the changes
log "Pulling changes from GitHub..."
git pull origin main >> "$LOG_FILE" 2>&1 || {
    log "ERROR: Git pull failed"
    exit 1
}

# Get new commit info
NEW_COMMIT=$(git rev-parse HEAD)
COMMIT_MESSAGE=$(git log --format=%B -n 1 HEAD)
COMMIT_AUTHOR=$(git log --format='%an <%ae>' -n 1 HEAD)

log "Deploying commit: $NEW_COMMIT"
log "Author: $COMMIT_AUTHOR"
log "Message: $COMMIT_MESSAGE"

# Sync website files to web directory
# IMPORTANT: The trailing slash on "website/" is crucial - it copies only the CONTENTS
# of the website directory, not the directory itself. This means:
# - Files at repository root (docker/, docker-compose.yml, *.md) are NOT copied
# - Only files inside website/ are deployed to production
# - This creates a deployment boundary: development tools vs production content
log "Syncing files to web directory..."
rsync -av --delete "$REPO_DIR/website/" "$WEB_DIR/" >> "$LOG_FILE" 2>&1 || {
    log "ERROR: Rsync failed"
    exit 1
}

# Set proper permissions
log "Setting permissions..."
sudo chmod -R 755 "$WEB_DIR" >> "$LOG_FILE" 2>&1
sudo chown -R www-data:www-data "$WEB_DIR" >> "$LOG_FILE" 2>&1

# List changed files
log "Changed files:"
git diff --name-status "$OLD_COMMIT" "$NEW_COMMIT" | tee -a "$LOG_FILE"

log "=== Deployment completed successfully! ==="
echo "" >> "$LOG_FILE"
```

Make it executable:

```bash
chmod +x /home/deploy/deploy-web1-site1.sh
```

### Step 3: Set Up GitHub Deploy Key (Read-Only)

On your server:

1. Generate a new SSH key specifically for deployment:
```bash
ssh-keygen -t ed25519 -C "deploy@web1-site1" -f ~/.ssh/github_deploy_key -N ""
```

2. Add the SSH key to your SSH agent:
```bash
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/github_deploy_key
```

3. Configure Git to use this key for your repository:
```bash
cd /home/deploy/web1-site1
git config core.sshCommand "ssh -i ~/.ssh/github_deploy_key"
```

4. Copy the public key:
```bash
cat ~/.ssh/github_deploy_key.pub
```

5. In GitHub:
   - Go to your repository → Settings → Deploy keys
   - Click "Add deploy key"
   - Title: "Home Server Pull Deployment"
   - Key: Paste the public key
   - Allow write access: **No** (keep unchecked for read-only)
   - Click "Add key"

### Step 4: Clone Repository on Server

```bash
cd /home/deploy
git clone git@github.com:YOUR_USERNAME/web1-site1.git
cd web1-site1
git config core.sshCommand "ssh -i ~/.ssh/github_deploy_key"
```

### Step 5: Create Webhook Configuration

Create the webhook configuration file. First, generate a secure secret:

```bash
# Generate a secure webhook secret
openssl rand -hex 32
# Save this value - you'll need it for GitHub and the webhook config
```

Create `/etc/webhook/hooks.json`:

```json
[
  {
    "id": "deploy-web1-site1",
    "execute-command": "/home/deploy/deploy-web1-site1.sh",
    "command-working-directory": "/home/deploy/web1-site1",
    "response-message": "Deployment triggered successfully",
    "trigger-rule": {
      "and": [
        {
          "match": {
            "type": "payload-hash-sha256",
            "secret": "YOUR_GENERATED_SECRET_HERE",
            "parameter": {
              "source": "header",
              "name": "X-Hub-Signature-256"
            }
          }
        },
        {
          "match": {
            "type": "value",
            "value": "refs/heads/main",
            "parameter": {
              "source": "payload",
              "name": "ref"
            }
          }
        }
      ]
    }
  }
]
```

This configuration:
- Verifies the webhook signature for security
- Only triggers on pushes to the main branch
- Executes your deployment script
- Returns a confirmation message

### Step 6: Set Up Webhook Service

Create a systemd service for webhook `/etc/systemd/system/webhook.service`:

```ini
[Unit]
Description=Webhook receiver for web1-site1
After=network.target

[Service]
Type=simple
User=deploy
Group=deploy
WorkingDirectory=/home/deploy
ExecStart=/usr/bin/webhook -hooks /etc/webhook/hooks.json -verbose -port 9000 -ip 127.0.0.1
Restart=on-failure
RestartSec=5s
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

Enable and start the service:

```bash
# Reload systemd
sudo systemctl daemon-reload

# Enable webhook to start on boot
sudo systemctl enable webhook.service

# Start webhook service
sudo systemctl start webhook.service

# Check status
sudo systemctl status webhook.service

# View logs
sudo journalctl -u webhook.service -f
```

### Step 7: Configure Cloudflare Tunnel

Add the webhook endpoint to your Cloudflare tunnel:

1. **Via cloudflared config file** (usually `/etc/cloudflared/config.yml`):

```yaml
tunnel: YOUR-TUNNEL-ID
credentials-file: /etc/cloudflared/YOUR-TUNNEL-ID.json

ingress:
  # Existing config for main site
  - hostname: benjmacaro.dev
    service: http://localhost:80

  # Add webhook endpoint
  - hostname: webhook.benjmacaro.dev
    service: http://localhost:9000

  # Catch-all
  - service: http_status:404
```

2. **Or via Cloudflare Dashboard**:
   - Go to Zero Trust → Access → Tunnels
   - Click on your tunnel
   - Go to Public Hostname tab
   - Add public hostname:
     - Subdomain: `webhook`
     - Domain: `benjmacaro.dev`
     - Service: HTTP, localhost:9000

3. **Restart cloudflared** if you edited the config file:

```bash
sudo systemctl restart cloudflared
```

### Step 8: Configure GitHub Webhook

In your GitHub repository:

1. Go to **Settings** → **Webhooks**
2. Click **Add webhook**
3. Configure:
   - **Payload URL**: `https://webhook.benjmacaro.dev/hooks/deploy-web1-site1`
   - **Content type**: `application/json`
   - **Secret**: Enter the secret you generated earlier
   - **Which events?**: Select "Just the push event"
   - **Active**: Check the box
4. Click **Add webhook**

### Step 9: Test the Webhook

1. **Test webhook connectivity**:

```bash
# From your local machine, test the webhook endpoint
curl -X POST https://webhook.benjmacaro.dev/hooks/deploy-web1-site1 \
  -H "Content-Type: application/json" \
  -d '{"test": "connection"}'
# Should return an error about signature validation - that's good!
```

2. **Make a test commit**:

```bash
# In your local repo
echo "<!-- Test deployment -->" >> website/index.html
git add website/index.html
git commit -m "Test webhook deployment"
git push origin main
```

3. **Monitor the deployment**:

```bash
# On your server
tail -f /home/deploy/deploy-web1-site1.log
```

### Step 10: Security Hardening

Add these optional security enhancements:

1. **Rate limiting** in webhook config:

```json
"trigger-rule": {
  "and": [
    // ... existing rules ...
    {
      "not": {
        "match": {
          "type": "ip-whitelist",
          "ip-range": ["140.82.112.0/20", "143.55.64.0/20"]
        }
      }
    }
  ]
}
```

2. **Cloudflare Access Policy** (optional):
   - Create an Access policy for webhook.benjmacaro.dev
   - Allow only GitHub's IP ranges
   - Or use Service Auth with tokens

## Benefits of Webhook Approach

1. **Instant deployments** - No delay, deploys trigger immediately on push
2. **Secure** - Uses HMAC signatures to verify webhooks are from GitHub
3. **Efficient** - Only runs when needed, not periodically
4. **Auditable** - Full logs of all deployments with commit info
5. **Reliable** - Systemd manages the service, auto-restarts on failure
6. **Simple** - Just git pull, no complex CI/CD infrastructure

## Troubleshooting

### Webhook Not Triggering

**Symptoms**: GitHub shows webhook delivered but deployment doesn't run

**Solutions**:
- Check webhook service is running: `sudo systemctl status webhook.service`
- View webhook logs: `sudo journalctl -u webhook.service -f`
- Verify secret matches in both GitHub and `/etc/webhook/hooks.json`
- Check the webhook ID in URL matches config: `/hooks/deploy-web1-site1`
- Test locally: `curl http://localhost:9000/hooks/deploy-web1-site1`

### Webhook Returns 404

**Symptoms**: GitHub webhook shows 404 error

**Solutions**:
- Verify Cloudflare tunnel is routing webhook.benjmacaro.dev to port 9000
- Check webhook service is listening on correct port: `sudo netstat -tlnp | grep 9000`
- Restart cloudflared: `sudo systemctl restart cloudflared`
- Check tunnel status in Cloudflare dashboard

### Signature Validation Fails

**Symptoms**: Webhook logs show "signature does not match"

**Solutions**:
- Regenerate secret and update both GitHub and webhook config
- Ensure secret doesn't contain special characters that need escaping
- Verify GitHub is sending X-Hub-Signature-256 header
- Check that the secret in hooks.json is exactly the same as in GitHub

### Git Pull Permission Denied

**Symptoms**: Deployment script fails at git pull step

**Solutions**:
- Check SSH key permissions: `chmod 600 ~/.ssh/github_deploy_key`
- Verify deploy key is added to GitHub repository
- Test SSH connection: `ssh -T git@github.com -i ~/.ssh/github_deploy_key`
- Ensure git config uses correct key: `git config core.sshCommand`

### Rsync Permission Errors

**Symptoms**: Files copy but permissions fail

**Solutions**:
- Add deploy user to sudoers for specific commands:
  ```bash
  echo "deploy ALL=(ALL) NOPASSWD: /bin/chmod -R 755 /var/www/web1-site1*, /bin/chown -R www-data:www-data /var/www/web1-site1*" | sudo tee /etc/sudoers.d/deploy
  ```
- Verify www-data user exists: `id www-data`
- Check web directory ownership: `ls -la /var/www/`

### Webhook Service Won't Start

**Symptoms**: systemctl start webhook.service fails

**Solutions**:
- Check for port conflicts: `sudo lsof -i :9000`
- Verify webhook binary exists: `which webhook`
- Check hooks.json syntax: `json_pp < /etc/webhook/hooks.json`
- Review service logs: `sudo journalctl -xe -u webhook.service`

### Deployment Runs But Site Doesn't Update

**Symptoms**: Logs show success but changes don't appear

**Solutions**:
- Check nginx is serving from correct directory: `/var/www/web1-site1`
- Clear browser cache or test in incognito mode
- Verify Cloudflare cache: Purge cache in Cloudflare dashboard
- Check nginx config: `nginx -t` and `sudo systemctl reload nginx`
- Confirm files were actually copied: `ls -la /var/www/web1-site1`

## Monitoring and Maintenance

### View Deployment Logs

```bash
# Real-time deployment logs
tail -f /home/deploy/deploy-web1-site1.log

# Webhook service logs
sudo journalctl -u webhook.service -f

# Last 50 lines of webhook logs
sudo journalctl -u webhook.service -n 50
```

### Check Service Health

```bash
# Webhook service status
sudo systemctl status webhook.service

# Cloudflared tunnel status
sudo systemctl status cloudflared

# Nginx status
sudo systemctl status nginx
```

### Manual Deployment

If webhook fails, trigger deployment manually:

```bash
# Run deployment script directly
/home/deploy/deploy-web1-site1.sh

# Or pull and sync manually
cd /home/deploy/web1-site1
git pull origin main
sudo rsync -av --delete website/ /var/www/web1-site1/
```

## Deployment Boundaries

The rsync command creates a clear separation between development and production files:

### Repository Structure vs Deployed Structure

**Repository (`/home/deploy/web1-site1/`):**
```
web1-site1/
├── docker/                 ← Development only, never deployed
├── docker-compose.yml      ← Development only, never deployed
├── .dockerignore          ← Development only, never deployed
├── CLAUDE.md              ← Documentation, never deployed
├── *.md files             ← Documentation, never deployed
├── .claude/               ← Claude config, never deployed
└── website/               ← DEPLOYMENT SOURCE
    ├── index.html         → Deployed to /var/www/web1-site1/index.html
    ├── counter/           → Deployed to /var/www/web1-site1/counter/
    └── images/            → Deployed to /var/www/web1-site1/images/
```

**Production (`/var/www/web1-site1/`):**
```
web1-site1/
├── index.html              ← From website/index.html
├── home.html               ← From website/home.html
├── counter/                ← From website/counter/
├── images/                 ← From website/images/
└── assets/                 ← From website/assets/
(No Docker files, no documentation, no development tools)
```

### Why This Design Works

1. **Security**: Development tools and documentation never reach production
2. **Simplicity**: Clear boundary - only content in `/website/` is deployed
3. **Safety**: Docker files, configs, and secrets stay in development
4. **Flexibility**: Can commit Docker development environment without affecting production

The trailing slash in `rsync -av --delete website/ /var/www/web1-site1/` is what makes this work - it copies the **contents** of website/, not the directory itself.

## Next Steps

1. Follow steps 1-9 to set up the webhook infrastructure
2. Test with a small commit to verify everything works
3. Monitor logs for the first few deployments
4. Add security hardening if needed (Step 10)
5. Set up monitoring/alerting for failed deployments (optional)