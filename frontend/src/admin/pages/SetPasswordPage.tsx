import { useLayoutEffect, useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useSearchParams } from 'react-router'
import { useResetPassword } from '@/admin/auth/useAuth'
import { Logo } from '@/components/Logo'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { Card } from '@/components/ui/Card'
import { TextField } from '@/components/ui/Field'
import { applyLocale } from '@/i18n'
import { ApiError } from '@/lib/api'

/** Choix du mot de passe depuis le lien d'invitation ou de réinitialisation (valable 24 h). */
export function SetPasswordPage() {
  const { t } = useTranslation()
  const [params] = useSearchParams()
  const token = params.get('token') ?? ''
  const email = params.get('email') ?? ''
  const [password, setPassword] = useState('')
  const [confirmation, setConfirmation] = useState('')
  const reset = useResetPassword()
  const errors = reset.error instanceof ApiError ? reset.error.fieldErrors : {}

  useLayoutEffect(() => applyLocale('fr'), [])

  const submit = (event: FormEvent) => {
    event.preventDefault()
    reset.mutate({ token, email, password, password_confirmation: confirmation })
  }

  return (
    <main className="grid min-h-dvh place-items-center bg-sand px-4 py-10">
      <div className="w-full max-w-sm">
        <Logo className="mb-6 justify-center" />
        <Card>
          <h1 className="text-2xl font-semibold">{t('admin.password.title')}</h1>
          {!token || !email ? (
            <div className="mt-4">
              <Alert tone="danger">{t('admin.password.missingLink')}</Alert>
            </div>
          ) : reset.isSuccess ? (
            <div className="mt-4 space-y-4">
              <Alert tone="success">{t('admin.password.done')}</Alert>
              <Link to="/admin/connexion" className={buttonClasses('primary', 'md', 'w-full')}>
                {t('admin.login.submit')}
              </Link>
            </div>
          ) : (
            <form onSubmit={submit} className="mt-4 space-y-4" noValidate>
              <p className="text-sm text-muted">{t('admin.password.intro', { email })}</p>
              {errors.token?.[0] && <Alert tone="danger">{errors.token[0]}</Alert>}
              <TextField
                label={t('admin.password.new')}
                type="password"
                autoComplete="new-password"
                hint={t('admin.password.rules')}
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                error={errors.password?.[0]}
                required
              />
              <TextField
                label={t('admin.password.confirm')}
                type="password"
                autoComplete="new-password"
                value={confirmation}
                onChange={(event) => setConfirmation(event.target.value)}
                required
              />
              {reset.isError && !(reset.error instanceof ApiError && reset.error.status === 422) && (
                <Alert tone="danger">{t('common.error')}</Alert>
              )}
              <Button type="submit" className="w-full" loading={reset.isPending}>
                {t('admin.password.submit')}
              </Button>
            </form>
          )}
        </Card>
      </div>
    </main>
  )
}
