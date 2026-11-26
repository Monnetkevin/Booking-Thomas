import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import type { LoginInput } from '@/admin/auth/types'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Checkbox, TextField } from '@/components/ui/Field'

interface LoginFormProps {
  onSubmit: (input: LoginInput) => void
  pending: boolean
  emailError?: string
  generalError?: string
}

/** Formulaire de connexion : saisie et affichage seulement, sans appel d'API. */
export function LoginForm({ onSubmit, pending, emailError, generalError }: LoginFormProps) {
  const { t } = useTranslation()
  const [form, setForm] = useState<LoginInput>({ email: '', password: '', remember: false })

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    onSubmit(form)
  }

  return (
    <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-4">
      {generalError && <Alert tone="danger">{generalError}</Alert>}
      <TextField
        label={t('admin.login.email')}
        type="email"
        name="email"
        autoComplete="username"
        required
        autoFocus
        value={form.email}
        error={emailError}
        onChange={(event) => setForm({ ...form, email: event.target.value })}
      />
      <TextField
        label={t('admin.login.password')}
        type="password"
        name="password"
        autoComplete="current-password"
        required
        value={form.password}
        onChange={(event) => setForm({ ...form, password: event.target.value })}
      />
      <Checkbox
        label={t('admin.login.remember')}
        checked={form.remember}
        onChange={(event) => setForm({ ...form, remember: event.target.checked })}
      />
      <Button type="submit" size="lg" loading={pending}>
        {pending ? t('admin.login.submitting') : t('admin.login.submit')}
      </Button>
    </form>
  )
}
