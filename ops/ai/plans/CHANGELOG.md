# Changelog

All notable changes to the Epic Marks WordPress site will be documented in this file.

## [2025-10-14] - File Permission Fixes & WP-CLI Workflow Update

### Fixed
- **Plugin/Theme Update Failures** - Fixed permission errors preventing WordPress from updating plugins through admin interface
- **Newsletter Plugin Updated** - Successfully updated from v8.9.9 to v9.0.1 after permission fix
- **Mass Permission Correction** - Changed ownership of all plugins and themes from `root:root` to `www-data:www-data`

### Changed
- **WP-CLI Workflow** - Replaced `sudo docker exec wordpress_app wp --allow-root` with cleaner alias
- **New wp Alias** - Added to `~/.bashrc`: runs WP-CLI as `www-data` user inside container automatically
- **Documentation Updates** - Added WP-CLI usage rules to invariants.md, updated plugins.md with correct versions

### Why These Changes
- **Prevent Update Failures**: WordPress runs as `www-data` but couldn't overwrite root-owned files, causing "inconsistent file permissions" errors
- **Eliminate Permission Issues**: All files created by WP-CLI now automatically owned by correct user
- **Streamlined Commands**: New `wp` alias is shorter, cleaner, and prevents accidentally creating root-owned files
- **Future-Proof**: Any plugin/theme installations or updates (via admin or WP-CLI) maintain correct permissions

### Technical Details
- Fixed: 17+ plugins, 1 theme (Kadence)
- New alias: `docker exec -u www-data -w /var/www/html wordpress_app wp`
- Location: `~/.bashrc` for user `webdev`

---

## [2025-10-14] - Epic Marks Shipping Plugin v1.0.0

### Added
- **New Plugin: Epic Marks Shipping** - Custom WooCommerce shipping plugin integrating UPS rating API with PirateShip label creation workflow
- **Multi-Origin Shipping Support** - Automatically determines shipping origin (warehouse or retail store) based on product tags and calculates combined rates for mixed carts
- **Tag-Based Location Detection** - Uses 'SSAW-App' and 'available-in-store' product tags to route orders to correct fulfillment location without manual configuration
- **PirateShip Integration** - One-click "Create Label" button on order pages that pre-fills shipping data from WooCommerce order into PirateShip deep link
- **Intelligent Rate Caching** - 30-minute transient caching of UPS API responses to improve checkout performance and reduce API calls

### Why These Changes
- **Automated Shipping Rates**: Eliminates manual shipping rate entry by fetching real-time UPS rates via API, reducing pricing errors and customer support issues
- **Dual-Location Fulfillment**: Supports Epic Marks' business model of shipping some products from warehouse (SSAW-App items) and others from retail store, with automatic detection
- **Streamlined Order Processing**: PirateShip integration reduces label creation time by pre-filling all shipping data from WooCommerce, eliminating duplicate data entry
- **Scalability**: Tag-based system allows easy product routing changes without code modifications - just add/remove product tags in WooCommerce admin

### Technical Details
- 6 PHP files, 1,422 lines of code
- Requires: WooCommerce 6.0+, WordPress 5.8+, PHP 8.0+
- HPOS compatible (High-Performance Order Storage)
- Follows WordPress/WooCommerce coding standards
- All automated tests passing (60/60 checks)

### Configuration Required
- UPS Developer API credentials (from https://developer.ups.com/)
- Warehouse and store addresses
- Product tags: 'SSAW-App' for warehouse items, 'available-in-store' for store items
- Product weights (defaults to 1 lb if missing)

---

## Documentation Structure

The following documentation files were created for this release:

- **directory_map.md** - Complete file structure and integration points
- **invariants.md** - Coding rules, conventions, and critical business logic
- **plugins.md** - Current plugin versions and dependencies
- **CHANGELOG.md** - This file

All documentation located in `/ops/ai/plans/`

---

## Previous Changes

### [2025-10-12] - Blocks and Theme Updates
- Service Showcase block with card reordering
- Dynamic countdown timer with timezone support
- USP blocks with advanced styling
- Enhanced Kadence child theme

### [2025-10-08] - AI Operations Workspace
- Created `/ops/ai/` directory structure
- Added role-based playbooks (Architect, Tester, Librarian)
- Implemented phase-based development workflow
