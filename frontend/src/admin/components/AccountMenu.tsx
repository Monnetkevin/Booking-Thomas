import { useCallback, useId, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { NavLink } from 'react-router'
import type { AuthUser } from '@/admin/auth/types'
import type { AdminNavItem } from '@/admin/navigation'
import { ChevronDownIcon, LogoutIcon } from '@/components/icons'
import { useDismiss } from '@/components/ui/useDismiss'
import { cn } from '@/lib/cn'
import { initials } from './initials'

interface AccountMenuProps {
  user: AuthUser
  /** Entrées du compte et du site autorisées au rôle (réglages, utilisateurs, e-mails). */
  items: AdminNavItem[]
  onLogout: () => void
}

/** Menu du compte, en haut à droite : identité, réglages du site, déconnexion. */
export function AccountMenu({ user, items, onLogout }: AccountMenuProps) {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)
  const rootRef = useRef<HTMLDivElement>(null)
  const buttonRef = useRef<HTMLButtonElement>(null)
  const menuId = useId()
  const close = useCallback(() => setOpen(false), [])
  useDismiss(open, rootRef, close, buttonRef)

  return (
    <div ref={rootRef} className="relative">
      <button
        ref={buttonRef}
        type="button"
        aria-expanded={open}
        aria-controls={menuId}
        aria-label={t('admin.account.label', { name: user.name })}
        onClick={() => setOpen((value) => !value)}
        className="flex min-h-11 items-center gap-2.5 rounded-xl px-1 hover:bg-sand"
      >
        <span aria-hidden="true" className="flex size-9 items-center justify-center rounded-full bg-accent text-sm font-bold text-white">
          {initials(user.name)}
        </span>
        <span className="hidden flex-col items-start leading-tight md:flex">
          <strong className="text-sm">{user.name}</strong>
          <span className="text-xs text-muted">{t(`admin.users.roles.${user.role}`)}</span>
        </span>
        <ChevronDownIcon width={16} height={16} />
      </button>

      {open && (
        <div
          id={menuId}
          className="absolute top-full right-0 z-40 mt-2 w-72 rounded-[var(--radius-card)] border border-border bg-white p-2 shadow-xl"
        >
          <div className="flex items-center gap-3 p-2.5">
            <span aria-hidden="true" className="flex size-10 items-center justify-center rounded-full bg-accent font-bold text-white">
              {initials(user.name)}
            </span>
            <span className="flex min-w-0 flex-col leading-tight">
              <strong className="truncate text-sm">{user.name}</strong>
              <span className="truncate text-xs text-muted">{user.email}</span>
            </span>
          </div>
          {items.length > 0 && (
            <ul className="border-t border-line pt-1">
              {items.map((item) => (
                <li key={item.to}>
                  <NavLink
                    to={item.to}
                    end
                    onClick={close}
                    className={({ isActive }) =>
                      cn(
                        'flex min-h-11 items-center gap-3 rounded-[10px] px-2.5 text-sm font-semibold text-ink no-underline hover:bg-sand hover:text-ink [&>svg]:text-muted',
                        isActive && 'bg-accent-soft',
                      )
                    }
                  >
                    {item.icon}
                    {t(item.labelKey)}
                  </NavLink>
                </li>
              ))}
            </ul>
          )}
          <div className="mt-1 border-t border-line pt-1">
            <button
              type="button"
              onClick={onLogout}
              className="flex min-h-11 w-full items-center gap-3 rounded-[10px] px-2.5 text-sm font-bold text-danger hover:bg-danger-soft"
            >
              <LogoutIcon />
              {t('admin.logout')}
            </button>
          </div>
        </div>
      )}
    </div>
  )
}
