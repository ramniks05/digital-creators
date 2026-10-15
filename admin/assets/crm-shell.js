(function () {
  'use strict';

  var STORAGE_THEME = 'crm-theme';
  var STORAGE_SIDEBAR = 'crm-sidebar-collapsed';

  function prefersDark() {
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  }

  function applyTheme(theme) {
    var root = document.documentElement;
    if (theme === 'dark') {
      root.setAttribute('data-theme', 'dark');
    } else if (theme === 'light') {
      root.setAttribute('data-theme', 'light');
    } else {
      root.removeAttribute('data-theme');
    }
  }

  function initTheme() {
    try {
      var saved = localStorage.getItem(STORAGE_THEME);
      if (saved === 'dark' || saved === 'light') {
        applyTheme(saved);
      }
    } catch (e) {
      /* ignore */
    }
    var toggle = document.getElementById('crm-theme-toggle');
    if (toggle) {
      toggle.addEventListener('click', function () {
        var next =
          document.documentElement.getAttribute('data-theme') === 'dark' ||
          (!document.documentElement.getAttribute('data-theme') && prefersDark())
            ? 'light'
            : 'dark';
        applyTheme(next);
        try {
          localStorage.setItem(STORAGE_THEME, next);
        } catch (e2) {
          /* ignore */
        }
      });
    }
  }

  function initSidebar() {
    var sidebar = document.getElementById('crm-app-sidebar');
    if (!sidebar) return;

    try {
      if (localStorage.getItem(STORAGE_SIDEBAR) === '1') {
        sidebar.classList.add('is-collapsed');
      }
    } catch (e) {
      /* ignore */
    }

    var collapseBtn = document.getElementById('crm-sidebar-collapse');
    if (collapseBtn) {
      collapseBtn.addEventListener('click', function () {
        sidebar.classList.toggle('is-collapsed');
        try {
          localStorage.setItem(STORAGE_SIDEBAR, sidebar.classList.contains('is-collapsed') ? '1' : '0');
        } catch (e2) {
          /* ignore */
        }
      });
    }

    var mobileToggle = document.getElementById('crm-mobile-nav-toggle');
    var backdrop = document.getElementById('crm-sidebar-backdrop');
    function closeMobile() {
      sidebar.classList.remove('is-mobile-open');
      if (backdrop) backdrop.classList.remove('is-visible');
    }
    function openMobile() {
      sidebar.classList.add('is-mobile-open');
      if (backdrop) backdrop.classList.add('is-visible');
    }
    if (mobileToggle) {
      mobileToggle.addEventListener('click', function () {
        if (sidebar.classList.contains('is-mobile-open')) closeMobile();
        else openMobile();
      });
    }
    if (backdrop) backdrop.addEventListener('click', closeMobile);
  }

  function initDropdowns() {
    document.querySelectorAll('[data-crm-dropdown]').forEach(function (wrap) {
      var btn = wrap.querySelector('[data-crm-dropdown-trigger]');
      if (!btn) return;
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = wrap.classList.contains('is-open');
        document.querySelectorAll('.crm-dropdown.is-open').forEach(function (d) {
          d.classList.remove('is-open');
        });
        if (!open) wrap.classList.add('is-open');
      });
    });
    document.addEventListener('click', function () {
      document.querySelectorAll('.crm-dropdown.is-open').forEach(function (d) {
        d.classList.remove('is-open');
      });
    });
  }

  function initGlobalSearch() {
    var input = document.getElementById('crm-global-search');
    if (!input) return;
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        var q = input.value.trim();
        if (!q) return;
        var base = document.body.getAttribute('data-crm-contacts-search') || 'contacts/index.php';
        var sep = base.indexOf('?') >= 0 ? '&' : '?';
        window.location.href = base + sep + 'q=' + encodeURIComponent(q);
      }
    });
  }

  window.crmToast = function (message, type) {
    var host = document.getElementById('crm-toast-host');
    if (!host) {
      if (type === 'error') alert(message);
      return;
    }
    var el = document.createElement('div');
    el.className = 'crm-toast' + (type ? ' is-' + type : '');
    el.setAttribute('role', 'status');
    el.textContent = message;
    host.appendChild(el);
    setTimeout(function () {
      el.remove();
    }, 4500);
  };

  initTheme();
  initSidebar();
  initDropdowns();
  initGlobalSearch();
})();
