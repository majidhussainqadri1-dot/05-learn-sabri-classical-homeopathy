#!/usr/bin/env python3
from pathlib import Path
p = Path(__file__).with_name('review80-cycle4-20260811.py')
s = p.read_text(encoding='utf-8')
old = "replace_method(SERVICES, 'recalculate_completion', new_recalc, 8, 'Completion evidence was inserted before enrollment completion without an optimistic state/version guard, and its failure result was ignored by progress(). Completion now prechecks enrollment, uses an expected-version state transition, propagates failures, and relies on the mutation transaction to roll back split state.')"
new = "replace_method(SERVICES, 'recalculate_completion', new_recalc, 8, 'Completion evidence was inserted before enrollment completion without an optimistic state/version guard, and its failure result was ignored by progress(). Completion now prechecks enrollment, uses an expected-version state transition, propagates failures, and relies on the mutation transaction to roll back split state.', visibility='private static')"
if s.count(old) != 1:
    raise SystemExit('cycle4 recalculate_completion transport pattern mismatch')
p.write_text(s.replace(old, new, 1), encoding='utf-8', newline='\n')
print('Cycle4 private completion-method transport corrected.')
