import { test, expect } from '@playwright/test'

const importCandidate = {
  slug: 'e2e-remote',
  name: 'E2E Remote Game',
  cover: '',
  platforms: 'PC',
  year: '2020',
  hasSteam: false,
  exists: false,
  igdbId: 42,
}

function mockSearch(page) {
  return page.route('**/steam-stats-api/search**', (route) => {
    const url = new URL(route.request().url())
    const isIgdb = url.searchParams.get('source') === 'igdb'

    return route.fulfill({
      json: {
        results: isIgdb ? [] : [importCandidate],
        fromIgdb: isIgdb,
      },
    })
  })
}

test('header search renders mocked results', async ({ page }) => {
  await mockSearch(page)
  await page.goto('/e2e-game')

  await page.locator('#steam-header-search input').fill('remote')

  const result = page.locator('.steam-search-results a[href="/e2e-remote"]')
  await expect(result).toBeVisible()
  await expect(result).toHaveAttribute('data-importing', '')
  await expect(result).toContainText('E2E Remote Game')
})

test('import overlay polls progress and redirects', async ({ page }) => {
  let progressCalls = 0

  await mockSearch(page)
  await page.route('**/steam-stats-api/import-game*', (route) =>
    route.fulfill({ json: { id: 'imp1', ok: true } })
  )
  await page.route('**/steam-stats-api/import-progress/imp1', (route) => {
    progressCalls++
    if (progressCalls === 1) {
      return route.fulfill({ json: { phase: 'metadata', text: 'Obteniendo información...' } })
    }
    return route.fulfill({ json: { ready: true, slug: 'e2e-second' } })
  })

  await page.goto('/e2e-game')
  await page.locator('#steam-header-search input').fill('remote')
  await page.locator('.steam-search-results a[href="/e2e-remote"]').click()

  const overlay = page.locator('[role="alertdialog"]')
  await expect(overlay).toBeVisible()
  await expect(overlay.locator('.import-progress-text')).toHaveText('Obteniendo información...')
  await expect(overlay.locator('.import-progress-text')).toHaveText('¡Listo! Redirigiendo…')

  await page.waitForURL('**/e2e-second')
})

test('favorites persist across reload', async ({ page }) => {
  await page.goto('/e2e-game')

  const star = page.locator('.site-fav[data-slug="e2e-game"]').first()
  await expect(star).toHaveText('☆')

  await star.click()
  await expect(star).toHaveText('★')

  const stored = await page.evaluate(() =>
    JSON.parse(localStorage.getItem('site-favorites-v1') || '{}')
  )
  expect(Object.keys(stored)).toEqual(['e2e-game'])
  expect(stored['e2e-game'].title).toBe('E2E Game')

  await page.reload()
  await expect(page.locator('.site-fav[data-slug="e2e-game"]').first()).toHaveText('★')
})
