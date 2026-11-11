import { useEffect, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { CloseIcon } from '@/components/icons'
import type { PublicPhoto } from '@/site/catalog/types'
import { Picture } from './Picture'

/** Mosaïque de 5 photos (une seule sur téléphone) et galerie complète en plein écran. */
export function PhotoGallery({ photos, name }: { photos: PublicPhoto[]; name: string }) {
  const { t } = useTranslation()
  const dialogRef = useRef<HTMLDialogElement>(null)
  const [open, setOpen] = useState(false)

  useEffect(() => {
    const dialog = dialogRef.current
    if (!dialog) return
    if (open && !dialog.open) dialog.showModal()
    if (!open && dialog.open) dialog.close()
  }, [open])

  if (photos.length === 0) return <div className="aspect-[16/9] rounded-[16px] bg-cream" />

  const [main, ...others] = photos
  const tiles = others.slice(0, 4)

  return (
    <section aria-label={t('detail.photosTitle', { name })} className="relative">
      <div className="grid gap-3 overflow-hidden rounded-[16px] sm:h-[432px] sm:grid-cols-[2fr_1fr_1fr] sm:grid-rows-2">
        <div className="aspect-[4/3] sm:row-span-2 sm:aspect-auto">
          {main && <Picture photo={main} sizes="(min-width: 640px) 600px, 100vw" priority alt={main.alt || name} />}
        </div>
        {tiles.map((photo) => (
          <div key={photo.id} className="hidden sm:block">
            <Picture photo={photo} sizes="300px" alt={photo.alt || name} />
          </div>
        ))}
      </div>
      {photos.length > 1 && (
        <button
          type="button"
          onClick={() => setOpen(true)}
          className="absolute right-3 bottom-3 min-h-11 rounded-[10px] border border-field bg-white px-3.5 text-sm font-semibold text-ink hover:bg-sand"
        >
          {t('detail.allPhotosCount', { count: photos.length })}
        </button>
      )}

      <dialog
        ref={dialogRef}
        aria-label={t('detail.photosTitle', { name })}
        onClose={() => setOpen(false)}
        className="m-0 h-dvh max-h-none w-screen max-w-none bg-paper p-0 backdrop:bg-night/70"
      >
        <div className="sticky top-0 z-10 flex items-center justify-between border-b border-line bg-paper/95 px-4 py-2 backdrop-blur">
          <h2 className="text-lg font-semibold">{t('detail.photosTitle', { name })}</h2>
          <button
            type="button"
            onClick={() => setOpen(false)}
            aria-label={t('common.close')}
            className="flex size-11 items-center justify-center rounded-full hover:bg-sand"
          >
            <CloseIcon />
          </button>
        </div>
        {open && (
          <ul className="mx-auto flex max-w-4xl flex-col gap-4 p-4">
            {photos.map((photo, index) => (
              <li key={photo.id} className="overflow-hidden rounded-[12px]">
                <Picture
                  photo={photo}
                  sizes="(min-width: 900px) 896px, 100vw"
                  className="h-auto"
                  alt={photo.alt || `${name} (${index + 1}/${photos.length})`}
                />
              </li>
            ))}
          </ul>
        )}
      </dialog>
    </section>
  )
}
