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
  layout?: 'bar' | 'stack'
}

const label = 'flex flex-col gap-1 text-xs font-bold tracking-[0.06em] text-muted uppercase'
const input = 'min-h-11 w-full rounded-[10px] border border-field bg-white px-3 text-[15px] font-medium text-ink normal-case tracking-normal'

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
        <input
          type="date"
          className={input}
          min={addDays(today, 1)}
          value={arrival}
          required
          onChange={(event) => {
            setArrival(event.target.value)
            if (departure && event.target.value >= departure) setDeparture('')
            setError(null)
          }}
        />
      </label>
      <label className={label}>
        {t('search.departure')}
        <input
          type="date"
          className={input}
          min={arrival ? addDays(arrival, 1) : addDays(today, 2)}
          value={departure}
          required
          onChange={(event) => {
            setDeparture(event.target.value)
            setError(null)
          }}
        />
      </label>
      <label className={cn(label, layout === 'stack' && 'col-span-2')}>
        {t('search.guests')}
        <select className={input} value={guests} onChange={(event) => setGuests(Number(event.target.value))}>
          {Array.from({ length: 8 }, (_, i) => i + 1).map((count) => (
            <option key={count} value={count}>
              {t('search.guests', { count })}
            </option>
          ))}
        </select>
      </label>
      <Button type="submit" size="lg" className={cn(layout === 'stack' && 'col-span-2', layout === 'bar' && 'sm:col-span-2 lg:col-span-1')}>
        {submitLabel ?? t('search.submit')}
      </Button>
      {error && (
        <p role="alert" className={cn('text-sm font-medium text-danger', layout === 'stack' ? 'col-span-2' : 'sm:col-span-2 lg:col-span-4')}>
          {error}
        </p>
      )}
    </form>
  )
}
