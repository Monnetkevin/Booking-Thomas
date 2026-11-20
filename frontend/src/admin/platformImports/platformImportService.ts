import { http, type HttpClient } from '@/lib/http'
import type { ImportPreview, PayoutPlatform, PlatformImport } from './types'

export interface PlatformImportService {
  history(): Promise<PlatformImport[]>
  preview(platform: PayoutPlatform, file: File): Promise<ImportPreview>
  /** mapping : annonce → logement. */
  import(platform: PayoutPlatform, file: File, mapping: Record<string, number>): Promise<PlatformImport>
}

function form(platform: PayoutPlatform, file: File, mapping: Record<string, number> = {}): FormData {
  const data = new FormData()
  data.append('platform', platform)
  data.append('file', file)
  Object.entries(mapping).forEach(([listing, apartmentId], index) => {
    data.append(`mapping[${index}][listing]`, listing)
    data.append(`mapping[${index}][apartment_id]`, String(apartmentId))
  })
  return data
}

/** Import des revenus des plateformes (fichiers CSV), sans React ni cache. */
export function createPlatformImportService(client: HttpClient): PlatformImportService {
  const unwrap = async <T>(promise: Promise<{ data: T }>) => (await promise).data
  return {
    history: () => unwrap(client.request('/admin/platform-imports')),
    preview: (platform, file) => unwrap(client.request('/admin/platform-imports/preview', { method: 'POST', body: form(platform, file) })),
    import: (platform, file, mapping) => unwrap(client.request('/admin/platform-imports', { method: 'POST', body: form(platform, file, mapping) })),
  }
}

export const platformImportService = createPlatformImportService(http)
