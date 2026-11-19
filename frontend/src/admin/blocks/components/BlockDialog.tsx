import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Dialog } from '@/components/ui/Dialog'
import { DateField, SelectField, TextField } from '@/components/ui/Field'
import { ApiError } from '@/lib/api'
import { useCreateBlock } from '../useBlocks'

interface BlockDialogProps {
  open: boolean
  onClose: () => void
  apartments: Array<{ id: number; name: string }>
  /** Valeurs proposées (clic sur une case du planning). */
  initial?: { apartmentId?: number; startsOn?: string; endsOn?: string }
  /** Aujourd'hui (« AAAA-MM-JJ ») : pas de fermeture dans le passé. */
  today: string
}

/** Fermer un logement (travaux, usage personnel) sur des nuits [début, fin[. */
export function BlockDialog({ open, onClose, apartments, initial, today }: BlockDialogProps) {
  const { t } = useTranslation()
  const [apartmentId, setApartmentId] = useState(String(initial?.apartmentId ?? apartments[0]?.id ?? ''))
  const [startsOn, setStartsOn] = useState(initial?.startsOn ?? '')
  const [endsOn, setEndsOn] = useState(initial?.endsOn ?? '')
  const [note, setNote] = useState('')
  const create = useCreateBlock()
  const errors = create.error instanceof ApiError ? create.error.fieldErrors : {}
  const conflict = create.error instanceof ApiError && create.error.code === 'dates_unavailable'

  const submit = (event: FormEvent) => {
    event.preventDefault()
    create.mutate(
      { apartmentId: Number(apartmentId), input: { starts_on: startsOn, ends_on: endsOn, note: note.trim() || null } },
      { onSuccess: onClose },
    )
  }

  return (
    <Dialog
      open={open}
      title={t('admin.blocks.title')}
      onClose={onClose}
      footer={
        <>
          <Button variant="secondary" onClick={onClose} disabled={create.isPending}>
            {t('admin.common.cancel')}
          </Button>
          <Button type="submit" form="block-form" loading={create.isPending} disabled={!startsOn || !endsOn}>
            {t('admin.blocks.submit')}
          </Button>
        </>
      }
    >
      <form id="block-form" onSubmit={submit} className="space-y-4" noValidate>
        <SelectField
          label={t('admin.blocks.apartment')}
          value={apartmentId}
          onChange={setApartmentId}
          options={apartments.map((apartment) => ({ value: String(apartment.id), label: apartment.name }))}
        />
        <div className="grid gap-4 sm:grid-cols-2">
          <DateField label={t('admin.blocks.startsOn')} value={startsOn} min={today} onChange={setStartsOn} error={errors.starts_on?.[0]} />
          <DateField label={t('admin.blocks.endsOn')} value={endsOn} min={startsOn || today} onChange={setEndsOn} error={errors.ends_on?.[0]} />
        </div>
        <TextField
          label={t('admin.blocks.note')}
          hint={t('admin.blocks.noteHint')}
          maxLength={120}
          value={note}
          onChange={(event) => setNote(event.target.value)}
          error={errors.note?.[0]}
        />
        {conflict && <Alert tone="danger">{t('admin.blocks.conflict')}</Alert>}
        {create.isError && !(create.error instanceof ApiError && [409, 422].includes(create.error.status)) && (
          <Alert tone="danger">{t('common.error')}</Alert>
        )}
      </form>
    </Dialog>
  )
}
