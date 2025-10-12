/**
 * Epic Marks Countdown Timer
 * Ported from Shopify countdown section
 * Supports multiple countdown blocks on same page
 */
(function(){
  var cfg = window.EM_COUNTDOWN_CONFIG || {};

  // Find all countdown text elements (supports multiple blocks)
  var textElements = document.querySelectorAll('[id^="countdown-"][id$="-text"]');

  if(textElements.length === 0) return;

  if (cfg.overrideClosed) {
    textElements.forEach(function(el) {
      el.textContent = cfg.overrideMessage || "Temporarily closed.";
    });
    return;
  }

  function parseList(s){
    return (s||'').split(/[\n,]+/).map(function(x) { return x.trim(); }).filter(function(v) { return /^\d{4}-\d{2}-\d{2}$/.test(v); });
  }

  var holidaySet = new Set(parseList(cfg.holidays));
  var extraClosed = new Set(parseList(cfg.extraClosed));

  var tz = cfg.tz || 'America/Chicago';
  var cutoffHour = +cfg.cutoffHour || 14;
  var cutoffMinute = +cfg.cutoffMinute || 0;
  var closeOnSunday = !!cfg.closeOnSunday;

  function partsFromDate(d, tz){
    var fmt = new Intl.DateTimeFormat('en-US', {
      timeZone: tz,
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: false
    });
    var p = {};
    fmt.formatToParts(d).forEach(function(x) { p[x.type] = x.value; });
    p.year = +p.year;
    p.month = +p.month;
    p.day = +p.day;
    p.hour = +p.hour;
    p.minute = +p.minute;
    p.second = +p.second;
    return p;
  }

  function dateFrom(y, m, d, h, mi, s){
    return new Date(Date.UTC(y, m - 1, d, h, mi, s || 0));
  }

  function iso(p){
    return p.year + '-' + String(p.month).padStart(2, '0') + '-' + String(p.day).padStart(2, '0');
  }

  function closedOn(ymd, dow){
    if(closeOnSunday && dow === 0) return true;
    if(holidaySet.has(ymd)) return true;
    if(extraClosed.has(ymd)) return true;
    return false;
  }

  function nextOpen(start){
    var y = start.year, m = start.month, d = start.day;
    for(var i = 0; i < 366; i++){
      var cand = dateFrom(y, m, d, 12, 0, 0);
      var p = partsFromDate(cand, tz);
      var dow = new Date(Date.UTC(p.year, p.month - 1, p.day)).getUTCDay();
      if(!closedOn(iso(p), dow)) return p;
      var n = dateFrom(p.year, p.month, p.day + 1, 12, 0, 0);
      var np = partsFromDate(n, tz);
      y = np.year;
      m = np.month;
      d = np.day;
    }
    return start;
  }

  function fmtDate(p){
    var d = dateFrom(p.year, p.month, p.day, 12, 0, 0);
    return new Intl.DateTimeFormat('en-US', {
      timeZone: tz,
      weekday: 'short',
      month: 'short',
      day: 'numeric'
    }).format(d);
  }

  function fmtTime(p){
    var d = dateFrom(p.year, p.month, p.day, p.hour, p.minute, p.second);
    return new Intl.DateTimeFormat('en-US', {
      timeZone: tz,
      hour: 'numeric',
      minute: '2-digit'
    }).format(d) + ' CT';
  }

  var msgActive = cfg.msgActive || "Order in {time} to ship today (by {cutoff}).";
  var msgAfter = cfg.msgAfter || "Orders after cutoff ship next business day ({date} by {time}).";
  var msgClosed = cfg.msgClosed || "Closed today — orders process {date} by {time}.";

  function tick(){
    var now = new Date();
    var n = partsFromDate(now, tz);
    var today = dateFrom(n.year, n.month, n.day, 12, 0, 0);
    var dow = today.getUTCDay();
    var ymd = iso(n);
    var cutoff = {
      year: n.year,
      month: n.month,
      day: n.day,
      hour: cutoffHour,
      minute: cutoffMinute,
      second: 0
    };
    var cutoffDate = dateFrom(cutoff.year, cutoff.month, cutoff.day, cutoff.hour, cutoff.minute, 0);
    var nowTZ = dateFrom(n.year, n.month, n.day, n.hour, n.minute, n.second);

    var message = '';

    if (closedOn(ymd, dow)) {
      var open = nextOpen(n);
      message = msgClosed
        .replace('{date}', fmtDate(open))
        .replace('{time}', fmtTime({
          year: open.year,
          month: open.month,
          day: open.day,
          hour: cutoffHour,
          minute: cutoffMinute,
          second: 0
        }));
    } else if (nowTZ.getTime() < cutoffDate.getTime()) {
      var diff = cutoffDate - nowTZ;
      var s = Math.floor(diff / 1000);
      var h = Math.floor(s / 3600);
      s -= h * 3600;
      var m = Math.floor(s / 60);
      s -= m * 60;
      var parts = [];
      if(h > 0) parts.push(h + 'h');
      parts.push(m + 'm');
      parts.push(s + 's');
      message = msgActive
        .replace('{time}', parts.join(' '))
        .replace('{cutoff}', fmtTime(cutoff));
    } else {
      var open = nextOpen({
        year: n.year,
        month: n.month,
        day: n.day + 1,
        hour: 12,
        minute: 0,
        second: 0
      });
      message = msgAfter
        .replace('{date}', fmtDate(open))
        .replace('{time}', fmtTime({
          year: open.year,
          month: open.month,
          day: open.day,
          hour: cutoffHour,
          minute: cutoffMinute,
          second: 0
        }))
        .replace('{cutoff}', fmtTime(cutoff));
    }

    // Update all countdown text elements with the same message
    textElements.forEach(function(el) {
      el.textContent = message;
    });
  }

  // Run immediately on page load
  tick();

  // Update every 1 second
  setInterval(tick, 1000);
})();
