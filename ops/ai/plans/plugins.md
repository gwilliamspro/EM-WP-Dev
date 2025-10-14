# WordPress Plugins

**Last Updated**: 2025-10-14

## Custom Plugins

### Epic Marks Shipping
- **Version**: 1.0.0
- **Status**: Active
- **Description**: UPS + PirateShip shipping integration with multi-origin support
- **Files**: 6 PHP files, 1,422 lines of code
- **Dependencies**: WooCommerce 6.0+, WordPress 5.8+, PHP 8.0+
- **Location**: `wordpress/wp-content/plugins/epic-marks-shipping/`

### Epic Marks Blocks
- **Version**: 1.3.0
- **Status**: Active
- **Description**: Custom Gutenberg blocks for Epic Marks website
- **Location**: `wordpress/wp-content/plugins/epic-marks-blocks/`

## E-Commerce

### WooCommerce
- **Version**: 10.2.2
- **Status**: Active
- **Description**: Core e-commerce functionality
- **HPOS**: Enabled (High-Performance Order Storage)

### WooCommerce Square
- **Version**: 5.1.0
- **Status**: Active
- **Description**: Square payment gateway integration

## Site Management

### Code Snippets
- **Version**: 3.7.0
- **Status**: Active
- **Description**: Safe PHP code snippet management

### All-in-One WP Migration
- **Version**: 7.100
- **Status**: Inactive
- **Description**: Site backup and migration tool

### UpdraftPlus
- **Version**: 1.25.8
- **Status**: Active
- **Description**: Backup and restoration

## Security

### Wordfence
- **Version**: 8.1.0
- **Status**: Active
- **Description**: Firewall and malware scanning

### Limit Login Attempts Reloaded
- **Version**: 2.26.23
- **Status**: Active
- **Description**: Brute force attack prevention

## Performance

### WP Super Cache
- **Version**: 3.0.2
- **Status**: Active
- **Description**: Page caching
- **Note**: Cart/checkout pages excluded by Epic Marks Shipping plugin

### Smush
- **Version**: 3.22.1
- **Status**: Active
- **Description**: Image optimization and lazy loading

## SEO & Marketing

### Yoast SEO
- **Version**: 26.1.1
- **Status**: Active
- **Description**: SEO optimization and XML sitemaps

### Newsletter
- **Version**: 8.9.9
- **Status**: Active
- **Update Available**: 9.0.1

## Search & Navigation

### Ajax Search for WooCommerce
- **Version**: 1.31.0
- **Status**: Active
- **Description**: Live product search

### Redirection
- **Version**: 5.5.2
- **Status**: Active
- **Description**: URL redirect management

## Content & Design

### Kadence Blocks
- **Version**: 3.5.24
- **Status**: Active
- **Description**: Advanced Gutenberg blocks

### WP Google Maps
- **Version**: 9.0.48
- **Status**: Active
- **Description**: Google Maps integration

## Support

### Tawk.to Live Chat
- **Version**: 0.9.2
- **Status**: Active
- **Description**: Live chat widget

## Development & Debugging

### Query Monitor
- **Version**: 3.20.0
- **Status**: Inactive
- **Description**: Development debugging and performance monitoring
- **Note**: Creates symlink at `wp-content/db.php` when active

### Akismet
- **Version**: 5.5
- **Status**: Inactive
- **Description**: Spam filtering (default WordPress plugin)

### Hello Dolly
- **Version**: 1.7.2
- **Status**: Inactive
- **Description**: Default WordPress plugin (not used)

## Must-Use Plugins

### Increase Limits
- **Version**: 1.0
- **Status**: Must-use
- **Description**: Custom must-use plugin for server limits

## Drop-ins

### Advanced Cache
- **Type**: Drop-in
- **Description**: WP Super Cache advanced caching component

### DB (Query Monitor)
- **Type**: Drop-in
- **Description**: Symlink to Query Monitor database profiler
- **Path**: `wp-content/db.php` → `plugins/query-monitor/wp-content/db.php`

## Plugin Dependencies

### Epic Marks Shipping Dependencies
1. **WooCommerce**: Required for shipping method integration
2. **Product Tags**: Requires 'SSAW-App' and 'available-in-store' tags
3. **UPS Developer Account**: Required for live shipping rates
4. **PHP Extensions**: cURL (for API calls), JSON

## Version Compatibility

- **WordPress**: 6.x (latest)
- **PHP**: 8.3.26
- **MySQL**: 8.0
- **WooCommerce**: 10.2.2 (Epic Marks Shipping tested up to 10.2)

## Notes

- **Auto-updates**: Disabled for all plugins (manual updates preferred)
- **Update Available**: Newsletter plugin has update 9.0.1 available
- **Inactive Plugins**: Query Monitor and All-in-One WP Migration kept inactive in production
- **Security**: All active plugins receive regular security updates
