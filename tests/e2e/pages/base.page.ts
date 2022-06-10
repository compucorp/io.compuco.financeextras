import { Page, Response } from 'playwright';
import config from '../playwright.config';

/**
 * Base Page.
 */
export abstract class BasePage {
  protected abstract url: string;
  public animationTransitionTime = 500;
  public selectors: TestPageSelectors = {};

  /**
   * Constructor.
   */
  constructor(
    protected page: Page,
  ) {
    this.page = page;
  }

  /**
   * Navigate to the page.
   */
  async navigate(): Promise<Response | null> {
    return this.page.goto(this.getPageUrl());
  }

  /**
   * Get page url.
   */
  getPageUrl(): string {
    return this.url;
  }

  /**
   * Get absolute page url.
   */
  getAbsolutePageUrl(): string {
    return config.use.baseURL + '/' + this.url;
  }
}

export type TestPageSelectors = Record<string, string>;
