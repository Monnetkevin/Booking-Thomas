import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Section } from '@/components/ui/Section'
import { Spinner } from '@/components/ui/Spinner'
import { cn } from '@/lib/cn'
import { useAmenityCatalog, useSyncAmenities } from '../useApartments'

/** Cases à cocher des équipements, enregistrées ensemble. */
export function AmenityPicker({ apartmentId, selectedIds }: { apartmentId: number; selectedIds: number[] }) {
  const { t } = useTranslation()
  const catalog = useAmenityCatalog()
  const sync = useSyncAmenities(apartmentId)
  const [selected, setSelected] = useState(() => new Set(selectedIds))

  const toggle = (id: number) =>
    setSelected((current) => {
      const next = new Set(current)
      if (next.has(id)) next.delete(id)
      else next.add(id)
      return next
    })

  const dirty = selected.size !== selectedIds.length || selectedIds.some((id) => !selected.has(id))

  return (
    <Section title={t('admin.apartments.amenities.title')} description={t('admin.apartments.amenities.description')}>
      {catalog.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : catalog.isError ? (
        <Alert tone="danger">{t('common.error')}</Alert>
      ) : (
        <fieldset>
          <legend className="sr-only">{t('admin.apartments.amenities.title')}</legend>
          <div className="flex flex-wrap gap-2">
            {catalog.data.map((amenity) => {
              const checked = selected.has(amenity.id)
              return (
                <label
                  key={amenity.id}
                  className={cn(
                    'flex min-h-11 cursor-pointer items-center gap-2 rounded-full border px-3.5 text-sm font-medium has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-accent',
                    checked ? 'border-accent bg-accent-soft text-accent-strong' : 'border-border bg-white',
                  )}
                >
                  <input type="checkbox" className="size-4 accent-accent" checked={checked} onChange={() => toggle(amenity.id)} />
                  {amenity.label.fr}
                </label>
              )
            })}
          </div>
        </fieldset>
      )}
      <div className="mt-4 flex flex-wrap items-center justify-end gap-3">
        {sync.isSuccess && !dirty && <span role="status" className="text-sm text-success">{t('admin.apartments.amenities.saved')}</span>}
        {sync.isError && <span role="alert" className="text-sm text-danger">{t('common.error')}</span>}
        <Button variant="secondary" disabled={!dirty} loading={sync.isPending} onClick={() => sync.mutate([...selected])}>
          {t('admin.apartments.amenities.save')}
        </Button>
      </div>
    </Section>
  )
}
