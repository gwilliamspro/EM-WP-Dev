# Invariants - Rules and Conventions

## Epic Marks Shipping Plugin Rules

### Security Rules (MUST FOLLOW)

1. **Direct Access Protection**: Every PHP file MUST include `if ( ! defined( 'ABSPATH' ) ) { exit; }` at the top
2. **Input Sanitization**: ALL user input MUST be sanitized:
   - Text fields: `sanitize_text_field()`
   - Numeric fields: `floatval()` or `absint()`
   - Arrays: `array_map('sanitize_text_field', $array)`
3. **Output Escaping**: ALL HTML output MUST be escaped:
   - Text: `esc_html()`, `esc_html_e()`, `esc_html__()`
   - Attributes: `esc_attr()`
   - URLs: `esc_url()`
4. **Capability Checks**: Admin pages MUST check `current_user_can('manage_woocommerce')`
5. **No Raw SQL**: Use WordPress/WooCommerce data APIs (exception: safe transient cleanup with no user input)

### WordPress Coding Standards

1. **Naming Conventions**:
   - Functions: `em_` prefix (e.g., `em_shipping_check_woocommerce()`)
   - Classes: `EM_` prefix (e.g., `EM_UPS_API`)
   - Constants: `EM_SHIPPING_` prefix (e.g., `EM_SHIPPING_VERSION`)
   - Options: `em_ups_` prefix (e.g., `em_ups_settings`)
   - Meta keys: `_em_` prefix (e.g., `_em_enable_shipping_markup`)
   - Transients: `em_ups_rate_` prefix

2. **Internationalization**:
   - Text domain: `'epic-marks-shipping'` (used consistently across all files)
   - Always use `__()`, `esc_html__()`, `esc_html_e()` for translatable strings

3. **Hook Usage**:
   - Use WordPress actions and filters (no direct function calls)
   - Check hook priority when multiple plugins may interact

### Tag-Based Location Rules

**CRITICAL**: Product shipping location is determined by product tags:

1. **Tag Detection Logic** (MUST be consistent across all files):
   - Has `SSAW-App` tag → warehouse
   - Has `available-in-store` tag → store
   - Both tags present → use `overlap_preference` setting (warehouse/store)
   - No tags present → use `default_location` setting (warehouse/store)

2. **Tag Names** (MUST match exactly):
   - Warehouse: `'SSAW-App'` (case-sensitive)
   - Store: `'available-in-store'` (case-sensitive)

3. **Location Resolution** (implemented in 3 places - MUST stay synchronized):
   - `includes/class-shipping-method.php:determine_location()`
   - `admin/product-meta.php:em_product_shipping_tab_content()`
   - Order processing: Uses same tag detection logic

### Weight Rules

1. **Default Weight**: Products without weight default to **1 lb** (prevents API errors)
2. **Weight Warning**: Products without weight get `_em_weight_warning` meta flag
3. **Weight Validation**: Display admin notice for products without weight in Shipping tab

### Multi-Origin Shipping Rules

1. **Grouping**: Cart items grouped by location (warehouse/store) based on tags
2. **Rate Calculation**: Separate UPS API call for each location
3. **Rate Combination Strategies**:
   - `'highest'`: Use highest rate (default, conservative)
   - `'sum'`: Add all rates together

4. **Service Availability**: Each origin calculates rates for enabled services independently

### Caching Rules

1. **Cache Duration**: 30 minutes (`30 * MINUTE_IN_SECONDS`)
2. **Cache Key**: MD5 hash of `origin_zip + destination_zip + weight`
3. **Cache Storage**: WordPress transients (`_transient_em_ups_rate_*`)
4. **Cache Invalidation**: Manual "Clear Cache" button + transient expiration
5. **Cache Cleanup**: Deactivation hook deletes all plugin transients

### UPS API Rules

1. **Endpoints**:
   - Production: `https://onlinetools.ups.com/ship/v1/rating/Rate`
   - Sandbox: `https://wwwcie.ups.com/ship/v1/rating/Rate`

2. **Test Mode**: Use `test_mode` setting to switch endpoints (DO NOT hardcode)
3. **Authentication**: OAuth headers (AccessLicenseNumber, Username, Password)
4. **Error Handling**: MUST return `WP_Error` on failures, check for API error responses
5. **Rate Parsing**: Extract `MonetaryValue` from `RatedShipment` array

### Service Code Mappings

**UPS Service Codes** (MUST stay synchronized):
- `'03'` → UPS Ground → `'ups_ground'` (PirateShip)
- `'02'` → UPS 2nd Day Air → `'ups_2day'` (PirateShip)
- `'01'` → UPS Next Day Air → `'ups_next_day'` (PirateShip)
- `'12'` → UPS 3 Day Select → `'ups_3day'` (PirateShip)

### PirateShip Integration Rules

1. **Base URL**: `https://ship.pirateship.com/ship`
2. **Deep Link Parameters**: Use `add_query_arg()` for URL building
3. **Required Fields**: to_name, to_street1, to_city, to_state, to_zip, to_country, weight_lb, service
4. **Order Meta Box**: Display for both traditional posts and HPOS orders

### WooCommerce HPOS Compatibility

1. **Order Retrieval**: ALWAYS use `wc_get_order($order_id)` (NEVER `get_post()`)
2. **Order Meta**: Use `$order->get_meta()` / `$order->update_meta_data()` (not `get_post_meta()`)
3. **Compatibility Declaration**: MUST declare compatibility in `before_woocommerce_init` hook
4. **Meta Box Registration**: Register for BOTH `'shop_order'` and `'woocommerce_page_wc-orders'`

### Performance Standards

1. **Lazy Loading**: Classes loaded only when needed (not all at once)
2. **Minimal Queries**: Use transient caching to reduce API calls
3. **No N+1 Queries**: Batch operations where possible
4. **Cache Exclusions**: Cart/checkout MUST be excluded from WP Super Cache

### Debug Logging

1. **Condition**: Only log when `WP_DEBUG` is enabled
2. **Function**: Use `error_log()` for debug output
3. **Location**: UPS API requests/responses logged in `class-ups-api.php`

### Free Shipping Rules

1. **Threshold Check**: Compare cart subtotal to `free_shipping_threshold` setting
2. **Application**: Set cost to 0 when threshold met (applies to all services)
3. **Disabled**: If threshold is 0 or empty, free shipping is disabled

### Markup Rules

1. **Per-Product**: Markup configured at product level (not global)
2. **Types**:
   - `'percentage'`: `cost += (cost * (value / 100))`
   - `'flat'`: `cost += value`
3. **Application**: Applied AFTER UPS rate calculation
4. **Multi-Origin**: Each origin's rate marked up independently

### Fallback Rate Rules

1. **Trigger**: Used when UPS API fails or credentials missing
2. **Configuration**: Set in plugin settings (ground, 2day, nextday)
3. **Display**: Show service name with "(Estimated)" suffix
4. **Production Use**: Should configure real fallback rates (not defaults)

### File Permission Standards

1. **Directories**: 755 (`drwxr-xr-x`)
2. **PHP Files**: 644 (`-rw-r--r-`)
3. **Owner**: `www-data:www-data` (WordPress user)

### Code Quality Rules

1. **PHP Version**: Minimum PHP 8.0 (use type hints where applicable)
2. **WordPress Version**: Minimum 5.8
3. **WooCommerce Version**: Minimum 6.0, tested up to 10.2
4. **No PHP Warnings**: All code must pass `php -l` syntax check
5. **No PHP Notices**: Code must run cleanly with `WP_DEBUG` enabled
