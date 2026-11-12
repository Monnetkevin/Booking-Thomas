/**
 * Texte dont les mots composés ne se coupent pas au trait d'union (« pied-à-terre ») :
 * les grands titres passent à la ligne entre les mots seulement.
 */
export function NoBreakHyphens({ text }: { text: string }) {
  return text.split(' ').map((word, index) => (
    <span key={index} className={word.includes('-') ? 'whitespace-nowrap' : undefined}>
      {index > 0 && ' '}
      {word}
    </span>
  ))
}
