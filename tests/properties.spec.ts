import { test, expect } from '@playwright/test';

test.describe('Properties page', () => {
  test('lista de propiedades visible', async ({ page }) => {
    await page.goto('/properties', { timeout: 15000 });
    // Preferido: data-testid, fallback: .lr-card
    const cardsPrimary = page.locator('[data-testid="property-card"]');
    const cardsFallback = page.locator('.lr-card');
    const cards = cardsPrimary.or(cardsFallback); // combina locators
    const cardCount = await cards.count();
    console.log('cards.count =', cardCount);
    await expect(cards.first()).toBeVisible();
  });

  test('CTA "Ver detalles" tiene href válido', async ({ page }) => {
    await page.goto('/properties', { timeout: 15000 });
    // Intento tolerante: rol + regex y fallback con filter/hasText
    let ctas = page.getByRole('link', { name: /Ver detalles/i });
    if (await ctas.count() === 0) {
      ctas = page.locator('a[href]:not([href="#"]):not([href=""])').filter({ hasText: /Ver detalles/i });
    }
    const allCtas = await ctas.all();
    let validCount = 0;
    for (const el of allCtas) {
      const href = await el.getAttribute('href');
      if (href && href !== '#' && href !== '') validCount++;
      expect(href).toBeTruthy();
      expect(href).not.toBe('#');
    }
    console.log(`CTAs “Ver detalles” válidos: ${validCount} / ${await ctas.count()}`);
    await expect(ctas.first()).toBeVisible();
  });

  test('paginación "Siguiente »" visible/oculta correctamente', async ({ page }) => {
    await page.goto('/properties', { timeout: 15000 });
    const nextLink = page.locator('a[rel="next"]');
    if (await nextLink.count() > 0) {
      await expect(nextLink).toBeVisible();
      console.log('“Siguiente »” visible y clickeable');
    } else {
      await expect(page.getByText('Siguiente »', { exact: true })).toBeHidden();
      console.log('“Siguiente »” oculto correctamente');
    }
  });
});
