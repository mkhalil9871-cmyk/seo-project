import { NavLink } from 'react-router-dom'
import { IcoAudit, IcoDashboard, IcoKeywords, IcoProjects, IcoSERP, IcoStrategy, IcoX, IcoZap } from '../lib/icons'
import { useAuth } from '../context/AuthContext'

const NAV_ITEMS = [
  { to: '/', label: 'Dashboard', Icon: IcoDashboard, end: true },
  { to: '/projects', label: 'Projects', Icon: IcoProjects, end: false },
  { to: '/keywords', label: 'Keywords', Icon: IcoKeywords, end: false },
  { to: '/audit', label: 'Audit', Icon: IcoAudit, end: false },
  { to: '/serp', label: 'SERP Tracking', Icon: IcoSERP, end: false },
  { to: '/strategy', label: 'AI Strategy', Icon: IcoStrategy, end: false, badge: 'AI' },
]

function initials(name?: string) {
  if (!name) return '?'
  return name
    .split(' ')
    .map((p) => p[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

export function Sidebar({ open, onClose }: { open: boolean; onClose: () => void }) {
  const { user } = useAuth()

  return (
    <>
      {open && <div className="fixed inset-0 bg-black/20 z-20 lg:hidden" onClick={onClose} />}
      <aside
        className={`fixed lg:static inset-y-0 left-0 z-30 w-56 bg-white border-r border-gray-200 flex flex-col transform transition-transform duration-200 ease-in-out ${
          open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        }`}
      >
        <div className="flex items-center gap-2.5 px-5 h-14 border-b border-gray-100 shrink-0">
          <div className="w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center shrink-0">
            <IcoZap />
          </div>
          <span className="font-bold text-gray-900 text-base tracking-tight">SEO Engine</span>
          <button className="ml-auto text-gray-400 hover:text-gray-600 lg:hidden" onClick={onClose}>
            <IcoX />
          </button>
        </div>

        <nav className="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
          <p className="text-[10px] font-semibold text-gray-400 uppercase tracking-widest px-3 pb-2.5 pt-1">
            Navigation
          </p>
          {NAV_ITEMS.map(({ to, label, Icon, end, badge }) => (
            <NavLink
              key={to}
              to={to}
              end={end}
              onClick={onClose}
              className={({ isActive }) =>
                `w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors ${
                  isActive ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                }`
              }
            >
              {({ isActive }) => (
                <>
                  <span className={isActive ? 'text-blue-600' : 'text-gray-400'}>
                    <Icon />
                  </span>
                  {label}
                  {badge && (
                    <span className="ml-auto text-[10px] font-bold bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded-full">
                      {badge}
                    </span>
                  )}
                </>
              )}
            </NavLink>
          ))}
        </nav>

        {user && (
          <div className="px-3 py-4 border-t border-gray-100 shrink-0">
            <div className="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-gray-50">
              <div className="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                {initials(user.name)}
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-gray-900 truncate leading-tight">{user.name}</p>
                <p className="text-xs text-gray-500 truncate leading-tight">{user.email}</p>
              </div>
            </div>
          </div>
        )}
      </aside>
    </>
  )
}
