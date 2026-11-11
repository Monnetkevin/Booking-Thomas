import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import type { ComponentType, SVGProps } from 'react'
import { LockIcon, ParkingIcon, PawIcon, PlusIcon, WifiIcon } from '@/components/icons'
import { Container } from '@/components/ui/Container'
import { ErrorState } from '@/components/feedback/ErrorState'
import { useLocale } from '@/i18n/useLocale'
import { cn } from '@/lib/cn'
import { useApartments } from '@/site/catalog/useCatalog'
import type { ApartmentSummary } from '@/site/catalog/types'
import { Picture } from '@/site/components/Picture'
import { SearchForm } from '@/site/components/SearchForm'
import { Seo } from '@/site/components/Seo'

/** Grands titres : Fraunces (la police et la graisse viennent de la classe .vitrine). */
const heading = 'font-display font-semibold'

interface Feature {
  title: string
  text: string
}

/** Icône de chaque atout, choisie par la clé « icon » des traductions. */
const featureIcons: Record<string, ComponentType<SVGProps<SVGSVGElement>>> = {
  parking: ParkingIcon,
  wifi: WifiIcon,
  key: LockIcon,
  pets: PawIcon,
}

/** Bouton sombre arrondi de la maquette v2. */
const darkButton =
  'inline-flex min-h-12 items-center justify-center rounded-full bg-ink px-6 text-[15px] font-bold text-white no-underline hover:bg-night-soft hover:text-white'

/** Fonds de secours des tuiles, tant qu'une photo n'est pas prête. */
const tileColors = ['bg-[#e8d3b8]', 'bg-[#ebdccb]', 'bg-[#f1d3cb]', 'bg-[#d9cdbf]', 'bg-[#e9d6da]']

