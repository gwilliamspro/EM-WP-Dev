# Epic Marks Shipping Plugin - Changelog

## Version 2.0.0 - 2025-10-15

### What Changed
- Transformed plugin from basic UPS rate calculator into comprehensive shipping management system
- Added shipping profiles system with flexible product assignment and zone configuration
- Implemented multi-location fulfillment with package splitting (warehouse + in-store pickup)
- Added Ship-to-Store functionality with configurable margins to cover supplier costs
- Built tabbed admin UI (Setup, Profiles, Routing, Package Control, Reports) for better organization
- Added comprehensive reporting with PirateShip integration for cost analysis and fulfillment tracking

### Why
- Simplify management of 30k+ dropship products requiring different shipping rules
- Enable customers to combine warehouse shipping with in-store pickup in single order
- Provide accurate Ship-to-Store pricing (UPS base rate + margin) instead of misleading free shipping
- Replace confusing tag-based routing with explicit profile assignments
- Give admins visibility into shipping costs, fulfillment locations, and service performance
- Support future scaling with zone-based rules and automated product routing

### Technical Details
- Plugin version: 1.0.0 → 2.0.0
- Files added: 20 new files (includes/, admin/, assets/, templates/)
- Files modified: 5 core files (epic-marks-shipping.php, class-shipping-method.php, etc.)
- Database: 1 new option (em_shipping_profiles), 7 new product/order meta keys
- Backward compatible: Tag-based routing preserved as fallback for products without profiles
- Migration: Automatic one-time assignment of SSAW-App tagged products to profiles

### Breaking Changes
None - fully backward compatible. Products without profile assignment fall back to tag-based routing.

---

## Version 1.0.0 - 2025-01-15 (Initial Release)

### Features
- UPS API integration for real-time shipping rates
- Tag-based product routing (SSAW-App → warehouse, available-in-store → store)
- Product-specific shipping markup configuration
- PirateShip deep link integration for label purchase
- Basic admin settings page with UPS credentials and addresses

