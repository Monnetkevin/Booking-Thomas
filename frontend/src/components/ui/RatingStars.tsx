import { StarIcon } from '@/components/icons'
import { cn } from '@/lib/cn'

interface RatingStarsProps {
  /** Note de 0 à 5 (arrondie à l'étoile la plus proche). */
  value: number
  /** Texte lu par les lecteurs d'écran, ex. « 4 sur 5 ». */
  label: string
  size?: number
  className?: string
}

/** Note affichée en étoiles (lecture seule), site et back-office. */
export function RatingStars({ value, label, size = 16, className }: RatingStarsProps) {
  const filled = Math.round(value)

  return (
    <span role="img" aria-label={label} className={cn('inline-flex gap-0.5 text-accent', className)}>
      {[1, 2, 3, 4, 5].map((star) => (
        <StarIcon key={star} width={size} height={size} strokeWidth={1.5} className={star <= filled ? 'fill-accent' : 'fill-none'} />
      ))}
    </span>
  )
}
