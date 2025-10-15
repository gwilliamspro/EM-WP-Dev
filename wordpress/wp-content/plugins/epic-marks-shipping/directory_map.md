# Epic Marks Shipping Plugin - Directory Map

## Root Files
```
epic-marks-shipping/
├── epic-marks-shipping.php    Main plugin file, initialization, hooks (v2.0.0)
├── CHANGELOG.md               Version history and feature updates
├── directory_map.md           This file - plugin structure documentation
└── README.md                  User-facing plugin documentation
```

## Core Classes (/includes)
```
includes/
├── class-ups-api.php                 UPS API wrapper for rate quotes (284 LOC)
├── class-shipping-method.php         WooCommerce shipping method integration (500+ LOC)
├── class-shipping-profile.php        Profile data model and CRUD (6.4KB)
├── class-profile-manager.php         Profile queries and product assignment (8.1KB)
├── class-profile-rate-calculator.php Rate calculation by profile (7.2KB)
├── class-package-splitter.php        Multi-package cart splitting logic (7.8KB)
├── class-shipping-reports.php        Report data queries and CSV export (8.8KB)
└── migration.php                     One-time product migration script (4.5KB)
```

## Admin UI (/admin)
```
admin/
├── admin-tabs.php           Tab router and navigation rendering
├── settings-page.php        Settings page registration and rendering
├── product-meta.php         Product edit page shipping tab
├── order-meta-box.php       Order admin meta box with PirateShip link
├── profile-edit.php         Profile create/edit form
├── bulk-assignment.php      AJAX bulk product assignment tool
│
├── tabs/                    Admin tab content pages
│   ├── setup-tab.php             UPS credentials, locations, services (14KB)
│   ├── profiles-tab.php          Profile management list view (6.0KB)
│   ├── routing-tab.php           Tag-based automation rules (6.1KB)
│   ├── package-control-tab.php   Box configuration (placeholder for Phase 3B)
│   └── reports-tab.php           Reports dashboard with date picker (9.7KB)
│
└── reports/                 Report rendering pages
    ├── cost-analysis.php         Estimated vs actual label costs (5.3KB)
    ├── fulfillment-breakdown.php Warehouse vs store statistics (6.5KB)
    └── service-performance.php   Service level usage analysis (6.7KB)
```

## Frontend Assets (/assets)
```
assets/
├── admin-tabs.css         Tabbed UI styling
├── profile-admin.js       Profile edit form interactions (2.7KB)
├── bulk-assignment.js     AJAX batch processing with progress bar (3.1KB)
├── reports-charts.js      Chart.js visualizations for reports (6.0KB)
├── checkout-toggle.js     Shipping mode toggle AJAX handler (2.1KB)
└── checkout-packages.css  Multi-package display styling (2.4KB)
```

## Templates (/templates)
```
templates/
└── checkout-shipping-toggle.php    Customer shipping mode selector UI (1.8KB)
```

## Key File Relationships

### Plugin Initialization Flow
```
epic-marks-shipping.php
├── Requires all includes/*.php classes
├── Registers WooCommerce shipping method (class-shipping-method.php)
├── Loads admin UI (admin-tabs.php, settings-page.php)
├── Enqueues scripts conditionally (admin vs checkout)
└── Registers AJAX endpoints (bulk assignment, shipping mode)
```

### Rate Calculation Flow
```
class-shipping-method.php (WooCommerce entry point)
├── group_by_profile() → class-profile-rate-calculator.php
├── calculate() → class-profile-rate-calculator.php
│   └── get_rates() → class-ups-api.php
└── split_packages() → class-package-splitter.php (if partial pickup)
    └── calculate_ship_to_store_rate() → class-ups-api.php
```

### Admin Settings Flow
```
admin-tabs.php (tab router)
├── Tab: Setup → tabs/setup-tab.php
├── Tab: Profiles → tabs/profiles-tab.php
│   └── Edit Profile → profile-edit.php
│       └── Save → class-shipping-profile.php
├── Tab: Routing → tabs/routing-tab.php
│   └── Bulk Assignment → bulk-assignment.php
│       └── AJAX → class-profile-manager.php
└── Tab: Reports → tabs/reports-tab.php
    └── Report Rendering → reports/*.php
        └── Data Queries → class-shipping-reports.php
```

## File Size Summary
- Total PHP files: 25
- Total JS/CSS files: 6
- Total LOC (estimated): ~15,000
- Largest file: admin/tabs/setup-tab.php (14KB)
- Most complex: class-shipping-method.php (500+ LOC)

## Dependencies
- WordPress 5.8+
- WooCommerce 6.0+
- PHP 8.0+
- Chart.js 4.4.0 (CDN, reports only)
- jQuery (WordPress core, admin UI)

## Modified from Initial Version
**New in v2.0.0:**
- 20 new files (profiles, package splitting, reports)
- 5 modified files (main plugin, shipping method, admin pages)
- Directory structure expanded from 6 files → 31 files
