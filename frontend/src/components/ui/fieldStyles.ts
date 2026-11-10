import { cn } from '@/lib/cn'

/** Apparence commune des champs (input, select, textarea). */
export function fieldClasses(hasError: boolean, extra?: string): string {
  return cn(
    'w-full rounded-[var(--radius-field)] border bg-white px-3.5 text-[15px] text-ink placeholder:text-muted',
    'disabled:cursor-not-allowed disabled:bg-sand',
    hasError ? 'border-danger' : 'border-field',
    extra,
  )
}

/** Identifiants d'aide et d'erreur reliés au champ (aria-describedby). */
export function describedBy(id: string, hint?: string, error?: string): string | undefined {
  return [hint ? `${id}-hint` : null, error ? `${id}-error` : null].filter(Boolean).join(' ') || undefined
}
