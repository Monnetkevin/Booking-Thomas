import { QueryClientProvider } from '@tanstack/react-query'
import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { RouterProvider } from 'react-router'
import { applyLocale, localeFromPath } from '@/i18n'
import { queryClient } from '@/lib/queryClient'
import { router } from '@/router'
import './index.css'

// Langue fixée avant le premier rendu, d'après l'URL (/en/… = anglais).
applyLocale(localeFromPath(window.location.pathname))

// Balises posées par le serveur pour les aperçus de liens : le composant Seo les remplace
// (sinon le titre de la première page resterait affiché après une navigation).
document.querySelectorAll('[data-server-meta]').forEach((element) => element.remove())

const root = document.getElementById('root')
if (!root) throw new Error('Élément #root introuvable')

createRoot(root).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <RouterProvider router={router} />
    </QueryClientProvider>
  </StrictMode>,
)
