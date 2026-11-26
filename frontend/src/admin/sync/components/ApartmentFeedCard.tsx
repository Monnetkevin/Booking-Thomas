import { useTranslation } from 'react-i18next'
import { Badge } from '@/components/ui/Badge'
import { Section } from '@/components/ui/Section'
import { feedHealth, healthTone } from '../syncStatus'
import { ICAL_PROVIDERS, type IcalFeed, type IcalProvider } from '../types'
import { ExportUrlPanel } from './ExportUrlPanel'
import { FeedState } from './FeedState'
import { ImportUrlForm } from './ImportUrlForm'
import { SyncActions } from './SyncActions'

/** Calendriers d'un logement, un par plateforme (Airbnb, Booking.com). */
export function ApartmentFeedCard({ apartment, feeds }: { apartment: { id: number; name: string }; feeds: IcalFeed[] }) {
  return (
    <Section title={apartment.name} className="scroll-mt-24" id={`calendrier-${apartment.id}`}>
      <div className="divide-y divide-line">
        {ICAL_PROVIDERS.map((provider) => (
          <ProviderFeed
            key={provider}
            apartmentId={apartment.id}
            provider={provider}
            feed={feeds.find((feed) => feed.provider === provider)}
          />
        ))}
      </div>
    </Section>
  )
}

/** Calendrier d'une plateforme : état, import, export, synchronisation et journal. */
function ProviderFeed({ apartmentId, provider, feed }: { apartmentId: number; provider: IcalProvider; feed?: IcalFeed }) {
  const { t } = useTranslation()
  const health = feedHealth(feed)
  const label = t(`admin.sync.providers.${provider}`)

  return (
    <div className="space-y-5 py-5 first:pt-0 last:pb-0">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <h3 className="text-lg font-semibold">{label}</h3>
        <Badge tone={healthTone[health]}>{t(`admin.sync.health.${health}`)}</Badge>
      </div>
      {feed && <FeedState feed={feed} />}
      <div className="grid gap-6 lg:grid-cols-2">
        <div>
          <h4 className="mb-3 font-semibold">{t('admin.sync.import.title', { provider: label })}</h4>
          {/* La clé réinitialise le champ quand l'URL enregistrée change (autre onglet, rafraîchissement). */}
          <ImportUrlForm key={feed?.import_url ?? 'new'} apartmentId={apartmentId} provider={provider} feed={feed} />
        </div>
        <div>
          <h4 className="mb-3 font-semibold">{t('admin.sync.export.title', { provider: label })}</h4>
          {feed ? <ExportUrlPanel feed={feed} /> : <p className="text-sm text-muted">{t('admin.sync.export.missing')}</p>}
        </div>
      </div>
      {feed?.import_url && <SyncActions feed={feed} />}
    </div>
  )
}
