import { useTranslation } from 'react-i18next'
import { RatingStars } from '@/components/ui/RatingStars'
import { useLocale } from '@/i18n/useLocale'
import type { ReviewSummary } from '../types'

const RATINGS = ['5', '4', '3', '2', '1'] as const

/** Note moyenne, nombre d'avis et répartition des notes. */
export function ReviewSummaryPanel({ summary }: { summary: ReviewSummary }) {
  const { t } = useTranslation()
  const { locale } = useLocale()
  if (summary.average === null) return null
  const average = summary.average.toLocaleString(locale === 'fr' ? 'fr-FR' : 'en-GB', { minimumFractionDigits: 1 })

  return (
    <div className="grid items-center gap-6 rounded-[var(--radius-card)] border border-border bg-white p-6 sm:grid-cols-[240px_1fr] sm:gap-12 sm:px-8">
      <div className="flex flex-col gap-2">
        <span className="font-display text-[52px] leading-none font-semibold">{average}</span>
        <RatingStars value={summary.average} label={t('reviews.average', { average })} size={18} />
        <span className="text-[15px] text-muted">{t('reviews.count', { count: summary.count })}</span>
      </div>
      <dl className="flex flex-col gap-2">
        {RATINGS.map((rating) => {
          const share = Math.round((summary.distribution[rating] / summary.count) * 100)
          return (
            <div key={rating} className="flex items-center gap-3 text-sm">
              <dt className="w-12">{t('reviews.stars', { count: Number(rating) })}</dt>
              <dd className="flex flex-1 items-center gap-3">
                <span className="h-2 flex-1 overflow-hidden rounded-full bg-border" aria-hidden="true">
                  <span className="block h-2 bg-accent" style={{ width: `${share}%` }} />
                </span>
                <span className="w-10 text-right text-muted">{share} %</span>
              </dd>
            </div>
          )
        })}
      </dl>
    </div>
  )
}
