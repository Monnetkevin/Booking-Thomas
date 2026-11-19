import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { TextField } from '@/components/ui/Field'
import { ApiError } from '@/lib/api'
import { useInviteAccount } from '../useUsers'

/** Invitation d'un compte ménage : un e-mail lui permet de choisir son mot de passe. */
export function InviteAccountForm() {
  const { t } = useTranslation()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const invite = useInviteAccount()
  const errors = invite.error instanceof ApiError ? invite.error.fieldErrors : {}

  const submit = (event: FormEvent) => {
    event.preventDefault()
    invite.mutate(
      { name, email },
      {
        onSuccess: () => {
          setName('')
          setEmail('')
        },
      },
    )
  }

  return (
    <form onSubmit={submit} className="space-y-4" noValidate>
      <div className="grid gap-4 sm:grid-cols-2">
        <TextField label={t('admin.users.name')} value={name} onChange={(event) => setName(event.target.value)} error={errors.name?.[0]} required autoComplete="off" />
        <TextField
          label={t('admin.users.email')}
          type="email"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          error={errors.email?.[0]}
          required
          autoComplete="off"
        />
      </div>
      {invite.isSuccess && <Alert tone="success">{t('admin.users.invited', { email: invite.data.email })}</Alert>}
      {invite.isError && !(invite.error instanceof ApiError && invite.error.status === 422) && <Alert tone="danger">{t('common.error')}</Alert>}
      <Button type="submit" loading={invite.isPending}>
        {t('admin.users.invite')}
      </Button>
    </form>
  )
}
