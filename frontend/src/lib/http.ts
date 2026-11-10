import { api } from './api'

export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'

export interface HttpRequestOptions {
  method?: HttpMethod
  body?: unknown
  signal?: AbortSignal
}

/**
 * Abstraction du transport HTTP : les services en dépendent, jamais de fetch directement
 * (inversion de dépendance ; un faux client suffit pour les tester).
 */
export interface HttpClient {
  request<T>(path: string, options?: HttpRequestOptions): Promise<T>
}

/** Client réel : API Laravel sous /api, cookie de session Sanctum et jeton CSRF. */
export const http: HttpClient = { request: api }
