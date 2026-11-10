import { useId, type InputHTMLAttributes } from 'react'
import { cn } from '@/lib/cn'

interface TextFieldProps extends InputHTMLAttributes<HTMLInputElement> {
  label: string
  error?: string
  hint?: string
}

/** Champ texte accessible : libellé lié, aide et erreur annoncées par les lecteurs d'écran. */
export function TextField({ label, error, hint, id, className, ...props }: TextFieldProps) {
  const generatedId = useId()
  const inputId = id ?? generatedId
  const hintId = hint ? `${inputId}-hint` : undefined
  const errorId = error ? `${inputId}-error` : undefined

  return (
    <div className={cn('flex flex-col gap-1.5', className)}>
      <label htmlFor={inputId} className="text-sm font-semibold text-ink">
        {label}
      </label>
      <input
        id={inputId}
        aria-invalid={error ? true : undefined}
        aria-describedby={[hintId, errorId].filter(Boolean).join(' ') || undefined}
        className={cn(
          'min-h-11 w-full rounded-[var(--radius-field)] border bg-white px-3.5 text-[15px] text-ink',
          'placeholder:text-muted',
          error ? 'border-danger' : 'border-field',
        )}
        {...props}
      />
      {hint && (
        <p id={hintId} className="text-sm text-muted">
          {hint}
        </p>
      )}
      {error && (
        <p id={errorId} className="text-sm font-medium text-danger">
          {error}
        </p>
      )}
    </div>
  )
}

interface CheckboxProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
  label: string
}

export function Checkbox({ label, id, className, ...props }: CheckboxProps) {
  const generatedId = useId()
  const inputId = id ?? generatedId

  return (
    <div className={cn('flex min-h-11 items-center gap-2.5', className)}>
      <input id={inputId} type="checkbox" className="size-5 accent-accent" {...props} />
      <label htmlFor={inputId} className="text-[15px]">
        {label}
      </label>
    </div>
  )
}
