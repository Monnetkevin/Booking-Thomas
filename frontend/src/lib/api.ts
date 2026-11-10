/**
 * Client HTTP de l'API Laravel. Les appels passent par le même domaine que la SPA
 * (/api), avec le cookie de session Sanctum et le jeton CSRF XSRF-TOKEN.
 */

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
}

type Method = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'

interface RequestOptions {
  method?: Method
  body?: unknown
  signal?: AbortSignal
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

function send(path: string, method: Method, options: RequestOptions): Promise<Response> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'Accept-Language': document.documentElement.lang || 'fr',
  }
  if (options.body !== undefined) headers['Content-Type'] = 'application/json'
  const xsrf = readCookie('XSRF-TOKEN')
  if (xsrf) headers['X-XSRF-TOKEN'] = xsrf

  return fetch(`/api${path}`, {
    method,
    headers,
    credentials: 'include',
    body: options.body === undefined ? undefined : JSON.stringify(options.body),
    signal: options.signal,
  })
}

export async function api<T>(path: string, options: RequestOptions = {}): Promise<T> {
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
