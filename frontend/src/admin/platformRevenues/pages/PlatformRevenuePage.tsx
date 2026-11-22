import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { useSearchParams } from 'react-router'
import { PageHeader } from '@/admin/components/PageHeader'
import { monthLabel } from '@/admin/statistics/chartScale'
import { ErrorState } from '@/components/feedback/ErrorState'
import { ChevronLeftIcon, ChevronRightIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Section } from '@/components/ui/Section'
import { Spinner } from '@/components/ui/Spinner'
import { fieldClasses } from '@/components/ui/fieldStyles'
import { ApiError } from '@/lib/api'
import { cn } from '@/lib/cn'
import { formatDateTime } from '@/lib/dates'
import { formatEuros } from '@/lib/money'
import { cellKey, currentMonth, defaultMonth, FIRST_MONTH, initialDrafts, shiftMonth, summarize, type RevenueDrafts } from '../revenueForm'
import { REVENUE_PLATFORMS, type RevenueHistoryEntry, type RevenueSheet } from '../types'
import { useRevenueHistory, useRevenueSheet, useSaveRevenues } from '../usePlatformRevenues'

const MONTH_PATTERN = /^\d{4}-(0[1-9]|1[0-2])$/

/**
 * Revenus des plateformes : chaque mois, l'admin reporte le net versé par Airbnb et
 * Booking.com pour chaque logement (rapport mensuel de la plateforme). Les nuits viennent
 * de l'iCal ; vide = pas encore saisi, 0 = aucun revenu.
 */
export function PlatformRevenuePage() {
  const { t } = useTranslation()
  const [params, setParams] = useSearchParams()
  const history = useRevenueHistory()
  // Mois en cours d'après le serveur (premier mois de l'historique), sinon d'après le navigateur.
  const latest = history.data?.[0]?.month ?? currentMonth()
  const asked = params.get('mois') ?? ''
  // Mois hors bornes ou mal écrit : le serveur choisit le mois par défaut (le précédent).
  const requested = MONTH_PATTERN.test(asked) && asked >= FIRST_MONTH && asked <= latest ? asked : null
  const sheet = useRevenueSheet(requested)
  const month = requested ?? sheet.data?.month ?? defaultMonth()
  const goTo = (next: string) => setParams({ mois: next })
  const refused = sheet.error instanceof ApiError && sheet.error.status === 422

  return (
    <>
      <PageHeader
        title={t('admin.platformRevenue.title')}
        subtitle={t('admin.platformRevenue.subtitle')}
        actions={
          <div role="group" aria-label={t('admin.platformRevenue.month')} className="flex items-center gap-1 rounded-xl border border-field bg-white p-1">
            <button
              type="button"
              onClick={() => goTo(shiftMonth(month, -1))}
              disabled={month <= FIRST_MONTH}
              aria-label={t('admin.platformRevenue.previousMonth')}
              className="flex size-10 items-center justify-center rounded-lg hover:bg-sand disabled:opacity-40 disabled:hover:bg-transparent"
            >
              <ChevronLeftIcon width={18} height={18} />
            </button>
            <strong className="min-w-36 text-center capitalize" aria-live="polite">{monthLabel(month, 'long')}</strong>
            <button
              type="button"
              onClick={() => goTo(shiftMonth(month, 1))}
              disabled={month >= latest}
              aria-label={t('admin.platformRevenue.nextMonth')}
              className="flex size-10 items-center justify-center rounded-lg hover:bg-sand disabled:opacity-40 disabled:hover:bg-transparent"
            >
              <ChevronRightIcon width={18} height={18} />
            </button>
          </div>
        }
      />

      <div className="space-y-5">
        <Alert tone="info">{t('admin.platformRevenue.where')}</Alert>

        {sheet.isPending ? (
          <Spinner label={t('common.loading')} />
        ) : refused ? (
          // Mois refusé par le serveur (horloges décalées) : retour au mois par défaut.
          <Alert tone="warning">
            {t('admin.platformRevenue.unavailable')}{' '}
            <button type="button" onClick={() => setParams({})} className="font-semibold underline underline-offset-4">
              {t('admin.platformRevenue.backToDefault')}
            </button>
          </Alert>
        ) : sheet.isError ? (
          <ErrorState onRetry={() => void sheet.refetch()} />
        ) : (
          // Gardé par mois : un rechargement (après une saisie, en arrière-plan) ne remet pas
          // les champs à zéro et n'efface pas la confirmation.
          <RevenueForm key={sheet.data.month} sheet={sheet.data} />
        )}

        <Section title={t('admin.platformRevenue.historyTitle')} description={t('admin.platformRevenue.historyHint')}>
          {history.isPending ? (
            <Spinner label={t('common.loading')} />
          ) : history.isError ? (
            <ErrorState onRetry={() => void history.refetch()} />
          ) : (
            <HistoryTable entries={history.data} shown={month} onOpen={goTo} />
          )}
        </Section>
      </div>
    </>
  )
}

