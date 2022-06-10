import { PlaywrightTestConfig } from '@playwright/test';

const E2E_SERVER_URL = getServerUrl();
const E2E_SERVER_PORT = getPort();

const portString = E2E_SERVER_PORT !== '80' ? `:${E2E_SERVER_PORT}` : '';
const baseURL = `${E2E_SERVER_URL}${portString}`;

/**
 * @see https://playwright.dev/docs/test-configuration
 * @type {import('@playwright/test').PlaywrightTestConfig}
 */
const config: PlaywrightTestConfig  = {
  testDir: './tests',
  outputDir: './report',
  reporter: 'list',
  timeout: 40000,
  use: {
    baseURL,
    browserName: 'chromium',
    headless: true,
    viewport: { width: 1280, height: 720 },
    trace: 'retain-on-failure',
  },
};

export default config;


/**
 * Get the server url.
 */
function getServerUrl(): string {
  return process.env.E2E_SERVER_URL || 'http://localhost';
}

/**
 * Get the port number.
 */
function getPort(): string {
  return process.env.E2E_SERVER_PORT || '80';
}
