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
    try {
      const raw = opt.dataset.paramSlots || '[]';
      const slots = JSON.parse(raw);
      return Array.isArray(slots) ? slots : [];
    } catch (e) {
      return [];
    }
  }

  function render() {
    const opt = select.selectedOptions[0];
    if (!opt) {
      return;
    }
    if (langInput) {
      langInput.value = opt.dataset.lang || '';
    }
    const slots = parseSlots(opt);
    paramsBox.innerHTML = '';
    if (slots.length > 0) {
      paramsBox.style.display = 'block';
      slots.forEach(function (slot, idx) {
        const label = document.createElement('label');
        label.textContent = slot.label || 'Variable ' + (idx + 1);
        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'template_body_params[]';
        input.required = true;
        input.placeholder = idx === 0 ? 'e.g. customer name' : 'Required value';
        paramsBox.appendChild(label);
        paramsBox.appendChild(input);
      });
    } else {
      paramsBox.style.display = 'none';
    }
    if (preview) {
      const text = opt.dataset.bodyPreview || '';
      preview.textContent = text ? 'Template body: ' + text : '';
    }
  }

  select.addEventListener('change', render);
  render();
};
