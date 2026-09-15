# Mautic compatibility

Supported Mautic range remains 5.x, 6.x, 7.x.

Run against each supported Mautic installation with this plugin installed:

```sh
MAUTIC_ROOT=/path/to/mautic php -d memory_limit=1G Tests/runtime-compatibility.php
```

Run the DB-free localized documentation and catalog checks with:

```sh
php Tests/documentation.php
php Tests/translation-catalogs.php
MAUTIC_VENDOR=/path/to/mautic/vendor php Tests/documentation-services.php
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

## Patch note for 2.1.4

2.1.4 removes the extra Bootstrap column from the conditional owner-email
wrapper. Both native `emailsend_list_row` blocks now receive the same available
width and horizontal origin, so their selectors and New/Edit/Preview buttons
align. The neutral wrapper still starts hidden server-side while unchecked and
is shown or hidden directly by the checkbox.

## Patch note for 2.1.5

2.1.5 renders the primary and owner native `emailsend_list_row` blocks directly
from the same parent Twig block. Scoped DOI styles neutralize only the outer
row margins and column padding produced by that native block, matching both
email selectors to ordinary full-width form fields such as the owner user,
tags and segments selectors. All fields therefore share the same right edge
without replacing Mautic's version-specific email controls or buttons.

## Patch note for 2.1.6

Mautic 5, 6 and 7 define the Bootstrap grid gutter as 30px. In the form action
modal, custom property-row content spans one gutter beyond ordinary
`form_row()` fields. Version 2.1.6 constrains the primary email wrapper and the
complete owner section by exactly that one gutter. The owner email and user
selector inherit the owner section width together, avoiding cumulative nested
subtractions. Their right edges therefore match tags, segments, URLs and text
fields while native email rendering remains unchanged.

## Patch note for 2.1.7

2.1.7 leaves Mautic's native `Email to send` labels and controls unchanged, but
adds a translated purpose heading before each email block. Standard Mautic
question icons expose hover tooltips that distinguish the immediate DOI request
sent to the contact from the owner notification sent only after successful
confirmation. The tooltips also document the `{doi_url}` requirement and the
selected Mautic user recipients.

## Major note for 3.0.0

3.0.0 adds an admin-only `/doi-confirm/documentation` page and links it from
the native Doi Report integration settings. The page follows the active Mautic
locale with English fallback: `en_US`, `de_DE`, Russian `ru`/`ru_RU`, and
`sr_RS`. It documents safe candidate-email storage, promotion only after DOI,
field-update and email tokens, parser limits, multiple-purpose examples,
recommended evidence fields, immutable audit retention and a validation
checklist. This documentation feature does not change DOI runtime semantics,
public confirmation routes, storage schema or existing form-action keys.

## Patch note for 3.0.1

3.0.1 removes the DOI integration's custom `getFormTemplate()` override. That
override replaced Mautic's complete native integration form and therefore hid
the runtime `Active` switch. The integration now retains
`@MauticPlugin/Integration/form.html.twig` and contributes only the
documentation button via the native `getFormNotes('custom')` extension point.
No DOI runtime, storage, route or documentation-page behavior changed.

## Patch note for 3.0.2

3.0.2 registers `DocumentationController` and
`DocumentationLocaleResolver` in the legacy plugin service map inside
`Config/config.php`, which Mautic 5, 6 and 7 process through `ServicePass`.
The controller receives all inherited `CommonController` constructor
dependencies: ten on Mautic 5 and nine on Mautic 6/7. It is tagged with
`controller.service_arguments`, so Symfony resolves the locale resolver used
by `indexAction()`. The unused plugin-local `Config/services.php` definition
was removed.

Controller tests cover full admin HTML, AJAX JSON, locale selection and the
non-admin 403 path. The service-wiring regression runs the real Mautic 7.2
plugin `ServicePass` and Symfony controller argument-locator pass while
simulating the constructor branches for 5.2.10, 6.0.9, 7.1.3 and 7.2.0.
This is plugin-owned DB-free evidence, not live HTTP acceptance on those exact
instances. The native integration form, Active switch, DOI state transitions,
public confirmation routes and one-line Sales Snap footer are unchanged.
