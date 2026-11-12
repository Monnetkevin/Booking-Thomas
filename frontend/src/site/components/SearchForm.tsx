import { useId, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/components/ui/Button'
import { DatePicker } from '@/components/ui/DatePicker'
import { Select } from '@/components/ui/Select'
import { useSearchForm } from '@/site/catalog/useSearchForm'
import type { SearchCriteria } from '@/site/catalog/types'

const MAX_GUESTS = 8

/**
 * Case de la pilule : petit libellé au-dessus du contrôle. Pas d'élément <label> autour,
 * car le calendrier ouvert contient lui-même des boutons : le contrôle est relié au
 * libellé par aria-labelledby.
 */
function SearchField({ label, className, children }: { label: string; className?: string; children: (labelId: string) => ReactNode }) {
  const labelId = useId()

  return (
    <div className={className ?? 'flex min-w-0 flex-1 flex-col gap-1 md:gap-0.5'}>
      <span id={labelId} className="text-[11px] font-bold tracking-[0.06em] text-muted uppercase">
        {label}
      </span>
      {children(labelId)}
    </div>
  )
}

function Divider() {
  return <span aria-hidden="true" className="hidden h-8 w-px shrink-0 bg-[#e4d8c8] md:block" />
}

/** Recherche par dates et voyageurs, en pilule : accueil et page des logements. */
export function SearchForm({ initial, submitLabel }: { initial?: SearchCriteria | null; submitLabel?: string }) {
  const { t } = useTranslation()
  const form = useSearchForm(initial)

  return (
    <div className="flex flex-col gap-2">
      <form
        onSubmit={form.submit}
        noValidate
        aria-label={t('search.label')}
        className="flex flex-col gap-3 rounded-[28px] border border-[#e4d8c8] bg-white p-4 shadow-[0_12px_32px_rgba(43,38,34,0.08)] md:flex-row md:items-center md:gap-4 md:rounded-full md:py-2 md:pr-2 md:pl-6"
      >
        <SearchField label={t('search.arrival')}>
          {(labelId) => (
            <DatePicker
              appearance="pill"
              label={t('search.arrival')}
              aria-labelledby={labelId}
              value={form.arrival}
              min={form.minArrival}
              onChange={form.setArrival}
            />
          )}
        </SearchField>
        <Divider />
        <SearchField label={t('search.departure')}>
          {(labelId) => (
            <DatePicker
              appearance="pill"
              label={t('search.departure')}
              aria-labelledby={labelId}
              value={form.departure}
              min={form.minDeparture}
              onChange={form.setDeparture}
            />
          )}
        </SearchField>
        <Divider />
        <SearchField label={t('search.guests')} className="flex flex-col gap-1 md:w-[130px] md:flex-none md:gap-0.5">
          {(labelId) => (
            <Select
              appearance="pill"
              aria-labelledby={labelId}
              value={form.guests}
              onChange={(event) => form.setGuests(Number(event.target.value))}
              options={Array.from({ length: MAX_GUESTS }, (_, i) => ({ value: i + 1, label: t('search.guests', { count: i + 1 }) }))}
            />
          )}
        </SearchField>
        <Button type="submit" size="xl" className="shrink-0">
          {submitLabel ?? t('search.submit')}
        </Button>
      </form>
      {form.error && (
        <p role="alert" className="px-6 text-sm font-medium text-danger">
          {form.error}
        </p>
      )}
    </div>
  )
}
