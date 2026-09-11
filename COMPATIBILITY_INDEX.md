# Compatibility index

Last synchronized from confirmed workspace evidence and 2.0.7 page-hit tracking fallback audit: 11 September 2026.

Current release: `2.0.7` (`v2.0.7`). Declared support: Mautic 5.x, 6.x, 7.x; PHP >=8.1 <8.5.

| Mautic | Status | Confirmed scope |
| --- | --- | --- |
| 5.2.10 | ✓ | Fresh container, integration and form checks, 6 Sep 2026 |
| 6.0.9 | ✓ | Fresh container, integration and form checks, 6 Sep 2026 |
| 7.1.3 | ✓ | Fresh container, integration and form checks, 6 Sep 2026; 2.0.7 source-API audit for Mautic 7.1.3 delayed DOI page-hit fallback, integration discovery and duplicate DoiReport resolver normalization, Plugins UI config route, form action registry/theme conventions, DOI request stack, routes, Messenger handler, tokens, success actions, audit and webhooks, 11 Sep 2026 |
| 7.2.0 | ✓ | Fresh container, integration and form checks, 6 Sep 2026; expanded local 7.x runtime service/form/action-registry and DB-free checks including delayed tracking-failure fallback, synchronous tracking success and duplicate DoiReport normalization, 11 Sep 2026 |

2.0.7 is a plugin-only corrective release. It preserves the declared dependency range, schema, routes `/doi/{enc}` and `/nothuman/{hash}`, form action storage, success actions, webhook event names and tokens `{doi_url}` / `{doi_nothuman}`. The patch keeps the 2.0.5 integration discovery alignment and the 2.0.6 duplicate `DoiReport` normalization, and makes Mautic page-hit tracking non-fatal in delayed Messenger/synthetic-request contexts. Audit, contact mutations, DNC removal, webhooks and redirects continue when tracking services are unavailable.

No external delivery/provider exchange or live customer-host installation was exercised. Update this file and the workspace `../../COMPATIBILITY_INDEX.md` row with every plugin-related change or review.
