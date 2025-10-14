# UPS + PirateShip Shipping Plugin - Architecture Plan

**Status**: ✅✅ READY FOR IMPLEMENTATION
**Priority**: Medium
**Estimated Effort**: 16-22 hours (reduced via tag system)
**Author**: Claude (AI Assistant)
**Last Updated**: 2025-10-14 (v4.0 - Tag-Based System)

---

## Quick Summary

Custom WooCommerce shipping plugin providing:
- Real-time UPS rates (Ground, 2-Day, Next Day) via UPS API
- Multi-location support (warehouse + retail store, tag-based like Shopify)
  - Tag `SSAW-App` → ships from warehouse
  - Tag `available-in-store` → ships from store
  - Both tags → admin preference (warehouse or store priority)
- Location-aware markup for dropship protection
- Free shipping threshold (cart total >= $X)
- PirateShip deep-link integration for discounted label purchasing
- Smart caching (30-min transients) to reduce API calls
- **Cost savings**: Eliminates $129-248/year in premium plugin subscriptions

---

## Scope (Files to Create/Modify)

### Phase 1: Core Plugin (6 files)

1. **wordpress/wp-content/plugins/epic-marks-shipping/epic-marks-shipping.php** (NEW)
   - Main plugin file: registration, constants, autoloader
   - Hooks: `woocommerce_shipping_methods`, `plugins_loaded`
   - Lines: ~80

2. **wordpress/wp-content/plugins/epic-marks-shipping/includes/class-shipping-method.php** (NEW)
   - Extends `WC_Shipping_Method`
   - Method: `calculate_shipping($package)` - core logic
   - Calls UPS API, applies markup, checks free shipping
   - Lines: ~200

3. **wordpress/wp-content/plugins/epic-marks-shipping/includes/class-ups-api.php** (NEW)
   - UPS Rating API wrapper
   - Methods: `authenticate()`, `get_rates()`, `parse_response()`
   - Error handling, logging, sandbox mode
   - Lines: ~150

4. **wordpress/wp-content/plugins/epic-marks-shipping/admin/settings-page.php** (NEW)
   - Settings API integration
   - Sections: UPS credentials, locations, services, free shipping threshold
   - Sanitization callbacks, validation
   - Lines: ~180

5. **wordpress/wp-content/plugins/epic-marks-shipping/admin/product-meta.php** (NEW)
   - Product shipping tab fields (markup settings only - location determined by tags)
   - Reads product tags: `SSAW-App` (warehouse), `available-in-store` (store)
   - Hooks: `woocommerce_product_options_shipping`, `woocommerce_process_product_meta`
   - Lines: ~100

6. **wordpress/wp-content/plugins/epic-marks-shipping/admin/order-meta-box.php** (NEW)
   - "Purchase Label on PirateShip" button
   - Deep link builder with order data
   - Hook: `add_meta_boxes`
   - Lines: ~80

**Total: 6 files, ~790 lines of PHP**

**Justification for 6 files**: WordPress best practices require separation of concerns. Plugin structure follows WordPress Plugin Handbook standards. All files are necessary for modular, testable code.

### Tag-Based Location System
- **No custom meta fields for location** - uses existing WooCommerce product tags
- Tags mirror Shopify structure: `SSAW-App` (warehouse), `available-in-store` (store)
- Products with both tags: Admin setting controls priority (default: warehouse)
- Backward compatible: Products without tags default to warehouse (configurable)

### Dependencies Checked

**wordpress/wp-content/themes/kadence-child/functions.php** (READ ONLY)
- Verified: No WooCommerce shipping hooks (lines 1-141)
- Theme adds WooCommerce support (line 41) but no shipping customizations
- **Status**: No conflicts

---

## Execution Flow Analysis

### WordPress/WooCommerce Hook Flow

