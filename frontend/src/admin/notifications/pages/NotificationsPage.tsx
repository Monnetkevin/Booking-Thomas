import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { Card } from '@/components/ui/Card'
import { FilterChips } from '@/components/ui/FilterChips'
import { Pager } from '@/components/ui/Pager'
import { Spinner } from '@/components/ui/Spinner'
import { NotificationItem } from '../components/NotificationItem'
import { useOpenNotification } from '../components/useOpenNotification'
import { useMarkAllRead, useNotificationList, useUnreadCount } from '../useNotifications'

type Filter = 'all' | 'unread'

/** Toutes les notifications du compte, filtre « non lues », pages de 20. */
export function NotificationsPage() {
  const { t } = useTranslation()
  const [filter, setFilter] = useState<Filter>('all')
  const [page, setPage] = useState(1)
  const list = useNotificationList(page, filter === 'unread')
  const unread = useUnreadCount()
  const markAllRead = useMarkAllRead()
  const openNotification = useOpenNotification()

  // Des notifications lues depuis le filtre « non lues » peuvent vider la page courante :
  // on revient alors à la dernière page qui existe encore.
  const lastPage = list.data?.meta.last_page
  if (lastPage !== undefined && !list.isPlaceholderData && page > Math.max(1, lastPage)) setPage(Math.max(1, lastPage))

  const changeFilter = (value: Filter) => {
    setFilter(value)
    setPage(1)
  }

  return (
    <>
      <PageHeader
        title={t('admin.notifications.title')}
        subtitle={t('admin.notifications.subtitle')}
        actions={
          <>
            <Button variant="secondary" onClick={() => markAllRead.mutate()} disabled={!unread.data} loading={markAllRead.isPending}>
              {t('admin.notifications.markAllRead')}
            </Button>
            <Link to="/admin/notifications/preferences" className={buttonClasses('ghost')}>
              {t('admin.notifications.preferences.link')}
            </Link>
          </>
        }
      />
      <div className="mb-5">
        <FilterChips
          label={t('admin.notifications.filter')}
          value={filter}
          onChange={changeFilter}
          options={[
            { value: 'all', label: t('admin.notifications.all') },
            { value: 'unread', label: t('admin.notifications.unread', { count: unread.data ?? 0 }) },
          ]}
        />
      </div>
      {list.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : list.isError ? (
        <ErrorState onRetry={() => void list.refetch()} />
      ) : list.data.data.length === 0 ? (
        <Alert tone="info">{filter === 'unread' ? t('admin.notifications.emptyUnread') : t('admin.notifications.empty')}</Alert>
      ) : (
        <Card className="p-2 sm:p-3">
          <ul className="divide-y divide-border">
            {list.data.data.map((notification) => (
              <li key={notification.id}>
                <NotificationItem notification={notification} onOpen={openNotification} />
              </li>
            ))}
          </ul>
          <Pager
            className="px-3 pt-3"
            page={page}
            lastPage={list.data.meta.last_page}
            onChange={setPage}
            label={t('admin.notifications.pages')}
            position={t('admin.notifications.page', { page: list.data.meta.current_page, total: list.data.meta.last_page })}
          />
        </Card>
      )}
    </>
  )
}
