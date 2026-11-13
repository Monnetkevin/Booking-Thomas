import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { useApartmentList } from '@/admin/apartments/useApartments'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Section } from '@/components/ui/Section'
import { Spinner } from '@/components/ui/Spinner'
import { ApartmentFeedCard } from '../components/ApartmentFeedCard'
import { useIcalFeeds } from '../useSync'

/** Synchronisation iCal : un bloc par logement avec son calendrier Airbnb. */
export function SyncPage() {
  const { t } = useTranslation()
  const apartments = useApartmentList()
  const feeds = useIcalFeeds()

  const retry = () => void Promise.all([apartments.refetch(), feeds.refetch()])

  return (
    <>
      <PageHeader title={t('admin.sync.title')} subtitle={t('admin.sync.subtitle')} />
      {apartments.isPending || feeds.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : apartments.isError || feeds.isError ? (
        <ErrorState onRetry={retry} />
      ) : apartments.data.length === 0 ? (
        <Alert tone="info">
          {t('admin.sync.noApartment')} <Link to="/admin/appartements/nouveau">{t('admin.apartments.add')}</Link>
        </Alert>
      ) : (
        <div className="space-y-5">
          <Section title={t('admin.sync.howTitle')}>
            <ol className="list-decimal space-y-2 pl-5 text-[15px]">
              <li>{t('admin.sync.howImport')}</li>
              <li>{t('admin.sync.howExport')}</li>
            </ol>
          </Section>
          {apartments.data.map((apartment) => (
            <ApartmentFeedCard
              key={apartment.id}
              apartment={apartment}
              feed={feeds.data.find((feed) => feed.apartment_id === apartment.id)}
            />
          ))}
        </div>
      )}
    </>
  )
}
