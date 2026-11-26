/** Montants : l'API parle en centimes d'euro, l'interface en euros. */

const formatters = new Map<string, Intl.NumberFormat>()

function formatter(locale: string, withCents: boolean): Intl.NumberFormat {
  const key = `${locale}-${withCents}`
  let format = formatters.get(key)
  if (!format) {
    format = new Intl.NumberFormat(locale, {
      style: 'currency',
      currency: 'EUR',
      minimumFractionDigits: withCents ? 2 : 0,
      maximumFractionDigits: 2,
    })
    formatters.set(key, format)
  }
  return format
}

/** 9550 → « 95,50 € » ; 9500 → « 95 € ». */
export function formatEuros(cents: number, locale = 'fr-FR'): string {
  return formatter(locale, cents % 100 !== 0).format(cents / 100)
}

/** Valeur d'un champ « 95,50 » : 9500 → « 95 », 9550 → « 95,50 ». */
export function centsToInput(cents: number | null | undefined): string {
  if (cents === null || cents === undefined) return ''
  const euros = Math.trunc(cents / 100)
  const rest = Math.abs(cents % 100)
  return rest === 0 ? String(euros) : `${euros},${String(rest).padStart(2, '0')}`
}

/** « 95,5 », « 95.50 », « 1 200 » → centimes ; null si la saisie n'est pas un montant. */
export function parseEuros(input: string): number | null {
  const normalized = input.replace(/[\s  €]/g, '').replace(',', '.')
  if (!/^\d+(\.\d{1,2})?$/.test(normalized)) return null
  return Math.round(Number(normalized) * 100)
}