```
Cart Page Load / Checkout Load:
1. WC_Cart::calculate_fees() triggers
2. do_action('woocommerce_calculate_shipping') fires
3. WC_Shipping::calculate_shipping($package) iterates all registered methods
4. Our method: EM_UPS_Shipping::calculate_shipping($package) executes
   - Line 45: Get cart items from $package['contents']
   - Line 48: For each product, get tags via wp_get_post_terms($product_id, 'product_tag')
   - Line 52: Determine location:
     - Has `SSAW-App` tag → warehouse
     - Has `available-in-store` tag → store
     - Has BOTH tags → check admin preference (em_ups_overlap_preference: warehouse|store)
     - No tags → default location (em_ups_default_location: warehouse|store)
   - Line 68: Group products by determined location (warehouse/store)
   - Line 82: For each origin, call UPS API via Class_UPS_API::get_rates()
   - Line 109: Apply location-aware markup from product meta (_em_markup_value)
   - Line 123: Combine rates (strategy: highest/sum/split - configurable)
   - Line 138: Check free shipping threshold (cart subtotal vs. em_ups_free_shipping_threshold)
   - Line 145: Add rates via $this->add_rate($rate) - WooCommerce core function
5. apply_filters('woocommerce_package_rates', $rates, $package)
6. Rates displayed at checkout via WooCommerce templates (no override needed)
7. Customer selects rate
8. Order created: WC saves _shipping_method, _shipping_method_title, _shipping_total to wp_postmeta
```

**Admin - Settings Save**:
```
1. User submits form (POST to options.php)
2. WordPress Settings API triggers registered sanitization callback
3. sanitize_callback: em_sanitize_settings() validates UPS credentials, addresses
4. Saved to wp_options: em_ups_settings (serialized array)
5. Admin notice: Settings saved
```

**Admin - Product Save**:
```
1. User saves product (POST to post.php)
2. do_action('woocommerce_process_product_meta', $post_id)
3. Our callback: em_save_product_shipping_meta($post_id)
   - Read tags: wp_get_post_terms($post_id, 'product_tag') - location determined by tags
   - Sanitize: _em_enable_shipping_markup (yes|no)
   - Sanitize: _em_markup_type (percentage|flat)
   - Sanitize: _em_markup_value (float)
4. Saved to wp_postmeta
5. Admin notice displays detected location (based on tags): "Shipping: Warehouse (SSAW-App tag)"
```

**Admin - Order Page "Purchase Label" Button**:
```
1. Order edit page loads
2. do_action('add_meta_boxes')
3. Our callback: em_add_pirateship_meta_box() registers meta box
4. Meta box displays:
   - Customer shipping address
   - Total package weight (calculated from line items)
   - Selected UPS service
   - Button: "Purchase Label on PirateShip"
5. Click opens: pirateship.com/ship?to_name={}&to_address={}&weight={}&service={}
```

---

## Complete Impact Analysis

### Direct Systems Affected

#### 1. WooCommerce Shipping Calculation
**Status**: Directly affected (new shipping method added)

**How it works**:
WooCommerce iterates all registered shipping methods during cart/checkout calculation. Each method returns available rates. Customer selects one. Rate saved to order meta.

**Data it reads**:
- Cart package: $package['contents'] (products, quantities, weights)
- Product meta: wp_postmeta (_weight, _em_markup_value)
- Product tags: wp_term_relationships (SSAW-App, available-in-store)
- Customer address: $package['destination'] (city, state, zip)
- Plugin settings: wp_options (em_ups_settings)

**Data it writes**:
- Transient cache: wp_options (_transient_em_ups_rate_{hash}) - 30 min TTL
- Order meta: wp_postmeta (_shipping_method, _shipping_total)

**Impact**: POSITIVE
- Adds new shipping method "UPS Live Rates" to WooCommerce
- Does NOT remove/replace existing methods (flat rate, free shipping, etc.)
- Customers see all methods, choose best option
- Multi-origin carts: 2+ UPS API calls (cached)

**Evidence**:
- WooCommerce core: includes/class-wc-shipping.php line 143 (`calculate_shipping()`)
- Our hook: `add_filter('woocommerce_shipping_methods', 'em_register_ups_method')`
- Isolated: No modifications to WooCommerce core files

**Edge cases**:
- UPS API timeout → Show fallback flat rates (configurable in settings)
- Invalid address → UPS returns error, display validation message
- Cart weight = 0 → Default to 1 lb (admin warning if product weight missing)
- Multi-origin cart → Call UPS API twice (warehouse + store), combine via strategy

---

#### 2. WP Super Cache (Active Plugin)
**Status**: HIGH RISK - Cache conflicts

**How it works**:
WP Super Cache caches entire page HTML. If cart/checkout pages are cached, UPS rates will be stale (wrong prices shown to customers).

**Data it reads**:
- wp_options: wp_super_cache_* settings
- Cached pages: wp-content/cache/supercache/

**Data it writes**:
- Static HTML files for cached pages

