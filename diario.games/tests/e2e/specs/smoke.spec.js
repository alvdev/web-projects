import { test, expect } from '@playwright/test'

test('game page renders the steam chart with seeded data', async ({ page }) => {
  await page.goto('/e2e-game')

  await expect(page.locator('#steam-chart-section')).toBeVisible()
  await expect(page.locator('#steam-chart-canvas')).toBeVisible()
  await expect(page.locator('#steam-current')).toHaveText('1.5K')
  await expect(page.locator('#steam-peak-24h')).toHaveText('1.5K')
  await expect(page.locator('#steam-peak-3m')).toHaveText('1.5K')
  await expect(page.locator('#steam-peak-alltime')).toHaveText('1.8K')
})