function ArrowIcon() {
  return (
    <svg aria-hidden="true" width="16" height="16" viewBox="0 0 16 16">
      <path d="M4 12L12 4M6 4h6v6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  )
}

/** Tuile de la mosaïque « N adresses, N ambiances » : photo pleine, cartouche du logement en bas. */
function ApartmentTile({ apartment, large, index }: { apartment: ApartmentSummary; large: boolean; index: number }) {
  const { t } = useTranslation()
  const { to } = useLocale()
  const meta = [
    apartment.surface_m2 ? t('listing.surface', { value: apartment.surface_m2 }) : null,
    t('search.guests', { count: apartment.capacity }),
  ].filter(Boolean)

  return (
    <article
      className={cn(
        'group relative isolate flex flex-col justify-end overflow-hidden rounded-[28px]',
        tileColors[index % tileColors.length],
        large ? 'min-h-[360px] p-5 sm:col-span-2 sm:p-7 lg:row-span-2 lg:min-h-0' : 'min-h-[260px] p-4 sm:p-5 lg:min-h-0',
      )}
    >
      {apartment.cover && (
        <div className="absolute inset-0 -z-10 transition-transform duration-500 group-hover:scale-[1.03]">
          <Picture
            photo={apartment.cover}
            sizes={large ? '(min-width: 1024px) 600px, 100vw' : '(min-width: 1024px) 300px, (min-width: 640px) 50vw, 100vw'}
            priority={large}
            alt=""
          />
        </div>
      )}
      <div className="flex items-end justify-between gap-3 rounded-[18px] bg-[rgba(255,253,249,0.92)] px-4 py-3.5 sm:px-5">
        <div className="flex min-w-0 flex-col gap-0.5">
          <h3 className={cn(heading, 'tracking-[-0.01em]', large ? 'text-[22px]' : 'text-[17px]')}>
            {/* Lien étiré sur toute la tuile. */}
            <Link to={to(`/logements/${apartment.slug}`)} className="text-ink no-underline after:absolute after:inset-0 hover:text-ink">
              {apartment.name}
            </Link>
          </h3>
          {apartment.tagline && <p className="text-[13px] text-muted">{apartment.tagline}</p>}
          <p className="text-[13px] text-muted">{meta.join(' · ')}</p>
        </div>
        <span aria-hidden="true" className="flex size-10 shrink-0 items-center justify-center rounded-full bg-ink text-white">
          <ArrowIcon />
        </span>
      </div>
    </article>
  )
}

export function HomePage() {
  const { t } = useTranslation()
  const { to } = useLocale()
  const apartments = useApartments()
  const list = apartments.data ?? []
  const featured = list.slice(0, 5)
  const [first, second] = list.map((apartment) => apartment.cover).filter((cover) => cover !== null)

  // Nombre de logements en toutes lettres (« Cinq adresses ») jusqu'à douze, en chiffres au-delà.
  const numbers = t('home.numbers', { returnObjects: true }) as string[]
  const count = list.length
  const word = numbers[count] ?? String(count)
  const capitalized = word.charAt(0).toUpperCase() + word.slice(1)
  const intro = count > 0 ? t('home.intro', { count, word: capitalized }) : t('home.introFallback')
  const [titleLine1, titleLine2] = (
    count > 0 ? t('home.collectionTitle', { count, word: capitalized, wordLower: word }) : t('home.collectionFallback')
  ).split('|')

  const features = t('home.features', { returnObjects: true }) as Array<Feature & { icon: string }>
  const steps = t('home.steps', { returnObjects: true }) as Feature[]
  const locations = t('home.locations', { returnObjects: true }) as Array<{ value: string; label: string }>
  const faq = t('home.faq', { returnObjects: true }) as Array<{ q: string; a: string }>

  return (
    <>
      <Seo
        title={t('home.title')}
        description={t('home.metaDescription')}
        path="/"
        image={first?.sources.large.webp}
        jsonLd={{
          '@context': 'https://schema.org',
          '@type': 'FAQPage',
          mainEntity: faq.map((item) => ({ '@type': 'Question', name: item.q, acceptedAnswer: { '@type': 'Answer', text: item.a } })),
        }}
      />

      {/* Présentation et recherche */}
      <section>
        <Container className="grid items-center gap-10 pt-10 pb-8 sm:pt-16 lg:grid-cols-[1.05fr_1fr] lg:gap-12 lg:pt-[72px] lg:pb-10">
          <div className="flex min-w-0 flex-col gap-6 sm:gap-7">
            <p className="flex items-center gap-2 self-start rounded-full border border-line bg-white px-3.5 py-2 text-[13px] font-bold">
              <span aria-hidden="true" className="size-2 rounded-full bg-[#2e9e5b]" />
              {t('home.badge')}
            </p>
            <h1 className={cn(heading, 'text-[38px] leading-[1.04] min-[400px]:text-[44px] sm:text-6xl lg:text-[64px] xl:text-[72px]')}>
              {/* « pied-à-terre » ne se coupe pas au trait d'union. */}
              {t('home.titleStart')
                .split(' ')
                .map((part, i) => (
                  <span key={i} className={part.includes('-') ? 'whitespace-nowrap' : undefined}>
                    {i > 0 && ' '}
                    {part}
                  </span>
                ))}{' '}
              <span className="text-accent">{t('home.titleAccent')}</span>
            </h1>
            <p className="max-w-[520px] text-[17px] leading-relaxed text-muted sm:text-[19px]">{intro}</p>
            <div className="max-w-[640px]">
              <SearchForm layout="pill" />
            </div>
          </div>

          <div aria-hidden="true" className="relative mx-auto h-[360px] w-full max-w-[460px] sm:h-[480px] lg:h-[540px] lg:max-w-none">
            <div className="absolute inset-y-0 right-0 left-10 overflow-hidden rounded-t-full rounded-b-[32px] bg-[#e8d3b8] sm:left-[60px] sm:rounded-b-[40px]">
              {first && <Picture photo={first} sizes="(min-width: 1024px) 560px, 90vw" priority alt="" />}
            </div>
            <div className="absolute bottom-6 left-0 h-[190px] w-[140px] overflow-hidden rounded-t-full rounded-b-[24px] border-[6px] border-paper bg-[#f1d3cb] sm:bottom-10 sm:h-[260px] sm:w-[190px] sm:rounded-b-[28px]">
              {second && <Picture photo={second} sizes="190px" alt="" />}
            </div>
          </div>
        </Container>
      </section>

      {/* Atouts (v1) : icônes sur fond crème */}
      <section className="mt-6 bg-cream py-14 sm:py-[72px]">
        <Container className="flex flex-col gap-8">
          <h2 className={cn(heading, 'text-3xl leading-tight sm:text-4xl')}>{t('home.featuresTitle')}</h2>
          <ul className="grid gap-5 sm:grid-cols-2 sm:gap-8 lg:grid-cols-4">
            {features.map((feature) => {
              const FeatureIcon = featureIcons[feature.icon]
              return (
                <li key={feature.title} className="flex items-start gap-4 sm:flex-col sm:gap-3">
                  {FeatureIcon && (
                    <span className="flex size-12 shrink-0 items-center justify-center rounded-full bg-blush text-accent-strong">
                      <FeatureIcon width={24} height={24} />
                    </span>
                  )}
                  <div className="flex flex-col gap-1 sm:gap-2">
                    <h3 className="text-[17px] sm:text-lg">{feature.title}</h3>
                    <p className="text-[15px] leading-relaxed text-muted">{feature.text}</p>
                  </div>
                </li>
              )
            })}
          </ul>
        </Container>
      </section>

      {/* Mosaïque des logements */}
      <section>
        <Container className="flex flex-col gap-8 py-16 lg:py-[72px]">
          <div className="flex flex-wrap items-end justify-between gap-4">
            <h2 className={cn(heading, 'text-[34px] leading-[1.05] sm:text-[44px]')}>
              {titleLine1}
              <br />
              {titleLine2}
            </h2>
            <Link
              to={to('/logements')}
              className="border-b-2 border-ink pb-0.5 text-[15px] font-bold text-ink no-underline hover:border-accent-text hover:text-accent-text"
            >
              {t('home.seeAll')} →
            </Link>
          </div>

          {apartments.isError ? (
            <ErrorState onRetry={() => void apartments.refetch()} />
          ) : (
            <div className="grid gap-4 sm:grid-cols-2 lg:auto-rows-[300px] lg:grid-cols-4">
              {apartments.isPending
                ? Array.from({ length: 5 }, (_, i) => (
                    <div
                      key={i}
                      className={cn('animate-pulse rounded-[28px] bg-cream', i === 0 ? 'min-h-[360px] sm:col-span-2 lg:row-span-2' : 'min-h-[260px]')}
                    />
                  ))
                : featured.map((apartment, index) => (
                    <ApartmentTile key={apartment.slug} apartment={apartment} large={index === 0} index={index} />
                  ))}
            </div>
          )}
        </Container>
      </section>

      {/* Situation */}
      <section aria-labelledby="quartier-titre">
        <Container className="pb-16 lg:pb-[72px]">
          <h2 id="quartier-titre" className="sr-only">
            {t('home.locationTitle')}
          </h2>
          <dl className="grid gap-4 sm:grid-cols-3">
            {locations.map((item) => (
              <div key={item.label} className="flex flex-col-reverse gap-2 rounded-[28px] border border-line bg-white p-7 sm:p-8">
                <dt className="text-base text-muted">{item.label}</dt>
                <dd className={cn(heading, 'text-[40px] leading-none text-accent sm:text-5xl')}>{item.value}</dd>
              </div>
            ))}
          </dl>
        </Container>
      </section>

      {/* Réserver, c'est simple (v1) */}
      <section className="mb-16 bg-sand py-14 sm:py-[72px] lg:mb-[72px]">
        <Container className="flex flex-col gap-8">
          <h2 className={cn(heading, 'text-3xl leading-tight sm:text-4xl')}>{t('home.stepsTitle')}</h2>
          <ol className="grid gap-6 sm:grid-cols-3">
            {steps.map((step, index) => (
              <li key={step.title} className="flex flex-col gap-2.5">
                <span aria-hidden="true" className="flex size-10 items-center justify-center rounded-full bg-accent font-bold text-white">
                  {index + 1}
                </span>
                <h3 className="text-lg">{step.title}</h3>
                <p className="text-[15px] leading-relaxed text-muted">{step.text}</p>
              </li>
            ))}
          </ol>
        </Container>
      </section>

      {/* Votre hôte */}
      <section>
        <Container className="pb-16 lg:pb-[72px]">
          <div className="grid items-center gap-8 rounded-[32px] border border-line bg-white p-6 sm:p-10 md:grid-cols-[minmax(0,320px)_minmax(0,1fr)] lg:grid-cols-[minmax(0,360px)_minmax(0,1fr)] lg:gap-14 lg:rounded-[40px] lg:p-12">
            {/* Emplacement de la photo de l'hôte, à fournir par le client. */}
            <div aria-hidden="true" className="flex h-[260px] items-center justify-center rounded-t-full rounded-b-[28px] bg-[#f1d3cb] sm:h-[340px] lg:h-[380px]">
              <svg viewBox="0 0 28 28" className="size-16 text-accent-strong opacity-60">
                <path d="M8 20V12l6-4 6 4v8" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round" strokeLinecap="round" />
              </svg>
            </div>
            <div className="flex max-w-[560px] flex-col items-start gap-5">
              <p className="text-[13px] font-bold tracking-[0.08em] text-accent-text uppercase">{t('home.hostEyebrow')}</p>
              <h2 className={cn(heading, 'text-[34px] leading-[1.05] sm:text-[44px]')}>{t('home.hostTitle')}</h2>
              <p className="text-[17px] leading-[1.7] text-[#3a2e24]">{t('home.hostText')}</p>
              <p className="text-[17px] leading-[1.7] text-muted">{t('home.hostQuestion')}</p>
              <Link to={to('/contact')} className={darkButton}>
                {t('home.hostCta')}
              </Link>
            </div>
          </div>
        </Container>
      </section>

      {/* Questions fréquentes (v1) : liste dépliable dans un bloc blanc, sur fond sable */}
      <section aria-labelledby="faq-titre" className="mb-14 bg-sand py-14 sm:py-[72px]">
        <Container>
          <div className="mx-auto flex max-w-3xl flex-col gap-6">
            <h2 id="faq-titre" className={cn(heading, 'text-3xl leading-tight sm:text-4xl')}>
              {t('home.faqTitle')}
            </h2>
            <div className="flex flex-col divide-y divide-line rounded-[var(--radius-card)] bg-white">
              {faq.map((item) => (
                <details key={item.q} className="group px-6 py-5">
                  <summary className="flex min-h-11 cursor-pointer list-none items-center justify-between gap-4 text-[17px] font-semibold [&::-webkit-details-marker]:hidden">
                    {item.q}
                    <PlusIcon aria-hidden="true" width={20} height={20} className="shrink-0 text-accent-text transition-transform group-open:rotate-45" />
                  </summary>
                  <p className="mt-2 text-[15px] leading-relaxed text-muted">{item.a}</p>
                </details>
              ))}
            </div>
            <p className="text-[15px] text-muted">
              {t('home.faqIntro')}{' '}
              <Link to={to('/contact')} className="font-semibold">
                {t('home.hostCta')}
              </Link>
            </p>
          </div>
        </Container>
      </section>

      {/* Appel à réserver */}
      <section>
        <Container className="pb-14">
          <div className="flex flex-col items-start gap-6 rounded-[32px] bg-night px-6 py-10 sm:px-12 sm:py-14 lg:flex-row lg:items-center lg:justify-between lg:rounded-[40px]">
            <div className="flex max-w-[620px] flex-col gap-2.5">
              <h2 className={cn(heading, 'text-[32px] leading-[1.05] text-white sm:text-[44px]')}>{t('home.ctaTitle')}</h2>
              <p className="text-[17px] leading-relaxed text-night-text">{t('home.ctaText')}</p>
            </div>
            <Link
              to={to('/logements')}
              className="inline-flex min-h-14 shrink-0 items-center rounded-full bg-accent px-8 text-base font-bold text-white no-underline hover:bg-accent-strong hover:text-white"
            >
              {t('home.ctaButton')}
            </Link>
          </div>
        </Container>
      </section>
    </>
  )
}
