import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate, useSearchParams } from 'react-router'
import { BlockDialog } from '@/admin/blocks/components/BlockDialog'
import { useRemoveBlock } from '@/admin/blocks/useBlocks'
import { KpiCard } from '@/admin/components/KpiCard'
import { countDelta, pointsDelta } from '@/admin/components/delta'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { CalendarIcon, GridIcon, HomeIcon, PlusIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { SegmentedControl } from '@/components/ui/SegmentedControl'
import { Spinner } from '@/components/ui/Spinner'
import { ApiError } from '@/lib/api'
import { toIso } from '@/lib/dates'
import { PlanningGrid } from '../components/PlanningGrid'
import { addDays, planningDays, type BlockKind } from '../planningLayout'
import { periodContaining, periodIncludes, shiftPeriod, type PlanningView } from '../planningPeriod'
import type { PlanningBlock } from '../types'
import { usePlanning } from '../usePlanning'

const LEGEND: BlockKind[] = ['direct', 'manualBooking', 'pending', 'ical', 'closed']
const legendClasses: Record<BlockKind, string> = {
  direct: 'bg-accent',
  manualBooking: 'bg-success',
  pending: 'border-2 border-dashed border-warning bg-warning-soft',
  ical: 'bg-night-soft',
  closed: 'bg-line',
}

const VIEW_PARAM: Record<string, PlanningView> = { mois: 'month', semaine: 'week' }
const VIEW_SLUG: Record<PlanningView, string> = { month: 'mois', week: 'semaine' }
const monthTitle = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric', timeZone: 'UTC' })
const shortDay = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short', timeZone: 'UTC' })
const percent = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 })
const asDate = (iso: string) => new Date(`${iso}T00:00:00Z`)

/**
 * Planning de tous les logements, au mois ou à la semaine (gardés dans l'adresse), avec les
 * chiffres de la période et la fermeture de dates en un clic.
 */
