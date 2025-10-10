# WordPress Dev Server Setup - COMPLETE ✅

## Setup Summary
Full WordPress development environment successfully configured with Docker, MySQL 8.0, phpMyAdmin, and essential plugins.

## Installed Components
- ✅ Docker Engine (v28.5.1)
- ✅ Docker Compose (v2.40.0)
- ✅ Git (pre-installed)
- ✅ Node.js v22 (pre-installed)
- ✅ npm (pre-installed)
- ✅ PHP 8.1 CLI with required extensions
- ✅ Composer (v2.8.12)
- ✅ WP-CLI (v2.12.0) - installed on host and in container
- ✅ ngrok (v3.30.0)

## Docker Setup
- ✅ User added to docker group
- ✅ MySQL 8.0 container running on port 3306
- ✅ WordPress latest container running on port 8080
- ✅ phpMyAdmin container running on port 8081
- ✅ WordPress files mounted to `./wordpress` directory for live editing

## WordPress Configuration
- ✅ WordPress core installed
- ✅ Admin username: `admin`
- ✅ Admin password: `admin123`
- ✅ Admin email: `admin@example.com`
- ✅ Site URL: `http://localhost:8080`

## Installed Plugins
- ✅ WooCommerce (v10.2.2) - Active
- ✅ All-in-One WP Migration (v7.100) - Active
- ✅ Query Monitor (v3.20.0) - Active

## Database Access
- MySQL Root Password: `rootpassword`
- WordPress DB Name: `wordpress`
- WordPress DB User: `wordpress`
- WordPress DB Password: `wordpress`
- phpMyAdmin URL: `http://localhost:8081`

## Key Features
- **Live Editing**: WordPress files are in `./wordpress` directory - changes persist without rebuilding
- **Persistent Data**: MySQL data stored in Docker volume `mysql_data`
- **No Rebuilds Needed**: Edit themes, plugins, and files directly in `./wordpress` folder

## Usage Commands

### Docker Management
```bash
# Start containers (use sudo until you log out and back in)
sudo docker compose up -d

# Stop containers
sudo docker compose down

# View logs
sudo docker compose logs -f

# Restart containers
sudo docker compose restart

# View running containers
sudo docker ps
```

### WP-CLI Commands (inside container)
```bash
# List plugins
sudo docker exec wordpress_app wp plugin list --allow-root

# Install a plugin
sudo docker exec wordpress_app wp plugin install [plugin-name] --activate --allow-root

# Update all plugins
sudo docker exec wordpress_app wp plugin update --all --allow-root

# List themes
sudo docker exec wordpress_app wp theme list --allow-root
```

### ngrok (for public URL)
```bash
# Expose WordPress site publicly
ngrok http 8080

# Note: Requires ngrok account and auth token
# Configure with: ngrok config add-authtoken YOUR_TOKEN
```

## Access URLs
- WordPress Site: http://localhost:8080
- WordPress Admin: http://localhost:8080/wp-admin
- phpMyAdmin: http://localhost:8081

## Directory Structure
```
/home/webdev/
├── docker-compose.yml    # Docker configuration
├── wordpress/            # WordPress files (live editable)
│   ├── wp-content/       # Themes, plugins, uploads
│   ├── wp-admin/
│   └── wp-includes/
└── setup-progress.md     # This file
```

## Notes for Paid Plugins
The following paid plugins were mentioned but not installed (require manual installation):
- **SquareSync**: Must be purchased and manually uploaded via WordPress admin or copied to `./wordpress/wp-content/plugins/`
- **Drip Apps**: Must be purchased and manually uploaded via WordPress admin or copied to `./wordpress/wp-content/plugins/`

To install paid plugins:
1. Download the plugin ZIP file
2. Upload via WordPress Admin → Plugins → Add New → Upload Plugin
3. Or extract to `./wordpress/wp-content/plugins/[plugin-folder]`
4. Activate via WordPress admin or WP-CLI

## Important Notes
- Server: Ubuntu 22.04.5 LTS
- Working Directory: /home/webdev
- Docker group membership active (may need to log out/in for non-sudo docker)
- WordPress Debug mode enabled in environment
- All services set to restart automatically unless stopped
