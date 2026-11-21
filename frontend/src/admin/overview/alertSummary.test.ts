import { describe, expect, it } from 'vitest'
import { alertParts } from './alertSummary'

describe('résumé des alertes', () => {
  it('ne garde que les alertes présentes', () => {
    expect(alertParts({ conflicts: 1, failing_feeds: 0, unread_messages: 3, last_sync_at: null })).toEqual([{ key: 'conflicts', count: 1 }])
    expect(alertParts({ conflicts: 0, failing_feeds: 0, unread_messages: 0, last_sync_at: null })).toEqual([])
    expect(alertParts(undefined)).toEqual([])
  })
})
