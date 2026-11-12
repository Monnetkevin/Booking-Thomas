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

/** Apparence d'un sélecteur ou d'un champ de date. */
export type FieldAppearance = 'field' | 'pill'

/**
 * field : champ encadré des formulaires ;
 * pill : encadré sur téléphone, sans cadre dans la barre de recherche en pilule à partir de la tablette.
 */
export function controlClasses(appearance: FieldAppearance, hasError = false, extra?: string): string {
  return appearance === 'pill'
    ? cn(
        'min-h-11 w-full rounded-[var(--radius-field)] border bg-white px-3 text-[15px] font-medium text-ink',
        'md:min-h-9 md:rounded-none md:border-0 md:bg-transparent md:px-0 md:font-semibold',
        hasError ? 'border-danger' : 'border-field',
        extra,
      )
    : fieldClasses(hasError, cn('min-h-11', extra))
}

/** Identifiants d'aide et d'erreur reliés au champ (aria-describedby). */
export function describedBy(id: string, hint?: string, error?: string): string | undefined {
  return [hint ? `${id}-hint` : null, error ? `${id}-error` : null].filter(Boolean).join(' ') || undefined
}
