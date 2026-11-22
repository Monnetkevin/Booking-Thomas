/**
 * Fonctionnalités mises de côté, alignées sur config/features.php côté API (qui répond 404
 * quand elles sont désactivées).
 */
export const FEATURES = {
  /** Import des revenus Airbnb et Booking.com (CSV) : repris en fin de projet. */
  platformImport: false,
} as const
