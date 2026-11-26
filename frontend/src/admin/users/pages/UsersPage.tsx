import { useTranslation } from 'react-i18next'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { Section } from '@/components/ui/Section'
import { Spinner } from '@/components/ui/Spinner'
import { AccountRow } from '../components/AccountRow'
import { InviteAccountForm } from '../components/InviteAccountForm'
import { RoleRightsTable } from '../components/RoleRightsTable'
import { useAccounts } from '../useUsers'

/** Comptes du back-office : équipe, invitation (ménage) et droits par rôle. */
export function UsersPage() {
  const { t } = useTranslation()
  const accounts = useAccounts()

  return (
    <>
      <PageHeader title={t('admin.users.title')} subtitle={t('admin.users.subtitle')} />
      <div className="space-y-5">
        <Section title={t('admin.users.listTitle')}>
          {accounts.isPending ? (
            <Spinner label={t('common.loading')} />
          ) : accounts.isError ? (
            <ErrorState onRetry={() => void accounts.refetch()} />
          ) : (
            <ul className="divide-y divide-border">
              {accounts.data.map((account) => (
                <AccountRow key={account.id} account={account} />
              ))}
            </ul>
          )}
        </Section>
        <Section title={t('admin.users.inviteTitle')} description={t('admin.users.inviteHint')}>
          <InviteAccountForm />
        </Section>
        <Section title={t('admin.users.rights.title')} description={t('admin.users.rights.description')}>
          <RoleRightsTable />
        </Section>
      </div>
    </>
  )
}
