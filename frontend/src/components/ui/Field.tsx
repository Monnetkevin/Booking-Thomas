import { useId, type InputHTMLAttributes, type ReactNode, type SelectHTMLAttributes, type TextareaHTMLAttributes } from 'react'
import { cn } from '@/lib/cn'
import { FieldMessages } from './FieldMessages'
import { describedBy, fieldClasses } from './fieldStyles'

interface BaseProps {
  label: string
  error?: string
  hint?: string
  /** Texte collé à droite du champ (« € », « nuits », « m² »). */
  suffix?: string
}

function Label({ htmlFor, children }: { htmlFor: string; children: ReactNode }) {
  return (
    <label htmlFor={htmlFor} className="text-sm font-semibold text-ink">
      {children}
    </label>
  )
}

type TextFieldProps = BaseProps & InputHTMLAttributes<HTMLInputElement>

/** Champ texte accessible : libellé lié, aide et erreur annoncées par les lecteurs d'écran. */
export function TextField({ label, error, hint, suffix, id, className, ...props }: TextFieldProps) {
  const generatedId = useId()
  const inputId = id ?? generatedId

  return (
    <div className={cn('flex flex-col gap-1.5', className)}>
      <Label htmlFor={inputId}>{label}</Label>
      <div className="relative">
        <input
          id={inputId}
          aria-invalid={error ? true : undefined}
          aria-describedby={describedBy(inputId, hint, error)}
          className={fieldClasses(Boolean(error), cn('min-h-11', suffix && 'pr-14'))}
          {...props}
        />
        {suffix && (
          <span aria-hidden="true" className="pointer-events-none absolute inset-y-0 right-3.5 flex items-center text-muted">
            {suffix}
          </span>
        )}
      </div>
      <FieldMessages id={inputId} hint={hint} error={error} />
    </div>
  )
}

type TextAreaProps = BaseProps & TextareaHTMLAttributes<HTMLTextAreaElement>

export function TextArea({ label, error, hint, id, className, rows = 5, ...props }: TextAreaProps) {
  const generatedId = useId()
  const inputId = id ?? generatedId

  return (
    <div className={cn('flex flex-col gap-1.5', className)}>
      <Label htmlFor={inputId}>{label}</Label>
      <textarea
        id={inputId}
        rows={rows}
        aria-invalid={error ? true : undefined}
        aria-describedby={describedBy(inputId, hint, error)}
        className={fieldClasses(Boolean(error), 'py-2.5 leading-relaxed')}
        {...props}
      />
      <FieldMessages id={inputId} hint={hint} error={error} />
    </div>
  )
}

interface SelectFieldProps extends BaseProps, SelectHTMLAttributes<HTMLSelectElement> {
  options: Array<{ value: string; label: string }>
}

export function SelectField({ label, error, hint, id, className, options, ...props }: SelectFieldProps) {
  const generatedId = useId()
  const inputId = id ?? generatedId

  return (
    <div className={cn('flex flex-col gap-1.5', className)}>
      <Label htmlFor={inputId}>{label}</Label>
      <select
        id={inputId}
        aria-invalid={error ? true : undefined}
        aria-describedby={describedBy(inputId, hint, error)}
        className={fieldClasses(Boolean(error), 'min-h-11')}
        {...props}
      >
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
      <FieldMessages id={inputId} hint={hint} error={error} />
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
