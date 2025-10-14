# Directory Map

## Epic Marks Shipping Plugin

**Path**: `wordpress/wp-content/plugins/epic-marks-shipping/`
**Version**: 1.0.0
**Total Lines**: 1,422 lines of PHP code

### File Structure

```
epic-marks-shipping/
├── epic-marks-shipping.php          (154 lines) - Main plugin file
├── includes/
│   ├── class-ups-api.php            (283 lines) - UPS Rating API integration
│   └── class-shipping-method.php    (336 lines) - WooCommerce shipping method
└── admin/
    ├── settings-page.php            (336 lines) - Plugin settings UI
    ├── product-meta.php             (146 lines) - Product-level shipping config
    └── order-meta-box.php           (167 lines) - PirateShip integration UI
```

### File Descriptions

#### Root Level
- **epic-marks-shipping.php**: Plugin bootstrap, handles initialization, WooCommerce dependency check, activation/deactivation hooks, HPOS compatibility declaration

#### includes/
- **class-ups-api.php**: UPS Rating API v1 client, handles API requests/responses, caching (30-min transients), test/production mode switching
- **class-shipping-method.php**: Core shipping logic extending WC_Shipping_Method, implements tag-based location grouping, multi-origin rate calculation, markup application, free shipping threshold

#### admin/
- **settings-page.php**: Settings page (WooCommerce > UPS Shipping), UPS credentials, warehouse/store addresses, service selection, fallback rates, cache management
- **product-meta.php**: Product-level meta fields in Shipping tab, tag-based location detection (SSAW-App/available-in-store), shipping markup configuration, weight validation warnings
- **order-meta-box.php**: Order page meta box, PirateShip deep link generator, UPS to PirateShip service mapping, shipping data extraction for HPOS compatibility

### Database Schema

**wp_options table**:
- `em_ups_settings` - Serialized array of plugin configuration
- `_transient_em_ups_rate_*` - Cached shipping rates (30-minute TTL)

**wp_postmeta table** (per product):
- `_em_enable_shipping_markup` - Enable/disable markup ('yes'/'no')
- `_em_markup_type` - Markup type ('percentage'/'flat')
- `_em_markup_value` - Markup amount (float)
- `_em_weight_warning` - Flag for products without weight

### Integration Points

- **WordPress**: Settings API, transient caching, meta data API, hooks/filters
- **WooCommerce**: Shipping method registration, product meta fields, order meta boxes, HPOS compatibility
- **UPS API**: Rating API v1 (JSON), OAuth authentication, test/production endpoints
- **PirateShip**: Deep link generation with pre-filled shipping data
- **WP Super Cache**: Cart/checkout page exclusion filter

### Dependencies

- **WordPress**: 5.8+
- **WooCommerce**: 6.0+ (tested up to 10.2)
- **PHP**: 8.0+
- **UPS Developer Account**: For API credentials
- **Product Tags**: 'SSAW-App' (warehouse), 'available-in-store' (store)
