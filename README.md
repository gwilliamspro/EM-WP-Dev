# EM WP Dev

WordPress development environment using Docker, MySQL 8.0, and phpMyAdmin.

## Quick Start

```bash
# Start the development environment
sudo docker compose up -d

# Access WordPress
open http://localhost:8080
```

## Access URLs

- **WordPress Site**: http://localhost:8080
- **WordPress Admin**: http://localhost:8080/wp-admin
- **phpMyAdmin**: http://localhost:8081

## Admin Credentials

- **Username**: admin
- **Password**: admin123
- **Email**: admin@example.com

## Database Credentials

- **Database Name**: wordpress
- **Database User**: wordpress
- **Database Password**: wordpress
- **Root Password**: rootpassword

## Installed Plugins

- WooCommerce (v10.2.2)
- All-in-One WP Migration (v7.100)
- Query Monitor (v3.20.0)

## Development

WordPress files are located in the `./wordpress` directory and are mounted as a volume. You can edit themes, plugins, and core files directly without rebuilding the container.

### File Structure

```
.
├── docker-compose.yml       # Docker configuration
├── wordpress/               # WordPress installation (live editable)
│   ├── wp-content/
│   │   ├── themes/         # Custom themes
│   │   ├── plugins/        # Plugins
│   │   └── uploads/        # Media files
│   ├── wp-admin/
│   └── wp-includes/
└── README.md
```

## Useful Commands

### Docker Management

```bash
# Start containers
sudo docker compose up -d

# Stop containers
sudo docker compose down

# View logs
sudo docker compose logs -f

# Restart containers
sudo docker compose restart
```

### WP-CLI Commands

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

### Expose Site Publicly with ngrok

```bash
# Start ngrok tunnel
ngrok http 8080

# Note: Requires ngrok account and auth token
# Configure with: ngrok config add-authtoken YOUR_TOKEN
```

## Tech Stack

- Docker & Docker Compose
- WordPress (latest)
- MySQL 8.0
- phpMyAdmin
- PHP 8.1
- WP-CLI
- Composer
- ngrok

## Notes

- WordPress debug mode is enabled
- All containers restart automatically unless stopped
- Data persists in Docker volumes
- No container rebuild needed for code changes
