# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

This is a Dockerized WordPress development environment configured with MySQL 8.0, phpMyAdmin, and essential e-commerce plugins. The setup enables live editing of WordPress files without container rebuilds.

## Architecture

### Docker Setup
- **wordpress_app**: Custom WordPress image (built from Dockerfile) on ports 80 & 443
  - Based on `wordpress:latest` with WP-CLI and Certbot pre-installed
  - PHP 8.3.26, Apache 2.4.65
  - Auto-redirects HTTP to HTTPS
- **wordpress_mysql**: MySQL 8.0 on port 3306
- **wordpress_phpmyadmin**: phpMyAdmin on port 8081
- All services use `wordpress_network` bridge network
- Persistent volumes:
  - `mysql_data`: MySQL database storage
  - `letsencrypt_data`: SSL certificates
  - `./wordpress`: WordPress files (live-mounted)

### Key Architectural Points
- **Custom Docker Image**: Uses `Dockerfile` to extend WordPress with WP-CLI and Certbot
- WordPress files in `./wordpress` are **live-mounted** - changes persist immediately without rebuilds
- SSL certificates in `letsencrypt_data` volume persist across container restarts
- WordPress debug mode is enabled via `WORDPRESS_DEBUG: 1` environment variable
- HTTP requests automatically redirect to HTTPS (301 redirect)
- File ownership: WordPress files owned by `www-data:www-data`, some plugin files by `root:root`
- Database credentials stored in docker-compose.yml environment variables

## Development Commands

### Container Management
```bash
# Start environment
docker compose up -d

# Stop environment
docker compose down

# Rebuild custom image (after Dockerfile changes)
docker compose build --no-cache

# View logs (all services)
docker compose logs -f

# View logs (specific service)
docker compose logs -f wordpress

# Restart services
docker compose restart
```

### WP-CLI Commands
WP-CLI is pre-installed in the custom WordPress image. Execute commands with `--allow-root`:

```bash
# List plugins
sudo docker exec wordpress_app wp plugin list --allow-root

# Install plugin
sudo docker exec wordpress_app wp plugin install [plugin-name] --activate --allow-root

# Update all plugins
sudo docker exec wordpress_app wp plugin update --all --allow-root

# List themes
sudo docker exec wordpress_app wp theme list --allow-root

# Database export
sudo docker exec wordpress_app wp db export /var/www/html/backup.sql --allow-root

# Clear cache
sudo docker exec wordpress_app wp cache flush --allow-root
```

### Database Access
- **phpMyAdmin**: http://localhost:8081
- **Direct MySQL**: `sudo docker exec -it wordpress_mysql mysql -u wordpress -p`
- Database name: `wordpress`, user: `wordpress`, password: `wordpress`

## Installed Components

### Pre-installed Plugins
- **WooCommerce** (v10.2.2) - E-commerce functionality
- **All-in-One WP Migration** (v7.100) - Site backup/migration
- **Query Monitor** (v3.20.0) - Development debugging (note: creates symlink at `wp-content/db.php`)

### Default Themes
- Twenty Twenty-Five (latest)
- Twenty Twenty-Four
- Twenty Twenty-Three

## Access Credentials

### WordPress Admin
- **Production URL**: https://dev.epicmarks.com/wp-admin
- **Local URL**: http://localhost/wp-admin (redirects to HTTPS)
- Username: `[SECURE - Not in documentation]`
- Password: `[SECURE - Not in documentation]`
- Email: `admin@example.com`

### Database
- Root password: `rootpassword`
- WordPress DB: `wordpress`
- User: `wordpress`
- Password: `wordpress`

### SSL Certificate
- **Domain**: dev.epicmarks.com
- **Certificate**: `/etc/letsencrypt/live/dev.epicmarks.com/fullchain.pem`
- **Private Key**: `/etc/letsencrypt/live/dev.epicmarks.com/privkey.pem`
- **Expires**: January 8, 2026
- **Auto-renewal**: Configured via Certbot

## File Structure
```
.
├── Dockerfile               # Custom WordPress image with WP-CLI and Certbot
├── docker-compose.yml       # Container orchestration configuration
├── wordpress/               # Live WordPress installation (volume mount)
│   ├── wp-content/
│   │   ├── plugins/        # Installed plugins
│   │   ├── themes/         # Installed themes
│   │   │   └── kadence-child/  # Custom child theme
│   │   ├── uploads/        # Media files (gitignored)
│   │   ├── ai1wm-backups/  # Migration plugin backups (gitignored)
│   │   └── db.php          # Symlink created by Query Monitor
│   ├── wp-admin/
│   ├── wp-includes/
│   └── wp-config.php       # Generated config (gitignored)
├── CLAUDE.md               # Project documentation for Claude Code
├── README.md               # User documentation
└── setup-progress.md       # Setup notes
```

## Important Notes

### Editing WordPress Files
- Edit files directly in `./wordpress/` directory - changes are immediate
- No container rebuild needed for theme/plugin development
- File changes persist across container restarts due to volume mount

### Custom Plugin/Theme Development
- Place custom plugins in `./wordpress/wp-content/plugins/`
- Place custom themes in `./wordpress/wp-content/themes/`
- Activate via WordPress admin or WP-CLI commands

### Query Monitor Plugin
- Creates a symlink: `wp-content/db.php` → `wp-content/plugins/query-monitor/wp-content/db.php`
- Do not delete this symlink while Query Monitor is active

### Paid Plugins
- **SquareSync** and **Drip Apps** require manual installation (not in free repository)
- Install via WordPress admin upload or copy to `./wordpress/wp-content/plugins/`

### SSL and HTTPS
- SSL certificate from Let's Encrypt installed for dev.epicmarks.com
- HTTP automatically redirects to HTTPS (301 redirect)
- Certificates stored in `letsencrypt_data` Docker volume (persists across container restarts)
- Certbot pre-installed in custom image for certificate management
- To renew certificate: `docker exec wordpress_app certbot renew`
- Certificate auto-renewal configured by Certbot

### Security Features
- File editing disabled in WordPress admin (`DISALLOW_FILE_EDIT`)
- XML-RPC disabled (common attack vector)
- Wordfence Security plugin installed and active
- Limit Login Attempts Reloaded plugin installed
- Secure admin credentials (see Access Credentials section)

## Development Workflow

1. Start containers: `docker compose up -d`
2. Access WordPress: https://dev.epicmarks.com (or http://localhost - auto-redirects to HTTPS)
3. Edit files in `./wordpress/` directory
4. Changes take effect immediately (no rebuild needed)
5. Use WP-CLI for plugin/theme management (pre-installed)
6. Use phpMyAdmin for database operations (http://localhost:8081)
7. Check Query Monitor for debugging (when logged in as admin)

## Custom Docker Image

The WordPress container uses a custom image defined in `Dockerfile` that extends the official WordPress image with:
- **WP-CLI** (v2.12.0): Pre-installed for command-line WordPress management
- **Certbot** (v4.0.0): Pre-installed for SSL certificate management
- **Apache modules**: SSL and Rewrite modules enabled
- **HTTP to HTTPS redirect**: Automatic 301 redirect configured

To rebuild the custom image after modifying the Dockerfile:
```bash
docker compose down
docker compose build --no-cache
docker compose up -d
```
