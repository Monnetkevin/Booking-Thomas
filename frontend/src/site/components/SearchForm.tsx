import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/components/ui/Button'
import { useSearchForm } from '@/site/catalog/useSearchForm'
import type { SearchCriteria } from '@/site/catalog/types'

const MAX_GUESTS = 8

// Champs encadrés sur téléphone ; sans cadre et séparés par un filet à partir de la tablette.
const input =
  'min-h-11 w-full rounded-[10px] border border-field bg-white px-3 text-[15px] font-medium tracking-normal text-ink normal-case md:min-h-9 md:rounded-none md:border-0 md:bg-transparent md:px-0 md:font-semibold'

function SearchField({ label, children }: { label: string; children: ReactNode }) {
  return (
    <label className="flex min-w-0 flex-1 flex-col gap-1 text-[11px] font-bold tracking-[0.06em] text-muted uppercase md:gap-0.5">
      {label}
      {children}
    </label>
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
          <input
            type="date"
            className={input}
            min={form.minArrival}
            value={form.arrival}
            required
            onChange={(event) => form.setArrival(event.target.value)}
          />
        </SearchField>
        <Divider />
        <SearchField label={t('search.departure')}>
          <input
            type="date"
            className={input}
            min={form.minDeparture}
            value={form.departure}
            required
            onChange={(event) => form.setDeparture(event.target.value)}
          />
        </SearchField>
        <Divider />
        <div className="md:w-[120px] md:flex-none">
          <SearchField label={t('search.guests')}>
            <select className={input} value={form.guests} onChange={(event) => form.setGuests(Number(event.target.value))}>
              {Array.from({ length: MAX_GUESTS }, (_, i) => i + 1).map((count) => (
                <option key={count} value={count}>
                  {t('search.guests', { count })}
                </option>
              ))}
            </select>
          </SearchField>
        </div>
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
