# Countdown Implementation — Phase 1: Backend Settings

**Date**: 2025-10-12
**Role**: Implementer
**Architecture**: See `/ops/ai/scratch/countdown-architecture.md`
**Estimated Time**: 30 minutes

---

## Objective

Create WordPress admin settings page for countdown timer configuration. Settings will be stored in `wp_options` table and consumed by JavaScript countdown logic.

---

## Architecture Reference

From countdown-architecture.md Section 1, Scope Item #1:
> **`wordpress/wp-content/plugins/epic-marks-blocks/epic-marks-blocks.php`**
> - Add WordPress admin settings page for countdown configuration
> - Add PHP render callback to inject JavaScript config from WP settings
> - Store settings in `wp_options` table

---

## Tasks

### 1. Add Settings Page Registration
- Hook: `add_action('admin_menu', ...)`
- Function: `add_options_page()` under "Settings" menu
- Page title: "Countdown Timer Settings"
- Capability: `manage_options`

### 2. Register 10 Settings Fields
Use WordPress Settings API (`register_setting`, `add_settings_section`, `add_settings_field`):

**Settings to Register:**
1. `em_countdown_cutoff_hour` (integer, 0-23, default: 14)
2. `em_countdown_cutoff_minute` (integer, 0-59, default: 0)
3. `em_countdown_close_sunday` (boolean, default: true)
4. `em_countdown_holidays` (textarea, default: "2025-01-01, 2025-07-04, 2025-11-27, 2025-12-25")
5. `em_countdown_extra_closed` (textarea, default: "")
6. `em_countdown_msg_active` (text, default: "Order in {time} to ship today (by {cutoff}).")
7. `em_countdown_msg_after` (text, default: "Orders after 2 PM ship next business day ({date} by {time}).")
8. `em_countdown_msg_closed` (text, default: "Closed today — orders process {date} by {time}.")
9. `em_countdown_override` (boolean, default: false)
10. `em_countdown_override_msg` (text, default: "Temporarily closed — orders process next business day.")

### 3. Add Settings Page HTML
- Form with `settings_fields()` and `do_settings_sections()`
- Submit button with `submit_button()`
- Help text for each field explaining format

### 4. Sanitization Callbacks
- Integer fields: `absint()`
- Boolean fields: `(bool)` cast
- Text fields: `sanitize_text_field()`
- Textarea fields: `sanitize_textarea_field()`

### 5. Update Countdown Block Render Callback
Modify existing `render_countdown_block()` function:
- Fetch settings from `get_option()`
- Build JavaScript config object
- Inject `<script>` tag with `window.EM_COUNTDOWN_CONFIG = {...}`
- Keep existing HTML structure for now (Phase 2 will add timer logic)

---

## Files to Modify

1. **`wordpress/wp-content/plugins/epic-marks-blocks/epic-marks-blocks.php`**
   - Add settings page methods in `Epic_Marks_Blocks` class
   - Update `render_countdown_block()` to inject config

**No other files modified in Phase 1.**

---

## Code Guidelines

- Follow WordPress Coding Standards
- Use `esc_html()`, `esc_attr()` for all output
- Use `wp_kses_post()` for textarea output
- Add inline documentation for each setting
- Keep settings page clean and well-organized
- Add descriptive labels and help text

---

## Testing Checklist (Phase 1)

- [ ] Settings page appears at **Settings → Countdown Timer**
- [ ] All 10 settings fields render correctly
- [ ] Default values populate on first load
- [ ] Settings save successfully (check `wp_options` table)
- [ ] Settings persist after page reload
- [ ] Sanitization prevents invalid data (test negative numbers, HTML injection)
- [ ] Countdown block render callback includes `<script>` with config
- [ ] `window.EM_COUNTDOWN_CONFIG` accessible in browser console

---

## WP-CLI Verification Commands

```bash
# Check if settings are registered
sudo docker exec wordpress_app wp option list --search="em_countdown_*" --allow-root

# View specific setting
sudo docker exec wordpress_app wp option get em_countdown_cutoff_hour --allow-root

# Manually set setting (testing)
sudo docker exec wordpress_app wp option update em_countdown_cutoff_hour 15 --allow-root

# Delete all countdown settings (reset)
sudo docker exec wordpress_app wp option delete em_countdown_cutoff_hour --allow-root
```

---

## Expected Outputs

After Phase 1 completion:

1. **New admin page**: `https://dev.epicmarks.com/wp-admin/options-general.php?page=em-countdown-settings`
2. **Settings in database**: 10 new rows in `wp_options` table with `option_name` prefix `em_countdown_*`
3. **JavaScript config**: View page source, see `<script>window.EM_COUNTDOWN_CONFIG = {...}</script>` in countdown block
4. **No visual changes**: Countdown still shows static default text (timer logic comes in Phase 2)

---

## Acceptance Criteria (From Architecture Doc)

From Section 4, "Configuration Requirements":
- [x] WordPress admin settings page at "Settings → Countdown Timer"
- [x] Cutoff hour/minute sliders (0-23 hours, 0-59 minutes)
- [x] Textarea accepts holidays in YYYY-MM-DD format (comma or newline separated)
- [x] Textarea accepts extra closed dates in YYYY-MM-DD format
- [x] Three message templates accept {time}, {date}, {cutoff} template variables
- [x] Temporary override checkbox with custom message field
- [x] Settings save successfully and persist across page reloads

---

## Next Phase

After Phase 1 complete, hand off to Phase 2:
- Create `countdown-timer.js` with Shopify logic port
- Implement tick() function and 1-second interval
- Connect JavaScript to `EM_COUNTDOWN_CONFIG`

---

**End of Phase 1 Task Document**
