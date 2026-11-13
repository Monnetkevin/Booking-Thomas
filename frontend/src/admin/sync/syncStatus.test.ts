import { describe, expect, it } from 'vitest'
import { feedHealth, syncChanges } from './syncStatus'
import type { IcalFeed, IcalSyncLog } from './types'

const feed: IcalFeed = {
  id: 1,
  apartment_id: 1,
  provider: 'airbnb',
  provider_label: 'Airbnb',
  import_url: 'https://www.airbnb.fr/calendar/ical/1.ics',
  export_url: 'https://exemple.fr/ical/abc.ics',
  is_active: true,
  last_synced_at: '2026-11-13T10:00:00+01:00',
  last_succeeded_at: '2026-11-13T10:00:00+01:00',
  last_status: 'success',
  last_error: null,
  consecutive_failures: 0,
}

const log: IcalSyncLog = {
  id: 1,
  feed_id: 1,
  trigger: 'manual',
  status: 'success',
  created: 2,
  updated: 0,
  removed: 1,
  conflicts: 0,
  error: null,
  duration_ms: 120,
  synced_at: '2026-11-13T10:00:00+01:00',
}

describe('état d’un calendrier', () => {
  it('distingue chaque situation, de l’absence de calendrier à la panne', () => {
    expect(feedHealth(undefined)).toBe('not_configured')
    expect(feedHealth({ ...feed, import_url: null })).toBe('export_only')
    expect(feedHealth({ ...feed, is_active: false })).toBe('disabled')
    expect(feedHealth({ ...feed, last_status: null })).toBe('pending')
    expect(feedHealth({ ...feed, last_status: 'failed' })).toBe('failing')
    expect(feedHealth({ ...feed, last_status: 'unchanged' })).toBe('ok')
    expect(feedHealth(feed)).toBe('ok')
  })

  it('résume une lecture par ses seuls changements', () => {
    expect(syncChanges(log)).toEqual([
      { change: 'created', count: 2 },
      { change: 'removed', count: 1 },
    ])
    expect(syncChanges({ ...log, created: 0, removed: 0 })).toEqual([])
  })
})
