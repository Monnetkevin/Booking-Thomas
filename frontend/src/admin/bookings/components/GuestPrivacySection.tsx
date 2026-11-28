import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { DownloadIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Section } from '@/components/ui/Section'
import { ApiError } from '@/lib/api'
import { formatDateTime } from '@/lib/dates'
import type { BookingDetail } from '../types'
import { useAnonymizeGuest, useDownloadGuestData } from '../useAdminBookings'

/**
 * Droits RGPD du voyageur : export de ses données (toutes ses réservations et ses messages,
 * même e-mail) et anonymisation, refusée par le serveur tant qu'un séjour n'est pas terminé.
 */
export function GuestPrivacySection({ booking }: { booking: BookingDetail }) {
  const { t } = useTranslation()
  const exportData = useDownloadGuestData(booking.reference)
  const anonymize = useAnonymizeGuest(booking.reference)
  const [confirming, setConfirming] = useState(false)

  if (booking.anonymized_at) {
    return (
      <Section title={t('admin.bookings.privacy.title')} className="print:hidden">
        <p className="text-sm text-muted">{t('admin.bookings.privacy.anonymizedOn', { date: formatDateTime(booking.anonymized_at) })}</p>
      </Section>
    )
  }

  const error = [exportData, anonymize].find((mutation) => mutation.isError)?.error

  return (
    <Section title={t('admin.bookings.privacy.title')} className="print:hidden">
      <p className="mb-3 text-sm text-muted">{t('admin.bookings.privacy.intro')}</p>
      {error && (
        <div className="mb-3">
          <Alert tone="danger">{(error instanceof ApiError && error.serverMessage) || t('common.error')}</Alert>
        </div>
      )}
      <div className="flex flex-col gap-2.5 [&>*]:w-full [&>*]:justify-center">
        <Button variant="secondary" loading={exportData.isPending} onClick={() => exportData.mutate()}>
          <DownloadIcon />
          {t('admin.bookings.privacy.export')}
        </Button>
        <Button variant="danger" onClick={() => setConfirming(true)}>
          {t('admin.bookings.privacy.anonymize')}
        </Button>
      </div>
      <ConfirmDialog
        open={confirming}
        title={t('admin.bookings.privacy.confirmTitle')}
        confirmLabel={t('admin.bookings.privacy.anonymize')}
        cancelLabel={t('admin.common.cancel')}
        tone="danger"
        pending={anonymize.isPending}
        onConfirm={() => anonymize.mutate(undefined, { onSettled: () => setConfirming(false) })}
        onCancel={() => setConfirming(false)}
      >
        <p>{t('admin.bookings.privacy.confirmText')}</p>
      </ConfirmDialog>
    </Section>
  )
}
