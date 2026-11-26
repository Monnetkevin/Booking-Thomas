import type { HTMLAttributes } from 'react'
import { cn } from '@/lib/cn'

/**
 * Largeur de lecture du site : 16 px de marge sur téléphone, 1200 px au plus.
 * `wide` : presque toute la largeur de l'écran (haut de l'accueil et son en-tête).
 */
export function Container({ className, wide = false, ...props }: HTMLAttributes<HTMLDivElement> & { wide?: boolean }) {
  return (
    <div
      className={cn(
        'mx-auto w-full px-4 sm:px-6',
        wide ? 'max-w-[1680px] lg:px-10' : 'max-w-[1200px] lg:px-8',
        className,
      )}
      {...props}
    />
  )
}
