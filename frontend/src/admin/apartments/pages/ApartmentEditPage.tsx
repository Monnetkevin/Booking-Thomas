import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useParams, useSearchParams } from 'react-router'
import { useCurrentUser } from '@/admin/auth/useAuth'
import { PageHeader } from '@/admin/components/PageHeader'
import { OccupancyStrip } from '@/admin/dashboard/components/OccupancyStrip'
import { relativeTime } from '@/admin/notifications/notificationFormat'
import { usePlanning } from '@/admin/planning/usePlanning'
import { ErrorState } from '@/components/feedback/ErrorState'
import { ArrowUpRightIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { Section } from '@/components/ui/Section'
import { Spinner } from '@/components/ui/Spinner'
import { Tabs } from '@/components/ui/Tabs'
import { ApiError } from '@/lib/api'
import { formatDateTime, toIso } from '@/lib/dates'
import { formErrorsFromApi, formFromApartment, formSectionOf, type ApartmentFormSection, type ApartmentFormValues } from '../apartmentForm'
import { AmenityPicker } from '../components/AmenityPicker'
import { ApartmentForm } from '../components/ApartmentForm'
import { ComputedAddressInfo } from '../components/ComputedAddressInfo'
import { DangerZone } from '../components/DangerZone'
import { PhotoManager } from '../components/PhotoManager'
import { PublicationPanel } from '../components/PublicationPanel'
import { StatusBadge } from '../components/StatusBadge'
import { useApartment, useUpdateApartment } from '../useApartments'
import { BackLink } from './ApartmentCreatePage'

type Tab = 'informations' | 'photos' | 'equipements' | 'regles' | 'calendrier'
const TABS: Tab[] = ['informations', 'photos', 'equipements', 'regles', 'calendrier']
const FORM_TAB: Record<ApartmentFormSection, Tab> = { info: 'informations', location: 'informations', rules: 'regles' }
const CALENDAR_DAYS = 31

/** Calendrier du logement : ses 31 prochaines nuits, depuis le planning de tous les logements. */
function ApartmentCalendar({ apartmentId }: { apartmentId: number }) {
  const { t } = useTranslation()
  const [today] = useState(() => toIso(new Date()))
  const planning = usePlanning(today, CALENDAR_DAYS)

  return (
    <Section
      title={t('admin.apartments.calendar.title')}
      description={t('admin.apartments.calendar.hint', { days: CALENDAR_DAYS })}
      actions={
        <Link to="/admin/planning" className="text-sm font-bold text-accent-strong no-underline">
          {t('admin.apartments.calendar.openPlanning')}
        </Link>
      }
    >
      {planning.data ? (
        <OccupancyStrip planning={{ ...planning.data, apartments: planning.data.apartments.filter((apartment) => apartment.id === apartmentId) }} />
      ) : planning.isError ? (
        <ErrorState onRetry={() => void planning.refetch()} />
      ) : (
        <Spinner label={t('common.loading')} />
      )}
    </Section>
  )
}

/**
 * Fiche d'un logement en onglets : informations et adresse, photos, équipements, règles du
 * séjour, calendrier. Un seul formulaire pour les informations et les règles : les champs
 * des autres onglets restent saisis, et une erreur ramène à son onglet.
 */
export function ApartmentEditPage() {
  const { t } = useTranslation()
  const id = Number(useParams().id)
  const [params, setParams] = useSearchParams()
  const apartment = useApartment(id)
  const update = useUpdateApartment(id)
  const { data: user } = useCurrentUser()
  const tab: Tab = TABS.find((value) => value === params.get('onglet')) ?? 'informations'
  const setTab = (value: Tab) =>
    setParams(
      (current) => {
        const next = new URLSearchParams(current)
        if (value === 'informations') next.delete('onglet')
        else next.set('onglet', value)
        return next
      },
      { replace: true },
    )

  if (apartment.isPending) return <Spinner label={t('common.loading')} />
  if (apartment.isError) {
    return apartment.error instanceof ApiError && apartment.error.status === 404 ? (
      <>
        <BackLink />
        <Alert tone="warning">{t('admin.apartments.notFound')}</Alert>
      </>
    ) : (
      <ErrorState onRetry={() => void apartment.refetch()} />
    )
  }

  const data = apartment.data
  const apiError = update.error instanceof ApiError ? update.error : null
  const serverErrors = apiError ? formErrorsFromApi(apiError.fieldErrors) : undefined
  const firstServerError = serverErrors ? (Object.keys(serverErrors)[0] as keyof ApartmentFormValues | undefined) : undefined
  const formTab = tab === 'informations' || tab === 'regles'
  const updatedAt = data.updated_at ? relativeTime(data.updated_at) : null

  return (
    <>
      <BackLink />
      <PageHeader
        title={data.name.fr ?? ''}
        subtitle={
          data.updated_at && updatedAt
            ? t('admin.apartments.lastUpdated', {
                when:
                  updatedAt.key === 'date'
                    ? formatDateTime(data.updated_at)
                    : t(`admin.notifications.when.${updatedAt.key}`, 'count' in updatedAt ? { count: updatedAt.count } : {}).toLocaleLowerCase('fr-FR'),
              })
            : t('admin.apartments.editSubtitle')
        }
        actions={
          <>
            <StatusBadge status={data.status} />
            {data.status === 'published' && (
              <a href={`/logements/${data.slug}`} target="_blank" rel="noopener" className={buttonClasses('secondary')}>
                {t('admin.apartments.viewOnSite')}
                <ArrowUpRightIcon width={16} height={16} />
              </a>
            )}
          </>
        }
      />

      {update.isSuccess && (
        <div className="mb-4">
          <Alert tone="success">{t('admin.common.saved')}</Alert>
        </div>
      )}
      {update.isError && apiError?.status !== 422 && (
        <div className="mb-4">
          <Alert tone="danger">{t('common.error')}</Alert>
        </div>
      )}
      {firstServerError && !formTab && (
        <div className="mb-4">
          <Alert tone="warning">
            {t('admin.apartments.errorsElsewhere')}{' '}
            <button type="button" className="font-bold underline" onClick={() => setTab(FORM_TAB[formSectionOf(firstServerError)])}>
              {t('admin.apartments.showErrors')}
            </button>
          </Alert>
        </div>
      )}

      <Tabs label={t('admin.apartments.tabs.label')} value={tab} onChange={setTab} tabs={TABS.map((value) => ({ value, label: t(`admin.apartments.tabs.${value}`) }))}>
        <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)] [&>*]:min-w-0">
          <div className="flex flex-col gap-5">
            {/* Toujours monté (masqué hors de ses onglets) : la saisie survit au changement d'onglet.
                Recréé avec les valeurs enregistrées après chaque sauvegarde, et seulement alors. */}
            <div hidden={!formTab}>
              <ApartmentForm
                key={update.data?.updated_at ?? 'initial'}
                initialValues={formFromApartment(data)}
                submitLabel={t('admin.common.save')}
                pending={update.isPending}
                serverErrors={serverErrors}
                addressInfo={<ComputedAddressInfo apartment={data} />}
                visibleSections={tab === 'regles' ? ['rules'] : ['info', 'location']}
                onErrorsIn={(section) => setTab(FORM_TAB[section])}
                canEditPricing={user?.role === 'admin'}
                onSubmit={(input) => update.mutate(input)}
              />
            </div>
            {tab === 'photos' && <PhotoManager apartmentId={data.id} photos={data.photos} />}
            {tab === 'equipements' && <AmenityPicker apartmentId={data.id} selectedIds={data.amenity_ids} />}
            {tab === 'calendrier' && <ApartmentCalendar apartmentId={data.id} />}
          </div>
          <div className="flex flex-col gap-5">
            <PublicationPanel apartment={data} />
            <DangerZone apartment={data} />
          </div>
        </div>
      </Tabs>
    </>
  )
}
