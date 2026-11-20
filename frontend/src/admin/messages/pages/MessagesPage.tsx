import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FilterChips } from '@/components/ui/FilterChips'
import { Spinner } from '@/components/ui/Spinner'
import { MessageCard } from '../components/MessageCard'
import { useMessages } from '../useMessages'

type Filter = 'all' | 'unread'

/** Messages reçus par le formulaire de contact, les plus récents d'abord. */
export function MessagesPage() {
  const { t } = useTranslation()
  const [filter, setFilter] = useState<Filter>('unread')
  const [page, setPage] = useState(1)
  const messages = useMessages(page, filter === 'unread')
  const lastPage = messages.data?.meta.last_page
  if (lastPage !== undefined && !messages.isPlaceholderData && page > Math.max(1, lastPage)) setPage(Math.max(1, lastPage))

  return (
    <>
      <PageHeader title={t('admin.messages.title')} subtitle={t('admin.messages.subtitle')} />
      <div className="mb-5">
        <FilterChips
          label={t('admin.messages.filter')}
          value={filter}
          onChange={(value) => {
            setFilter(value)
            setPage(1)
          }}
          options={[
            { value: 'unread', label: t('admin.messages.unread', { count: messages.data?.meta.unread ?? 0 }) },
            { value: 'all', label: t('admin.messages.all') },
          ]}
        />
      </div>
      {messages.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : messages.isError ? (
        <ErrorState onRetry={() => void messages.refetch()} />
      ) : messages.data.data.length === 0 ? (
        <Alert tone="info">{filter === 'unread' ? t('admin.messages.emptyUnread') : t('admin.messages.empty')}</Alert>
      ) : (
        <div className="space-y-4">
          {messages.data.data.map((message) => (
            <MessageCard key={message.id} message={message} />
          ))}
          {messages.data.meta.last_page > 1 && (
            <nav aria-label={t('admin.messages.pages')} className="flex items-center justify-between gap-3">
              <Button variant="secondary" disabled={page <= 1} onClick={() => setPage((value) => value - 1)}>
                {t('common.previous')}
              </Button>
              <span className="text-sm text-muted">{t('admin.notifications.page', { page, total: messages.data.meta.last_page })}</span>
              <Button variant="secondary" disabled={page >= messages.data.meta.last_page} onClick={() => setPage((value) => value + 1)}>
                {t('common.next')}
              </Button>
            </nav>
          )}
        </div>
      )}
    </>
  )
}
