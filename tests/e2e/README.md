# End-to-end tests (Playwright)

These tests drive a real, running CiviCRM instance in a browser to guard against
the "Create New Credit Note" / "Void Contribution" buttons leaking onto the
**Find Contributions** search form (see `CIVIMM` bug), and to confirm both
buttons still render on the contribution View page.

They are **not** part of the extension's default CI (which only has a headless
CiviCRM PHP environment). Run them against any environment that has this
extension enabled.

## Prerequisites

- Node 18+ and `npx playwright install chromium`
- A CiviCRM site with `io.compuco.financeextras` enabled
- A **Completed** contribution to exercise (both buttons are eligible for it)

## Running

```bash
cd tests/e2e
npm install
npx playwright install chromium

CIVI_BASE_URL="http://your-civi.example" \
CIVI_USER="admin" \
CIVI_PASS="admin" \
TEST_CONTRIBUTION_ID="123" \
TEST_CONTACT_ID="456" \
npm test
```

Videos are recorded for every test (`video: 'on'`) under `test-results/` — used
as evidence on the fix PR.

## Environment variables

| Variable | Description |
|---|---|
| `CIVI_BASE_URL` | Base URL of the CiviCRM/Drupal site |
| `CIVI_USER` / `CIVI_PASS` | Drupal login for a user who can view contributions |
| `TEST_CONTRIBUTION_ID` | Id of a Completed contribution |
| `TEST_CONTACT_ID` | Contact id that owns the contribution |
