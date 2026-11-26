/** Message envoyé depuis le formulaire de contact du site. */
export interface ContactMessage {
  id: number
  name: string
  email: string
  phone: string | null
  message: string
  apartment: { id: number; name: string } | null
  locale: string
  read: boolean
  created_at: string
}

export interface ContactMessagePage {
  data: ContactMessage[]
  meta: { current_page: number; last_page: number; total: number; unread: number }
}
