import { BasePage } from './base.page';

/**
 * Contact view Page
 */
export class ContactView extends BasePage {
  url = 'civicrm/contact/view?reset=1&cid=';

  selectors = {
    membershipsTab: 'text=Memberships 0',
  };

  async goToMembershipsTab(): Promise<void> {
    await this.page.locator(this.selectors.membershipsTab).click();
  }
}
