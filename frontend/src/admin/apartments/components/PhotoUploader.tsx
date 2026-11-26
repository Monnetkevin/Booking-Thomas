import { useId, useRef, useState, type DragEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { UploadIcon } from '@/components/icons'
import { cn } from '@/lib/cn'

const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp']

/** Zone de dépôt + sélecteur de fichiers. Ne garde que les images acceptées. */
export function PhotoUploader({ onFiles, disabled }: { onFiles: (files: File[]) => void; disabled: boolean }) {
  const { t } = useTranslation()
  const inputRef = useRef<HTMLInputElement>(null)
  const hintId = useId()
  const [over, setOver] = useState(false)

  const send = (list: FileList | null) => {
    const files = Array.from(list ?? []).filter((file) => ACCEPTED.includes(file.type))
    if (files.length > 0) onFiles(files.slice(0, 10))
  }

  const handleDrop = (event: DragEvent<HTMLDivElement>) => {
    event.preventDefault()
    setOver(false)
    // Une photo de la grille déplacée ici n'est pas un fichier : on l'ignore.
    if (!disabled && event.dataTransfer.files.length > 0) send(event.dataTransfer.files)
  }

  return (
    <div
      onDragOver={(event) => {
        if (event.dataTransfer.types.includes('Files')) {
          event.preventDefault()
          setOver(true)
        }
      }}
      onDragLeave={() => setOver(false)}
      onDrop={handleDrop}
      className={cn(
        'flex flex-col items-center justify-center gap-2 rounded-[var(--radius-card)] border-2 border-dashed px-4 py-6 text-center',
        over ? 'border-accent bg-accent-soft' : 'border-field bg-sand',
      )}
    >
      <UploadIcon className="text-muted" />
      <p className="text-[15px]">
        {t('admin.apartments.photos.drop')}{' '}
        <button
          type="button"
          disabled={disabled}
          onClick={() => inputRef.current?.click()}
          aria-describedby={hintId}
          className="min-h-11 font-semibold text-accent underline underline-offset-4 hover:text-accent-strong disabled:opacity-60"
        >
          {t('admin.apartments.photos.choose')}
        </button>
      </p>
      <p id={hintId} className="text-sm text-muted">
        {t('admin.apartments.photos.formats')}
      </p>
      <input
        ref={inputRef}
        type="file"
        accept={ACCEPTED.join(',')}
        multiple
        hidden
        onChange={(event) => {
          send(event.target.files)
          event.target.value = ''
        }}
      />
    </div>
  )
}
