import { useLayoutEffect } from 'react'
import { useTranslation } from 'react-i18next'
import { Navigate, useNavigate, useSearchParams } from 'react-router'
import { safeAdminRedirect } from '@/admin/auth/redirect'
import { useCurrentUser, useLogin } from '@/admin/auth/useAuth'
import { LoginForm } from '@/admin/components/LoginForm'
import { FullPageSpinner } from '@/components/feedback/FullPageSpinner'
import { Logo } from '@/components/Logo'
import { Card } from '@/components/ui/Card'
import { applyLocale } from '@/i18n'
import { ApiError } from '@/lib/api'

/** Erreur à afficher sous le champ e-mail (identifiants refusés, blocage) ou en tête. */
function loginErrors(error: unknown, fallback: string): { emailError?: string; generalError?: string } {
  if (!error) return {}
  if (error instanceof ApiError && (error.status === 422 || error.status === 429)) {
    const emailError = error.fieldErrors.email?.[0]
    if (emailError) return { emailError }
  }
  return { generalError: fallback }
}

export function LoginPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [params] = useSearchParams()
  const redirect = safeAdminRedirect(params.get('redirect'))
  const currentUser = useCurrentUser()
  const login = useLogin()

  useLayoutEffect(() => applyLocale('fr'), [])

  if (currentUser.isPending) return <FullPageSpinner />
  if (currentUser.data) return <Navigate to={redirect} replace />

  return (
    <main className="grid min-h-dvh place-items-center bg-sand px-4 py-10">
      <div className="w-full max-w-sm">
        <Logo className="mb-6 justify-center" />
        <Card>
          <h1 className="text-2xl font-semibold">{t('admin.login.title')}</h1>
          <p className="mt-1 mb-5 text-sm text-muted">{t('admin.login.intro')}</p>
          <LoginForm
            pending={login.isPending}
            {...loginErrors(login.error, t('common.error'))}
            onSubmit={(input) => login.mutate(input, { onSuccess: () => navigate(redirect, { replace: true }) })}
          />
        </Card>
      </div>
    </main>
  )
}
