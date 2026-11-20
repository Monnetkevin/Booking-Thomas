import { useTranslation } from 'react-i18next'
import type { Role } from '@/admin/auth/types'
import { CheckIcon } from '@/components/icons'

type RightKey = 'bookings' | 'apartments' | 'messages' | 'rates' | 'settings' | 'housekeeping'

/** Droits de chaque rôle, tels que l'API les applique (routes role: et Policies). */
const RIGHTS: { key: RightKey; roles: Role[] }[] = [
  { key: 'bookings', roles: ['admin', 'manager'] },
  { key: 'apartments', roles: ['admin', 'manager'] },
  { key: 'messages', roles: ['admin', 'manager'] },
  { key: 'rates', roles: ['admin'] },
  { key: 'settings', roles: ['admin'] },
  { key: 'housekeeping', roles: ['admin', 'manager', 'housekeeping'] },
]
const ROLES: Role[] = ['admin', 'manager', 'housekeeping']

/** Tableau en lecture seule des droits par rôle. */
export function RoleRightsTable() {
  const { t } = useTranslation()

  return (
    <div className="overflow-x-auto">
      <table className="w-full min-w-[480px] text-left text-sm">
        <thead>
          <tr className="border-b border-border text-xs tracking-wide text-muted uppercase">
            <th scope="col" className="py-2 pr-3 font-semibold">{t('admin.users.rights.right')}</th>
            {ROLES.map((role) => (
              <th key={role} scope="col" className="px-3 py-2 text-center font-semibold">{t(`admin.users.roles.${role}`)}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {RIGHTS.map((right) => (
            <tr key={right.key} className="border-b border-line last:border-0">
              <th scope="row" className="py-2.5 pr-3 font-medium">{t(`admin.users.rights.rows.${right.key}`)}</th>
              {ROLES.map((role) => (
                <td key={role} className="px-3 py-2.5 text-center">
                  {right.roles.includes(role) ? (
                    <span role="img" aria-label={t('admin.users.rights.yes')} className="inline-flex text-success">
                      <CheckIcon width={18} height={18} />
                    </span>
                  ) : (
                    <span className="text-muted" aria-label={t('admin.users.rights.no')} role="img">—</span>
                  )}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
