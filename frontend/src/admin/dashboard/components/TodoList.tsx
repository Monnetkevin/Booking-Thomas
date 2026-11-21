import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { formatDate } from '@/lib/dates'
import type { TodoItem } from '../dashboardFigures'

/** Situations à traiter, chacune avec un lien vers la page qui la règle. */
export function TodoList({ items }: { items: TodoItem[] }) {
  const { t } = useTranslation()

  if (items.length === 0) return <p className="text-sm text-muted">{t('admin.dashboard.nothingToDo')}</p>

  return (
    <ul className="divide-y divide-line">
      {items.map((item) => (
        <li key={item.key} className="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
          <span aria-hidden="true" className={item.kind === 'messages' ? 'mt-1.5 size-2.5 shrink-0 rounded-full bg-accent' : 'mt-1.5 size-2.5 shrink-0 rounded-full bg-danger'} />
          <div className="min-w-0 flex-1">
            <p className="text-sm font-bold">{t(`admin.dashboard.todo.${item.kind}.title`, { count: item.count ?? 0 })}</p>
            <p className="text-sm text-muted">
              {t(`admin.dashboard.todo.${item.kind}.text`, {
                apartment: item.apartment,
                provider: item.provider,
                count: item.count ?? 0,
                from: item.from ? formatDate(item.from) : '',
                to: item.to ? formatDate(item.to) : '',
              })}
            </p>
          </div>
          <Link to={item.link} className="shrink-0 rounded-lg px-2.5 py-1.5 text-sm font-bold text-accent-strong no-underline hover:bg-accent-soft">
            {t('admin.dashboard.open')}
          </Link>
        </li>
      ))}
    </ul>
  )
}
