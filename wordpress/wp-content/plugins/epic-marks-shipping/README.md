# Epic Marks Shipping Plugin

**Version:** 2.0.0  
**Requires:** WordPress 5.8+, WooCommerce 6.0+, PHP 8.0+  
**Author:** Epic Marks Development Team

## Overview

Comprehensive shipping management system for WooCommerce with UPS integration, shipping profiles, multi-location fulfillment, and PirateShip reporting.

## Features

### Shipping Profiles
- Create unlimited shipping profiles with custom configurations
- Assign products to profiles via dropdown or bulk assignment
- Profile-specific fulfillment locations (warehouse, store, or both)
- Zone-based shipping methods (foundation for future expansion)
- Tag-based automation for bulk product assignment

### Multi-Location Fulfillment
- **Warehouse Shipping:** UPS real-time rates for direct-to-customer shipping
- **In-Store Pickup:** Free local pickup option for store-stocked items
- **Ship to Store:** Warehouse items shipped to store with accurate pricing (UPS base rate + configurable margin)
- **Package Splitting:** Customers can choose per-package shipping methods in single order

### Checkout Experience
- Intelligent shipping mode toggle: "Ship Everything" vs "Partial Pickup"
- Multi-package display with clear labeling
- Per-package method selection (pickup, ship, or ship-to-store)
- Mobile-responsive design

### Admin Interface
Organized tabbed UI for better management:
- **Setup Tab:** UPS credentials, addresses, service configuration
- **Profiles Tab:** Create/edit shipping profiles
- **Routing Tab:** Bulk product assignment and tag automation
- **Package Control Tab:** Box configuration (coming in Phase 3B)
- **Reports Tab:** Comprehensive shipping analytics

### Reports & Analytics
- **Cost Analysis:** Estimated vs actual label costs (PirateShip integration)
- **Fulfillment Breakdown:** Warehouse vs store performance
- **Service Performance:** Ground, 2-Day, Next Day usage statistics
- **Missing Labels:** Orders awaiting label purchase
- **Chart.js visualizations** with CSV export

### UPS Integration
- Real-time rate quotes from UPS API
- Support for all standard UPS services (Ground, 2-Day Air, Next Day, etc.)
- 30-minute rate caching for performance
- Dimensional weight calculation
- Fallback rates for API failures

### PirateShip Integration
- Deep links for one-click label purchase
- Label cost tracking in reports
- Service usage analytics
- Tracking number management

## Installation

1. Upload plugin files to `/wp-content/plugins/epic-marks-shipping/`
2. Activate via WordPress admin (Plugins > Installed Plugins)
3. Navigate to WooCommerce > UPS Shipping
4. Configure UPS credentials in Setup tab
5. Click "Initialize Default Profiles" in Profiles tab
6. Assign products to profiles (Routing tab or product edit page)

## Configuration

### UPS Credentials
Required for live rate quotes:
- Access Key
- User ID
- Password
- Account Number
- Shipper Number

