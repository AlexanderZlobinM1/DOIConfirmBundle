# Changelog

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
