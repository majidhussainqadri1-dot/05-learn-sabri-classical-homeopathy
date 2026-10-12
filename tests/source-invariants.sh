#!/usr/bin/env bash
set -euo pipefail
root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$root"

python3 tests/static-invariants.py
python3 tests/future18-invariants.py
php tests/unit-policy.php
php tests/security-invariants.php
php tests/future18-score-safety.php
php tests/future18-privacy-contract.php
php tests/public-visibility-version.php
php tests/access-governance-contract.php
php tests/core-privacy-erasure.php
php tests/core-privacy-projection.php
php tests/cross-owner-visibility.php
php tests/parent-access-consent.php
php tests/queue-and-consent-retention.php
php tests/prerequisite-integrity.php
php tests/lesson-rest-cache.php
php tests/note-rotation-db-errors.php
php tests/consent-history-protection.php
php tests/enrollment-postwrite-verification.php
php tests/catalog-taxonomy-failure.php
php tests/saved-search-read-failure.php
php tests/system-health-db-failure.php
php tests/learning-requirement-failclosed.php
php tests/current-competency-evidence.php
php tests/course-analytics-db-failclosed.php
php tests/ninth-private-note-assessment-read.php
php tests/ninth-correction-completion-db.php
php tests/ninth-reconcile-failclosed.php
php tests/tenth-dashboard-projection-db.php
php tests/eleventh-postwrite-verified.php
php tests/eleventh-private-read-guards.php
php tests/twelfth-owner-ledger-failures.php
php tests/twelfth-corrections-idempotency-db.php
php tests/twelfth-future18-private-projections.php
node --check 05-learn-sabri-classical-homeopathy/assets/js/learning.js

find 05-learn-sabri-classical-homeopathy -type f -name '*.php' -print0 \
  | sort -z \
  | xargs -0 -n1 php -l >/tmp/file05-php-lint.txt
cat /tmp/file05-php-lint.txt

if grep -RInE -- '-----BEGIN (RSA |EC |OPENSSH |DSA )?PRIVATE KEY-----|AKIA[0-9A-Z]{16}|ghp_[A-Za-z0-9]{30,}' \
  05-learn-sabri-classical-homeopathy tests scripts .github 2>/dev/null; then
  echo 'ERROR: public repository secret pattern detected' >&2
  exit 1
fi

if find . -type f \( -name '*.pyc' -o -name '*.pyo' \) -print -quit | grep -q . || find . -type d -name '__pycache__' -print -quit | grep -q .; then
  echo 'ERROR: generated Python cache artifact committed to repository' >&2
  exit 1
fi

if find 05-learn-sabri-classical-homeopathy -type l -print -quit | grep -q .; then
  echo 'ERROR: symlink in canonical plugin source' >&2
  exit 1
fi

echo 'PASS: File 05 current-plan source integrity.'
