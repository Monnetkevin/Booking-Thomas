import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { ASSIGNABLE_ROLES, type AssignableRole } from '@/admin/auth/types'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Select } from '@/components/ui/Select'
import { ApiError } from '@/lib/api'
import { formatDateTime } from '@/lib/dates'
import type { Account } from '../types'
import { useSendPasswordReset, useSetAccountActive, useSetAccountRole } from '../useUsers'

/** Un compte : rôle (modifiable sauf administrateur), état, dernière connexion, activation et réinitialisation du mot de passe. */
export function AccountRow({ account }: { account: Account }) {
  const { t } = useTranslation()
  const [confirming, setConfirming] = useState(false)
  const setActive = useSetAccountActive()
  const reset = useSendPasswordReset()
  const setRole = useSetAccountRole()
  const roleLabelId = `role-${account.id}`
  const error = [setActive.error, setRole.error].find((value) => value instanceof ApiError && value.serverMessage) as ApiError | undefined

  const toggle = () =>
    setActive.mutate({ id: account.id, active: !account.is_active }, { onSettled: () => setConfirming(false) })

  return (
    <li className="flex flex-wrap items-center justify-between gap-3 py-4">
      <div className="min-w-0">
        <p className="flex flex-wrap items-center gap-2 font-semibold">
          {account.name}
          <Badge tone={account.role === 'housekeeping' ? 'neutral' : 'accent'}>{t(`admin.users.roles.${account.role}`)}</Badge>
          {!account.is_active && <Badge tone="warning">{t('admin.users.inactive')}</Badge>}
          {account.is_self && <span className="text-sm font-normal text-muted">{t('admin.users.you')}</span>}
        </p>
        <p className="truncate text-sm text-muted">{account.email}</p>
        <p className="text-xs text-muted">
          {account.last_login_at ? t('admin.users.lastLogin', { when: formatDateTime(account.last_login_at) }) : t('admin.users.neverLoggedIn')}
        </p>
        {error && (
          <p className="mt-1 text-sm text-danger" role="alert">
            {error.serverMessage}
          </p>
        )}
        {reset.isSuccess && (
          <p className="mt-1 text-sm text-success" role="status">
            {t('admin.users.resetSent', { email: account.email })}
          </p>
        )}
      </div>
      {!account.is_self && (
        <div className="flex flex-wrap items-center gap-2">
          {account.role !== 'admin' && (
            <>
              <span id={roleLabelId} className="sr-only">
                {t('admin.users.changeRole', { name: account.name })}
              </span>
              <Select
                aria-labelledby={roleLabelId}
                className="w-44"
                value={account.role}
                disabled={setRole.isPending}
                options={ASSIGNABLE_ROLES.map((value) => ({ value, label: t(`admin.users.roles.${value}`) }))}
                onChange={(value) => value !== account.role && setRole.mutate({ id: account.id, role: value as AssignableRole })}
              />
            </>
          )}
          <Button variant="ghost" onClick={() => reset.mutate(account.id)} loading={reset.isPending} disabled={!account.is_active}>
            {t('admin.users.sendReset')}
          </Button>
          <Button variant="secondary" onClick={() => (account.is_active ? setConfirming(true) : toggle())} loading={setActive.isPending}>
            {account.is_active ? t('admin.users.deactivate') : t('admin.users.activate')}
          </Button>
        </div>
      )}
      <ConfirmDialog
        open={confirming}
        title={t('admin.users.deactivateTitle', { name: account.name })}
        confirmLabel={t('admin.users.deactivate')}
        cancelLabel={t('admin.common.cancel')}
        tone="danger"
        pending={setActive.isPending}
        onCancel={() => setConfirming(false)}
        onConfirm={toggle}
      >
        {t('admin.users.deactivateConfirm')}
      </ConfirmDialog>
    </li>
  )
}
