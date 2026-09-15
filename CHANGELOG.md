# Changelog

## 2.1.6 - 2026-09-15

- Constrain the primary email controls and the complete owner-email section by
  one 30px Mautic grid gutter, matching the right edge of normal action fields.
- Apply that constraint once per section so the owner email and user selector
  remain equal instead of accumulating nested gutter widths.

## 2.1.5 - 2026-09-15

- Render both native email blocks directly at the same Twig level instead of
  routing the owner email through an additional compound `form_row`.
- Normalize the native email template's outer row and column gutters inside
  the DOI action, so the primary email, owner email, user selector and all
  ordinary DOI fields share one right edge.

## 2.1.4 - 2026-09-15

- Remove the extra Bootstrap grid column around the conditional owner-email
  section so its selector, buttons and user field align with the primary DOI
  email controls.
- Keep unchecked server-side hiding and the checkbox's direct visibility
  toggle without introducing layout padding.

## 2.1.3 - 2026-09-15

- Render the primary DOI email and optional owner email with the same native
  Mautic `emailsend_list_row` block, preserving each installed Mautic version's
  horizontal selector/button layout.
- Render the owner settings as one compound form row, hidden server-side while
  unchecked, and toggle that exact row directly from the checkbox without
  relying on execution of an injected script block.
- Add runtime HTML assertions for shared native layout and unchecked visibility.

## 2.1.2 - 2026-09-15

- Build the primary DOI confirmation email selector/buttons with Mautic's
  native `EmailSendType` instead of plugin-copied field definitions, so button
  attributes and icons come from the installed Mautic version.
- Keep the native Twig block reuse from 2.1.1 and make unchecked owner-email
  settings hidden with server-rendered `hide`/`hidden` state plus the checkbox
  toggle fallback.

## 2.1.1 - 2026-09-15

- Render both the primary DOI email selector and the post-confirmation owner
  email selector through Mautic's native email form theme blocks, so the email
  select and New/Edit/Preview buttons follow the active Mautic version's own
  markup.
- Hide the owner email block by default when the checkbox is unchecked, and
  toggle it from the checkbox without exposing the nested fields prematurely.

## 2.1.0 - 2026-09-15

- Add an optional `Send owner email after DOI confirmation` checkbox to the DOI
  form action.
- Keep the 2.0.x DOI action UI unchanged while the checkbox is off; when it is
  enabled, show only the native Mautic owner notification fields: `Email to
  send`, email create/edit/preview buttons, and `Send email to user`.
- Send the selected owner/user notification only after the contact completes
  final DOI confirmation, using Mautic's native `SendEmailToUser` model.
- Keep owner notification delivery best-effort after DOI success actions, so a
  notification failure is logged without rolling back confirmation state.
- Extend Mautic 6/7 DB-free coverage and Mautic 7.2 runtime form coverage for
  the conditional owner-email fields and post-confirmation dispatch.

## 2.0.13 - 2026-09-15

- Keep Mautic's native `Doi Report` Active switch as the runtime lifecycle
  boundary, but hide DOI from the new submit-action chooser while inactive.
- Preserve existing configured DOI form actions while inactive by registering a
  disabled builder placeholder only for forms that already contain
  `jw.email.send.lead`.
- Render inactive existing DOI actions as disabled/non-editable rows so they do
  not look executable and do not expose builder action controls while their
  stored settings remain intact through form saves.
- Extend Mautic 6/7 runtime and DB-free regression coverage for inactive UI
  registration, existing action preservation and locale catalog parity.

## 2.0.12 - 2026-09-15

- Make the pre-email pending DOI tag/segment update best-effort so a tag,
  segment or listener failure cannot prevent the DOI email from being sent.
- Log a warning with DOI contact context when pending state assignment fails.
- Extend the state-transition regression to cover non-fatal pending failures.

## 2.0.11 - 2026-09-11

- Apply the existing `remove_tags_doi_success_tags` selections as pending DOI
  tags immediately after a successful form submit starts the DOI challenge.
- Apply the existing `remove_campaign_doi_success_lists` selections as pending
  DOI segments immediately after a successful form submit starts the DOI
  challenge.
