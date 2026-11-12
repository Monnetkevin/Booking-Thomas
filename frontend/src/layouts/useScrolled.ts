import { useEffect, useState } from 'react'

/** Vrai dès que la page a défilé de plus de `threshold` pixels (ombre de l'en-tête fixe). */
export function useScrolled(threshold = 4): boolean {
  const [scrolled, setScrolled] = useState(() => typeof window !== 'undefined' && window.scrollY > threshold)

  useEffect(() => {
    const update = () => setScrolled(window.scrollY > threshold)
    update()
    window.addEventListener('scroll', update, { passive: true })
    return () => window.removeEventListener('scroll', update)
  }, [threshold])

  return scrolled
}
