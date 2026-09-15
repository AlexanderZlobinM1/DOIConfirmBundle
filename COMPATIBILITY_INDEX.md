# Compatibility index

Last synchronized from confirmed workspace evidence and 3.0.0 built-in documentation regression: 15 September 2026.

Current release: `3.0.0` (`v3.0.0`). Declared support: Mautic 5.x, 6.x, 7.x; PHP >=8.1 <8.5.

| Mautic | Status | Confirmed scope |
| --- | --- | --- |
| 5.2.10 | ✓ | Fresh container, integration and form checks, 6 Sep 2026 |
| 6.0.9 | ✓ | Fresh container, integration and form checks, 6 Sep 2026; 3.0.0 DB-free documentation wiring/content and 30-key locale parity; 2.1.7 runtime HTML evidence for native email blocks, owner dispatch and standard form-row width retained, 15 Sep 2026 |
| 7.1.3 | ✓ | Fresh container, integration and form checks, 6 Sep 2026; 2.0.12 DB-free DOI pending-state non-fatal regression, retained 2.0.11 pending-to-confirmed state-transition regression, translation catalog parity for ru/ru_RU/sr_RS plus retained source-API audit for Mautic 7.1.3 no-session delayed DOI requests, page-hit fallback, integration discovery and duplicate DoiReport resolver normalization, 15 Sep 2026 |
| 7.2.0 | ✓ | Fresh container, integration and form checks, 6 Sep 2026; 3.0.0 DB-free documentation wiring/content, Twig syntax and 30-key locale parity; 2.1.7 runtime service/form/action evidence including native email blocks, owner dispatch, inactive lifecycle and no-session handling retained, 15 Sep 2026 |

3.0.0 adds an admin-only built-in documentation route and a localized link in
the Doi Report settings. English, German, Russian (`ru`/`ru_RU`) and Serbian
pages explain candidate-email isolation, post-confirmation promotion, tokens,
parser limitations, evidence fields, audit retention and validation scenarios.
It preserves the dependency range, schema, public routes, action storage keys,
native email controls, webhook names and runtime DOI behavior from 2.1.7.

No external delivery/provider exchange or live customer-host installation was exercised. Update this file and the workspace `../../COMPATIBILITY_INDEX.md` row with every plugin-related change or review.
