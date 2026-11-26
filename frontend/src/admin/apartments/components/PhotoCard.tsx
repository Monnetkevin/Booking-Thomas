import { useState, type DragEvent, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { ChevronDownIcon, ChevronUpIcon, PencilIcon, StarIcon, TrashIcon } from '@/components/icons'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { TextField } from '@/components/ui/Field'
import { cn } from '@/lib/cn'
import type { Photo } from '../types'

interface PhotoCardProps {
  photo: Photo
  index: number
  total: number
  busy: boolean
  onMove: (to: number) => void
  onSaveAlt: (alt: Photo['alt']) => Promise<unknown>
  onDelete: () => void
  dragHandlers: {
    onDragStart: (event: DragEvent<HTMLLIElement>) => void
    onDragOver: (event: DragEvent<HTMLLIElement>) => void
    onDrop: (event: DragEvent<HTMLLIElement>) => void
    onDragEnd: () => void
  }
  dragging: boolean
}

const iconButton =
  'flex size-11 items-center justify-center rounded-[10px] text-ink hover:bg-sand disabled:cursor-not-allowed disabled:opacity-40'

export function PhotoCard({ photo, index, total, busy, onMove, onSaveAlt, onDelete, dragHandlers, dragging }: PhotoCardProps) {
  const { t } = useTranslation()
  const [editing, setEditing] = useState(false)
  const [alt, setAlt] = useState(photo.alt)
  const number = index + 1
  const label = t('admin.apartments.photos.label', { index: number })

  async function handleSave(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    await onSaveAlt(alt)
    setEditing(false)
  }

  return (
    <li
      draggable={!editing}
      {...dragHandlers}
      className={cn(
        'flex flex-col overflow-hidden rounded-[var(--radius-card)] border border-border bg-white',
        dragging && 'opacity-50',
      )}
    >
      <div className="relative aspect-[4/3] bg-cream">
        {photo.ready && photo.urls.thumb.webp ? (
          <picture>
            {photo.urls.thumb.avif && <source srcSet={photo.urls.thumb.avif} type="image/avif" />}
            <img
              src={photo.urls.thumb.webp}
              alt={photo.alt.fr || label}
              width={480}
              height={360}
              className="h-full w-full cursor-grab object-cover"
            />
          </picture>
        ) : (
          <div className="flex h-full items-center justify-center text-sm text-muted">{t('admin.apartments.photos.processing')}</div>
        )}
        <span className="absolute top-2 left-2 flex gap-1.5">
          {index === 0 ? <Badge tone="accent">{t('admin.apartments.photos.cover')}</Badge> : <Badge>{number}</Badge>}
          {!photo.alt.fr && <Badge tone="warning">{t('admin.apartments.photos.altMissing')}</Badge>}
        </span>
      </div>

      {editing ? (
        <form onSubmit={handleSave} className="flex flex-col gap-3 p-3">
          <TextField
            label={t('admin.apartments.photos.altFr')}
            hint={t('admin.apartments.photos.altHint')}
            maxLength={200}
            value={alt.fr}
            onChange={(event) => setAlt({ ...alt, fr: event.target.value })}
            autoFocus
          />
          <TextField
            label={t('admin.apartments.photos.altEn')}
            maxLength={200}
            value={alt.en}
            onChange={(event) => setAlt({ ...alt, en: event.target.value })}
          />
          <div className="flex justify-end gap-2">
            <Button variant="ghost" onClick={() => { setAlt(photo.alt); setEditing(false) }}>
              {t('admin.common.cancel')}
            </Button>
            <Button type="submit" loading={busy}>
              {t('admin.apartments.photos.saveAlt')}
            </Button>
          </div>
        </form>
      ) : (
        <div className="flex items-center justify-between gap-1 p-1.5">
          <div className="flex">
            <button type="button" className={iconButton} disabled={busy || index === 0} onClick={() => onMove(index - 1)} aria-label={t('admin.apartments.photos.moveUp', { index: number })}>
              <ChevronUpIcon />
            </button>
            <button type="button" className={iconButton} disabled={busy || index === total - 1} onClick={() => onMove(index + 1)} aria-label={t('admin.apartments.photos.moveDown', { index: number })}>
              <ChevronDownIcon />
            </button>
            <button type="button" className={iconButton} disabled={busy || index === 0} onClick={() => onMove(0)} aria-label={`${t('admin.apartments.photos.makeCover')} (${label})`} title={t('admin.apartments.photos.makeCover')}>
              <StarIcon />
            </button>
          </div>
          <div className="flex">
            <button type="button" className={iconButton} onClick={() => setEditing(true)} aria-label={`${t('admin.apartments.photos.editAlt')} (${label})`} title={t('admin.apartments.photos.editAlt')}>
              <PencilIcon />
            </button>
            <button type="button" className={cn(iconButton, 'text-danger')} disabled={busy} onClick={onDelete} aria-label={t('admin.apartments.photos.delete', { index: number })}>
              <TrashIcon />
            </button>
          </div>
        </div>
      )}
    </li>
  )
}
