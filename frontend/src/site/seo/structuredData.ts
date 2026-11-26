/** Données structurées schema.org, lues par les moteurs de recherche. */

export interface FaqEntry {
  question: string
  answer: string
}

/** Page de questions fréquentes (FAQPage) : les questions peuvent s'afficher dans les résultats. */
export function faqStructuredData(entries: FaqEntry[]): Record<string, unknown> {
  return {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: entries.map((entry) => ({
      '@type': 'Question',
      name: entry.question,
      acceptedAnswer: { '@type': 'Answer', text: entry.answer },
    })),
  }
}
