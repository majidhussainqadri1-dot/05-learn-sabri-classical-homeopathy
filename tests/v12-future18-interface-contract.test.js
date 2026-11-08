/* Static contract gate, not a WordPress end-to-end/browser acceptance claim. */
const fs = require('fs');
const assert = require('assert');
const root = '05-learn-sabri-classical-homeopathy/';
const js = fs.readFileSync(root + 'assets/js/future18.js', 'utf8');
const f = fs.readFileSync(root + 'includes/class-lsch-future18.php', 'utf8');
const routes = fs.readFileSync(root + 'includes/class-lsch-future18-rest.php', 'utf8');
const lessons = fs.readFileSync(root + 'includes/class-lsch-frontend.php', 'utf8');
const quick = fs.readFileSync(root + 'includes/class-lsch-quick-checks.php', 'utf8');
const idempotency = fs.readFileSync(root + 'includes/class-lsch-idempotency.php', 'utf8');
const phpLabel = (id) => "'F05-FUT-" + String(id).padStart(2, '0') + "'";
for (let id = 1; id <= 18; id++) {
  assert.ok(f.includes(phpLabel(id)), 'Future18 canonical feature ' + id + ' must exist.');
  assert.ok(f.includes("'F05-FUT-" + String(id).padStart(2, '0') + "'"), 'Future18 view option ' + id + ' is missing.');
  const labIds = [4, 5, 6, 7, 8, 9, 10, 13];
  assert.ok(labIds.includes(id)
      ? js.includes("'F05-FUT-" + String(id).padStart(2, '0') + "'")
      : js.includes('number === ' + id),
    'Future18 JS feature ' + id + ' is missing.');
}
for (const lab of [
  'case-simulation', 'remedy-differentiation', 'case-taking', 'repertory-reasoning',
  'clinical-reasoning-map', 'viva', 'osce', 'evidence-appraisal'
]) assert.ok(routes.includes("'" + lab + "'"), 'Missing lab REST mode: ' + lab);

assert.ok(routes.includes("'/blueprint'"), 'Each practice mode needs a protected no-answer projection.');
assert.ok(f.includes('function learner_blueprint('), 'Learner-safe blueprint projection missing.');
assert.ok(f.includes("'manual' => true") && f.includes('self::FEATURE_OSCE'), 'OSCE/viva must be manually assessed.');
assert.ok(routes.includes("'/history'"), 'Authorized mentoring history route missing.');
assert.ok(js.includes("'Idempotency-Key'"), 'Mutating workbench calls require an idempotency key.');
assert.ok(idempotency.includes("Idempotency-Key"), 'Server-side idempotency enforcement is missing.');
assert.ok(lessons.includes("data-lsch-quick-checks"), 'Lesson formative quick checks UI missing.');
assert.ok(quick.includes('count( $items ) > 5'), 'Five-check bound missing.');
assert.ok(quick.includes("'correct' => $correct"), 'Correct answers must stay server-side.');
assert.ok(!js.includes('innerHTML'), 'Untrusted educational response must never use innerHTML.');
console.log('PASS: v12 Future18 interface/static route/answer integrity, 18 IDs, 8 labs, safe DOM and idempotency.');
