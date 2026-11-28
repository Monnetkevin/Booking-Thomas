import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/components/ui/Button'
import { registerServiceWorker } from './serviceWorker'

/** Bandeau « nouvelle version » en bas de l'écran, sur le site comme dans le back-office. */
export function UpdateBanner() {
  const { t } = useTranslation()
  const [apply, setApply] = useState<(() => void) | null>(null)

  useEffect(() => {
    if (!import.meta.env.PROD || !('serviceWorker' in navigator)) return
    void registerServiceWorker(navigator.serviceWorker, (update) => setApply(() => update)).catch(() => undefined)
  }, [])

  if (!apply) return null

  return (
    <div
      role="status"
      className="fixed inset-x-4 bottom-4 z-50 mx-auto flex max-w-md items-center justify-between gap-4 rounded-2xl border border-line bg-paper px-5 py-3.5 shadow-[0_10px_30px_rgba(43,38,34,0.18)] print:hidden"
    >
      <p className="text-sm font-semibold text-ink">{t('pwa.updateAvailable')}</p>
      <Button onClick={apply} className="shrink-0 whitespace-nowrap">{t('pwa.update')}</Button>
    </div>
  )
}