**Impact**: NEGATIVE (if not mitigated)
- Cached checkout page shows old UPS rates
- Customer sees $12.45 but actual rate is $15.00
- Cart changes don't trigger re-calculation

**Mitigation**:
- Add cart/checkout to WP Super Cache exclusion list
- Filter: `add_filter('wpsc_cache_uris', 'em_exclude_cart_from_cache')`
- Exclude: `/cart`, `/checkout`, `/my-account`
- **REQUIRED in Phase 1**

**Evidence**:
- WP Super Cache plugin active (verified via wp plugin list)
- wordpress/wp-content/plugins/wp-super-cache/ exists
- Default behavior: caches all pages except admin

**Edge cases**:
- User clears cache → Rates recalculate (good)
- Cache plugin disabled → Plugin still works (no dependency)

---

#### 3. WooCommerce Square (Active Plugin)
**Status**: CHECK - Payment gateway integration

**How it works**:
Square processes payments. Shipping total must pass correctly to Square API for accurate charge amounts.

**Data it reads**:
- Order meta: _shipping_total (from our plugin)
- Order total: includes shipping

**Data it writes**:
- Square transaction ID to order meta

**Impact**: NEUTRAL (no conflicts expected)
- Square reads order total (product + shipping + tax)
- Our plugin sets _shipping_total like any WooCommerce shipping method
- Square unaware of UPS vs. flat rate (just sees total)

**Verification needed**:
- Test checkout with Square sandbox
- Verify transaction amount = products + UPS rate + tax

**Evidence**:
- WooCommerce Square plugin active (verified)
- Square processes totals, not line items
- Standard WooCommerce order meta structure

---

#### 4. Product Data & Weight Configuration
**Status**: CRITICAL DEPENDENCY

**How it works**:
UPS pricing requires accurate weights. If products lack weight data, API calls fail or return inaccurate rates.

**Data it reads**:
- wp_postmeta: _weight (WooCommerce standard meta key)

**Data it writes**:
- N/A (read-only)

**Impact**: HIGH DEPENDENCY
- Missing weight → Default to 1 lb (fallback)
- Inaccurate weight → Inaccurate rates → Customer dissatisfaction

**Mitigation**:
- Admin audit page: "Products Missing Weight Data" report
- Warning notice if weight = 0 when saving product
- Bulk edit tool for weight assignment (future enhancement)

**Evidence**:
- WooCommerce stores weight in wp_postmeta (_weight)
- UPS API requires weight (required field in RateRequest)

---

#### 5. WordPress Transients (Rate Caching)
**Status**: NEW SYSTEM - Caching layer

**How it works**:
WordPress transients provide temporary data storage. We cache UPS API responses for 30 minutes keyed by origin + destination + weight.

**Data it reads**:
- wp_options: _transient_em_ups_rate_{hash}

**Data it writes**:
- Transient on API success: set_transient('em_ups_rate_{hash}', $rates, 1800)

**Impact**: POSITIVE
- Reduces API calls (UPS has rate limits)
- Faster checkout (no 1-2 second API wait)
- Shared cache: Multiple customers same zip → 1 API call

**Evidence**:
- WordPress transient API: wp-includes/option.php
- Expiration: 1800 seconds (30 minutes)
- Auto-cleanup: WordPress cron job deletes expired transients

**Edge cases**:
- Cache hit rate low if customers all different zips → Acceptable
- Cache miss → API call happens, new cache entry created
- Rate changes → Cache expires after 30 min (acceptable delay)

---

### Downstream Systems

#### 6. Docker Environment
**Status**: Unaffected (no rebuild needed)

**How it works**:
WordPress files live-mounted from ./wordpress/ directory. Changes take effect immediately.

**Impact**: POSITIVE
- No `docker compose rebuild` needed
- Edit PHP files, refresh browser → changes live
- Plugin activation via WP admin or WP-CLI

**Verification**:
- Files created in ./wordpress/wp-content/plugins/epic-marks-shipping/
- Ownership: www-data:www-data (set via docker exec chown)
- Permissions: 755 directories, 644 files

**Evidence**:
- CLAUDE.md line 24: "Changes persist immediately without rebuilds"
- docker-compose.yml: volume mount ./wordpress:/var/www/html

---

#### 7. Database Performance
**Status**: Minimal impact

