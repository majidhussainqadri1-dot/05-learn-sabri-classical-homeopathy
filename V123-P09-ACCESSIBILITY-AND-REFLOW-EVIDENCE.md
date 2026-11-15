# File05 V12.3 — P09 Accessible UI and Reflow Evidence

**Created:** 2026-11-15 (Asia/Karachi)  
**Evidence level:** GitHub source + disposable WordPress, MariaDB and Chromium CI; NOT manual WCAG 2.2 AA audit, Founder approval, Hostinger staging or Live.  
**Source gate:** Draft PR #9 stacked on Draft PR #8, whose real File00/01/20/06/26 peer integration remains independent from deployed status.

## Direct governing requirements

- Master plan V12.3 P09 / F05-09.02: 320, 390, 768, 1024, 1440 (+1920 in staging), responsive and RTL/LTR, 200% text/400% zoom.
- P09 / F05-09.03: WCAG 2.2 AA applicable; keyboard and actual NVDA/TalkBack/VoiceOver observations with accessible success/error.
- QA-16 and Release G4/G5: automated evidence complements independent human review; neither green CI nor screenshots certify accessibility.

## Distinct source issues and repairs

| Source issue | Evidence | Candidate correction | Permanent test |
| --- | --- | --- | --- |
| F05-S23 | A media-only card link had an aria-hidden icon but no accessible link name when no thumbnail existed | Localized \`aria-label\` derived from the actual linked card title | \`tests/v123-accessible-lesson-ui-wp.php\` in genuine disposable WordPress |
| F05-S24 | Progress/bookmark button selects immediate \`.lsch-learning-tools\` status parent, which had no live region; update was silent | Add nearby \`role=status\`, \`aria-live=polite\` plus responsive wrapping | WP rendered structure + actual \`learning.js\` Node event mutation test |
| F05-S25 | Once stylesheet was verified loaded with 200 asset responses, the actual \`.lsch-hero\` min-content item measured 335px at a 320px viewport and pushed document width to 396px | CSS \`minmax(0,1fr)\` for narrow hero track, \`min-width:0\` for grid items, wrap long kicker | Headless Chromium scrolling/reflow/RTL/axe in \`tests/v123-disposable-chromium-landing.test.js\` |

**Not a source defect — T01:** Earlier headless target-size diagnosis was contaminated by a wrong PHP disposable static server router, different hostnames and WordPress base-subdirectory paths; after correcting those, all CSS and JS static assets returned 200. A speculative \`is_learning_context\` change was reverted. T01 is an **environment/test-harness** issue and is excluded from the File05 source issue count.

## Automated test matrix and boundaries

| Feature | Genuine source test | Evidence needed for full acceptance |
| --- | --- | --- |
| Media card name | Rendering of actual WordPress markup with title/aria-label | Manual screen reader link-list comprehension, translation |
| Progress/bookmark feedback | Invoke original JS handlers; verify persisted revision, status text and aria-pressed | Actual learner with File00 approvals on staging and multiple devices |
| Responsive reflow | Local page from disposable WP, seven widths (320/375/390/768/1024/1440/1920), both \`dir=ltr\` and \`dir=rtl\` | Actual File20 shell, site content, 400% zoom, orientation, iOS/Android |
| WCAG checks | \`axe-core\` WCAG 2.0/2.1/2.2 A/AA rules **scoped to \`.lsch-shell\`** | Full-site/component audits, manual WCAG evaluation, NVDA, VoiceOver, TalkBack |
| Keyboard | Tab can acquire real interactive focus; no uncaught JS on public landing | Full keyboard interactions, focus order/traps/visible focus at every learner/teacher form |
| Text enlargement | Disposable 200% root text-size smoke on 320 and 390 only | Authentic browser text-only zoom and 400% page zoom under real WordPress theme |
| Media & weak networks | CSS/JS static assets loaded in disposable browser; reduced motion | Screenreader media, slow/offline, cache, high contrast and low-end device tests |

## Latest immutable browser test receipt

- Candidate code commit `1238837786dbb9d3759f05624caff6831c43e85b`: GitHub Actions Chromium run `43682098010`, **SUCCESS**, recorded 14/14 disposable headless combinations across seven widths (320, 375, 390, 768, 1024, 1440, 1920), RTL/LTR, scoped axe WCAG A/AA, reduced motion and no horizontal overflow or JS runtime exception. The 320/390 subset also ran a limited simulated 200% root-font enlargement check.
- Full manual 400% browser zoom, screen reader, current File20 shell and actual Hostinger staging are **NOT** verified by this run.

## Decisions

1. Do not count automated axe 0 violations on *one public empty catalog* as whole-application WCAG 2.2 AA acceptance.
2. Do not infer production HTML/CSS or plugin version from GitHub or this disposable WordPress.
3. The 4-level/16-topic actual approved course content and 8 Founder works remain outside this UI source code repair.
4. Do not merge PR #9 to PR #8 or main until exact-head CI green, direct independent review, File25 token owner and File20 shell owner compatibility evidence. PR8 and PR9 remain Draft.
5. Do not deploy or mark Resolved without G0 deployed package/DB/Schema/Migration parity, G5 staging+restore, and G7 same-build Live re-test.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

## S26 real evidence

At 320px and 200% root font, hero H1 scrollWidth=344 against clientWidth=198 and fixed-minimum 260px catalog grid. CSS wraps headings and uses flexible grid minimums. Source CI run 43682098010 passed in 14 combinations; manual accessibility still outstanding.
