/**
 * Service worker (PWA) : inscription en production et détection d'une nouvelle version.
 * Le navigateur vérifie sw.js à chaque chargement ; un nouveau déploiement l'a changé,
 * la nouvelle version s'installe puis attend que l'utilisateur choisisse de recharger.
 */

/** Partie de l'API du navigateur utilisée ici (remplaçable dans les tests). */
export interface ServiceWorkerHost {
  register(url: string): Promise<ServiceWorkerRegistration>
  readonly controller: ServiceWorker | null
  addEventListener(type: 'controllerchange', listener: () => void): void
}

/**
 * Inscrit /sw.js et appelle `onUpdate` dès qu'une nouvelle version attend.
 * La première installation (aucune version en cours) ne déclenche rien.
 */
export async function registerServiceWorker(host: ServiceWorkerHost, onUpdate: (apply: () => void) => void): Promise<void> {
  const registration = await host.register('/sw.js')
  const apply = (worker: ServiceWorker) => () => {
    host.addEventListener('controllerchange', () => window.location.reload())
    worker.postMessage('skip-waiting')
  }

  if (registration.waiting && host.controller) onUpdate(apply(registration.waiting))

  registration.addEventListener('updatefound', () => {
    const worker = registration.installing
    worker?.addEventListener('statechange', () => {
      if (worker.state === 'installed' && host.controller) onUpdate(apply(worker))
    })
  })
}

/** Manifeste du back-office sous /admin : l'application installée de là s'ouvre sur /admin. */
export function selectManifest(pathname: string, doc: Document = document): void {
  if (!pathname.startsWith('/admin')) return
  doc.querySelector('link[rel="manifest"]')?.setAttribute('href', '/admin.webmanifest')
}
