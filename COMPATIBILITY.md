# Mautic compatibility

Supported Mautic range remains 5.x, 6.x, 7.x.

Run against each supported Mautic installation with this plugin installed:

```sh
MAUTIC_ROOT=/path/to/mautic php -d memory_limit=1G Tests/runtime-compatibility.php
```

The test compiles an isolated container, instantiates this plugin’s integrations
and form types, then removes its temporary cache. Production cache and provider
settings are not changed. Live external-provider delivery requires a separate
configured acceptance environment; a successful kernel test does not prove delivery.

## Verified on 6 September 2026

Fresh-kernel service instantiation and form construction/resolution passed on `7.2.0`, `7.1.3`, `6.0.9`, `5.2.10`. Mautic 5/6 used PHP 8.2.33; Mautic 7 used PHP 8.4.25. External delivery and OAuth/CAPTCHA provider exchanges were not exercised.

## Patch note for 2.0.3

The DOI success-action patch preserves the original click request in the local
request stack while delayed processing runs. It does not change the declared
Mautic/PHP dependency range, routes, schema, form action storage or token names.

## Patch note for 2.0.4

The DOI form action is always contributed to the Mautic form action registry so
operators can configure it through the UI even before the integration is active.
Runtime execution, public endpoints and queued mutations remain guarded by the
integration master switch. The custom form theme now uses the Mautic 7 Twig
namespace path and renders the remaining DOI configuration fields with
`form_rest()`.

## Patch note for 2.0.5

Mautic discovers plugin integrations by scanning `Integration/*Integration.php`,
deriving the integration name from the filename, then looking for a matching
service id `mautic.integration.<lowercase-name>`. DOI now ships
`Integration/DoiReportIntegration.php` with service id
`mautic.integration.doireport`, so plugin reload can create the normal
`plugin_integration_settings` row and expose `/s/plugins/config/DoiReport`.
`Tests/integration-discovery.php` checks this convention without requiring a
database.

## Patch note for 2.0.6

If a live instance already has historical duplicate exact-name `DoiReport`
integration settings rows, DOI runtime now chooses deterministically: an active
row wins over a disabled row, and the newest id wins within the same state. The
resolver then performs a plugin-owned idempotent normalization by keeping the
selected row named `DoiReport`, copying settings from a stale row only when the
selected row is empty, renaming stale exact-name duplicates to
`DoiReport.duplicate.<id>` and disabling them. No non-`DoiReport` integration
rows are changed.

## Patch note for 2.0.7

DOI confirmation page-hit tracking is best-effort. If Mautic page tracking
cannot run in a delayed Messenger/synthetic-request context, the plugin logs a
warning and continues the confirmation flow: audit `confirm_doi`, tags,
segments, field updates, DNC removal, webhook dispatch and the controller
redirect are not aborted by the tracking failure. Synchronous request tracking
still calls `PageModel::hitPage()` when available.
