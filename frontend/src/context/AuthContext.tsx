import { createContext, useContext, useEffect, useState, type ReactNode } from 'react'
import { authApi } from '../lib/endpoints'
import { setToken } from '../lib/api'
import type { User } from '../lib/types'

interface AuthContextValue {
  user: User | null
  loading: boolean
  login: (email: string, password: string) => Promise<void>
  register: (name: string, email: string, password: string, passwordConfirmation: string) => Promise<void>
  logout: () => Promise<void>
}

const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  // Starts true: on first load we don't yet know if a stored token is still
  // valid, so routes must wait for this check before deciding to show the
  // login screen or the app (otherwise a refresh always bounces to /login
  // for a split second, even when the session is fine).
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const stored = localStorage.getItem('seo_engine_token')
    if (!stored) {
      setLoading(false)
      return
    }
    authApi
      .me()
      .then(setUser)
      .catch(() => setToken(null))
      .finally(() => setLoading(false))
  }, [])

  const login = async (email: string, password: string) => {
    const res = await authApi.login(email, password)
    setToken(res.token)
    setUser(res.user)
  }

  const register = async (name: string, email: string, password: string, passwordConfirmation: string) => {
    const res = await authApi.register(name, email, password, passwordConfirmation)
    setToken(res.token)
    setUser(res.user)
  }

  const logout = async () => {
    try {
      await authApi.logout()
    } finally {
      setToken(null)
      setUser(null)
    }
  }

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout }}>{children}</AuthContext.Provider>
  )
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}
