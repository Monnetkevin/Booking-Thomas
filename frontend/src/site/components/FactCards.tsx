export interface Fact {
  value: string
  label: string
}

/** Chiffres clés en cartes (« 5 min · du circuit des 24 Heures »). */
export function FactCards({ items }: { items: Fact[] }) {
  return (
    <dl className="grid gap-4 sm:grid-cols-3">
      {items.map((fact) => (
        <div key={fact.label} className="flex flex-col-reverse gap-2 rounded-[var(--radius-card)] border border-line bg-white p-7 sm:p-8">
          <dt className="text-base text-muted">{fact.label}</dt>
          <dd className="font-display text-[40px] leading-none font-semibold text-accent sm:text-5xl">{fact.value}</dd>
        </div>
      ))}
    </dl>
  )
}
