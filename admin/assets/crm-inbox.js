(function () {
  const apiBase = document.body.dataset.crmApiBase || '../api/v1/index.php';
  const csrf = document.body.dataset.csrf || '';

  const els = {
    list: document.getElementById('crm-conv-list'),
    thread: document.getElementById('crm-thread'),
    compose: document.getElementById('crm-compose-form'),
    text: document.getElementById('crm-compose-text'),
    search: document.getElementById('crm-search'),
    status: document.getElementById('crm-filter-status'),
    assigned: document.getElementById('crm-filter-assigned'),
    side: document.getElementById('crm-side-panel'),
    err: document.getElementById('crm-inbox-error'),
  };

  let activeId = null;
  let pollTimer = null;

  function qs(extra) {
    const p = new URLSearchParams();
    if (els.status && els.status.value) p.set('status', els.status.value);
    if (els.assigned && els.assigned.value) p.set('assigned', els.assigned.value);
    if (els.search && els.search.value.trim()) p.set('q', els.search.value.trim());
    if (extra) {
      Object.keys(extra).forEach((k) => p.set(k, extra[k]));
    }
    const q = p.toString();
    return q ? '?' + q : '';
  }

  async function api(path, options) {
    const url = apiBase.replace(/\/$/, '') + path;
    const opts = Object.assign(
      {
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-CSRF-Token': csrf,
        },
      },
      options || {}
    );
    if (opts.body && typeof opts.body === 'object' && !(opts.body instanceof FormData)) {
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

  function showError(msg) {
    if (els.err) {
      els.err.textContent = msg;
      els.err.hidden = !msg;
    }
  }

  function renderList(rows) {
    if (!els.list) return;
    els.list.innerHTML = '';
    rows.forEach((c) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'crm-conv-item' + (activeId === Number(c.id) ? ' active' : '');
      const name = c.contact_name || c.peer_wa_id || 'Unknown';
      const unread = Number(c.unread_count || 0);
      btn.innerHTML =
        '<strong>' +
        escapeHtml(name) +
        (unread > 0 ? '<span class="crm-badge">' + unread + '</span>' : '') +
        '</strong><small>' +
        escapeHtml(c.last_message_preview || '') +
        '</small>';
      btn.addEventListener('click', () => openConversation(Number(c.id)));
      els.list.appendChild(btn);
    });
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function renderMessages(messages) {
    if (!els.thread) return;
    els.thread.innerHTML = '';
    messages.forEach((m) => {
      const div = document.createElement('div');
      div.className = 'crm-bubble ' + (m.direction === 'inbound' ? 'in' : 'out');
      const body = m.body_text || '[' + (m.message_type || 'message') + ']';
      const status = m.delivery_status ? ' · ' + m.delivery_status : '';
      div.innerHTML =
        escapeHtml(body) +
        '<meta>' +
        escapeHtml(m.sent_at || m.created_at || '') +
        status +
        '</meta>';
      els.thread.appendChild(div);
    });
    els.thread.scrollTop = els.thread.scrollHeight;
  }

  function renderSide(conv) {
    if (!els.side || !conv) return;
    const tags = conv.tags_json ? JSON.parse(conv.tags_json) : [];
    els.side.innerHTML =
      '<dl><dt>Phone</dt><dd>+' +
      escapeHtml(conv.peer_wa_id || '') +
      '</dd>' +
      '<dt>Status</dt><dd><span class="crm-status-pill">' +
      escapeHtml(conv.status || '') +
      '</span></dd>' +
      '<dt>Assigned</dt><dd>' +
      escapeHtml(conv.assigned_admin_id ? '#' + conv.assigned_admin_id : 'Unassigned') +
      '</dd>' +
      '<dt>Tags</dt><dd>' +
      escapeHtml(Array.isArray(tags) ? tags.join(', ') : '—') +
      '</dd></dl>';
  }

  async function loadConversations() {
    try {
      showError('');
      const data = await api('/conversations' + qs());
      renderList(data.conversations || []);
    } catch (e) {
      showError(e.message);
    }
  }

  async function openConversation(id) {
    activeId = id;
    try {
      showError('');
      const detail = await api('/conversations/' + id);
      renderSide(detail.conversation);
      const msgData = await api('/conversations/' + id + '/messages?limit=80');
      renderMessages(msgData.messages || []);
      await loadConversations();
    } catch (e) {
      showError(e.message);
    }
  }

  async function sendMessage(ev) {
    ev.preventDefault();
    if (!activeId || !els.text) return;
    const text = els.text.value.trim();
    if (!text) return;
    try {
      showError('');
      await api('/conversations/' + activeId + '/messages', {
        method: 'POST',
        body: { text: text, idempotency_key: 'ui-' + activeId + '-' + Date.now() },
      });
      els.text.value = '';
      await openConversation(activeId);
    } catch (e) {
      showError(e.message);
    }
  }

  function startPolling() {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(() => {
      loadConversations();
      if (activeId) {
        api('/conversations/' + activeId + '/messages?limit=80')
          .then((d) => renderMessages(d.messages || []))
          .catch(() => {});
      }
    }, 8000);
  }

  [els.search, els.status, els.assigned].forEach((el) => {
    if (el) el.addEventListener('change', loadConversations);
    if (el && el.id === 'crm-search') el.addEventListener('input', debounce(loadConversations, 350));
  });

  if (els.compose) els.compose.addEventListener('submit', sendMessage);

  function debounce(fn, ms) {
    let t;
    return function () {
      clearTimeout(t);
      t = setTimeout(fn, ms);
    };
  }

  loadConversations();
  startPolling();
})();
