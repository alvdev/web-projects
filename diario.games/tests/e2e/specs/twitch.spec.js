import { test, expect } from '@playwright/test'

test('homepage twitch widget switches tabs', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' })

  const widget = page.locator('[data-twitch-widget]')
  await expect(widget).toBeVisible()

  await expect(widget.locator('[data-twitch-tab-content="games"] [data-twitch-row]')).toHaveCount(3)

  await widget.locator('[data-twitch-tab="streamers"]').click()
  await expect(widget.locator('[data-twitch-tab-content="streamers"] [data-twitch-row]')).toHaveCount(3)

  await widget.locator('[data-twitch-tab="spanish"]').click()
  await expect(widget.locator('[data-twitch-tab-content="spanish"] [data-twitch-row]')).toHaveCount(2)
})

test('twitch stats page renders tabs, tracker columns and sparklines', async ({ page }) => {
  await page.goto('/twitch-stats', { waitUntil: 'domcontentloaded' })

  await expect(page.locator('h1')).toContainText('Twitch Charts')

  const gameRows = page.locator('[data-twitch-page-content="games"] [data-twitch-page-row]')
  await expect(gameRows).toHaveCount(3)
  await expect(page.locator('[data-twitch-page-content="games"] svg').first()).toBeVisible()

  await page.locator('[data-twitch-page-tab="streamers"]').click()
  await expect(page.locator('[data-twitch-page-content="streamers"] [data-twitch-page-row]')).toHaveCount(3)
})

test('by-igdb-id redirects to imported pages and falls back to twitch', async ({ page }) => {
  await page.goto('/games/by-igdb-id/1')
  await expect(page).toHaveURL(/\/e2e-game$/)

  await page.route('https://www.twitch.tv/**', (route) =>
    route.fulfill({ status: 200, contentType: 'text/html', body: '<html>twitch</html>' })
  )
  await page.goto('/games/by-igdb-id/987654')
  await expect(page).toHaveURL(/twitch\.tv\/directory\/category\/987654/)
})
