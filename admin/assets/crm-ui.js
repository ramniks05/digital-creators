(function () {
  'use strict';

  var lastFocus = null;

  function trapFocus(modal) {
    var focusable = modal.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    if (!focusable.length) return;
    var first = focusable[0];
    var last = focusable[focusable.length - 1];
    modal.addEventListener('keydown', function (e) {
      if (e.key !== 'Tab') return;
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    });
  }

  window.crmOpenModal = function (id) {
    var backdrop = document.getElementById(id);
    if (!backdrop) return;
    lastFocus = document.activeElement;
    backdrop.classList.add('is-open');
    backdrop.setAttribute('aria-hidden', 'false');
    var modal = backdrop.querySelector('.crm-modal');
    if (modal) trapFocus(modal);
    var focusEl = backdrop.querySelector('[data-autofocus]') || backdrop.querySelector('button, input');
    if (focusEl) focusEl.focus();
  };

  window.crmCloseModal = function (id) {
    var backdrop = document.getElementById(id);
    if (!backdrop) return;
    backdrop.classList.remove('is-open');
    backdrop.setAttribute('aria-hidden', 'true');
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  };

  document.addEventListener('click', function (e) {
    var close = e.target.closest('[data-crm-modal-close]');
    if (close) {
      var backdrop = close.closest('.crm-modal-backdrop');
      if (backdrop && backdrop.id) crmCloseModal(backdrop.id);
    }
    if (e.target.classList && e.target.classList.contains('crm-modal-backdrop')) {
      crmCloseModal(e.target.id);
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.crm-modal-backdrop.is-open').forEach(function (b) {
        if (b.id) crmCloseModal(b.id);
      });
    }
  });
})();
