import { expect } from '@playwright/test';
import { test } from './helpers/drupal-test';

test.describe('Language prefix', () => {
  test('English login stays on the English path', async ({ page }) => {
    await page.goto('/en/user/login');

    await expect(page).toHaveURL(/\/en\/user\/login/);
    await expect(page.locator('main')).toBeVisible();
    await expect(page.locator('input[name="name"]')).toBeVisible();
  });
});
