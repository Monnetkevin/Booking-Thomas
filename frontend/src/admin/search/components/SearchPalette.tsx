import { useEffect, useId, useMemo, useRef, useState, type KeyboardEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router'
import { BuildingIcon, CalendarIcon, ListIcon, SearchIcon } from '@/components/icons'
import { Spinner } from '@/components/ui/Spinner'
import { cn } from '@/lib/cn'
import { formatDate } from '@/lib/dates'
import { useDebouncedValue } from '@/lib/useDebouncedValue'
import { apiHits, MIN_SEARCH_LENGTH, pageHits } from '../searchHits'
import type { SearchHit } from '../types'
import { useSearch } from '../useSearch'

interface SearchPaletteProps {
  /** Pages du back-office autorisées au rôle, avec leur libellé traduit. */
  pages: { to: string; label: string }[]
}

const KIND_ICON = { booking: CalendarIcon, apartment: BuildingIcon, page: ListIcon } as const

/**
 * Recherche rapide : champ de la barre du haut (icône sur téléphone) ouvrant une palette,
 * aussi par Ctrl K / Cmd K. Flèches pour choisir, Entrée pour ouvrir la page, Échap pour fermer.
 */
export function SearchPalette({ pages }: SearchPaletteProps) {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [open, setOpen] = useState(false)
  const [term, setTerm] = useState('')
  // Résultat choisi, suivi par son identifiant : les résultats de l'API arrivent après ceux des pages
  // et décalent la liste ; un index ferait ouvrir un autre résultat que celui surligné.
  const [activeId, setActiveId] = useState<string | null>(null)
  const dialogRef = useRef<HTMLDialogElement>(null)
  const listId = useId()
  const debounced = useDebouncedValue(term)
  const search = useSearch(debounced)

  const ready = term.trim().length >= MIN_SEARCH_LENGTH
  // Résultats de l'API seulement s'ils répondent au terme affiché (pas pendant la temporisation).
  const current = ready && debounced.trim() === term.trim() ? search.data : undefined
  const hits = useMemo<SearchHit[]>(
    () => [...apiHits(current, (arrival, departure) => `${formatDate(arrival)} → ${formatDate(departure)}`), ...pageHits(term, pages)],
    [current, term, pages],
  )
  const found = hits.findIndex((hit) => hit.id === activeId)
  const active = found === -1 ? 0 : found

  // Ctrl K / Cmd K ouvre la palette depuis n'importe quelle page.
  useEffect(() => {
    const onKeyDown = (event: globalThis.KeyboardEvent) => {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault()
        setOpen(true)
      }
    }
    document.addEventListener('keydown', onKeyDown)
    return () => document.removeEventListener('keydown', onKeyDown)
  }, [])

  useEffect(() => {
    const dialog = dialogRef.current
    if (!dialog) return
    if (open && !dialog.open) dialog.showModal()
    if (!open && dialog.open) dialog.close()
  }, [open])

  const close = () => {
    setOpen(false)
    setTerm('')
    setActiveId(null)
  }

  const go = (hit: SearchHit | undefined) => {
    if (!hit) return
    close()
    void navigate(hit.to)
  }

  const onKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault()
      if (hits.length === 0) return
      const step = event.key === 'ArrowDown' ? 1 : -1
      setActiveId(hits[(active + step + hits.length) % hits.length]?.id ?? null)
    } else if (event.key === 'Enter') {
      event.preventDefault()
      go(hits[active])
    }
  }

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen(true)}
        aria-label={t('admin.search.open')}
        aria-keyshortcuts="Control+K Meta+K"
        className="flex h-11 w-11 items-center justify-center gap-3 rounded-xl border border-line text-muted hover:bg-sand md:h-[42px] md:w-full md:max-w-md md:justify-start md:border-transparent md:bg-sand md:px-3.5 md:text-sm"
      >
        <SearchIcon />
        <span className="hidden flex-1 truncate text-left md:inline">{t('admin.search.placeholder')}</span>
        <kbd className="hidden rounded-md border border-border bg-white px-1.5 py-0.5 font-sans text-xs font-bold md:inline">Ctrl K</kbd>
      </button>

      <dialog
        ref={dialogRef}
        aria-label={t('admin.search.title')}
        onCancel={(event) => {
          event.preventDefault()
          close()
        }}
        onClick={(event) => event.target === event.currentTarget && close()}
        className="mx-auto mt-[10vh] w-[calc(100%-2rem)] max-w-xl rounded-[var(--radius-card)] border border-border bg-white p-0 text-ink shadow-2xl backdrop:bg-night/40"
      >
        {open && (
          <div>
            <div className="flex items-center gap-3 border-b border-line px-4">
              <SearchIcon className="shrink-0 text-muted" />
              <input
                autoFocus
                type="text"
                enterKeyHint="search"
                role="combobox"
                aria-expanded={hits.length > 0}
                aria-controls={listId}
                aria-autocomplete="list"
                aria-activedescendant={hits[active] ? `${listId}-${active}` : undefined}
                aria-label={t('admin.search.title')}
                placeholder={t('admin.search.placeholder')}
                value={term}
                onChange={(event) => {
                  setTerm(event.target.value)
                  setActiveId(null)
                }}
                onKeyDown={onKeyDown}
                className="h-14 min-w-0 flex-1 bg-transparent text-[15px] outline-none placeholder:text-muted"
              />
              {search.isFetching && <Spinner label={t('common.loading')} className="size-4" />}
            </div>
            <ul id={listId} role="listbox" aria-label={t('admin.search.results')} className="max-h-[60vh] overflow-y-auto p-2">
              {hits.map((hit, index) => {
                const Icon = KIND_ICON[hit.kind]
                return (
                  <li
                    key={hit.id}
                    id={`${listId}-${index}`}
                    role="option"
                    aria-selected={index === active}
                    onMouseEnter={() => setActiveId(hit.id)}
                    onMouseDown={(event) => event.preventDefault()}
                    onClick={() => go(hit)}
                    className={cn('flex min-h-12 cursor-pointer items-center gap-3 rounded-xl px-3 py-2', index === active && 'bg-accent-soft')}
                  >
                    <Icon width={18} height={18} className="shrink-0 text-muted" />
                    <span className="flex min-w-0 flex-col">
                      <span className="truncate text-sm font-semibold">{hit.title}</span>
                      <span className="truncate text-xs text-muted">{hit.detail ?? t(`admin.search.kinds.${hit.kind}`)}</span>
                    </span>
                  </li>
                )
              })}
            </ul>
            {hits.length === 0 && (
              <p className="px-5 pb-5 text-sm text-muted" role="status">
                {!ready ? t('admin.search.hint') : search.isFetching || debounced !== term ? t('common.loading') : t('admin.search.empty')}
              </p>
            )}
          </div>
        )}
      </dialog>
    </>
  )
}
