import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { IcoBell, IcoChevDown, IcoLogOut, IcoMenu, IcoSettings } from '../lib/icons'
import { useAuth } from '../context/AuthContext'

function initials(name?: string) {
  if (!name) return '?'
  return name
    .split(' ')
    .map((p) => p[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

export function Header({ title, onMenuOpen }: { title: string; onMenuOpen: () => void }) {
  const [menuOpen, setMenuOpen] = useState(false)
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  const handleLogout = async () => {
    setMenuOpen(false)
    await logout()
    navigate('/login')
  }

  return (
    <header className="h-14 bg-white border-b border-gray-200 flex items-center gap-3 px-4 lg:px-6 shrink-0">
      <button
        className="lg:hidden text-gray-500 hover:text-gray-700 p-1.5 rounded-lg hover:bg-gray-100"
        onClick={onMenuOpen}
      >
        <IcoMenu />
      </button>

      <h1 className="text-sm font-semibold text-gray-900">{title}</h1>

      <div className="ml-auto flex items-center gap-1">
        <button className="relative p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg">
          <IcoBell />
        </button>

        {user && (
          <div className="relative">
            <button
              className="flex items-center gap-1.5 px-2 py-1.5 rounded-lg hover:bg-gray-100 transition-colors"
              onClick={() => setMenuOpen(!menuOpen)}
            >
              <div className="w-7 h-7 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-bold">
                {initials(user.name)}
              </div>
              <span className="text-xs text-gray-600 hidden sm:block font-medium">{user.name}</span>
              <IcoChevDown />
            </button>

            {menuOpen && (
              <>
                <div className="fixed inset-0 z-40" onClick={() => setMenuOpen(false)} />
                <div className="absolute right-0 top-full mt-1 w-44 bg-white border border-gray-200 rounded-xl shadow-lg py-1.5 z-50">
                  <div className="px-3 py-2 border-b border-gray-100 mb-1">
                    <p className="text-xs font-semibold text-gray-900">{user.name}</p>
                    <p className="text-xs text-gray-500">{user.email}</p>
                  </div>
                  <button className="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs text-gray-700 hover:bg-gray-50">
                    <IcoSettings /> Settings
                  </button>
                  <div className="border-t border-gray-100 my-1" />
                  <button
                    className="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs text-red-600 hover:bg-red-50"
                    onClick={handleLogout}
                  >
                    <IcoLogOut /> Sign out
                  </button>
                </div>
              </>
            )}
          </div>
        )}
      </div>
    </header>
  )
}
