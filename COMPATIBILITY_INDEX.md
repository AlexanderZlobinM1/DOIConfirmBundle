# Compatibility index

Last synchronized from the rollback to the pre-normalization 3.0.3 contract: 22 November 2026.

Current manifest contract: `3.0.3`; immutable baseline tag: `v3.0.3`; canonical main contains the rollback evidence. Declared support: Mautic 5.x, 6.x, 7.x; PHP >=8.1 <8.5.

| Mautic | Status | Confirmed scope |
| --- | --- | --- |
| 5.2.10 | ✓ | Restored 3.0.3 contract; prior exact service evidence retained and URL pass-through regression passed |
| 6.0.9 | ✓ | Restored 3.0.3 contract; exact cache/runtime/controller evidence retained |
| 7.1.3 | ✓ | Restored 3.0.3 contract; exact cache/runtime/controller and live route-health evidence retained |
| 7.2.0 | ✓ | Restored 3.0.3 contract; prior 7.2 runtime/service evidence and URL pass-through regression retained |
| 7.2.2 | ✓ | Restored 3.0.3 contract; exact route matrix confirms Mautic/AuthGuard retain access-control ownership while the plugin preserves the configured target |

Canonical main restores the pre-normalization 3.0.3 manifest and behavior.
Configured public `/form/{id}`, private `/s/forms/preview/{id}` and external
targets are passed through without route classification, decoding or rewriting;
only explicitly configured contact-token replacement remains. Mautic and
AuthGuard apply the configured target's native access policy. Tags `v3.0.4`
and `v3.0.5` are rejected for acceptance because they contain the erroneous
same-host URL rewrite.

The post-release email-route matrix records the complete DOI chain for Mautic
7.2.2: optional native `/r/{redirectId}` tracking, plugin-public `/doi/{enc}`
or `/nothuman/{hash}` and native email tracking routes. Exact 7.2.2
route-source regression confirms each target retains its native Mautic access
group. AuthGuard is neither referenced nor modified by DOIConfirmBundle.

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
plugin-related change or review. The immutable rollback-ref live install, cache and redirect/MFA
acceptance on Mautic 7.2.2 remain with SalesSnap-Operation.
