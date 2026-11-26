import { useTranslation } from 'react-i18next'
import { Button } from './Button'

interface PagerProps {
  page: number
  lastPage: number
  onChange: (page: number) => void
  /** Nom de la navigation, ex. « Pages des notifications ». */
  label: string
  /** Position affichée, ex. « Page 2 sur 5 ». */
  position: string
  className?: string
}

/** Page précédente / suivante d'une liste paginée par l'API ; rien s'il n'y a qu'une page. */
export function Pager({ page, lastPage, onChange, label, position, className = '' }: PagerProps) {
  const { t } = useTranslation()
  if (lastPage <= 1) return null

  return (
    <nav aria-label={label} className={`flex items-center justify-between gap-3 ${className}`}>
      <Button variant="secondary" disabled={page <= 1} onClick={() => onChange(page - 1)}>
        {t('common.previous')}
      </Button>
      <span className="text-sm text-muted">{position}</span>
      <Button variant="secondary" disabled={page >= lastPage} onClick={() => onChange(page + 1)}>
        {t('common.next')}
      </Button>
    </nav>
  )
}
