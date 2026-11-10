import { Skeleton } from '@/components/ui/Skeleton'

export function ApartmentCardSkeleton() {
  return (
    <div className="flex flex-col overflow-hidden rounded-[20px] border border-border bg-white">
      <Skeleton className="aspect-[3/2] rounded-none" />
      <div className="flex flex-col gap-3 p-5">
        <Skeleton className="h-7 w-2/3" />
        <Skeleton className="h-4 w-1/2" />
        <Skeleton className="h-4 w-3/4" />
        <Skeleton className="mt-4 h-11 w-full" />
      </div>
    </div>
  )
}
