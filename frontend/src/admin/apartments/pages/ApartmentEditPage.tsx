import { useTranslation } from 'react-i18next'
import { useParams } from 'react-router'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Spinner } from '@/components/ui/Spinner'
import { ApiError } from '@/lib/api'
import { formErrorsFromApi, formFromApartment } from '../apartmentForm'
import { AmenityPicker } from '../components/AmenityPicker'
import { ComputedAddressInfo } from '../components/ComputedAddressInfo'
import { ApartmentForm } from '../components/ApartmentForm'
import { DangerZone } from '../components/DangerZone'
import { PhotoManager } from '../components/PhotoManager'
import { PublicationPanel } from '../components/PublicationPanel'
import { useApartment, useUpdateApartment } from '../useApartments'
import { BackLink } from './ApartmentCreatePage'

export function ApartmentEditPage() {
  const { t } = useTranslation()
  const id = Number(useParams().id)
  const apartment = useApartment(id)
  const update = useUpdateApartment(id)

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

  return (
    <>
      <BackLink />
      <PageHeader title={data.name.fr ?? ''} subtitle={t('admin.apartments.editSubtitle')} />

      <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
        <div className="flex min-w-0 flex-col gap-5">
          {update.isSuccess && <Alert tone="success">{t('admin.common.saved')}</Alert>}
          {update.isError && apiError?.status !== 422 && <Alert tone="danger">{t('common.error')}</Alert>}
          {/* Recréé avec les valeurs enregistrées après chaque sauvegarde du formulaire,
              et seulement alors : publier ou ajouter une photo ne perd pas la saisie en cours. */}
          <ApartmentForm
            key={update.data?.updated_at ?? 'initial'}
            initialValues={formFromApartment(data)}
            submitLabel={t('admin.common.save')}
            pending={update.isPending}
            serverErrors={apiError ? formErrorsFromApi(apiError.fieldErrors) : undefined}
            addressInfo={<ComputedAddressInfo apartment={data} />}
            onSubmit={(input) => update.mutate(input)}
          />
          <PhotoManager apartmentId={data.id} photos={data.photos} />
        </div>
        <div className="flex flex-col gap-5">
          <PublicationPanel apartment={data} />
          <AmenityPicker apartmentId={data.id} selectedIds={data.amenity_ids} />
          <DangerZone apartment={data} />
        </div>
      </div>
    </>
  )
}
