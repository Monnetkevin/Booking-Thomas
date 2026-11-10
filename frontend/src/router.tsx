import { createBrowserRouter, type RouteObject } from 'react-router'
import { PublicLayout } from '@/layouts/PublicLayout'
import { HomePage } from '@/pages/HomePage'
import { NotFoundPage } from '@/pages/NotFoundPage'

/** Pages publiques, identiques en français (/) et en anglais (/en). */
const publicPages: RouteObject[] = [
  { index: true, element: <HomePage /> },
  { path: '*', element: <NotFoundPage /> },
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
