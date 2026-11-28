import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { ArrowUpRightIcon } from '@/components/icons'
import { Container } from '@/components/ui/Container'
import { NoBreakHyphens } from '@/components/ui/NoBreakHyphens'
import { StatusPill } from '@/components/ui/StatusPill'
import { useLocale } from '@/i18n/useLocale'
import { cn } from '@/lib/cn'
import { apartmentFacts } from '@/site/catalog/apartmentFacts'
import type { ApartmentSummary, PublicPhoto } from '@/site/catalog/types'
import { Picture } from '@/site/components/Picture'
import { SearchForm } from '@/site/components/SearchForm'
import { HeroRating } from './HeroRating'
import { GROW_DURATION, useHeroSlides } from './useHeroSlides'

/** Cartes des logements suivants, à droite : la quatrième n'apparaît qu'à partir de 1440 px, pour laisser sa place à la recherche. */
const MAX_CARDS = 4

type Slide = ApartmentSummary & { cover: PublicPhoto }

const pad = (n: number) => String(n).padStart(2, '0')

/**
 * Haut de l'accueil (maquette v4) : photo d'un logement en plein fond, promesse, grand titre,
 * logement affiché et recherche ; à droite, les cartes des autres logements pour changer de fond.
 * Passe sous l'en-tête du site, transparent tant que la page n'a pas défilé.
 */
