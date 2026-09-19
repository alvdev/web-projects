import { mkdtempSync, rmSync, writeFileSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { describe, expect, it } from 'vitest'
import {
  buildProxyUrl,
  computePeak,
  loadEnv,
  mergeDailyHourlyPoints,
  mergeGraphPoints,
  parseDataTableRows,
  parseDomPeak,
} from '../../scripts/lib/steamdb-parsers.mjs'

describe('loadEnv', () => {
  it('parses key=value lines and ignores comments and blanks', () => {
    const dir = mkdtempSync(join(tmpdir(), 'steamdb-env-'))
    const file = join(dir, '.env')
    writeFileSync(file, '# comment\n\nPROXY_HOST=1.2.3.4\nPROXY_PORT=8080\nKEY=value=with=equals\nBADLINE\n')

    try {
      expect(loadEnv(file)).toEqual({
        PROXY_HOST: '1.2.3.4',
        PROXY_PORT: '8080',
        KEY: 'value=with=equals',
      })
    } finally {
      rmSync(dir, { recursive: true, force: true })
    }
  })

  it('returns an empty object for missing files', () => {
    expect(loadEnv('/nonexistent/.env')).toEqual({})
  })
})

describe('buildProxyUrl', () => {
  it('returns null without host or port', () => {
    expect(buildProxyUrl({})).toBeNull()
    expect(buildProxyUrl({ PROXY_HOST: '1.2.3.4' })).toBeNull()
  })

  it('builds server url and adds credentials when present', () => {
    expect(buildProxyUrl({ PROXY_HOST: '1.2.3.4', PROXY_PORT: '8080' })).toEqual({
      server: 'http://1.2.3.4:8080',
    })
    expect(buildProxyUrl({ PROXY_HOST: '1.2.3.4', PROXY_PORT: '8080', PROXY_USER: 'u', PROXY_PASS: 'p' })).toEqual({
      server: 'http://1.2.3.4:8080',
      username: 'u',
      password: 'p',
    })
  })
})

describe('mergeGraphPoints', () => {
  const daily = { start: 1000, step: 10, values: [5, 6] }
  const hourly = { start: 1000, step: 5, values: [1, 2, 3] }

  it('dedupes by second-timestamp, hourly first, sorted ascending', () => {
    expect(mergeGraphPoints(daily, hourly, null)).toEqual([
      [1000000, 1],
      [1005000, 2],
      [1010000, 3],
    ])
  })

  it('uses highstock data when it is longer than the merged points', () => {
    const highstock = [[2000000, 10], [1000000, 20], [3000000, 30], [4000000, 40]]

    expect(mergeGraphPoints(daily, hourly, highstock)).toEqual([
      [1000000, 20],
      [2000000, 10],
      [3000000, 30],
      [4000000, 40],
    ])
  })

  it('filters invalid highstock points and keeps merged data when not longer', () => {
    expect(mergeGraphPoints(daily, hourly, [[1000000, 0]])).toEqual([
      [1000000, 1],
      [1005000, 2],
      [1010000, 3],
    ])
  })

  it('handles missing data sets', () => {
    expect(mergeGraphPoints(null, null, null)).toEqual([])
  })
})

describe('mergeDailyHourlyPoints', () => {
  it('adds daily points first, then unseen hourly points', () => {
    const daily = { start: 1000, step: 10, values: [5, 6] }
    const hourly = { start: 1000, step: 5, values: [1, 2] }

    expect(mergeDailyHourlyPoints(daily, hourly)).toEqual([
      [1000000, 5],
      [1005000, 2],
      [1010000, 6],
    ])
  })
})

describe('computePeak', () => {
  it('returns the max value with its timestamp', () => {
    expect(computePeak([1, 9, 4], 1000, 60)).toEqual({ peak: 9, timestamp: 1060 })
  })

  it('returns null for empty or all-zero series', () => {
    expect(computePeak([], 1000, 60)).toBeNull()
    expect(computePeak([0, 0], 1000, 60)).toBeNull()
    expect(computePeak(undefined, 1000, 60)).toBeNull()
  })
})

describe('parseDataTableRows', () => {
  it('maps rows, parses numbers and object cells, assigns ranks', () => {
    const data = [
      ['1', '<a href="/app/730/"><img src="x"></a>', '<a href="/app/730/">Counter-Strike 2</a>', { display: '1,234', '@data-sort': '1234' }, '5,000', '1,000,000'],
      ['2', '<a href="/app/570/">x</a>', '<a href="/app/570/">Dota 2</a>', '42', '50', '800'],
    ]
    const names = ['Counter-Strike 2', 'Dota 2']

    expect(parseDataTableRows(data, names, 10)).toEqual([
      { rank: 1, appid: 730, name: 'Counter-Strike 2', current: 1234, peak_24h: 5000, peak_all_time: 1000000 },
      { rank: 2, appid: 570, name: 'Dota 2', current: 42, peak_24h: 50, peak_all_time: 800 },
    ])
  })

  it('skips short rows and rows without an appid, honoring maxRows', () => {
    const data = [
      ['1'],
      ['1', 'no app link', 'Game', '1', '2', '3'],
      ['1', '<a href="/app/10/">x</a>', 'Ten', '1', '2', '3'],
      ['2', '<a href="/app/20/">x</a>', 'Twenty', '1', '2', '3'],
    ]

    const rows = parseDataTableRows(data, ['', '', 'Ten', 'Twenty'], 1)

    expect(rows).toEqual([
      { rank: 1, appid: 10, name: 'Ten', current: 1, peak_24h: 2, peak_all_time: 3 },
    ])
  })
})

describe('parseDomPeak', () => {
  it('extracts the all-time peak number', () => {
    expect(parseDomPeak('Players\n1,234,567\nall-time peak')).toBe(1234567)
  })

  it('returns null when the pattern is missing', () => {
    expect(parseDomPeak('nothing here')).toBeNull()
    expect(parseDomPeak(null)).toBeNull()
  })
})
