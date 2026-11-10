import { useId, type ReactNode } from 'react'
import { cn } from '@/lib/cn'

/** Bloc titré du back-office (carte blanche). */
export function Section({
  title,
  description,
  actions,
  className,
  children,
}: {
  title: string
  description?: string
  actions?: ReactNode
  className?: string
  children: ReactNode
}) {
  const titleId = useId()

  return (
    <section
      aria-labelledby={titleId}
      className={cn('rounded-[var(--radius-card)] border border-border bg-white p-5 sm:p-6', className)}
    >
      <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 id={titleId} className="text-xl font-semibold">
            {title}
          </h2>
          {description && <p className="mt-1 text-sm text-muted">{description}</p>}
        </div>
        {actions}
      </div>
      {children}
    </section>
  )
}
