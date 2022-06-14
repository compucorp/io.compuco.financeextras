import { test, expect } from '@playwright/test';
import { LoginPage } from '../pages/login.page';
import { IndividualCreatePage } from '../pages/individual-create.page';
import { ContactView } from '../pages/contact-view.page';

test.describe('Payment "Pending" status selection', async () => {
  let loginPage: LoginPage;
  let individualCreatePage: IndividualCreatePage;
  let contactView: ContactView;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
    await loginPage.logInAsAdmin();

    individualCreatePage = new IndividualCreatePage(page);
    await individualCreatePage.createNew();

    contactView = new ContactView(page);
  });

  test('Selecting pending status on new contribution page sets payment method to "Accounts Receivable" and hides payment method section', async ({ page }) => {
    var PendingStatusId = '2';
    await page.locator('text=Contributions 0').click();
    await page.waitForLoadState('domcontentloaded')

    await page.locator('a:has-text("Record Contribution")').first().click();
    await page.waitForLoadState('domcontentloaded')

    await page.locator('select[name="financial_type_id"]').selectOption('1');
    await page.locator('input[name="total_amount"]').fill('100');

    await expect(page.locator('.payment-details_group')).toBeVisible();

    await page.locator('select[name="contribution_status_id"]').selectOption(PendingStatusId);
    await page.waitForLoadState('domcontentloaded')

    await expect(page.locator('.payment-details_group')).toBeHidden();

    await page.locator('.ui-dialog-buttonset > button').first().click();
    await page.waitForLoadState('domcontentloaded');

    await page.locator('a[title="View Contribution"]').click();
    await page.waitForLoadState('domcontentloaded');

    var paymentMethodSelector = page.locator('td:right-of(#ContributionView td:has-text("Payment Method"))').first();
    await expect(paymentMethodSelector).toHaveText("Accounts Receivable");
  });

  test('Selecting pending payment status on new membership page sets payment method to "Accounts Receivable" and hides payment method section', async ({ page }) => {
    var PendingStatusId = '2';
    await page.locator('text=Memberships 0').click();
    await page.waitForLoadState('domcontentloaded')

    await page.locator('span:has-text("Add Membership")').click();
    await page.waitForLoadState('domcontentloaded');

    await page.locator('select[name="membership_type_id\\[0\\]"]').selectOption('3');
    await page.locator('select[name="membership_type_id\\[1\\]"]').selectOption('1');
    await page.waitForLoadState('domcontentloaded');

    await page.locator('.crm-membership-form-block-join_date input:nth-of-type(2)').fill('01/01/2022');
    await page.locator('.crm-membership-form-block-start_date input:nth-of-type(2)').fill('01/01/2022');
    await page.waitForLoadState('domcontentloaded');

    await expect(page.locator('.crm-membership-form-block-payment_instrument_id')).toBeVisible();
    await expect(page.locator('.crm-membership-form-block-billing')).toBeVisible();

    await page.locator('select[name="contribution_status_id"]').selectOption(PendingStatusId);
    await page.waitForLoadState('domcontentloaded')

    await expect(page.locator('.crm-membership-form-block-payment_instrument_id')).toBeHidden();
    await expect(page.locator('.crm-membership-form-block-billing')).toBeHidden();

    await page.locator('.ui-dialog-buttonset > button').first().click();
    await page.waitForLoadState('domcontentloaded');

    await page.locator('text=Contributions 1').click();
    await page.waitForLoadState('domcontentloaded')

    await page.locator('a[title="View Contribution"]').click();
    await page.waitForLoadState('domcontentloaded');

    var paymentMethodSelector = page.locator('td:right-of(#ContributionView td:has-text("Payment Method"))').first();
    await expect(paymentMethodSelector).toHaveText("Accounts Receivable");
  });

  test('Selecting pending payment status on admin event registration sets payment method to "Accounts Receivable" and hides payment method section', async ({ page }) => {
    var PendingStatusId = '2';
    await page.locator('text=Events 0').click();
    await page.waitForLoadState('domcontentloaded')

    await page.locator('span:has-text("Add Event Registration")').click();
    await page.waitForLoadState('domcontentloaded');

    await page.locator('text=- select Event -').click();
    await page.locator('text=Event * Searching... >> input[role="combobox"]').fill('test');
    await page.locator('div[role="option"] >> text=test').click();
    await page.waitForLoadState('domcontentloaded');

    await expect(page.locator('.crm-event-eventfees-form-block-payment_instrument_id')).toBeVisible();
    await expect(page.locator('#billing-payment-block')).toBeVisible();

    await page.locator('select[name="contribution_status_id"]').selectOption(PendingStatusId);
    await page.waitForLoadState('domcontentloaded');

    await expect(page.locator('.crm-event-eventfees-form-block-payment_instrument_id')).toBeHidden();
    await expect(page.locator('#billing-payment-block')).toBeHidden();

    await page.locator('.ui-dialog-buttonset > button').first().click();
    await page.waitForLoadState('domcontentloaded');

    await page.locator('text=Contributions 1').click();
    await page.waitForLoadState('domcontentloaded')

    await page.locator('a[title="View Contribution"]').click();
    await page.waitForLoadState('domcontentloaded');

    var paymentMethodSelector = page.locator('td:right-of(#ContributionView td:has-text("Payment Method"))').first();
    await expect(paymentMethodSelector).toHaveText("Accounts Receivable");
  });
});
