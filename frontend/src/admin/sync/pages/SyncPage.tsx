import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { useApartmentList } from '@/admin/apartments/useApartments'
import { PageHeader } from '@/admin/components/PageHeader'
import { relativeTime } from '@/admin/notifications/notificationFormat'
import { ErrorState } from '@/components/feedback/ErrorState'
import { CopyIcon, SyncIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Section } from '@/components/ui/Section'
import { Spinner } from '@/components/ui/Spinner'
import { ApiError } from '@/lib/api'
import { formatDateTime, formatTime } from '@/lib/dates'
import { ApartmentFeedCard } from '../components/ApartmentFeedCard'
import { feedHealth, healthTone } from '../syncStatus'
import type { IcalFeed } from '../types'
import { useIcalFeeds, useLatestSyncLogs, useSyncAll, useSyncNow } from '../useSync'

/** « il y a 6 min », « hier », ou la date. */
function useWhen() {
  const { t } = useTranslation()
  return (iso: string | null) => {
    if (!iso) return t('admin.sync.overview.never')
    const when = relativeTime(iso)
    return when.key === 'date' ? formatDateTime(iso) : t(`admin.notifications.when.${when.key}`, 'count' in when ? { count: when.count } : {})
  }
}

/** Lien d'export du site, abrégé, avec un bouton pour le copier. */
function ExportLink({ url }: { url: string }) {
  const { t } = useTranslation()
  const [copied, setCopied] = useState(false)
  const short = `…${url.slice(url.lastIndexOf('/'))}`

  return (
    <span className="flex items-center gap-1.5">
      <code className="max-w-40 truncate text-xs text-muted" title={url}>
        {short}
      </code>
      <button
        type="button"
        onClick={() => void navigator.clipboard?.writeText(url).then(() => setCopied(true))}
        aria-label={copied ? t('admin.sync.overview.copied') : t('admin.sync.overview.copy')}
        title={copied ? t('admin.sync.overview.copied') : t('admin.sync.overview.copy')}
        className="flex size-9 items-center justify-center rounded-lg text-muted hover:bg-sand hover:text-ink"
      >
        <CopyIcon width={16} height={16} />
      </button>
    </span>
  )
}

/**
 * Synchronisation iCal : alertes des calendriers en échec, tableau des calendriers (lien
 * d'export, dernière lecture, état, lecture immédiate), « Tout synchroniser », journal récent,
 * puis le réglage de chaque logement.
 */
