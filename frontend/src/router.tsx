import { createBrowserRouter, type RouteObject } from 'react-router'
import { PublicLayout } from '@/layouts/PublicLayout'

const legalRoutes = { legal: 'mentions-legales', terms: 'cgv', privacy: 'confidentialite' } as const

/**
 * Pages publiques, identiques en français (/) et en anglais (/en). Chaque page est un
 * morceau de code chargé à la demande : l'accueil ne télécharge ni le calendrier ni la carte.
 */
const publicPages: RouteObject[] = [
  { index: true, lazy: async () => ({ Component: (await import('@/site/pages/HomePage')).HomePage }) },
  { path: 'logements', lazy: async () => ({ Component: (await import('@/site/pages/ApartmentsPage')).ApartmentsPage }) },
  { path: 'logements/:slug', lazy: async () => ({ Component: (await import('@/site/pages/ApartmentPage')).ApartmentPage }) },
  { path: 'contact', lazy: async () => ({ Component: (await import('@/site/pages/ContactPage')).ContactPage }) },
  // Suivi d'une réservation : lien signé reçu par e-mail.
  { path: 'reservation/:reference', lazy: async () => ({ Component: (await import('@/site/pages/BookingPage')).BookingPage }) },
  ...(Object.keys(legalRoutes) as Array<keyof typeof legalRoutes>).map((page) => ({
    path: legalRoutes[page],
    lazy: async () => {
      const { LegalPage } = await import('@/site/pages/LegalPage')
      return { Component: () => <LegalPage page={page} /> }
    },
  })),
  { path: '*', lazy: async () => ({ Component: (await import('@/site/pages/NotFoundPage')).NotFoundPage }) },
]

export const router = createBrowserRouter([
  { path: '/', element: <PublicLayout />, children: publicPages },
  { path: '/en', element: <PublicLayout />, children: publicPages },
  {
    // Le back-office est un morceau de code séparé, chargé seulement sous /admin.
    path: '/admin',
    children: [
      {
        path: 'connexion',
        lazy: async () => ({ Component: (await import('@/admin/pages/LoginPage')).LoginPage }),
      },
      {
        // Lien d'invitation ou de réinitialisation reçu par e-mail.
        path: 'mot-de-passe',
        lazy: async () => ({ Component: (await import('@/admin/pages/SetPasswordPage')).SetPasswordPage }),
      },
      {
        lazy: async () => ({ Component: (await import('@/admin/AdminShell')).AdminShell }),
        children: [
          {
            index: true,
            lazy: async () => ({ Component: (await import('@/admin/pages/DashboardPage')).DashboardPage }),
          },
          {
            path: 'appartements',
            lazy: async () => ({ Component: (await import('@/admin/apartments/pages/ApartmentsPage')).ApartmentsPage }),
          },
          {
            path: 'appartements/nouveau',
            lazy: async () => ({
              Component: (await import('@/admin/apartments/pages/ApartmentCreatePage')).ApartmentCreatePage,
            }),
          },
          {
            path: 'appartements/:id',
            lazy: async () => ({ Component: (await import('@/admin/apartments/pages/ApartmentEditPage')).ApartmentEditPage }),
          },
          {
            path: 'tarifs',
            lazy: async () => ({ Component: (await import('@/admin/rates/pages/RatesPage')).RatesPage }),
          },
          {
            path: 'synchronisation',
            lazy: async () => ({ Component: (await import('@/admin/sync/pages/SyncPage')).SyncPage }),
          },
          {
            path: 'notifications',
            lazy: async () => ({ Component: (await import('@/admin/notifications/pages/NotificationsPage')).NotificationsPage }),
          },
          {
            path: 'notifications/preferences',
            lazy: async () => ({
              Component: (await import('@/admin/notifications/pages/NotificationPreferencesPage')).NotificationPreferencesPage,
            }),
          },
          {
            path: 'reservations',
            lazy: async () => ({ Component: (await import('@/admin/bookings/pages/BookingsPage')).BookingsPage }),
          },
          {
            path: 'reservations/nouvelle',
            lazy: async () => ({ Component: (await import('@/admin/bookings/pages/NewBookingPage')).NewBookingPage }),
          },
          {
            path: 'reservations/:reference',
            lazy: async () => ({ Component: (await import('@/admin/bookings/pages/BookingDetailPage')).BookingDetailPage }),
          },
          {
            path: 'messages',
            lazy: async () => ({ Component: (await import('@/admin/messages/pages/MessagesPage')).MessagesPage }),
          },
          {
            path: 'reglages',
            lazy: async () => ({ Component: (await import('@/admin/settings/pages/SettingsPage')).SettingsPage }),
          },
          {
            path: 'journal',
            lazy: async () => ({ Component: (await import('@/admin/audit/pages/AuditLogPage')).AuditLogPage }),
          },
          {
            path: 'utilisateurs',
            lazy: async () => ({ Component: (await import('@/admin/users/pages/UsersPage')).UsersPage }),
          },
          {
            path: '*',
            lazy: async () => ({ Component: (await import('@/admin/pages/AdminNotFoundPage')).AdminNotFoundPage }),
          },
        ],
      },
    ],
  },
])
