# Epic Marks Shipping Plugin - Changelog

## Version 2.3.1 - 2025-10-16

### What Changed
- Removed deprecated "Free Shipping Threshold" field from Setup tab (replaced by Rules system)
- Removed "Default UPS Services" field from Setup tab (migration-only, duplicated in Locations)
- Simplified Setup tab from 193 to 153 lines (21% reduction)

### Why
- Eliminates admin confusion between deprecated threshold and new Rules tab
- Removes settings that appear functional but have no effect on new locations
- Clearer separation: Setup = global UPS config, Locations = per-location services
- Streamlines admin experience by removing 40 lines of obsolete UI

---

## Version 2.3.0 - 2025-10-16

### What Changed
- Delivery date estimation with business day calculator (skips weekends and holidays)
- Transparent fee line items (fragile handling, overweight, signature required)
- Cutoff time aware processing (orders after 2 PM ship next business day)
- Holiday calendar integration (store-specific closures + carrier holidays)

### Why
- Customers need delivery expectations at checkout to plan accordingly
- Transparent fees build trust vs hidden markups (compliance, customer satisfaction)
- Accurate delivery windows reduce "where's my order?" support tickets
- Holiday-aware estimates prevent customer disappointment during peak seasons

---

## Version 2.2.0 - 2025-10-16

### What Changed
- Intelligent order routing: calculate ship-together vs split-shipment costs
- Customer choice at checkout with recommended option pre-selected
- Cost comparison display shows savings for recommended routing
- Order meta tracking for routing selection and fulfillment breakdown

### Why
- Mixed carts (store + warehouse items) previously forced split shipments
- Ship-together option saves customer money when cost-effective
- Recommended badge guides customers to best value without forcing choice
- Routing analytics help optimize fulfillment strategy over time

---

## Version 2.1.0 - 2025-10-16

### What Changed
- Dynamic location management system (unlimited locations vs 2 hardcoded)
- SSAW multi-warehouse network with cheapest-warehouse selection
- Conditional free shipping rules engine (profile + order total + tags)
- Box configuration with dimensional weight calculation
- CSV/JSON import for SSAW warehouse addresses

### Why
- 3rd location (South Austin store) launch blocked by 2-location limit
- SSAW ships from multiple warehouses - need accurate origin for UPS rates
- Business requires different free shipping rules per product type (SSAW >$300, DTF >$50)
- Dimensional weight critical for accurate rates (DTF rolls, large boxes)
- Admin needs scalable system as business adds warehouses/stores

---

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
