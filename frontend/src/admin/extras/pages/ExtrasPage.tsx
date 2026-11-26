import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useApartmentList } from '@/admin/apartments/useApartments'
import { KpiCard } from '@/admin/components/KpiCard'
import { PageHeader } from '@/admin/components/PageHeader'
import { percentDelta } from '@/admin/components/delta'
import { ErrorState } from '@/components/feedback/ErrorState'
import { BarChartIcon, CalendarIcon, TagIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Section } from '@/components/ui/Section'
import { Spinner } from '@/components/ui/Spinner'
import { formatEuros } from '@/lib/money'
import { ExtraCatalogTable } from '../components/ExtraCatalogTable'
import { ExtraFormDialog } from '../components/ExtraFormDialog'
import { ToPrepareList } from '../components/ToPrepareList'
import { emptyExtraForm, formFromExtra } from '../extraForm'
import type { Extra } from '../types'
import { useExtraCatalog, useExtrasToPrepare, useSaveExtra } from '../useExtras'

/** Options proposées par logement : chiffres du mois, catalogue, options des prochaines arrivées. */
export function ExtrasPage() {
  const { t } = useTranslation()
  const catalog = useExtraCatalog()
  const toPrepare = useExtrasToPrepare()
  const apartments = useApartmentList()
  const save = useSaveExtra()
  // null : fermé ; « new » : nouvelle option ; sinon l'option modifiée.
  const [editing, setEditing] = useState<Extra | 'new' | null>(null)
  const apartmentOptions = (apartments.data ?? []).map((apartment) => ({ id: apartment.id, name: apartment.name }))

  const close = () => {
    setEditing(null)
    save.reset()
  }

  return (
    <>
      <PageHeader
        title={t('admin.extras.title')}
        subtitle={t('admin.extras.subtitle')}
        actions={<Button onClick={() => setEditing('new')}>{t('admin.extras.new')}</Button>}
      />
      {catalog.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : catalog.isError ? (
        <ErrorState onRetry={() => void catalog.refetch()} />
      ) : (
        <div className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-3">
            <KpiCard
              icon={<TagIcon />}
              label={t('admin.extras.kpi.active')}
              value={catalog.data.meta.figures.active}
              detail={t('admin.extras.kpi.activeDetail', { count: catalog.data.meta.figures.total })}
              delta={null}
            />
            <KpiCard
              icon={<CalendarIcon />}
              label={t('admin.extras.kpi.chosen')}
              value={catalog.data.meta.figures.with_extras}
              detail={t('admin.extras.kpi.chosenDetail', { count: catalog.data.meta.figures.bookings })}
              delta={null}
            />
            <KpiCard
              icon={<BarChartIcon />}
              label={t('admin.extras.kpi.revenue')}
              value={formatEuros(catalog.data.meta.figures.revenue)}
              detail={t('admin.extras.kpi.revenueDetail')}
              delta={percentDelta(catalog.data.meta.figures.revenue, catalog.data.meta.figures.previous_revenue)}
            />
          </div>
          <div className="space-y-4">
            <Section title={t('admin.extras.catalog')}>
              {catalog.data.data.length === 0 ? (
                <Alert tone="info">{t('admin.extras.empty')}</Alert>
              ) : (
                <ExtraCatalogTable extras={catalog.data.data} apartmentCount={apartmentOptions.length} onEdit={setEditing} />
              )}
            </Section>
            <Section title={t('admin.extras.toPrepare')} description={t('admin.extras.toPrepareHint')}>
              {toPrepare.isError ? <ErrorState onRetry={() => void toPrepare.refetch()} /> : toPrepare.data ? <ToPrepareList items={toPrepare.data} /> : <Spinner label={t('common.loading')} />}
            </Section>
          </div>
        </div>
      )}
      {editing !== null && (
        <ExtraFormDialog
          title={t(editing === 'new' ? 'admin.extras.new' : 'admin.extras.edit')}
          initialValues={editing === 'new' ? { ...emptyExtraForm, apartmentIds: apartmentOptions.map((apartment) => apartment.id) } : formFromExtra(editing)}
          apartments={apartmentOptions}
          pending={save.isPending}
          failed={save.isError}
          onSubmit={(input) => save.mutate({ id: editing === 'new' ? null : editing.id, input }, { onSuccess: close })}
          onClose={close}
        />
      )}
    </>
  )
}
