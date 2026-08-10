#!/usr/bin/env bash
set -euo pipefail
root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$root"

python3 tests/static-invariants.py
python3 tests/future18-invariants.py
php tests/unit-policy.php
php tests/security-invariants.php
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

if find 05-learn-sabri-classical-homeopathy -type l -print -quit | grep -q .; then
  echo 'ERROR: symlink in canonical plugin source' >&2
  exit 1
fi

echo 'PASS: File 05 current-plan source integrity.'
