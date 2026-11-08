(() => {
  'use strict';
  const app = window.LSCH_APP || {};
  const one = (selector, root = document) => root.querySelector(selector);
  const all = (selector, root = document) => Array.from(root.querySelectorAll(selector));
  const idempotency = () => window.crypto?.randomUUID?.() || `lsch-${Date.now()}-${Math.random().toString(36).slice(2)}`;
  const show = (element, message, ok = true) => {
    const target = element.closest('.lsch-callout,.lsch-panel,.lsch-private-note,.lsch-learning-tools,.lsch-single')?.querySelector('[data-lsch-status]');
    if (target) {
      target.textContent = message;
      target.dataset.state = ok ? 'success' : 'error';
    }
  };
  const requireLogin = () => {
    if (app.loggedIn) return true;
    window.location.href = app.loginUrl;
    return false;
  };
  const api = async (path, options = {}) => {
    const method = String(options.method || 'GET').toUpperCase();
    const headers = {
      'Content-Type': 'application/json',
      'X-WP-Nonce': app.nonce || '',
      ...(options.headers || {}),
    };
    // File05's durable REST ledger requires an idempotency key on every mutation.
    if (!['GET', 'HEAD', 'OPTIONS'].includes(method) && !headers['Idempotency-Key']) {
      headers['Idempotency-Key'] = idempotency();
    }
    const response = await fetch(`${app.root || ''}${path}`, {
      credentials: 'same-origin',
      ...options,
      headers,
    });
    let data = {};
    try { data = await response.json(); } catch (error) { data = {}; }
    if (!response.ok) {
      const message = data?.message || app.strings?.error || 'Request failed';
      const trace = data?.data?.trace_id || response.headers.get('X-Request-ID') || '';
      throw new Error(trace ? `${message} (${trace})` : message);
    }
    return data;
  };

  all('[data-lsch-enroll]').forEach((button) => button.addEventListener('click', async () => {
    if (!requireLogin()) return;
    button.disabled = true; show(button, app.strings?.working || 'Working…');
    try {
      await api(`course/${button.dataset.lschEnroll}/enroll`, { method: 'POST', headers: { 'Idempotency-Key': idempotency() }, body: '{}' });
      show(button, app.strings?.saved || 'Saved');
    } catch (error) { show(button, error.message, false); } finally { button.disabled = false; }
  }));

  const reminderLabel = (enabled) => enabled
    ? (app.strings?.reminderDisable || 'Disable weekly reminder')
    : (app.strings?.reminderEnable || 'Enable weekly reminder');
  const setReminderButton = (button, enabled) => {
    button.dataset.enabled = enabled ? '1' : '0';
    button.setAttribute('aria-pressed', enabled ? 'true' : 'false');
    button.textContent = reminderLabel(enabled);
  };
  all('[data-lsch-reminder]').forEach((button) => {
    // A missing preference is disabled. Never guess from a hard-coded HTML attribute.
    button.disabled = true;
    (async () => {
      if (!app.loggedIn) return;
      try {
        const saved = await api(`course/${button.dataset.lschReminder}/reminder`);
        setReminderButton(button, Number(saved.enabled || 0) === 1);
        button.disabled = false;
      } catch (error) { show(button, error.message, false); }
    })();
    button.addEventListener('click', async () => {
      if (!requireLogin()) return;
      button.disabled = true;
      try {
        // Re-read before changing: another device may have changed this preference.
        const previous = await api(`course/${button.dataset.lschReminder}/reminder`);
        const desired = Number(previous.enabled || 0) !== 1;
        const data = await api(`course/${button.dataset.lschReminder}/reminder`, {
          method: 'PUT',
          body: JSON.stringify({ enabled: desired, cadence: previous.cadence || 'weekly', quiet_hours: { start: '21:00', end: '08:00' } }),
        });
        setReminderButton(button, Number(data.enabled || 0) === 1);
        show(button, app.strings?.saved || 'Saved');
      } catch (error) { show(button, error.message, false); } finally { button.disabled = false; }
    });
  });

  all('[data-lsch-progress]').forEach((button) => button.addEventListener('click', async () => {
    if (!requireLogin()) return;
    button.disabled = true;
    try {
      // Read the current optimistic-lock version; never send an implicit zero on existing rows.
      const current = await api(`lesson/${button.dataset.lschProgress}/progress`);
      if (current.state === 'completed') {
        button.setAttribute('aria-pressed', 'true');
        show(button, app.strings?.alreadyCompleted || 'Lesson already completed');
        return;
      }
      const data = await api(`lesson/${button.dataset.lschProgress}/progress`, {
        method: 'PUT',
        body: JSON.stringify({ complete: true, version: Number(current.version || 0) }),
      });
      button.setAttribute('aria-pressed', data.state === 'completed' ? 'true' : 'false');
      show(button, `${app.strings?.saved || 'Saved'} — ${Number(data.percent || 0)}%`);
    } catch (error) { show(button, error.message, false); } finally { button.disabled = false; }
  }));

  all('[data-lsch-bookmark]').forEach((button) => button.addEventListener('click', async () => {
    if (!requireLogin()) return;
    button.disabled = true;
    try {
      const data = await api(`object/${button.dataset.lschType}/${button.dataset.lschBookmark}/bookmark`, { method: 'PUT', body: '{}' });
      button.setAttribute('aria-pressed', data.active ? 'true' : 'false');
      show(button, app.strings?.saved || 'Saved');
    } catch (error) { show(button, error.message, false); } finally { button.disabled = false; }
  }));

  all('[data-lsch-note]').forEach(async (area) => {
    if (!app.loggedIn) return;
    try {
      const data = await api(`lesson/${area.dataset.lschNote}/note`);
      area.value = data.note || '';
      area.dataset.version = String(data.version || 0);
    } catch (error) { /* private note loading fails silently without exposing detail */ }
  });

  all('[data-lsch-save-note]').forEach((button) => button.addEventListener('click', async () => {
    if (!requireLogin()) return;
    const area = one(`[data-lsch-note="${button.dataset.lschSaveNote}"]`);
    if (!area) return;
    button.disabled = true;
    try {
      const data = await api(`lesson/${button.dataset.lschSaveNote}/note`, { method: 'PUT', body: JSON.stringify({ note: area.value, version: Number(area.dataset.version || 0) }) });
      area.dataset.version = String(data.version || 0);
      show(button, app.strings?.saved || 'Saved');
    } catch (error) { show(button, error.message, false); } finally { button.disabled = false; }
  }));

  all('[data-lsch-assessment]').forEach((form) => form.addEventListener('submit', async (event) => {
    event.preventDefault(); if (!requireLogin()) return;
    const button = one('button[type="submit"]', form); button.disabled = true;
    const answers = {};
    all('fieldset', form).forEach((fieldset, index) => {
      const selected = one('input[type="radio"]:checked', fieldset);
      if (selected) answers[index] = selected.value;
    });
    try {
      const data = await api(`assessment/${form.dataset.lschAssessment}/submit`, { method: 'POST', headers: { 'Idempotency-Key': idempotency() }, body: JSON.stringify({ answers }) });
      show(form, `${app.strings?.submitted || 'Submitted'} — ${Number(data.score || 0)}%`);
      form.querySelectorAll('input,button').forEach((control) => { control.disabled = true; });
    } catch (error) { show(form, error.message, false); button.disabled = false; }
  }));

  all('[data-lsch-assignment]').forEach((form) => form.addEventListener('submit', async (event) => {
    event.preventDefault(); if (!requireLogin()) return;
    const button = one('button[type="submit"]', form); const body = one('textarea[name="body"]', form);
    button.disabled = true;
    try {
      await api(`assignment/${form.dataset.lschAssignment}/submit`, { method: 'POST', body: JSON.stringify({ body: body.value, attachments: [] }) });
      show(form, app.strings?.submitted || 'Submitted');
      body.disabled = true; button.disabled = true;
    } catch (error) { show(form, error.message, false); button.disabled = false; }
  }));
})();
