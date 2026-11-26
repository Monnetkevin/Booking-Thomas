import 'i18next'
import type admin from './locales/admin.fr.json'
import type fr from './locales/fr.json'

// Clés de traduction typées : t('nav.home') est vérifié à la compilation.
declare module 'i18next' {
  interface CustomTypeOptions {
    defaultNS: 'translation'
    // Les textes du back-office (admin.*) sont chargés avec son code : voir admin/i18n.ts.
    resources: { translation: typeof fr & { admin: typeof admin } }
  }
}
