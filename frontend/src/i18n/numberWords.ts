/**
 * Nombre écrit en lettres d'après la liste traduite (« cinq », « five »), en chiffres
 * au-delà de la liste ; null s'il n'y a rien à compter.
 */
export function countInWords(count: number, words: readonly string[]): { word: string; capitalized: string } | null {
  if (!Number.isInteger(count) || count <= 0) return null
  const word = words[count] ?? String(count)
  return { word, capitalized: word.charAt(0).toUpperCase() + word.slice(1) }
}
