import { useCallback, useId, useRef, useState, type KeyboardEvent } from 'react'
import { CheckIcon, ChevronDownIcon } from '@/components/icons'
import { cn } from '@/lib/cn'
import { controlClasses, type FieldAppearance } from './fieldStyles'
import { useDismiss } from './useDismiss'

export interface SelectOption {
  value: string | number
  label: string
}

export interface SelectProps {
  options: SelectOption[]
  value: string | number
  onChange: (value: string) => void
  appearance?: FieldAppearance
  invalid?: boolean
  disabled?: boolean
  id?: string
  /** Nom envoyé avec le formulaire (champ caché). */
  name?: string
  className?: string
  'aria-labelledby'?: string
  'aria-describedby'?: string
}

/**
 * Liste déroulante aux couleurs du site (le menu natif ne se personnalise pas, surtout
 * sous Windows). Modèle ARIA « combobox » à choix unique : le focus reste sur la case ;
 * flèches, Origine/Fin, Entrée/Espace, Échap et première lettre au clavier.
 */
export function Select({
  options,
  value,
  onChange,
  appearance = 'field',
  invalid = false,
  disabled = false,
  id,
  name,
  className,
  ...aria
}: SelectProps) {
  const [open, setOpen] = useState(false)
  const selectedIndex = Math.max(0, options.findIndex((option) => String(option.value) === String(value)))
  const [activeIndex, setActiveIndex] = useState(selectedIndex)
  const rootRef = useRef<HTMLDivElement>(null)
  const buttonRef = useRef<HTMLButtonElement>(null)
  const listId = useId()
  const optionId = (index: number) => `${listId}-${index}`

  const close = useCallback(() => setOpen(false), [])
  useDismiss(open, rootRef, close, buttonRef)

  function show(index = selectedIndex) {
    setActiveIndex(index)
    setOpen(true)
  }

  function choose(index: number) {
    const option = options[index]
    if (option) onChange(String(option.value))
    setOpen(false)
    buttonRef.current?.focus()
  }

  function move(index: number) {
    setActiveIndex(Math.min(options.length - 1, Math.max(0, index)))
  }

  function handleKeyDown(event: KeyboardEvent<HTMLButtonElement>) {
    const key = event.key
    if (!open) {
      if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(key)) {
        event.preventDefault()
        show()
      }
      return
    }
    const actions: Record<string, () => void> = {
      ArrowDown: () => move(activeIndex + 1),
      ArrowUp: () => move(activeIndex - 1),
      Home: () => move(0),
      End: () => move(options.length - 1),
      Enter: () => choose(activeIndex),
      ' ': () => choose(activeIndex),
      Tab: () => setOpen(false),
    }
    const action = actions[key]
    if (action) {
      if (key !== 'Tab') event.preventDefault()
      action()
      return
    }
    // Première lettre : va à l'option suivante qui commence par cette lettre.
    if (key.length === 1) {
      const letter = key.toLowerCase()
      const next = [...options.keys()]
        .map((offset) => (activeIndex + 1 + offset) % options.length)
        .find((index) => options[index]?.label.toLowerCase().startsWith(letter))
      if (next !== undefined) move(next)
    }
  }

  return (
    <div ref={rootRef} className={cn('relative', className)}>
      {name && <input type="hidden" name={name} value={value} />}
      <button
        ref={buttonRef}
        id={id}
        type="button"
        role="combobox"
        disabled={disabled}
        aria-haspopup="listbox"
        aria-expanded={open}
        aria-controls={listId}
        aria-activedescendant={open ? optionId(activeIndex) : undefined}
        aria-invalid={invalid || undefined}
        {...aria}
        onClick={() => (open ? setOpen(false) : show())}
        onKeyDown={handleKeyDown}
        className={controlClasses(appearance, invalid, 'flex items-center justify-between gap-2 text-left')}
      >
        <span className="truncate">{options[selectedIndex]?.label}</span>
        <ChevronDownIcon
          aria-hidden="true"
          width={18}
          height={18}
          className={cn('shrink-0 text-muted transition-transform', open && 'rotate-180')}
        />
      </button>

      <ul
        id={listId}
        role="listbox"
        hidden={!open}
        aria-labelledby={aria['aria-labelledby'] ?? id}
        className="absolute top-full left-0 z-40 mt-2 max-h-72 min-w-full overflow-auto rounded-[16px] border border-line bg-white p-1.5 shadow-[0_16px_40px_rgba(43,38,34,0.14)]"
      >
        {options.map((option, index) => {
          const selected = index === selectedIndex
          return (
            <li
              key={option.value}
              id={optionId(index)}
              role="option"
              aria-selected={selected}
              // Le focus reste sur la case : la souris ne doit pas le lui prendre.
              onMouseDown={(event) => event.preventDefault()}
              // Mouvement réel de la souris seulement : une liste qui s'ouvre sous le curseur ne vole pas la sélection du clavier.
              onMouseMove={() => activeIndex !== index && setActiveIndex(index)}
              onClick={() => choose(index)}
              className={cn(
                'flex min-h-10 cursor-pointer items-center justify-between gap-3 rounded-[10px] px-3 text-[15px] whitespace-nowrap text-ink',
                index === activeIndex && 'bg-accent-soft',
                selected && 'font-semibold text-accent-text',
              )}
            >
              {option.label}
              {selected && <CheckIcon aria-hidden="true" width={16} height={16} />}
            </li>
          )
        })}
      </ul>
    </div>
  )
}
