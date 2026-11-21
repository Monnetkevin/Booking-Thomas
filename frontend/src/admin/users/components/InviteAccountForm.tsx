import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { ASSIGNABLE_ROLES, type AssignableRole } from '@/admin/auth/types'
import { SelectField, TextField } from '@/components/ui/Field'
import { ApiError } from '@/lib/api'
import { useInviteAccount } from '../useUsers'

/** Invitation d'un compte gestionnaire ou ménage : un e-mail lui permet de choisir son mot de passe. */
export function InviteAccountForm() {
  const { t } = useTranslation()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [role, setRole] = useState<AssignableRole>('housekeeping')
  const invite = useInviteAccount()
  const errors = invite.error instanceof ApiError ? invite.error.fieldErrors : {}

  const submit = (event: FormEvent) => {
    event.preventDefault()
    invite.mutate(
      { name, email, role },
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
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
        <SelectField
          label={t('admin.users.role')}
          hint={t(`admin.users.roleHints.${role}`)}
          value={role}
          options={ASSIGNABLE_ROLES.map((value) => ({ value, label: t(`admin.users.roles.${value}`) }))}
          onChange={(value) => setRole(value as AssignableRole)}
          error={errors.role?.[0]}
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
