import type { PublicPhoto } from '@/site/catalog/types'
import { Picture } from './Picture'

/** Deux photos en arche qui se chevauchent : la grande à droite, la petite devant, en bas à gauche. */
export function ArchPhotos({ main, secondary }: { main?: PublicPhoto; secondary?: PublicPhoto }) {
  return (
    <div aria-hidden="true" className="relative mx-auto h-[360px] w-full max-w-[460px] sm:h-[480px] xl:h-[540px] xl:max-w-none">
      <div className="absolute inset-y-0 right-0 left-10 overflow-hidden rounded-t-full rounded-b-[32px] bg-[#e8d3b8] sm:left-[60px] sm:rounded-b-[40px]">
        {main && <Picture photo={main} sizes="(min-width: 1024px) 560px, 90vw" priority alt="" />}
      </div>
      <div className="absolute bottom-6 left-0 h-[190px] w-[140px] overflow-hidden rounded-t-full rounded-b-[24px] border-[6px] border-paper bg-[#f1d3cb] sm:bottom-10 sm:h-[260px] sm:w-[190px] sm:rounded-b-[28px]">
        {secondary && <Picture photo={secondary} sizes="190px" alt="" />}
      </div>
    </div>
  )
}
