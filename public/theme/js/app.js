/* =====================================================================
   Nyvora Admin - Core App JS
   - Theme persistence (dark/light/system)
   - Sidebar toggle (mini + mobile drawer)
   - Layout modes (vertical / mini / horizontal / boxed)
   - Working theme customizer (color, layout, mode, RTL, reset)
   - Multi-level menu accordion
   ===================================================================== */

(function ($) {
  'use strict';

  // Admin panel and client area keep independent preferences: one browser can be
  // logged into both, and an admin toggling the theme must not restyle the client app.
  const SCOPE = document.documentElement.getAttribute('data-ny-scope') === 'admin' ? 'admin' : 'app';
  const STORAGE_KEY = SCOPE === 'admin' ? 'nyvora-admin-config' : 'nyvora-config';
  const COOKIE_NAME = SCOPE === 'admin' ? 'nyvora-admin-theme' : 'nyvora-theme';
  const COOKIE_PATH = SCOPE === 'admin'
    ? (document.documentElement.getAttribute('data-admin-path') || '/admin')
    : '/';
  const DEFAULTS = {
    mode: 'light',          // light | dark | system
    layout: 'boxed',        // vertical | mini | horizontal | boxed
    sidebarMini: false,
    primary: '#007257',
    direction: 'ltr'        // ltr | rtl
  };

  // ---------- Hex helpers ----------
  function hexToRgb(hex) {
    if (!hex) return null;
    const m = hex.replace('#','').match(/.{1,2}/g);
    if (!m || m.length !== 3) return null;
    return m.map(c => parseInt(c, 16));
  }
  function shade(hex, percent) {
    const rgb = hexToRgb(hex); if (!rgb) return hex;
    const adj = c => Math.max(0, Math.min(255, Math.round(c + (255 - c) * percent)));
    const r = percent >= 0 ? adj(rgb[0]) : Math.max(0, Math.round(rgb[0] * (1 + percent)));
    const g = percent >= 0 ? adj(rgb[1]) : Math.max(0, Math.round(rgb[1] * (1 + percent)));
    const b = percent >= 0 ? adj(rgb[2]) : Math.max(0, Math.round(rgb[2] * (1 + percent)));
    return '#' + [r,g,b].map(c => c.toString(16).padStart(2,'0')).join('');
  }

  // ---------- Bootstrap RTL CSS swap ----------
  function swapBootstrapCss(direction) {
    const links = document.querySelectorAll('link[rel="stylesheet"][href*="bootstrap"]');
    links.forEach(link => {
      const href = link.getAttribute('href');
      if (!href || !/bootstrap[^/]*\.min\.css/.test(href)) return;
      if (direction === 'rtl' && !href.includes('bootstrap.rtl')) {
        link.setAttribute('href', href.replace('bootstrap.min.css', 'bootstrap.rtl.min.css'));
      } else if (direction !== 'rtl' && href.includes('bootstrap.rtl')) {
        link.setAttribute('href', href.replace('bootstrap.rtl.min.css', 'bootstrap.min.css'));
      }
    });
  }

  // ---------- Config persistence ----------
  function loadConfig() {
    try {
      const parsed = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
      const stored = parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
      const merged = Object.assign({}, DEFAULTS, stored);
      if (merged.layout === 'mini') {
        merged.sidebarMini = true;
        merged.layout = 'boxed';
      } else if (merged.layout !== 'horizontal') {
        merged.layout = 'boxed';
      }
      if (stored.duventoLayout !== 1) {
        merged.layout = 'boxed';
        merged.duventoLayout = 1;
      }
      if (merged.primary !== '#007257') {
        merged.primary = '#007257';
      }
      if (stored.duventoLayout !== 1 || stored.primary !== '#007257') {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(merged));
      }
      return merged;
    } catch (e) {
      return Object.assign({}, DEFAULTS);
    }
  }
  function saveConfig(cfg) { localStorage.setItem(STORAGE_KEY, JSON.stringify(cfg)); }

  let cfg = loadConfig();

  function resolveMode() {
    if (cfg.mode === 'system') {
      return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    return cfg.mode;
  }

  function applyAll() {
    const body = document.body;
    const html = document.documentElement;

    // Theme
    const mode = resolveMode();
    html.setAttribute('data-bs-theme', mode);
    if (SCOPE === 'admin') html.classList.toggle('dark', mode === 'dark');
    document.cookie = COOKIE_NAME + '=' + mode + ';path=' + COOKIE_PATH + ';max-age=31536000;SameSite=Lax';

    // Direction
    html.setAttribute('dir', cfg.direction);
    html.setAttribute('lang', cfg.direction === 'rtl' ? 'ar' : 'en');

    // Swap Bootstrap CSS to RTL build when direction is rtl
    swapBootstrapCss(cfg.direction);

    // Re-render every registered chart with the new direction, then trigger window resize
    setTimeout(function () {
      try { if (typeof window.NyvoraRefreshCharts === 'function') window.NyvoraRefreshCharts(cfg.direction === 'rtl'); } catch (e) {}
      try { window.dispatchEvent(new Event('resize')); } catch (e) {}
    }, 80);

    // Primary color (with light/dark shading)
    html.style.setProperty('--ny-primary', '#007257');
    html.style.setProperty('--ny-primary-2', '#00a87a');
    html.style.setProperty('--ny-primary-rgb', '0,114,87');
    html.style.setProperty('--ny-grad-primary', 'linear-gradient(135deg, #00a87a 0%, #007257 100%)');

    if (cfg.layout === 'horizontal') {
      body.classList.remove('sidebar-mini', 'layout-boxed');
      body.classList.add('layout-horizontal');
    } else {
      body.classList.remove('layout-horizontal');
      if (body.querySelector('.app')) body.classList.add('layout-boxed');
      body.classList.toggle('sidebar-mini', !!cfg.sidebarMini);
    }
  }

  // Apply ASAP
  applyAll();

  // Listen to OS theme changes when mode is system
  if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
      if (cfg.mode === 'system') applyAll();
    });
  }

  // ---------- Sidebar toggle ----------
  $(document).on('click', '[data-toggle="sidebar"]', function () {
    const isMobile = window.matchMedia('(max-width: 991.98px)').matches;
    if (isMobile) {
      document.body.classList.toggle('sidebar-open');
    } else {
      cfg.sidebarMini = !cfg.sidebarMini;
      saveConfig(cfg); applyAll();
    }
  });
  $(document).on('click', '.backdrop, [data-close="sidebar"]', function () {
    document.body.classList.remove('sidebar-open');
  });

  // ---------- Dark/light toggle button ----------
  $(document).on('click', '[data-toggle="theme"]', function () {
    cfg.mode = (resolveMode() === 'dark') ? 'light' : 'dark';
    saveConfig(cfg); applyAll();
  });

  // ---------- Fullscreen ----------
  $(document).on('click', '[data-toggle="fullscreen"]', function () {
    if (!document.fullscreenElement) {
      (document.documentElement.requestFullscreen || document.documentElement.webkitRequestFullscreen || function(){}).call(document.documentElement);
    } else {
      (document.exitFullscreen || document.webkitExitFullscreen || function(){}).call(document);
    }
  });

  // ---------- Image lightbox ----------
  function closeLightbox() {
    const box = document.querySelector('.ny-lightbox');
    if (box) box.remove();
  }

  $(document).on('click', 'a[data-lightbox]', function (e) {
    e.preventDefault();
    closeLightbox();
    const box = document.createElement('div');
    box.className = 'ny-lightbox';
    box.setAttribute('role', 'dialog');
    const img = document.createElement('img');
    img.src = this.getAttribute('href');
    img.alt = this.getAttribute('title') || '';
    box.appendChild(img);
    document.body.appendChild(box);
  });

  $(document).on('click', '.ny-lightbox', closeLightbox);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeLightbox();
  });
  document.addEventListener('livewire:navigating', closeLightbox);

  // ---------- Multi-level vertical menu (accordion - click) ----------
  $(document).on('click', '.menu a', function (e) {
    const $a = $(this);
    const $li = $a.parent('li');
    const $sub = $a.next('.submenu');
    if ($sub.length) {
      e.preventDefault();
      if ($li.hasClass('open')) {
        $li.removeClass('open');
      } else {
        // Close siblings (accordion)
        $li.parent().children('li.open').removeClass('open');
        $li.addClass('open');
      }
    }
  });

  // (Hover-to-open removed — vertical menu submenus open only on click)

  // Prevent the trigger button from navigating or submitting
  $(document).on('click', '.h-nav .h-nav-item.dropdown-toggle', function (e) { e.preventDefault(); });

  // Per-document-tree setup. Re-runs after Livewire SPA navigation because the whole
  // body (shell included) is swapped out on every visit.
  function initPage() {
    // Horizontal nav opens on hover, so keep Bootstrap from managing those triggers
    $('.h-nav [data-bs-toggle="dropdown"]').removeAttr('data-bs-toggle');

    const path = (location.pathname.split('/').pop() || 'index.html').toLowerCase();
    $('.menu a, .submenu a').each(function () {
      const href = ($(this).attr('href') || '').split('/').pop().toLowerCase();
      if (href && href === path && href !== '#' && href !== 'javascript:;') {
        $(this).addClass('active');
        $(this).parents('.submenu').parent('li').addClass('open');
        $(this).closest('.menu > li').addClass('active');
      }
    });

    // Annotate each top-level menu item with its label for mini-mode tooltips/flyouts
    $('.menu > li').each(function () {
      const label = $(this).find('> a .menu-text').first().text().trim();
      if (label) $(this).attr('data-label', label);
    });

    if (!window.bootstrap) return;
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => new bootstrap.Popover(el));
  }

  $(initPage);
  document.addEventListener('livewire:navigated', initPage);

  // ---------- Mini sidebar flyout (positioned via JS to escape clipping) ----------
  let flyoutTimer = null;
  let activeSub = null;
  let activeTip = null;
  const FLYOUT_CLOSE_DELAY = 200;

  function isMini() { return document.body.classList.contains('sidebar-mini'); }

  function clearTip() {
    if (activeTip) { activeTip.remove(); activeTip = null; }
  }
  function closeFlyout() {
    if (activeSub) {
      activeSub.classList.remove('flyout-open');
      activeSub.style.cssText = '';
      activeSub.removeAttribute('data-flyout-title');
      // Put the submenu back to its original parent so menu behaves normally
      const origParent = activeSub._origParent;
      const origNext = activeSub._origNext;
      if (origParent) {
        if (origNext && origNext.parentNode === origParent) origParent.insertBefore(activeSub, origNext);
        else origParent.appendChild(activeSub);
        activeSub._origParent = null;
        activeSub._origNext = null;
      }
      activeSub = null;
    }
    clearTip();
  }
  function scheduleClose() {
    clearTimeout(flyoutTimer);
    flyoutTimer = setTimeout(closeFlyout, FLYOUT_CLOSE_DELAY);
  }
  function openFlyout(li) {
    const sub = li.querySelector(':scope > .submenu');
    const label = li.getAttribute('data-label') || '';
    clearTimeout(flyoutTimer);
    if (activeSub && activeSub !== sub) closeFlyout();

    const isRtl = document.documentElement.getAttribute('dir') === 'rtl';

    if (!sub) {
      // Show a small tooltip with the label for items without submenu
      clearTip();
      const a = li.querySelector(':scope > a');
      if (!a || !label) return;
      const rect = a.getBoundingClientRect();
      const tip = document.createElement('div');
      tip.className = 'menu-tooltip';
      tip.textContent = label;
      document.body.appendChild(tip);
      const tipRect = tip.getBoundingClientRect();
      if (isRtl) {
        tip.style.left = (rect.left - tipRect.width - 10) + 'px';
      } else {
        tip.style.left = (rect.right + 10) + 'px';
      }
      tip.style.top = (rect.top + rect.height / 2 - tipRect.height / 2) + 'px';
      activeTip = tip;
      return;
    }

    // Move the submenu out of the sidebar (which has overflow:hidden + transform → clips fixed children)
    if (sub.parentNode !== document.body) {
      sub._origParent = sub.parentNode;
      sub._origNext = sub.nextSibling;
      document.body.appendChild(sub);
    }

    // Position the submenu as fixed: opens to the right of LI in LTR, to the left in RTL
    const rect = li.getBoundingClientRect();
    sub.classList.add('flyout-open');
    sub.setAttribute('data-flyout-title', label);
    sub.style.position = 'fixed';
    sub.style.top = rect.top + 'px';
    if (isRtl) {
      // Position the flyout's RIGHT edge at the LI's LEFT edge — no width measurement needed
      sub.style.left = 'auto';
      sub.style.right = (window.innerWidth - rect.left) + 'px';
    } else {
      sub.style.left = rect.right + 'px';
      sub.style.right = 'auto';
    }

    // Vertical bounds check after render
    requestAnimationFrame(() => {
      if (!sub.classList.contains('flyout-open')) return;
      const subRect = sub.getBoundingClientRect();
      if (subRect.bottom > window.innerHeight - 8) {
        sub.style.top = Math.max(8, window.innerHeight - subRect.height - 8) + 'px';
      }
    });

    activeSub = sub;
  }

  document.addEventListener('mouseover', function (e) {
    if (!isMini()) return;
    const li = e.target.closest('.sidebar .menu > li');
    if (li) {
      openFlyout(li);
      return;
    }
    // Keep flyout open while hovering it
    if (activeSub && (activeSub === e.target || activeSub.contains(e.target))) {
      clearTimeout(flyoutTimer);
    }
  });

  document.addEventListener('mouseout', function (e) {
    if (!isMini()) return;
    const li = e.target.closest('.sidebar .menu > li');
    const sub = e.target.closest('.submenu.flyout-open');
    if (li || sub) {
      const movedTo = e.relatedTarget;
      // If moving into the active submenu or another part of the same li, keep open
      if (movedTo && activeSub && (movedTo === activeSub || activeSub.contains(movedTo))) return;
      if (movedTo && movedTo.closest && movedTo.closest('.sidebar .menu > li') === li) return;
      scheduleClose();
    }
  });

  // Close on layout change / scroll / resize
  window.addEventListener('scroll', closeFlyout, true);
  window.addEventListener('resize', closeFlyout);
  $(document).on('click', '[data-toggle="sidebar"]', closeFlyout);
  // The flyout is reparented to <body>, so it must be put back before Livewire swaps the body
  document.addEventListener('livewire:navigating', closeFlyout);

  // ---------- Global chart RTL patcher ----------
  // Runs once on DOMContentLoaded so chart libraries have loaded by then.
  // Wraps ApexCharts + Chart.js + ECharts to inject RTL options when html[dir=rtl],
  // registers every created instance so we can flip them on direction toggle without reload.
  const _charts = { apex: [], chartjs: [], echarts: [] };
  function _isRtl() { return document.documentElement.getAttribute('dir') === 'rtl'; }

  document.addEventListener('DOMContentLoaded', function () {

    // ----- ApexCharts patch -----
    if (window.ApexCharts) {
      const Orig = window.ApexCharts;
      function PatchedApex(el, opts) {
        opts = opts || {};
        opts.chart = opts.chart || {};
        if (opts.chart.rtl === undefined) opts.chart.rtl = _isRtl();
        // Sensible RTL defaults for legend/tooltip
        if (_isRtl()) {
          opts.legend = Object.assign({ position: 'bottom', horizontalAlign: 'right' }, opts.legend || {});
          opts.tooltip = Object.assign({ x: { format: undefined } }, opts.tooltip || {});
        }
        const inst = new Orig(el, opts);
        _charts.apex.push(inst);
        return inst;
      }
      Object.keys(Orig).forEach(function (k) { PatchedApex[k] = Orig[k]; });
      PatchedApex.prototype = Orig.prototype;
      window.ApexCharts = PatchedApex;
    }

    // ----- Chart.js patch -----
    if (window.Chart && window.Chart.defaults) {
      const d = window.Chart.defaults;
      d.plugins = d.plugins || {};
      d.plugins.legend = d.plugins.legend || {};
      d.plugins.tooltip = d.plugins.tooltip || {};
      function applyChartJsDefaults(rtl) {
        d.plugins.legend.rtl = rtl;
        d.plugins.legend.textDirection = rtl ? 'rtl' : 'ltr';
        d.plugins.tooltip.rtl = rtl;
        d.plugins.tooltip.textDirection = rtl ? 'rtl' : 'ltr';
      }
      applyChartJsDefaults(_isRtl());

      const OrigChart = window.Chart;
      function PatchedChart(canvas, cfg) {
        cfg = cfg || {};
        cfg.options = cfg.options || {};
        if (_isRtl()) {
          cfg.options.plugins = cfg.options.plugins || {};
          cfg.options.plugins.legend = Object.assign({ rtl: true, textDirection: 'rtl' }, cfg.options.plugins.legend || {});
          cfg.options.plugins.tooltip = Object.assign({ rtl: true, textDirection: 'rtl' }, cfg.options.plugins.tooltip || {});
        }
        const inst = new OrigChart(canvas, cfg);
        _charts.chartjs.push(inst);
        return inst;
      }
      Object.keys(OrigChart).forEach(function (k) { PatchedChart[k] = OrigChart[k]; });
      PatchedChart.prototype = OrigChart.prototype;
      window.Chart = PatchedChart;
      window.Chart._applyRtl = applyChartJsDefaults;
    }

    // ----- ECharts patch -----
    if (window.echarts && window.echarts.init) {
      const origInit = window.echarts.init;
      window.echarts.init = function (el, theme, opts) {
        const inst = origInit.call(this, el, theme, opts);
        _charts.echarts.push(inst);
        // Apply RTL hook on each setOption call
        const origSetOption = inst.setOption.bind(inst);
        inst.setOption = function (option, notMerge, lazy) {
          if (_isRtl() && option && typeof option === 'object') {
            // Tooltip + legend RTL
            if (option.tooltip) {
              if (Array.isArray(option.tooltip)) option.tooltip.forEach(t => t.textStyle = Object.assign({ align: 'right' }, t.textStyle || {}));
              else option.tooltip.textStyle = Object.assign({ align: 'right' }, option.tooltip.textStyle || {});
            }
            if (option.legend) {
              const wrap = l => { l.textStyle = Object.assign({ align: 'right' }, l.textStyle || {}); };
              Array.isArray(option.legend) ? option.legend.forEach(wrap) : wrap(option.legend);
            }
            // X-axis reverse for cartesian charts
            if (option.xAxis) {
              const wrap = a => { if (a.type === 'category' || a.type === 'value' || !a.type) { a.inverse = true; if (a.axisLabel) a.axisLabel.align = 'right'; } };
              Array.isArray(option.xAxis) ? option.xAxis.forEach(wrap) : wrap(option.xAxis);
            }
          }
          return origSetOption(option, notMerge, lazy);
        };
        return inst;
      };
    }
  });

  // ----- Direction-change handler: refresh every registered chart -----
  window.NyvoraRefreshCharts = function (rtl) {
    rtl = (rtl !== undefined) ? rtl : _isRtl();

    // ApexCharts: updateOptions with new rtl (redrawPaths=true, animate=false, updateSyncedCharts=true)
    _charts.apex.forEach(function (c) {
      try {
        c.updateOptions({
          chart: { rtl: rtl },
          legend: { horizontalAlign: rtl ? 'right' : 'left' }
        }, true, false, true);
      } catch (e) {}
    });

    // Chart.js: update defaults + each instance
    if (window.Chart && window.Chart._applyRtl) window.Chart._applyRtl(rtl);
    _charts.chartjs.forEach(function (c) {
      try {
        c.options.plugins = c.options.plugins || {};
        c.options.plugins.legend = Object.assign(c.options.plugins.legend || {}, { rtl: rtl, textDirection: rtl ? 'rtl' : 'ltr' });
        c.options.plugins.tooltip = Object.assign(c.options.plugins.tooltip || {}, { rtl: rtl, textDirection: rtl ? 'rtl' : 'ltr' });
        c.update();
      } catch (e) {}
    });

    // ECharts: resize + setOption to apply RTL hook
    _charts.echarts.forEach(function (c) {
      try {
        c.resize();
        const opt = c.getOption();
        if (opt) c.setOption(opt, true);
      } catch (e) {}
    });
  };

})(jQuery);
