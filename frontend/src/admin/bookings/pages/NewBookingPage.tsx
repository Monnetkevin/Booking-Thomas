import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { useCurrentUser } from '@/admin/auth/useAuth'
import { Link, useNavigate } from 'react-router'
import { useApartmentList } from '@/admin/apartments/useApartments'
import { PageHeader } from '@/admin/components/PageHeader'
import { useQuotePreview } from '@/admin/rates/useRates'
import { QuoteBreakdown } from '@/components/QuoteBreakdown'
import { ArrowLeftIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Checkbox, DateField, SelectField, TextArea, TextField } from '@/components/ui/Field'
import { Section } from '@/components/ui/Section'
import { ApiError } from '@/lib/api'
import { toIso } from '@/lib/dates'
import { emptyManualBooking, manualBookingInput, type ManualBookingFormErrors, type ManualBookingFormValues } from '../manualBookingForm'
import { useCreateBooking } from '../useAdminBookings'

const QUOTE_FIELDS: readonly (keyof ManualBookingFormValues)[] = ['apartmentId', 'arrival', 'departure', 'adults', 'children']

/**
 * Réservation prise par téléphone ou par une connaissance : confirmée tout de suite, au
 * prix calculé ou à un total fixé par l'hôte, réglée hors ligne ou plus tard.
 */
