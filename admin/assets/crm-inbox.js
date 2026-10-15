(function () {
  'use strict';

  var apiBase = document.body.dataset.crmApiBase || '../api/v1/index.php';
  var csrf = document.body.dataset.csrf || '';
  var inboxCfg = window.CRM_INBOX || { meAdminId: 0, admins: [] };

  var els = {
    list: document.getElementById('crm-conv-list'),
    thread: document.getElementById('crm-thread'),
    threadHeader: document.getElementById('crm-thread-header'),
    compose: document.getElementById('crm-compose-form'),
    composeWrap: document.getElementById('crm-compose-wrap'),
    text: document.getElementById('crm-compose-text'),
    sendBtn: document.getElementById('crm-compose-send'),
    templateBtn: document.getElementById('crm-template-btn'),
    windowHint: document.getElementById('crm-compose-window-hint'),
    search: document.getElementById('crm-search'),
    status: document.getElementById('crm-filter-status'),
    assigned: document.getElementById('crm-filter-assigned'),
    side: document.getElementById('crm-side-panel'),
    detailCol: document.getElementById('crm-inbox-detail-col'),
    err: document.getElementById('crm-inbox-error'),
    modeTabs: document.querySelectorAll('[data-compose-mode]'),
    mobileBack: document.getElementById('crm-inbox-mobile-back'),
    mobileDetails: document.getElementById('crm-inbox-mobile-details'),
    mobileTitle: document.getElementById('crm-inbox-mobile-title'),
    listCol: document.querySelector('.crm-inbox-col.crm-inbox-list'),
    chatCol: document.querySelector('.crm-inbox-col.crm-inbox-chat'),
    waStatusText: document.getElementById('crm-wa-status-text'),
    waStatusDot: document.getElementById('crm-wa-status-dot'),
    templateList: document.getElementById('crm-template-list'),
  };

  var activeId = null;
  var activeConv = null;
  var composeMode = 'reply';
  var pollTimer = null;
  var templatesCache = null;
  var isSending = false;
  var openRequestId = 0;
  var messageLimit = 80;

  function qs(extra) {
    var p = new URLSearchParams();
    if (els.status && els.status.value) p.set('status', els.status.value);
    if (els.assigned && els.assigned.value) p.set('assigned', els.assigned.value);
    if (els.search && els.search.value.trim()) p.set('q', els.search.value.trim());
    if (extra) {
      Object.keys(extra).forEach(function (k) {
        p.set(k, extra[k]);
      });
    }
    var q = p.toString();
    return q ? '?' + q : '';
  }

  async function api(path, options) {
    var url = apiBase.replace(/\/$/, '') + path;
    var opts = Object.assign(
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
    var res = await fetch(url, opts);
    var data = await res.json().catch(function () {
      return {};
    });
    if (!res.ok || data.ok === false) {
      throw new Error(data.error || 'Request failed');
    }
    return data.data !== undefined ? data.data : data;
  }

  function toast(msg, type) {
    if (window.crmToast) window.crmToast(msg, type);
    else if (type === 'error') showError(msg);
  }

  function showError(msg) {
    if (els.err) {
      els.err.textContent = msg || '';
      els.err.hidden = !msg;
    }
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function initial(name, phone) {
    var s = (name || phone || '?').trim();
    return s.charAt(0).toUpperCase();
  }

  function parseTs(iso) {
    if (!iso) return null;
    var d = new Date(iso.replace(' ', 'T') + (iso.indexOf('Z') > -1 ? '' : 'Z'));
    return isNaN(d.getTime()) ? null : d;
  }

  function formatTime(iso) {
    var d = parseTs(iso);
    if (!d) return iso || '';
    var now = new Date();
    if (d.toDateString() === now.toDateString()) {
      return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    return d.toLocaleDateString([], { month: 'short', day: 'numeric' });
  }

  function formatDay(iso) {
    var d = parseTs(iso);
    if (!d) return '';
    return d.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric' });
  }

  function serviceWindowOpen(lastInboundAt) {
    var d = parseTs(lastInboundAt);
    if (!d) return false;
    return Date.now() - d.getTime() < 24 * 60 * 60 * 1000;
  }

  function messageBody(m) {
    if (m.body_text) return m.body_text;
    var media = m.media_json;
    if (typeof media === 'string') {
      try {
        media = JSON.parse(media);
      } catch (e) {
        media = null;
      }
    }
    if (media && typeof media === 'object') {
      var type = m.message_type || media.type || 'media';
      if (media.url) return '[' + type + '] ' + media.url;
      return '[' + type + ' attachment]';
    }
    return '[' + (m.message_type || 'message') + ']';
  }

  function deliveryLabel(status) {
    if (!status) return '';
    var s = String(status).toLowerCase();
    if (s === 'failed' || s === 'undelivered') return ' · Failed';
    if (s === 'read') return ' · Read';
    if (s === 'delivered') return ' · Delivered';
    if (s === 'sent') return ' · Sent';
    return ' · ' + status;
  }

  function isFailedDelivery(status) {
    if (!status) return false;
    var s = String(status).toLowerCase();
    return s === 'failed' || s === 'undelivered' || s === 'error';
  }

  function setMobilePanel(panel) {
    if (!els.listCol || !els.chatCol) return;
    if (window.innerWidth > 768) {
      if (els.detailCol) els.detailCol.classList.remove('is-mobile-visible');
      return;
    }
    els.listCol.classList.toggle('is-mobile-visible', panel === 'list');
    els.chatCol.classList.toggle('is-mobile-visible', panel === 'chat');
    if (els.detailCol) els.detailCol.classList.toggle('is-mobile-visible', panel === 'detail');
    if (els.mobileBack) els.mobileBack.hidden = panel === 'list';
    if (els.mobileDetails) els.mobileDetails.hidden = panel !== 'chat';
  }

  function renderList(rows) {
    if (!els.list) return;
    if (!rows.length) {
      els.list.innerHTML =
        '<div class="crm-inbox-empty"><p class="crm-empty-title">No conversations</p><p>Try adjusting filters or wait for inbound WhatsApp messages.</p></div>';
      return;
    }
    els.list.innerHTML = '';
    rows.forEach(function (c) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'crm-conv-item' + (activeId === Number(c.id) ? ' is-active' : '');
      var name = c.contact_name || c.peer_wa_id || 'Unknown';
      var unread = Number(c.unread_count || 0);
      var preview = c.last_message_preview || 'No messages yet';
      var time = formatTime(c.last_message_at || c.updated_at);
      btn.innerHTML =
        '<span class="crm-conv-avatar" aria-hidden="true">' +
        escapeHtml(initial(name, c.peer_wa_id)) +
        '</span><span class="crm-conv-body"><span class="crm-conv-row"><span class="crm-conv-name">' +
        escapeHtml(name) +
        (unread > 0 ? ' <span class="crm-badge" aria-label="' + unread + ' unread">' + unread + '</span>' : '') +
        '</span><span class="crm-conv-time">' +
        escapeHtml(time) +
        '</span></span><span class="crm-conv-preview">' +
        escapeHtml(preview) +
        '</span><span class="crm-conv-meta"><span class="crm-status-pill is-' +
        escapeHtml(c.status || 'open') +
        '">' +
        escapeHtml(c.status || 'open') +
        '</span>' +
        (c.assigned_admin_id
          ? '<span class="crm-status-pill">Agent #' + escapeHtml(c.assigned_admin_id) + '</span>'
          : '<span class="crm-status-pill">Unassigned</span>') +
        '</span></span>';
      btn.addEventListener('click', function () {
        openConversation(Number(c.id));
      });
      els.list.appendChild(btn);
    });
  }

  function buildTimeline(messages, notes) {
    var items = [];
    (messages || []).forEach(function (m) {
      items.push({
        kind: 'msg',
        ts: m.sent_at || m.created_at,
        data: m,
      });
    });
    (notes || []).forEach(function (n) {
      items.push({
        kind: 'note',
        ts: n.created_at,
        data: n,
      });
    });
    items.sort(function (a, b) {
      var da = parseTs(a.ts);
      var db = parseTs(b.ts);
      if (!da || !db) return 0;
      return da - db;
    });
    return items;
  }

  function renderTimeline(messages, notes) {
    if (!els.thread) return;
    els.thread.innerHTML = '';
    var items = buildTimeline(messages, notes);
    if (!items.length) {
      els.thread.innerHTML = '<div class="crm-inbox-empty"><p>No messages in this thread yet.</p></div>';
      return;
    }
    var lastDay = '';
    items.forEach(function (item) {
      var ts = item.ts || '';
      var day = formatDay(ts);
      if (day && day !== lastDay) {
        lastDay = day;
        var sep = document.createElement('div');
        sep.className = 'crm-day-sep';
        sep.innerHTML = '<span>' + escapeHtml(day) + '</span>';
        els.thread.appendChild(sep);
      }
      var div = document.createElement('div');
      if (item.kind === 'note') {
        div.className = 'crm-bubble is-note';
        div.innerHTML =
          '<strong>Internal note</strong><br>' +
          escapeHtml(item.data.body || '') +
          '<span class="crm-bubble-meta">' +
          escapeHtml(formatTime(ts)) +
          '</span>';
      } else {
        var m = item.data;
        var inbound = m.direction === 'inbound';
        var failed = isFailedDelivery(m.delivery_status);
        div.className =
          'crm-bubble ' + (inbound ? 'is-in' : 'is-out') + (failed ? ' is-failed' : '');
        div.innerHTML =
          escapeHtml(messageBody(m)) +
          '<span class="crm-bubble-meta">' +
          escapeHtml(formatTime(ts)) +
          escapeHtml(deliveryLabel(m.delivery_status)) +
          '</span>';
      }
      els.thread.appendChild(div);
    });
    els.thread.scrollTop = els.thread.scrollHeight;
  }

  function showThreadLoading() {
    if (!els.thread) return;
    els.thread.innerHTML =
      '<div class="crm-inbox-empty"><div class="crm-skeleton" style="height:64px;margin:8px"></div><div class="crm-skeleton" style="height:64px;margin:8px;width:70%;margin-left:auto"></div></div>';
  }

  function renderThreadHeader(conv) {
    if (!els.threadHeader || !conv) return;
    var name = conv.contact_name || '+' + (conv.peer_wa_id || '');
    var open = serviceWindowOpen(conv.last_inbound_at);
    var adminOptions = (inboxCfg.admins || [])
      .map(function (a) {
        var sel = Number(conv.assigned_admin_id) === Number(a.id) ? ' selected' : '';
        return (
          '<option value="' + escapeHtml(a.id) + '"' + sel + '>' + escapeHtml(a.username) + '</option>'
        );
      })
      .join('');
    els.threadHeader.innerHTML =
      '<h2>' +
      escapeHtml(name) +
      '</h2>' +
      '<span class="crm-window-badge ' +
      (open ? 'is-open' : 'is-closed') +
      '" title="24-hour customer service window">' +
      (open ? 'Service window open' : 'Outside service window') +
      '</span>' +
      '<label class="sr-only" for="crm-header-status">Status</label>' +
      '<select id="crm-header-status" aria-label="Conversation status">' +
      ['open', 'pending', 'resolved']
        .map(function (s) {
          return (
            '<option value="' +
            s +
            '"' +
            (conv.status === s ? ' selected' : '') +
            '>' +
            s +
            '</option>'
          );
        })
        .join('') +
      '</select>' +
      (inboxCfg.admins && inboxCfg.admins.length
        ? '<label class="sr-only" for="crm-header-assign">Assign agent</label><select id="crm-header-assign" aria-label="Assign agent"><option value="0">Unassigned</option>' +
          adminOptions +
          '</select>'
        : '');
    var statusSel = document.getElementById('crm-header-status');
    if (statusSel) {
      statusSel.addEventListener('change', function () {
        api('/conversations/' + activeId + '/status', {
          method: 'PATCH',
          body: { status: statusSel.value },
        })
          .then(function () {
            toast('Status updated', 'success');
            return openConversation(activeId);
          })
          .catch(function (e) {
            toast(e.message, 'error');
          });
      });
    }
    var assignSel = document.getElementById('crm-header-assign');
    if (assignSel) {
      assignSel.addEventListener('change', function () {
        api('/conversations/' + activeId + '/assignment', {
          method: 'PATCH',
          body: { assigned_admin_id: parseInt(assignSel.value, 10) || 0 },
        })
          .then(function () {
            toast('Assignment updated', 'success');
            return openConversation(activeId);
          })
          .catch(function (e) {
            toast(e.message, 'error');
          });
      });
    }
    if (els.mobileTitle) els.mobileTitle.textContent = name;
  }

  function renderSide(conv, notes) {
    if (!els.side || !conv) return;
    var tags = [];
    try {
      tags = conv.tags_json ? JSON.parse(conv.tags_json) : [];
    } catch (e) {
      tags = [];
    }
    var contactLink =
      conv.contact_id > 0
        ? '<a href="contacts/view.php?id=' + encodeURIComponent(conv.contact_id) + '">View full contact</a>'
        : '<span class="crm-field-hint">Contact record will sync from WhatsApp activity.</span>';
    var notesHtml = (notes || [])
      .slice(0, 8)
      .map(function (n) {
        return '<li>' + escapeHtml(n.body || '') + '<br><small>' + escapeHtml(n.created_at || '') + '</small></li>';
      })
      .join('');
    els.side.innerHTML =
      '<h3>Customer</h3><dl>' +
      '<dt>Phone</dt><dd>+' +
      escapeHtml(conv.peer_wa_id || '') +
      '</dd>' +
      '<dt>Status</dt><dd><span class="crm-status-pill is-' +
      escapeHtml(conv.status || '') +
      '">' +
      escapeHtml(conv.status || '') +
      '</span></dd>' +
      '<dt>Assigned</dt><dd>' +
      escapeHtml(conv.assigned_admin_id ? 'Agent #' + conv.assigned_admin_id : 'Unassigned') +
      '</dd>' +
      '<dt>Consent</dt><dd>' +
      escapeHtml(conv.consent_status || '—') +
      '</dd>' +
      '<dt>Tags</dt><dd>' +
      escapeHtml(Array.isArray(tags) && tags.length ? tags.join(', ') : '—') +
      '</dd></dl>' +
      '<p>' +
      contactLink +
      '</p>' +
      '<div class="crm-side-notes"><h3>Recent notes</h3><ul>' +
      (notesHtml || '<li class="crm-inbox-empty">No internal notes yet.</li>') +
      '</ul></div>';
  }

  async function loadNotes(contactId) {
    if (!contactId) return [];
    try {
      var data = await api('/contacts/' + contactId + '/notes');
      return data.notes || [];
    } catch (e) {
      return [];
    }
  }

  async function loadConversations() {
    if (document.hidden) return;
    try {
      showError('');
      var data = await api('/conversations' + qs());
      renderList(data.conversations || []);
    } catch (e) {
      showError(e.message);
    }
  }

  async function openConversation(id) {
    activeId = id;
    var reqId = ++openRequestId;
    setMobilePanel('chat');
    try {
      var url = new URL(window.location.href);
      url.searchParams.set('conversation', String(id));
      window.history.replaceState({}, '', url.pathname + url.search);
    } catch (e) {
      /* ignore */
    }
    showThreadLoading();
    try {
      showError('');
      var detail = await api('/conversations/' + id);
      if (reqId !== openRequestId) return;
      activeConv = detail.conversation;
      renderThreadHeader(activeConv);
      var msgData = await api('/conversations/' + id + '/messages?limit=' + messageLimit);
      if (reqId !== openRequestId) return;
      var notes = await loadNotes(activeConv.contact_id ? Number(activeConv.contact_id) : 0);
      if (reqId !== openRequestId) return;
      renderTimeline(msgData.messages || [], notes);
      renderSide(activeConv, notes);
      await loadConversations();
      updateComposeUi();
    } catch (e) {
      if (reqId === openRequestId) showError(e.message);
    }
  }

  function updateComposeUi() {
    if (!els.composeWrap || !els.text || !els.sendBtn) return;
    var isNote = composeMode === 'note';
    var hasConv = !!activeId && !!activeConv;
    var windowOpen = hasConv && serviceWindowOpen(activeConv.last_inbound_at);
    els.composeWrap.classList.toggle('is-note-mode', isNote);

    if (els.windowHint) {
      if (!hasConv) {
        els.windowHint.hidden = true;
      } else if (!isNote && !windowOpen) {
        els.windowHint.hidden = false;
        els.windowHint.textContent =
          'Free-form replies are disabled outside the 24-hour window. Use an approved template.';
      } else {
        els.windowHint.hidden = true;
      }
    }

    if (!hasConv) {
      els.text.disabled = true;
      els.sendBtn.disabled = true;
      if (els.templateBtn) els.templateBtn.disabled = true;
      return;
    }

    if (isNote) {
      els.text.disabled = !activeConv.contact_id;
      els.sendBtn.disabled = !activeConv.contact_id;
      els.text.placeholder = activeConv.contact_id
        ? 'Write an internal note — never sent to WhatsApp…'
        : 'Notes require a linked contact record.';
    } else {
      els.text.disabled = !windowOpen;
      els.sendBtn.disabled = !windowOpen;
      els.text.placeholder = windowOpen
        ? 'Reply to customer…'
        : 'Use a template — free-form reply not available';
    }
    els.sendBtn.textContent = isNote ? 'Save note' : 'Send';
    if (els.templateBtn) {
      els.templateBtn.hidden = isNote;
      els.templateBtn.disabled = isNote || !hasConv;
    }
  }

  async function sendMessage(ev) {
    ev.preventDefault();
    if (isSending || !activeId || !els.text) return;
    var text = els.text.value.trim();
    if (!text) return;

    if (composeMode === 'reply' && activeConv && !serviceWindowOpen(activeConv.last_inbound_at)) {
      toast('Outside the 24-hour window. Use an approved template.', 'error');
      return;
    }

    isSending = true;
    if (els.sendBtn) {
      els.sendBtn.disabled = true;
      els.sendBtn.classList.add('is-loading');
    }
    try {
      showError('');
      if (composeMode === 'note') {
        if (!activeConv || !activeConv.contact_id) {
          throw new Error('Link a contact before saving notes.');
        }
        await api('/contacts/' + activeConv.contact_id + '/notes', {
          method: 'POST',
          body: { body: text },
        });
        els.text.value = '';
        toast('Note saved', 'success');
        await openConversation(activeId);
      } else {
        await api('/conversations/' + activeId + '/messages', {
          method: 'POST',
          body: { text: text, idempotency_key: 'ui-' + activeId + '-' + Date.now() },
        });
        els.text.value = '';
        toast('Message queued', 'success');
        await openConversation(activeId);
      }
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      isSending = false;
      if (els.sendBtn) els.sendBtn.classList.remove('is-loading');
      updateComposeUi();
    }
  }

  async function loadTemplates() {
    if (templatesCache) return templatesCache;
    var data = await api('/templates');
    templatesCache = data.templates || [];
    if (data.fetch_error) toast(String(data.fetch_error), 'error');
    return templatesCache;
  }

  function renderTemplateModal(list) {
    if (!els.templateList) return;
    if (!list.length) {
      els.templateList.innerHTML =
        '<p class="crm-inbox-empty">No approved templates. Configure WhatsApp credentials or approve templates in Meta.</p>';
      return;
    }
    els.templateList.innerHTML = list
      .map(function (t) {
        return (
          '<button type="button" class="crm-template-row" data-template-name="' +
          escapeHtml(t.name || '') +
          '" data-template-lang="' +
          escapeHtml(t.language || 'en_US') +
          '"><strong>' +
          escapeHtml(t.name || '') +
          '</strong><span class="crm-field-hint">' +
          escapeHtml(t.language || '') +
          ' · ' +
          escapeHtml(t.category || '') +
          '</span></button>'
        );
      })
      .join('');
    els.templateList.querySelectorAll('[data-template-name]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        sendTemplate(btn.getAttribute('data-template-name'), btn.getAttribute('data-template-lang'));
      });
    });
  }

  async function openTemplatePicker() {
    if (composeMode === 'note' || !activeId) return;
    try {
      var list = await loadTemplates();
      renderTemplateModal(list);
      if (window.crmOpenModal) crmOpenModal('crm-template-modal');
    } catch (e) {
      toast(e.message, 'error');
    }
  }

  async function sendTemplate(name, language) {
    if (isSending || !activeId || !name) return;
    isSending = true;
    if (window.crmCloseModal) crmCloseModal('crm-template-modal');
    try {
      await api('/conversations/' + activeId + '/messages', {
        method: 'POST',
        body: {
          template: name,
          language: language || 'en_US',
          idempotency_key: 'ui-tpl-' + activeId + '-' + Date.now(),
        },
      });
      toast('Template queued', 'success');
      await openConversation(activeId);
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      isSending = false;
      updateComposeUi();
    }
  }

  async function loadWaStatus() {
    if (!els.waStatusText) return;
    try {
      var st = await api('/whatsapp/status');
      var ok = st.configured && st.app_secret_set;
      els.waStatusText.textContent = st.configured
        ? 'WhatsApp API configured' + (st.webhook_verify_token_set ? ' · Webhook ready' : ' · Verify webhook token')
        : 'WhatsApp not configured — set access token and phone number ID';
      if (els.waStatusDot) {
        els.waStatusDot.className = 'crm-wa-status-dot ' + (st.configured ? 'is-ok' : 'is-warn');
      }
    } catch (e) {
      els.waStatusText.textContent = 'Could not load connection status';
      if (els.waStatusDot) els.waStatusDot.className = 'crm-wa-status-dot is-warn';
    }
  }

  function startPolling() {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(function () {
      if (document.hidden) return;
      loadConversations();
      if (activeId && activeConv) {
        api('/conversations/' + activeId + '/messages?limit=' + messageLimit)
          .then(function (d) {
            return loadNotes(activeConv.contact_id ? Number(activeConv.contact_id) : 0).then(function (notes) {
              renderTimeline(d.messages || [], notes);
            });
          })
          .catch(function () {});
      }
    }, 8000);
  }

  function debounce(fn, ms) {
    var t;
    return function () {
      clearTimeout(t);
      t = setTimeout(fn, ms);
    };
  }

  els.modeTabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      composeMode = tab.getAttribute('data-compose-mode') || 'reply';
      els.modeTabs.forEach(function (t) {
        var on = t === tab;
        t.classList.toggle('is-active', on);
        t.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      updateComposeUi();
    });
  });

  if (els.templateBtn) els.templateBtn.addEventListener('click', openTemplatePicker);
  if (els.mobileBack) {
    els.mobileBack.addEventListener('click', function () {
      setMobilePanel('list');
    });
  }
  if (els.mobileDetails) {
    els.mobileDetails.addEventListener('click', function () {
      setMobilePanel('detail');
    });
  }

  [els.search, els.status, els.assigned].forEach(function (el) {
    if (el) el.addEventListener('change', loadConversations);
    if (el && el.id === 'crm-search') el.addEventListener('input', debounce(loadConversations, 350));
  });

  if (els.compose) els.compose.addEventListener('submit', sendMessage);

  window.addEventListener('resize', function () {
    if (window.innerWidth > 768) {
      if (els.listCol) els.listCol.classList.add('is-mobile-visible');
      if (els.chatCol) els.chatCol.classList.add('is-mobile-visible');
      if (els.detailCol) els.detailCol.classList.remove('is-mobile-visible');
    } else if (!activeId) {
      setMobilePanel('list');
    }
  });

  if (inboxCfg.meAdminId === 0 && els.assigned) {
    var meOpt = els.assigned.querySelector('option[value="me"]');
    if (meOpt) meOpt.textContent = 'Assigned to me (admin login only)';
  }

  function parseInitialConversationId() {
    try {
      var params = new URLSearchParams(window.location.search);
      var id = parseInt(params.get('conversation') || params.get('id') || '0', 10);
      return id > 0 ? id : null;
    } catch (e) {
      return null;
    }
  }

  setMobilePanel('list');
  loadWaStatus();
  loadConversations().then(function () {
    var initialId = parseInitialConversationId();
    if (initialId) {
      openConversation(initialId);
    }
  });
  startPolling();
  updateComposeUi();
})();
