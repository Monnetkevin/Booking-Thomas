import { cn } from '@/lib/cn'

interface FilterChipsProps<T extends string> {
  label: string
  options: Array<{ value: T; label: string }>
  value: T
  onChange: (value: T) => void
}

/** Filtres exclusifs en pastilles (boutons à bascule, un seul actif). */
export function FilterChips<T extends string>({ label, options, value, onChange }: FilterChipsProps<T>) {
  return (
    <div role="group" aria-label={label} className="flex flex-wrap gap-2.5">
      {options.map((option) => {
        const active = option.value === value
        return (
          <button
            key={option.value}
            type="button"
            aria-pressed={active}
            onClick={() => onChange(option.value)}
            className={cn(
              'min-h-11 rounded-full border px-4 text-sm font-semibold transition-colors',
              active ? 'border-ink bg-ink text-white' : 'border-[#c9b8a6] bg-white text-ink hover:bg-sand',
            )}
          >
            {option.label}
          </button>
        )
      })}
    </div>
  )
}
