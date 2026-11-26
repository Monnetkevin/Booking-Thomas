import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useApartmentList } from '@/admin/apartments/useApartments'
import { KpiCard } from '@/admin/components/KpiCard'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { BellIcon, MailIcon, StarIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { SelectField } from '@/components/ui/Field'
import { FilterChips } from '@/components/ui/FilterChips'
import { Pager } from '@/components/ui/Pager'
import { Spinner } from '@/components/ui/Spinner'
import { AdminReviewCard } from '../components/AdminReviewCard'
import { ReviewTextDialog } from '../components/ReviewTextDialog'
import { REVIEW_STATUSES, type AdminReview, type ReviewFilters } from '../types'
import { useAdminReviews, useReviewAction } from '../useAdminReviews'

type Editing = { kind: 'reject' | 'reply'; review: AdminReview } | null

const decimal = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 })

/**
 * Avis des voyageurs : file de modération (à modérer, publiés, refusés, invitations sans
 * réponse). Tous les avis sont traités de la même façon, quelle que soit la note.
 */
export function ReviewsPage() {
  const { t } = useTranslation()
  const [filters, setFilters] = useState<ReviewFilters>({ status: 'pending', apartmentId: null, page: 1 })
  const [editing, setEditing] = useState<Editing>(null)
  const list = useAdminReviews(filters)
  const apartments = useApartmentList()
  const publish = useReviewAction<number>((service, id) => service.publish(id))
  const reject = useReviewAction<{ id: number; text: string }>((service, input) => service.reject(input.id, input.text))
  const reply = useReviewAction<{ id: number; text: string }>((service, input) => service.reply(input.id, input.text))
  const resend = useReviewAction<number>((service, id) => service.resendInvitation(id))
  const failed = [publish, reject, reply, resend].some((mutation) => mutation.isError)

  const change = (next: Partial<ReviewFilters>) => setFilters((current) => ({ ...current, page: 1, ...next }))
  const counts = list.data?.meta.counts
  const summary = list.data?.meta.summary
  const rate = (value: number | null | undefined) => (value === null || value === undefined ? t('admin.reviews.kpi.none') : `${value} %`)
  const submitText = (text: string) => {
    if (!editing) return
    const action = editing.kind === 'reject' ? reject : reply
    action.mutate({ id: editing.review.id, text }, { onSuccess: () => setEditing(null) })
  }

  const actions = (review: AdminReview) => {
    const busy = (mutation: { isPending: boolean; variables?: unknown }) => mutation.isPending && mutation.variables === review.id
    const publishButton = (
      <Button loading={busy(publish)} onClick={() => publish.mutate(review.id)}>
        {t('admin.reviews.publish')}
      </Button>
    )
    const rejectButton = (
      <Button variant="secondary" onClick={() => setEditing({ kind: 'reject', review })}>
        {t(review.status === 'published' ? 'admin.reviews.unpublish' : 'admin.reviews.reject')}
      </Button>
    )

    const replyButton = (
      <Button variant="secondary" onClick={() => setEditing({ kind: 'reply', review })}>
        {t(review.host_reply ? 'admin.reviews.editReply' : 'admin.reviews.reply')}
      </Button>
    )

    switch (review.status) {
      case 'invited':
        return (
          <Button variant="secondary" loading={busy(resend)} onClick={() => resend.mutate(review.id)}>
            {t('admin.reviews.resend')}
          </Button>
        )
      case 'pending':
        return (
          <>
            {publishButton}
            {rejectButton}
            {replyButton}
          </>
        )
      case 'published':
        return (
          <>
            {replyButton}
            {rejectButton}
          </>
        )
      case 'rejected':
        return publishButton
    }
  }

  return (
    <>
      <PageHeader title={t('admin.reviews.title')} subtitle={t('admin.reviews.subtitle')} />
      {summary && (
        <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <KpiCard
            icon={<StarIcon />}
            label={t('admin.reviews.kpi.average')}
            value={summary.average === null ? t('admin.reviews.kpi.none') : t('admin.reviews.kpi.averageValue', { average: decimal.format(summary.average) })}
            detail={t('admin.reviews.kpi.averageDetail', { count: summary.count })}
            delta={null}
          />
          <KpiCard icon={<BellIcon />} label={t('admin.reviews.kpi.pending')} value={summary.pending} detail={t('admin.reviews.kpi.pendingDetail')} delta={null} />
          <KpiCard icon={<MailIcon />} label={t('admin.reviews.kpi.replyRate')} value={rate(summary.reply_rate)} detail={t('admin.reviews.kpi.replyRateDetail')} delta={null} />
          <KpiCard icon={<StarIcon />} label={t('admin.reviews.kpi.fiveStars')} value={rate(summary.five_star_share)} detail={t('admin.reviews.kpi.fiveStarsDetail')} delta={null} />
        </div>
      )}
      <div className="mb-5 flex flex-wrap items-end justify-between gap-4">
        <FilterChips
          label={t('admin.reviews.filter')}
          value={filters.status}
          onChange={(status) => change({ status })}
          options={REVIEW_STATUSES.map((status) => ({
            value: status,
            label: t(`admin.reviews.filters.${status}`, { count: counts?.[status] ?? 0 }),
          }))}
        />
        <SelectField
          label={t('admin.reviews.apartment')}
          className="w-full sm:w-64"
          value={filters.apartmentId ? String(filters.apartmentId) : ''}
          onChange={(value) => change({ apartmentId: value ? Number(value) : null })}
          options={[{ value: '', label: t('admin.reviews.allApartments') }, ...(apartments.data ?? []).map((apartment) => ({ value: String(apartment.id), label: apartment.name }))]}
        />
      </div>
      {failed && (
        <div className="mb-4">
          <Alert tone="danger">{t('admin.reviews.failed')}</Alert>
        </div>
      )}
      {list.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : list.isError ? (
        <ErrorState onRetry={() => void list.refetch()} />
      ) : list.data.data.length === 0 ? (
        <Alert tone="info">{t(`admin.reviews.empty.${filters.status}`)}</Alert>
      ) : (
        <>
          <div className="grid gap-4 lg:grid-cols-2">
            {list.data.data.map((review) => (
              <AdminReviewCard key={review.id} review={review} actions={actions(review)} />
            ))}
          </div>
          <Pager
            className="mt-5"
            page={filters.page}
            lastPage={list.data.meta.last_page}
            onChange={(page) => setFilters((current) => ({ ...current, page }))}
            label={t('admin.reviews.pages')}
            position={t('admin.reviews.page', { page: list.data.meta.current_page, total: list.data.meta.last_page })}
          />
        </>
      )}
      <p className="mt-6 text-[13px] text-muted">{t('admin.reviews.fairness')}</p>
      {editing && (
        <ReviewTextDialog
          open
          key={`${editing.kind}-${editing.review.id}`}
          title={t(`admin.reviews.dialogs.${editing.kind}.title`)}
          label={t(`admin.reviews.dialogs.${editing.kind}.label`)}
          hint={t(`admin.reviews.dialogs.${editing.kind}.hint`)}
          submitLabel={t(`admin.reviews.dialogs.${editing.kind}.submit`)}
          initial={editing.kind === 'reply' ? (editing.review.host_reply ?? '') : (editing.review.rejection_reason ?? '')}
          loading={(editing.kind === 'reject' ? reject : reply).isPending}
          onSubmit={submitText}
          onClose={() => setEditing(null)}
        />
      )}
    </>
  )
}
