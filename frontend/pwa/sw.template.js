/* Service worker du site (généré au build par pwa/serviceWorkerPlugin.ts).
 * Garde en cache le code déjà téléchargé (fichiers /assets/ de Vite) pour un démarrage
 * rapide ; rien n'est préchargé. Pas de mode hors ligne : pages, API et photos passent
 * toujours par le réseau. VERSION change à chaque déploiement : le navigateur voit un
 * nouveau sw.js, qui attend que l'utilisateur clique « Mettre à jour » (pas de skipWaiting
 * à l'installation). */
const VERSION = '__VERSION__'
const CACHE = `code-${VERSION}`

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) => Promise.all(keys.filter((key) => key.startsWith('code-') && key !== CACHE).map((key) => caches.delete(key))))
      .then(() => self.clients.claim()),
  )
})

self.addEventListener('message', (event) => {
  if (event.data === 'skip-waiting') self.skipWaiting()
})

// Fichiers versionnés de Vite (/assets/…, nom avec empreinte) : le cache d'abord, ils ne changent jamais.
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url)
  if (event.request.method !== 'GET' || url.origin !== self.location.origin || !url.pathname.startsWith('/assets/')) return
  event.respondWith(
    caches.open(CACHE).then((cache) =>
      cache.match(event.request).then(
        (cached) =>
          cached ??
          fetch(event.request).then((response) => {
            if (response.ok) cache.put(event.request, response.clone())
            return response
          }),
      ),
    ),
  )
})
