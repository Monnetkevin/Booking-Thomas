import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { CopyField } from '@/components/ui/CopyField'
import type { IcalFeed } from '../types'
import { useRegenerateExportToken } from '../useSync'

/** URL du site à coller chez la plateforme, et son remplacement (l'ancienne cesse de marcher). */
export function ExportUrlPanel({ feed }: { feed: IcalFeed }) {
  const { t } = useTranslation()
  const [confirming, setConfirming] = useState(false)
  const regenerate = useRegenerateExportToken()

  return (
    <div className="space-y-3">
      <CopyField label={t('admin.sync.export.label')} value={feed.export_url} />
      {regenerate.isError && <Alert tone="danger">{t('common.error')}</Alert>}
      <Button variant="ghost" size="sm" onClick={() => setConfirming(true)}>
        {t('admin.sync.export.regenerate')}
      </Button>
      <ConfirmDialog
        open={confirming}
        title={t('admin.sync.export.confirmTitle')}
        confirmLabel={t('admin.sync.export.confirmAction')}
        cancelLabel={t('admin.common.cancel')}
        tone="danger"
        pending={regenerate.isPending}
        onCancel={() => setConfirming(false)}
        onConfirm={() => regenerate.mutate(feed.id, { onSettled: () => setConfirming(false) })}
      >
        {t('admin.sync.export.confirm')}
      </ConfirmDialog>
    </div>
  )
}
