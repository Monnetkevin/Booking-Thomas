import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { HomeIcon } from '@/components/icons'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { Container } from '@/components/ui/Container'
import { useLocale } from '@/i18n/useLocale'

/** Présentation de l'hôte : emplacement de photo en arche, texte et lien vers le contact. */
export function HostIntro() {
  const { t } = useTranslation()
  const { to } = useLocale()

  return (
    <section className="py-8 sm:py-10 lg:py-12">
      <Container>
        <div className="grid items-center gap-8 rounded-[32px] border border-line bg-white p-6 sm:p-10 md:grid-cols-[minmax(0,320px)_minmax(0,1fr)] lg:grid-cols-[minmax(0,360px)_minmax(0,1fr)] lg:gap-14 lg:rounded-[40px] lg:p-12">
          {/* Emplacement de la photo de l'hôte, à fournir par le client. */}
          <div aria-hidden="true" className="flex h-[260px] items-center justify-center rounded-t-full rounded-b-[28px] bg-[#f1d3cb] sm:h-[340px] lg:h-[380px]">
            <HomeIcon width={56} height={56} strokeWidth={1.25} className="text-accent-strong opacity-60" />
          </div>
          <div className="flex max-w-[560px] flex-col items-start gap-5">
            <p className="text-[13px] font-bold tracking-[0.08em] text-accent-text uppercase">{t('home.hostEyebrow')}</p>
            <h2 className="text-[34px] leading-[1.05] sm:text-[44px]">{t('home.hostTitle')}</h2>
            <p className="text-[17px] leading-[1.7] text-[#3a2e24]">{t('home.hostText')}</p>
            <p className="text-[17px] leading-[1.7] text-muted">{t('home.hostQuestion')}</p>
            <Link to={to('/contact')} className={buttonClasses('dark', 'lg')}>
              {t('home.hostCta')}
            </Link>
          </div>
        </div>
      </Container>
    </section>
  )
}
