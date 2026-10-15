(function () {
  const apiBase = document.body.dataset.crmApiBase || '../../api/v1/index.php';
  const csrf = document.body.dataset.csrf || '';
  const PAGE_SIZE = 50;

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
    const paginationEl = document.getElementById('crm-contacts-pagination');
    let offset = 0;
    let lastBatchSize = 0;

    if (search && document.body.dataset.crmInitialQ) {
      search.value = document.body.dataset.crmInitialQ;
    }

    function renderPagination() {
      if (!paginationEl) return;
      paginationEl.innerHTML = '';
      if (offset > 0) {
        const prev = document.createElement('button');
        prev.type = 'button';
        prev.className = 'crm-btn crm-btn-secondary crm-btn-sm';
        prev.textContent = 'Previous';
        prev.addEventListener('click', () => {
          offset = Math.max(0, offset - PAGE_SIZE);
          load(false);
        });
        paginationEl.appendChild(prev);
      }
      const info = document.createElement('span');
      info.className = 'crm-field-hint';
      info.textContent =
        offset === 0 && lastBatchSize < PAGE_SIZE
          ? 'Showing ' + lastBatchSize + ' contact(s)'
          : 'Showing ' + (offset + 1) + '–' + (offset + lastBatchSize);
      paginationEl.appendChild(info);
      if (lastBatchSize >= PAGE_SIZE) {
        const next = document.createElement('button');
        next.type = 'button';
        next.className = 'crm-btn crm-btn-secondary crm-btn-sm';
        next.textContent = 'Next';
        next.addEventListener('click', () => {
          offset += PAGE_SIZE;
          load(false);
        });
        paginationEl.appendChild(next);
      }
    }

    const load = async (resetOffset) => {
      if (resetOffset !== false) {
        offset = 0;
      }
      try {
        const q = search ? search.value : '';
        const tagId = tagFilter && tagFilter.value ? '&tag_id=' + encodeURIComponent(tagFilter.value) : '';
        const data = await api(
          '/contacts?q=' + encodeURIComponent(q) + tagId + '&limit=' + PAGE_SIZE + '&offset=' + offset
        );
        listEl.innerHTML = '';
        const rows = data.contacts || [];
        lastBatchSize = rows.length;
        if (!rows.length && offset === 0) {
          listEl.innerHTML =
            '<tr><td colspan="6"><div class="crm-empty"><p class="crm-empty-title">No contacts found</p><p>Try a different search or import a CSV.</p></div></td></tr>';
          renderPagination();
          return;
        }
        if (!rows.length && offset > 0) {
          offset = Math.max(0, offset - PAGE_SIZE);
          load(false);
          return;
        }
        rows.forEach((c) => {
          const tr = document.createElement('tr');
          const tags = (c.tags || [])
            .map(
              (t) =>
                '<span class="crm-tag-chip" style="background:' +
                (t.color || '#64748b') +
                '">' +
                escapeHtml(t.label) +
                '</span>'
            )
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
            '<td><a class="crm-btn crm-btn-sm crm-btn-secondary" href="view.php?id=' +
            c.id +
            '">Open</a></td>';
          listEl.appendChild(tr);
        });
        renderPagination();
      } catch (e) {
        listEl.innerHTML =
          '<tr><td colspan="6"><div class="crm-alert crm-alert-error">' + escapeHtml(e.message) + '</div></td></tr>';
        if (paginationEl) paginationEl.innerHTML = '';
      }
    };
    if (search) search.addEventListener('input', debounce(() => load(true), 300));
    if (tagFilter) tagFilter.addEventListener('change', () => load(true));
    load(true);

    const importForm = document.getElementById('crm-import-form');
    if (importForm) {
      importForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fileInput = document.getElementById('crm-import-file');
        const file = fileInput && fileInput.files[0];
        if (!file) return;
        const text = await file.text();
        const result = await api('/contacts/import', { method: 'POST', body: { csv: text } });
        const msg = 'Imported: ' + (result.imported || 0) + ', skipped: ' + (result.skipped || 0);
        if (window.crmToast) window.crmToast(msg, 'success');
        else alert(msg);
        load(true);
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
        if (window.crmToast) window.crmToast('Note saved', 'success');
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
