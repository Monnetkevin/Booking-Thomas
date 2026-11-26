export type NotificationLevel = 'urgent' | 'info'

export type NotificationType =
  | 'ical_conflict'
  | 'ical_feed_failing'
  | 'ical_empty_calendar_refused'
  | 'ical_feed_recovered'
  | 'booking_confirmed'
  | 'contact_message'
  | 'review_submitted'
  | 'stay_changed'
  | 'daily_summary'

/** Notification du back-office, contenu figé à l'envoi (rédigé par l'API). */
export interface AppNotification {
  id: string
  type: NotificationType
  level: NotificationLevel
  title: string
  body: string
  details: string[]
  /** Page du back-office concernée (« /admin/synchronisation »), ou null. */
  link: string | null
  read: boolean
  created_at: string
}

export interface NotificationPage {
  data: AppNotification[]
  meta: { current_page: number; last_page: number; total: number }
}

export interface NotificationTypePreference {
  type: NotificationType
  email: boolean
  /** false : e-mail toujours envoyé (conflit de dates) ou jamais (déjà reçu autrement). */
  editable: boolean
}

export interface NotificationPreferences {
  types: NotificationTypePreference[]
  summary_enabled: boolean
  summary_hour: number
}

export interface NotificationPreferencesInput {
  email?: Partial<Record<NotificationType, boolean>>
  summary_enabled?: boolean
  summary_hour?: number
}
