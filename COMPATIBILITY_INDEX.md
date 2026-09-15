# Compatibility index

Last synchronized from confirmed workspace evidence and 2.1.7 email-purpose UI regression: 15 September 2026.

Current release: `2.1.7` (`v2.1.7`). Declared support: Mautic 5.x, 6.x, 7.x; PHP >=8.1 <8.5.

| Mautic | Status | Confirmed scope |
| --- | --- | --- |
| 5.2.10 | ✓ | Fresh container, integration and form checks, 6 Sep 2026 |
| 6.0.9 | ✓ | Fresh container, integration and form checks, 6 Sep 2026; 2.1.7 DB-free checks including inactive existing-action preservation, post-confirmation owner-email dispatch and unchecked-owner-email skip, translation parity for 25 message keys, plus runtime HTML checks for two distinct email-purpose headings, detailed Mautic hover tooltips, native email blocks and retained standard form-row width, 15 Sep 2026 |
| 7.1.3 | ✓ | Fresh container, integration and form checks, 6 Sep 2026; 2.0.12 DB-free DOI pending-state non-fatal regression, retained 2.0.11 pending-to-confirmed state-transition regression, translation catalog parity for ru/ru_RU/sr_RS plus retained source-API audit for Mautic 7.1.3 no-session delayed DOI requests, page-hit fallback, integration discovery and duplicate DoiReport resolver normalization, 15 Sep 2026 |
| 7.2.0 | ✓ | Fresh container, integration and form checks, 6 Sep 2026; expanded local 7.x runtime service/form/action-registry and DB-free checks including inactive lifecycle, DOI state transitions, post-confirmation owner-email dispatch/unchecked skip, rendered HTML with two distinct email-purpose headings and detailed Mautic hover tooltips, native email blocks and retained standard field width, no-session delayed request handling, tracking fallback, duplicate DoiReport normalization and 25-key ru/ru_RU/sr_RS catalog parity, 15 Sep 2026 |

2.1.7 is a plugin-only email-purpose UI correction over 2.1.0-2.1.6. It preserves the declared dependency range, schema, routes `/doi/{enc}` and `/nothuman/{hash}`, the existing form action storage keys, native `Email to send` labels/buttons, field-width correction, webhook event names and tokens. Separate translated headings identify the contact DOI request and post-confirmation owner notification. Standard Mautic hover tooltips explain recipients, exact send timing, the `{doi_url}` requirement and that the owner notification is not sent on initial form submission. Owner/user email is still sent only after final DOI confirmation through Mautic's native user-email action service.

No external delivery/provider exchange or live customer-host installation was exercised. Update this file and the workspace `../../COMPATIBILITY_INDEX.md` row with every plugin-related change or review.
