/**
 * Dynamic variable fields for WhatsApp template send forms (Meta Cloud API).
 */
window.dcWaInitTemplateForm = function (selectId, langInputId, paramsContainerId, previewId) {
  const select = document.getElementById(selectId);
  const langInput = document.getElementById(langInputId);
  const paramsBox = document.getElementById(paramsContainerId);
  const preview = document.getElementById(previewId);
  if (!select || !paramsBox) {
    return;
  }

  function parseSlots(opt) {
    const b64 = opt.getAttribute('data-param-slots-b64');
    if (b64) {
      try {
        const decoded = atob(b64);
        const slots = JSON.parse(decoded);
        if (Array.isArray(slots) && slots.length > 0) {
          return slots;
        }
      } catch (e) {
        /* fall through */
      }
    }
    try {
      const raw = opt.getAttribute('data-param-slots') || opt.dataset.paramSlots || '[]';
      const slots = JSON.parse(raw);
      if (Array.isArray(slots) && slots.length > 0) {
        return slots;
      }
    } catch (e) {
      /* fall through */
    }
    const n = parseInt(opt.getAttribute('data-param-count') || '0', 10);
    if (n > 0) {
      return Array.from({ length: n }, function (_, i) {
        return { section: 'body', label: 'Variable ' + (i + 1) };
      });
    }
    return [];
  }

  function render() {
    const opt = select.selectedOptions[0];
    if (!opt) {
      return;
    }
    if (langInput) {
      langInput.value = opt.dataset.lang || opt.getAttribute('data-lang') || '';
    }
    const slots = parseSlots(opt);
    paramsBox.innerHTML = '';
    if (slots.length > 0) {
      paramsBox.style.display = 'block';
      const hint = document.createElement('p');
      hint.className = 'muted';
      hint.style.margin = '0 0 8px';
      hint.textContent =
        slots.length === 1
          ? 'This template needs 1 variable (e.g. customer name).'
          : 'This template needs ' + slots.length + ' variables, in Meta order (header first, then body).';
      paramsBox.appendChild(hint);
      slots.forEach(function (slot, idx) {
        const label = document.createElement('label');
        label.textContent = slot.label || 'Variable ' + (idx + 1);
        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'template_body_params[]';
        input.required = true;
        input.autocomplete = 'off';
        input.placeholder = idx === 0 ? 'e.g. customer name' : 'Required value';
        paramsBox.appendChild(label);
        paramsBox.appendChild(input);
      });
    } else {
      paramsBox.style.display = 'none';
    }
    if (preview) {
      const text = opt.dataset.bodyPreview || opt.getAttribute('data-body-preview') || '';
      preview.textContent = text ? 'Template body: ' + text : '';
    }
  }

  select.addEventListener('change', render);
  render();
};

/** Chat view: scroll to latest, keyboard send, focus reply. */
window.dcWaInitChatUi = function () {
  const thread = document.getElementById('wa-chat-thread');
  if (thread) {
    thread.scrollTop = thread.scrollHeight;
  }
  const ta = document.querySelector('.wa-reply-row textarea');
  if (!ta || !ta.form) {
    return;
  }
  window.requestAnimationFrame(function () {
    ta.focus();
  });
  ta.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      if (ta.value.trim() !== '') {
        ta.form.requestSubmit();
      }
    }
  });
};
