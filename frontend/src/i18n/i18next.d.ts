import 'i18next'
import type fr from './locales/fr.json'

// Clés de traduction typées : t('nav.home') est vérifié à la compilation.
declare module 'i18next' {
  interface CustomTypeOptions {
    defaultNS: 'translation'
    resources: { translation: typeof fr }
  }
}
