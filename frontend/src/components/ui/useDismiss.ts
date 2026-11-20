import { useEffect, type RefObject } from 'react'

/**
 * Referme un panneau ouvert (calendrier, liste) sur un clic en dehors de `ref` ou sur Échap.
 * `ref` peut être une liste : bouton et panneau rendu ailleurs dans la page (portail).
 * Échap rend aussi le focus à l'élément `returnFocus`.
 */
export function useDismiss(
  open: boolean,
  ref: RefObject<HTMLElement | null> | RefObject<HTMLElement | null>[],
  onClose: () => void,
  returnFocus?: RefObject<HTMLElement | null>,
) {
  useEffect(() => {
    if (!open) return
    const onPointerDown = (event: MouseEvent) => {
      const inside = (Array.isArray(ref) ? ref : [ref]).some((zone) => zone.current?.contains(event.target as Node))
      if (!inside) onClose()
    }
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key !== 'Escape') return
      onClose()
      returnFocus?.current?.focus()
    }
    document.addEventListener('mousedown', onPointerDown)
    document.addEventListener('keydown', onKeyDown)
    return () => {
      document.removeEventListener('mousedown', onPointerDown)
      document.removeEventListener('keydown', onKeyDown)
    }
  }, [open, ref, onClose, returnFocus])
}