export function PlanningPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [params, setParams] = useSearchParams()
  const [today] = useState(() => toIso(new Date()))
  const view = VIEW_PARAM[params.get('vue') ?? ''] ?? 'month'
  const anchor = /^\d{4}-\d{2}-\d{2}$/.test(params.get('date') ?? '') ? (params.get('date') as string) : today
  const period = periodContaining(view, anchor)
  const { from, days } = period
  const previous = shiftPeriod(period, -1)
  const planning = usePlanning(from, days)
  const previousPlanning = usePlanning(previous.from, previous.days)
  const figures = planning.data?.figures ?? null
  const previousFigures = previousPlanning.data?.figures ?? null

  const go = (nextView: PlanningView, day: string) =>
    setParams(
      (current) => {
        const next = new URLSearchParams(current)
        next.set('vue', VIEW_SLUG[nextView])
        if (periodIncludes(periodContaining(nextView, today), day)) next.delete('date')
        else next.set('date', day)
        return next
      },
      { replace: true },
    )
  const title =
    view === 'month'
      ? monthTitle.format(asDate(from))
      : t('admin.planning.weekTitle', { from: shortDay.format(asDate(from)), to: shortDay.format(asDate(addDays(from, days - 1))) })
  const removeBlock = useRemoveBlock()
  const [closing, setClosing] = useState<{ apartmentId?: number; startsOn?: string; endsOn?: string } | null>(null)
  const [removing, setRemoving] = useState<PlanningBlock | null>(null)
  const apartments = planning.data?.apartments ?? []

  const openBlock = (block: PlanningBlock) => {
    if (block.booking) void navigate(`/admin/reservations/${block.booking.reference}`)
    else if (block.type === 'manual') setRemoving(block)
    // Lien venu du flux de la plateforme : ouvert seulement s'il est en https (déjà filtré à l'import).
    else if (block.external_url?.startsWith('https://')) window.open(block.external_url, '_blank', 'noopener,noreferrer')
  }

  return (
    <>
      <PageHeader
        title={t('admin.planning.title')}
        subtitle={t('admin.planning.subtitle')}
        actions={
          <>
            <SegmentedControl
              label={t('admin.planning.view')}
              value={view}
              onChange={(value) => go(value, from)}
              options={(['month', 'week'] as const).map((value) => ({ value, label: t(`admin.planning.views.${value}`) }))}
            />
            <Button variant="secondary" onClick={() => setClosing({})}>
              {t('admin.blocks.title')}
            </Button>
            <Link to="/admin/reservations/nouvelle" className={buttonClasses('primary')}>
              <PlusIcon />
              {t('admin.dashboard.newBooking')}
            </Link>
          </>
        }
      />

      <div className="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard
          icon={<HomeIcon />}
          label={t('admin.planning.figures.sold')}
          value={figures?.sold_nights ?? '—'}
          detail={figures ? t('admin.planning.figures.soldDetail', { count: figures.open_nights }) : undefined}
          delta={null}
        />
        <KpiCard
          icon={<GridIcon />}
          label={t('admin.planning.figures.occupancy')}
          value={figures?.occupancy == null ? '—' : `${percent.format(figures.occupancy)} %`}
          detail={t(`admin.planning.figures.occupancyDetail.${view}`)}
          delta={figures && previousFigures ? pointsDelta(figures.occupancy, previousFigures.occupancy) : null}
        />
        <KpiCard
          icon={<CalendarIcon />}
          label={t('admin.planning.figures.arrivals')}
          value={figures?.arrivals ?? '—'}
          detail={figures ? t('admin.planning.figures.arrivalsDetail', { count: figures.direct_arrivals }) : undefined}
          delta={figures && previousFigures ? countDelta(figures.arrivals, previousFigures.arrivals) : null}
        />
        <KpiCard
          icon={<GridIcon />}
          label={t('admin.planning.figures.free')}
          value={figures?.free_nights ?? '—'}
          detail={figures ? t('admin.planning.figures.freeDetail', { count: figures.free_weekend_nights }) : undefined}
          delta={null}
        />
      </div>

      <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
        <h2 className="text-xl font-semibold first-letter:uppercase" aria-live="polite">
          {title}
        </h2>
        <div className="flex gap-2">
          <Button variant="secondary" aria-label={t(`admin.planning.previousPeriod.${view}`)} onClick={() => go(view, previous.from)}>
            ←
          </Button>
          <Button variant="secondary" onClick={() => go(view, today)} disabled={periodIncludes(period, today)}>
            {t('admin.planning.today')}
          </Button>
          <Button variant="secondary" aria-label={t(`admin.planning.nextPeriod.${view}`)} onClick={() => go(view, shiftPeriod(period, 1).from)}>
            →
          </Button>
        </div>
      </div>

      {planning.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : planning.isError ? (
        <ErrorState onRetry={() => void planning.refetch()} />
      ) : apartments.length === 0 ? (
        <Alert tone="info">{t('admin.planning.noApartment')}</Alert>
      ) : (
        <PlanningGrid
          days={planningDays(from, days)}
          dense={view === 'month'}
          today={today}
          apartments={apartments}
          onBlock={openBlock}
          onEmptyDay={(apartment, day) => setClosing({ apartmentId: apartment.id, startsOn: day < today ? today : day, endsOn: addDays(day < today ? today : day, 1) })}
        />
      )}

      <ul className="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm" aria-label={t('admin.planning.legend')}>
        {LEGEND.map((kind) => (
          <li key={kind} className="flex items-center gap-2">
            <span aria-hidden="true" className={`inline-block h-3.5 w-6 rounded ${legendClasses[kind]}`} />
            {t(`admin.planning.kinds.${kind}`)}
          </li>
        ))}
        <li className="flex items-center gap-2">
          <span aria-hidden="true" className="inline-block h-3.5 w-6 rounded bg-white ring-2 ring-danger" />
          {t('admin.planning.conflict')}
        </li>
      </ul>

      {closing && (
        <BlockDialog
          open
          onClose={() => setClosing(null)}
          apartments={apartments.map((apartment) => ({ id: apartment.id, name: apartment.name }))}
          initial={closing}
          today={today}
        />
      )}
      <ConfirmDialog
        open={removing !== null}
        title={t('admin.blocks.removeTitle')}
        confirmLabel={t('admin.blocks.remove')}
        cancelLabel={t('admin.common.cancel')}
        tone="danger"
        pending={removeBlock.isPending}
        onCancel={() => {
          setRemoving(null)
          removeBlock.reset()
        }}
        onConfirm={() => removing && removeBlock.mutate(removing.id, { onSuccess: () => setRemoving(null) })}
      >
        {removing && `${removing.label ?? t('admin.planning.kinds.closed')} · ${removing.starts_on} → ${removing.ends_on}. `}
        {t('admin.blocks.removeConfirm')}
        {removeBlock.isError && (
          <span role="alert" className="mt-3 block font-semibold text-danger">
            {(removeBlock.error instanceof ApiError && removeBlock.error.serverMessage) || t('common.error')}
          </span>
        )}
      </ConfirmDialog>
    </>
  )
}
