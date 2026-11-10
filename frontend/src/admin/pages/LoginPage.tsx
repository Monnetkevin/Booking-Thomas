import { useLayoutEffect, useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Navigate, useNavigate, useSearchParams } from 'react-router'
import { safeAdminRedirect, useCurrentUser, useLogin } from '@/admin/auth'
import { Logo } from '@/components/Logo'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { Checkbox, TextField } from '@/components/ui/Field'
import { Spinner } from '@/components/ui/Spinner'
import { applyLocale } from '@/i18n'
import { ApiError } from '@/lib/api'

export function LoginPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [params] = useSearchParams()
  const redirect = safeAdminRedirect(params.get('redirect'))
  const currentUser = useCurrentUser()
  const login = useLogin()
  const [form, setForm] = useState({ email: '', password: '', remember: false })

  useLayoutEffect(() => applyLocale('fr'), [])

  if (currentUser.isPending) {
    return (
      <div className="grid min-h-dvh place-items-center bg-sand text-muted">
        <Spinner label={t('common.loading')} />
      </div>
    )
  }
  if (currentUser.data) return <Navigate to={redirect} replace />

  const error = login.error
  const emailError =
    error instanceof ApiError && (error.status === 422 || error.status === 429)
      ? error.fieldErrors.email?.[0]
      : undefined
  const genericError = error && !emailError ? t('common.error') : undefined

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    login.mutate(form, { onSuccess: () => navigate(redirect, { replace: true }) })
  }

  return (
    <main className="grid min-h-dvh place-items-center bg-sand px-4 py-10">
      <div className="w-full max-w-sm">
        <Logo className="mb-6 justify-center" />
        <Card>
          <h1 className="text-2xl font-semibold">{t('admin.login.title')}</h1>
          <p className="mt-1 mb-5 text-sm text-muted">{t('admin.login.intro')}</p>

          <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-4">
            {genericError && <Alert tone="danger">{genericError}</Alert>}
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
            <Button type="submit" size="lg" loading={login.isPending}>
              {login.isPending ? t('admin.login.submitting') : t('admin.login.submit')}
            </Button>
          </form>
        </Card>
      </div>
    </main>
  )
}
