#!/usr/bin/env python3
from pathlib import Path
p = Path(__file__).with_name('review80-fresh-20260811.py')
s = p.read_text(encoding='utf-8')
old = "for token in ['posts_per_page\\' => 201','posts_per_page\\' => 501','lsch_lesson_component_limit','course_completion_scope_exceeded']:"
new = "for token in [\"'posts_per_page' => 201\", \"'posts_per_page' => 501\", 'lsch_lesson_component_limit', 'course_completion_scope_exceeded']:"
if s.count(old) != 1:
    raise SystemExit(f'Expected one Review-80 harness quoting defect, found {s.count(old)}')
p.write_text(s.replace(old, new, 1), encoding='utf-8', newline='\n')
print('Temporary Review-80 harness quoting corrected.')