- Keep DOI confirmation idempotent: successful DOI still removes the pending
  tags/segments and adds the configured confirmed tags/segments.
- Add DB-free regression coverage for the submit-to-confirm state transition.

## 2.0.10 - 2026-09-11

- Add a `ru_RU` Russian translation catalog for Mautic installations that use
  that locale code instead of `ru`.
- Audit all shipped translation catalogs for current DOI UI keys, placeholder
  parity and English fallback strings.
- Polish German and English DOI labels that still contained fallback or legacy
  wording.

## 2.0.9 - 2026-09-11

- Add Russian (`ru`) and Serbian (`sr_RS`) translation catalogs for DOI form
  action labels, report fields and webhook event labels.

## 2.0.8 - 2026-09-11

- Attach an in-memory session to DOI synthetic requests when delayed Messenger
  confirmation runs without an HTTP session, so Mautic listeners that read
  `RequestStack::getSession()` do not abort the DOI flow.
- Preserve the existing synchronous request path while making no-session delayed
  confirmation safe for audit, contact mutations, DNC removal, webhook dispatch
  and redirect completion.
- Extend regression coverage for no-session delayed listeners and synchronous
  session access.

## 2.0.7 - 2026-09-11

- Make DOI confirmation page-hit tracking best-effort so unavailable Mautic
  tracking request services cannot abort audit, contact mutations, DNC removal,
  webhook dispatch or redirect completion.
- Log page-hit tracking failures with DOI context for operator diagnosis.
- Add regression coverage for delayed synthetic-request handling with failing
  page-hit tracking and synchronous tracking success.

## 2.0.6 - 2026-09-11

- Make the `DoiReport` integration resolver deterministic when historical
  duplicate settings rows exist, preferring an active row over a disabled row.
- Normalize duplicate exact-name `DoiReport` settings rows in place by keeping
  the selected row authoritative and archiving stale duplicates without
  deleting their settings.
- Add regression coverage for duplicate disabled/enabled rows and idempotent
  duplicate normalization.

## 2.0.5 - 2026-09-11

- Register the `DoiReport` integration under Mautic's legacy discovery
  convention (`Integration/DoiReportIntegration.php` and
  `mautic.integration.doireport`) so it appears in the Plugins UI and can
  create/update its normal Integration settings row.
- Keep DOI runtime fail-closed when the integration is missing or disabled, but
  log an explicit diagnostic instead of silently skipping every submission.
- Add regression coverage for integration discovery, UI-config service naming
  and the disabled/enabled resolver transition.

## 2.0.4 - 2026-09-11

- Always register the dedicated DOI form action in the Mautic form action
  builder so operators can configure DOI settings before or after enabling the
  integration master switch.
- Keep runtime execution, public endpoints and queued mutations guarded by the
  integration master switch.
- Use the Mautic 7-compatible Twig form theme path for the DOI form action and
  render all DOI-specific configuration fields.
- Extend runtime compatibility coverage to assert the form action registry and
  DOI action configuration fields.

## 2.0.3 - 2026-09-11

- Preserve the original public DOI request context in Mautic's request stack
  while delayed success actions run, so audit IP handling, page-hit tracking,
  privacy headers and bot checks use the confirmation click rather than a CLI
  fallback context.
- Expand the runtime compatibility check to instantiate DOI listeners, helpers
  and the message handler, not only the integration and form type services.
- Document safe cleanup for test `{doi_nothuman}` markers.

## 2.0.2 — 2026-09-06

- Support Mautic 7.2 while retaining the declared older Mautic versions.
- Use a plugin-scoped EncryptionHelper service alias; keep legacy argument parsing and the global core container unchanged.
- Add a fresh-kernel regression check that instantiates integration services and resolves form types.
- Preserve the Mautic 5 session constructor argument and correctly recognize two-digit patch versions such as 5.2.10.
- Use the shared Symfony translation contract for DOI action forms.


## 2.0.1 - 2026-08-31

- Make the integration publication switch the master runtime boundary for
  form actions, report and webhook registration, public DOI endpoints, queued
  confirmations, and contact mutations.
- Queued DOI confirmations created before shutdown now become no-ops while the
  integration remains unpublished.
