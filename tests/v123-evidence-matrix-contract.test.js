'use strict';
/**
 * File05 V12.3 P01 evidence inventory integrity — NOT implementation acceptance.
 * A requirement row or passing code test does not prove deployment parity.
 */
const fs = require('fs');
const assert = require('assert');
const matrix = fs.readFileSync('V123-REQUIREMENT-EVIDENCE-MATRIX.md', 'utf8');
const rows = matrix.split(/\r?\n/).filter(line => /^\|\s*F05-(?:FR|NFR|FUT|CEN)-\d{2,3}\s*\|/.test(line));
const ids = rows.map(x => x.split('|')[1].trim());
const expected = [
  ...Array.from({ length: 18 }, (_, i) => 'F05-FR-' + String(i + 1).padStart(3, '0')),
  ...Array.from({ length: 10 }, (_, i) => 'F05-NFR-' + String(i + 1).padStart(3, '0')),
  ...Array.from({ length: 7 }, (_, i) => 'F05-CEN-' + String(i + 1).padStart(2, '0')),
  ...Array.from({ length: 18 }, (_, i) => 'F05-FUT-' + String(i + 1).padStart(2, '0'))
];
assert.strictEqual(rows.length, expected.length, 'Combined source register must contain 53 rows');
assert.strictEqual(new Set(ids).size, 53, 'No duplicate File05 requirement IDs');
for (const id of expected) assert.ok(ids.includes(id), 'Missing governed requirement ' + id);
for (const line of rows) {
  const parts = line.split('|').slice(1, -1).map(s => s.trim());
  assert.ok(parts.length >= 5, 'Requirement missing status/evidence/remaining gate: ' + line);
  assert.ok(parts[1].length > 3 && parts[2].length > 3 && parts[3].length > 3,
    'Requirement must retain specific description/source/gap, not filler');
  assert.ok(/acceptance pending/i.test(parts[4]), 'Source evidence must not be mislabeled accepted/live: ' + parts[0]);
}
for (let n = 130; n <= 141; n++) assert.ok(matrix.includes('CV-' + n), 'Missing canonical CV cross-reference ' + n);
assert.match(matrix, /48 IDs were located directly in V12\.3/, 'Distinguish 48 direct and 5 older supplements');
assert.match(matrix, /0\/53 independently established as Live-verified/, 'Do not imply production acceptance');
for (const file of [
  'V123-P00-LIVE-REALITY-FREEZE-RUNBOOK.md',
  'V123-DEFECT-AND-REVIEW-EVIDENCE-LEDGER.md',
  'V123-ACADEMIC-CURRICULUM-BLUEPRINT-DRAFT.md',
  'V123-FILE16-SOCRATIC-TUTOR-OWNER-CONTRACT.md',
  'tests/v123-file16-socratic-owner.php',
  'tests/v123-file26-public-version.php',
  'tests/v12-file06-approved-links.php',
  'tests/v123-upgrade-verify-v410.php',
  '.github/workflows/file05-v123-real-file26-contract-smoke.yml',
  '.github/workflows/file05-v123-real-file06-contract-smoke.yml',
  '.github/workflows/file05-v123-disposable-upgrade.yml'
]) assert.ok(fs.existsSync(file), 'Missing evidence artifact ' + file);
console.log('PASS: File05 V12.3 48+5=53 requirement IDs and 12 CV mappings preserved; no live-accepted misrepresentation.');
