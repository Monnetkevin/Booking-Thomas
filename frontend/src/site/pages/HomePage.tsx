import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { Container } from '@/components/ui/Container'
import { ErrorState } from '@/components/feedback/ErrorState'
import { useLocale } from '@/i18n/useLocale'
import { cn } from '@/lib/cn'
import { useApartments } from '@/site/catalog/useCatalog'
import type { ApartmentSummary } from '@/site/catalog/types'
import { Picture } from '@/site/components/Picture'
import { SearchForm } from '@/site/components/SearchForm'
import { Seo } from '@/site/components/Seo'

/** Titres de l'accueil v2 : Plus Jakarta très gras et serré (et non Fraunces comme le reste du site). */
const heading = 'font-sans font-extrabold tracking-[-0.035em]'

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

  const highlights = t('home.highlights', { returnObjects: true }) as string[]
  const locations = t('home.locations', { returnObjects: true }) as Array<{ value: string; label: string }>

  return (
    <>
      <Seo title={t('home.title')} description={t('home.metaDescription')} path="/" image={first?.sources.large.webp} />

      {/* Présentation et recherche */}
      <section>
        <Container className="grid items-center gap-10 pt-10 pb-8 sm:pt-16 lg:grid-cols-[1.05fr_1fr] lg:gap-12 lg:pt-[72px] lg:pb-10">
          <div className="flex flex-col gap-6 sm:gap-7">
            <p className="flex items-center gap-2 self-start rounded-full border border-line bg-white px-3.5 py-2 text-[13px] font-bold">
              <span aria-hidden="true" className="size-2 rounded-full bg-[#2e9e5b]" />
              {t('home.badge')}
            </p>
            <h1 className={cn(heading, 'text-[44px] leading-[1] tracking-[-0.045em] sm:text-6xl lg:text-[64px] lg:leading-[0.98] xl:text-[76px]')}>
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

          <div aria-hidden="true" className="relative h-[340px] sm:h-[460px] lg:h-[520px]">
            <div className="absolute inset-y-0 right-0 left-10 overflow-hidden rounded-[32px] bg-[#e8d3b8] sm:left-[60px] sm:rounded-[40px]">
              {first && <Picture photo={first} sizes="(min-width: 1024px) 560px, 90vw" priority alt="" />}
            </div>
            <div className="absolute bottom-6 left-0 h-[170px] w-[140px] overflow-hidden rounded-[24px] border-[6px] border-paper bg-[#f1d3cb] sm:bottom-10 sm:h-[230px] sm:w-[190px] sm:rounded-[28px]">
              {second && <Picture photo={second} sizes="190px" alt="" />}
            </div>
          </div>
        </Container>
      </section>

      {/* Atouts */}
      <section aria-label={t('home.highlightsLabel')}>
        <Container className="pb-12 sm:pb-14">
          <ul className="flex flex-wrap gap-2.5">
            {highlights.map((item) => (
              <li key={item} className="rounded-full border border-[#e4d8c8] bg-white px-[18px] py-2.5 text-sm font-semibold">
                {item}
              </li>
            ))}
          </ul>
        </Container>
      </section>

      {/* Mosaïque des logements */}
      <section>
        <Container className="flex flex-col gap-8 pt-6 pb-16 lg:pb-[72px]">
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

      {/* Situation (cible du lien « Le quartier » de l'en-tête) */}
      <section id="quartier" aria-labelledby="quartier-titre" className="scroll-mt-24">
        <Container className="pb-16 lg:pb-[72px]">
          <h2 id="quartier-titre" className="sr-only">
            {t('home.locationTitle')}
          </h2>
          <dl className="grid gap-4 sm:grid-cols-3">
            {locations.map((item) => (
              <div key={item.label} className="flex flex-col-reverse gap-2 rounded-[28px] border border-line bg-white p-7 sm:p-8">
                <dt className="text-base text-muted">{item.label}</dt>
                <dd className={cn(heading, 'text-[40px] leading-none tracking-[-0.04em] text-accent sm:text-5xl')}>{item.value}</dd>
              </div>
            ))}
          </dl>
        </Container>
      </section>

      {/* Votre hôte */}
      <section>
        <Container className="pb-16 lg:pb-[72px]">
          <div className="grid items-center gap-8 rounded-[32px] border border-line bg-white p-6 sm:p-10 md:grid-cols-[minmax(0,320px)_minmax(0,1fr)] lg:grid-cols-[minmax(0,360px)_minmax(0,1fr)] lg:gap-14 lg:rounded-[40px] lg:p-12">
            {/* Emplacement de la photo de l'hôte, à fournir par le client. */}
            <div aria-hidden="true" className="flex h-[240px] items-center justify-center rounded-[28px] bg-[#f1d3cb] sm:h-[320px] lg:h-[380px] lg:rounded-[32px]">
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

      {/* Appel à réserver */}
      <section>
        <Container className="pb-14">
          <div className="flex flex-col items-start gap-6 rounded-[32px] bg-accent px-6 py-10 sm:px-12 sm:py-14 lg:flex-row lg:items-center lg:justify-between lg:rounded-[40px]">
            <div className="flex max-w-[620px] flex-col gap-2.5">
              <h2 className={cn(heading, 'text-[32px] leading-[1.05] text-white sm:text-[44px]')}>{t('home.ctaTitle')}</h2>
              <p className="text-[17px] leading-relaxed text-white">{t('home.ctaText')}</p>
            </div>
            <Link
              to={to('/logements')}
              className="inline-flex min-h-14 shrink-0 items-center rounded-full bg-white px-8 text-base font-bold text-ink no-underline hover:bg-paper hover:text-ink"
            >
              {t('home.ctaButton')}
            </Link>
          </div>
        </Container>
      </section>
    </>
  )
}
