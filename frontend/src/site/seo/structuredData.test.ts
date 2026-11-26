import { describe, expect, it } from 'vitest'
import { faqStructuredData } from './structuredData'

describe('données structurées', () => {
  it('décrit une FAQ au format schema.org', () => {
    expect(faqStructuredData([{ question: 'Comment payer ?', answer: 'Par carte.' }])).toEqual({
      '@context': 'https://schema.org',
      '@type': 'FAQPage',
      mainEntity: [{ '@type': 'Question', name: 'Comment payer ?', acceptedAnswer: { '@type': 'Answer', text: 'Par carte.' } }],
    })
  })
})
