import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router'
import { BlockDialog } from '@/admin/blocks/components/BlockDialog'
import { useRemoveBlock } from '@/admin/blocks/useBlocks'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { FilterChips } from '@/components/ui/FilterChips'
import { Spinner } from '@/components/ui/Spinner'
import { ApiError } from '@/lib/api'
import { toIso } from '@/lib/dates'
import { PlanningGrid } from '../components/PlanningGrid'
import { addDays, planningDays, type BlockKind } from '../planningLayout'
import type { PlanningBlock } from '../types'
import { usePlanning } from '../usePlanning'

type Span = '14' | '31'
const LEGEND: BlockKind[] = ['direct', 'manualBooking', 'pending', 'ical', 'closed']
const legendClasses: Record<BlockKind, string> = {
  direct: 'bg-accent',
  manualBooking: 'bg-success',
  pending: 'border-2 border-dashed border-warning bg-warning-soft',
  ical: 'bg-night-soft',
  closed: 'bg-line',
}

/** Planning de tous les logements, 14 ou 31 jours, avec fermeture de dates en un clic. */
export function PlanningPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [today] = useState(() => toIso(new Date()))
  const [from, setFrom] = useState(today)
  const [span, setSpan] = useState<Span>('14')
  const days = Number(span)
  const planning = usePlanning(from, days)
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
        actions={<Button onClick={() => setClosing({})}>{t('admin.blocks.title')}</Button>}
      />
      <div className="mb-4 flex flex-wrap items-center gap-3">
        <div className="flex gap-2">
          <Button variant="secondary" aria-label={t('admin.planning.previous')} onClick={() => setFrom(addDays(from, -days))}>
            ←
          </Button>
          <Button variant="secondary" onClick={() => setFrom(today)} disabled={from === today}>
            {t('admin.planning.today')}
          </Button>
          <Button variant="secondary" aria-label={t('admin.planning.next')} onClick={() => setFrom(addDays(from, days))}>
            →
          </Button>
        </div>
        <FilterChips
          label={t('admin.planning.span')}
          value={span}
          onChange={setSpan}
          options={[
            { value: '14', label: t('admin.planning.days', { count: 14 }) },
            { value: '31', label: t('admin.planning.days', { count: 31 }) },
          ]}
        />
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