**Queries added**:
- Product meta reads: `SELECT meta_value FROM wp_postmeta WHERE post_id = X AND meta_key = '_em_ship_from_location'` (per product in cart, ~3-5 queries)
- Settings read: `SELECT option_value FROM wp_options WHERE option_name = 'em_ups_settings'` (1 query, cached by WordPress)
- Transient read: `SELECT option_value FROM wp_options WHERE option_name = '_transient_em_ups_rate_{hash}'` (1 query)
- Transient write: `INSERT INTO wp_options ...` (1 query on cache miss)

**Total**: 5-8 queries per checkout load

**Impact**: LOW
- Product meta already loaded by WooCommerce (no extra query)
- Settings cached by WordPress object cache
- Transients reduce repeated UPS API calls

**Verification**:
- Use Query Monitor plugin (installed, currently inactive)
- Measure before/after query count
- Target: <10 additional queries

---

## Dependencies

### WordPress Core
- **Version**: 5.8+ (block editor API)
- **Functions**: `register_activation_hook()`, `add_action()`, `add_filter()`, `update_option()`, `get_option()`, `set_transient()`, `get_transient()`

### WooCommerce
- **Version**: 6.0+ (required)
- **Current**: 10.2.2 (verified active)
- **Classes**: `WC_Shipping_Method`, `WC_Cart`, `WC_Order`
- **Hooks**: `woocommerce_shipping_methods`, `woocommerce_process_product_meta`, `add_meta_boxes`
- **HPOS Compatibility**: MUST use `wc_get_order()` instead of direct post queries (WooCommerce 8.0+ may enable HPOS)

### PHP Extensions
- **cURL**: Required for UPS API calls (already installed in wordpress_app container)
- **JSON**: Required for API request/response (standard in PHP 7.4+)
- **Version**: PHP 8.3.26 (current container version)

### External APIs
- **UPS Rating API**: https://onlinetools.ups.com/ship/v1/rating/Rate
  - Credentials: Access Key, User ID, Password, Account Number (provided by user)
  - Rate limits: Unknown (mitigate with caching)
  - Sandbox: https://wwwcie.ups.com (test mode option in settings)
- **PirateShip**: Deep link only (no API calls)
  - Format: `pirateship.com/ship?to_name={}&to_address={}&weight={}`

### Active Plugins (Checked for Conflicts)
- **WooCommerce**: Required ✅
- **WooCommerce Square**: No conflicts (payment gateway only) ✅
- **WP Super Cache**: Conflicts (MUST exclude cart/checkout) ⚠️ **MITIGATION REQUIRED**
- **Kadence Blocks**: No interaction ✅
- **Query Monitor**: Use for debugging (currently inactive) ✅

### Prerequisites (Required Installation)
- **PirateShip Official WooCommerce Plugin**: Install for enhanced order sync
  - Download: https://wordpress.org/plugins/pirateship-woocommerce-shipping/
  - Install via: WP Admin > Plugins > Add New > Search "PirateShip"
  - Activation: Connect PirateShip account via plugin settings
  - Integration: Our plugin's deep-link button works independently, but official plugin provides order sync

### Theme
- **Kadence Child**: No conflicts (verified functions.php:1-141)
- **WooCommerce support**: Enabled (line 41)
- **Templates**: No overrides needed (WooCommerce default checkout template works)

### Database Schema
- **wp_postmeta**: Product shipping meta (3 new meta keys)
  - `_em_enable_shipping_markup`: 'yes' | 'no'
  - `_em_markup_type`: 'percentage' | 'flat'
  - `_em_markup_value`: float
- **wp_term_relationships**: Product tags (uses existing WooCommerce tags - NO new tables)
  - Tag: `SSAW-App` → warehouse location
  - Tag: `available-in-store` → store location
- **wp_options**: Plugin settings (1 option key)
  - `em_ups_settings`: Serialized array (API creds, locations, services, thresholds, overlap preference, default location)
- **Transients**: Rate cache (dynamic keys)
  - `_transient_em_ups_rate_{hash}`: Cached rates (30 min expiration)

---

## Risks & Mitigations

### Risk 1: WP Super Cache Conflicts
- **Impact**: CRITICAL (shows wrong prices)
- **Likelihood**: HIGH (plugin active, caches all pages by default)
- **Mitigation**:
  - Add cart/checkout to exclusion list via `wpsc_cache_uris` filter
  - Code in plugin activation hook: `em_configure_cache_exclusions()`
  - Test: Clear cache, add to cart, verify rates update
- **Fallback**: Deactivate WP Super Cache if conflicts persist (user decision)
- **Lines to add**: ~15 lines in epic-marks-shipping.php

