# File 05 Security and Privacy Model — 3.3.0

- Identity/eligibility truth comes from File 00 public versioned assertions; private File 00 storage is not an integration API.
- Endpoint permission, object ownership/state and field rules are server-side and revalidated at action time.
- Public DTOs and private learner state are separated; private responses are no-store/noindex where applicable.
- Private notes use independent AES-256-GCM keys with AAD binding user, lesson and key generation. New encryption never derives from WordPress auth salts.
- Correction proposer self-approval is prohibited; reviewer object scope, optimistic versioning, stale-content detection and recoverable `applying` state prevent silent overwrite.
- Reliable event/job paths use idempotency/deduplication, bounded retry/dead states, worker claim controls and reconciliation.
- File 05 never claims global ranking, PDF/video/AI/message/notification/feed/visual ownership.
- Privacy export/erasure covers account-owned learning state; governance/legal-hold records are pseudonymized or retained only under documented constraints.
- Logs/audits must not contain note plaintext, assessment answers, patient identifiers, secrets or raw sensitive evidence.
