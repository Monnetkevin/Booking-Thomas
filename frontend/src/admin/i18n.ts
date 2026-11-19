import i18n from '@/i18n'
import admin from '@/i18n/locales/admin.fr.json'

/**
 * Textes du back-office (français seulement), dans le morceau de code de l'admin : le
 * site public ne les télécharge pas. Importé en tête de chaque page d'entrée de /admin.
 */
i18n.addResourceBundle('fr', 'translation', { admin }, true, true)
