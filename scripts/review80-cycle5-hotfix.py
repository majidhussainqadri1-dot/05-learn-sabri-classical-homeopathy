#!/usr/bin/env python3
from pathlib import Path
p = Path(__file__).with_name('review80-cycle5-20260811.py')
s = p.read_text(encoding='utf-8')
remove = [
    "    'Curriculum Lead least-privilege boundary',\n",
    "    'operator versus curriculum/assessor separation',\n",
    "    'read-only break-glass diagnostics versus mutating repair separation',\n",
    "    'repair reason/confirmation/step-up/reversible-backup gates',\n",
]
for item in remove:
    if s.count(item) != 1:
        raise SystemExit('cycle5 lens-count hotfix pattern mismatch: ' + item.strip())
    s = s.replace(item, '', 1)
p.write_text(s, encoding='utf-8', newline='\n')
print('Cycle5 transport corrected to exactly 72 clean lenses for rounds 9-80.')
