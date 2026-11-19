import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Dialog } from '@/components/ui/Dialog'
import { Checkbox } from '@/components/ui/Field'
import { ApiError } from '@/lib/api'
import { formatEuros } from '@/lib/money'
import type { BookingDetail } from '../types'
import { useCancelBooking } from '../useAdminBookings'

/** Annulation par l'hôte : remboursement proposé selon le barème, voyageur prévenu ou non. */
export function CancelBookingDialog({ booking, open, onClose }: { booking: BookingDetail; open: boolean; onClose: () => void }) {
  const { t } = useTranslation()
  const [notifyGuest, setNotifyGuest] = useState(true)
  const cancel = useCancelBooking(booking.reference)
  const proposal = booking.refund_proposal

  return (
    <Dialog
      open={open}
      onClose={onClose}
      title={t('admin.bookings.cancelTitle', { reference: booking.reference })}
      footer={
        <>
          <Button variant="secondary" onClick={onClose} disabled={cancel.isPending}>
            {t('admin.common.cancel')}
          </Button>
          <Button variant="danger" loading={cancel.isPending} onClick={() => cancel.mutate(notifyGuest, { onSuccess: onClose })}>
            {t('admin.bookings.cancelConfirm')}
          </Button>
        </>
      }
    >
      <div className="space-y-4 text-[15px]">
        <p>{t('admin.bookings.cancelText')}</p>
        {proposal && (
          <div className="rounded-xl bg-sand px-4 py-3">
            <p className="font-semibold">{t('admin.bookings.refundProposed', { amount: formatEuros(proposal.amount) })}</p>
            <p className="text-sm text-muted">
              {proposal.days_before_arrival >= 0
                ? t('admin.bookings.refundRule', { days: proposal.days_before_arrival, percent: proposal.percent })
                : t('admin.bookings.refundStarted')}
              {' '}
              {t('admin.bookings.refundPaid', { amount: formatEuros(proposal.paid) })}
            </p>
            <p className="mt-2 text-sm">{t('admin.bookings.refundOffline')}</p>
          </div>
        )}
        <Checkbox label={t('admin.bookings.notifyGuest')} checked={notifyGuest} onChange={(event) => setNotifyGuest(event.target.checked)} />
        {cancel.isError && <Alert tone="danger">{(cancel.error instanceof ApiError && cancel.error.serverMessage) || t('common.error')}</Alert>}
      </div>
    </Dialog>
  )
}
