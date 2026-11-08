/* Actual learning.js click contract: respect current revision and persisted reminder state. */
const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const source = fs.readFileSync('05-learn-sabri-classical-homeopathy/assets/js/learning.js', 'utf8');
const events = {};
const buttons = {
  progress: button({ lschProgress: '7' }, 'progress'),
  reminder: button({ lschReminder: '9' }, 'reminder')
};
function button(dataset, key) {
  return {
    dataset, disabled: false, textContent: '', attributes: {}, label: key,
    addEventListener(event, callback) { events[key + ':' + event] = callback; },
    setAttribute(key, value) { this.attributes[key] = value; },
    closest() { return { querySelector() { return { textContent: '', dataset: {} }; } }; }
  };
}
let progress = { version: 7, state: 'in_progress', percent: 40 };
let reminder = { version: 8, enabled: 1, cadence: 'weekly' };
const calls = [];
async function fetch(url, options = {}) {
  const method = options.method || 'GET';
  calls.push({ url, method, body: options.body ? JSON.parse(options.body) : null });
  let data;
  if (url.includes('/lesson/7/progress')) {
    if (method === 'GET') data = progress;
    else {
      assert.strictEqual(options.body && JSON.parse(options.body).version, progress.version, 'Lost optimistic-lock token.');
      progress = { version: progress.version + 1, state: 'completed', percent: 100 };
      data = progress;
    }
  } else if (url.includes('/course/9/reminder')) {
    if (method === 'GET') data = reminder;
    else {
      reminder = { ...reminder, enabled: Number(Boolean(JSON.parse(options.body).enabled)), version: reminder.version + 1 };
      data = reminder;
    }
  } else throw new Error('Unexpected request ' + url);
  return { ok: true, json: async () => data, headers: { get: () => null } };
}
const document = {
  querySelectorAll(selector) {
    if (selector === '[data-lsch-progress]') return [buttons.progress];
    if (selector === '[data-lsch-reminder]') return [buttons.reminder];
    return [];
  },
  querySelector() { return null; }
};
const window = {
  LSCH_APP: { loggedIn: true, root: '/wp-json/learn-sabri-classical-homeopathy/v2/', nonce: 'test', strings: {} },
  location: { href: '' },
  crypto: { randomUUID: () => 'v12-test-id' }
};
vm.runInNewContext(source, { document, window, fetch, console });
const tick = () => new Promise(resolve => setImmediate(resolve));
(async () => {
  await tick();
  assert.strictEqual(buttons.reminder.attributes['aria-pressed'], 'true', 'The visible reminder must reflect the persisted setting.');
  assert.strictEqual(buttons.reminder.dataset.enabled, '1');
  await events['reminder:click']();
  assert.strictEqual(reminder.enabled, 0, 'Click must disable a previously enabled reminder.');
  assert.strictEqual(buttons.reminder.attributes['aria-pressed'], 'false');
  await events['progress:click']();
  const put = calls.filter(c => c.url.includes('/progress') && c.method === 'PUT');
  assert.strictEqual(put.length, 1, 'Progress should be written once.');
  assert.strictEqual(put[0].body.version, 7, 'The actual revision must be supplied.');
  await events['progress:click']();
  assert.strictEqual(calls.filter(c => c.url.includes('/progress') && c.method === 'PUT').length, 1, 'Reopening a completed lesson must not add a write.');
  console.log('PASS: v12 progress version, completed replay and reminder state UI.');
})().catch(error => { console.error(error); process.exit(1); });
