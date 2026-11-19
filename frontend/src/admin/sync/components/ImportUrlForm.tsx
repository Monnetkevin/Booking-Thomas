import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Checkbox, TextField } from '@/components/ui/Field'
import { ApiError } from '@/lib/api'
import type { IcalFeed, IcalFeedInput } from '../types'
import { useSaveIcalFeed } from '../useSync'

/**
 * URL d'import du calendrier Airbnb. Enregistrer crée le calendrier du logement s'il
 * n'existe pas (même sans URL : l'export du site fonctionne seul).
 */
export function ImportUrlForm({ apartmentId, feed }: { apartmentId: number; feed?: IcalFeed }) {
  const { t } = useTranslation()
  const [url, setUrl] = useState(feed?.import_url ?? '')
  const save = useSaveIcalFeed(apartmentId)

  const submit = (input: IcalFeedInput) => save.mutate({ feedId: feed?.id ?? null, provider: feed?.provider ?? 'airbnb', input })
  const onSubmit = (event: FormEvent) => {
    event.preventDefault()
    submit({ import_url: url.trim() || null })
  }

  const fieldError = save.error instanceof ApiError ? save.error.fieldErrors.import_url?.[0] : undefined
  const otherError = save.isError && !fieldError ? (save.error instanceof ApiError && save.error.serverMessage) || t('common.error') : null

  return (
    <form onSubmit={onSubmit} className="space-y-3" noValidate>
      <TextField
        label={t('admin.sync.import.label')}
        type="url"
        inputMode="url"
        placeholder={t('admin.sync.import.placeholder')}
        hint={t('admin.sync.import.hint')}
        value={url}
        error={fieldError}
        onChange={(event) => setUrl(event.target.value)}
        className="[&_input]:font-mono [&_input]:text-sm"
      />
      {feed?.import_url && (
        <>
          <Checkbox
            label={t('admin.sync.import.active')}
            checked={feed.is_active}
            disabled={save.isPending}
            onChange={(event) => submit({ is_active: event.target.checked })}
          />
          {!feed.is_active && <p className="text-sm text-muted">{t('admin.sync.import.inactiveHint')}</p>}
        </>
      )}
      {otherError && <Alert tone="danger">{otherError}</Alert>}
      {save.isSuccess && <Alert tone="success">{t('admin.sync.import.saved')}</Alert>}
      <Button type="submit" variant="secondary" loading={save.isPending}>
        {t('admin.sync.import.save')}
      </Button>
    </form>
  )
}
