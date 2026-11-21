import { describe, expect, it } from 'vitest'
import { settingsTabOf } from './settingsForm'

describe('onglet d’une erreur des réglages', () => {
  it('ramène chaque champ à son onglet', () => {
    expect(settingsTabOf('minNoticeDays')).toBe('reservation')
    expect(settingsTabOf('horizon_months')).toBe('reservation')
    expect(settingsTabOf('notification_emails.1')).toBe('notifications')
    expect(settingsTabOf('moderate.0.daysBefore')).toBe('paiement')
    expect(settingsTabOf('taxRate')).toBe('paiement')
  })
})
