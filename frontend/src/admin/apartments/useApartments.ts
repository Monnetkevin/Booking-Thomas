import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { amenityService, type AmenityService } from './amenityService'
import { apartmentService, type ApartmentService } from './apartmentService'
import { apartmentKeys } from './keys'
import { photoService, type PhotoService } from './photoService'
import type { Apartment, ApartmentInput, ApartmentStatus, Photo } from './types'

/** Hooks React Query des logements ; chaque service est injectable pour les tests. */

export function useApartmentList(status?: ApartmentStatus, service: ApartmentService = apartmentService) {
  return useQuery({ queryKey: apartmentKeys.list(status), queryFn: () => service.list(status) })
}

export function useApartment(id: number, service: ApartmentService = apartmentService) {
  return useQuery({
    queryKey: apartmentKeys.detail(id),
    queryFn: () => service.get(id),
    // Tant qu'une photo ou la position sur la carte est calculée en tâche de fond, on revérifie.
    refetchInterval: (query) => {
      const data = query.state.data
      return data && (data.geocoding === 'pending' || data.photos.some((photo) => !photo.ready)) ? 3000 : false
    },
  })
}

/** Met la fiche à jour dans le cache et rafraîchit les listes. */
function useStoreApartment() {
  const queryClient = useQueryClient()

  return (apartment: Apartment) => {
    queryClient.setQueryData(apartmentKeys.detail(apartment.id), apartment)
    void queryClient.invalidateQueries({ queryKey: [...apartmentKeys.all, 'list'] })
  }
}

export function useCreateApartment(service: ApartmentService = apartmentService) {
  const store = useStoreApartment()
  return useMutation({ mutationFn: (input: ApartmentInput) => service.create(input), onSuccess: store })
}

export function useUpdateApartment(id: number, service: ApartmentService = apartmentService) {
  const store = useStoreApartment()
  return useMutation({ mutationFn: (input: ApartmentInput) => service.update(id, input), onSuccess: store })
}

export function usePublication(id: number, service: ApartmentService = apartmentService) {
  const store = useStoreApartment()
  return useMutation({
    mutationFn: (publish: boolean) => (publish ? service.publish(id) : service.unpublish(id)),
    onSuccess: store,
  })
}

export function useDeleteApartment(id: number, service: ApartmentService = apartmentService) {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: () => service.remove(id),
    onSuccess: () => {
      queryClient.removeQueries({ queryKey: apartmentKeys.detail(id) })
      void queryClient.invalidateQueries({ queryKey: [...apartmentKeys.all, 'list'] })
    },
  })
}

export function useReorderApartments(service: ApartmentService = apartmentService) {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (ids: number[]) => service.reorder(ids),
    onSettled: () => queryClient.invalidateQueries({ queryKey: [...apartmentKeys.all, 'list'] }),
  })
}

export function useAmenityCatalog(service: AmenityService = amenityService) {
  return useQuery({ queryKey: apartmentKeys.amenities, queryFn: () => service.catalog(), staleTime: Infinity })
}

export function useSyncAmenities(id: number, service: AmenityService = amenityService) {
  const store = useStoreApartment()
  return useMutation({ mutationFn: (ids: number[]) => service.sync(id, ids), onSuccess: store })
}

/** Remplace les photos dans la fiche en cache (ordre, ajout, suppression). */
function useStorePhotos(apartmentId: number) {
  const queryClient = useQueryClient()

  return (update: (photos: Photo[]) => Photo[]) => {
    queryClient.setQueryData<Apartment>(apartmentKeys.detail(apartmentId), (apartment) =>
      apartment ? { ...apartment, photos: update(apartment.photos) } : apartment,
    )
    void queryClient.invalidateQueries({ queryKey: [...apartmentKeys.all, 'list'] })
  }
}

export function usePhotoActions(apartmentId: number, service: PhotoService = photoService) {
  const storePhotos = useStorePhotos(apartmentId)
  const queryClient = useQueryClient()

  const upload = useMutation({
    mutationFn: (files: File[]) => service.upload(apartmentId, files),
    onSuccess: (added) => storePhotos((photos) => [...photos, ...added]),
  })
  const reorder = useMutation({
    mutationFn: (ids: number[]) => service.reorder(apartmentId, ids),
    // Ordre affiché tout de suite, corrigé par la réponse ou annulé en cas d'erreur.
    onMutate: (ids) =>
      storePhotos((photos) => ids.map((id) => photos.find((photo) => photo.id === id)).filter((p): p is Photo => !!p)),
    onSuccess: (photos) => storePhotos(() => photos),
    onError: () => queryClient.invalidateQueries({ queryKey: apartmentKeys.detail(apartmentId) }),
  })
  const updateAlt = useMutation({
    mutationFn: ({ photoId, alt }: { photoId: number; alt: Photo['alt'] }) =>
      service.updateAlt(apartmentId, photoId, alt),
    onSuccess: (updated) => storePhotos((photos) => photos.map((photo) => (photo.id === updated.id ? updated : photo))),
  })
  const remove = useMutation({
    mutationFn: (photoId: number) => service.remove(apartmentId, photoId),
    onSuccess: (_, photoId) => storePhotos((photos) => photos.filter((photo) => photo.id !== photoId)),
  })

  return { upload, reorder, updateAlt, remove }
}
