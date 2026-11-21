import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createPlatformImportService } from './platformImportService'

describe('service d’import des revenus des plateformes', () => {
  it('envoie le fichier et le rattachement des annonces en formulaire', async () => {
    const client: HttpClient = { request: vi.fn(async <T,>() => ({ data: {} }) as T) as HttpClient['request'] }
    const file = new File(['a;b'], 'export.csv', { type: 'text/csv' })

    await createPlatformImportService(client).import('airbnb', file, { 'Studio Cocon': 3 })

    const [path, options] = vi.mocked(client.request).mock.calls[0] ?? []
    expect(path).toBe('/admin/platform-imports')
    const body = options?.body as FormData
    expect(body.get('platform')).toBe('airbnb')
    expect(body.get('file')).toBeInstanceOf(File)
    expect(body.get('mapping[0][listing]')).toBe('Studio Cocon')
    expect(body.get('mapping[0][apartment_id]')).toBe('3')
  })
})
