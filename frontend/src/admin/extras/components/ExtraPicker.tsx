import { useTranslation } from 'react-i18next'
import { Checkbox, TextField } from '@/components/ui/Field'
import { formatEuros } from '@/lib/money'
import type { Extra } from '../types'

interface ExtraPickerProps {
  /** Options actives proposées dans le logement choisi. */
  extras: Extra[]
  /** Identifiant → quantité des options choisies. */
  value: Record<number, number>
  onChange: (value: Record<number, number>) => void
}

/** Choix des options d'une réservation saisie par l'hôte ; le prix est calculé par l'API. */
export function ExtraPicker({ extras, value, onChange }: ExtraPickerProps) {
  const { t } = useTranslation()

  const toggle = (extra: Extra, checked: boolean) => {
    const { [extra.id]: _removed, ...rest } = value
    onChange(checked ? { ...rest, [extra.id]: 1 } : rest)
  }
  const setQuantity = (extra: Extra, raw: string) => {
    const quantity = Math.min(extra.max_quantity, Math.max(1, Number.parseInt(raw, 10) || 1))
    onChange({ ...value, [extra.id]: quantity })
  }

  return (
    <fieldset className="flex flex-col gap-1">
      <legend className="mb-1 text-sm font-semibold">{t('admin.manual.extras')}</legend>
      {extras.map((extra) => (
        <div key={extra.id} className="flex flex-wrap items-center gap-x-4">
          <Checkbox
            label={`${extra.name_fr} · ${extra.price === 0 ? t('admin.extras.free') : formatEuros(extra.price)} ${t(`admin.extras.pricing.${extra.pricing}`)}`}
            checked={extra.id in value}
            onChange={(event) => toggle(extra, event.target.checked)}
          />
          {extra.pricing === 'per_unit' && extra.id in value && (
            <TextField
              label={t('admin.manual.extraQuantity', { max: extra.max_quantity })}
              className="w-40"
              inputMode="numeric"
              value={String(value[extra.id])}
              onChange={(event) => setQuantity(extra, event.target.value)}
            />
          )}
        </div>
      ))}
    </fieldset>
  )
}
