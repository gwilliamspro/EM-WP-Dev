# Countdown Block — Dynamic Timer Architecture

**Date**: 2025-10-12
**Status**: Planning
**Architect**: Claude
**Feature**: Port Shopify countdown logic to WordPress Countdown Block

---

## 1. Scope: Files to Touch

1. **`wordpress/wp-content/plugins/epic-marks-blocks/epic-marks-blocks.php`**
   - Add WordPress admin settings page for countdown configuration
   - Add PHP render callback to inject JavaScript config from WP settings
   - Store settings in `wp_options` table

2. **`wordpress/wp-content/plugins/epic-marks-blocks/assets/blocks.js`**
   - Add block attributes for countdown configuration in editor
   - Add InspectorControls for cutoff time, holidays, messages
   - Update edit() preview to show live countdown

3. **`wordpress/wp-content/plugins/epic-marks-blocks/assets/countdown-timer.js`** (NEW)
   - Create standalone JavaScript module for countdown logic
   - Timezone-aware calculations using Intl.DateTimeFormat
   - Holiday/closed date detection algorithm
   - Next-open-business-day calculator
   - 1-second interval update loop

4. **`wordpress/wp-content/plugins/epic-marks-blocks/assets/blocks.css`**
   - Update countdown block styles for dynamic content
   - Add mobile-responsive text sizing

5. **`wordpress/wp-content/plugins/epic-marks-blocks/README.md`**
   - Document countdown configuration options
   - Add troubleshooting section for timezone issues
   - Include example holiday date formats

6. **`wordpress/wp-content/themes/kadence-child/functions.php`**
   - Register custom settings section in Customizer (optional alternative to admin page)

---

## 2. Risks & Unknowns

### High Priority
- **Browser timezone compatibility**: `Intl.DateTimeFormat` with timezone support requires modern browsers (IE11 incompatible)
  - **Mitigation**: Acceptable based on browser compatibility notes in README (Chrome 90+, Safari 14+)

- **Server vs client time drift**: User's system clock could be wrong
  - **Mitigation**: Use client-side Date() but document that countdown is client-time based

- **WordPress option storage limit**: Large holiday lists may hit character limits
  - **Mitigation**: Use textarea with newline/comma parsing (tested in Shopify version)

### Medium Priority
- **Block editor preview lag**: 1-second interval may slow Gutenberg editor
  - **Mitigation**: Disable timer in editor, show static preview only

- **Caching conflicts**: Countdown shows cached old time if page cached
  - **Mitigation**: JavaScript runs client-side, bypasses cache (server-side render shows placeholder)

### Low Priority
- **Holiday date parsing**: Admin may enter invalid YYYY-MM-DD formats
  - **Mitigation**: Add regex validation in settings page, show error messages

---

## 3. Dependencies

### WordPress Core
- **Settings API**: For admin configuration page (`add_options_page`, `register_setting`)
- **Localization**: `wp_enqueue_script` with localized data for passing PHP settings to JavaScript
- **Block Editor**: Gutenberg components (`InspectorControls`, `PanelBody`, `RangeControl`, `TextareaControl`)

### JavaScript APIs
- **Intl.DateTimeFormat**: For timezone conversion (America/Chicago)
- **Date API**: For countdown calculations and interval timers
- **Set**: For holiday/closed date lookups (ES6+)

### Theme Integration
- **Kadence Theme**: Optional CSS variable integration for colors
- **Header/Footer**: Countdown block typically placed in header group

### No External Dependencies
- No npm packages required (vanilla JS)
- No PHP libraries needed (uses core WP functions)
- No database schema changes (uses `wp_options`)

---

## 4. Acceptance Checks (For Tester)

### Functional Requirements
- [ ] Countdown displays "Order in Xh Ym Zs to ship today" before cutoff (e.g., 2:00 PM CT)
- [ ] Timer updates every second with accurate hours/minutes/seconds
- [ ] After cutoff, shows "Orders after 2 PM ship next business day (Mon Dec 16 by 2:00 PM CT)"
- [ ] On closed day (Sunday or holiday), shows "Closed today — orders process Mon Dec 16 by 2:00 PM CT"
- [ ] Next-open-day calculation skips Sundays, holidays, and extra closed dates correctly
- [ ] Temporary override toggle displays custom "Closed" message immediately

