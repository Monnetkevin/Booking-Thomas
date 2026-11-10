import { useTranslation } from 'react-i18next'
import { PencilIcon, TrashIcon } from '@/components/icons'
import { formatDate } from '@/lib/dates'
import { formatEuros } from '@/lib/money'
import type { RatePeriod } from '../types'

interface RatePeriodListProps {
  periods: RatePeriod[]
  apartmentMinStay: number
  selectedId: number | null
  onEdit: (period: RatePeriod) => void
  onDelete: (period: RatePeriod) => void
}

const iconButton = 'flex size-11 items-center justify-center rounded-[10px] hover:bg-sand'

/** Périodes d'un logement : tableau sur ordinateur, cartes sur téléphone. */
export function RatePeriodList({ periods, apartmentMinStay, selectedId, onEdit, onDelete }: RatePeriodListProps) {
  const { t } = useTranslation()

  const name = (period: RatePeriod) => period.label || t('admin.rates.unnamed')
  const dates = (period: RatePeriod) =>
    t('admin.rates.dateRange', { from: formatDate(period.starts_on), to: formatDate(period.ends_on) })
  const minStay = (period: RatePeriod) =>
    period.min_stay === null
      ? t('admin.rates.defaultMinStay', { count: apartmentMinStay })
      : t('admin.rates.nights', { count: period.min_stay })
  const weekend = (period: RatePeriod) =>
    period.weekend_supplement ? `+ ${formatEuros(period.weekend_supplement)}` : t('admin.common.none')

  const actions = (period: RatePeriod) => (
    <span className="flex justify-end">
      <button type="button" className={iconButton} onClick={() => onEdit(period)} aria-label={t('admin.rates.editLabel', { name: name(period) })}>
        <PencilIcon />
      </button>
      <button type="button" className={`${iconButton} text-danger`} onClick={() => onDelete(period)} aria-label={t('admin.rates.deleteLabel', { name: name(period) })}>
        <TrashIcon />
      </button>
    </span>
  )

  return (
    <>
      <ul className="flex flex-col divide-y divide-line md:hidden">
        {periods.map((period) => (
          <li key={period.id} className={`flex items-start justify-between gap-2 py-3 ${period.id === selectedId ? 'bg-accent-soft/40' : ''}`}>
            <span className="flex flex-col gap-0.5 text-sm">
              <strong className="text-[15px]">{name(period)}</strong>
              <span className="text-muted">{dates(period)}</span>
              <span>
                {formatEuros(period.nightly_price)} · {minStay(period)}
                {period.weekend_supplement ? ` · ${t('admin.rates.columns.weekend')} ${weekend(period)}` : ''}
              </span>
            </span>
            {actions(period)}
          </li>
        ))}
      </ul>

      <table className="hidden w-full text-left text-sm md:table">
        <thead>
          <tr className="border-b border-[#c9b8a6] text-xs font-bold tracking-[0.04em] text-muted uppercase">
            <th scope="col" className="py-3 pr-3">{t('admin.rates.columns.period')}</th>
            <th scope="col" className="px-3 py-3">{t('admin.rates.columns.dates')}</th>
            <th scope="col" className="px-3 py-3 text-right">{t('admin.rates.columns.price')}</th>
            <th scope="col" className="px-3 py-3">{t('admin.rates.columns.minStay')}</th>
            <th scope="col" className="px-3 py-3">{t('admin.rates.columns.weekend')}</th>
            <th scope="col" className="py-3 pl-3"><span className="sr-only">{t('admin.rates.columns.actions')}</span></th>
          </tr>
        </thead>
        <tbody>
          {periods.map((period) => (
            <tr key={period.id} className={`border-b border-line last:border-0 ${period.id === selectedId ? 'bg-accent-soft/40' : ''}`}>
              <th scope="row" className="py-2 pr-3 font-semibold">{name(period)}</th>
              <td className="px-3 py-2">{dates(period)}</td>
              <td className="px-3 py-2 text-right tabular-nums">{formatEuros(period.nightly_price)}</td>
              <td className="px-3 py-2">{minStay(period)}</td>
              <td className="px-3 py-2">{weekend(period)}</td>
              <td className="py-2 pl-3">{actions(period)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </>
  )
}
