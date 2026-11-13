import { useTranslation } from 'react-i18next'
import { Badge } from '@/components/ui/Badge'
import { Section } from '@/components/ui/Section'
import { feedHealth, healthTone } from '../syncStatus'
import type { IcalFeed } from '../types'
import { ExportUrlPanel } from './ExportUrlPanel'
import { FeedState } from './FeedState'
import { ImportUrlForm } from './ImportUrlForm'
import { SyncActions } from './SyncActions'

/** Calendrier Airbnb d'un logement : état, import, export, synchronisation et journal. */
export function ApartmentFeedCard({ apartment, feed }: { apartment: { id: number; name: string }; feed?: IcalFeed }) {
  const { t } = useTranslation()
  const health = feedHealth(feed)

  return (
    <Section title={apartment.name} actions={<Badge tone={healthTone[health]}>{t(`admin.sync.health.${health}`)}</Badge>}>
      {feed && <FeedState feed={feed} />}
      <div className="grid gap-6 lg:grid-cols-2">
        <div>
          <h3 className="mb-3 font-semibold">{t('admin.sync.import.title')}</h3>
          {/* La clé réinitialise le champ quand l'URL enregistrée change (autre onglet, rafraîchissement). */}
          <ImportUrlForm key={feed?.import_url ?? 'new'} apartmentId={apartment.id} feed={feed} />
        </div>
        <div>
          <h3 className="mb-3 font-semibold">{t('admin.sync.export.title')}</h3>
          {feed ? <ExportUrlPanel feed={feed} /> : <p className="text-sm text-muted">{t('admin.sync.export.missing')}</p>}
        </div>
      </div>
      {feed?.import_url && <SyncActions feed={feed} />}
    </Section>
  )
}
