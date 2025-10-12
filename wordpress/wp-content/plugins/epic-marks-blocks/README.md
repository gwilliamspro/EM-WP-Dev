# Epic Marks Custom Blocks

Custom Gutenberg blocks for the Epic Marks WordPress site, built to match the brand's design system and provide specialized e-commerce functionality.

## Overview

This plugin provides three custom Gutenberg blocks designed specifically for Epic Marks:
- **Wave Block**: Animated SVG wave dividers with customizable styling
- **Countdown Block**: Full-width urgency banner for same-day shipping deadlines
- **USP Block**: Unique Selling Point feature blocks with icon/emoji support

All blocks are server-side rendered for optimal performance and include comprehensive styling controls via the WordPress block editor.

## Architecture

### Plugin Structure
```
epic-marks-blocks/
├── epic-marks-blocks.php    # Main plugin file (v1.0.8)
├── assets/
│   ├── blocks.js            # Block registration and editor UI (642 lines)
│   ├── blocks.css           # Frontend and editor styles (87 lines)
│   └── countdown-timer.js   # Countdown timer logic (187 lines, 5.2 KB)
└── README.md                # This documentation
```

### Technical Implementation
- **Block Registration**: PHP-based with `register_block_type()` and server-side rendering
- **Editor Interface**: React-based using WordPress Gutenberg components
- **Styling System**: Inline styles for dynamic attributes, CSS classes for static styles
- **Color Picker**: Integrated with WordPress theme color palette
- **Dynamic Countdown**: Real-time JavaScript timer with timezone-aware calculations
- **Version**: 1.0.8

## Block Reference

### 1. Wave Block (`epic-marks/wave-block`)

Animated SVG wave dividers for visual section transitions. Commonly used between hero sections and content areas.

