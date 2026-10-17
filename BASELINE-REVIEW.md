# Mandatory Post-Upload Review — File 05

The baseline must remain immutable and unmerged while the following review is performed.

## Review domains

1. WordPress bootstrap, activation, deactivation, uninstall, and upgrade behavior.
2. Dependencies on Files 01–04 and failure behavior when a dependency is absent.
3. Roles, capabilities, author eligibility, reviewer authority, and privilege escalation.
4. Lesson submission, moderation, publication, rejection, and audit-history state transitions.
5. Public/private boundaries for progress, bookmarks, knowledge checks, and user metadata.
6. Nonces, authorization, sanitization, validation, escaping, SQL preparation, and REST/AJAX controls.
7. Database schema, indexes, uniqueness, concurrency, orphan cleanup, retention, and privacy erasure/export.
8. Patient-case anonymity, consent, medical-safety notices, and prohibited claims.
9. Catalog search/filter correctness, pagination, performance, and denial-of-service resistance.
10. Structured data, canonical URLs, duplicate content, and output escaping.
11. Accessibility, keyboard operation, responsive behavior, reduced motion, and screen-reader semantics.
12. Compatibility with WordPress, PHP 7.4+, Files 01–04, the unified shell, caching, and multisite assumptions.
13. Packaging reproducibility and staging acceptance evidence.

## Stop-the-line rule

Every discovered defect, regression, incomplete workflow, security/privacy concern, or integration blocker must be fully corrected and retested before the project proceeds to merge, staging acceptance, production, or the next module.
