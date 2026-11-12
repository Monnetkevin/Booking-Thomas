import { Link } from 'react-router'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { Container } from '@/components/ui/Container'

/** Bandeau sombre d'appel à l'action, en fin de page. */
export function CtaBanner({ title, text, action }: { title: string; text: string; action: { to: string; label: string } }) {
  return (
    <section className="pt-6 pb-14">
      <Container>
        <div className="flex flex-col items-start gap-6 rounded-[32px] bg-night px-6 py-10 sm:px-12 sm:py-14 lg:flex-row lg:items-center lg:justify-between lg:rounded-[40px]">
          <div className="flex max-w-[620px] flex-col gap-2.5">
            <h2 className="text-[32px] leading-[1.05] text-white sm:text-[44px]">{title}</h2>
            <p className="text-[17px] leading-relaxed text-night-text">{text}</p>
          </div>
          <Link to={action.to} className={buttonClasses('primary', 'xl', 'shrink-0')}>
            {action.label}
          </Link>
        </div>
      </Container>
    </section>
  )
}
