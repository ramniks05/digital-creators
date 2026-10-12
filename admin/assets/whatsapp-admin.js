/**
 * Dynamic body-variable fields for WhatsApp template send forms.
 */
window.dcWaInitTemplateForm = function (selectId, langInputId, paramsContainerId, previewId) {
  const select = document.getElementById(selectId);
  const langInput = document.getElementById(langInputId);
  const paramsBox = document.getElementById(paramsContainerId);
  const preview = document.getElementById(previewId);
  if (!select || !paramsBox) {
    return;
  }

  function render() {
    const opt = select.selectedOptions[0];
    if (!opt) {
      return;
    }
    if (langInput) {
      langInput.value = opt.dataset.lang || '';
    }
    const count = parseInt(opt.dataset.bodyParams || '0', 10) || 0;
    paramsBox.innerHTML = '';
    if (count > 0) {
      paramsBox.style.display = 'block';
      for (let i = 0; i < count; i++) {
        const label = document.createElement('label');
        label.textContent = 'Variable {{' + (i + 1) + '}}';
        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'template_body_params[]';
        input.required = true;
        input.placeholder = i === 0 ? 'Customer name' : 'Value for {{' + (i + 1) + '}}';
        paramsBox.appendChild(label);
        paramsBox.appendChild(input);
      }
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
