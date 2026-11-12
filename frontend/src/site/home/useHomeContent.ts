import { useTranslation } from 'react-i18next'
import { countInWords } from '@/i18n/numberWords'
import type { Fact } from '@/site/components/FactCards'
import type { Feature } from '@/site/components/FeatureList'
import type { Step } from '@/site/components/StepList'
import type { FaqEntry } from '@/site/seo/structuredData'

/**
 * Textes de l'accueil, typés, lus dans les traductions. Le nombre de logements publiés
 * s'écrit en lettres (« Cinq adresses, cinq ambiances »).
 */
export function useHomeContent(apartmentCount: number) {
  const { t } = useTranslation()
  const count = countInWords(apartmentCount, t('home.numbers', { returnObjects: true }) as string[])

  const [collectionLine1 = '', collectionLine2 = ''] = (
    count
      ? t('home.collectionTitle', { count: apartmentCount, word: count.capitalized, wordLower: count.word })
      : t('home.collectionFallback')
  ).split('|')

  return {
    intro: count ? t('home.intro', { count: apartmentCount, word: count.capitalized }) : t('home.introFallback'),
    collectionTitle: [collectionLine1, collectionLine2] as const,
    features: t('home.features', { returnObjects: true }) as Feature[],
    facts: t('home.locations', { returnObjects: true }) as Fact[],
    steps: t('home.steps', { returnObjects: true }) as Step[],
    faq: (t('home.faq', { returnObjects: true }) as Array<{ q: string; a: string }>).map(
      (item): FaqEntry => ({ question: item.q, answer: item.a }),
    ),
  }
}
