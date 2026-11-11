import type { PublicPhoto } from '@/site/catalog/types'
import { cn } from '@/lib/cn'

interface PictureProps {
  photo: PublicPhoto
  /** Largeur affichée, pour que le navigateur choisisse la bonne taille (attribut sizes). */
  sizes: string
  className?: string
  /** Image principale de la page : chargée tout de suite, en priorité. */
  priority?: boolean
  alt?: string
}

/** Photo en AVIF (si le navigateur sait le lire) ou WebP, en trois tailles. */
export function Picture({ photo, sizes, className, priority = false, alt }: PictureProps) {
  const set = (format: 'avif' | 'webp') =>
    Object.values(photo.sources)
      .filter((source) => source[format])
      .map((source) => `${source[format]} ${source.width}w`)
      .join(', ')

  const fallback = photo.sources.card.webp ?? photo.sources.large.webp ?? photo.sources.thumb.webp ?? undefined

  return (
    <picture className="block h-full w-full">
      {set('avif') && <source type="image/avif" srcSet={set('avif')} sizes={sizes} />}
      {set('webp') && <source type="image/webp" srcSet={set('webp')} sizes={sizes} />}
      <img
        src={fallback}
        alt={alt ?? photo.alt}
        width={photo.width ?? undefined}
        height={photo.height ?? undefined}
        loading={priority ? 'eager' : 'lazy'}
        fetchPriority={priority ? 'high' : 'auto'}
        decoding="async"
        className={cn('h-full w-full object-cover', className)}
      />
    </picture>
  )
}
