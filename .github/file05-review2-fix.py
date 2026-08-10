#!/usr/bin/env python3
from pathlib import Path
import re


def read(path):
    return Path(path).read_text(encoding='utf-8')


def write(path, text):
    Path(path).write_text(text, encoding='utf-8')

# Remove the now-unrouted legacy service method so no companion can bypass the
# governed proposal/review/apply correction state machine by calling it directly.
services_path = '05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php'
services = read(services_path)
pattern = re.compile(r"\n\tpublic static function mark_content_corrected\(.*?\n\t}\n\n(?=\tpublic static function assign_staff)", re.S)
services, count = pattern.subn('\n', services, count=1)
if count != 1:
    raise SystemExit(f'legacy correction service: expected one match, found {count}')
write(services_path, services)

# Round-2 exact-source tests: idempotency must execute after permission checks,
# handle WP_Error responses safely, and the bypass method must be absent.
static_path = 'tests/static-invariants.py'
static = read(static_path)
static = static.replace("    'rest_pre_dispatch',\n    'rest_post_dispatch',\n", "    'rest_request_before_callbacks',\n    'rest_request_after_callbacks',\n", 1)
needle = "    'lsch_idempotency_payload_conflict',\n"
if needle not in static:
    raise SystemExit('idempotency invariant insertion point missing')
static = static.replace(needle, needle + "    'JSON_INVALID_UTF8_SUBSTITUTE',\n    'error_code',\n", 1)
insert = """
idempotency_source = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-idempotency.php', '')
if 'rest_pre_dispatch' in idempotency_source or 'rest_post_dispatch' in idempotency_source:
    errors.append('Idempotency guard still executes before REST permission callbacks.')
if 'is_wp_error( $response )' not in idempotency_source or 'response_status( $response )' not in idempotency_source:
    errors.append('Idempotency response finalization is not WP_Error-safe.')
services_source = files.get('05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php', '')
if 'mark_content_corrected' in services_source:
    errors.append('Legacy direct correction service bypass remains callable.')
"""
if '\nif errors:\n' not in static:
    raise SystemExit('static invariant footer missing')
static = static.replace('\nif errors:\n', insert + '\nif errors:\n', 1)
write(static_path, static)

Path('.github/file05-review2-fix.py').unlink()
print('Applied File 05 review round 2 source corrections.')
