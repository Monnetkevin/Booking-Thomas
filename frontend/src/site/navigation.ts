/**
 * Liens du site public, déclarés ici : l'en-tête et le pied de page les affichent sans
 * les connaître (ouvert à l'extension, fermé à la modification).
 * Les chemins sont ceux de la version française ; useLocale().to les adapte à la langue.
 */
export interface SiteLink {
  path: string
  labelKey: 'nav.apartments' | 'nav.contact' | 'footer.legal' | 'footer.terms' | 'footer.privacy'
}

export const mainNavigation: SiteLink[] = [
  { path: '/logements', labelKey: 'nav.apartments' },
  { path: '/contact', labelKey: 'nav.contact' },
]

export const legalNavigation: SiteLink[] = [
  { path: '/mentions-legales', labelKey: 'footer.legal' },
  { path: '/cgv', labelKey: 'footer.terms' },
  { path: '/confidentialite', labelKey: 'footer.privacy' },
]

/** Page vers laquelle mènent les boutons « Réserver ». */
export const BOOKING_PATH = '/logements'

/** Back-office : non traduit, exclu des moteurs par robots.txt. */
export const ADMIN_PATH = '/admin'
