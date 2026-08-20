import { Navigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { PageLoader } from '../lib/ui'

export function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { user, loading } = useAuth()

  if (loading) {
    return (
      <div className="h-screen flex items-center justify-center bg-gray-50">
        <PageLoader />
      </div>
    )
  }

  if (!user) return <Navigate to="/login" replace />

  return <>{children}</>
}
