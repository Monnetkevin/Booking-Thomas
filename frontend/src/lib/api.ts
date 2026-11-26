/**
 * Client HTTP de l'API Laravel. Les appels passent par le même domaine que la SPA
 * (/api), avec le cookie de session Sanctum et le jeton CSRF XSRF-TOKEN.
 */
import type { HttpMethod, HttpRequestOptions } from './http'

export class ApiError extends Error {
  readonly status: number
  readonly data: unknown

  constructor(status: number, data: unknown) {
    super(`Erreur API ${status}`)
    this.status = status
    this.data = data
  }

  /** Erreurs de validation Laravel (422) : champ → messages. */
  get fieldErrors(): Record<string, string[]> {
    const errors = (this.data as { errors?: Record<string, string[]> } | null)?.errors
    return errors ?? {}
  }

  /** Message renvoyé par l'API (règle métier, validation…), déjà traduit. */
  get serverMessage(): string | undefined {
    return (this.data as { message?: string } | null)?.message
  }

  /** Code stable d'une erreur métier, ex. « apartment_not_publishable ». */
  get code(): string | undefined {
    return (this.data as { code?: string } | null)?.code
  }

  get details(): Record<string, unknown> {
    return (this.data as { details?: Record<string, unknown> } | null)?.details ?? {}
  }
}

function readCookie(name: string): string | null {
  const match = document.cookie.split('; ').find((row) => row.startsWith(`${name}=`))
  return match ? decodeURIComponent(match.slice(name.length + 1)) : null
}

/** Pose le cookie XSRF-TOKEN avant la première requête qui modifie des données. */
export async function ensureCsrfCookie(): Promise<void> {
  if (readCookie('XSRF-TOKEN')) return
  await fetch('/sanctum/csrf-cookie', { credentials: 'include' })
}

function send(path: string, method: HttpMethod, options: HttpRequestOptions): Promise<Response> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'Accept-Language': document.documentElement.lang || 'fr',
  }
  // FormData (envoi de fichiers) : le navigateur pose lui-même le Content-Type multipart.
  const isForm = options.body instanceof FormData
  if (options.body !== undefined && !isForm) headers['Content-Type'] = 'application/json'
  const xsrf = readCookie('XSRF-TOKEN')
  if (xsrf) headers['X-XSRF-TOKEN'] = xsrf

  return fetch(`/api${path}`, {
    method,
    headers,
    credentials: 'include',
    body: options.body === undefined ? undefined : isForm ? (options.body as FormData) : JSON.stringify(options.body),
    signal: options.signal,
  })
}

/** Nom de fichier de l'en-tête Content-Disposition (forme RFC 5987 « filename*= » d'abord). */
export function filenameFromDisposition(header: string | null): string | null {
  if (!header) return null
  const encoded = /filename\*=(?:UTF-8'')?([^;]+)/i.exec(header)
  if (encoded) {
    try {
      return decodeURIComponent((encoded[1] ?? '').trim().replace(/^"|"$/g, ''))
    } catch {
      // nom mal encodé : on tente la forme simple
    }
  }
  const plain = /filename="?([^";]+)"?/i.exec(header)
  return plain?.[1]?.trim() ?? null
}

/** Téléchargement d'un fichier : mêmes en-têtes et mêmes erreurs que api(). */
export async function apiDownload(path: string): Promise<{ blob: Blob; filename: string | null }> {
  const response = await send(path, 'GET', {})
  if (!response.ok) throw new ApiError(response.status, await response.json().catch(() => null))
  return { blob: await response.blob(), filename: filenameFromDisposition(response.headers.get('Content-Disposition')) }
}

export async function api<T>(path: string, options: HttpRequestOptions = {}): Promise<T> {
  const method = options.method ?? 'GET'
  if (method !== 'GET') await ensureCsrfCookie()

  let response = await send(path, method, options)

  // 419 : jeton CSRF expiré (session renouvelée) ; on en reprend un et on réessaie une fois.
  if (response.status === 419) {
    await fetch('/sanctum/csrf-cookie', { credentials: 'include' })
    response = await send(path, method, options)
  }

  if (response.status === 204) return undefined as T
  const data: unknown = await response.json().catch(() => null)
  if (!response.ok) throw new ApiError(response.status, data)
  return data as T
}