Available from UPS Developer Portal: [ups.com/developer](https://developer.ups.com/)

### Fulfillment Locations
Configure warehouse and store addresses in Setup tab:
- Warehouse: Company Name, Address, City, State, ZIP
- Store: Company Name, Address, City, State, ZIP

### Shipping Profiles
Create profiles based on product categories:
1. Navigate to Profiles tab → Add New Profile
2. Set profile name (e.g., "General Products", "SSAW Products")
3. Select fulfillment locations (warehouse, store, or both)
4. Enable local pickup (requires Store location)
5. Configure Ship to Store (requires both locations):
   - Margin Type: Percentage or Flat Amount
   - Margin Value: e.g., 30% or $5
   - Customer Label: e.g., "Ship to Store (includes handling)"

### Product Assignment
**Option 1: Manual Assignment**
1. Edit product > Shipping tab
2. Select profile from dropdown
3. Save product

**Option 2: Bulk Assignment**
1. Navigate to Routing tab
2. Select tag (e.g., "SSAW-App")
3. Select profile (e.g., "SSAW Products")
4. Click "Start Bulk Assignment"
5. Wait for progress bar completion

**Option 3: Automatic Migration**
- Plugin automatically assigns products on first activation
- Products with "SSAW-App" tag → SSAW Products profile
- Products with "available-in-store" tag → General profile
- Migration runs once, tracked by version number

## Usage

### Customer Checkout Flow

**Single-Location Cart (Simple):**
1. Customer adds warehouse products to cart
2. Proceeds to checkout
3. Sees standard UPS shipping rates
4. Selects preferred service (Ground, 2-Day, etc.)
5. Completes order

**Multi-Location Cart (Advanced):**
1. Customer adds warehouse + store products
2. Proceeds to checkout
3. Sees "Shipping Options" toggle
4. Default: "Ship Everything to Me" (all products shipped)
5. Alternative: "Partial In-Store Pickup"
   - Cart splits into packages by location
   - Package 1 (Store): Local Pickup (Free) OR Ship to Me
   - Package 2 (Warehouse): Ship to Me OR Ship to Store
6. Selects method per package
7. Completes order

### Ship to Store Pricing Example
**Scenario:** Warehouse product, customer wants store pickup

**Calculation:**
1. UPS API: Warehouse → Store = $8.00 (base rate)
2. Profile margin: 30% (covers supplier's higher costs)
3. Customer cost: $8.00 × 1.30 = $10.40
4. Displayed: "Ship to Store - UPS Ground $10.40 (includes handling)"

**Why not free?** Margin covers the difference between supplier's actual shipping costs and UPS retail rates, ensuring accurate pricing.

## Reports

### Accessing Reports
WooCommerce > UPS Shipping > Reports tab

### Date Range Filtering
- Manual: Select start and end dates
- Quick shortcuts: Last 7 Days, Last 30 Days, This Month, Last Month

### Available Reports

**1. Cost Analysis**
- Total orders vs orders with labels
- Estimated costs (customer paid)
- Actual costs (PirateShip labels)
- Savings/losses
- Average label cost
- CSV export

**2. Fulfillment Breakdown**
- Orders by location (warehouse, store, unknown)
- Local pickup count
- Ship to Store count and revenue
- Pie charts for visual analysis
- CSV export

**3. Service Performance**
- Orders by service (Ground, 2-Day, Next Day, etc.)
- Percentage breakdown
- Total revenue per service
- Average cost per service
- CSV export

**4. Missing Labels**
- Orders in processing without tracking numbers
- Excludes local pickup orders (no label needed)
- Direct links to order edit page
- CSV export

## Troubleshooting

### No Shipping Rates Display
1. Check UPS credentials (Setup tab → Test API Connection)
2. Verify product has profile assigned (Product > Shipping tab)
3. Check debug.log: `/wp-content/debug.log`
4. Fallback rates configured? (Setup tab → Fallback Rates section)

### Products Not Auto-Assigned
1. Check migration ran: WooCommerce > UPS Shipping (look for success notice)
2. Verify tags exist: Products > Tags (should see SSAW-App, available-in-store)
3. Run manual bulk assignment: Routing tab → Bulk Product Assignment

### Ship to Store Not Showing
1. Profile must have BOTH warehouse AND store locations
2. "Ship to Store" must be enabled in profile settings
3. Margin must be configured (percentage or flat)
4. UPS API must be accessible (warehouse → store route)

### Reports Show "PirateShip Plugin Required"
1. Install PirateShip for WooCommerce plugin
2. Activate plugin
3. Purchase at least one label
4. Refresh reports

### Performance Issues
1. Enable object caching (Redis, Memcached)
2. Verify transient caching working (30-min cache for rates)
3. Check Query Monitor for slow queries
4. Consider bulk profile assignment during off-peak hours

## Database Schema

### WordPress Options
- `em_ups_settings` - UPS credentials and configuration
- `em_shipping_profiles` - All profile configurations
- `em_profile_migration_version` - Migration tracking

### Product Meta
- `_shipping_profile` - Profile assignment (e.g., "ssaw-products")
- `_em_enable_shipping_markup` - Product-specific markup toggle
- `_em_markup_type` - Markup type (percentage/flat)
- `_em_markup_value` - Markup amount

### Order Meta
- `_shipping_profile_used` - Which profile calculated rates
- `_fulfillment_location` - warehouse/store
- `_partial_pickup_enabled` - Multi-package order flag
- `_ship_to_store_selected` - Ship to store option chosen
- `_ship_to_store_cost` - Final cost charged
- `_ship_to_store_base_rate` - UPS base rate
- `_ship_to_store_margin_applied` - Margin added

### PirateShip Meta (External Plugin)
- `_pirateship_label_cost` - Actual label cost
- `_pirateship_tracking_number` - Tracking number
- `_pirateship_service` - Service used

## Hooks & Filters

### Actions
- `em_profile_saved` - After profile save
- `em_profile_deleted` - After profile deletion
- `em_product_assigned` - After product assignment

### Filters
- `em_shipping_rate_calculated` - Modify calculated rates
- `em_package_split_criteria` - Customize package splitting
- `em_ship_to_store_margin` - Adjust ship-to-store margin

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## Support

For issues or feature requests:
1. Check troubleshooting section above
2. Review [CHANGELOG.md](CHANGELOG.md) for recent changes
3. Check WordPress debug.log for errors
4. Contact Epic Marks development team

## Credits

- **UPS API:** United Parcel Service
- **Chart.js:** [chartjs.org](https://www.chartjs.org/)
- **PirateShip Integration:** [pirateship.com](https://www.pirateship.com/)
