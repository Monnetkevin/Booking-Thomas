import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/components/ui/Button'
import { Dialog } from '@/components/ui/Dialog'
import { TextArea } from '@/components/ui/Field'

interface ReviewTextDialogProps {
  open: boolean
  title: string
  label: string
  hint: string
  submitLabel: string
  initial?: string
  loading: boolean
  onSubmit: (text: string) => void
  onClose: () => void
}

/** Petit formulaire d'un texte libre sur un avis : motif de refus ou réponse publique. */
export function ReviewTextDialog({ open, title, label, hint, submitLabel, initial = '', loading, onSubmit, onClose }: ReviewTextDialogProps) {
  const { t } = useTranslation()
  const [text, setText] = useState(initial)

  return (
    <Dialog
      open={open}
      title={title}
      onClose={onClose}
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>
            {t('admin.common.cancel')}
          </Button>
          <Button loading={loading} onClick={() => onSubmit(text)}>
            {submitLabel}
          </Button>
        </>
      }
    >
      <TextArea label={label} hint={hint} rows={5} maxLength={1000} value={text} onChange={(event) => setText(event.target.value)} />
    </Dialog>
  )
}
