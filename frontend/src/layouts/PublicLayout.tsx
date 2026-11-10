import { Outlet } from 'react-router'

export function PublicLayout() {
  return (
    <div className="min-h-dvh">
      <main>
        <Outlet />
      </main>
    </div>
  )
}
