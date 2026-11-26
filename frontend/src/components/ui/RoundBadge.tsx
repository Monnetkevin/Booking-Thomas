import type { ReactNode } from 'react'
import { cn } from '@/lib/cn'

type Tone = 'soft' | 'accent' | 'dark'
type Size = 'md' | 'lg'

const tones: Record<Tone, string> = {
  soft: 'bg-blush text-accent-strong',
  accent: 'bg-accent text-white',
  dark: 'bg-ink text-white',
}

const sizes: Record<Size, string> = {
  md: 'size-10',
  lg: 'size-12',
}

/** Pastille ronde contenant une icône ou un chiffre (atouts, étapes, flèche d'une tuile). */
export function RoundBadge({
  tone = 'soft',
  size = 'md',
  className,
  children,
}: {
  tone?: Tone
  size?: Size
  className?: string
  children: ReactNode
}) {
  return (
    <span className={cn('flex shrink-0 items-center justify-center rounded-full font-bold', tones[tone], sizes[size], className)}>
      {children}
    </span>
  )
}
