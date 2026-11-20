import { useSyncExternalStore } from 'react'

/** Vrai tant que la requête média correspond (ex. « (min-width: 1024px) »), mis à jour en direct. */
export function useMediaQuery(query: string): boolean {
  return useSyncExternalStore(
    (onChange) => {
      const list = window.matchMedia(query)
      list.addEventListener('change', onChange)
      return () => list.removeEventListener('change', onChange)
    },
    () => window.matchMedia(query).matches,
    () => false,
  )
}