### Risk 2: UPS API Rate Limits
- **Impact**: MEDIUM (checkout delays)
- **Likelihood**: MEDIUM (unknown UPS limits)
- **Mitigation**:
  - Aggressive caching (30 min transients)
  - Fallback flat rates if API throttled
  - Admin notice if API errors exceed threshold
- **Fallback**: Temporarily disable plugin, use WooCommerce flat rate
- **Monitoring**: Log API errors to wp-content/debug.log (WP_DEBUG mode)

### Risk 3: Inaccurate Product Weights
- **Impact**: MEDIUM (inaccurate rates, customer complaints)
- **Likelihood**: MEDIUM (manual data entry prone to errors)
- **Mitigation**:
  - Admin audit page: List products with weight = 0
  - Warning notice when saving product without weight
  - Default to 1 lb if weight missing (prevents fatal errors)
- **Fallback**: Admin manually corrects product weights
- **Future**: Bulk weight editor

### Risk 4: Multi-Origin Cart Performance
- **Impact**: LOW (slightly slower checkout)
- **Likelihood**: MEDIUM (common use case)
- **Mitigation**:
  - Parallel API calls (if possible, check UPS API docs)
  - Cache each origin separately
  - Admin setting: "Prefer single origin" (combine all at warehouse)
- **Fallback**: Customer waits 2-3 seconds (acceptable for accurate rates)
- **Measurement**: Query Monitor - target <3 seconds total checkout load

### Risk 5: Docker Container Permissions
- **Impact**: LOW (plugin files unwritable)
- **Likelihood**: LOW (standard Docker setup)
- **Mitigation**:
  - Create files as user, then `docker exec wordpress_app chown -R www-data:www-data /var/www/html/wp-content/plugins/epic-marks-shipping`
  - Check in CLAUDE.md: File ownership requirements (line 19)
- **Fallback**: Manually fix permissions via bash

### Risk 6: WooCommerce HPOS Breaking Changes
- **Impact**: MEDIUM (plugin breaks if HPOS enabled)
- **Likelihood**: MEDIUM (WooCommerce 8.0+ may default to HPOS)
- **Mitigation**:
  - Use `wc_get_order($order_id)` instead of `get_post_meta()`
  - Declare HPOS compatibility in plugin header
  - Test with HPOS enabled (WooCommerce > Settings > Advanced > Custom order tables)
- **Fallback**: Disable HPOS if conflicts arise
- **Future-proofing**: Follow WooCommerce development blog

---

## Edge Cases

