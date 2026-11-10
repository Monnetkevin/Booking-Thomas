import { useTranslation } from 'react-i18next'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'

/** Erreur de chargement avec bouton « Réessayer ». */
export function ErrorState({ onRetry, fullPage = false }: { onRetry: () => void; fullPage?: boolean }) {
  const { t } = useTranslation()

  const content = (
    <div className="flex flex-col items-start gap-3">
      <Alert tone="danger">{t('common.error')}</Alert>
      <Button variant="secondary" onClick={onRetry}>
        {t('common.retry')}
      </Button>
    </div>
  )

  return fullPage ? <div className="grid min-h-dvh place-items-center bg-sand px-4">{content}</div> : content
}
