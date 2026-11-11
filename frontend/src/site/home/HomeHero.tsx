import { useTranslation } from 'react-i18next'
import { Container } from '@/components/ui/Container'
import { NoBreakHyphens } from '@/components/ui/NoBreakHyphens'
import { StatusPill } from '@/components/ui/StatusPill'
import type { PublicPhoto } from '@/site/catalog/types'
import { ArchPhotos } from '@/site/components/ArchPhotos'
import { SearchForm } from '@/site/components/SearchForm'

/** Haut de l'accueil : promesse, grand titre, recherche et deux photos en arche. */
export function HomeHero({ intro, photos }: { intro: string; photos: PublicPhoto[] }) {
  const { t } = useTranslation()
  const [main, secondary] = photos

  return (
    <section>
      <Container className="grid items-center gap-10 pt-10 pb-8 sm:pt-16 xl:grid-cols-[1.45fr_1fr] xl:gap-12 lg:pt-[72px] lg:pb-10">
        <div className="flex min-w-0 flex-col gap-6 sm:gap-7">
          <StatusPill>{t('home.badge')}</StatusPill>
          <h1 className="text-[38px] leading-[1.04] min-[400px]:text-[44px] sm:text-6xl lg:text-[64px] xl:text-[72px]">
            <NoBreakHyphens text={t('home.titleStart')} /> <span className="text-accent">{t('home.titleAccent')}</span>
          </h1>
          <p className="max-w-[520px] text-[17px] leading-relaxed text-muted sm:text-[19px]">{intro}</p>
          <div className="max-w-[640px]">
            <SearchForm />
          </div>
        </div>
        <ArchPhotos main={main} secondary={secondary} />
      </Container>
    </section>
  )
}
