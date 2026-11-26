/** « Camille Martin » → « C. Martin » ; un seul mot reste tel quel. */
export function shortGuestName(name: string): string {
  const words = name.trim().split(/\s+/)
  if (words.length < 2) return name.trim()
  return `${words[0]?.charAt(0).toLocaleUpperCase('fr-FR')}. ${words.slice(1).join(' ')}`
}
