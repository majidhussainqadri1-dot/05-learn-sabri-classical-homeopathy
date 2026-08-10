# File 05 Versioned Contract Registry — 3.3.0

| Owner/provider | Direction | Required | Boundary |
|---|---|---:|---|
| File 00 Membership Core | consume | yes | `SMC_CONTRACT_VERSION >= 1.2.2`, `SMC_Contracts::assertions()`, `smc_policy()`; no private meta/table reads |
| File 01 Foundation | consume | yes | platform registry/foundation readiness only |
| File 20 Shell | consume | yes | shell/layout/navigation slots; no duplicate shell |
| File 06 Encyclopedia | bidirectional refs | conditional | canonical knowledge IDs/URLs only; no copied encyclopedia truth |
| File 10 Video/Live | related refs | conditional | media references only |
| File 12 PDF Library | related/attachment refs | conditional | validated PDF/document references; no PDF storage |
| File 15 Radar | related refs | optional | owner references only |
| File 16 AI | provide context | optional | source-grounded educational context; no diagnosis/prescription/emergency authority |
| File 17 Network/Messages | context bridge | optional | cohort/study context; no message transport |
| File 19 Notifications | event delivery | optional | reminder/correction/completion event projection; File 19 owns delivery |
| File 21 Feed/Publishing | event/reference | optional | educational publication projection only; no feed truth |
| File 22 Composer | authoring adapter | optional | File 05 validation/publication owner remains authoritative |
| File 24 Security Center | assurance | optional | controls/evidence adapter; native enforcement remains File 05/File 00 |
| File 25 Visual System | consume | optional | design tokens/components; File 05 fallback only |
| File 26 Search/Discovery | provide projection | optional | File 26 owns global ranking; File 05 provides learning catalog visibility/freshness/why metadata |

The REST namespace remains `learn-sabri-classical-homeopathy/v2` for compatibility. A runtime version bump does not silently break an existing versioned API contract.
