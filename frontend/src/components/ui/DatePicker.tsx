import { useCallback, useId, useRef, useState } from 'react'
import { DayPicker } from 'react-day-picker'
import { enGB, fr } from 'react-day-picker/locale'
import 'react-day-picker/style.css'
import { useTranslation } from 'react-i18next'
import { CalendarIcon } from '@/components/icons'
import { useLocale } from '@/i18n/useLocale'
import { cn } from '@/lib/cn'
import { formatDate, fromIso, toIso } from '@/lib/dates'
import { controlClasses, type FieldAppearance } from './fieldStyles'
import { useDismiss } from './useDismiss'

export interface DatePickerProps {
  /** Date « AAAA-MM-JJ », ou chaîne vide. */
  value: string
  onChange: (value: string) => void
  /** Bornes « AAAA-MM-JJ » incluses. */
  min?: string
  max?: string
  /** Nom du champ, pour le calendrier ouvert (« Calendrier : Arrivée »). */
  label: string
  id?: string
  appearance?: FieldAppearance
  invalid?: boolean
  disabled?: boolean
  placeholder?: string
  'aria-labelledby'?: string
  'aria-describedby'?: string
}

/**
 * Choix d'une date dans un calendrier aux couleurs du site, à la place du champ natif
 * (dont l'apparence et le format dépendent du navigateur). La date s'affiche dans la
 * langue du site ; Échap ou un clic à côté referme le calendrier.
 */
export function DatePicker({
  value,
  onChange,
  min,
  max,
  label,
  id,
  appearance = 'field',
  invalid = false,
  disabled = false,
  placeholder,
  ...aria
}: DatePickerProps) {
  const { t } = useTranslation()
  const { locale } = useLocale()
  const [open, setOpen] = useState(false)
  const rootRef = useRef<HTMLDivElement>(null)
  const buttonRef = useRef<HTMLButtonElement>(null)
  const panelId = useId()

  const close = useCallback(() => setOpen(false), [])
  useDismiss(open, rootRef, close, buttonRef)

  const selected = value ? fromIso(value) : undefined
  const disabledDays = [...(min ? [{ before: fromIso(min) }] : []), ...(max ? [{ after: fromIso(max) }] : [])]

  return (
    <div ref={rootRef} className="relative">
      <button
        ref={buttonRef}
        id={id}
        type="button"
        disabled={disabled}
        aria-haspopup="dialog"
        aria-expanded={open}
        aria-controls={open ? panelId : undefined}
        aria-invalid={invalid || undefined}
        {...aria}
        onClick={() => setOpen(!open)}
        className={controlClasses(appearance, invalid, 'flex items-center justify-between gap-2 text-left')}
      >
        <span className={cn('truncate', !value && 'text-muted')}>
          {value ? formatDate(value, locale === 'fr' ? 'fr-FR' : 'en-GB') : (placeholder ?? t('common.chooseDate'))}
        </span>
        <CalendarIcon aria-hidden="true" className="shrink-0 text-muted" />
      </button>

      {open && (
        <div
          id={panelId}
          role="dialog"
          aria-label={t('common.calendarFor', { label })}
          className="date-picker absolute top-full left-0 z-40 mt-2 w-[min(340px,calc(100vw-32px))] rounded-[20px] border border-line bg-white p-3 shadow-[0_16px_40px_rgba(43,38,34,0.14)]"
        >
          <DayPicker
            mode="single"
            locale={locale === 'fr' ? fr : enGB}
            weekStartsOn={1}
            selected={selected}
            defaultMonth={selected ?? (min ? fromIso(min) : undefined)}
            startMonth={min ? fromIso(min) : undefined}
            endMonth={max ? fromIso(max) : undefined}
            disabled={disabledDays}
            showOutsideDays={false}
            autoFocus
            onSelect={(date) => {
              if (!date) return
              onChange(toIso(date))
              setOpen(false)
              buttonRef.current?.focus()
            }}
          />
        </div>
      )}
    </div>
  )
}
