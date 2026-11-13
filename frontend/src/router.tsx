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
        ],
      },
    ],
  },
])
