import { describe, expect, it } from 'vitest'
import { auditChanges } from './auditChanges'

describe('changements journalisés', () => {
  it('ne garde que les champs modifiés', () => {
    expect(auditChanges({ nightly_price: 9500, label: 'Hiver' }, { nightly_price: 11000, label: 'Hiver' })).toEqual([
      { field: 'nightly_price', before: '9500', after: '11000' },
    ])
  })

  it('montre tout l’après d’une création et tout l’avant d’une suppression', () => {
    expect(auditChanges(null, { name: 'Alice' })).toEqual([{ field: 'name', before: null, after: 'Alice' }])
    expect(auditChanges({ is_active: true }, null)).toEqual([{ field: 'is_active', before: 'oui', after: null }])
  })
})
