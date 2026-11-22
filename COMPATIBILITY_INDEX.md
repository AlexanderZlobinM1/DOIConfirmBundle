# Compatibility index

Last synchronized from the 3.0.5 DOI public-success redirect correction: 22 November 2026.

Current release: `3.0.5` (`v3.0.5`). Declared support: Mautic 5.x, 6.x, 7.x; PHP >=8.1 <8.5.

| Mautic | Status | Confirmed scope |
| --- | --- | --- |
| 5.2.10 | ✓ | Inherited from 3.0.3; 3.0.5 changes only pure URL normalization before payload encryption and introduces no Mautic API, service or schema change |
| 6.0.9 | ✓ | Inherited from exact 3.0.3 cache/runtime/controller evidence; 3.0.5 changes only pure URL normalization before payload encryption |
| 7.1.3 | ✓ | Inherited from exact 3.0.3 cache/runtime/controller and live route-health evidence; 3.0.5 changes only pure URL normalization before payload encryption |
| 7.2.0 | ✓ | Inherited from prior 7.2 runtime/service evidence; targeted 3.0.5 URL regression and 7.2.2 service wiring pass, while infopoint live acceptance remains Operations-owned |
| 7.2.2 | ✓ | Compatible by retained 7.2 runtime evidence and patch-release inheritance: 3.0.5 changes only pure URL normalization, with no Mautic API, service, schema or lifecycle change; the specific infopoint redirect remains an acceptance check, not a compatibility downgrade |

3.0.5 normalizes a same-host `/s/forms/preview/{id}` configured success URL to
the public `/form/{id}` route before the DOI payload is encrypted. Explicit
external success URLs are preserved, and the plugin does not alter or override
Mautic's protected admin preview route. Per operator direction, the release
contains one forward algorithm only: no migration or compensating handling for
previously issued encrypted payloads.

3.0.3 replaces the documentation controller's eager inherited dependencies
with action injection, removing the Mautic 6/7.1.3 container cycle introduced
by 3.0.2. Version 3.0.2 is explicitly unsafe on Mautic 6 and Mautic 7.1.3 and
must be replaced with 3.0.3 or later. 3.0.3 preserves the native form
restoration from 3.0.1
and the localized help introduced in 3.0.0. 3.0.1 retains the
admin-only documentation introduced in 3.0.0 but restores
Mautic's complete native integration form and `Active` switch. The settings
page receives only one standard documentation button through custom form notes.
3.0.0 added an admin-only built-in documentation route and a localized link in
the Doi Report settings. English, German, Russian (`ru`/`ru_RU`) and Serbian
pages explain candidate-email isolation, post-confirmation promotion, tokens,
parser limitations, evidence fields, audit retention and validation scenarios.
It preserves the dependency range, schema, public routes, action storage keys,
native email controls, webhook names and runtime DOI behavior from 2.1.7.

The 3.0.3 candidate was installed as a narrowly authorized repair on Mautic
7.1.3 and restored non-authenticated route health. No external delivery/provider
exchange or authenticated administrator acceptance was exercised. Update this
file and the workspace `../../COMPATIBILITY_INDEX.md` row with every
plugin-related change or review. The 3.0.5 live install, cache and redirect/MFA
acceptance on Mautic 7.2.2 remain with SalesSnap-Operation.
