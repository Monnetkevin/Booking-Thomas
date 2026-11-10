import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Section } from '@/components/ui/Section'
import { ApiError } from '@/lib/api'
import type { Apartment } from '../types'
import { useDeleteApartment } from '../useApartments'

/** Suppression du logement, après confirmation ; refusée par l'API s'il reste des séjours. */
export function DangerZone({ apartment }: { apartment: Apartment }) {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const remove = useDeleteApartment(apartment.id)
  const [confirming, setConfirming] = useState(false)

  const error = remove.error instanceof ApiError ? remove.error.serverMessage : remove.error ? t('common.error') : undefined

  return (
    <Section title={t('admin.apartments.danger.title')} className="border-danger/40">
      <div className="flex flex-col gap-3">
        <p className="text-sm text-muted">{t('admin.apartments.danger.text')}</p>
        {error && <Alert tone="danger">{error}</Alert>}
        <Button variant="danger" onClick={() => setConfirming(true)}>
          {t('admin.apartments.danger.delete')}
        </Button>
      </div>
      <ConfirmDialog
        open={confirming}
        title={t('admin.apartments.danger.confirmTitle', { name: apartment.name.fr })}
        confirmLabel={t('admin.common.delete')}
        cancelLabel={t('admin.common.cancel')}
        tone="danger"
        pending={remove.isPending}
        onCancel={() => setConfirming(false)}
        onConfirm={() =>
          remove.mutate(undefined, {
            onSuccess: () => navigate('/admin/appartements', { replace: true }),
            onSettled: () => setConfirming(false),
          })
        }
      >
        {t('admin.apartments.danger.confirm')}
      </ConfirmDialog>
    </Section>
  )
}
