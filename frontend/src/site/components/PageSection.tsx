import { useId, type ReactNode } from 'react'
import { Container } from '@/components/ui/Container'
import { cn } from '@/lib/cn'

type Tone = 'plain' | 'cream' | 'sand'

const tones: Record<Tone, string> = {
  // Les sections sur fond coloré prennent plus d'air que les sections sur le fond du site.
  plain: 'py-8 sm:py-10 lg:py-12',
  cream: 'my-6 bg-cream py-14 sm:py-[72px]',
  sand: 'my-6 bg-sand py-14 sm:py-[72px]',
}

interface PageSectionProps {
  /** Titre de la section (h2) ; masqué visuellement avec hideTitle, mais lu par les lecteurs d'écran. */
  title?: ReactNode
  hideTitle?: boolean
  /** Lien ou bouton aligné à droite du titre. */
  action?: ReactNode
  tone?: Tone
  /** Colonne de lecture étroite (FAQ, texte). */
  narrow?: boolean
  children: ReactNode
}

/** Section du site public : fond, marges, titre et action, contenu. */
export function PageSection({ title, hideTitle = false, action, tone = 'plain', narrow = false, children }: PageSectionProps) {
  const titleId = useId()

  return (
    <section aria-labelledby={title ? titleId : undefined} className={tones[tone]}>
      <Container>
        <div className={cn('flex flex-col gap-8', narrow && 'mx-auto max-w-3xl gap-6')}>
          {title && (
            <div className={cn('flex flex-wrap items-end justify-between gap-4', hideTitle && 'sr-only')}>
              <h2 id={titleId} className="text-3xl leading-[1.08] sm:text-4xl">
                {title}
              </h2>
              {action}
            </div>
          )}
          {children}
        </div>
      </Container>
    </section>
  )
}
