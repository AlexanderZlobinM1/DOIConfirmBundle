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

## Patch note for 2.0.8

Delayed DOI confirmations rebuild the public click as a synthetic Symfony
`Request` so Mautic helpers can read client context. That synthetic request did
not have a session, so event listeners reached from
`DoiActionHelper::identifyLead()` or DOI success dispatch could throw
`SessionNotFoundException` through `RequestStack::getSession()`. DOI now
attaches an in-memory `MockArraySessionStorage` session only when the request
has no session. This keeps delayed processing sessionless-safe without writing
browser session state or changing Mautic core.

## Patch note for 2.0.9

Russian (`ru`) and Serbian (`sr_RS`) translation catalogs now cover the same
DOI form action, report and webhook labels as `en_US` and `de_DE`. No runtime
logic, storage schema, routes or dependency ranges changed.

## Patch note for 2.0.10

Mautic loads plugin translations from the exact `Translations/<locale>`
directory. DOI now ships both `ru` and `ru_RU` Russian catalogs, plus `sr_RS`,
so instances configured with either Russian locale code avoid English fallback
labels. `Tests/translation-catalogs.php` audits all shipped locales for key
parity, placeholder parity and known English fallback strings.

## Patch note for 2.0.11

The existing "remove after successful DOI" tag and segment settings now also
define the pending DOI state. On initial successful form submit, DOI assigns
those selected tags and segments before sending the confirmation email. On
successful confirmation, the existing success flow remains unchanged: pending
tags and segments are removed, confirmed tags and segments are added, field
updates run, DNC is removed and events/audit continue. No form action storage
keys, routes, schema, token names or translation keys changed.

## Patch note for 2.0.12

The 2.0.11 pending-state assignment runs before DOI email dispatch. In Mautic
6.0.9, submit-action execution only catches `ValidationException`, so any
unexpected tag, segment or listener exception from that pre-email mutation can
abort the DOI action before `EmailModel::sendEmail()` and `doi.started` run.
2.0.12 keeps the pending-state behavior but makes that pre-email mutation
best-effort and logs a warning. DOI email dispatch continues; confirmation
success actions remain unchanged.

## Patch note for 2.0.13

Mautic's native integration details form always owns the `isPublished` / Active
switch for integration lifecycle. DOI keeps that switch as the only runtime
boundary. When `Doi Report` is inactive, new forms and forms without saved DOI
actions do not receive `jw.email.send.lead` in the add-action registry, so DOI
is absent from the "Add a new submit action" chooser. Forms that already store
`jw.email.send.lead` receive a plugin-owned disabled placeholder under the same
action key, with the normal `EmailSendType` and storage keys intact. This lets
Mautic load the existing action into the builder session and save the form
without silently dropping DOI configuration. The disabled builder row has no
edit/delete action controls and runtime handlers remain fail-closed.

## Patch note for 2.1.0

The DOI form action now has an optional post-confirmation owner notification.
When `send_owner_email` is not enabled, the action form keeps the same visible
shape as the 2.0.x series. When enabled, the action renders a nested
`owner_email` configuration with only Mautic's native email select/buttons and
user selector fields. These settings are stored in the encrypted DOI payload
created on initial form submit, but the plugin does not send this owner/user
email until the contact completes final DOI confirmation. Delivery uses
Mautic's `mautic.email.model.send_email_to_user` service after DOI success
actions have completed; failures are logged as warnings and do not roll back
confirmed tags, segments, field updates, DNC removal, audit, tracking or
webhook dispatch.

## Patch note for 2.1.1

2.1.1 keeps the 2.1.0 storage and post-confirmation delivery semantics, but
changes the email action form rendering. The primary DOI email selector now
delegates to Mautic's native email-send-list Twig block, and the nested
`owner_email.useremail` field delegates to Mautic's native form-action email
selector/button Twig block instead of duplicating button markup in this plugin.
The owner wrapper is hidden when `send_owner_email` is unchecked and is tied to
the checkbox through Mautic's `data-show-on` condition plus a lightweight
fallback listener.

## Patch note for 2.1.2

2.1.2 completes the native rendering correction. The primary DOI confirmation
email controls are now added by Mautic's own `EmailSendType::buildForm()` rather
than by plugin-copied field definitions, so button classes, icons, disabled
state and version-specific attributes come from the installed Mautic version.
The unchecked owner-email wrapper is also rendered hidden server-side with
`hide`, `hidden` and `display:none`, then toggled by the checkbox fallback.

## Patch note for 2.1.3

2.1.3 corrects the remaining form-theme mismatch: both the primary DOI email
and nested owner email now render through the same native Mautic
`emailsend_list_row` block. The owner configuration is rendered as one compound
form row, starts hidden when `send_owner_email` is unchecked and is toggled by
an event handler attached directly to the checkbox. This avoids relying on an
inline script element inside Mautic's AJAX-loaded action editor.
