import { useTranslation } from 'react-i18next'
import { Spinner } from '@/components/ui/Spinner'

export function FullPageSpinner() {
  const { t } = useTranslation()

  return (
    <div className="grid min-h-dvh place-items-center bg-sand text-muted">
      <Spinner label={t('common.loading')} />
    </div>
  )
}
