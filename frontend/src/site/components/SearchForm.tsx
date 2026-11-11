import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router'
import { Button } from '@/components/ui/Button'
import { useLocale } from '@/i18n/useLocale'
import { cn } from '@/lib/cn'
import { addDays, isoDate } from '@/site/catalog/stayCalendar'
import { searchToParams } from '@/site/catalog/searchParams'
import type { SearchCriteria } from '@/site/catalog/types'

interface SearchFormProps {
  initial?: SearchCriteria | null
  submitLabel?: string
  /** bar : barre de la page des logements ; stack : bloc compact ; pill : barre arrondie de l'accueil. */
  layout?: 'bar' | 'stack' | 'pill'
}

const label = 'flex flex-col gap-1 text-xs font-bold tracking-[0.06em] text-muted uppercase'
const input = 'min-h-11 w-full rounded-[10px] border border-field bg-white px-3 text-[15px] font-medium text-ink normal-case tracking-normal'
// Pilule : champs encadrés sur téléphone, sans cadre et séparés par un filet à partir de la tablette.
const pillLabel = 'flex min-w-0 flex-1 flex-col gap-1 text-[11px] font-bold tracking-[0.06em] text-muted uppercase md:gap-0.5'
const pillInput = cn(input, 'md:min-h-9 md:rounded-none md:border-0 md:bg-transparent md:px-0 md:font-semibold')
const pillDivider = 'hidden h-8 w-px shrink-0 bg-[#e4d8c8] md:block'

/** Arrivée, départ et voyageurs → page des logements filtrée (critères dans l'URL). */
export function SearchForm({ initial, submitLabel, layout = 'bar' }: SearchFormProps) {
  const { t } = useTranslation()
  const { to } = useLocale()
  const navigate = useNavigate()
  // Date du jour figée au premier rendu (minimum des champs de date).
  const [today] = useState(() => isoDate(new Date()))
  const [arrival, setArrival] = useState(initial?.arrival ?? '')
  const [departure, setDeparture] = useState(initial?.departure ?? '')
  const [guests, setGuests] = useState(initial?.guests ?? 2)
  const [error, setError] = useState<string | null>(null)

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!arrival || !departure || departure <= arrival) {
      setError(t('search.invalidDates'))
      return
    }
    navigate(`${to('/logements')}?${searchToParams({ arrival, departure, guests })}`)
  }

  const arrivalInput = (className: string) => (
    <input
      type="date"
      className={className}
      min={addDays(today, 1)}
      value={arrival}
      required
      onChange={(event) => {
        setArrival(event.target.value)
        if (departure && event.target.value >= departure) setDeparture('')
        setError(null)
      }}
    />
  )
  const departureInput = (className: string) => (
    <input
      type="date"
      className={className}
      min={arrival ? addDays(arrival, 1) : addDays(today, 2)}
      value={departure}
      required
      onChange={(event) => {
        setDeparture(event.target.value)
        setError(null)
      }}
    />
  )
  const guestsSelect = (className: string) => (
    <select className={className} value={guests} onChange={(event) => setGuests(Number(event.target.value))}>
      {Array.from({ length: 8 }, (_, i) => i + 1).map((count) => (
        <option key={count} value={count}>
          {t('search.guests', { count })}
        </option>
      ))}
    </select>
  )
  const errorMessage = (className: string) =>
    error && (
      <p role="alert" className={cn('text-sm font-medium text-danger', className)}>
        {error}
      </p>
    )

  if (layout === 'pill') {
    return (
      <div className="flex flex-col gap-2">
        <form
          onSubmit={handleSubmit}
          noValidate
          aria-label={t('search.label')}
          className="flex flex-col gap-3 rounded-[28px] border border-[#e4d8c8] bg-white p-4 shadow-[0_12px_32px_rgba(43,38,34,0.08)] md:flex-row md:items-center md:gap-4 md:rounded-full md:py-2 md:pr-2 md:pl-6"
        >
          <label className={pillLabel}>
            {t('search.arrival')}
            {arrivalInput(pillInput)}
          </label>
          <span aria-hidden="true" className={pillDivider} />
          <label className={pillLabel}>
            {t('search.departure')}
            {departureInput(pillInput)}
          </label>
          <span aria-hidden="true" className={pillDivider} />
          <label className={cn(pillLabel, 'md:min-w-[120px] md:flex-none')}>
            {t('search.guests')}
            {guestsSelect(pillInput)}
          </label>
          <button
            type="submit"
            className="min-h-12 shrink-0 rounded-full bg-accent px-7 text-[15px] font-bold text-white transition-colors hover:bg-accent-strong md:min-h-14"
          >
            {submitLabel ?? t('search.submit')}
          </button>
        </form>
        {errorMessage('px-6')}
      </div>
    )
  }

  return (
    <form
      onSubmit={handleSubmit}
      noValidate
      aria-label={t('search.label')}
      className={cn(
        'grid gap-3 rounded-[16px] border border-border bg-white p-4 shadow-[0_8px_24px_rgba(43,38,34,0.06)]',
        layout === 'bar' ? 'sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end' : 'grid-cols-2',
      )}
    >
      <label className={label}>
        {t('search.arrival')}
        {arrivalInput(input)}
      </label>
      <label className={label}>
        {t('search.departure')}
        {departureInput(input)}
      </label>
      <label className={cn(label, layout === 'stack' && 'col-span-2')}>
        {t('search.guests')}
        {guestsSelect(input)}
      </label>
      <Button type="submit" size="lg" className={cn(layout === 'stack' && 'col-span-2', layout === 'bar' && 'sm:col-span-2 lg:col-span-1')}>
        {submitLabel ?? t('search.submit')}
      </Button>
      {errorMessage(layout === 'stack' ? 'col-span-2' : 'sm:col-span-2 lg:col-span-4')}
    </form>
  )
}
