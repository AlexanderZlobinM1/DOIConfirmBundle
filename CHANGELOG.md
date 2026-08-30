# Changelog

## 2.0.1 - 2026-08-31

- Make the integration publication switch the master runtime boundary for
  form actions, report and webhook registration, public DOI endpoints, queued
  confirmations, and contact mutations.
- Queued DOI confirmations created before shutdown now become no-ops while the
  integration remains unpublished.
