(function () {
  const apiBase = document.body.dataset.crmApiBase || '../../api/v1/index.php';
  const csrf = document.body.dataset.csrf || '';

  async function api(path, options) {
    const url = apiBase.replace(/\/$/, '') + path;
    const opts = Object.assign(
      {
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-CSRF-Token': csrf },
      },
      options || {}
    );
    if (opts.body && typeof opts.body === 'object') {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(opts.body);
    }
    const res = await fetch(url, opts);
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.ok === false) {
      throw new Error(data.error || 'Request failed');
    }
    return data.data !== undefined ? data.data : data;
  }

  window.crmContactsApi = { api };

  const listEl = document.getElementById('crm-contacts-list-body');
  if (listEl) {
    const search = document.getElementById('crm-contacts-search');
    const tagFilter = document.getElementById('crm-contacts-tag');
    const load = async () => {
      const q = search ? search.value : '';
      const tagId = tagFilter && tagFilter.value ? '&tag_id=' + encodeURIComponent(tagFilter.value) : '';
      const data = await api('/contacts?q=' + encodeURIComponent(q) + tagId);
      listEl.innerHTML = '';
      (data.contacts || []).forEach((c) => {
        const tr = document.createElement('tr');
        const tags = (c.tags || [])
          .map((t) => '<span class="crm-tag-chip" style="background:' + (t.color || '#64748b') + '">' + escapeHtml(t.label) + '</span>')
          .join('');
        tr.innerHTML =
          '<td><a href="view.php?id=' +
          c.id +
          '">' +
          escapeHtml(c.display_name || c.wa_id || 'Contact') +
          '</a></td>' +
          '<td>+' +
          escapeHtml(c.wa_id || '') +
          '</td>' +
          '<td>' +
          escapeHtml(c.source || '') +
          '</td>' +
          '<td>' +
          escapeHtml(c.consent_status || '') +
          '</td>' +
          '<td>' +
          tags +
          '</td>' +
          '<td><a class="btn btn-sm btn-secondary" href="view.php?id=' +
          c.id +
          '">Open</a></td>';
        listEl.appendChild(tr);
      });
    };
    if (search) search.addEventListener('input', debounce(load, 300));
    if (tagFilter) tagFilter.addEventListener('change', load);
    load();

    const importForm = document.getElementById('crm-import-form');
    if (importForm) {
      importForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fileInput = document.getElementById('crm-import-file');
        const file = fileInput && fileInput.files[0];
        if (!file) return;
        const text = await file.text();
        const result = await api('/contacts/import', { method: 'POST', body: { csv: text } });
        alert('Imported: ' + (result.imported || 0) + ', skipped: ' + (result.skipped || 0));
        load();
      });
    }
  }

  const detailId = document.body.dataset.contactId;
  if (detailId) {
    const id = parseInt(detailId, 10);
    loadDetail(id);
  }

  async function loadDetail(id) {
    const data = await api('/contacts/' + id);
    const c = data.contact;
    const title = document.getElementById('crm-contact-title');
    if (title) title.textContent = c.display_name || '+' + c.wa_id;

    const meta = document.getElementById('crm-contact-meta');
    if (meta) {
      meta.innerHTML =
        '<p><strong>Phone:</strong> +' +
        escapeHtml(c.wa_id) +
        '</p><p><strong>Source:</strong> ' +
        escapeHtml(c.source || '') +
        '</p><p><strong>Consent:</strong> ' +
        escapeHtml(c.consent_status) +
        '</p>';
    }

    const timeline = document.getElementById('crm-contact-timeline');
    if (timeline) {
      timeline.innerHTML = '';
      (c.activity || []).forEach((a) => {
        const li = document.createElement('li');
        li.textContent = (a.created_at || '') + ' — ' + (a.activity_type || '') + ': ' + (a.summary || '');
        timeline.appendChild(li);
      });
    }

    const msgs = await api('/contacts/' + id + '/messages');
    const msgEl = document.getElementById('crm-contact-messages');
    if (msgEl) {
      msgEl.innerHTML = (msgs.messages || [])
        .map(
          (m) =>
            '<div><small>' +
            escapeHtml(m.direction) +
            '</small> ' +
            escapeHtml(m.body_text || m.message_type) +
            '</div>'
        )
        .join('');
    }

    const noteForm = document.getElementById('crm-note-form');
    if (noteForm) {
      noteForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = document.getElementById('crm-note-body').value;
        await api('/contacts/' + id + '/notes', { method: 'POST', body: { body } });
        document.getElementById('crm-note-body').value = '';
        loadDetail(id);
      });
    }
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function debounce(fn, ms) {
    let t;
    return function () {
      clearTimeout(t);
      t = setTimeout(fn, ms);
    };
  }
})();
