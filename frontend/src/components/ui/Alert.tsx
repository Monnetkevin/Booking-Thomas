import type { ReactNode } from 'react'
import { cn } from '@/lib/cn'

type Tone = 'info' | 'success' | 'warning' | 'danger'

const tones: Record<Tone, string> = {
  info: 'border-border bg-sand text-ink',
  success: 'border-success/30 bg-success-soft text-success',
  warning: 'border-warning/30 bg-warning-soft text-warning',
  danger: 'border-danger/30 bg-danger-soft text-danger',
}

/** Message annoncé aux lecteurs d'écran (role="alert" pour les erreurs). */
export function Alert({ tone = 'info', children }: { tone?: Tone; children: ReactNode }) {
  return (
    <div
      role={tone === 'danger' ? 'alert' : 'status'}
      className={cn('rounded-xl border px-4 py-3 text-[15px] font-medium', tones[tone])}
    >
      {children}
    </div>
  )
}