export function HomeHero({ intro, apartments, loading = false }: { intro: string; apartments: ApartmentSummary[]; loading?: boolean }) {
  const { t } = useTranslation()
  const { to } = useLocale()
  const slides = apartments.filter((apartment): apartment is Slide => apartment.cover !== null)
  const { current, growing, growStyle, sectionRef, pick } = useHeroSlides(slides.length)
  const shown = slides[current]
  const others = slides.map((_, i) => (current + i) % slides.length).slice(1, MAX_CARDS + 1)

  return (
    // Remonte sous l'en-tête (72 px + filet de 1 px) pour que la photo parte du haut de l'écran.
    <section ref={sectionRef} className="relative isolate -mt-[73px] overflow-hidden bg-[#1b1612] text-white">
      {slides.map((slide, i) => (
        <div
          key={slide.slug}
          aria-hidden="true"
          // Pas de fondu : le fond change sous la carte agrandie, qui montre déjà la même photo.
          className={cn('absolute inset-0 -z-20', i === current ? 'opacity-100' : 'opacity-0')}
        >
          <Picture photo={slide.cover} sizes="100vw" priority={i === 0} alt="" />
        </div>
      ))}
      {growing !== null && growStyle && slides[growing] && (
        <div
          aria-hidden="true"
          className="absolute -z-20 overflow-hidden"
          style={{
            ...growStyle,
            transition: ['left', 'top', 'width', 'height', 'border-radius']
              .map((p) => `${p} ${GROW_DURATION}ms cubic-bezier(0.65,0,0.35,1)`)
              .join(','),
          }}
        >
          <Picture photo={slides[growing].cover} sizes="100vw" alt="" />
        </div>
      )}
      {/* Voile : sombre à gauche sous le texte, léger à droite ; plus uniforme sur téléphone. */}
      <div
        aria-hidden="true"
        className="absolute inset-0 -z-10 bg-[linear-gradient(180deg,rgba(20,14,10,0.7)_0%,rgba(20,14,10,0.55)_45%,rgba(20,14,10,0.8)_100%)] lg:bg-[linear-gradient(90deg,rgba(20,14,10,0.9)_0%,rgba(20,14,10,0.64)_46%,rgba(20,14,10,0.12)_100%),linear-gradient(180deg,rgba(20,14,10,0.62)_0%,rgba(20,14,10,0)_26%,rgba(20,14,10,0.55)_100%)]"
      />

      {/* Exactement la hauteur de l'écran : le contenu se resserre sur les écrans peu hauts. */}
      <Container wide className="flex h-svh items-end justify-between gap-8 pt-[96px] pb-6 sm:pb-10 lg:pb-[52px] short:pb-6">
        <div className="flex w-full max-w-[600px] min-w-0 flex-col gap-5 sm:gap-6 short:gap-4 tiny:gap-3">
          <div className="flex flex-wrap gap-2 tiny:hidden">
            <StatusPill tone="glass">{t('home.badge')}</StatusPill>
            <HeroRating />
          </div>
          <h1 className="text-[40px] leading-[1.02] text-white min-[400px]:text-[46px] sm:text-6xl lg:text-[68px] short:lg:text-6xl tiny:text-[34px] tiny:sm:text-[40px] tiny:lg:text-5xl">
            <NoBreakHyphens text={t('home.titleStart')} /> <span className="text-[#f3b08e]">{t('home.titleAccent')}</span>
          </h1>
          <p className="max-w-[480px] text-[17px] leading-relaxed text-[#f4ebdd] sm:text-lg short:hidden">{intro}</p>

          {/* Logements pas encore chargés : place réservée (même hauteur), sans décaler le haut de page. */}
          {!shown && loading && <div aria-hidden="true" className="min-h-[91px] border-t border-white/35 sm:min-h-[71px]" />}
          {shown && (
            <div className="flex items-center justify-between gap-4 border-t border-white/35 pt-5 short:pt-4" aria-live="polite">
              <div className="flex min-w-0 flex-col gap-0.5">
                <h2 className="text-2xl text-white">{shown.name}</h2>
                <p className="text-sm text-[#f4ebdd]">
                  {[shown.tagline, apartmentFacts(shown, t, { withBedrooms: false })].filter(Boolean).join(' · ')}
                </p>
              </div>
              <Link
                to={to(`/logements/${shown.slug}`)}
                className="flex min-h-11 shrink-0 items-center gap-2 rounded-full bg-white px-5 text-sm font-bold text-ink no-underline hover:bg-sand hover:text-ink focus-visible:outline-white"
              >
                {t('home.discover')}
                <ArrowUpRightIcon aria-hidden="true" width={14} height={14} />
              </Link>
            </div>
          )}

          {/* Message d'erreur de la recherche lisible sur la photo. */}
          <div className="text-ink [&_[role=alert]]:text-[#ffc2ad]">
            <SearchForm compact />
          </div>
        </div>

        {others.length > 0 && (
          <div className="hidden shrink-0 flex-col items-end gap-[18px] lg:flex">
            <div className="flex items-center gap-3.5" aria-hidden="true">
              <span className="text-[15px] font-bold tracking-[0.04em]">
                {pad(current + 1)} / {pad(slides.length)}
              </span>
              <div className="flex gap-1.5">
                {slides.map((slide, i) => (
                  <span
                    key={slide.slug}
                    className={cn('block h-1 w-7 rounded-full transition-colors duration-300', i === current ? 'bg-white' : 'bg-white/40')}
                  />
                ))}
              </div>
            </div>
            <ul className="flex gap-3.5" aria-label={t('home.heroApartments')}>
              {others.map((i, slot) => {
                const slide = slides[i]!
                return (
                  <li key={slide.slug} className={cn(slot === 3 && 'hidden min-[1440px]:block')}>
                    <button
                      type="button"
                      onClick={(event) => pick(i, event.currentTarget)}
                      aria-label={t('home.showApartment', { name: slide.name })}
                      className={cn(
                        'relative isolate flex h-[230px] w-[148px] cursor-pointer flex-col justify-end overflow-hidden rounded-[22px] border border-white/40 bg-[#2a1a21] p-3.5 text-left text-white',
                        'transition-[transform,border-color,opacity] duration-200 hover:-translate-y-2 hover:border-white focus-visible:outline-3 focus-visible:outline-offset-3 focus-visible:outline-white',
                        growing === i && 'opacity-0',
                      )}
                    >
                      <span aria-hidden="true" className="absolute inset-0 -z-10">
                        <Picture photo={slide.cover} sizes="148px" eager alt="" className="object-[50%_60%]" />
                      </span>
                      <span
                        aria-hidden="true"
                        className="absolute inset-0 -z-10 bg-[linear-gradient(180deg,rgba(15,10,7,0)_38%,rgba(15,10,7,0.86)_100%)]"
                      />
                      <span aria-hidden="true" className="mb-2 block h-0.5 w-[22px] bg-white" />
                      <span className="block text-[15px] leading-tight font-extrabold tracking-[-0.01em]">{slide.name}</span>
                      {slide.tagline && <span className="mt-0.5 block text-xs leading-snug text-[#f4ebdd]">{slide.tagline}</span>}
                    </button>
                  </li>
                )
              })}
            </ul>
          </div>
        )}
      </Container>
    </section>
  )
}
