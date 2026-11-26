import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Card } from '@/components/ui/Card'
import { SegmentedControl } from '@/components/ui/SegmentedControl'
import { Spinner } from '@/components/ui/Spinner'
import { formatLongDate } from '@/lib/dates'
import { relativeDay } from '../cleaningFormat'
import { CleaningMovementRow } from '../components/CleaningMovementRow'
import { CLEANING_PERIODS, type CleaningPeriod } from '../types'
import { useCleaningSchedule } from '../useCleaningSchedule'

/** Planning du ménage, jour par jour : départs à nettoyer, arrivées, fermetures. Pensé pour le téléphone. */
export function CleaningPage() {
  const { t } = useTranslation()
  const [days, setDays] = useState<CleaningPeriod>(CLEANING_PERIODS[0])
  const schedule = useCleaningSchedule(days)

  return (
    <>
      <PageHeader
        title={t('admin.cleaning.title')}
        subtitle={t('admin.cleaning.subtitle')}
        actions={
          <SegmentedControl
            label={t('admin.cleaning.period')}
            value={String(days)}
            onChange={(value) => setDays(Number(value) as CleaningPeriod)}
            options={CLEANING_PERIODS.map((value) => ({ value: String(value), label: t('admin.cleaning.days', { count: value }) }))}
          />
        }
      />
      {schedule.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : schedule.isError ? (
        <ErrorState onRetry={() => void schedule.refetch()} />
      ) : schedule.data.days.length === 0 ? (
        <Alert tone="info">{t('admin.cleaning.empty', { count: days })}</Alert>
      ) : (
        <div className="flex flex-col gap-4">
          {schedule.data.days.map((day) => {
            const when = relativeDay(day.date, schedule.data.from)
            return (
              <Card key={day.date} role="region" className="px-4 py-3 sm:px-5" aria-labelledby={`menage-${day.date}`}>
                <h2 id={`menage-${day.date}`} className="text-base font-semibold">
                  {when === 'later' ? formatLongDate(day.date) : t(`admin.cleaning.${when}`, { date: formatLongDate(day.date) })}
                </h2>
                <ul className="divide-y divide-border">
                  {day.movements.map((movement) => (
                    <li key={`${movement.apartment_id}-${movement.kind}`}>
                      <CleaningMovementRow movement={movement} />
                    </li>
                  ))}
                </ul>
              </Card>
            )
          })}
        </div>
      )}
    </>
  )
}
