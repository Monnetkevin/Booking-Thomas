import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { ApiError } from '@/lib/api'
import type { Account } from '../types'
import { useSendPasswordReset, useSetAccountActive } from '../useUsers'

/** Un compte : rôle, état, activation et lien de mot de passe. */
export function AccountRow({ account }: { account: Account }) {
  const { t } = useTranslation()
  const [confirming, setConfirming] = useState(false)
  const setActive = useSetAccountActive()
  const reset = useSendPasswordReset()

  const toggle = () =>
    setActive.mutate({ id: account.id, active: !account.is_active }, { onSettled: () => setConfirming(false) })

  return (
    <li className="flex flex-wrap items-center justify-between gap-3 py-4">
      <div className="min-w-0">
        <p className="flex flex-wrap items-center gap-2 font-semibold">
          {account.name}
          <Badge tone={account.role === 'admin' ? 'accent' : 'neutral'}>{t(`admin.users.roles.${account.role}`)}</Badge>
          {!account.is_active && <Badge tone="warning">{t('admin.users.inactive')}</Badge>}
          {account.is_self && <span className="text-sm font-normal text-muted">{t('admin.users.you')}</span>}
        </p>
        <p className="truncate text-sm text-muted">{account.email}</p>
        {setActive.error instanceof ApiError && setActive.error.serverMessage && (
          <p className="mt-1 text-sm text-danger" role="alert">
            {setActive.error.serverMessage}
          </p>
        )}
        {reset.isSuccess && (
          <p className="mt-1 text-sm text-success" role="status">
            {t('admin.users.resetSent', { email: account.email })}
          </p>
        )}
      </div>
      {!account.is_self && (
        <div className="flex flex-wrap gap-2">
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
