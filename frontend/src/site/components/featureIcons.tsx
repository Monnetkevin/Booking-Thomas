import type { ComponentType, SVGProps } from 'react'
import { LockIcon, ParkingIcon, PawIcon, WifiIcon } from '@/components/icons'

export type IconComponent = ComponentType<SVGProps<SVGSVGElement>>

/**
 * Icônes des atouts, désignées par une clé dans les traductions. Un nouvel atout
 * s'ajoute ici, sans toucher au composant qui les affiche.
 */
export const featureIcons: Record<string, IconComponent> = {
  parking: ParkingIcon,
  wifi: WifiIcon,
  key: LockIcon,
  pets: PawIcon,
}
