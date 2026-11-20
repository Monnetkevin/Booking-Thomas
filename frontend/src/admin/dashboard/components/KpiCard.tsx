import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Card } from '@/components/ui/Card'
import { cn } from '@/lib/cn'
import type { Delta } from '../dashboardFigures'

const signed = new Intl.NumberFormat('fr-FR', { signDisplay: 'exceptZero', maximumFractionDigits: 1 })

/** Écart avec la période précédente : vert en hausse, rouge en baisse, neutre à zéro. */
function DeltaChip({ delta }: { delta: Delta }) {
  const { t } = useTranslation()
  if (!delta) return null
  const text = delta.value === 0 ? t('admin.dashboard.stable') : t(`admin.dashboard.delta.${delta.kind}`, { value: signed.format(delta.value) })
  return (
    <span
      className={cn(
        'rounded-full px-2.5 py-1 text-xs font-bold whitespace-nowrap',
        delta.value > 0 ? 'bg-success-soft text-success' : delta.value < 0 ? 'bg-danger-soft text-danger' : 'bg-cream text-muted',
      )}
    >
      {text}
      <span className="sr-only"> {t('admin.dashboard.vsPrevious')}</span>
    </span>
  )
}

interface KpiCardProps {
  icon: ReactNode
  label: string
  value: ReactNode
  detail?: ReactNode
  delta: Delta
}

/** Indicateur clé : pictogramme, écart, chiffre, libellé et contexte. */
export function KpiCard({ icon, label, value, detail, delta }: KpiCardProps) {
  return (
    <Card className="flex flex-col gap-3.5 p-5 sm:p-5">
      <div className="flex items-center justify-between gap-2">
        <span aria-hidden="true" className="flex size-10 items-center justify-center rounded-xl bg-accent-soft text-accent [&>svg]:size-5">
          {icon}
        </span>
        <DeltaChip delta={delta} />
      </div>
      <div>
        <p className="text-sm font-semibold text-muted">{label}</p>
        <p className="mt-0.5 text-[28px] leading-tight font-bold tabular-nums">{value}</p>
        {detail && <p className="mt-0.5 text-sm text-muted">{detail}</p>}
      </div>
    </Card>
  )
}
