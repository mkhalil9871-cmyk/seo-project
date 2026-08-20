import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { ApiError } from '../lib/api'
import { Btn, Card, ErrorBanner, Input } from '../lib/ui'
import { IcoEye, IcoZap } from '../lib/icons'

export default function LoginPage() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [showPass, setShowPass] = useState(false)
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const submit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    if (!email || !password) {
      setError('Please fill in all fields.')
      return
    }
    setSubmitting(true)
    try {
      await login(email, password)
      navigate('/')
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Something went wrong. Please try again.')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50/30 flex items-center justify-center p-4">
      <div className="w-full max-w-sm">
        <div className="text-center mb-8">
          <div className="inline-flex items-center gap-2 mb-6">
            <div className="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center shadow-md shadow-blue-200">
              <IcoZap />
            </div>
            <span className="font-bold text-gray-900 text-xl tracking-tight">SEO Engine</span>
          </div>
          <h2 className="text-2xl font-bold text-gray-900">Welcome back</h2>
          <p className="text-sm text-gray-500 mt-1">Sign in to your account to continue</p>
        </div>

        <Card className="p-6">
          <form onSubmit={submit} className="space-y-4">
            {error && <ErrorBanner message={error} />}
            <Input label="Email address" type="email" placeholder="you@company.com" value={email} onChange={setEmail} />
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
              <div className="relative">
                <input
                  type={showPass ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  className="w-full px-3.5 py-2.5 pr-10 text-sm bg-white border border-gray-300 rounded-lg outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-colors"
                />
                <button
                  type="button"
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  onClick={() => setShowPass(!showPass)}
                >
                  <IcoEye off={showPass} />
                </button>
              </div>
            </div>

            <Btn type="submit" className="w-full justify-center py-2.5" size="lg" disabled={submitting}>
              {submitting ? 'Signing in…' : 'Sign in'}
            </Btn>
          </form>

          <p className="text-center text-xs text-gray-500 mt-5">
            Don&apos;t have an account?{' '}
            <Link to="/register" className="text-blue-600 font-medium hover:text-blue-700">
              Create one
            </Link>
          </p>
        </Card>
      </div>
    </div>
  )
}
