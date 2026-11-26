import { describe, expect, it } from 'vitest'
import { loginPath, safeAdminRedirect } from './redirect'

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

  it('encode la page demandée dans le lien de connexion', () => {
    expect(loginPath('/admin/reservations?statut=pending')).toBe(
      '/admin/connexion?redirect=%2Fadmin%2Freservations%3Fstatut%3Dpending',
    )
  })
})
