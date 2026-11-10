import { Outlet } from 'react-router'

export function AdminLayout() {
  return (
    <div className="min-h-dvh">
      <Outlet />
    </div>
  )
}
