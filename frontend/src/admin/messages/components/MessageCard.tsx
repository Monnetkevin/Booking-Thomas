import { useTranslation } from 'react-i18next'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { Card } from '@/components/ui/Card'
import { formatDateTime } from '@/lib/dates'
import type { ContactMessage } from '../types'
import { useSetMessageRead } from '../useMessages'

/** Un message : expéditeur, logement, texte, réponse par e-mail et suivi lu / non lu. */
export function MessageCard({ message }: { message: ContactMessage }) {
  const { t } = useTranslation()
  const setRead = useSetMessageRead()
  const subject = t('admin.messages.replySubject', { apartment: message.apartment?.name ?? t('admin.messages.yourMessage') })

  return (
    <Card className={message.read ? undefined : 'border-accent'}>
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div className="min-w-0">
          <p className="flex flex-wrap items-center gap-2 font-semibold">
            {message.name}
            {!message.read && <Badge tone="accent">{t('admin.messages.new')}</Badge>}
            {message.locale === 'en' && <Badge>EN</Badge>}
          </p>
          <p className="text-sm break-all text-muted">
            {message.email}
            {message.phone && ` · ${message.phone}`}
          </p>
          <p className="mt-0.5 text-sm text-muted">
            {formatDateTime(message.created_at)}
            {message.apartment && ` · ${message.apartment.name}`}
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <a
            href={`mailto:${message.email}?subject=${encodeURIComponent(subject)}`}
            className={buttonClasses('secondary')}
            onClick={() => !message.read && setRead.mutate({ id: message.id, read: true })}
          >
            {t('admin.messages.reply')}
          </a>
          <Button variant="ghost" loading={setRead.isPending} onClick={() => setRead.mutate({ id: message.id, read: !message.read })}>
            {message.read ? t('admin.messages.markUnread') : t('admin.messages.markRead')}
          </Button>
        </div>
      </div>
      <p className="mt-4 text-[15px] leading-relaxed whitespace-pre-line">{message.message}</p>
    </Card>
  )
}
