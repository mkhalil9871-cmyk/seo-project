import { lazy, Suspense } from 'react'
import { Navigate, Route, Routes } from 'react-router-dom'
import { AppLayout } from './components/AppLayout'
import { ProtectedRoute } from './components/ProtectedRoute'
import { useAuth } from './context/AuthContext'
import { PageLoader } from './lib/ui'

// Every route is its own JS chunk — visiting Dashboard never downloads the
// Keywords/SERP/Strategy code, and vice versa. This keeps the initial load
// small regardless of how many pages the app grows to.
const LoginPage = lazy(() => import('./pages/Login'))
const RegisterPage = lazy(() => import('./pages/Register'))
const DashboardPage = lazy(() => import('./pages/Dashboard'))
const ProjectsPage = lazy(() => import('./pages/Projects'))
const ProjectDetailPage = lazy(() => import('./pages/ProjectDetail'))
const AuditPage = lazy(() => import('./pages/Audit'))
const KeywordsPage = lazy(() => import('./pages/Keywords'))
const SERPPage = lazy(() => import('./pages/SERP'))
const StrategyPage = lazy(() => import('./pages/Strategy'))

function AuthLandingRedirect() {
  const { user, loading } = useAuth()
  if (loading) return <PageLoader />
  return <Navigate to={user ? '/' : '/login'} replace />
}

export default function App() {
  return (
    <Suspense fallback={<PageLoader />}>
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />

        <Route
          element={
            <ProtectedRoute>
              <AppLayout />
            </ProtectedRoute>
          }
        >
          <Route path="/" element={<DashboardPage />} />
          <Route path="/projects" element={<ProjectsPage />} />
          <Route path="/projects/:id" element={<ProjectDetailPage />} />
          <Route path="/audit" element={<AuditPage />} />
          <Route path="/keywords" element={<KeywordsPage />} />
          <Route path="/serp" element={<SERPPage />} />
          <Route path="/strategy" element={<StrategyPage />} />
        </Route>

        <Route path="*" element={<AuthLandingRedirect />} />
      </Routes>
    </Suspense>
  )
}
