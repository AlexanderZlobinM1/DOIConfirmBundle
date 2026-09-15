# Compatibility index

Last synchronized from confirmed workspace evidence and 2.1.1 owner-email UI rendering regression: 15 September 2026.

Current release: `2.1.1` (`v2.1.1`). Declared support: Mautic 5.x, 6.x, 7.x; PHP >=8.1 <8.5.

| Mautic | Status | Confirmed scope |
| --- | --- | --- |
| 5.2.10 | ✓ | Fresh container, integration and form checks, 6 Sep 2026 |
| 6.0.9 | ✓ | Fresh container, integration and form checks, 6 Sep 2026; 2.1.1 DB-free checks including inactive existing-action preservation, inactive empty-form hiding, post-confirmation owner-email dispatch and unchecked-owner-email skip, 15 Sep 2026 |
| 7.1.3 | ✓ | Fresh container, integration and form checks, 6 Sep 2026; 2.0.12 DB-free DOI pending-state non-fatal regression, retained 2.0.11 pending-to-confirmed state-transition regression, translation catalog parity for ru/ru_RU/sr_RS plus retained source-API audit for Mautic 7.1.3 no-session delayed DOI requests, page-hit fallback, integration discovery and duplicate DoiReport resolver normalization, 15 Sep 2026 |
| 7.2.0 | ✓ | Fresh container, integration and form checks, 6 Sep 2026; expanded local 7.x runtime service/form/action-registry and DB-free checks including inactive empty-form hiding, inactive existing-action preservation, DOI pending-state non-fatal handling, pending-to-confirmed state transition, post-confirmation owner-email field registration/dispatch, unchecked-owner-email skip, native primary DOI email and owner-email button Twig block reuse, no-session delayed request listener access, delayed tracking-failure fallback, synchronous tracking success, duplicate DoiReport normalization and ru/ru_RU/sr_RS INI catalog parity/no-fallback audit, 15 Sep 2026 |

2.1.1 is a plugin-only owner notification UI correction over 2.1.0. It preserves the declared dependency range, schema, routes `/doi/{enc}` and `/nothuman/{hash}`, the existing 2.0.x form action storage keys, webhook event names and tokens `{doi_url}` / `{doi_nothuman}`. The patch keeps optional storage keys `send_owner_email` and `owner_email`; both email selector/button areas reuse Mautic native Twig blocks, owner/user email is hidden unless enabled, and it is sent only after final DOI confirmation through Mautic's native user-email action service.

No external delivery/provider exchange or live customer-host installation was exercised. Update this file and the workspace `../../COMPATIBILITY_INDEX.md` row with every plugin-related change or review.
