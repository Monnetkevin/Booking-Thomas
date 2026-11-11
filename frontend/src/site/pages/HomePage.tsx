import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { UnderlineLink } from '@/components/ui/UnderlineLink'
import { useLocale } from '@/i18n/useLocale'
import { useApartments } from '@/site/catalog/useCatalog'
import type { PublicPhoto } from '@/site/catalog/types'
import { ApartmentMosaic } from '@/site/components/ApartmentMosaic'
import { CtaBanner } from '@/site/components/CtaBanner'
import { FactCards } from '@/site/components/FactCards'
import { FaqList } from '@/site/components/FaqList'
import { FeatureList } from '@/site/components/FeatureList'
import { PageSection } from '@/site/components/PageSection'
import { Seo } from '@/site/components/Seo'
import { StepList } from '@/site/components/StepList'
import { HomeHero } from '@/site/home/HomeHero'
import { HostIntro } from '@/site/home/HostIntro'
import { useHomeContent } from '@/site/home/useHomeContent'
import { faqStructuredData } from '@/site/seo/structuredData'

/** Accueil : assemble les sections ; chacune est un composant réutilisable alimenté par ses props. */
export function HomePage() {
  const { t } = useTranslation()
  const { to } = useLocale()
  const apartments = useApartments()
  const list = apartments.data ?? []
  const content = useHomeContent(list.length)
  const covers = list.map((apartment) => apartment.cover).filter((cover): cover is PublicPhoto => cover !== null)

  return (
    <>
      <Seo
        title={t('home.title')}
        description={t('home.metaDescription')}
        path="/"
        image={covers[0]?.sources.large.webp}
        jsonLd={faqStructuredData(content.faq)}
      />

      <HomeHero intro={content.intro} photos={covers} />

      <PageSection title={t('home.featuresTitle')} tone="cream">
        <FeatureList items={content.features} />
      </PageSection>

      <PageSection
        title={
          <>
            {content.collectionTitle[0]}
            <br />
            {content.collectionTitle[1]}
          </>
        }
        action={<UnderlineLink to={to('/logements')}>{t('home.seeAll')}</UnderlineLink>}
      >
        <ApartmentMosaic
          apartments={list}
          loading={apartments.isPending}
          error={apartments.isError}
          onRetry={() => void apartments.refetch()}
        />
      </PageSection>

      <PageSection title={t('home.locationTitle')} hideTitle>
        <FactCards items={content.facts} />
      </PageSection>

      <PageSection title={t('home.stepsTitle')} tone="sand">
        <StepList items={content.steps} />
      </PageSection>

      <HostIntro />

      <PageSection title={t('home.faqTitle')} tone="sand" narrow>
        <FaqList items={content.faq} />
        <p className="text-[15px] text-muted">
          {t('home.faqIntro')}{' '}
          <Link to={to('/contact')} className="font-semibold">
            {t('home.hostCta')}
          </Link>
        </p>
      </PageSection>

      <CtaBanner
        title={t('home.ctaTitle')}
        text={t('home.ctaText')}
        action={{ to: to('/logements'), label: t('home.ctaButton') }}
      />
    </>
  )
}