### Configuration Requirements
- [ ] WordPress admin settings page at "Settings → Countdown Timer"
- [ ] Cutoff hour/minute sliders (0-23 hours, 0-59 minutes)
- [ ] Textarea accepts holidays in YYYY-MM-DD format (comma or newline separated)
- [ ] Textarea accepts extra closed dates in YYYY-MM-DD format
- [ ] Three message templates accept {time}, {date}, {cutoff} template variables
- [ ] Temporary override checkbox with custom message field
- [ ] Settings save successfully and persist across page reloads

### Block Editor Requirements
- [ ] Countdown block appears in Gutenberg inserter under "Widgets" category
- [ ] Block preview shows static countdown text (not live timer to avoid editor lag)
- [ ] Inspector panel shows "Countdown Settings" with link to admin settings page
- [ ] Block attributes sync with global WordPress settings
- [ ] Block can be inserted multiple times on same page (unique IDs)

### Timezone Requirements
- [ ] All times displayed in America/Chicago timezone (Central Time)
- [ ] Countdown correctly handles CST ↔ CDT daylight saving transitions
- [ ] "CT" suffix appears on all formatted times (e.g., "2:00 PM CT")
- [ ] Countdown works correctly for users in other timezones (shows CT, not local time)

### Holiday Detection Requirements
- [ ] Default holidays configured: Jan 1, Jul 4, Nov 27 (Thanksgiving), Dec 25
- [ ] Custom holidays in YYYY-MM-DD format (e.g., "2025-12-26") work correctly
- [ ] Invalid date formats are ignored (with console warning)
- [ ] Holiday dates persist year-to-year (admin must update annually)

### Edge Cases
- [ ] Countdown at 23:59:59 transitions correctly to next day
- [ ] Countdown on Dec 31 transitions correctly to Jan 1
- [ ] Countdown during DST transition (spring forward, fall back) shows correct times
- [ ] Countdown with empty holiday list works (no crashes)
- [ ] Countdown with 366+ consecutive closed days doesn't infinite loop (returns start date)
- [ ] Multiple countdown blocks on same page show identical times
- [ ] Countdown works on mobile browsers (iOS Safari, Chrome Android)

### Performance Requirements
- [ ] JavaScript file size under 10KB (minified)
- [ ] setInterval at 1-second does not cause memory leaks (test 10+ minute runtime)
- [ ] Block editor does not lag with countdown block inserted
- [ ] Frontend page load time impact under 50ms (cached JS)

