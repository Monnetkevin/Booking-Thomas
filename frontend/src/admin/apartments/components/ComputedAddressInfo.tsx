import { useTranslation } from 'react-i18next'
import { Badge } from '@/components/ui/Badge'
import type { Apartment } from '../types'

const tones = { pending: 'neutral', found: 'success', not_found: 'warning' } as const

/** Adresse de la page et position sur la carte, calculées par l'API (non modifiables). */
export function ComputedAddressInfo({ apartment }: { apartment: Apartment }) {
  const { t } = useTranslation()

  return (
    <dl className="grid gap-3 rounded-xl bg-sand p-4 text-sm sm:grid-cols-2">
      <div>
        <dt className="font-semibold">{t('admin.apartments.computed.pageUrl')}</dt>
        <dd className="mt-0.5 break-all">/logements/{apartment.slug}</dd>
        <dd className="mt-0.5 text-muted">{t('admin.apartments.computed.pageUrlHint')}</dd>
      </div>
      <div>
        <dt className="font-semibold">{t('admin.apartments.computed.map')}</dt>
        <dd className="mt-1" role="status">
          <Badge tone={tones[apartment.geocoding]}>{t(`admin.apartments.computed.${apartment.geocoding}`)}</Badge>
        </dd>
      </div>
    </dl>
  )
}
