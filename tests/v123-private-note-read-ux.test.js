/* UX-03 source regression: never turn a failed private-note read into an editable blank draft. */
'use strict';
const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const source = fs.readFileSync('05-learn-sabri-classical-homeopathy/assets/js/learning.js', 'utf8');

async function scenario(readSucceeds) {
  const status = { textContent: '', dataset: {} };
  const events = {};
  const area = {
    dataset: { lschNote: '17' }, value: '', readOnly: false,
    closest() { return { querySelector() { return status; } }; }
  };
  const save = {
    dataset: { lschSaveNote: '17' }, disabled: false,
    addEventListener(name, callback) { events[name] = callback; },
    closest() { return { querySelector() { return status; } }; }
  };
  const calls = [];
  const document = {
    querySelectorAll(selector) {
      if (selector === '[data-lsch-note]') return [area];
      if (selector === '[data-lsch-save-note]') return [save];
      return [];
    },
    querySelector(selector) {
      return selector === '[data-lsch-note="17"]' || selector === '[data-lsch-save-note="17"]'
        ? (selector.startsWith('[data-lsch-note') ? area : save) : null;
    }
  };
  const window = {
    LSCH_APP: { loggedIn: true, root: '/wp-json/file05/v2/', nonce: 'test', strings: {} },
    location: { href: '' }, crypto: { randomUUID: () => 'test-idempotency-00017' }
  };
  async function fetch(url, options = {}) {
    const method = options.method || 'GET';
    calls.push({ url, method, payload: options.body ? JSON.parse(options.body) : null });
    if (method === 'GET') {
      return readSucceeds
        ? { ok: true, json: async () => ({ note: 'Previously saved note', version: 8 }), headers: { get: () => null } }
        : { ok: false, json: async () => ({ message: 'Stored private note was not readable' }), headers: { get: () => null } };
    }
    assert.strictEqual(method, 'PUT');
    assert.strictEqual(options.headers['Idempotency-Key'], 'test-idempotency-00017');
    return { ok: true, json: async () => ({ version: 9 }), headers: { get: () => null } };
  }
  vm.runInNewContext(source, { document, window, fetch, console, setTimeout, clearTimeout });
  assert.strictEqual(save.disabled, true, 'Save is gated before GET resolves.');
  assert.strictEqual(area.readOnly, true, 'The field is read-only until GET resolves.');
  await new Promise(resolve => setImmediate(resolve));
  if (readSucceeds) {
    assert.strictEqual(area.dataset.loaded, '1');
    assert.strictEqual(area.value, 'Previously saved note');
    assert.strictEqual(area.dataset.version, '8');
    assert.strictEqual(area.readOnly, false);
    assert.strictEqual(save.disabled, false);
    area.value = 'Edited safely';
    await events.click();
    assert.strictEqual(calls.filter(x => x.method === 'PUT').length, 1);
    assert.strictEqual(calls.find(x => x.method === 'PUT').payload.version, 8);
    assert.strictEqual(area.dataset.version, '9');
  } else {
    assert.strictEqual(area.dataset.loaded, '0');
    assert.strictEqual(area.readOnly, true);
    assert.strictEqual(save.disabled, true);
    assert.strictEqual(status.dataset.state, 'error');
    assert.match(status.textContent, /could not be loaded/i);
    await events.click();
    assert.strictEqual(calls.filter(x => x.method === 'PUT').length, 0,
      'Synthetic save attempts after failed load must not write.');
  }
}
(async () => {
  await scenario(false);
  await scenario(true);
  console.log('PASS: File05 V12.3 private-note read fail-closed and valid-version save.');
})().catch(error => { console.error(error); process.exit(1); });
