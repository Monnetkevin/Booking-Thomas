import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useSearchParams } from 'react-router'
import { useApartmentList } from '@/admin/apartments/useApartments'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { PlusIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Checkbox } from '@/components/ui/Field'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { FilterChips } from '@/components/ui/FilterChips'
import { Section } from '@/components/ui/Section'
import { Spinner } from '@/components/ui/Spinner'
import { ApiError } from '@/lib/api'
import { RatePeriodForm } from '../components/RatePeriodForm'
import { QuotePreviewPanel } from '../components/QuotePreviewPanel'
import { RatePeriodList } from '../components/RatePeriodList'
import { emptyRateForm, formFromPeriod, rateErrorsFromApi } from '../rateForm'
import type { RatePeriod } from '../types'
import { useDeleteRatePeriod, useRatePeriods, useSaveRatePeriod } from '../useRates'

/** null : formulaire fermé ; 'new' : nouvelle période ; sinon la période modifiée. */
type Editing = null | 'new' | RatePeriod

export function RatesPage() {
  const { t } = useTranslation()
  const apartments = useApartmentList()
  const [params, setParams] = useSearchParams()

  if (apartments.isPending) return <Spinner label={t('common.loading')} />
  if (apartments.isError) return <ErrorState onRetry={() => void apartments.refetch()} />

  const list = apartments.data
  const requested = Number(params.get('logement'))
  const selected = list.find((apartment) => apartment.id === requested) ?? list[0]

  return (
    <>
      <PageHeader title={t('admin.rates.title')} subtitle={t('admin.rates.subtitle')} />
      {!selected ? (
        <Alert tone="info">
          {t('admin.rates.noApartment')} <Link to="/admin/appartements/nouveau">{t('admin.apartments.add')}</Link>
        </Alert>
      ) : (
        <>
          <div className="mb-5">
            <FilterChips<string>
              label={t('admin.rates.chooseApartment')}
              value={String(selected.id)}
              onChange={(id) => setParams({ logement: id }, { replace: true })}
              options={list.map((apartment) => ({ value: String(apartment.id), label: apartment.name }))}
            />
          </div>
          {/* La clé repart d'un état propre (formulaire fermé) à chaque changement de logement. */}
          <ApartmentRates key={selected.id} apartmentId={selected.id} name={selected.name} minStay={selected.min_stay} />
        </>
      )}
    </>
  )
}

function ApartmentRates({ apartmentId, name, minStay }: { apartmentId: number; name: string; minStay: number }) {
  const { t } = useTranslation()
  const [includePast, setIncludePast] = useState(false)
  const [editing, setEditing] = useState<Editing>(null)
  const [toDelete, setToDelete] = useState<RatePeriod | null>(null)
  const periods = useRatePeriods(apartmentId, includePast)
  const save = useSaveRatePeriod(apartmentId)
  const remove = useDeleteRatePeriod(apartmentId)

  const open = (next: Editing) => {
    save.reset()
    setEditing(next)
  }

  const saveError = save.error instanceof ApiError && save.error.status === 422 ? save.error : null
  const editingPeriod = editing !== null && editing !== 'new' ? editing : null

  return (
    <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(340px,1fr)]">
      <Section
        title={t('admin.rates.tableTitle', { name })}
        actions={
          <Button onClick={() => open('new')}>
            <PlusIcon />
            {t('admin.rates.add')}
          </Button>
        }
      >
        <Checkbox label={t('admin.rates.showPast')} checked={includePast} onChange={(event) => setIncludePast(event.target.checked)} />
        {periods.isPending ? (
          <Spinner label={t('common.loading')} />
        ) : periods.isError ? (
          <ErrorState onRetry={() => void periods.refetch()} />
        ) : periods.data.length === 0 ? (
          <p className="py-4 text-muted">{t('admin.rates.empty')}</p>
        ) : (
          <RatePeriodList
            periods={periods.data}
            apartmentMinStay={minStay}
            selectedId={editingPeriod?.id ?? null}
            onEdit={(period) => open(period)}
            onDelete={setToDelete}
          />
        )}
        {remove.isError && <Alert tone="danger">{t('common.error')}</Alert>}
        <p className="mt-4 text-sm text-muted">{t('admin.rates.note')}</p>
      </Section>

      {editing === null ? (
        <QuotePreviewPanel apartmentId={apartmentId} />
      ) : (
        <Section title={editingPeriod ? t('admin.rates.form.editTitle') : t('admin.rates.form.newTitle')}>
          {save.isError && !saveError && <Alert tone="danger">{t('common.error')}</Alert>}
          <RatePeriodForm
            key={editingPeriod?.id ?? 'new'}
            initialValues={editingPeriod ? formFromPeriod(editingPeriod) : emptyRateForm}
            apartmentMinStay={minStay}
            pending={save.isPending}
            serverErrors={saveError ? rateErrorsFromApi(saveError.fieldErrors) : undefined}
            onCancel={() => setEditing(null)}
            onSubmit={(input) => save.mutate({ id: editingPeriod?.id ?? null, input }, { onSuccess: () => setEditing(null) })}
          />
        </Section>
      )}

      <ConfirmDialog
        open={toDelete !== null}
        title={t('admin.rates.deleteConfirmTitle')}
        confirmLabel={t('admin.common.delete')}
        cancelLabel={t('admin.common.cancel')}
        tone="danger"
        pending={remove.isPending}
        onCancel={() => setToDelete(null)}
        onConfirm={() =>
          toDelete &&
          remove.mutate(toDelete.id, {
            onSuccess: () => editingPeriod?.id === toDelete.id && setEditing(null),
            onSettled: () => setToDelete(null),
          })
        }
      >
        {t('admin.rates.deleteConfirm')}
      </ConfirmDialog>
    </div>
  )
}
