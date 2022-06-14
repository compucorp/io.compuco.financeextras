import { BasePage } from './base.page';

/**
 * Login Page
 */
export class LoginPage extends BasePage {
  url = 'user';
  selectors = {
    userNameField: 'input[name="name"]',
    passwordField: 'input[name="pass"]',
  };
  credentials = {
    admin: { user: 'compucorp_admin', password: 'compucorp_admin' },
  };

  /**
   * Logs In the website.
   */
  async logInAsAdmin(): Promise<void> {
    await this.page.goto(this.getAbsolutePageUrl());
    await this.page.fill(this.selectors.userNameField, this.credentials.admin.user);
    await this.page.fill(this.selectors.passwordField, this.credentials.admin.password);
    await this.page.locator(this.selectors.passwordField).press('Enter');
  }
}
