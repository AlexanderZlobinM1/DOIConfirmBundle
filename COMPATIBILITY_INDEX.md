# Compatibility index

Last synchronized from confirmed workspace evidence and the 3.0.3 Mautic 7.1.3 container-cycle repair: 16 September 2026.

Current release: `3.0.3` (`v3.0.3`). Declared support: Mautic 5.x, 6.x, 7.x; PHP >=8.1 <8.5.

| Mautic | Status | Confirmed scope |
| --- | --- | --- |
| 5.2.10 | ✓ | Fresh container, integration and form checks retained from 6 Sep 2026; 3.0.3 DB-free service-map branch verifies the zero-constructor controller and action-locator wiring; exact 3.0.3 runtime NOT RUN |
| 6.0.9 | ✓ | Fresh container, integration and form checks retained from 6 Sep 2026; 3.0.3 DB-free service-map branch verifies the zero-constructor controller and action-locator wiring; exact 3.0.3 runtime NOT RUN |
| 7.1.3 | ✓ | Exact 3.0.3 cache clear/warmup, service/route discovery, runtime compatibility and controller tests pass; live public-route recovery passes, while authenticated UI/docs and end-to-end delivery remain NOT RUN |
| 7.2.0 | ✓ | Fresh container/integration/form and 2.1.7 runtime HTML evidence retained; 3.0.3 uses the actual 7.2 ServicePass and controller argument-locator pass, with controller full/AJAX/403 unit checks |

3.0.3 replaces the documentation controller's eager inherited dependencies
with action injection, removing the Mautic 7.1.3 container cycle introduced by
3.0.2. Version 3.0.2 is explicitly unsafe on Mautic 7.1.3 and must be replaced
with 3.0.3 or later. 3.0.3 preserves the native form restoration from 3.0.1
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
plugin-related change or review.
