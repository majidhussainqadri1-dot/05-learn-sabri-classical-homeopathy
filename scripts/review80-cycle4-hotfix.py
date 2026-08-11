#!/usr/bin/env python3
from pathlib import Path
p = Path(__file__).with_name('review80-cycle4-20260811.py')
s = p.read_text(encoding='utf-8')
old = "replace_method(SERVICES, 'recalculate_completion', new_recalc, 8, 'Completion evidence was inserted before enrollment completion without an optimistic state/version guard, and its failure result was ignored by progress(). Completion now prechecks enrollment, uses an expected-version state transition, propagates failures, and relies on the mutation transaction to roll back split state.')"
new = "replace_method(SERVICES, 'recalculate_completion', new_recalc, 8, 'Completion evidence was inserted before enrollment completion without an optimistic state/version guard, and its failure result was ignored by progress(). Completion now prechecks enrollment, uses an expected-version state transition, propagates failures, and relies on the mutation transaction to roll back split state.', visibility='private static')"
if s.count(old) != 1:
    raise SystemExit('cycle4 recalculate_completion transport pattern mismatch')
s = s.replace(old, new, 1)
old = "'program/course/book/lesson CPT registration','four levels and sixteen topics'"
new = "'program/course/book/lesson CPT registration plus four levels and sixteen topics'"
if s.count(old) != 1:
    raise SystemExit('cycle4 curriculum closure-lens pair mismatch')
s = s.replace(old, new, 1)
old = "'core privacy pagination','core privacy DB failure handling'"
new = "'core privacy bounded pagination and explicit DB failure handling'"
if s.count(old) != 1:
    raise SystemExit('cycle4 privacy closure-lens pair mismatch')
s = s.replace(old, new, 1)
p.write_text(s, encoding='utf-8', newline='\n')

# Round-12 test-shape compatibility: the privacy exporter changes from
# tuple(table,column,...) to tuple(table,predicate,args,...). Keep the prior
# completeness invariant, but assert the new exact predicate shape.
static = Path(__file__).resolve().parents[1] / 'tests' / 'static-invariants.py'
t = static.read_text(encoding='utf-8')
old = '"array( $t[\'request_keys\'], \'user_id\'"'
new = '"array( $t[\'request_keys\'], \'user_id=%d\'"'
if t.count(old) != 1:
    raise SystemExit('cycle4 prior privacy invariant token mismatch')
static.write_text(t.replace(old, new, 1), encoding='utf-8', newline='\n')
print('Cycle4 private-method, 68-lens, and prior privacy-invariant transport corrected.')
