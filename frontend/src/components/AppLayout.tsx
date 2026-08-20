import { useState } from 'react'
import { Outlet, useLocation } from 'react-router-dom'
import { Sidebar } from './Sidebar'
import { Header } from './Header'

const PAGE_TITLES: Record<string, string> = {
  '/': 'Dashboard',
  '/projects': 'Projects',
  '/audit': 'Site Audit',
  '/keywords': 'Keywords',
  '/serp': 'SERP Tracking',
  '/strategy': 'AI Strategy',
}

function titleFor(pathname: string): string {
  if (PAGE_TITLES[pathname]) return PAGE_TITLES[pathname]
  if (pathname.startsWith('/projects/')) return 'Project Detail'
  return 'SEO Engine'
}

export function AppLayout() {
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const location = useLocation()

  return (
    <div className="h-screen flex bg-gray-50 overflow-hidden">
      <Sidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} />
      <div className="flex-1 flex flex-col min-w-0">
        <Header title={titleFor(location.pathname)} onMenuOpen={() => setSidebarOpen(true)} />
        <main className="flex-1 overflow-y-auto p-4 lg:p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
