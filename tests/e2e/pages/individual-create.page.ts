import { BasePage } from './base.page';

/**
 * Individual contact creation form
 */
export class IndividualCreatePage extends BasePage {
  url = 'civicrm/contact/add?reset=1&ct=Individual';

  /**
   * Creates new individual contact with random name.
   */
  async createNew(): Promise<void> {
    await this.page.goto(this.getAbsolutePageUrl());

    let contactName = this.makeRandomString(6);
    await this.page.locator('input[name="first_name"]').fill(contactName);
    await this.page.locator('input[name="last_name"]').fill(contactName);

    await this.page.locator('input[name="last_name"]').press('Enter');
    await this.page.waitForLoadState('domcontentloaded');
  }

  makeRandomString(length): string {
    var outputString = '';
    var characters  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var charactersLength = characters.length;
    for ( var i = 0; i < length; i++ ) {
      outputString += characters.charAt(Math.floor(Math.random() * charactersLength));
    }

    return outputString;
  }
}
