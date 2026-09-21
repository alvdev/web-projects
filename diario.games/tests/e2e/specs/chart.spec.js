import { test, expect } from '@playwright/test'

test.describe('steam chart interactions', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/e2e-game')
    await expect(page.locator('#steam-chart-canvas')).toBeVisible()
  })

  test('switches the active range tab', async ({ page }) => {
    const tabOneMonth = page.locator('.steam-range-tab[data-range="1m"]')
    const tab48h = page.locator('.steam-range-tab[data-range="48h"]')

    await expect(tab48h).toHaveClass(/bg-neon-cyan\/20/)

    await tabOneMonth.click()

    await expect(tabOneMonth).toHaveClass(/bg-neon-cyan\/20/)
    await expect(tabOneMonth).toHaveClass(/text-neon-cyan/)
    await expect(tab48h).not.toHaveClass(/bg-neon-cyan\/20/)
  })

  test('selects a timezone from suggestions', async ({ page }) => {
    const input = page.locator('#steam-timezone-input')
    const suggestions = page.locator('#steam-tz-suggestions')

    await expect(input).toHaveValue('España - Península y Baleares')

    await input.click()
    await input.fill('Madrid')

    const option = page.locator('.steam-tz-option[data-tz="Europe/Madrid"]')
    await expect(option).toBeVisible()

    await option.click()

    await expect(input).toHaveValue('España - Península y Baleares')
    await expect(suggestions).toBeHidden()
  })

  test('share button downloads a chart image', async ({ page }) => {
    const downloadPromise = page.waitForEvent('download')

    await page.locator('#steam-share-btn').click()

    const download = await downloadPromise
    expect(download.suggestedFilename()).toBe('e2e-game-chart.png')
    await expect(page.locator('#steam-share-btn')).toHaveText('Compartir')
  })
})
