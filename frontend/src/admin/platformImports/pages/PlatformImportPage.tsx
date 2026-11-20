import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { useApartmentList } from '@/admin/apartments/useApartments'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { UploadIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { SelectField } from '@/components/ui/Field'
import { Section } from '@/components/ui/Section'
import { SegmentedControl } from '@/components/ui/SegmentedControl'
import { Spinner } from '@/components/ui/Spinner'
import { stayLabel } from '@/admin/bookings/stayLabel'
import { ApiError } from '@/lib/api'
import { formatDateTime } from '@/lib/dates'
import { formatEuros } from '@/lib/money'
import type { PayoutPlatform } from '../types'
import { useImportHistory, usePreviewImport, useRunImport } from '../usePlatformImports'

const PLATFORMS: PayoutPlatform[] = ['airbnb', 'booking']

/**
 * Revenus des plateformes : l'hôte dépose l'export CSV d'Airbnb ou de Booking.com, vérifie
 * l'aperçu, rattache chaque annonce à un logement (retenu pour la fois suivante), importe.
 */
export function PlatformImportPage() {
  const { t } = useTranslation()
  const [platform, setPlatform] = useState<PayoutPlatform>('airbnb')
  const [file, setFile] = useState<File | null>(null)
  // Choix de l'hôte pour chaque annonce du fichier (null : non rattachée), envoyé en entier.
  const [mapping, setMapping] = useState<Record<string, number | null>>({})
  const apartments = useApartmentList()
  const history = useImportHistory()
  const preview = usePreviewImport()
  const run = useRunImport()

  const reset = () => {
    preview.reset()
    run.reset()
    setMapping({})
  }

  const submitPreview = (event: FormEvent) => {
    event.preventDefault()
    if (!file) return
    run.reset()
    preview.mutate(
      { platform, file },
      { onSuccess: (data) => setMapping(Object.fromEntries(data.listings.map((entry) => [entry.listing, entry.apartment_id]))) },
    )
  }

  const data = preview.data
  const linked = (listing: string) => mapping[listing] !== null && mapping[listing] !== undefined
  const unmapped = data ? data.listings.filter((entry) => !linked(entry.listing)).length : 0
  const importable = data ? data.rows.filter((row) => linked(row.listing)) : []
  // Versements déjà importés d'une annonce non rattachée : le serveur les retire à l'import.
  const detachedRemoved = data ? data.rows.filter((row) => !linked(row.listing) && row.already_imported).length : 0
  const errorMessage = (error: unknown) => (error instanceof ApiError && error.serverMessage) || t('common.error')

  return (
    <>
      <PageHeader title={t('admin.platformImport.title')} subtitle={t('admin.platformImport.subtitle')} />

      <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)] [&>*]:min-w-0">
        <div className="space-y-5">
          <Section title={t('admin.platformImport.fileTitle')} description={t(`admin.platformImport.where.${platform}`)}>
            <form onSubmit={submitPreview} className="space-y-4">
              <SegmentedControl
                label={t('admin.platformImport.platform')}
                value={platform}
                onChange={(value) => {
                  setPlatform(value)
                  reset()
                }}
                options={PLATFORMS.map((value) => ({ value, label: t(`admin.platformImport.platforms.${value}`) }))}
              />
              <label className="flex flex-col gap-1.5">
                <span className="text-sm font-semibold">{t('admin.platformImport.file')}</span>
                <input
                  type="file"
                  accept=".csv,text/csv"
                  onChange={(event) => {
                    setFile(event.target.files?.[0] ?? null)
                    reset()
                  }}
                  className="text-sm file:mr-3 file:min-h-11 file:rounded-xl file:border file:border-field file:bg-white file:px-4 file:font-semibold"
                />
              </label>
              <Button type="submit" disabled={!file} loading={preview.isPending}>
                <UploadIcon />
                {t('admin.platformImport.preview')}
              </Button>
              {preview.isError && <Alert tone="danger">{errorMessage(preview.error)}</Alert>}
            </form>
          </Section>

          {data && !run.isSuccess && (
            <>
              {data.listings.length > 0 && (
                <Section title={t('admin.platformImport.listingsTitle')} description={t('admin.platformImport.listingsHint')}>
                  <div className="grid gap-3 sm:grid-cols-2">
                    {data.listings.map((entry) => (
                      <SelectField
                        key={entry.listing}
                        label={entry.listing}
                        value={mapping[entry.listing] ? String(mapping[entry.listing]) : ''}
                        onChange={(value) => setMapping((current) => ({ ...current, [entry.listing]: value ? Number(value) : null }))}
                        options={[
                          { value: '', label: t('admin.platformImport.notLinked') },
                          ...(apartments.data ?? []).map((apartment) => ({ value: String(apartment.id), label: apartment.name })),
                        ]}
                      />
                    ))}
                  </div>
                </Section>
              )}

              <Section
                title={t('admin.platformImport.previewTitle')}
                description={t('admin.platformImport.previewSummary', { count: data.rows.length, skipped: data.skipped.length })}
              >
                {data.rows.length === 0 ? (
                  <p className="text-sm text-muted">{t('admin.platformImport.noRow')}</p>
                ) : (
                  <div className="relative overflow-x-auto">
                    <table className="w-full min-w-[640px] text-left text-sm">
                      <thead>
                        <tr className="border-b border-border text-xs tracking-wide text-muted uppercase">
                          <th scope="col" className="px-3 py-2 font-semibold">{t('admin.platformImport.columns.code')}</th>
                          <th scope="col" className="px-3 py-2 font-semibold">{t('admin.platformImport.columns.listing')}</th>
                          <th scope="col" className="px-3 py-2 font-semibold">{t('admin.platformImport.columns.stay')}</th>
                          <th scope="col" className="px-3 py-2 text-right font-semibold">{t('admin.platformImport.columns.fees')}</th>
                          <th scope="col" className="px-3 py-2 text-right font-semibold">{t('admin.platformImport.columns.net')}</th>
                          <th scope="col" className="px-3 py-2 font-semibold"><span className="sr-only">{t('admin.platformImport.columns.state')}</span></th>
                        </tr>
                      </thead>
                      <tbody>
                        {data.rows.map((row) => (
                          <tr key={row.entry_key} className="border-b border-line last:border-0">
                            <td className="px-3 py-2 font-semibold">{row.confirmation_code}</td>
                            <td className="px-3 py-2">{row.listing}</td>
                            <td className="px-3 py-2 whitespace-nowrap">{stayLabel(row.arrival, row.departure)}</td>
                            <td className="px-3 py-2 text-right tabular-nums">{row.fees === null ? '—' : formatEuros(row.fees)}</td>
                            <td className="px-3 py-2 text-right font-semibold tabular-nums">{formatEuros(row.net_amount)}</td>
                            <td className="px-3 py-2">
                              {!linked(row.listing) && row.already_imported ? (
                                <Badge tone="danger">{t('admin.platformImport.states.removed')}</Badge>
                              ) : !linked(row.listing) ? (
                                <Badge tone="warning">{t('admin.platformImport.states.unlinked')}</Badge>
                              ) : row.already_imported ? (
                                <Badge tone="neutral">{t('admin.platformImport.states.update')}</Badge>
                              ) : (
                                <Badge tone="success">{t('admin.platformImport.states.new')}</Badge>
                              )}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
                {data.cancelled > 0 && <p className="mt-3 text-sm text-muted">{t('admin.platformImport.cancelled', { count: data.cancelled })}</p>}
                {data.skipped.length > 0 && (
                  <p className="mt-3 text-sm text-muted">{t('admin.platformImport.skippedLines', { lines: data.skipped.map((entry) => entry.line).join(', ') })}</p>
                )}
                {unmapped > 0 && <p className="mt-3 text-sm text-warning">{t('admin.platformImport.unmapped', { count: unmapped })}</p>}
                {detachedRemoved > 0 && (
                  <div className="mt-3"><Alert tone="warning">{t('admin.platformImport.detachedRemoved', { count: detachedRemoved })}</Alert></div>
                )}
                <div className="mt-4 flex flex-wrap items-center gap-3">
                  <Button
                    disabled={!file || importable.length === 0}
                    loading={run.isPending}
                    onClick={() => file && run.mutate({ platform, file, mapping })}
                  >
                    {t('admin.platformImport.import', { count: importable.length })}
                  </Button>
                  {run.isError && <Alert tone="danger">{errorMessage(run.error)}</Alert>}
                </div>
              </Section>
            </>
          )}

          {run.isSuccess && (
            <Alert tone="success">
              {t('admin.platformImport.done', { created: run.data.rows_created, updated: run.data.rows_updated, skipped: run.data.rows_skipped, removed: run.data.rows_removed })}
            </Alert>
          )}
        </div>

        <Section title={t('admin.platformImport.historyTitle')}>
          {history.isPending ? (
            <Spinner label={t('common.loading')} />
          ) : history.isError ? (
            <ErrorState onRetry={() => void history.refetch()} />
          ) : history.data.length === 0 ? (
            <p className="text-sm text-muted">{t('admin.platformImport.noHistory')}</p>
          ) : (
            <ul className="divide-y divide-line text-sm">
              {history.data.map((entry) => (
                <li key={entry.id} className="py-2.5">
                  <p className="font-semibold">
                    {t(`admin.platformImport.platforms.${entry.platform}`)} · <span className="font-normal break-all">{entry.filename}</span>
                  </p>
                  <p className="text-xs text-muted">
                    {formatDateTime(entry.created_at)}
                    {entry.by && ` · ${entry.by}`} · {t('admin.platformImport.counts', { created: entry.rows_created, updated: entry.rows_updated, skipped: entry.rows_skipped, removed: entry.rows_removed })}
                  </p>
                </li>
              ))}
            </ul>
          )}
        </Section>
      </div>
    </>
  )
}