### 1. No Products in Cart
- **Scenario**: Customer views checkout with empty cart (edge case - shouldn't happen)
- **Behavior**: Plugin doesn't execute (WooCommerce prevents empty checkout)
- **Handling**: Graceful (no errors)

### 2. Product Missing Weight
- **Scenario**: Admin forgets to set product weight
- **Behavior**: Default to 1 lb, log warning
- **Handling**: UPS API accepts request, returns rates for 1 lb package
- **Mitigation**: Admin audit page flags products

### 3. Multi-Origin Cart (Warehouse + Store)
- **Scenario**: Cart has 2 warehouse items + 1 store item
- **Behavior**: 2 UPS API calls (one per origin), combine rates
- **Handling**: Admin chooses strategy (highest/sum/split)
- **Default**: Highest rate (safest, covers split shipment costs)

### 4. Free Shipping Threshold Edge Cases
- **Scenario A**: Cart $74.50, threshold $75.00
  - **Behavior**: Show UPS rates (not free)
  - **Future**: "Add $0.50 for free shipping" message
- **Scenario B**: Cart $80, threshold $75
  - **Behavior**: Override all rates to $0.00
  - **Customer sees**: "UPS Ground: FREE"
  - **Order notes**: "Free shipping applied (cart total $80 >= $75)"
  - **Backend**: Still tracks selected service (UPS Ground) for fulfillment

### 5. International Shipping
- **Scenario**: Customer enters Canadian address
- **Behavior**: UPS API returns international rates (if enabled)
- **Phase 1**: US domestic only
- **Phase 2 (future)**: International support (customs declarations)

### 6. UPS API Timeout
- **Scenario**: API takes >10 seconds (network issue)
- **Behavior**: Timeout, show fallback rates
- **Handling**: Log error, display admin notice, allow checkout with fallback
- **Fallback rates**: Configurable in settings (e.g., Ground $10, 2-Day $15, Next Day $25)

### 7. Invalid Customer Address
- **Scenario**: Customer enters "12345" as zip code
- **Behavior**: UPS API returns error "Invalid postal code"
- **Handling**: Display WooCommerce error: "Please verify your shipping address"
- **UX**: Checkout blocked until valid address entered

### 8. Cache Invalidation Scenarios
- **Scenario**: UPS changes rates mid-day
- **Behavior**: Stale cache for up to 30 minutes
- **Handling**: Acceptable (rates don't change frequently)
- **Admin override**: "Clear shipping cache" button in settings

### 9. Docker Container Restart During Checkout
- **Scenario**: Admin runs `docker compose restart` while customer checking out
- **Behavior**: Session lost, customer re-enters cart
- **Handling**: WordPress/WooCommerce standard behavior (session recovery)
- **Plugin impact**: None (stateless, no in-memory data)

### 10. Product with Both Tags (SSAW-App + available-in-store)
- **Scenario**: Product tagged with both `SSAW-App` AND `available-in-store`
- **Behavior**: Plugin checks admin preference setting (em_ups_overlap_preference)
- **Handling**: Ships from preferred location (warehouse or store)
- **Default**: Warehouse (configurable in settings)
- **Admin Notice**: Product edit page shows "Ships from: Warehouse (both tags present, using preference)"
- **Future**: Advanced routing (choose closest location based on customer zip)

---

## Acceptance Criteria

### Functional Requirements

**Core Functionality**:
- [ ] Plugin activates without errors (check wp-content/debug.log)
- [ ] Settings page appears at WooCommerce > Settings > Shipping > UPS Live Rates
- [ ] UPS API credentials save correctly (verified in wp_options table)
- [ ] Warehouse + store addresses save correctly
- [ ] Overlap preference setting saves (warehouse/store priority for products with both tags)
- [ ] Default location setting saves (for products with no tags)
- [ ] Product shipping tab shows markup fields (NO location dropdown - uses tags)
- [ ] Product edit page displays detected location based on tags
- [ ] Products with `SSAW-App` tag ship from warehouse
- [ ] Products with `available-in-store` tag ship from store
- [ ] Products with both tags use overlap preference (warehouse or store)
- [ ] Products with no tags use default location setting
- [ ] Cart page displays UPS rates (Ground, 2-Day, Next Day)
- [ ] Rates update when customer changes zip code (if zip input available)
- [ ] Multi-origin cart calculates rates correctly (warehouse + store)
- [ ] Markup applies correctly per product settings
- [ ] Free shipping activates when cart total >= threshold
- [ ] Order shows selected UPS service in order meta
- [ ] "Purchase Label" button appears on order edit page
- [ ] Button opens PirateShip with pre-filled data (name, address, weight, service)
- [ ] PirateShip official plugin installed and active

**Edge Cases**:
- [ ] Empty cart: No shipping rates shown (WooCommerce default behavior)
- [ ] Product missing weight: Defaults to 1 lb, admin sees warning
- [ ] UPS API timeout: Fallback rates display, error logged
- [ ] Invalid address: Checkout blocked, error message shown
- [ ] Cache hit: Rates load instantly (<100ms)
- [ ] Cache miss: Rates load within 2 seconds (UPS API call)

### Non-Functional Requirements

**Performance**:
- [ ] Checkout page load time <3 seconds (Query Monitor measurement)
- [ ] UPS API calls <5 per session (via caching)
- [ ] Database queries <10 additional per checkout
- [ ] Transient cache hit rate >60% (logged to debug.log if WP_DEBUG=true)

**Security**:
- [ ] Settings page requires `manage_woocommerce` capability
- [ ] Form submissions use nonces (`wp_nonce_field()`, `wp_verify_nonce()`)
- [ ] UPS credentials sanitized (`sanitize_text_field()`)
- [ ] Admin settings output escaped (`esc_attr()`, `esc_html()`)
- [ ] Product meta sanitized on save
- [ ] No SQL injection vectors (use `$wpdb->prepare()` if custom queries)

**Compatibility**:
- [ ] Works with Kadence theme (no template conflicts)
- [ ] Works with WooCommerce Square (payment totals correct)
- [ ] Cart/checkout excluded from WP Super Cache
- [ ] Works with WooCommerce HPOS (custom order tables)
- [ ] Docker environment: Files owned by www-data:www-data
- [ ] Docker environment: No container rebuild needed for plugin updates

**User Experience**:
- [ ] Admin settings have help text/tooltips
- [ ] Error messages are clear and actionable
- [ ] Loading indicator shown during rate calculation (if AJAX)
- [ ] PirateShip button has clear label + icon

### Regression Checks

**Must NOT Break**:
- [ ] Existing WooCommerce flat rate shipping still works
- [ ] WooCommerce checkout process unchanged (same steps)
- [ ] Other shipping methods still display (if configured)
- [ ] Product pages load without errors
- [ ] Cart page loads without errors
- [ ] WooCommerce admin pages load without errors
- [ ] Epic Marks custom blocks still render (epic-marks-blocks plugin)
- [ ] Theme styling intact (no CSS conflicts)

### Verification Steps

**After Implementation**:
1. **Check Docker logs**: `docker compose logs -f wordpress` (no PHP errors)
2. **Check WordPress debug log**: `tail -f wordpress/wp-content/debug.log` (no errors during checkout)
3. **Check database**:
   ```sql
   SELECT * FROM wp_options WHERE option_name = 'em_ups_settings';
   SELECT * FROM wp_postmeta WHERE meta_key LIKE '_em_%';
   SELECT * FROM wp_options WHERE option_name LIKE '_transient_em_ups%';
   SELECT t.name, COUNT(*) FROM wp_terms t
   JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
   WHERE tt.taxonomy = 'product_tag' AND t.name IN ('SSAW-App', 'available-in-store')
   GROUP BY t.name;
   ```
4. **Query Monitor**: Activate plugin, check Queries panel (should show <10 additional queries)
5. **UPS Sandbox**: Test with UPS test credentials (settings: Enable test mode)
6. **WP Super Cache**: Verify `/cart` and `/checkout` in exclusion list (WP Super Cache > Advanced > Rejected URLs)
7. **Browser DevTools**: Network tab - UPS API calls should be cached (no repeated requests)
8. **PirateShip Plugin**: Verify installed and connected (Plugins > Installed Plugins)

---

## Testing Strategy

### Unit Testing (Optional - Time Permitting)
- Test `em_calculate_rate()` with mock UPS responses
- Test `em_apply_markup()` with percentage/flat scenarios
- Test `em_group_by_location()` with multi-origin carts
- Test `em_check_free_shipping()` with various cart totals

### Integration Testing (Required)

**Test Case 1: Single Origin (Warehouse via Tag)**
1. Create product, add tag `SSAW-App`
2. Add 2 products to cart
3. Go to checkout
4. Expected: UPS Ground $X, 2-Day $Y, Next Day $Z (rates from warehouse address)
5. Select Ground, complete order
6. Expected: Order meta shows UPS Ground, tracking info blank (until PirateShip)

**Test Case 2: Multi-Origin (Tag-Based: Warehouse + Store)**
1. Create product A with tag `SSAW-App` (warehouse)
2. Create product B with tag `available-in-store` (store)
3. Add both to cart
4. Go to checkout
5. Expected: Rates calculated for each origin, combined per strategy
6. Admin strategy set to "highest"
7. Expected: Customer sees highest of (warehouse rate, store rate)

**Test Case 3: Free Shipping Threshold**
1. Set threshold to $75
2. Add products totaling $80
3. Go to checkout
4. Expected: "UPS Ground: $0.00" (or "FREE")
5. Order notes: "Free shipping applied"

**Test Case 4: Markup Application**
1. Create product with tag `SSAW-App` (warehouse)
2. Set product: Enable markup, 15% markup, percentage type
3. Add to cart
4. Expected: UPS Ground base rate $12.45 → Final $14.32 (15% markup)
5. Customer sees $14.32 (markup invisible)

**Test Case 4b: Both Tags - Overlap Preference**
1. Create product with tags `SSAW-App` + `available-in-store`
2. Set admin preference: "Prefer warehouse"
3. Add to cart
4. Expected: Rates calculated from warehouse address (not store)
5. Change admin preference to "Prefer store"
6. Clear cache, refresh cart
7. Expected: Rates now calculated from store address

**Test Case 5: PirateShip Deep Link**
1. Complete order with UPS Ground
2. Admin: Open order edit page
3. Expected: Meta box "Purchase Label on PirateShip"
4. Click button
5. Expected: Opens `pirateship.com/ship?to_name=...&weight=5.5&service=ground`

**Test Case 6: Cache Performance**
1. Clear all caches
2. Customer A (zip 90210) checks out - UPS API call logged
3. Customer B (zip 90210) checks out within 30 min
4. Expected: No API call (cache hit), rates display instantly

**Test Case 7: API Error Handling**
1. Set invalid UPS credentials
2. Go to checkout
3. Expected: Fallback rates display, error logged to debug.log
4. Admin sees notice: "UPS API Error - Check credentials"

---

## Rollout Plan

### Phase 1: Development (Week 1)
1. Create plugin structure (directories, main file)
2. Implement UPS API wrapper class
3. Implement shipping method class
4. Implement admin settings page
5. Implement WP Super Cache exclusion
6. Test locally with UPS sandbox

### Phase 2: Product Meta & PirateShip (Week 2)
1. Implement product shipping meta fields
2. Implement order meta box (PirateShip button)
3. Test multi-origin carts
4. Test markup scenarios

### Phase 3: Testing & Polish (Week 3)
1. Full integration testing (all test cases)
2. Query Monitor performance analysis
3. Security audit (sanitization, escaping, nonces)
4. Documentation (README, inline comments)
5. User guide for admin

### Phase 4: Deployment
1. Copy plugin to `wordpress/wp-content/plugins/epic-marks-shipping/`
2. Fix permissions: `docker exec wordpress_app chown -R www-data:www-data /var/www/html/wp-content/plugins/epic-marks-shipping`
3. Activate via WP admin: Plugins > Epic Marks Shipping > Activate
4. Configure settings: UPS credentials, addresses, services, threshold
5. Test checkout with real UPS account (not sandbox)
6. Monitor debug.log for 24 hours

### Rollback Plan
If critical issues:
1. Deactivate plugin: WP Admin > Plugins > Deactivate
2. Delete plugin directory: `rm -rf wordpress/wp-content/plugins/epic-marks-shipping`
3. Revert to WooCommerce flat rate shipping
4. Debug locally, fix, redeploy

**Rollback time**: <5 minutes (no database changes to revert)

---

## Configuration Requirements (User-Provided via UI)

All configuration will be entered via WordPress admin UI. No hard-coded values needed.

### Required at Plugin Activation

1. **UPS Account** (Settings Page):
   - UPS Developer credentials (Access Key, User ID, Password)
   - Sign up at https://developer.ups.com/ if not available
   - Test mode toggle for sandbox testing

2. **Addresses** (Settings Page):
   - Warehouse address (Street, City, State, ZIP)
   - Retail store address (Street, City, State, ZIP)

3. **Settings** (Settings Page):
   - UPS services to offer (Ground / 2-Day / Next Day / All) - checkboxes
   - Default package dimensions (L x W x H in inches)
   - Free shipping threshold ($X.XX or disabled)
   - Multi-origin strategy (highest / sum / split)
   - **Overlap preference**: Warehouse or Store (for products with both tags)
   - **Default location**: Warehouse or Store (for products with no tags)

4. **PirateShip**:
   - ✅ User has PirateShip account
   - ✅ Install PirateShip official WooCommerce plugin (prerequisite)

5. **Products**:
   - Location determined by tags: `SSAW-App` (warehouse), `available-in-store` (store)
   - No products created yet (will be migrated from Shopify)
   - Tags will be applied during migration to match Shopify tag structure

---

## Next Steps

1. ✅ **User input received**: Tag-based system, admin preference (Option B), PirateShip account confirmed
2. ✅ **Architect**: Plan updated for tag-based location system
3. **Prerequisites**: Install PirateShip official WooCommerce plugin
4. **Developer**: Begin Phase 1 development (6 files, ~790 lines)
5. **Tester**: Prepare test cases based on acceptance criteria

---

**Plan Status**: ✅✅ Architecture READY FOR IMPLEMENTATION
**Blockers**: None - All user input resolved via UI configuration
**Ready for**: Developer role - Phase 1 coding

---

**Document Version**: 4.0 (Tag-Based System)
**Previous Version**: 3.0 (Architecture Format - single location meta)
**Changes**:
- Replaced single location product meta with tag-based system (`SSAW-App`, `available-in-store`)
- Added overlap preference setting (Option B: admin chooses warehouse/store priority)
- Added default location setting (for products without tags)
- Removed product location dropdown (uses tags only)
- Added PirateShip plugin as prerequisite
- Updated all test cases for tag-based logic
- Reduced complexity: 3 meta keys (was 4), reuses existing tag system
