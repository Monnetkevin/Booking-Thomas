import { describe, expect, it } from 'vitest'
import { safeAdminRedirect } from './auth'

describe('redirection après connexion', () => {
  it('revient sur la page du back-office demandée', () => {
    expect(safeAdminRedirect('/admin/reservations?statut=pending')).toBe('/admin/reservations?statut=pending')
  })

  it('refuse les redirections hors du back-office', () => {
    expect(safeAdminRedirect('https://exemple.com')).toBe('/admin')
    expect(safeAdminRedirect('//exemple.com/admin')).toBe('/admin')
    expect(safeAdminRedirect('/logements')).toBe('/admin')
    expect(safeAdminRedirect(null)).toBe('/admin')
  })

  it('ne renvoie pas vers la page de connexion', () => {
    expect(safeAdminRedirect('/admin/connexion')).toBe('/admin')
  })
})
