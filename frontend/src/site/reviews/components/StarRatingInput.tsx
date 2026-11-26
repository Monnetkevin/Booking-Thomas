import { useTranslation } from 'react-i18next'
import { StarIcon } from '@/components/icons'
import { cn } from '@/lib/cn'

const RATINGS = [1, 2, 3, 4, 5] as const
type Rating = (typeof RATINGS)[number]

interface StarRatingInputProps {
  value: number
  onChange: (value: number) => void
  error?: string
}

/** Note de 1 à 5 : boutons radio natifs (clavier, lecteurs d'écran) dessinés en étoiles. */
export function StarRatingInput({ value, onChange, error }: StarRatingInputProps) {
  const { t } = useTranslation()

  return (
    <fieldset className="flex flex-col gap-2" aria-describedby={error ? 'note-erreur' : undefined}>
      <legend className="mb-2 text-sm font-semibold">{t('review.rating')}</legend>
      <div className="flex gap-1">
        {RATINGS.map((star) => (
          <label key={star} className="flex size-12 cursor-pointer items-center justify-center rounded-full has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-accent">
            <input type="radio" name="rating" value={star} checked={value === star} onChange={() => onChange(star)} className="sr-only" />
            <span className="sr-only">{t('review.stars', { count: star })}</span>
            <StarIcon width={36} height={36} strokeWidth={1.5} className={cn('text-accent', star <= value ? 'fill-accent' : 'fill-none')} />
          </label>
        ))}
      </div>
      <span className="text-sm text-muted">{value > 0 ? t('review.ratingLabel', { count: value, label: t(`review.labels.${value as Rating}`) }) : t('review.ratingHint')}</span>
      {error && (
        <span id="note-erreur" className="text-sm text-danger">
          {error}
        </span>
      )}
    </fieldset>
  )
}
