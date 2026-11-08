/* Real client code executed against a minimal deterministic DOM and REST contract.
 * Unit/browser harness only; NOT a replacement for WordPress staging or accessibility audits.
 */
'use strict';
const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const js = fs.readFileSync('05-learn-sabri-classical-homeopathy/assets/js/future18.js', 'utf8');
let keyNumber = 0;
class Node {
  constructor(tag) {
    this.tagName = String(tag).toUpperCase();
    this.children = [];
    this.listeners = {};
    this.attributes = {};
    this.dataset = {};
    this.type = '';
    this.name = '';
    this.value = '';
    this.disabled = false;
    this.textContent = '';
    this.parent = null;
    this.maxLength = 0;
  }
  appendChild(child) { child.parent = this; this.children.push(child); return child; }
  removeChild(child) { const index = this.children.indexOf(child); if (index !== -1) this.children.splice(index, 1); child.parent = null; return child; }
  remove() { if (this.parent) this.parent.removeChild(this); }
  get firstChild() { return this.children[0] || null; }
  setAttribute(name, value) { this.attributes[name] = String(value); }
  getAttribute(name) { return this.attributes[name] ?? null; }
  hasAttribute(name) { return Object.prototype.hasOwnProperty.call(this.attributes, name); }
  addEventListener(type, callback) { this.listeners[type] = callback; }
  matches(selector) {
    if (selector === '[type="submit"]') return this.type === 'submit';
    if (selector === '[data-lsch-history-more]') return Boolean(this.dataset.lschHistoryMore);
    if (selector === 'textarea') return this.tagName === 'TEXTAREA';
    if (selector === 'button') return this.tagName === 'BUTTON';
    if (selector === 'input[type="radio"]') return this.tagName === 'INPUT' && this.type === 'radio';
    return false;
  }
  querySelector(selector) { return this.descendants().find(x => x.matches(selector)) || null; }
  querySelectorAll(query) { const selectors = query.split(',').map(x => x.trim()); return this.descendants().filter(x => selectors.some(y => x.matches(y))); }
  descendants() { return this.children.flatMap(child => [child, ...child.descendants()]); }
}
const root = new Node('section'), tool = new Node('select'), panel = new Node('div'), status = new Node('p');
tool.value = 'F05-FUT-01';
root.appendChild(tool); root.appendChild(panel); root.appendChild(status);
root.querySelector = (selector) => ({
  '[data-lsch-f18-tool]': tool, '[data-lsch-f18-panel]': panel, '[data-lsch-f18-status]': status
})[selector] || null;
const document = {
  querySelectorAll(selector) { return selector === '[data-lsch-f18-workbench]' ? [root] : []; },
  createElement(tag) { return new Node(tag); },
};
const labSlug = {
  4: 'case-simulation', 5: 'remedy-differentiation', 6: 'case-taking',
  7: 'repertory-reasoning', 8: 'clinical-reasoning-map', 9: 'viva',
  10: 'osce', 13: 'evidence-appraisal'
};
const endpoints = [];
let viewer = 26;
let mentorshipRecords = [{
  id: 15, mentor_id: 25, learner_id: 26, status: 'active', version: 8, created_at: '2026-11-08 00:00:00'
}];
function response(data, statusCode = 200) {
  return { ok: statusCode < 400, status: statusCode, json: async () => data };
}
async function fetch(url, options = {}) {
  const method = String(options.method || 'GET').toUpperCase();
  if (method !== 'GET') {
    assert.ok(options.headers && /^[A-Za-z0-9._:-]{12,128}$/.test(options.headers['Idempotency-Key'] || ''),
      'Every Future18 mutation must have an idempotency key');
  }
  const path = url.replace(/^\/wp-json\/learn-sabri-classical-homeopathy\/v2\//, '');
  const body = options.body ? JSON.parse(options.body) : null;
  endpoints.push({ path, method, body });
  if (path === 'future18/mastery') return response({ summary: { confidence: 50 }, competencies: [] });
  if (path.startsWith('future18/review-queue')) return response([]);
  if (path.startsWith('future18/mistakes')) return response([]);
  if (path.startsWith('future18/portfolio')) return response([]);
  if (path.startsWith('future18/change-impact')) return response([]);
  if (path.startsWith('future18/learning-path')) return response({ goal: body?.goal || 'balanced_mastery', recommendations: [] });
  if (path === 'future18/cpd' && method === 'GET') return response([]);
  if (path === 'future18/mentorship') return response(mentorshipRecords);
  if (path === 'future18/practice/pending?limit=10') return response([
    { id: 42, public_id: 'simulated-learning-response-42', user_id: 9, mode: 'viva',
      version: 3, blueprint_version: 2, response_json: '{"reasoning":"a simulated academic case"}',
      status: 'awaiting_assessor' }
  ]);
  if (path === 'future18/practice/42/grade' && method === 'PUT') {
    assert.strictEqual(body.version, 3, 'Human assessor must provide exact optimistic-lock revision');
    assert.strictEqual(body.score, 75);
    assert.strictEqual(body.feedback.text, 'Good reasoning with two learning gaps');
    return response({ status: 'graded', version: 4 });
  }
  if (path.includes('/history?limit=50')) {
    const older = path.includes('&before=51');
    const items = older
      ? Array.from({ length: 3 }, (_, index) => ({ sequence_no: 50 - index, feedback_json: '{}' }))
      : Array.from({ length: 50 }, (_, index) => ({ sequence_no: 100 - index, feedback_json: '{}' }));
    return response(items);
  }
  if (/^future18\/lab\/[a-z-]+\/blueprint\?lesson_id=7$/.test(path))
    return response({ lesson_id: 7, instructions: 'Educational scenario only', fields: [{ key: 'reasoning', prompt: 'Explain simulated reasoning' }], manual_assessment: path.includes('/osce/') || path.includes('/viva/') });
  if (/^future18\/lab\/[a-z-]+$/.test(path) && method === 'POST') {
    assert.strictEqual(body.source_type, 'lesson');
    assert.strictEqual(body.source_id, 7);
    assert.strictEqual(body.response.reasoning, 'Simulated reasoning');
    return response({ status: 'awaiting_assessor', public_id: 'simulated-uuid' });
  }
  throw new Error('Unexpected REST request: ' + method + ' ' + path);
}
const window = {
  LSCH_F18_APP: { root: '/wp-json/learn-sabri-classical-homeopathy/v2/', nonce: 'unit-nonce', viewer_id: viewer },
  crypto: { randomUUID: () => 'test-key-' + String(++keyNumber).padStart(8, '0') }
};
vm.runInNewContext(js, { document, window, fetch, console, Number, String, Array, Object });
const textContains = (node, expected) => node.descendants().some(x => typeof x.textContent === 'string' && x.textContent.includes(expected));
const findInput = (node, name) => node.descendants().find(x => x.name === name);
const click = async (node, event) => {
  assert.ok(node.listeners[event], 'Missing ' + event + ' listener');
  return node.listeners[event]({ preventDefault() {} });
};
(async () => {
  await new Promise(resolve => setImmediate(resolve));
  assert.ok(textContains(panel, 'My competency mastery'), 'Initial mastery feature has not rendered');
  for (let id = 1; id <= 18; id++) {
    tool.value = 'F05-FUT-' + String(id).padStart(2, '0');
    await click(tool, 'change');
    assert.ok(panel.children.length > 0, 'Future18 mode has no rendered UI: ' + id);
    assert.strictEqual(status.textContent, '', 'Future18 mode errored: ' + id + ' ' + status.textContent);
    if (labSlug[id]) {
      assert.ok(textContains(panel, 'Governed practice'), 'Lab screen missing for ' + id);
      const lesson = findInput(panel, 'lesson_id');
      assert.ok(lesson, 'Lab requires lesson ID');
      lesson.value = '7';
      const forms = panel.descendants().filter(x => x.tagName === 'FORM');
      assert.ok(forms.length, 'Lab must expose a request form');
      await click(forms[0], 'submit');
      const reasoning = findInput(panel, 'reasoning');
      assert.ok(reasoning, 'Approved blueprint was not projected as an answer form');
      reasoning.value = 'Simulated reasoning';
      const answerForm = panel.descendants().filter(x => x.tagName === 'FORM')[1];
      await click(answerForm, 'submit');
      const submission = endpoints.find(x => x.method === 'POST' && x.path === 'future18/lab/' + labSlug[id]);
      assert.ok(submission, 'Lab REST submission not sent for ' + id);
    }
    if (id === 15) {
      assert.ok(textContains(panel, 'Active and historical mentorship'));
      assert.ok(!textContains(panel, 'Record mentoring feedback'), 'Learner must not see mentor-only write form');
      const open = panel.descendants().find(x => x.tagName === 'BUTTON' && x.textContent === 'View mentoring feedback');
      assert.ok(open, 'Learner can view their own mentoring history');
      await click(open, 'click');
      assert.ok(textContains(panel, 'Load older feedback'), 'Exactly-50 mentoring page must expose older cursor');
      const more = panel.descendants().find(x => x.tagName === 'BUTTON' && x.textContent === 'Load older feedback');
      await click(more, 'click');
      assert.ok(endpoints.some(x => x.path.includes('&before=51')), 'Older history cursor was not applied');
    }
  }

  // Assessor-only queue is not mounted for a learner; exercise the qualified
  // role UI separately and prove that each grading request is revisioned.
  window.LSCH_F18_APP.can_assess = true;
  tool.value = 'F05-FUT-09';
  await click(tool, 'change');
  assert.ok(textContains(panel, 'Assigned assessor work queue'), 'Qualified viva assessor work area must be visible.');
  const queueButton = panel.descendants().find(x => x.tagName === 'BUTTON' && x.textContent === 'Load my pending assigned assessments');
  assert.ok(queueButton, 'Assessor requires an explicit private queue loading action');
  await click(queueButton, 'click');
  assert.ok(textContains(panel, 'Read the complete submitted educational response'), 'Private learning evidence must be accessible to the assigned assessor.');
  const score = findInput(panel, 'score');
  const feedback = findInput(panel, 'feedback');
  assert.ok(score && feedback, 'Independent assessor score and evidence forms must exist.');
  score.value = '75';
  feedback.value = 'Good reasoning with two learning gaps';
  const assessorForm = score.parent.parent;
  await click(assessorForm, 'submit');
  assert.ok(endpoints.some(x => x.method === 'PUT' && x.path === 'future18/practice/42/grade'),
    'Human viva grading must use the authenticated REST endpoint, not local auto-scoring.');
  window.LSCH_F18_APP.can_assess = false;
  assert.strictEqual(Object.keys(labSlug).length, 8, 'Expected eight governed educational labs');
  assert.strictEqual(endpoints.filter(x => x.method === 'POST' && x.path.startsWith('future18/lab/')).length, 8,
    'All eight lab submission buttons must reach their actual routes');
  console.log('PASS: 18 interactive modes, eight blueprint-to-submit labs, learner-only mentorship UI and 2-page history cursor.');
})().catch(error => { console.error(error); process.exit(1); });
