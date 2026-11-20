import { useTranslation } from 'react-i18next'
import { TrashIcon } from '@/components/icons'
import { Button } from '@/components/ui/Button'
import { TextField } from '@/components/ui/Field'
import type { SettingsFormErrors, SettingsFormValues } from '../settingsForm'
import type { PolicyKey } from '../types'

const MAX_TIERS = 5

type Tiers = SettingsFormValues['policies'][PolicyKey]

/** Paliers d'un barème : « jusqu'à N jours avant l'arrivée, X % remboursé ». */
export function PolicyEditor({
  policy,
  tiers,
  errors,
  onChange,
}: {
  policy: PolicyKey
  tiers: Tiers
  errors: SettingsFormErrors
  onChange: (tiers: Tiers) => void
}) {
  const { t } = useTranslation()
  const update = (index: number, field: 'daysBefore' | 'refundPercent', value: string) =>
    onChange(tiers.map((tier, i) => (i === index ? { ...tier, [field]: value } : tier)))

  return (
    <fieldset className="space-y-3">
      <legend className="mb-2 font-semibold">{t(`admin.settings.policies.${policy}`)}</legend>
      {tiers.map((tier, index) => {
        const error = (field: string) => {
          const code = errors[`${policy}.${index}.${field}`]
          return code ? t(`admin.settings.errors.${code}`) : undefined
        }
        return (
          <div key={index} className="flex flex-wrap items-end gap-3">
            <TextField
              className="w-40"
              label={t('admin.settings.daysBefore')}
              inputMode="numeric"
              suffix={t('admin.settings.daysSuffix')}
              value={tier.daysBefore}
              error={error('daysBefore')}
              onChange={(event) => update(index, 'daysBefore', event.target.value)}
            />
            <TextField
              className="w-36"
              label={t('admin.settings.refundPercent')}
              inputMode="numeric"
              suffix="%"
              value={tier.refundPercent}
              error={error('refundPercent')}
              onChange={(event) => update(index, 'refundPercent', event.target.value)}
            />
            {tiers.length > 1 && (
              <Button
                variant="ghost"
                aria-label={t('admin.settings.removeTier')}
                title={t('admin.settings.removeTier')}
                onClick={() => onChange(tiers.filter((_, i) => i !== index))}
              >
                <TrashIcon />
              </Button>
            )}
          </div>
        )
      })}
      {tiers.length < MAX_TIERS && (
        <Button variant="secondary" onClick={() => onChange([...tiers, { daysBefore: '', refundPercent: '' }])}>
          {t('admin.settings.addTier')}
        </Button>
      )}
    </fieldset>
  )
}
