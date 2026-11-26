import { RoundBadge } from '@/components/ui/RoundBadge'

export interface Step {
  title: string
  text: string
}

/** Étapes numérotées (« Réserver, c'est simple »). */
export function StepList({ items }: { items: Step[] }) {
  return (
    <ol className="grid gap-6 sm:grid-cols-3">
      {items.map((step, index) => (
        <li key={step.title} className="flex flex-col gap-2.5">
          <RoundBadge tone="accent" className="self-start">
            <span aria-hidden="true">{index + 1}</span>
          </RoundBadge>
          <h3 className="text-lg">{step.title}</h3>
          <p className="text-[15px] leading-relaxed text-muted">{step.text}</p>
        </li>
      ))}
    </ol>
  )
}
