import { PlusIcon } from '@/components/icons'
import type { FaqEntry } from '@/site/seo/structuredData'

/** Questions dépliables dans un bloc blanc, séparées par un filet. */
export function FaqList({ items }: { items: FaqEntry[] }) {
  return (
    <div className="flex flex-col divide-y divide-line rounded-[var(--radius-card)] bg-white">
      {items.map((item) => (
        <details key={item.question} className="group px-6 py-5">
          <summary className="flex min-h-11 cursor-pointer list-none items-center justify-between gap-4 text-[17px] font-semibold [&::-webkit-details-marker]:hidden">
            {item.question}
            <PlusIcon aria-hidden="true" width={20} height={20} className="shrink-0 text-accent-text transition-transform group-open:rotate-45" />
          </summary>
          <p className="mt-2 text-[15px] leading-relaxed text-muted">{item.answer}</p>
        </details>
      ))}
    </div>
  )
}
