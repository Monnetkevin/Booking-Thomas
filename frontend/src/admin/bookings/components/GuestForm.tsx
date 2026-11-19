import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { TextArea, TextField } from '@/components/ui/Field'
import { ApiError } from '@/lib/api'
import type { BookingDetail } from '../types'
import { useUpdateBooking } from '../useAdminBookings'

/** Coordonnées du voyageur et notes de ménage, corrigées par l'hôte. */
export function GuestForm({ booking }: { booking: BookingDetail }) {
  const { t } = useTranslation()
  const [values, setValues] = useState({
    guest_name: booking.guest_name,
    guest_email: booking.guest_email,
    guest_phone: booking.guest_phone ?? '',
    housekeeping_notes: booking.housekeeping_notes ?? '',
  })
  const update = useUpdateBooking(booking.reference)
  const errors = update.error instanceof ApiError ? update.error.fieldErrors : {}
  const set = (field: keyof typeof values, value: string) => setValues((current) => ({ ...current, [field]: value }))

  const submit = (event: FormEvent) => {
    event.preventDefault()
    update.mutate({ ...values, guest_phone: values.guest_phone || null, housekeeping_notes: values.housekeeping_notes || null })
  }

  return (
    <form onSubmit={submit} className="space-y-4" noValidate>
      <div className="grid gap-4 sm:grid-cols-2">
        <TextField label={t('admin.bookings.guestName')} value={values.guest_name} error={errors.guest_name?.[0]} onChange={(event) => set('guest_name', event.target.value)} />
        <TextField label={t('admin.bookings.guestEmail')} type="email" value={values.guest_email} error={errors.guest_email?.[0]} onChange={(event) => set('guest_email', event.target.value)} />
        <TextField label={t('admin.bookings.guestPhone')} value={values.guest_phone} error={errors.guest_phone?.[0]} onChange={(event) => set('guest_phone', event.target.value)} />
      </div>
      <TextArea
        label={t('admin.bookings.housekeepingNotes')}
        hint={t('admin.bookings.housekeepingNotesHint')}
        rows={3}
        maxLength={500}
        value={values.housekeeping_notes}
        error={errors.housekeeping_notes?.[0]}
        onChange={(event) => set('housekeeping_notes', event.target.value)}
      />
      {update.isSuccess && <Alert tone="success">{t('admin.common.saved')}</Alert>}
      {update.isError && !(update.error instanceof ApiError && update.error.status === 422) && <Alert tone="danger">{t('common.error')}</Alert>}
      <Button type="submit" loading={update.isPending}>
        {t('admin.common.save')}
      </Button>
    </form>
  )
}
