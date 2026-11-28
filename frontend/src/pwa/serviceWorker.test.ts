import { describe, expect, it, vi } from 'vitest'
import { codeFiles } from '../../pwa/serviceWorkerPlugin'
import { registerServiceWorker, selectManifest, type ServiceWorkerHost } from './serviceWorker'

/** Inscription simulée : une version déjà en cours (`controller`) et un nouveau service worker. */
function fakeHost(withController: boolean) {
  const listeners: Record<string, () => void> = {}
  const worker = {
    state: 'installing',
    postMessage: vi.fn(),
    addEventListener: (_type: string, listener: () => void) => (listeners.statechange = listener),
  }
  const registration = {
    waiting: null,
    installing: worker,
    addEventListener: (_type: string, listener: () => void) => (listeners.updatefound = listener),
  }
  const host = {
    register: vi.fn(async () => registration as unknown as ServiceWorkerRegistration),
    controller: withController ? ({} as ServiceWorker) : null,
    addEventListener: vi.fn(),
  } satisfies ServiceWorkerHost
  const install = () => {
    listeners.updatefound?.()
    worker.state = 'installed'
    listeners.statechange?.()
  }
  return { host, worker, install }
}

describe('service worker (PWA)', () => {
  it('propose la mise à jour quand une nouvelle version s’installe', async () => {
    const { host, worker, install } = fakeHost(true)
    const onUpdate = vi.fn()

    await registerServiceWorker(host, onUpdate)
    install()

    expect(host.register).toHaveBeenCalledWith('/sw.js')
    expect(onUpdate).toHaveBeenCalledTimes(1)
    onUpdate.mock.calls[0]?.[0]()
    expect(worker.postMessage).toHaveBeenCalledWith('skip-waiting')
  })

  it('ne dit rien à la première installation', async () => {
    const { host, install } = fakeHost(false)
    const onUpdate = vi.fn()

    await registerServiceWorker(host, onUpdate)
    install()

    expect(onUpdate).not.toHaveBeenCalled()
  })

  it('tire la version du code seulement (JS, CSS)', () => {
    expect(codeFiles(['assets/index-a1.js', 'assets/index-b2.css', 'assets/font-c3.woff2', 'icons/icon-192.png', 'index.html'])).toEqual([
      '/assets/index-a1.js',
      '/assets/index-b2.css',
    ])
  })

  it('prend le manifeste du back-office sous /admin', () => {
    const link = { href: '/manifest.webmanifest', setAttribute(_name: string, value: string) { this.href = value } }
    const doc = { querySelector: () => link } as unknown as Document

    selectManifest('/logements', doc)
    expect(link.href).toBe('/manifest.webmanifest')

    selectManifest('/admin/planning', doc)
    expect(link.href).toBe('/admin.webmanifest')
  })
})
