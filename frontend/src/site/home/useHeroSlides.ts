import { useCallback, useEffect, useRef, useState, type CSSProperties } from 'react'

/** Durée de l'agrandissement d'une carte jusqu'au plein cadre (ms). */
export const GROW_DURATION = 900

interface Grow {
  index: number
  /** Position de départ de la carte, relative à la section. */
  from: { left: number; top: number; width: number; height: number }
  expanded: boolean
}

function prefersReducedMotion(): boolean {
  return typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches === true
}

/**
 * Logement affiché en fond du haut de l'accueil. Une carte choisie grossit depuis sa place
 * jusqu'au plein cadre, puis devient le fond (sans animation si l'utilisateur la refuse).
 */
export function useHeroSlides(count: number) {
  const [current, setCurrent] = useState(0)
  const [grow, setGrow] = useState<Grow | null>(null)
  const sectionRef = useRef<HTMLElement>(null)
  const timers = useRef<number[]>([])

  useEffect(() => () => timers.current.forEach((id) => window.clearTimeout(id)), [])

  const pick = useCallback(
    (index: number, card: HTMLElement) => {
      const section = sectionRef.current
      if (grow || !section) return
      if (prefersReducedMotion()) {
        setCurrent(index)
        return
      }
      const box = section.getBoundingClientRect()
      const rect = card.getBoundingClientRect()
      setGrow({
        index,
        from: { left: rect.left - box.left, top: rect.top - box.top, width: rect.width, height: rect.height },
        expanded: false,
      })
      // Deux temps : la photo se pose sur la carte, puis s'étire au cadre au rendu suivant.
      timers.current.push(
        window.setTimeout(() => setGrow((g) => (g ? { ...g, expanded: true } : null)), 40),
        window.setTimeout(() => {
          setCurrent(index)
          setGrow(null)
        }, GROW_DURATION + 60),
      )
    },
    [grow],
  )

  const growStyle: CSSProperties | null = grow
    ? grow.expanded
      ? { left: 0, top: 0, width: '100%', height: '100%', borderRadius: 0 }
      : { ...grow.from, borderRadius: 22 }
    : null

  return {
    current: count > 0 ? current % count : 0,
    growing: grow?.index ?? null,
    growStyle,
    sectionRef,
    pick,
  }
}
