import { test, expect } from '@playwright/test'

test('ticker keeps a single list of five posts', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' })

  await expect(page.locator('[data-home-ticker]')).toBeVisible()
  await expect(page.locator('[data-ticker-card]')).toHaveCount(5)
  await expect(page.locator('[data-spotlight-card]')).toHaveCount(5)
})

test('selector advances and syncs the spotlight every 5 seconds', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' })

  const visibleSpotlight = page.locator('[data-spotlight-card]:not(.hidden)')
  await expect(page.locator('[data-ticker-card][data-active]').first()).toHaveAttribute('data-ticker-card', '0')
  await expect(visibleSpotlight).toHaveAttribute('data-spotlight-card', '0')

  await expect(visibleSpotlight).toHaveAttribute('data-spotlight-card', '1', { timeout: 8000 })
  await expect(page.locator('[data-ticker-card][data-active]').first()).toHaveAttribute('data-ticker-card', '1')
})

test('selector wraps back to the first post after the fifth', async ({ page }) => {
  await page.clock.install()
  await page.goto('/', { waitUntil: 'domcontentloaded' })

  const active = page.locator('[data-ticker-card][data-active]').first()
  const visibleSpotlight = page.locator('[data-spotlight-card]:not(.hidden)')

  for (let step = 1; step <= 6; step++) {
    await page.clock.fastForward(5100)

    const expected = String(step % 5)
    await expect(active).toHaveAttribute('data-ticker-card', expected)
    await expect(visibleSpotlight).toHaveAttribute('data-spotlight-card', expected)
  }
})

test('clicking a ticker card shows its post in the spotlight without navigating', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' })

  await page.locator('[data-ticker-card]').nth(2).click()

  await expect(page).toHaveURL(/\/$/)
  await expect(page.locator('[data-ticker-card][data-active]').first()).toHaveAttribute('data-ticker-card', '2')

  const visibleSpotlight = page.locator('[data-spotlight-card]:not(.hidden)')
  await expect(visibleSpotlight).toHaveAttribute('data-spotlight-card', '2')
  await expect(visibleSpotlight.locator('a')).toHaveAttribute('href', /e2e-guide-three$/)
})

test('hovering a ticker card pauses auto-advance but does not change the spotlight', async ({ page }) => {
  await page.clock.install()
  await page.goto('/', { waitUntil: 'domcontentloaded' })

  await page.locator('[data-ticker-card]').nth(3).hover()
  await page.clock.fastForward(6000)

  await expect(page.locator('[data-ticker-card][data-active]').first()).toHaveAttribute('data-ticker-card', '0')
  await expect(page.locator('[data-spotlight-card]:not(.hidden)')).toHaveAttribute('data-spotlight-card', '0')
})
