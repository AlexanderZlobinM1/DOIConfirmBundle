# Compatibility index

Last synchronized from confirmed workspace evidence and 2.0.4 form-action registry audit: 11 September 2026.

Current release: `2.0.4` (`v2.0.4`). Declared support: Mautic 5.x, 6.x, 7.x; PHP >=8.1 <8.5.

| Mautic | Status | Confirmed scope |
| --- | --- | --- |
| 5.2.10 | ✓ | Fresh container, integration and form checks, 6 Sep 2026 |
| 6.0.9 | ✓ | Fresh container, integration and form checks, 6 Sep 2026 |
| 7.1.3 | ✓ | Fresh container, integration and form checks, 6 Sep 2026; 2.0.4 source-API audit for Mautic 7.1.3 form action registry/theme conventions, DOI request stack, routes, Messenger handler, tokens, success actions, audit and webhooks, 11 Sep 2026 |
| 7.2.0 | ✓ | Fresh container, integration and form checks, 6 Sep 2026; expanded local 7.x runtime service/form/action-registry check, 11 Sep 2026 |

2.0.4 is a plugin-only corrective release. It preserves the declared dependency range, schema, routes `/doi/{enc}` and `/nothuman/{hash}`, form action storage, success actions, webhook event names and tokens `{doi_url}` / `{doi_nothuman}`. The patch keeps runtime execution guarded by the integration master switch while always contributing the dedicated DOI action to the Mautic form action registry. It also keeps the 2.0.3 request-stack fix for delayed success processing.

No external delivery/provider exchange or live customer-host installation was exercised. Update this file and the workspace `../../COMPATIBILITY_INDEX.md` row with every plugin-related change or review.
