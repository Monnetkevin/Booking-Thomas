import type { SelectHTMLAttributes } from 'react'
import { ChevronDownIcon } from '@/components/icons'
import { cn } from '@/lib/cn'
import { controlClasses, type FieldAppearance } from './fieldStyles'

export interface SelectOption {
  value: string | number
  label: string
}

interface SelectProps extends Omit<SelectHTMLAttributes<HTMLSelectElement>, 'children'> {
  options: SelectOption[]
  appearance?: FieldAppearance
  invalid?: boolean
}

/**
 * Liste déroulante aux couleurs du site. Le menu reste celui du système (pratique au
 * doigt, accessible au clavier) ; seule la case est redessinée, avec sa flèche.
 */
export function Select({ options, appearance = 'field', invalid = false, className, ...props }: SelectProps) {
  return (
    <div className={cn('relative', className)}>
      <select
        aria-invalid={invalid || undefined}
        className={controlClasses(appearance, invalid, 'cursor-pointer appearance-none pr-10 md:pr-8')}
        {...props}
      >
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
      <ChevronDownIcon
        aria-hidden="true"
        width={18}
        height={18}
        className={cn('pointer-events-none absolute top-1/2 -translate-y-1/2 text-muted', appearance === 'pill' ? 'right-3 md:right-0' : 'right-3')}
      />
    </div>
  )
}
