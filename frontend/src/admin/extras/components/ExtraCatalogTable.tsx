import { useTranslation } from 'react-i18next'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { formatEuros } from '@/lib/money'
import type { Extra } from '../types'

interface ExtraCatalogTableProps {
  extras: Extra[]
  apartmentCount: number
  onEdit: (extra: Extra) => void
}

/** Catalogue des options : nom et mode de prix, prix, logements concernés, état. */
export function ExtraCatalogTable({ extras, apartmentCount, onEdit }: ExtraCatalogTableProps) {
  const { t } = useTranslation()

  return (
    <div className="overflow-x-auto">
      <table className="w-full min-w-[640px] text-left text-sm">
        <thead>
          <tr className="border-b border-border text-xs font-bold tracking-wide text-muted uppercase">
            <th scope="col" className="py-2 pr-4">{t('admin.extras.columns.option')}</th>
            <th scope="col" className="py-2 pr-4">{t('admin.extras.columns.price')}</th>
            <th scope="col" className="py-2 pr-4">{t('admin.extras.columns.apartments')}</th>
            <th scope="col" className="py-2 pr-4">{t('admin.extras.columns.state')}</th>
            <th scope="col" className="py-2"><span className="sr-only">{t('admin.common.edit')}</span></th>
          </tr>
        </thead>
        <tbody className="divide-y divide-border">
          {extras.map((extra) => (
            <tr key={extra.id}>
              <td className="py-3 pr-4">
                <strong className="block">{extra.name_fr}</strong>
                <span className="text-muted">{t(`admin.extras.pricing.${extra.pricing}`)}</span>
              </td>
              <td className="py-3 pr-4 tabular-nums">{extra.price === 0 ? <Badge tone="accent">{t('admin.extras.free')}</Badge> : formatEuros(extra.price)}</td>
              <td className="py-3 pr-4">
                {extra.apartments.length === 0 ? (
                  <span className="text-muted">{t('admin.extras.noApartment')}</span>
                ) : extra.apartments.length === apartmentCount ? (
                  t('admin.extras.allApartments')
                ) : (
                  <span className="flex flex-wrap gap-1.5">
                    {extra.apartments.map((apartment) => (
                      <Badge key={apartment.id}>{apartment.name}</Badge>
                    ))}
                  </span>
                )}
              </td>
              <td className="py-3 pr-4">
                <Badge tone={extra.is_active ? 'success' : 'neutral'}>{t(extra.is_active ? 'admin.extras.active' : 'admin.extras.inactive')}</Badge>
              </td>
              <td className="py-3 text-right">
                <Button variant="ghost" onClick={() => onEdit(extra)}>
                  {t('admin.common.edit')}
                </Button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
