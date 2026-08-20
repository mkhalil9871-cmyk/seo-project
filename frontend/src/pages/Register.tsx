import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { ApiError } from '../lib/api'
import { Btn, Card, Input } from '../lib/ui'
import { IcoEye, IcoZap } from '../lib/icons'

export default function RegisterPage() {
  const { register } = useAuth()
  const navigate = useNavigate()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [confirm, setConfirm] = useState('')
  const [showPass, setShowPass] = useState(false)
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [submitting, setSubmitting] = useState(false)

  const submit = async (e: React.FormEvent) => {
    e.preventDefault()
    const errs: Record<string, string> = {}
    if (!name) errs.name = 'Name is required.'
    if (!email) errs.email = 'Email is required.'
    if (password.length < 8) errs.password = 'Password must be at least 8 characters.'
    if (password !== confirm) errs.confirm = 'Passwords do not match.'
    if (Object.keys(errs).length) {
      setErrors(errs)
      return
    }
    setErrors({})
    setSubmitting(true)
    try {
      await register(name, email, password, confirm)
      navigate('/')
    } catch (err) {
      if (err instanceof ApiError && err.errors) {
        // Laravel validation errors: { field: [messages] } -> flatten to first message per field
        const flat: Record<string, string> = {}
        Object.entries(err.errors).forEach(([field, messages]) => (flat[field] = messages[0]))
        setErrors(flat)
      } else {
        setErrors({ form: err instanceof ApiError ? err.message : 'Something went wrong. Please try again.' })
      }
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
          <h2 className="text-2xl font-bold text-gray-900">Create an account</h2>
          <p className="text-sm text-gray-500 mt-1">Start auditing your sites in minutes</p>
        </div>

        <Card className="p-6">
          <form onSubmit={submit} className="space-y-4">
            {errors.form && (
              <div className="bg-red-50 border border-red-200 text-red-700 text-xs px-3 py-2.5 rounded-lg">
                {errors.form}
              </div>
            )}
            <Input label="Full name" placeholder="Jane Doe" value={name} onChange={setName} error={errors.name} />
            <Input
              label="Work email"
              type="email"
              placeholder="you@company.com"
              value={email}
              onChange={setEmail}
              error={errors.email}
            />
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
              <div className="relative">
                <input
                  type={showPass ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="Min. 8 characters"
                  className={`w-full px-3.5 py-2.5 pr-10 text-sm bg-white border rounded-lg outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-colors ${errors.password ? 'border-red-400' : 'border-gray-300'}`}
                />
                <button
                  type="button"
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  onClick={() => setShowPass(!showPass)}
                >
                  <IcoEye off={showPass} />
                </button>
              </div>
              {errors.password && <p className="mt-1 text-xs text-red-600">{errors.password}</p>}
            </div>
            <Input
              label="Confirm password"
              type={showPass ? 'text' : 'password'}
              placeholder="Re-enter password"
              value={confirm}
              onChange={setConfirm}
              error={errors.confirm}
            />

            <Btn type="submit" className="w-full justify-center py-2.5 mt-1" size="lg" disabled={submitting}>
              {submitting ? 'Creating account…' : 'Create account'}
            </Btn>
          </form>

          <p className="text-center text-xs text-gray-500 mt-4">
            Already have an account?{' '}
            <Link to="/login" className="text-blue-600 font-medium hover:text-blue-700">
              Sign in
            </Link>
          </p>
        </Card>
      </div>
    </div>
  )
}
