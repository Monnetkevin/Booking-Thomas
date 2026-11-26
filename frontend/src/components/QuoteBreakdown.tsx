import { useTranslation } from 'react-i18next'
import { formatEuros } from '@/lib/money'
import type { Quote } from '@/lib/pricing/types'

/** Lignes du prix et total, telles que calculées par le serveur. */
export function QuoteBreakdown({ quote, locale = 'fr-FR' }: { quote: Quote; locale?: string }) {
  const { t } = useTranslation()

  return (
    <dl className="flex flex-col gap-2.5 text-[15px]">
      {quote.lines.map((line, index) => (
        <div key={`${line.type}-${index}`} className="flex justify-between gap-4">
          <dt>{line.label}</dt>
          <dd className="tabular-nums">{formatEuros(line.amount, locale)}</dd>
        </div>
      ))}
      <div className="mt-1 flex justify-between gap-4 border-t border-line pt-3 text-lg font-bold">
        <dt>{t('pricing.total')}</dt>
        <dd className="tabular-nums">{formatEuros(quote.total, locale)}</dd>
      </div>
    </dl>
  )
}