export function NewBookingPage() {
  const { t } = useTranslation()
  const { data: user } = useCurrentUser()
  const navigate = useNavigate()
  const apartments = useApartmentList()
  const [values, setValues] = useState<ManualBookingFormValues>(emptyManualBooking)
  const [errors, setErrors] = useState<ManualBookingFormErrors>({})
  const create = useCreateBooking()
  const preview = useQuotePreview(Number(values.apartmentId))
  const serverErrors = create.error instanceof ApiError ? create.error.fieldErrors : {}
  const set = <K extends keyof ManualBookingFormValues>(key: K, value: ManualBookingFormValues[K]) => {
    setValues((current) => ({ ...current, [key]: value }))
    // Le prix affiché ne vaut que pour le logement, les dates et les voyageurs calculés.
    if (QUOTE_FIELDS.includes(key)) preview.reset()
  }
  const error = (key: keyof ManualBookingFormValues, server?: string) =>
    (errors[key] ? t(`admin.manual.errors.${errors[key]}`) : undefined) ?? serverErrors[server ?? key]?.[0]
  const [today] = useState(() => toIso(new Date()))
  const canPreview = values.apartmentId !== '' && values.arrival !== '' && values.departure !== ''

  const submit = (event: FormEvent) => {
    event.preventDefault()
    const result = manualBookingInput(values)
    setErrors(result.errors ?? {})
    if (result.input) create.mutate(result.input, { onSuccess: (booking) => void navigate(`/admin/reservations/${booking.reference}`) })
  }

  // Règle métier (dates prises, trop de voyageurs) : message de l'API ; erreurs de champ affichées sous les champs.
  const businessError = !create.isError
    ? null
    : create.error instanceof ApiError
      ? create.error.status === 422 && !create.error.code
        ? null
        : (create.error.serverMessage ?? t('common.error'))
      : t('common.error')

  return (
    <>
      <Link to="/admin/reservations" className="mb-3 inline-flex min-h-11 items-center gap-2 text-sm font-semibold no-underline">
        <ArrowLeftIcon width={18} height={18} />
        {t('admin.bookings.title')}
      </Link>
      <PageHeader title={t('admin.manual.title')} subtitle={t('admin.manual.subtitle')} />
      <form onSubmit={submit} className="space-y-5" noValidate>
        <Section title={t('admin.manual.stay')}>
          <div className="grid gap-4 sm:grid-cols-2">
            <SelectField
              label={t('admin.bookings.apartment')}
              value={values.apartmentId}
              error={error('apartmentId', 'apartment_id')}
              onChange={(value) => set('apartmentId', value)}
              options={[{ value: '', label: t('admin.manual.chooseApartment') }, ...(apartments.data ?? []).map((apartment) => ({ value: String(apartment.id), label: apartment.name }))]}
            />
            <div className="grid grid-cols-2 gap-4">
              <TextField label={t('admin.manual.adults')} inputMode="numeric" value={values.adults} error={error('adults')} onChange={(event) => set('adults', event.target.value)} />
              <TextField label={t('admin.manual.children')} inputMode="numeric" value={values.children} error={error('children')} onChange={(event) => set('children', event.target.value)} />
            </div>
            <DateField label={t('admin.manual.arrival')} value={values.arrival} min={today} onChange={(value) => set('arrival', value)} error={error('arrival')} />
            <DateField label={t('admin.manual.departure')} value={values.departure} min={values.arrival || today} onChange={(value) => set('departure', value)} error={error('departure')} />
          </div>
          <div className="mt-4">
            <Button
              variant="secondary"
              disabled={!canPreview}
              loading={preview.isPending}
              onClick={() => preview.mutate({ arrival: values.arrival, departure: values.departure, adults: Number(values.adults) || 1, children: Number(values.children) || 0 })}
            >
              {t('admin.manual.computePrice')}
            </Button>
          </div>
          {preview.data && (
            <div className="mt-4 space-y-3">
              {preview.data.quote ? <QuoteBreakdown quote={preview.data.quote} /> : <Alert tone="warning">{t('admin.manual.noRate')}</Alert>}
              {preview.data.warnings.length > 0 && (
                <p className="text-sm text-muted">{t('admin.manual.warnings', { list: preview.data.warnings.map((warning) => warning.message).join(' · ') })}</p>
              )}
            </div>
          )}
        </Section>

        <Section title={t('admin.manual.price')} description={t('admin.manual.priceHint')}>
          <div className="grid gap-4 sm:grid-cols-2">
            {/* Prix libre : administrateur seulement (le gestionnaire réserve au prix calculé). */}
            {user?.can_manage_pricing && (
              <TextField label={t('admin.manual.total')} inputMode="decimal" suffix="€" value={values.total} error={error('total', 'total_amount')} onChange={(event) => set('total', event.target.value)} />
            )}
          </div>
          <Checkbox className="mt-2" label={t('admin.manual.paid')} checked={values.paid} onChange={(event) => set('paid', event.target.checked)} />
        </Section>

        <Section title={t('admin.bookings.guest')}>
          <div className="grid gap-4 sm:grid-cols-2">
            <TextField label={t('admin.bookings.guestName')} value={values.guestName} error={error('guestName', 'guest_name')} onChange={(event) => set('guestName', event.target.value)} />
            <TextField label={t('admin.bookings.guestEmail')} hint={t('admin.manual.emailHint')} type="email" value={values.guestEmail} error={error('guestEmail', 'guest_email')} onChange={(event) => set('guestEmail', event.target.value)} />
            <TextField label={t('admin.bookings.guestPhone')} value={values.guestPhone} error={error('guestPhone', 'guest_phone')} onChange={(event) => set('guestPhone', event.target.value)} />
            <SelectField
              label={t('admin.manual.locale')}
              value={values.locale}
              onChange={(value) => set('locale', value as 'fr' | 'en')}
              options={[
                { value: 'fr', label: 'Français' },
                { value: 'en', label: 'English' },
              ]}
            />
          </div>
          <TextArea
            className="mt-4"
            label={t('admin.bookings.housekeepingNotes')}
            hint={t('admin.bookings.housekeepingNotesHint')}
            rows={3}
            maxLength={500}
            value={values.housekeepingNotes}
            onChange={(event) => set('housekeepingNotes', event.target.value)}
          />
          <Checkbox
            className="mt-2"
            label={t('admin.manual.sendConfirmation')}
            checked={values.sendConfirmation && values.guestEmail.trim() !== ''}
            disabled={values.guestEmail.trim() === ''}
            onChange={(event) => set('sendConfirmation', event.target.checked)}
          />
        </Section>

        {businessError && <Alert tone="danger">{businessError}</Alert>}
        <Button type="submit" loading={create.isPending}>
          {t('admin.manual.submit')}
        </Button>
      </form>
    </>
  )
}
