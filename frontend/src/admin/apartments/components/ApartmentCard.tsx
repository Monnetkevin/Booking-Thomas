import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { formatEuros } from '@/lib/money'
import type { ApartmentListItem } from '../types'
import { StatusBadge } from './StatusBadge'

const percent = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 })

/** Carte d'un logement : photo, ville, statut, capacité, occupation du mois, prix, liens. */
export function ApartmentCard({ apartment, occupancy, showPrice }: { apartment: ApartmentListItem; occupancy: number | null | undefined; showPrice: boolean }) {
  const { t } = useTranslation()
  const image = apartment.card_url ?? apartment.cover_url

  return (
    <article className="flex flex-col overflow-hidden rounded-[var(--radius-card)] border border-border bg-white">
      {image ? (
        <img src={image} alt="" className="aspect-[16/10] w-full object-cover" loading="lazy" />
      ) : (
        <div aria-hidden="true" className="aspect-[16/10] w-full bg-cream" />
      )}
      <div className="flex flex-1 flex-col gap-3 p-4">
        <div className="flex items-start justify-between gap-2">
          <div className="min-w-0">
            <h2 className="truncate font-sans text-[17px] font-bold tracking-normal">{apartment.name}</h2>
            {apartment.city && <p className="text-sm text-muted">{apartment.city}</p>}
          </div>
          <StatusBadge status={apartment.status} />
        </div>
        <p className="flex justify-between gap-2 text-sm text-muted">
          <span>
            {t('admin.apartments.guests', { count: apartment.capacity })}
            {apartment.bedrooms !== null && ` · ${t('admin.apartments.bedrooms', { count: apartment.bedrooms })}`}
          </span>
          <span>{t('admin.apartments.occupancy', { value: occupancy === null || occupancy === undefined ? '—' : `${percent.format(occupancy)} %` })}</span>
        </p>
        {showPrice && (
          <p className="text-lg font-bold">
            {apartment.base_price === null ? <span className="text-sm font-normal text-muted">{t('admin.apartments.noRate')}</span> : t('admin.apartments.perNight', { price: formatEuros(apartment.base_price) })}
          </p>
        )}
        <div className="mt-auto flex gap-2">
          {apartment.status === 'published' && (
            <a href={`/logements/${apartment.slug}`} target="_blank" rel="noopener" className={`${buttonClasses('secondary')} flex-1 justify-center`}>
              {t('admin.apartments.view')}
            </a>
          )}
          <Link
            to={`/admin/appartements/${apartment.id}`}
            className={`${buttonClasses('primary')} flex-1 justify-center`}
            aria-label={t('admin.apartments.editLabel', { name: apartment.name })}
          >
            {t('admin.common.edit')}
          </Link>
        </div>
      </div>
    </article>
  )
}
