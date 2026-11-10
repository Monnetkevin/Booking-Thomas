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
    lazy: async () => ({ Component: (await import('@/admin/AdminLayout')).AdminLayout }),
    children: [
      {
        index: true,
        lazy: async () => ({ Component: (await import('@/admin/pages/DashboardPage')).DashboardPage }),
      },
    ],
  },
])
