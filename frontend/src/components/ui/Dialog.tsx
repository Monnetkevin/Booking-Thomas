import { useEffect, useId, useRef, type ReactNode } from 'react'

interface DialogProps {
  open: boolean
  title: string
  onClose: () => void
  children: ReactNode
  /** Boutons du bas (Annuler, Enregistrer…). */
  footer?: ReactNode
  size?: 'md' | 'lg'
}

/**
 * Fenêtre modale (élément <dialog> natif : focus piégé, Échap pour fermer), pour un petit
 * formulaire ou un détail. Pour une simple confirmation, voir ConfirmDialog.
 */
export function Dialog({ open, title, onClose, children, footer, size = 'md' }: DialogProps) {
  const ref = useRef<HTMLDialogElement>(null)
  const titleId = useId()

  useEffect(() => {
    const dialog = ref.current
    if (!dialog) return
    if (open && !dialog.open) dialog.showModal()
    if (!open && dialog.open) dialog.close()
  }, [open])

  return (
    <dialog
      ref={ref}
      aria-labelledby={titleId}
      onCancel={(event) => {
        event.preventDefault()
        onClose()
      }}
      className={`m-auto w-[calc(100%-2rem)] ${size === 'lg' ? 'max-w-2xl' : 'max-w-md'} rounded-[var(--radius-card)] border border-border bg-white p-6 text-ink backdrop:bg-night/50`}
    >
      {open && (
        <>
          <h2 id={titleId} className="text-xl font-semibold">
            {title}
          </h2>
          <div className="mt-4">{children}</div>
          {footer && <div className="mt-6 flex flex-wrap justify-end gap-2.5">{footer}</div>}
        </>
      )}
    </dialog>
  )
}