### Accessibility Requirements
- [ ] Countdown text is screen-reader accessible (not aria-hidden)
- [ ] Countdown container has semantic HTML (not just divs)
- [ ] Text contrast ratio meets WCAG AA (white text on Steel Blue #627A94)
- [ ] Countdown text size adjustable via WordPress settings (14-28px range)

### Browser Compatibility
- [ ] Chrome 90+ (desktop/mobile) - full support
- [ ] Firefox 88+ (desktop/mobile) - full support
- [ ] Safari 14+ (desktop/mobile) - full support
- [ ] Edge 90+ - full support
- [ ] IE 11 - graceful degradation (show static message, no countdown)

---

## 5. Migration from Shopify Code

### Direct Ports (Copy/Paste)
- Timezone calculation functions: `partsFromDate()`, `dateFrom()`, `iso()`
- Holiday detection: `closedOn()`, `parseList()`
- Next-open-day algorithm: `nextOpen()`
- Date formatting: `fmtDate()`, `fmtTime()`
- Main timer loop: `tick()` function

### Shopify → WordPress Conversions
| Shopify Liquid | WordPress Equivalent |
|----------------|----------------------|
| `{{ section.settings.cutoff_hour }}` | `get_option('em_countdown_cutoff_hour')` |
| `{{ section.settings.holidays \| json }}` | `wp_json_encode(get_option('em_countdown_holidays'))` |
| `<script>window.CONFIG = {...}</script>` | `wp_localize_script('em-countdown-timer', 'EM_COUNTDOWN_CONFIG', [...])` |
| Liquid schema settings | WordPress Settings API (`register_setting`) |
| Section presets | Block template in `registerBlockType` |

### New WordPress-Specific Code
- Admin settings page with `add_options_page()`
- Settings validation with `sanitize_text_field()`, `sanitize_textarea_field()`
- Block registration with `InspectorControls` UI
- Localized script data with `wp_localize_script()`

---

## 6. Implementation Order (For Implementer)

### Phase 1: Backend Settings (30 min)
1. Create admin settings page in `epic-marks-blocks.php`
2. Register 10 settings fields (cutoff hour/minute, holidays, messages, override)
3. Add settings page under "Settings → Countdown Timer"
4. Test settings save/load in WordPress admin

### Phase 2: JavaScript Timer Logic (45 min)
1. Create `countdown-timer.js` with Shopify functions (timezone, holiday, next-open)
2. Port `tick()` function with message templates
3. Enqueue script and localize settings data
4. Test countdown in browser console with manual config

### Phase 3: Block Integration (30 min)
1. Update `blocks.js` with countdown block attributes
2. Add InspectorControls with link to settings page
3. Update PHP render callback to inject script + config
4. Test block insertion and preview in Gutenberg

### Phase 4: Styling & Polish (15 min)
1. Update `blocks.css` with responsive styles
2. Add mobile text sizing adjustments
3. Test on multiple screen sizes

### Phase 5: Documentation (15 min)
1. Update `README.md` with countdown configuration guide
2. Add troubleshooting section for timezone issues
3. Document holiday date formats and examples

**Total Estimated Time**: 2.25 hours

---

## 7. Testing Strategy (For Tester)

### Unit Tests (Manual)
- Test `nextOpen()` with consecutive closed days
- Test `closedOn()` with Sundays, holidays, extra dates
- Test `partsFromDate()` with DST transitions
- Test template variable replacement in messages

### Integration Tests
- Insert countdown block on test page
- Configure cutoff as 14:00 (2 PM)
- Add current date to holidays list
- Verify "Closed today" message displays
- Remove holiday, verify countdown shows before cutoff
- Wait until after cutoff, verify "next business day" message

### Cross-Browser Tests
- Test on Chrome (desktop + mobile)
- Test on Safari (desktop + iOS)
- Test on Firefox (desktop)
- Test on Edge (desktop)
- Verify IE11 shows static fallback message

### Performance Tests
- Load page with countdown block 10 times, measure avg load time
- Keep page open for 10 minutes, check memory usage in DevTools
- Open Gutenberg editor with 5 countdown blocks, verify no lag

---

## 8. Rollback Plan

If countdown implementation fails or causes issues:

1. **Revert plugin file**: `git checkout HEAD wordpress/wp-content/plugins/epic-marks-blocks/epic-marks-blocks.php`
2. **Delete new JS file**: `rm wordpress/wp-content/plugins/epic-marks-blocks/assets/countdown-timer.js`
3. **Deactivate block**: Edit `epic-marks-blocks.php`, comment out `register_block_type('epic-marks/countdown-block')`
4. **Remove from pages**: Use WordPress admin to delete countdown blocks from pages
5. **Clear cache**: `wp cache flush --allow-root`

Original static countdown block remains functional if dynamic timer is removed.

---

## 9. Post-Launch Monitoring

### Week 1
- Monitor browser console errors on production site
- Check countdown accuracy at cutoff time (2:00 PM CT daily)
- Verify holiday detection on next Sunday
- Review Google Analytics for bounce rate changes

### Week 2
- Collect user feedback on countdown messaging
- Test countdown during next DST transition
- Review server logs for JavaScript errors

### Week 3
- Document any edge cases discovered
- Update holiday list for next year
- Create admin documentation for non-technical users

---

**End of Architecture Document**

**Next Step**: Hand off to Implementer with this plan and begin Phase 1 (Backend Settings).
