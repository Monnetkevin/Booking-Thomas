import { RoundBadge } from '@/components/ui/RoundBadge'
import { featureIcons as defaultIcons, type IconComponent } from './featureIcons'

export interface Feature {
  icon: string
  title: string
  text: string
}

/** Atouts avec icône : en ligne sur grand écran, icône à gauche du texte sur téléphone. */
export function FeatureList({ items, icons = defaultIcons }: { items: Feature[]; icons?: Record<string, IconComponent> }) {
  return (
    <ul className="grid gap-5 sm:grid-cols-2 sm:gap-8 lg:grid-cols-4">
      {items.map((item) => {
        const Icon = icons[item.icon]
        return (
          <li key={item.title} className="flex items-start gap-4 sm:flex-col sm:gap-3">
            {Icon && (
              <RoundBadge tone="soft" size="lg">
                <Icon aria-hidden="true" width={24} height={24} />
              </RoundBadge>
            )}
            <div className="flex flex-col gap-1 sm:gap-2">
              <h3 className="text-[17px] sm:text-lg">{item.title}</h3>
              <p className="text-[15px] leading-relaxed text-muted">{item.text}</p>
            </div>
          </li>
        )
      })}
    </ul>
  )
}
