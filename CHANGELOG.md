# Changelog

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
