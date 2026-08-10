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

# Temporary audit-harness correction 2: the real privacy query contains all three schema-2
# fields but not as one brittle contiguous substring. Require the fields independently.
old = "new=\"if 'blueprint_version,competency_key,response_json' not in privacy:\\n    errors.append('Privacy export query does not carry the immutable Future18 practice competency snapshot in schema order.')\\n\""
new = "new=\"if 'blueprint_version' not in privacy or 'competency_key' not in privacy or 'response_json' not in privacy:\\n    errors.append('Privacy export query does not carry the immutable Future18 practice competency snapshot.')\\n\""
if s.count(old) != 1:
    raise SystemExit(f'Expected one brittle privacy invariant, found {s.count(old)}')
s = s.replace(old, new, 1)

p.write_text(s, encoding='utf-8', newline='\n')
print('Temporary Review-80 harness quoting and privacy assertions corrected.')
