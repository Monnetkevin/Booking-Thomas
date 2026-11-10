import { http, type HttpClient } from '@/lib/http'
import type { Photo } from './types'

export interface PhotoService {
  list(apartmentId: number): Promise<Photo[]>
  upload(apartmentId: number, files: File[]): Promise<Photo[]>
  reorder(apartmentId: number, ids: number[]): Promise<Photo[]>
  updateAlt(apartmentId: number, photoId: number, alt: Photo['alt']): Promise<Photo>
  remove(apartmentId: number, photoId: number): Promise<void>
}

/** Photos d'un logement ; la première de la liste est la couverture. */
export function createPhotoService(client: HttpClient): PhotoService {
  const base = (apartmentId: number) => `/admin/apartments/${apartmentId}/photos`
  const unwrap = async <T>(promise: Promise<{ data: T }>) => (await promise).data

  return {
    list: (apartmentId) => unwrap(client.request(base(apartmentId))),
    upload: (apartmentId, files) => {
      const form = new FormData()
      files.forEach((file) => form.append('photos[]', file))
      return unwrap(client.request(base(apartmentId), { method: 'POST', body: form }))
    },
    reorder: (apartmentId, ids) => unwrap(client.request(`${base(apartmentId)}/order`, { method: 'PUT', body: { ids } })),
    updateAlt: (apartmentId, photoId, alt) =>
      unwrap(client.request(`${base(apartmentId)}/${photoId}`, { method: 'PATCH', body: { alt } })),
    remove: (apartmentId, photoId) => client.request<void>(`${base(apartmentId)}/${photoId}`, { method: 'DELETE' }),
  }
}

export const photoService = createPhotoService(http)
