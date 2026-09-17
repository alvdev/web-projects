import { describe, expect, it } from 'vitest'
import {
  findCityMatch,
  findCountryMatch,
  getCountryForTimezone,
  getDisplayLabel,
  getUtcOffset,
  isValidTimezone,
  normalize,
} from '../../assets/src/js/timezones.js'

describe('normalize', () => {
  it('lowercases and strips accents', () => {
    expect(normalize('México')).toBe('mexico')
    expect(normalize('España')).toBe('espana')
    expect(normalize('SÃO PAULO')).toBe('sao paulo')
  })

  it('strips accents only in the à–ü range', () => {
    expect(normalize('Köln')).toBe('koln')
    expect(normalize('Łódź')).toBe('łodź')
    expect(normalize('Straße')).toBe('straße')
  })
})

describe('findCityMatch', () => {
  it('matches exact, prefix and substring keys', () => {
    expect(findCityMatch('Madrid')).toBe('Europe/Madrid')
    expect(findCityMatch('londres')).toBe('Europe/London')
    expect(findCityMatch('amst')).toBe('Europe/Amsterdam')
    expect(findCityMatch('sterdam')).toBe('Europe/Amsterdam')
    expect(findCityMatch('México')).toBe('America/Mexico_City')
  })

  it('returns null for empty or unknown queries', () => {
    expect(findCityMatch('')).toBeNull()
    expect(findCityMatch('  ')).toBeNull()
    expect(findCityMatch('zzzzzzz')).toBeNull()
  })
})

describe('findCountryMatch', () => {
  it('matches exact and accent-insensitive keys', () => {
    expect(findCountryMatch('España')).toEqual(['Europe/Madrid', 'Atlantic/Canary', 'Africa/Ceuta'])
    expect(findCountryMatch('espana')).toEqual(['Europe/Madrid', 'Atlantic/Canary', 'Africa/Ceuta'])
    expect(findCountryMatch('mex')).toEqual(findCountryMatch('Mexico'))
    expect(findCountryMatch('Mexico')).toContain('America/Mexico_City')
  })

  it('returns null for unknown queries', () => {
    expect(findCountryMatch('zzzzzzz')).toBeNull()
  })
})

describe('getCountryForTimezone', () => {
  it('finds the first country for a timezone', () => {
    expect(getCountryForTimezone('Europe/Madrid')).toBe('España')
  })

  it('returns null for unknown timezones', () => {
    expect(getCountryForTimezone('Etc/GMT+5')).toBeNull()
  })
})

describe('getDisplayLabel', () => {
  it('combines country and zone name', () => {
    expect(getDisplayLabel('Europe/Madrid')).toBe('España - Península y Baleares')
  })

  it('returns the raw timezone when no country is known', () => {
    expect(getDisplayLabel('Etc/GMT+5')).toBe('Etc/GMT+5')
  })
})

describe('isValidTimezone', () => {
  it('accepts valid IANA zones and UTC', () => {
    expect(isValidTimezone('UTC')).toBe(true)
    expect(isValidTimezone('Europe/Madrid')).toBe(true)
    expect(isValidTimezone('America/Argentina/Buenos_Aires')).toBe(true)
  })

  it('rejects invalid zones', () => {
    expect(isValidTimezone('Not/AZone')).toBe(false)
    expect(isValidTimezone('')).toBe(false)
  })
})

describe('getUtcOffset', () => {
  it('returns +00:00 for UTC', () => {
    expect(getUtcOffset('UTC')).toBe('+00:00')
  })

  it('returns hour offsets for real zones', () => {
    expect(getUtcOffset('Europe/Madrid')).toMatch(/^\+0?[12](:00)?$/)
    expect(getUtcOffset('America/New_York')).toMatch(/^-0?[45](:00)?$/)
    expect(getUtcOffset('Asia/Kolkata')).toBe('+5:30')
  })

  it('falls back to +00:00 for invalid zones', () => {
    expect(getUtcOffset('Not/AZone')).toBe('+00:00')
  })
})