**Attributes:**
- `waveHeight`: Desktop wave height in pixels (default: 80)
- `waveHeightMobile`: Mobile wave height in pixels (default: 60)
- `waveColor`: Wave fill color (default: #627a94 - Steel Blue)
- `backgroundColor`: Container background color (default: transparent)
- `bottomSectionHeight`: Solid section below waves in pixels (default: 100)
- `animate`: Enable wave animation (default: true)
- `rotate`: Rotate waves 180° for upward effect (default: false)

**Usage Example:**
```html
<!-- Waves transitioning from white to Steel Blue -->
waveColor: #627a94
backgroundColor: #FFFFFF
waveHeight: 120
bottomSectionHeight: 80
animate: true
rotate: false
```

**Technical Notes:**
- Full viewport width using CSS `100vw` technique
- 4 layered SVG waves with opacity variations (0.1, 0.2, 0.3, 0.4)
- Smooth cubic-bezier animations at different speeds (4s, 6s, 8s, 10s)
- Unique keyframe IDs prevent animation conflicts when multiple instances exist
- Responsive with separate mobile/desktop heights

**Common Use Cases:**
- Homepage hero to content transition
- Section dividers with color changes
- Footer visual separation
- Product category headers

---

### 2. Countdown Block (`epic-marks/countdown-block`)

Dynamic countdown timer with live updates for same-day shipping deadlines and promotional messages. Now with real-time countdown logic that updates every second!

**Attributes (Styling Only):**
- `countdownText`: Initial placeholder text (overridden by live countdown)
- `backgroundColor`: Banner background color (default: #627a94 - Steel Blue)
- `textColor`: Text color (default: #ffffff - White)
- `textSize`: Font size in pixels (default: 18)
- `paddingTop`: Top padding in pixels (default: 12)
- `paddingBottom`: Bottom padding in pixels (default: 12)

**Note:** Block attributes control styling only. Countdown logic and messages are configured via **Settings → Countdown Timer** in WordPress admin.

**Usage Example:**
```html
<!-- Epic Marks same-day shipping banner -->
countdownText: "Order by 2:00 PM CT for same-day shipping!"
backgroundColor: #627a94
textColor: #ffffff
textSize: 18
paddingTop: 12
paddingBottom: 12
```

**Technical Notes:**
- Full viewport width using breakout technique
- Bold 700 weight with Inter font family
- Text center-aligned for optimal readability
- Unique ID per instance for JavaScript targeting (future countdown feature)
- 1rem horizontal padding for mobile safety

---

### Countdown Block Configuration

The countdown timer is configured via **WordPress Admin → Settings → Countdown Timer**. All countdown blocks on your site use these global settings.

#### Cutoff Time Settings

- **Cutoff Hour** (0-23): Hour of day for same-day cutoff (default: 14 = 2:00 PM)
- **Cutoff Minute** (0-59): Minute of hour (default: 0 = :00)
- **Close on Sundays**: Toggle to close business on Sundays (default: enabled)

#### Closed Dates

- **US Holidays**: Enter dates in YYYY-MM-DD format (comma or newline separated)
  - Default: `2025-01-01, 2025-07-04, 2025-11-27, 2025-12-25`
- **Additional Closed Dates**: Extra dates for vacations, company events, etc.

#### Message Templates

Three message templates with template variables:

1. **Before Cutoff Message**: `{time}` = countdown timer, `{cutoff}` = cutoff time
   - Default: `"Order in {time} to ship today (by {cutoff})."`

2. **After Cutoff Message**: `{date}` = next open date, `{time}` = cutoff time
   - Default: `"Orders after 2 PM ship next business day ({date} by {time})."`

3. **Closed Today Message**: `{date}` = next open date, `{time}` = cutoff time
   - Default: `"Closed today — orders process {date} by {time}."`

#### Temporary Override

- **Temporarily Closed**: Emergency toggle to show custom closure message
- **Override Message**: Custom message when temporarily closed
  - Default: `"Temporarily closed — orders process next business day."`

**Template Variables:**
- `{time}` - Dynamic countdown (e.g., "2h 15m 30s") or cutoff time (e.g., "2:00 PM CT")
- `{date}` - Next open business day (e.g., "Mon Dec 16")
- `{cutoff}` - Cutoff time (e.g., "2:00 PM CT")

---

### How Countdown Works

The countdown timer has three states that update automatically based on current time and business rules:

#### State 1: Before Cutoff (Active Countdown)
**When**: Current time is before cutoff AND today is an open business day
**Display**: Live countdown timer updating every second
**Example**: `"Order in 2h 15m 30s to ship today (by 2:00 PM CT)."`

#### State 2: After Cutoff
**When**: Current time is after cutoff AND today is an open business day
**Display**: Static message showing next open business day
**Example**: `"Orders after 2 PM ship next business day (Mon Dec 16 by 2:00 PM CT)."`

#### State 3: Closed Today
**When**: Today is Sunday, holiday, or extra closed date
**Display**: Static message showing next open business day
**Example**: `"Closed today — orders process Mon Dec 16 by 2:00 PM CT."`

#### Timezone
All times are calculated and displayed in **America/Chicago timezone (Central Time)**. The countdown automatically adjusts for users in different timezones to show accurate Central Time.

#### Live Updates
The countdown timer updates every 1 second using JavaScript. Multiple countdown blocks on the same page display identical messages for consistency.

#### Next-Open-Business-Day Calculation
The countdown automatically skips:
- Sundays (if "Close on Sundays" is enabled)
- US Holiday dates from settings
- Additional closed dates from settings

The algorithm looks up to 366 days ahead to find the next open business day.

**Common Use Cases:**
- Same-day shipping deadlines
- Flash sale countdowns
- Holiday cutoff notifications
- Promotional announcements

---

### Configuration Examples

#### Example 1: 2 PM Central Time Cutoff (Default)
Navigate to **Settings → Countdown Timer** and configure:
- **Cutoff Hour**: 14
- **Cutoff Minute**: 0
- **Before Cutoff Message**: `"Order in {time} to ship today (by {cutoff})."`
- **After Cutoff Message**: `"Orders after 2 PM ship next business day ({date} by {time})."`

**Result**: Countdown shows hours/minutes/seconds until 2:00 PM CT. After 2 PM, shows next business day message.

#### Example 2: Holiday Closure (Christmas)
Navigate to **Settings → Countdown Timer** and configure:
- **US Holidays**: Add `2025-12-25` to the list
- **Closed Today Message**: `"Closed today — orders process {date} by {time}."`

**Result**: On December 25th, countdown shows "Closed today — orders process Mon Dec 26 by 2:00 PM CT."

#### Example 3: Extended Weekend (Closed Friday-Monday)
Navigate to **Settings → Countdown Timer** and configure:
- **Close on Sundays**: Enabled (default)
- **Additional Closed Dates**: Add `2025-12-26, 2025-12-27, 2025-12-29` (Fri, Sat, Mon)

**Result**: On these dates, countdown skips to next open business day (Tuesday Dec 30).

#### Example 4: Emergency Closure (Weather, etc.)
Navigate to **Settings → Countdown Timer** and configure:
- **Temporarily Closed**: Check the box
- **Override Message**: `"Due to weather, orders will process Monday by 2:00 PM CT."`

**Result**: All countdown blocks immediately show override message. Timer stops updating. Uncheck box to restore normal countdown.

#### Example 5: 3 PM Cutoff (Non-Standard)
Navigate to **Settings → Countdown Timer** and configure:
- **Cutoff Hour**: 15
- **Cutoff Minute**: 0
- **Before Cutoff Message**: `"Order in {time} for same-day shipping (by {cutoff})!"`

**Result**: Countdown runs until 3:00 PM CT instead of default 2:00 PM.

#### Testing Configuration with WP-CLI
```bash
# View current cutoff hour
sudo docker exec wordpress_app wp option get em_countdown_cutoff_hour --allow-root

# Change cutoff to 3:00 PM (15)
sudo docker exec wordpress_app wp option update em_countdown_cutoff_hour 15 --allow-root

# Add a test holiday (today's date)
sudo docker exec wordpress_app wp option update em_countdown_holidays "2025-10-12" --allow-root

# Enable temporary override
sudo docker exec wordpress_app wp option update em_countdown_override 1 --allow-root

# Disable temporary override
sudo docker exec wordpress_app wp option update em_countdown_override 0 --allow-root

# Flush cache after changes
sudo docker exec wordpress_app wp cache flush --allow-root
```

---

### 3. USP Block (`epic-marks/usp-block`)

Individual Unique Selling Point feature blocks with icon/emoji support and advanced styling controls.

**Attributes:**
- `icon`: Emoji or icon character (e.g., "🚀", "✓", "📦")
- `title`: Feature title text (default: "Feature Title")
- `iconSize`: Icon font size in pixels (default: 32)
- `titleSize`: Title font size in pixels (default: 16)
- `textColor`: Text and icon color (default: #454C57 - Slate Gray)
- `backgroundColor`: Block background color (default: transparent)
- `padding`: Internal padding in pixels (default: 16)
- `borderRadius`: Corner radius in pixels (default: 0)
- `boxShadow`: Drop shadow toggle (default: false)

**Advanced Features:**
- **Copy/Paste Styles**: Toolbar buttons to copy block styles and paste to other USP blocks
- **Theme Color Palette**: Color picker integrated with WordPress theme colors
- **Auto-centering**: Horizontal layout with 180px fixed width for consistent text wrapping
- **Vertical Spacing**: Dynamic spacing for text-only blocks (no icon)

**Usage Example:**
```html
<!-- White card with shadow for product features -->
icon: "⚡"
title: "SAME-DAY PRINTING"
iconSize: 36
titleSize: 14
textColor: #454C57
backgroundColor: #FFFFFF
padding: 24
borderRadius: 8
boxShadow: true
```

**Copy/Paste Styles Workflow:**
1. Design one USP block with desired styles
2. Click **Copy Styles** button in toolbar
3. Select another USP block
4. Click **Paste Styles** button
5. All styling attributes transfer instantly (icon and title text remain unique)

**Technical Notes:**
- Inline-block layout for horizontal auto-centering
- CSS `:has()` selectors for conditional spacing
- Server-side rendering for performance
- Style clipboard stored in JavaScript memory (per-session)
- WordPress notices API for user feedback

**Common Use Cases:**
- Homepage feature grids (3-4 columns)
- Product page benefits
- Service highlights
- Trust indicators

**Typical USP Grid:**
```
[Row with 3 USP Blocks]
📦 SAME-DAY SHIPPING    |    ✓ PRO QUALITY    |    🎨 CUSTOM DESIGN
```

---

## Installation & Activation

### Manual Installation
1. Upload `epic-marks-blocks/` directory to `/wp-content/plugins/`
2. Navigate to **Plugins → Installed Plugins** in WordPress admin
3. Click **Activate** on "Epic Marks Custom Blocks"

### Verification
```bash
# Check if plugin is active
sudo docker exec wordpress_app wp plugin list --allow-root | grep epic-marks

# Expected output:
# epic-marks-blocks    1.0.6    active    Epic Marks Custom Blocks
```

### Deactivation
```bash
# Via WP-CLI
sudo docker exec wordpress_app wp plugin deactivate epic-marks-blocks --allow-root

# Via WordPress admin
# Plugins → Installed Plugins → Deactivate
```

---

## Using the Blocks in Editor

### Adding Blocks
1. Open page/post in Block Editor
2. Click **+** (Add Block) button
3. Search for:
   - "Wave Block"
   - "Countdown Block"
   - "USP Block"
4. Click to insert

### Block Categories
All blocks appear in the **Widgets** category in the block inserter.

### Editing Blocks
- **Inspector Panel** (right sidebar): All styling and content options
- **Toolbar** (top of block): Copy/paste styles (USP Block only)
- **Direct Editing**: Type directly into title fields

---

## Styling Customization

### Epic Marks Brand Colors (Pre-configured)

All blocks integrate with the Epic Marks color palette defined in the Kadence child theme:

```css
--slate-gray: #454C57;      /* Primary text */
--steel-blue: #627A94;      /* Accent */
--accent-hover: #C2CCD1;    /* Hover states */
--white: #FFFFFF;           /* Backgrounds */
--neutral-bg: #F2F7F9;      /* Subtle backgrounds */
--border: #E6EEF2;          /* Borders */
--success: #64BF99;         /* Success states */
--error: #DA3F3F;           /* Error states */
```

These colors appear in the WordPress color picker for easy selection.

### CSS Classes

**Wave Block:**
- `.em-wave-full-container`: Outer wrapper
- `.em-wave-container`: Wave SVG container
- `.em-waves-animated`: Animation enabled class
- `.em-waves-rotated`: 180° rotation class
- `.em-wave-bottom-section`: Solid section below waves

**Countdown Block:**
- `.em-countdown-banner`: Full-width banner wrapper

**USP Block:**
- `.em-usp-item`: Block wrapper
- `.em-usp-icon`: Icon/emoji container
- `.em-usp-title`: Title text

### Custom CSS Overrides

Add to Kadence child theme `style.css` or **Customizer → Additional CSS**:

```css
/* Increase wave animation speed */
.em-waves-animated .wave-parallax-1 > use {
    animation-duration: 5s !important;
}

/* Custom countdown hover effect */
.em-countdown-banner:hover {
    opacity: 0.9;
    transition: opacity 0.3s ease;
}

/* USP grid layout (3 columns) */
.wp-block-group.usp-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
}

@media (max-width: 768px) {
    .wp-block-group.usp-grid {
        grid-template-columns: 1fr;
    }
}
```

---

## Common Design Patterns

### Homepage Hero with Wave Transition
```
[Hero Section - White background]
[Wave Block - waveColor: #627A94, backgroundColor: #FFFFFF]
[Content Section - #627A94 background]
```

### Same-Day Shipping Header
```
[Countdown Block - "Order by 2PM for same-day shipping!"]
[Page Content]
```

### 3-Column USP Grid
```
[Group Block - 3 columns]
    [USP Block - "📦 SAME-DAY SHIPPING"]
    [USP Block - "✓ PRO QUALITY"]
    [USP Block - "🎨 CUSTOM DESIGN"]
```

### Feature Section with Cards
```
[Wave Block - Inverted]
[Group Block - White background, 4 columns]
    [USP Block - White card with shadow]
    [USP Block - White card with shadow]
    [USP Block - White card with shadow]
    [USP Block - White card with shadow]
[Wave Block - Regular]
```

---

## Development

### File Structure
- **epic-marks-blocks.php**: Plugin loader, block registration, PHP render callbacks
- **assets/blocks.js**: React components, block definitions, editor UI
- **assets/blocks.css**: Frontend styling, editor preview styles

### Adding New Blocks
1. Register block in `epic-marks-blocks.php`:
   ```php
   register_block_type('epic-marks/new-block', array(
       'editor_script' => 'epic-marks-blocks',
       'style' => 'epic-marks-blocks-style',
       'render_callback' => array($this, 'render_new_block')
   ));
   ```

2. Add block definition in `assets/blocks.js`:
   ```javascript
   registerBlockType('epic-marks/new-block', {
       title: 'New Block',
       icon: 'star-filled',
       category: 'widgets',
       attributes: { /* ... */ },
       edit: function(props) { /* ... */ },
       save: function() { return null; } // Server-side render
   });
   ```

3. Create render callback in `epic-marks-blocks.php`:
   ```php
   public function render_new_block($attributes) {
       ob_start();
       ?>
       <div class="em-new-block">
           <?php echo esc_html($attributes['content']); ?>
       </div>
       <?php
       return ob_get_clean();
   }
   ```

### Testing Changes
```bash
# Clear WordPress cache
sudo docker exec wordpress_app wp cache flush --allow-root

# Check for JavaScript errors in browser console
# Refresh page editor and test block

# View frontend rendering
# Publish page and view on frontend
```

### Version Bumping
Update version in plugin header (line 5 of `epic-marks-blocks.php`):
```php
* Version: 1.0.7
```

---

## Troubleshooting

**Issue: Blocks not appearing in editor**
- **Solution:** Clear browser cache, hard refresh (Ctrl+Shift+R)
- **Solution:** Check plugin is activated: `wp plugin list --allow-root`
- **Solution:** Check browser console for JavaScript errors

**Issue: Wave animations not working**
- **Solution:** Ensure `animate` attribute is `true`
- **Solution:** Check CSS animations not disabled by theme
- **Solution:** Test in different browser (animations use CSS3)

**Issue: Countdown block not full-width**
- **Solution:** Ensure block is not nested in narrow container
- **Solution:** Check parent block doesn't have `max-width` restriction
- **Solution:** Use "Full Width" alignment if available

**Issue: USP block icons showing as squares**
- **Solution:** Emoji rendering depends on OS/browser fonts
- **Solution:** Test with different emojis (some render better)
- **Solution:** Use Font Awesome classes instead of emojis (requires icon font)

**Issue: Copy/paste styles not working**
- **Solution:** Both source and target must be USP blocks
- **Solution:** Styles stored in session memory (lost on page refresh)
- **Solution:** Copy styles before navigating away from page

**Issue: Colors not matching design**
- **Solution:** Use hex codes from Epic Marks brand palette (see Styling section)
- **Solution:** Check Kadence theme Global Palette settings
- **Solution:** Test on frontend (editor may show slightly different colors)

**Issue: Countdown not updating (shows static text)**
- **Solution:** Open browser console (F12) and check for JavaScript errors
- **Solution:** Verify countdown-timer.js is loading: View page source and search for "countdown-timer.js"
- **Solution:** Check that block has unique ID: Inspect element and verify `id="countdown-{uniqueid}-text"`
- **Solution:** Verify `window.EM_COUNTDOWN_CONFIG` exists: Type in console and check output
- **Solution:** Disable browser extensions that block JavaScript
- **Solution:** Test in incognito mode to rule out caching issues

**Issue: Countdown shows wrong timezone**
- **Solution:** Verify timezone is America/Chicago in config: Check `window.EM_COUNTDOWN_CONFIG.tz` in console
- **Solution:** Countdown always shows Central Time (CT), not local time - this is by design
- **Solution:** Check browser supports `Intl.DateTimeFormat` with timezone: Run in console: `new Intl.DateTimeFormat('en-US', {timeZone: 'America/Chicago'}).format(new Date())`
- **Solution:** Update old browsers (Chrome 24+, Firefox 29+, Safari 10+ required)

**Issue: Countdown shows wrong message state**
- **Solution:** Verify cutoff time settings in **Settings → Countdown Timer**
- **Solution:** Check current time vs cutoff: Open console and run `new Date().toLocaleString('en-US', {timeZone: 'America/Chicago'})`
- **Solution:** Verify today's date not in holidays or extra closed dates lists
- **Solution:** Check "Close on Sundays" setting if today is Sunday
- **Solution:** Disable temporary override if enabled

**Issue: Multiple countdown blocks show different times**
- **Solution:** This should not happen - all blocks use shared timer logic
- **Solution:** Hard refresh page (Ctrl+Shift+R) to clear cached JavaScript
- **Solution:** Check browser console for JavaScript errors
- **Solution:** Report as bug with screenshots and browser version

**Issue: Countdown not accounting for holidays**
- **Solution:** Verify holiday dates in YYYY-MM-DD format (e.g., "2025-12-25")
- **Solution:** Check for typos in date format (must use dashes, not slashes)
- **Solution:** Dates can be comma-separated or newline-separated
- **Solution:** Open browser console and check `window.EM_COUNTDOWN_CONFIG.holidays`
- **Solution:** Invalid dates are silently ignored - verify format is correct

**Issue: Next business day calculation wrong**
- **Solution:** Verify all closed dates are configured (Sundays, holidays, extra closed)
- **Solution:** Check that "Close on Sundays" setting matches business hours
- **Solution:** Algorithm looks 366 days ahead - if all days closed, returns start date
- **Solution:** Open console and inspect countdown logic with debugging

**Issue: Temporary override not working**
- **Solution:** Verify "Temporarily Closed" checkbox is checked in settings
- **Solution:** Check override message is not empty
- **Solution:** Hard refresh page (Ctrl+Shift+R) to clear cache
- **Solution:** Verify `window.EM_COUNTDOWN_CONFIG.overrideClosed` is `true` in console

---

## Performance Notes

- **Server-Side Rendering**: All blocks use PHP rendering for zero client-side JavaScript
- **Inline Styles**: Dynamic attributes generate inline styles for maximum flexibility
- **Animation Performance**: Wave animations use CSS transforms (GPU-accelerated)
- **Caching Compatibility**: Blocks are cache-friendly (no dynamic server-side data)
- **File Size**: Total plugin size ~25KB (minimal footprint)

---

## Browser Compatibility

**Fully Supported:**
- Chrome 90+ (desktop/mobile)
- Firefox 88+ (desktop/mobile)
- Safari 14+ (desktop/mobile)
- Edge 90+ (desktop)

**Partial Support:**
- IE 11: Wave animations may not work (CSS transform limitations)
- Older Android browsers: Emoji rendering varies by device

**Recommended Testing Browsers:**
- Chrome (primary development browser)
- Safari (iOS compatibility)
- Firefox (standards compliance check)

---

## Future Enhancements

**Planned Features:**
- [x] Dynamic countdown timer with JavaScript (real-time updates) - **COMPLETED v1.0.8**
- [ ] Wave block presets (common color combinations)
- [ ] USP block icon library (pre-curated emoji/icon sets)
- [ ] Animation timing controls (custom easing curves)
- [ ] Global style presets (save/load common designs)
- [ ] WooCommerce integration (dynamic shipping deadline from store settings)
- [ ] Countdown block: Email notification when settings changed
- [ ] Countdown block: Analytics tracking for conversion rates

**Under Consideration:**
- [ ] Video background wave option
- [ ] Gradient support for wave colors
- [ ] USP block description field (subtitle text)
- [ ] Mobile-specific animation toggle

---

## Version History

**v1.0.8** (2025-10-12)
- Add dynamic countdown timer with live JavaScript updates
- Implement timezone-aware calculations (America/Chicago)
- Add holiday and Sunday closure detection
- Add next-open-business-day calculator
- Add WordPress admin settings page for countdown configuration
- Add three countdown states (before cutoff, after cutoff, closed)
- Add temporary override mode for emergency closures
- Add template variable support for countdown messages ({time}, {date}, {cutoff})
- Add countdown-timer.js (5.2 KB) with real-time updates every 1 second
- Support multiple countdown blocks on same page with shared timer

**v1.0.7** (2025-10-12)
- Add countdown configuration infrastructure
- Add Settings API integration for countdown options
- Add 10 settings fields (cutoff time, holidays, messages, override)
- Add countdown settings page under Settings menu

**v1.0.6** (2025-10-12)
- Add copy/paste styles functionality for USP blocks
- Replace basic color picker with WordPress theme palette
- Add comprehensive styling options (background, padding, radius, shadow)
- Implement dynamic vertical spacing for text-only USP blocks
- Add horizontal layout CSS with auto-centering
- Update Kadence child theme styles for white backgrounds

**v1.0.5** (2025-10-12)
- Initial release with Wave, Countdown, and USP blocks
- Server-side rendering implementation
- Basic styling controls

---

## Support & Documentation

**Code Location:** `/wordpress/wp-content/plugins/epic-marks-blocks/`

**Related Documentation:**
- Main project docs: `/CLAUDE.md`
- Header/Footer guide: `/HEADER-FOOTER-GUIDE.md`
- Kadence child theme: `/wordpress/wp-content/themes/kadence-child/`

**WP-CLI Commands:**
```bash
# Check plugin status
sudo docker exec wordpress_app wp plugin status epic-marks-blocks --allow-root

# View plugin info
sudo docker exec wordpress_app wp plugin get epic-marks-blocks --allow-root
```

**Debugging:**
Enable WordPress debug mode in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs: `/wordpress/wp-content/debug.log`

---

**Last Updated:** 2025-10-12
**Plugin Version:** 1.0.8
**WordPress Version:** 6.4+
**Minimum PHP:** 7.4
**Countdown Timer:** America/Chicago timezone (Central Time)
