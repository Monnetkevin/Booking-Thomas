import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { Container } from '@/components/ui/Container'
import { useLocale } from '@/i18n/useLocale'
import { useApartments } from '@/site/catalog/useCatalog'
import { ApartmentCard } from '@/site/components/ApartmentCard'
import { ApartmentCardSkeleton } from '@/site/components/ApartmentCardSkeleton'
import { Picture } from '@/site/components/Picture'
import { SearchForm } from '@/site/components/SearchForm'
import { Seo } from '@/site/components/Seo'

interface Feature {
  title: string
  text: string
}

function SectionTitle({ title, intro }: { title: string; intro?: string }) {
  return (
    <div className="flex max-w-2xl flex-col gap-2">
      <h2 className="text-3xl leading-tight font-semibold sm:text-4xl">{title}</h2>
      {intro && <p className="text-[17px] leading-relaxed text-muted">{intro}</p>}
    </div>
  )
}

export function HomePage() {
  const { t } = useTranslation()
  const { to } = useLocale()
  const apartments = useApartments()
  const featured = apartments.data?.slice(0, 3) ?? []
  const [first, second] = featured.map((apartment) => apartment.cover)

  const features = t('home.features', { returnObjects: true }) as Feature[]
  const locations = t('home.locations', { returnObjects: true }) as Array<{ value: string; label: string }>
  const steps = t('home.steps', { returnObjects: true }) as Feature[]
  const faq = t('home.faq', { returnObjects: true }) as Array<{ q: string; a: string }>

  return (
    <>
      <Seo title={t('home.title')} description={t('home.metaDescription')} path="/" image={first?.sources.large.webp} />

      {/* Présentation et recherche */}
      <section className="bg-paper">
        <Container className="grid items-center gap-10 py-10 sm:py-16 lg:grid-cols-[1.1fr_1fr]">
          <div className="flex flex-col gap-5">
            <p className="text-xs font-semibold tracking-[0.08em] text-accent uppercase">{t('home.eyebrow')}</p>
            <h1 className="text-4xl leading-[1.08] font-semibold sm:text-5xl lg:text-[56px]">{t('home.title')}</h1>
            <p className="max-w-xl text-[17px] leading-relaxed text-muted">{t('home.intro')}</p>
            <SearchForm layout="stack" submitLabel={t('search.submitHome')} />
          </div>
          <div aria-hidden="true" className="relative h-[340px] sm:h-[460px]">
            <div className="absolute bottom-0 left-0 h-[96%] w-[56%] overflow-hidden rounded-t-full rounded-b-[20px] bg-[#e8d3b8]">
              {first && <Picture photo={first} sizes="(min-width: 1024px) 300px, 56vw" priority alt="" />}
            </div>
            <div className="absolute top-0 right-0 h-[72%] w-[40%] overflow-hidden rounded-t-full rounded-b-[20px] bg-[#f1d3cb]">
              {second && <Picture photo={second} sizes="(min-width: 1024px) 220px, 40vw" alt="" />}
            </div>
          </div>
        </Container>
      </section>

      {/* Logements mis en avant */}
      <section className="py-12 sm:py-16">
        <Container className="flex flex-col gap-8">
          <div className="flex flex-wrap items-end justify-between gap-4">
            <SectionTitle title={t('home.apartmentsTitle')} intro={t('home.apartmentsIntro')} />
            <Link to={to('/logements')} className="font-semibold">
              {t('home.seeAll')}
            </Link>
          </div>
          <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {apartments.isPending
              ? Array.from({ length: 3 }, (_, i) => (
                  <li key={i}>
                    <ApartmentCardSkeleton />
                  </li>
                ))
              : featured.map((apartment) => (
                  <li key={apartment.slug} className="flex">
                    <ApartmentCard apartment={apartment} />
                  </li>
                ))}
          </ul>
        </Container>
      </section>

      {/* Ce que vous trouverez */}
      <section className="bg-sand py-12 sm:py-16">
        <Container className="flex flex-col gap-8">
          <SectionTitle title={t('home.featuresTitle')} />
          <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {features.map((feature) => (
              <li key={feature.title} className="rounded-[var(--radius-card)] bg-white p-6">
                <h3 className="text-xl font-semibold">{feature.title}</h3>
                <p className="mt-2 text-[15px] leading-relaxed text-muted">{feature.text}</p>
              </li>
            ))}
          </ul>
        </Container>
      </section>

      {/* Situation */}
      <section className="py-12 sm:py-16">
        <Container className="flex flex-col gap-8">
          <SectionTitle title={t('home.locationTitle')} intro={t('home.locationIntro')} />
          <dl className="grid gap-5 sm:grid-cols-3">
            {locations.map((item) => (
              <div key={item.label} className="flex flex-col-reverse gap-1 border-l-2 border-accent pl-4">
                <dt className="text-[15px] text-muted">{item.label}</dt>
                <dd className="font-display text-3xl font-semibold">{item.value}</dd>
              </div>
            ))}
          </dl>
        </Container>
      </section>

      {/* Étapes */}
      <section className="bg-sand py-12 sm:py-16">
        <Container className="flex flex-col gap-8">
          <SectionTitle title={t('home.stepsTitle')} />
          <ol className="grid gap-5 sm:grid-cols-3">
            {steps.map((step, index) => (
              <li key={step.title} className="flex gap-4">
                <span aria-hidden="true" className="flex size-10 shrink-0 items-center justify-center rounded-full bg-accent font-bold text-white">
                  {index + 1}
                </span>
                <div>
                  <h3 className="text-xl font-semibold">{step.title}</h3>
                  <p className="mt-1 text-[15px] leading-relaxed text-muted">{step.text}</p>
                </div>
              </li>
            ))}
          </ol>
        </Container>
      </section>

      {/* Votre hôte */}
      <section className="py-12 sm:py-16">
        <Container className="flex max-w-3xl flex-col items-start gap-4">
          <h2 className="text-3xl font-semibold sm:text-4xl">{t('home.hostTitle')}</h2>
          <p className="text-[17px] leading-relaxed">{t('home.hostText')}</p>
          <p className="text-[17px] leading-relaxed text-muted">{t('home.hostQuestion')}</p>
          <Link to={to('/contact')} className={buttonClasses('secondary')}>
            {t('home.hostCta')}
          </Link>
        </Container>
      </section>

      {/* Questions fréquentes */}
      <section className="bg-sand py-12 sm:py-16">
        <Container className="flex max-w-3xl flex-col gap-6">
          <h2 className="text-3xl font-semibold sm:text-4xl">{t('home.faqTitle')}</h2>
          <div className="flex flex-col divide-y divide-line rounded-[var(--radius-card)] bg-white">
            {faq.map((item) => (
              <details key={item.q} className="group p-5">
                <summary className="flex min-h-11 cursor-pointer list-none items-center justify-between gap-4 text-[17px] font-semibold">
                  {item.q}
                  <span aria-hidden="true" className="text-accent transition-transform group-open:rotate-45">+</span>
                </summary>
                <p className="mt-2 text-[15px] leading-relaxed text-muted">{item.a}</p>
              </details>
            ))}
          </div>
        </Container>
      </section>

      {/* Appel à réserver */}
      <section className="py-12 sm:py-16">
        <Container className="flex flex-col items-start gap-4 rounded-[24px] bg-night px-6 py-10 text-white sm:px-12">
          <h2 className="text-3xl font-semibold sm:text-4xl">{t('home.ctaTitle')}</h2>
          <p className="max-w-xl text-[17px] text-night-text">{t('home.ctaText')}</p>
          <Link to={to('/logements')} className={buttonClasses('primary', 'lg')}>
            {t('home.ctaButton')}
          </Link>
        </Container>
      </section>
    </>
  )
}
