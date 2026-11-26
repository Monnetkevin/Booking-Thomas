import { api, apiDownload } from './api'

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

/** Fichier reçu de l'API (PDF, CSV…) et le nom proposé par le serveur. */
export interface DownloadedFile {
  blob: Blob
  filename: string | null
}

/**
 * Téléchargement de fichiers par le même transport que l'API (session, erreurs en ApiError,
 * 401 traité comme une session expirée) ; interface séparée, seuls les services qui
 * téléchargent en dépendent.
 */
export interface FileDownloader {
  download(path: string): Promise<DownloadedFile>
}

/** Client réel : API Laravel sous /api, cookie de session Sanctum et jeton CSRF. */
export const http: HttpClient & FileDownloader = { request: api, download: apiDownload }
