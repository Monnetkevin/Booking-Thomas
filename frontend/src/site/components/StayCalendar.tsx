import { DayPicker, type DateRange } from 'react-day-picker'
import { enGB, fr } from 'react-day-picker/locale'
import 'react-day-picker/style.css'
import { useTranslation } from 'react-i18next'
import { useLocale } from '@/i18n/useLocale'
import { fromIso, isoDate, type StayCalendarRules } from '@/site/catalog/stayCalendar'
import type { AvailabilityCalendar } from '@/site/catalog/types'

interface StayCalendarProps {
  calendar: AvailabilityCalendar
  rules: StayCalendarRules
  arrival: string | null
  departure: string | null
  onChange: (arrival: string | null, departure: string | null) => void
}

/**
 * Choix de l'arrivée puis du départ. Une date de départ est possible même si sa nuit est
 * prise (le départ libère la chambre) ; les nuits prises sont barrées.
 */
export function StayCalendar({ calendar, rules, arrival, departure, onChange }: StayCalendarProps) {
  const { t } = useTranslation()
  const { locale } = useLocale()
  const choosingDeparture = arrival !== null && departure === null

  const selectable = (date: Date) => {
    const day = isoDate(date)
    // Les jours du séjour choisi restent affichés comme tels, jamais grisés.
    if (arrival && departure && day >= arrival && day <= departure) return true
    return choosingDeparture ? rules.canDepart(arrival, day) || rules.canArrive(day) : rules.canArrive(day)
  }

  const handleClick = (date: Date) => {
    const day = isoDate(date)
    if (choosingDeparture && rules.canDepart(arrival, day)) onChange(arrival, day)
    else if (rules.canArrive(day)) onChange(day, null)
    else onChange(null, null)
  }

  const range: DateRange | undefined = arrival ? { from: fromIso(arrival), to: departure ? fromIso(departure) : undefined } : undefined

  return (
    <div className="stay-calendar">
      <DayPicker
        locale={locale === 'fr' ? fr : enGB}
        weekStartsOn={1}
        startMonth={fromIso(calendar.from)}
        endMonth={fromIso(calendar.last_arrival)}
        defaultMonth={arrival ? fromIso(arrival) : fromIso(calendar.first_arrival)}
        disabled={(date) => !selectable(date)}
        modifiers={{
          booked: (date) => !rules.isNightFree(isoDate(date)),
          range_start: range?.from,
          range_end: range?.to,
          range_middle: range?.from && range.to ? { after: range.from, before: range.to } : undefined,
        }}
        onDayClick={handleClick}
        showOutsideDays={false}
        aria-label={t('booking.calendarLabel')}
      />
      <div className="mt-2 flex flex-wrap gap-x-5 gap-y-2 text-[13px] text-muted">
        <span className="flex items-center gap-2">
          <span aria-hidden="true" className="text-muted line-through">12</span>
          {t('booking.legendUnavailable')}
        </span>
        <span className="flex items-center gap-2">
          <span aria-hidden="true" className="size-3 rounded-full bg-accent" />
          {t('booking.legendStay')}
        </span>
      </div>
    </div>
  )
}
