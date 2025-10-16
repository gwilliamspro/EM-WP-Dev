# Epic Marks Shipping Plugin - Directory Map

## Root Files
```
epic-marks-shipping/
├── epic-marks-shipping.php    Main plugin file, initialization, hooks (v2.3.0)
├── CHANGELOG.md               Version history and feature updates
├── directory_map.md           This file - plugin structure documentation
└── README.md                  User-facing plugin documentation
```

## Core Classes (/includes)
```
includes/
├── class-ups-api.php                    UPS API wrapper for rate quotes (284 LOC)
├── class-shipping-method.php            WooCommerce shipping method integration (650+ LOC)
├── class-shipping-profile.php           Profile data model and CRUD (6.4KB)
├── class-profile-manager.php            Profile queries and product assignment (8.1KB)
├── class-profile-rate-calculator.php    Rate calculation by profile (7.2KB)
├── class-package-splitter.php           Multi-package cart splitting and routing (10.5KB)
├── class-shipping-reports.php           Report data queries and CSV export (8.8KB)
├── class-location-manager.php           Location CRUD operations (8.3KB) [v2.1.0]
├── class-ssaw-warehouse-selector.php    SSAW warehouse selection algorithm (6.9KB) [v2.1.0]
├── class-shipping-rule-engine.php       Conditional free shipping rules (9.4KB) [v2.1.0]
├── class-box-manager.php                Box configuration and selection (7.5KB) [v2.1.0]
├── class-delivery-estimator.php         Business day calculator, delivery dates (10.8KB) [v2.3.0]
├── class-shipping-fee-calculator.php    Transparent fee calculation (6.2KB) [v2.3.0]
└── migration.php                        One-time migration scripts (4.5KB)
```

## Admin UI (/admin)
```
admin/
├── admin-tabs.php           Tab router and navigation rendering
├── settings-page.php        Settings page registration and rendering
├── product-meta.php         Product edit page shipping tab (enhanced v2.1.0)
├── order-meta-box.php       Order admin meta box with routing info (enhanced v2.2.0)
├── profile-edit.php         Profile create/edit form
├── bulk-assignment.php      AJAX bulk product assignment tool
├── location-edit.php        Location create/edit form (3.2KB) [v2.1.0]
├── box-edit.php             Box create/edit form (2.8KB) [v2.1.0]
├── rule-edit.php            Free shipping rule form (4.1KB) [v2.1.0]
│
├── tabs/                    Admin tab content pages
│   ├── setup-tab.php             UPS credentials, services (153 lines, cleaned up v2.3.1)
│   ├── locations-tab.php         Location management UI (6.8KB) [v2.1.0]
│   ├── profiles-tab.php          Profile management list view (6.0KB)
│   ├── routing-tab.php           Tag-based automation rules (6.1KB)
│   ├── rules-tab.php             Free shipping rules management (5.5KB) [v2.1.0]
│   ├── package-control-tab.php   Box configuration UI (7.2KB) [v2.1.0]
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
├── checkout-packages.css  Multi-package display styling (2.4KB)
├── location-admin.js      Location UI interactions (2.9KB) [v2.1.0]
├── location-admin.css     Location UI styling (1.8KB) [v2.1.0]
├── rule-admin.js          Rule builder interactions (3.4KB) [v2.1.0]
├── rule-admin.css         Rule UI styling (2.1KB) [v2.1.0]
├── checkout-routing.js    Routing toggle AJAX handler (3.2KB) [v2.2.0]
└── checkout-routing.css   Routing options styling (2.5KB) [v2.2.0]
```

## Templates (/templates)
```
templates/
├── checkout-shipping-toggle.php    Customer shipping mode selector UI (1.8KB)
├── checkout-routing-options.php    Ship together vs split options (2.9KB) [v2.2.0]
├── checkout-delivery-estimate.php  Delivery date display (1.2KB) [v2.3.0]
└── checkout-fee-breakdown.php      Transparent fee line items (1.6KB) [v2.3.0]
```

## Key File Relationships

### Plugin Initialization Flow
```
epic-marks-shipping.php
├── Requires all includes/*.php classes
├── Registers WooCommerce shipping method (class-shipping-method.php)
├── Loads admin UI (admin-tabs.php, settings-page.php)
├── Enqueues scripts conditionally (admin vs checkout)
└── Registers AJAX endpoints (bulk assignment, shipping mode, routing)
```

### Rate Calculation Flow (Enhanced v2.1.0-2.3.0)
```
class-shipping-method.php (WooCommerce entry point)
├── group_by_profile() → class-profile-rate-calculator.php
├── calculate() → class-profile-rate-calculator.php
│   ├── check_free_shipping_rules() → class-shipping-rule-engine.php [v2.1.0]
│   ├── select_ssaw_warehouse() → class-ssaw-warehouse-selector.php [v2.1.0]
│   ├── select_box() → class-box-manager.php [v2.1.0]
│   ├── calculate_fees() → class-shipping-fee-calculator.php [v2.3.0]
│   ├── estimate_delivery() → class-delivery-estimator.php [v2.3.0]
│   └── get_rates() → class-ups-api.php
├── split_packages() → class-package-splitter.php (if partial pickup)
│   └── calculate_routing_options() [v2.2.0]
└── apply_dim_weight() → class-box-manager.php [v2.1.0]
```

### Admin Settings Flow (Enhanced v2.1.0)
```
admin-tabs.php (tab router)
├── Tab: Setup → tabs/setup-tab.php
├── Tab: Locations → tabs/locations-tab.php [v2.1.0]
│   └── Edit Location → location-edit.php
│       └── Save → class-location-manager.php
├── Tab: Profiles → tabs/profiles-tab.php
│   └── Edit Profile → profile-edit.php
│       └── Save → class-shipping-profile.php
├── Tab: Routing → tabs/routing-tab.php
│   └── Bulk Assignment → bulk-assignment.php
│       └── AJAX → class-profile-manager.php
├── Tab: Rules → tabs/rules-tab.php [v2.1.0]
│   └── Edit Rule → rule-edit.php
│       └── Save → class-shipping-rule-engine.php
├── Tab: Package Control → tabs/package-control-tab.php [v2.1.0]
│   └── Edit Box → box-edit.php
│       └── Save → class-box-manager.php
└── Tab: Reports → tabs/reports-tab.php
    └── Report Rendering → reports/*.php
        └── Data Queries → class-shipping-reports.php
```

## File Size Summary
- Total PHP files: 38 (was 25 in v2.0.0)
- Total JS/CSS files: 12 (was 6 in v2.0.0)
- Total LOC (estimated): ~22,000 (was ~15,000 in v2.0.0)
- Largest file: class-shipping-method.php (650+ LOC)
- Most complex: class-shipping-method.php (650+ LOC, was 500+ in v2.0.0)

## Dependencies
- WordPress 5.8+
- WooCommerce 6.0+
- PHP 8.0+
- Chart.js 4.4.0 (CDN, reports only)
- jQuery (WordPress core, admin UI)

## Version History
**v2.3.1 (2025-10-16):** Setup tab cleanup (removed deprecated fields)
**v2.3.0 (2025-10-16):** Delivery estimation + transparent fees
**v2.2.0 (2025-10-16):** Intelligent routing (ship together vs split)
**v2.1.0 (2025-10-16):** Multi-location, SSAW warehouses, boxes, conditional rules
**v2.0.0 (2025-10-15):** Shipping profiles + package splitting + reports
**v1.0.0 (2025-01-15):** Initial UPS integration + tag routing
