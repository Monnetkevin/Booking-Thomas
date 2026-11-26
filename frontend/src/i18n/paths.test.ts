import { describe, expect, it } from 'vitest'
import { localeFromPath, localizedPath, stripLocale } from './index'

describe('chemins localisés', () => {
  it('détecte la langue depuis le préfixe /en', () => {
    expect(localeFromPath('/')).toBe('fr')
    expect(localeFromPath('/logements/le-cocon')).toBe('fr')
    expect(localeFromPath('/en')).toBe('en')
    expect(localeFromPath('/en/logements')).toBe('en')
    expect(localeFromPath('/english-page')).toBe('fr')
  })

  it('construit le chemin dans la langue voulue', () => {
    expect(localizedPath('/', 'fr')).toBe('/')
    expect(localizedPath('/', 'en')).toBe('/en')
    expect(localizedPath('/logements', 'en')).toBe('/en/logements')
    expect(localizedPath('contact', 'fr')).toBe('/contact')
  })

  it('retire le préfixe de langue', () => {
    expect(stripLocale('/en')).toBe('/')
    expect(stripLocale('/en/logements/le-cocon')).toBe('/logements/le-cocon')
    expect(stripLocale('/logements')).toBe('/logements')
  })
})
