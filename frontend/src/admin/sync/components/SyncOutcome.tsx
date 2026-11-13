import { useTranslation } from 'react-i18next'
import { syncChanges } from '../syncStatus'
import type { IcalSyncLog } from '../types'

/** Bilan d'une lecture en une phrase : erreur, calendrier inchangé ou changements. */
export function SyncOutcome({ log }: { log: IcalSyncLog }) {
  const { t } = useTranslation()

  if (log.status === 'failed') return t('admin.sync.result.failed', { error: log.error ?? '' })
  if (log.status === 'unchanged') return t('admin.sync.result.unchanged')

  const changes = syncChanges(log)
  return changes.length === 0
    ? t('admin.sync.result.nothing')
    : changes.map(({ change, count }) => t(`admin.sync.changes.${change}`, { count })).join(' · ')
}
