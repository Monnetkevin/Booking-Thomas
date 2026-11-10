import { useCallback } from 'react'
import { useLocation } from 'react-router'
import { localeFromPath, localizedPath, stripLocale, type Locale } from './index'

/** Langue de la page courante et constructeur de liens dans cette langue. */
export function useLocale() {
  const { pathname, search } = useLocation()
  const locale = localeFromPath(pathname)

  const to = useCallback((path: string) => localizedPath(path, locale), [locale])

  /** Même page dans l'autre langue (filtres et dates de l'URL conservés). */
  const switchTo = useCallback(
    (target: Locale) => localizedPath(stripLocale(pathname), target) + search,
    [pathname, search],
  )

  return { locale, to, switchTo }
}
