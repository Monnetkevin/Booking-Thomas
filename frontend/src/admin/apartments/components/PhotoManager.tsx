import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Alert } from '@/components/ui/Alert'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Section } from '@/components/ui/Section'
import { Spinner } from '@/components/ui/Spinner'
import { ApiError } from '@/lib/api'
import { moveItem } from '@/lib/arrays'
import type { Photo } from '../types'
import { usePhotoActions } from '../useApartments'
import { PhotoCard } from './PhotoCard'
import { PhotoUploader } from './PhotoUploader'

function errorMessage(error: unknown, fallback: string): string | undefined {
  if (!error) return undefined
  if (error instanceof ApiError) {
    return Object.values(error.fieldErrors)[0]?.[0] ?? error.serverMessage ?? fallback
  }
  return fallback
}

/** Photos d'un logement : envoi, ordre (la première est la couverture), description, suppression. */
export function PhotoManager({ apartmentId, photos }: { apartmentId: number; photos: Photo[] }) {
  const { t } = useTranslation()
  const { upload, reorder, updateAlt, remove } = usePhotoActions(apartmentId)
  const [dragIndex, setDragIndex] = useState<number | null>(null)
  const [toDelete, setToDelete] = useState<Photo | null>(null)

  const move = (from: number, to: number) => {
    const ids = moveItem(photos.map((photo) => photo.id), from, to)
    reorder.mutate(ids)
  }

  const error = errorMessage(upload.error ?? reorder.error ?? updateAlt.error ?? remove.error, t('common.error'))
  const busy = reorder.isPending || remove.isPending

  return (
    <Section title={t('admin.apartments.photos.title')} description={t('admin.apartments.photos.description')}>
      <div className="flex flex-col gap-4">
        {error && <Alert tone="danger">{error}</Alert>}
        <PhotoUploader disabled={upload.isPending} onFiles={(files) => upload.mutate(files)} />
        {upload.isPending && (
          <div className="text-muted">
            <Spinner label={t('admin.apartments.photos.uploading', { count: upload.variables.length })} />
          </div>
        )}

        {photos.length === 0 ? (
          <p className="text-muted">{t('admin.apartments.photos.empty')}</p>
        ) : (
          <>
            <p className="text-sm text-muted">{t('admin.apartments.photos.reorderHint')}</p>
            <ol className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
              {photos.map((photo, index) => (
                <PhotoCard
                  key={photo.id}
                  photo={photo}
                  index={index}
                  total={photos.length}
                  busy={busy || (updateAlt.isPending && updateAlt.variables.photoId === photo.id)}
                  dragging={dragIndex === index}
                  onMove={(to) => move(index, to)}
                  onSaveAlt={(alt) => updateAlt.mutateAsync({ photoId: photo.id, alt })}
                  onDelete={() => setToDelete(photo)}
                  dragHandlers={{
                    onDragStart: (event) => {
                      event.dataTransfer.effectAllowed = 'move'
                      setDragIndex(index)
                    },
                    onDragOver: (event) => {
                      if (dragIndex !== null) event.preventDefault()
                    },
                    onDrop: (event) => {
                      event.preventDefault()
                      if (dragIndex !== null && dragIndex !== index) move(dragIndex, index)
                      setDragIndex(null)
                    },
                    onDragEnd: () => setDragIndex(null),
                  }}
                />
              ))}
            </ol>
          </>
        )}
      </div>

      <ConfirmDialog
        open={toDelete !== null}
        title={t('admin.apartments.photos.deleteConfirmTitle')}
        confirmLabel={t('admin.common.delete')}
        cancelLabel={t('admin.common.cancel')}
        tone="danger"
        pending={remove.isPending}
        onCancel={() => setToDelete(null)}
        onConfirm={() => toDelete && remove.mutate(toDelete.id, { onSettled: () => setToDelete(null) })}
      >
        {t('admin.apartments.photos.deleteConfirm')}
      </ConfirmDialog>
    </Section>
  )
}