export function SyncPage() {
  const { t } = useTranslation()
  const when = useWhen()
  const apartments = useApartmentList()
  const feeds = useIcalFeeds()
  const logs = useLatestSyncLogs()
  const syncNow = useSyncNow()
  const syncAll = useSyncAll()

  const retry = () => void Promise.all([apartments.refetch(), feeds.refetch()])
  const nameOf = (feed: IcalFeed) => apartments.data?.find((apartment) => apartment.id === feed.apartment_id)?.name ?? ''
  const failing = (feeds.data ?? []).filter((feed) => feedHealth(feed) === 'failing')

  return (
    <>
      <PageHeader
        title={t('admin.sync.title')}
        subtitle={t('admin.sync.subtitle')}
        actions={
          <Button loading={syncAll.isPending} onClick={() => syncAll.mutate()}>
            <SyncIcon width={18} height={18} />
            {t('admin.sync.overview.syncAll')}
          </Button>
        }
      />
      {syncAll.isSuccess && (
        <div className="mb-4">
          <Alert tone="success">{t('admin.sync.overview.queued', { count: syncAll.data })}</Alert>
        </div>
      )}
      {syncAll.isError && (
        <div className="mb-4">
          <Alert tone="danger">
            {syncAll.error instanceof ApiError && syncAll.error.status === 429 ? t('admin.sync.overview.tooSoon') : t('common.error')}
          </Alert>
        </div>
      )}

      {apartments.isPending || feeds.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : apartments.isError || feeds.isError ? (
        <ErrorState onRetry={retry} />
      ) : apartments.data.length === 0 ? (
        <Alert tone="info">
          {t('admin.sync.noApartment')} <Link to="/admin/appartements/nouveau">{t('admin.apartments.add')}</Link>
        </Alert>
      ) : (
        <div className="space-y-5">
          {failing.map((feed) => (
            <Alert key={feed.id} tone="danger">
              <span className="font-bold">
                {t('admin.sync.overview.failingTitle', { apartment: nameOf(feed), provider: feed.provider_label, since: when(feed.last_succeeded_at).toLocaleLowerCase('fr-FR') })}
              </span>{' '}
              {t('admin.sync.overview.failingHint', { provider: feed.provider_label })}{' '}
              <a href={`#calendrier-${feed.apartment_id}`} className="font-bold">
                {t('admin.sync.overview.fixLink')}
              </a>
            </Alert>
          ))}

          <Section title={t('admin.sync.overview.title')}>
            <div className="relative overflow-x-auto">
              <table className="w-full min-w-[720px] text-left text-sm">
                <thead>
                  <tr className="border-b border-border text-xs tracking-wide text-muted uppercase">
                    {(['apartment', 'channel', 'export', 'lastSync', 'state'] as const).map((column) => (
                      <th key={column} scope="col" className="px-3 py-2.5 font-semibold">
                        {t(`admin.sync.overview.columns.${column}`)}
                      </th>
                    ))}
                    <th scope="col" className="px-3 py-2.5">
                      <span className="sr-only">{t('admin.sync.overview.columns.actions')}</span>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {apartments.data.map((apartment) => {
                    const feed = feeds.data.find((candidate) => candidate.apartment_id === apartment.id)
                    const health = feedHealth(feed)
                    return (
                      <tr key={apartment.id} className="border-b border-line last:border-0">
                        <th scope="row" className="px-3 py-2.5 font-semibold">
                          <a href={`#calendrier-${apartment.id}`} className="text-ink">
                            {apartment.name}
                          </a>
                        </th>
                        <td className="px-3 py-2.5">{feed?.provider_label ?? '—'}</td>
                        <td className="px-3 py-2.5">{feed ? <ExportLink url={feed.export_url} /> : '—'}</td>
                        <td className="px-3 py-2.5 whitespace-nowrap">{feed?.import_url ? when(feed.last_synced_at) : '—'}</td>
                        <td className="px-3 py-2.5">
                          <Badge tone={healthTone[health]}>{t(`admin.sync.health.${health}`)}</Badge>
                        </td>
                        <td className="px-3 py-2.5 text-right">
                          {feed?.import_url && feed.is_active && (
                            <Button
                              variant="ghost"
                              loading={syncNow.isPending && syncNow.variables?.feedId === feed.id}
                              onClick={() => syncNow.mutate({ feedId: feed.id })}
                              aria-label={t('admin.sync.overview.syncOne', { apartment: apartment.name })}
                            >
                              {t('admin.sync.overview.sync')}
                            </Button>
                          )}
                        </td>
                      </tr>
                    )
                  })}
                </tbody>
              </table>
            </div>
            {syncNow.isError && (
              <div className="mt-3">
                <Alert tone="danger">{(syncNow.error instanceof ApiError && syncNow.error.serverMessage) || t('common.error')}</Alert>
              </div>
            )}
          </Section>

          <Section title={t('admin.sync.overview.logTitle')}>
            {logs.isPending ? (
              <Spinner label={t('common.loading')} />
            ) : logs.isError ? (
              <ErrorState onRetry={() => void logs.refetch()} />
            ) : logs.data.length === 0 ? (
              <p className="text-sm text-muted">{t('admin.sync.overview.noLog')}</p>
            ) : (
              <ul className="divide-y divide-line text-sm">
                {logs.data.map((log) => (
                  <li key={log.id} className="flex flex-wrap items-center gap-x-3 gap-y-1 py-2.5">
                    <time dateTime={log.synced_at} title={formatDateTime(log.synced_at)} className="w-12 shrink-0 text-muted tabular-nums">
                      {formatTime(log.synced_at)}
                    </time>
                    <span className="font-semibold">{log.apartment}</span>
                    <span className="min-w-0 flex-1 text-muted">
                      {log.status === 'failed'
                        ? t('admin.sync.overview.logFailed', { error: log.error ?? '' })
                        : t('admin.sync.overview.logOk', { created: log.created, updated: log.updated, removed: log.removed })}
                    </span>
                    <Badge tone={log.status === 'failed' ? 'danger' : 'success'}>{t(`admin.sync.overview.logStatus.${log.status}`)}</Badge>
                  </li>
                ))}
              </ul>
            )}
          </Section>

          <Section title={t('admin.sync.howTitle')}>
            <ol className="list-decimal space-y-2 pl-5 text-[15px]">
              <li>{t('admin.sync.howImport')}</li>
              <li>{t('admin.sync.howExport')}</li>
            </ol>
          </Section>
          {apartments.data.map((apartment) => (
            <ApartmentFeedCard key={apartment.id} apartment={apartment} feed={feeds.data.find((feed) => feed.apartment_id === apartment.id)} />
          ))}
        </div>
      )}
    </>
  )
}
