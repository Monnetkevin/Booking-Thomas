import type { ReactNode } from 'react'
import { cn } from '@/lib/cn'

const tones = {
  light: 'border-line bg-white',
  /** Sur photo : verre dépoli, texte blanc. */
  glass: 'border-white/45 bg-white/15 text-white backdrop-blur-sm',
}

/** Étiquette arrondie précédée d'un point de couleur (« Réservation en direct… »). */
export function StatusPill({ children, tone = 'light' }: { children: ReactNode; tone?: keyof typeof tones }) {
  return (
    <p className={cn('flex items-center gap-2 self-start rounded-full border px-3.5 py-2 text-[13px] font-bold', tones[tone])}>
      <span aria-hidden="true" className={cn('size-2 rounded-full', tone === 'glass' ? 'bg-[#5fd28b]' : 'bg-[#2e9e5b]')} />
      {children}
    </p>
  )
}
