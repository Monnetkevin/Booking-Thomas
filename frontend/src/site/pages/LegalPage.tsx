import { useTranslation } from 'react-i18next'
import { Alert } from '@/components/ui/Alert'
import { Container } from '@/components/ui/Container'
import { useLocale } from '@/i18n/useLocale'
import { legalDocuments, legalPaths, type LegalPageKey } from '@/site/legal/content'
import { Seo } from '@/site/components/Seo'

/** Mentions légales, conditions de vente ou confidentialité, dans la langue de la page. */
export function LegalPage({ page }: { page: LegalPageKey }) {
  const { t } = useTranslation()
  const { locale } = useLocale()
  const document = legalDocuments[page][locale]
  const isDraft = document.sections.some((section) => section.paragraphs.some((p) => p.includes('[')))

  return (
    <Container className="max-w-3xl py-10 sm:py-14">
      <Seo title={document.title} description={document.description} path={legalPaths[page]} />
      <article className="flex flex-col gap-6">
        <h1 className="text-4xl font-semibold">{document.title}</h1>
        {isDraft && <Alert tone="warning">{t('legal.draftNotice')}</Alert>}
        {document.sections.map((section) => (
          <section key={section.heading} className="flex flex-col gap-2">
            <h2 className="text-2xl font-semibold">{section.heading}</h2>
            {section.paragraphs.map((paragraph) => (
              <p key={paragraph} className="text-base leading-relaxed">
                {paragraph}
              </p>
            ))}
          </section>
        ))}
      </article>
    </Container>
  )
}