function RevenueForm({ sheet }: { sheet: RevenueSheet }) {
  const { t } = useTranslation()
  const [drafts, setDrafts] = useState<RevenueDrafts>(() => initialDrafts(sheet))
  const [showErrors, setShowErrors] = useState(false)
  const save = useSaveRevenues()
  const summary = summarize(sheet, drafts)
  const invalid = new Set(showErrors ? summary.invalid : [])
  const cell = 'px-3 py-2'
  const split = 'border-l border-border'
  // Colonne des logements figée quand le tableau défile (téléphone).
  const sticky = 'sticky left-0 z-10 min-w-32'

  const submit = (event: FormEvent) => {
    event.preventDefault()
    setShowErrors(true)
    if (summary.invalid.length > 0) return
    save.mutate({ month: sheet.month, entries: summary.entries })
  }

  return (
    <Section
      title={t('admin.platformRevenue.sheetTitle', { month: monthLabel(sheet.month, 'long') })}
      description={t('admin.platformRevenue.sheetHint')}
      actions={
        sheet.is_current ? (
          <Badge>{t('admin.platformRevenue.current')}</Badge>
        ) : summary.missing > 0 ? (
          <Badge tone="warning">{t('admin.platformRevenue.missing', { count: summary.missing })}</Badge>
        ) : (
          <Badge tone="success">{t('admin.platformRevenue.complete')}</Badge>
        )
      }
    >
      <form onSubmit={submit} noValidate>
        {sheet.apartments.length === 0 ? (
          <p className="text-sm text-muted">{t('admin.platformRevenue.noApartment')}</p>
        ) : (
          <div className="relative overflow-x-auto rounded-xl border border-border">
            <table className="w-full min-w-[46rem] text-sm">
              <thead className="bg-sand text-left text-muted">
                <tr className="text-xs tracking-wide uppercase">
                  <th scope="col" rowSpan={2} className={cn(cell, sticky, 'bg-sand font-semibold')}>{t('admin.platformRevenue.apartment')}</th>
                  {REVENUE_PLATFORMS.map((platform) => (
                    <th key={platform} scope="colgroup" colSpan={2} className={cn(cell, split, 'pb-0 font-semibold')}>
                      {t(`admin.statistics.channels.${platform}`)}
                    </th>
                  ))}
                  <th scope="col" rowSpan={2} className={cn(cell, split, 'text-right font-semibold')}>{t('admin.platformRevenue.perNight')}</th>
                </tr>
                <tr className="text-xs">
                  {REVENUE_PLATFORMS.map((platform) => (
                    <PlatformSubHeaders key={platform} cell={cell} split={split} />
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {sheet.apartments.map((apartment) => {
                  const toFill = REVENUE_PLATFORMS.some(
                    (platform) => apartment.platforms[platform].nights > 0 && (drafts[cellKey(apartment.id, platform)] ?? '').trim() === '',
                  )
                  return (
                    <tr key={apartment.id} className={toFill && !sheet.is_current ? 'bg-warning-soft' : 'bg-white'}>
                      <th scope="row" className={cn(cell, sticky, 'bg-inherit text-left font-semibold')}>{apartment.name}</th>
                      {REVENUE_PLATFORMS.map((platform) => {
                        const key = cellKey(apartment.id, platform)
                        const { nights } = apartment.platforms[platform]
                        const label = t('admin.platformRevenue.amountLabel', { platform: t(`admin.statistics.channels.${platform}`), apartment: apartment.name })
                        return [
                          <td key={`${key}-nights`} className={cn(cell, split, 'text-right tabular-nums', nights === 0 && 'text-muted')}>{nights}</td>,
                          <td key={`${key}-amount`} className={cn(cell, 'py-1.5')}>
                            <label className="relative block w-36">
                              <span className="sr-only">{label}</span>
                              <input
                                type="text"
                                inputMode="decimal"
                                autoComplete="off"
                                value={drafts[key] ?? ''}
                                placeholder={nights > 0 ? t('admin.platformRevenue.toFill') : undefined}
                                onChange={(event) => {
                                  setDrafts((current) => ({ ...current, [key]: event.target.value }))
                                  if (save.isSuccess) save.reset()
                                }}
                                aria-invalid={invalid.has(key) || undefined}
                                className={fieldClasses(invalid.has(key), 'min-h-11 pr-8 text-right tabular-nums')}
                              />
                              <span aria-hidden="true" className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-muted">€</span>
                            </label>
                          </td>,
                        ]
                      })}
                      <td className={cn(cell, split, 'text-right font-semibold tabular-nums')}>
                        {summary.perNight[apartment.id] == null ? <span className="font-normal text-muted">—</span> : formatEuros(summary.perNight[apartment.id] ?? 0)}
                      </td>
                    </tr>
                  )
                })}
              </tbody>
              <tfoot className="border-t-2 border-field bg-sand font-semibold">
                <tr>
                  <th scope="row" className={cn(cell, sticky, 'bg-sand text-left')}>{t('admin.platformRevenue.total')}</th>
                  {REVENUE_PLATFORMS.map((platform) => (
                    <TotalCells
                      key={platform}
                      cell={cell}
                      split={split}
                      nights={sheet.apartments.reduce((sum, apartment) => sum + apartment.platforms[platform].nights, 0)}
                      amount={summary.totals[platform]}
                      incomplete={sheet.apartments.some(
                        (apartment) => apartment.platforms[platform].nights > 0 && (drafts[cellKey(apartment.id, platform)] ?? '').trim() === '',
                      )}
                    />
                  ))}
                  <td className={cn(cell, split)} />
                </tr>
              </tfoot>
            </table>
          </div>
        )}

        {showErrors && summary.invalid.length > 0 && (
          <div className="mt-4"><Alert tone="danger">{t('admin.platformRevenue.invalid')}</Alert></div>
        )}
        {save.isError && (
          <div className="mt-4">
            <Alert tone="danger">{(save.error instanceof ApiError && save.error.serverMessage) || t('common.error')}</Alert>
          </div>
        )}
        <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
          <p className="text-sm text-muted" aria-live="polite">
            {save.isSuccess
              ? t('admin.platformRevenue.saved')
              : sheet.last_saved?.at
                ? t('admin.platformRevenue.lastSaved', { date: formatDateTime(sheet.last_saved.at), by: sheet.last_saved.by ?? '—' })
                : t('admin.platformRevenue.neverSaved')}
          </p>
          <Button type="submit" loading={save.isPending} disabled={sheet.apartments.length === 0}>
            {t('admin.common.save')}
          </Button>
        </div>
      </form>
    </Section>
  )
}

function PlatformSubHeaders({ cell, split }: { cell: string; split: string }) {
  const { t } = useTranslation()
  return (
    <>
      <th scope="col" className={cn(cell, split, 'pt-0 text-right font-medium')}>{t('admin.platformRevenue.nights')}</th>
      <th scope="col" className={cn(cell, 'pt-0 font-medium')}>{t('admin.platformRevenue.net')}</th>
    </>
  )
}

function TotalCells({ cell, split, nights, amount, incomplete }: { cell: string; split: string; nights: number; amount: number; incomplete: boolean }) {
  const { t } = useTranslation()
  return (
    <>
      <td className={cn(cell, split, 'text-right tabular-nums')}>{nights}</td>
      <td className={cn(cell, 'tabular-nums')}>
        {formatEuros(amount)}
        {incomplete && <span className="ml-1.5 text-xs font-medium text-warning">{t('admin.platformRevenue.incomplete')}</span>}
      </td>
    </>
  )
}

function HistoryTable({ entries, shown, onOpen }: { entries: RevenueHistoryEntry[]; shown: string; onOpen: (month: string) => void }) {
  const { t } = useTranslation()
  const cell = 'px-3 py-2'
  return (
    <div className="relative overflow-x-auto">
      <table className="w-full min-w-[34rem] text-sm">
        <thead className="text-left text-xs tracking-wide text-muted uppercase">
          <tr>
            <th scope="col" className={cn(cell, 'font-semibold')}>{t('admin.platformRevenue.month')}</th>
            {REVENUE_PLATFORMS.map((platform) => (
              <th key={platform} scope="col" className={cn(cell, 'text-right font-semibold')}>{t(`admin.statistics.channels.${platform}`)}</th>
            ))}
            <th scope="col" className={cn(cell, 'font-semibold')}>{t('admin.platformRevenue.state')}</th>
            <th scope="col" className={cell}><span className="sr-only">{t('admin.platformRevenue.action')}</span></th>
          </tr>
        </thead>
        <tbody className="divide-y divide-border">
          {entries.map((entry) => (
            <tr key={entry.month} className={cn(entry.month === shown && 'bg-accent-soft')}>
              <th scope="row" className={cn(cell, 'text-left font-semibold capitalize')}>{monthLabel(entry.month, 'long')}</th>
              {REVENUE_PLATFORMS.map((platform) => (
                <td key={platform} className={cn(cell, 'text-right tabular-nums')}>
                  {entry.entered ? formatEuros(entry.totals[platform]) : <span className="text-muted">—</span>}
                </td>
              ))}
              <td className={cell}>
                {entry.is_current ? (
                  <Badge>{t('admin.platformRevenue.current')}</Badge>
                ) : entry.missing > 0 ? (
                  <Badge tone="warning">{t('admin.platformRevenue.missing', { count: entry.missing })}</Badge>
                ) : (
                  <Badge tone="success">{t('admin.platformRevenue.complete')}</Badge>
                )}
              </td>
              <td className={cn(cell, 'text-right')}>
                {entry.month === shown ? (
                  <span className="text-xs font-semibold text-muted">{t('admin.platformRevenue.shown')}</span>
                ) : (
                  <button type="button" onClick={() => onOpen(entry.month)} className="min-h-11 px-2 font-semibold text-accent-strong underline-offset-4 hover:underline">
                    {entry.missing > 0 || !entry.entered ? t('admin.platformRevenue.fill') : t('admin.platformRevenue.edit')}
                    <span className="sr-only"> {monthLabel(entry.month, 'long')}</span>
                  </button>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
