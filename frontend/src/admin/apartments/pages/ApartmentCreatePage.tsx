import { useTranslation } from 'react-i18next'
import { Link, useNavigate } from 'react-router'
import { PageHeader } from '@/admin/components/PageHeader'
import { ArrowLeftIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { ApiError } from '@/lib/api'
import { emptyApartmentForm, formErrorsFromApi } from '../apartmentForm'
import { ApartmentForm } from '../components/ApartmentForm'
import { useCreateApartment } from '../useApartments'

export function ApartmentCreatePage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const create = useCreateApartment()
  const apiError = create.error instanceof ApiError ? create.error : null

  return (
    <>
      <BackLink />
      <PageHeader title={t('admin.apartments.newTitle')} subtitle={t('admin.apartments.newSubtitle')} />
      {create.isError && apiError?.status !== 422 && <Alert tone="danger">{t('common.error')}</Alert>}
      <ApartmentForm
        initialValues={emptyApartmentForm}
        submitLabel={t('admin.apartments.create')}
        pending={create.isPending}
        serverErrors={apiError ? formErrorsFromApi(apiError.fieldErrors) : undefined}
        onSubmit={(input) =>
          create.mutate(input, { onSuccess: (apartment) => navigate(`/admin/appartements/${apartment.id}`, { replace: true }) })
        }
      />
    </>
  )
}

export function BackLink() {
  const { t } = useTranslation()

  return (
    <Link to="/admin/appartements" className="mb-3 inline-flex min-h-11 items-center gap-2 text-sm font-semibold no-underline">
      <ArrowLeftIcon width={18} height={18} />
      {t('admin.apartments.backToList')}
    </Link>
  )
}
