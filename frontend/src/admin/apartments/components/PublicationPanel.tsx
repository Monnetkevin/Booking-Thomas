import { useTranslation } from 'react-i18next'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Section } from '@/components/ui/Section'
import { ApiError } from '@/lib/api'
import type { Apartment } from '../types'
import { usePublication } from '../useApartments'
import { StatusBadge } from './StatusBadge'

interface MissingItem {
  key: string
  message: string
}

/** Statut du logement et bascule publié / brouillon ; liste ce qui manque pour publier. */
export function PublicationPanel({ apartment }: { apartment: Apartment }) {
  const { t } = useTranslation()
  const publication = usePublication(apartment.id)
  const published = apartment.status === 'published'

  const error = publication.error
  const missing =
    error instanceof ApiError && error.code === 'apartment_not_publishable'
      ? ((error.details.missing as MissingItem[] | undefined) ?? [])
      : []

  return (
    <Section title={t('admin.apartments.publication.title')}>
      <div className="flex flex-col gap-3">
        <div className="flex items-center gap-3">
          <StatusBadge status={apartment.status} />
          <p className="text-sm text-muted">
            {published ? t('admin.apartments.publication.published') : t('admin.apartments.publication.draft')}
          </p>
        </div>
        {missing.length > 0 && (
          <Alert tone="warning">
            <p>{t('admin.apartments.publication.missing')}</p>
            <ul className="mt-1 list-disc pl-5 font-normal">
              {missing.map((item) => (
                <li key={item.key}>{item.message}</li>
              ))}
            </ul>
          </Alert>
        )}
        {error && missing.length === 0 && <Alert tone="danger">{t('common.error')}</Alert>}
        <Button
          variant={published ? 'secondary' : 'primary'}
          loading={publication.isPending}
          onClick={() => publication.mutate(!published)}
        >
          {published ? t('admin.apartments.publication.unpublish') : t('admin.apartments.publication.publish')}
        </Button>
      </div>
    </Section>
  )
}
