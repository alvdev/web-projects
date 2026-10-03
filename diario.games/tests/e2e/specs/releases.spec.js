import { test, expect } from '@playwright/test'

test('homepage shows three release columns and links to the calendar', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' })

  const module = page.locator('[data-home-releases]')
  await expect(module).toBeVisible()
  await expect(module.locator('[data-home-column]')).toHaveCount(3)
  await expect(module.locator('[data-home-release]')).toHaveCount(9)
  await expect(module).toContainText('Recién lanzados')
  await expect(module).toContainText('Próximos lanzamientos')
  await expect(module).toContainText('Más esperados')
  await expect(module).toContainText('Neon Drift')
  await expect(module.getByRole('link', { name: /Ver calendario completo/ })).toHaveAttribute('href', '/lanzamientos')
})

test('releases page shows three sections and filters by genre', async ({ page }) => {
  await page.goto('/lanzamientos', { waitUntil: 'domcontentloaded' })

  await expect(page.locator('h1')).toContainText('Próximos lanzamientos de videojuegos')
  await expect(page.locator('[data-releases-section]')).toHaveCount(3)
  await expect(page.locator('[data-release-row]')).toHaveCount(9)

  await page.locator('[data-releases-filter] [data-genre="RPG"]').click()
  await expect(page.locator('[data-release-row]:visible')).toHaveCount(4)
  await expect(page.locator('[data-release-row]:visible').filter({ hasText: 'Shadow Realm' })).toHaveCount(1)

  await page.locator('[data-releases-filter] [data-genre="*"]').click()
  await expect(page.locator('[data-release-row]:visible')).toHaveCount(9)

  await expect(page.locator('text=Datos de IGDB')).toBeVisible()
})

test('games page filters cards by genre chip', async ({ page }) => {
  await page.goto('/games', { waitUntil: 'domcontentloaded' })

  await expect(page.locator('[data-genre-item]')).toHaveCount(2)

  await page.locator('[data-genre="RPG"]').click()
  await expect(page.locator('[data-genre-item]:visible')).toHaveCount(1)
  await expect(page.locator('[data-genre-item]:visible')).toContainText('E2E Game')

  await page.locator('[data-genre="*"]').click()
  await expect(page.locator('[data-genre-item]:visible')).toHaveCount(2)
})
