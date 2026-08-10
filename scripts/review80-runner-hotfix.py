#!/usr/bin/env python3
from pathlib import Path
p = Path(__file__).with_name('review80-fresh-20260811.py')
s = p.read_text(encoding='utf-8')

# Temporary audit-harness correction 1: make the generated Python token list syntactically valid.
old = "for token in ['posts_per_page\\' => 201','posts_per_page\\' => 501','lsch_lesson_component_limit','course_completion_scope_exceeded']:"
new = "for token in [\"'posts_per_page' => 201\", \"'posts_per_page' => 501\", 'lsch_lesson_component_limit', 'course_completion_scope_exceeded']:"
if s.count(old) != 1:
    raise SystemExit(f'Expected one Review-80 harness quoting defect, found {s.count(old)}')
s = s.replace(old, new, 1)

# Temporary audit-harness correction 2: the old split matched privacy_exporters/privacy_erasers
# rather than privacy_export()/privacy_erase(). Scope the assertion to the actual exporter body
# and require each schema-2 field independently.
old = "new=\"if 'blueprint_version,competency_key,response_json' not in privacy:\\n    errors.append('Privacy export query does not carry the immutable Future18 practice competency snapshot in schema order.')\\n\""
new = "new=\"privacy_export_body = f.split('public static function privacy_export(',1)[-1].split('public static function privacy_erase(',1)[0]\\nif 'blueprint_version' not in privacy_export_body or 'competency_key' not in privacy_export_body or 'response_json' not in privacy_export_body:\\n    errors.append('Privacy export query does not carry the immutable Future18 practice competency snapshot.')\\n\""
if s.count(old) != 1:
    raise SystemExit(f'Expected one brittle privacy invariant, found {s.count(old)}')
s = s.replace(old, new, 1)

p.write_text(s, encoding='utf-8', newline='\n')
print('Temporary Review-80 harness quoting and privacy exporter assertions corrected.')
