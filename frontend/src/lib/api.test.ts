import { describe, expect, it } from 'vitest'
import { filenameFromDisposition } from './api'

describe('nom du fichier téléchargé', () => {
  it('lit la forme simple et la forme encodée', () => {
    expect(filenameFromDisposition('attachment; filename="statistiques.csv"')).toBe('statistiques.csv')
    expect(filenameFromDisposition("attachment; filename=x.pdf; filename*=UTF-8''r%C3%A9servation.pdf")).toBe('réservation.pdf')
  })

  it('renvoie null sans en-tête exploitable', () => {
    expect(filenameFromDisposition(null)).toBeNull()
    expect(filenameFromDisposition('inline')).toBeNull()
  })
})
