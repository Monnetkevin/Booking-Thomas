import { useTranslation } from 'react-i18next'
import { localizedPath } from '@/i18n'
import { useLocale } from '@/i18n/useLocale'

interface SeoProps {
  title: string
  description: string
  /** Chemin sans préfixe de langue, ex. « /logements/le-cocon ». */
  path: string
  image?: string | null
  type?: 'website' | 'article'
  jsonLd?: Record<string, unknown>
  noIndex?: boolean
}

/**
 * Titre, description, URL canonique, versions FR/EN (hreflang), Open Graph et données
 * structurées. React 19 place ces balises dans <head>.
 */
export function Seo({ title, description, path, image, type = 'website', jsonLd, noIndex = false }: SeoProps) {
  const { t } = useTranslation()
  const { locale } = useLocale()
  const origin = window.location.origin
  const url = (target: 'fr' | 'en') => origin + localizedPath(path, target)
  const fullTitle = `${title} · ${t('site.name')}`

  return (
    <>
      <title>{fullTitle}</title>
      <meta name="description" content={description} />
      {noIndex && <meta name="robots" content="noindex" />}
      <link rel="canonical" href={url(locale)} />
      <link rel="alternate" hrefLang="fr" href={url('fr')} />
      <link rel="alternate" hrefLang="en" href={url('en')} />
      <link rel="alternate" hrefLang="x-default" href={url('fr')} />
      <meta property="og:type" content={type} />
      <meta property="og:title" content={fullTitle} />
      <meta property="og:description" content={description} />
      <meta property="og:url" content={url(locale)} />
      <meta property="og:locale" content={locale === 'fr' ? 'fr_FR' : 'en_GB'} />
      <meta property="og:site_name" content={t('site.name')} />
      {image && <meta property="og:image" content={image} />}
      <meta name="twitter:card" content={image ? 'summary_large_image' : 'summary'} />
      {jsonLd && <script type="application/ld+json">{JSON.stringify(jsonLd)}</script>}
    </>
  )
}
