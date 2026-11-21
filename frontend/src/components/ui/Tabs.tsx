import { useId, useRef, type KeyboardEvent, type ReactNode } from 'react'
import { cn } from '@/lib/cn'

interface TabsProps<T extends string> {
  label: string
  tabs: { value: T; label: string }[]
  value: T
  onChange: (value: T) => void
  /** Contenu de l'onglet actif. */
  children: ReactNode
}

/**
 * Onglets (modèle ARIA « tablist ») : flèches gauche/droite, Origine et Fin au clavier, un
 * seul onglet dans l'ordre de tabulation. Défilent à l'horizontale sur téléphone.
 */
export function Tabs<T extends string>({ label, tabs, value, onChange, children }: TabsProps<T>) {
  const id = useId()
  const refs = useRef<(HTMLButtonElement | null)[]>([])

  const onKeyDown = (event: KeyboardEvent<HTMLButtonElement>, index: number) => {
    const last = tabs.length - 1
    const next = { ArrowRight: index === last ? 0 : index + 1, ArrowLeft: index === 0 ? last : index - 1, Home: 0, End: last }[event.key]
    if (next === undefined) return
    event.preventDefault()
    const tab = tabs[next]
    if (!tab) return
    onChange(tab.value)
    refs.current[next]?.focus()
  }

  return (
    <div>
      <div role="tablist" aria-label={label} className="mb-5 flex gap-1 overflow-x-auto border-b border-border">
        {tabs.map((tab, index) => {
          const selected = tab.value === value
          return (
            <button
              key={tab.value}
              ref={(element) => {
                refs.current[index] = element
              }}
              id={`${id}-tab-${tab.value}`}
              type="button"
              role="tab"
              aria-selected={selected}
              aria-controls={`${id}-panel`}
              tabIndex={selected ? 0 : -1}
              onClick={() => onChange(tab.value)}
              onKeyDown={(event) => onKeyDown(event, index)}
              className={cn(
                '-mb-px min-h-11 shrink-0 border-b-2 px-3.5 text-sm font-bold whitespace-nowrap',
                selected ? 'border-accent text-accent-strong' : 'border-transparent text-muted hover:text-ink',
              )}
            >
              {tab.label}
            </button>
          )
        })}
      </div>
      <div id={`${id}-panel`} role="tabpanel" aria-labelledby={`${id}-tab-${value}`}>
        {children}
      </div>
    </div>
  )
}
