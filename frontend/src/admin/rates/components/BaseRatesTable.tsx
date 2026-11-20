import { useTranslation } from 'react-i18next'
import type { ApartmentListItem } from '@/admin/apartments/types'
import { cn } from '@/lib/cn'
import { formatDate } from '@/lib/dates'
import { formatEuros } from '@/lib/money'

interface BaseRatesTableProps {
  apartments: ApartmentListItem[]
  selectedId: number
  onSelect: (id: number) => void
}

/**
 * Tarifs de base de tous les logements : période en cours (ou prochaine), prix par nuit,
 * supplément week-end, séjour minimum et ménage. Choisir une ligne ouvre ses périodes.
 */
export function BaseRatesTable({ apartments, selectedId, onSelect }: BaseRatesTableProps) {
  const { t } = useTranslation()
  const money = (cents: number | null | undefined) => (cents === null || cents === undefined ? '—' : formatEuros(cents))

  return (
    <div className="relative overflow-x-auto">
      <table className="w-full min-w-[720px] text-left text-sm">
        <thead>
          <tr className="border-b border-border text-xs tracking-wide text-muted uppercase">
            {(['apartment', 'period', 'nightly', 'weekend', 'minStay', 'cleaning'] as const).map((column) => (
              <th key={column} scope="col" className={cn('px-3 py-2.5 font-semibold', column !== 'apartment' && column !== 'period' && 'text-right')}>
                {t(`admin.rates.base.columns.${column}`)}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {apartments.map((apartment) => {
            const rate = apartment.current_rate
            const selected = apartment.id === selectedId
            return (
              <tr key={apartment.id} className={cn('border-b border-line last:border-0', selected && 'bg-accent-soft/50')}>
                <th scope="row" className="px-3 py-2.5 font-semibold">
                  <button
                    type="button"
                    aria-pressed={selected}
                    onClick={() => onSelect(apartment.id)}
                    className="min-h-9 text-left font-semibold underline-offset-4 hover:underline"
                  >
                    {apartment.name}
                  </button>
                </th>
                <td className="px-3 py-2.5 text-muted">
                  {rate ? `${rate.label ?? t('admin.rates.base.unnamed')} · ${formatDate(rate.starts_on)} → ${formatDate(rate.ends_on)}` : t('admin.rates.base.none')}
                </td>
                <td className="px-3 py-2.5 text-right font-semibold tabular-nums">{money(rate?.nightly_price)}</td>
                <td className="px-3 py-2.5 text-right tabular-nums">{rate?.weekend_supplement ? `+ ${formatEuros(rate.weekend_supplement)}` : '—'}</td>
                <td className="px-3 py-2.5 text-right tabular-nums">{t('admin.rates.base.nights', { count: rate?.min_stay ?? apartment.min_stay })}</td>
                <td className="px-3 py-2.5 text-right tabular-nums">{formatEuros(apartment.cleaning_fee)}</td>
              </tr>
            )
          })}
        </tbody>
      </table>
    </div>
  )
}
