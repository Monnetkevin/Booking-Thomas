import type { ReactNode } from 'react'
import { Card } from '@/components/ui/Card'

/** Indicateur clé : libellé, chiffre et contexte, en couleurs d'encre (la couleur ne porte pas la donnée). */
export function StatTile({ label, value, detail }: { label: string; value: ReactNode; detail?: ReactNode }) {
  return (
    <Card className="p-5">
      <p className="text-sm font-semibold text-muted">{label}</p>
      <p className="mt-1 text-[32px] leading-tight font-semibold tabular-nums">{value}</p>
      {detail && <p className="mt-1 text-sm text-muted">{detail}</p>}
    </Card>
  )
}
