# Compatibility index

Last synchronized from confirmed workspace evidence and 2.0.11 DOI state-transition regression: 11 September 2026.

Current release: `2.0.11` (`v2.0.11`). Declared support: Mautic 5.x, 6.x, 7.x; PHP >=8.1 <8.5.

| Mautic | Status | Confirmed scope |
| --- | --- | --- |
| 5.2.10 | ✓ | Fresh container, integration and form checks, 6 Sep 2026 |
| 6.0.9 | ✓ | Fresh container, integration and form checks, 6 Sep 2026 |
| 7.1.3 | ✓ | Fresh container, integration and form checks, 6 Sep 2026; 2.0.11 DB-free DOI pending-to-confirmed state-transition regression, translation catalog parity for ru/ru_RU/sr_RS plus retained source-API audit for Mautic 7.1.3 no-session delayed DOI requests, page-hit fallback, integration discovery and duplicate DoiReport resolver normalization, 11 Sep 2026 |
| 7.2.0 | ✓ | Fresh container, integration and form checks, 6 Sep 2026; expanded local 7.x runtime service/form/action-registry and DB-free checks including DOI pending-to-confirmed state transition, no-session delayed request listener access, delayed tracking-failure fallback, synchronous tracking success, duplicate DoiReport normalization and ru/ru_RU/sr_RS INI catalog parity/no-fallback audit, 11 Sep 2026 |

2.0.11 is a plugin-only lifecycle release. It preserves the declared dependency range, schema, routes `/doi/{enc}` and `/nothuman/{hash}`, form action storage keys, webhook event names and tokens `{doi_url}` / `{doi_nothuman}`. The patch keeps the 2.0.10 ru/ru_RU/sr_RS translation coverage and applies the existing "remove after successful DOI" tag/segment settings as pending DOI state on initial form submit before the DOI email is sent; successful DOI confirmation continues to remove those pending tags/segments and add the configured confirmed tags/segments.

No external delivery/provider exchange or live customer-host installation was exercised. Update this file and the workspace `../../COMPATIBILITY_INDEX.md` row with every plugin-related change or review.
