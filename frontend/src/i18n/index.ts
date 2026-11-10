import i18n from 'i18next'
import { initReactI18next } from 'react-i18next'
import en from './locales/en.json'
import fr from './locales/fr.json'

export const LOCALES = ['fr', 'en'] as const
export type Locale = (typeof LOCALES)[number]
export const DEFAULT_LOCALE: Locale = 'fr'

// Le back-office n'existe qu'en français : ses clés (admin.*) ne figurent que dans fr.json,
// et l'anglais retombe sur le français pour toute clé absente.
void i18n.use(initReactI18next).init({
  resources: { fr: { translation: fr }, en: { translation: en } },
  lng: DEFAULT_LOCALE,
  fallbackLng: DEFAULT_LOCALE,
  interpolation: { escapeValue: false }, // React échappe déjà
})

/** Les pages anglaises portent le préfixe /en ; tout le reste est en français. */
export function localeFromPath(pathname: string): Locale {
  return pathname === '/en' || pathname.startsWith('/en/') ? 'en' : 'fr'
}

/** Chemin sans le préfixe de langue : « /en/logements » → « /logements ». */
export function stripLocale(pathname: string): string {
  if (localeFromPath(pathname) === 'fr') return pathname
  return pathname.slice(3) || '/'
}

/** « /logements » dans la langue voulue : « /logements » ou « /en/logements ». */
export function localizedPath(path: string, locale: Locale): string {
  const clean = path.startsWith('/') ? path : `/${path}`
  if (locale === DEFAULT_LOCALE) return clean
  return clean === '/' ? `/${locale}` : `/${locale}${clean}`
}

export function applyLocale(locale: Locale): void {
  if (i18n.language !== locale) void i18n.changeLanguage(locale)
  document.documentElement.lang = locale
}

export default i18n
