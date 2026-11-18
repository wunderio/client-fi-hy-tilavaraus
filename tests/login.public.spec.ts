import { expect } from '@playwright/test';
import { test } from './helpers/drupal-test';

test.describe('Anonymous login', () => {
  test('Finnish login page shows the form', async ({ page }) => {
    await page.goto('/fi/user/login');

    await expect(page.locator('main')).toBeVisible();
    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('input[name="pass"]')).toBeVisible();
  });
});
