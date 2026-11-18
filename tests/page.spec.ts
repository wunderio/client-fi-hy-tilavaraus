import { expect } from '@playwright/test';
import { faker } from '@faker-js/faker';
import { test } from './helpers/drupal-test';

test.describe('Page', () => {
  test('an editor can create a page', async ({ drupal, page }) => {
    const title = faker.lorem.sentence({ min: 3, max: 4 });

    await drupal.loginAsAdmin();
    await page.goto('/fi/node/add/page');

    await page.locator('input[name="title[0][value]"]').fill(title);
    await page.getByRole('button', { name: /^(Save|Tallenna)$/ }).click();

    // The page display does not print the title as a heading. The status
    // message links to the new page by title.
    const status = page.locator('[data-drupal-messages], .messages');
    await expect(status.getByRole('link', { name: title })).toBeVisible();

    await page.getByRole('link', { name: /^(Poista|Delete)$/ }).click();
    await page.getByRole('button', { name: /^(Delete|Poista)$/ }).click();
    await expect(status.getByText(/poistettu|has been deleted/i)).toBeVisible();
  });
});
