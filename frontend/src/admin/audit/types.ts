export type AuditAction =
  | 'booking.created'
  | 'booking.updated'
  | 'booking.cancelled'
  | 'block.created'
  | 'block.deleted'
  | 'apartment.deleted'
  | 'apartment.published'
  | 'apartment.unpublished'
  | 'apartment.price_updated'
  | 'rate_period.created'
  | 'rate_period.updated'
  | 'rate_period.deleted'
  | 'settings.updated'
  | 'user.invited'
  | 'user.updated'
  | 'user.password_link_sent'
  | 'platform_revenues.saved'
  /** Ancien import CSV des revenus (abandonné), encore au journal. */
  | 'platform_payouts.imported'
  | 'bookings.exported'
  | 'review.published'
  | 'review.rejected'
  | 'review.replied'
  | 'extra.created'
  | 'extra.updated'
  | 'coupon.created'
  | 'coupon.updated'
  | 'guest.exported'
  | 'guest.anonymized'

export const AUDIT_ACTIONS: AuditAction[] = [
  'booking.created',
  'booking.updated',
  'booking.cancelled',
  'block.created',
  'block.deleted',
  'apartment.deleted',
  'apartment.published',
  'apartment.unpublished',
  'apartment.price_updated',
  'rate_period.created',
  'rate_period.updated',
  'rate_period.deleted',
  'settings.updated',
  'user.invited',
  'user.updated',
  'user.password_link_sent',
  'platform_revenues.saved',
  'platform_payouts.imported',
  'bookings.exported',
  'review.published',
  'review.rejected',
  'review.replied',
  'extra.created',
  'extra.updated',
  'coupon.created',
  'coupon.updated',
  'guest.exported',
  'guest.anonymized',
]

export type AuditValues = Record<string, unknown>

/** Une action sensible du back-office, telle que journalisée. */
export interface AuditLogEntry {
  id: number
  action: AuditAction
  user: string | null
  subject_type: string | null
  subject_id: string | null
  before: AuditValues | null
  after: AuditValues | null
  ip: string | null
  created_at: string
}

export interface AuditLogPage {
  data: AuditLogEntry[]
  meta: { current_page: number; last_page: number; total: number }
}
