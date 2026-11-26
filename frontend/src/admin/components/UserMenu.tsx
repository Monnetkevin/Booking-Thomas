import { useTranslation } from 'react-i18next'
import type { AuthUser } from '@/admin/auth/types'
import { LogoutIcon } from '@/components/icons'

/** Nom de la personne connectée et bouton de déconnexion, en bas de la barre latérale. */
export function UserMenu({ user, onLogout }: { user: AuthUser; onLogout: () => void }) {
  const { t } = useTranslation()

  return (
    <div className="flex items-center justify-between gap-2">
      <span className="truncate px-1 text-[13px] text-night-text">{t('admin.connectedAs', { name: user.name })}</span>
      <button
        type="button"
        onClick={onLogout}
        aria-label={t('admin.logout')}
        title={t('admin.logout')}
        className="flex size-11 shrink-0 items-center justify-center rounded-[10px] text-night-text hover:bg-night-soft hover:text-white"
      >
        <LogoutIcon />
      </button>
    </div>
  )
}
