'use strict';

const { test, expect } = require('@playwright/test');

const CONTRIBUTION_ID = Number(process.env.TEST_CONTRIBUTION_ID);
const CONTACT_ID = Number(process.env.TEST_CONTACT_ID);

async function login (page) {
  await page.goto('/user/login', { waitUntil: 'domcontentloaded' });
  await page.fill('#edit-name', process.env.CIVI_USER);
  await page.fill('#edit-pass', process.env.CIVI_PASS);
  await Promise.all([
    page.waitForURL('**/*', { waitUntil: 'domcontentloaded' }),
    page.click('#edit-submit'),
  ]);
}

test.describe('Contribution action buttons (Create New Credit Note / Void Contribution)', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  // Regression guard for the leak: opening a contribution View (which renders
  // as a crm-popup from search results) must not inject the action buttons
  // into the underlying Find Contributions search form.
  test('do not leak onto the Find Contributions search form', async ({ page }) => {
    await page.goto('/civicrm/contribute/search?reset=1');
    await page.waitForFunction(() => window.CRM && window.CRM.$ && window.CRM.vars);

    // Open the contribution View exactly as a search-result "View" link does.
    await page.evaluate(({ id, cid }) => {
      const url = window.CRM.url('civicrm/contact/view/contribution', {
        reset: 1, id, cid, action: 'view', context: 'search', selectedChild: 'contribute',
      });
      window.CRM.$('<a class="crm-popup" href="' + url + '">View</a>').appendTo('body')[0].click();
    }, { id: CONTRIBUTION_ID, cid: CONTACT_ID });

    // Allow the pop-up snippet + any injected page-header script to execute.
    await page.waitForTimeout(4000);

    await expect(
      page.locator('#Search a.btn-creditnote-create'),
      'no action button should be injected into the #Search form'
    ).toHaveCount(0);
  });

  // Guard that the fix does not break the intended placement: both buttons
  // must still render on the contribution View page itself.
  test('both render on the contribution View page', async ({ page }) => {
    await page.goto(
      `/civicrm/contact/view/contribution?reset=1&id=${CONTRIBUTION_ID}&cid=${CONTACT_ID}&action=view`,
      { waitUntil: 'domcontentloaded' }
    );

    const form = page.locator('form.CRM_Contribute_Form_ContributionView');
    await form.waitFor({ state: 'visible' });
    await expect(form.locator('a.btn-creditnote-create span', { hasText: 'Create New Credit Note' })).toBeVisible();
    await expect(form.locator('a.btn-creditnote-create span', { hasText: 'Void Contribution' })).toBeVisible();
  });
});
