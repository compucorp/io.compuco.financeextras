'use strict';

const { defineConfig } = require('@playwright/test');

/**
 * These tests drive a real CiviCRM instance, so they are configured entirely
 * from environment variables and are NOT wired into the extension's default
 * CI (which has no browser/CiviCRM-web environment). See README.md.
 *
 *   CIVI_BASE_URL         e.g. http://compuclient-latest.localhost:8081
 *   CIVI_USER / CIVI_PASS Drupal admin credentials
 *   TEST_CONTRIBUTION_ID  a Completed contribution id
 *   TEST_CONTACT_ID       that contribution's contact id
 */
module.exports = defineConfig({
  testDir: '.',
  timeout: 120000,
  expect: { timeout: 30000 },
  retries: 0,
  reporter: 'list',
  use: {
    baseURL: process.env.CIVI_BASE_URL,
    headless: true,
    video: 'on',
    screenshot: 'only-on-failure',
    ignoreHTTPSErrors: true,
  },
});
