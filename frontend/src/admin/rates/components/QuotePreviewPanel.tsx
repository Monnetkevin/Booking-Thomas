import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { QuoteBreakdown } from '@/components/QuoteBreakdown'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { TextField } from '@/components/ui/Field'
import { Section } from '@/components/ui/Section'
import { ApiError } from '@/lib/api'
import { useQuotePreview } from '../useRates'

/** Prix d'un séjour test calculé par l'API, avec les règles non respectées. */
export function QuotePreviewPanel({ apartmentId }: { apartmentId: number }) {
  const { t } = useTranslation()
  const preview = useQuotePreview(apartmentId)
  const [stay, setStay] = useState({ arrival: '', departure: '', adults: '2', children: '0' })

  const fieldError = (field: string) =>
    preview.error instanceof ApiError ? preview.error.fieldErrors[field]?.[0] : undefined

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    preview.mutate({
      arrival: stay.arrival,
      departure: stay.departure,
      adults: Number(stay.adults),
      children: Number(stay.children),
    })
  }

  const result = preview.data

  return (
    <Section title={t('admin.rates.preview.title')} description={t('admin.rates.preview.description')}>
      <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-3">
        <div className="grid grid-cols-2 gap-3">
          <TextField label={t('admin.rates.preview.arrival')} type="date" value={stay.arrival} error={fieldError('arrival')} onChange={(e) => setStay({ ...stay, arrival: e.target.value })} />
          <TextField label={t('admin.rates.preview.departure')} type="date" min={stay.arrival || undefined} value={stay.departure} error={fieldError('departure')} onChange={(e) => setStay({ ...stay, departure: e.target.value })} />
          <TextField label={t('admin.rates.preview.adults')} type="number" min={1} inputMode="numeric" value={stay.adults} error={fieldError('adults')} onChange={(e) => setStay({ ...stay, adults: e.target.value })} />
          <TextField label={t('admin.rates.preview.children')} type="number" min={0} inputMode="numeric" value={stay.children} onChange={(e) => setStay({ ...stay, children: e.target.value })} />
        </div>
        <Button type="submit" variant="secondary" loading={preview.isPending}>
          {t('admin.rates.preview.submit')}
        </Button>
      </form>

      <div aria-live="polite" className="mt-4 flex flex-col gap-3">
        {preview.isError && !(preview.error instanceof ApiError && preview.error.status === 422) && (
          <Alert tone="danger">{t('common.error')}</Alert>
        )}
        {result && result.warnings.length > 0 && (
          <Alert tone="warning">
            <ul className="list-disc pl-5 font-normal">
              {result.warnings.map((warning) => (
                <li key={warning.code}>{warning.message}</li>
              ))}
            </ul>
          </Alert>
        )}
        {result?.quote ? (
          <QuoteBreakdown quote={result.quote} />
        ) : (
          result && <p className="text-sm text-muted">{t('admin.rates.preview.noRate')}</p>
        )}
      </div>
    </Section>
  )
}
