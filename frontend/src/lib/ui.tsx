import type { ReactNode } from 'react'
import { IcoAlert } from './icons'

export const Btn = ({
  children,
  variant = 'primary',
  size = 'md',
  onClick,
  type = 'button',
  className = '',
  disabled = false,
}: {
  children: ReactNode
  variant?: 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger'
  size?: 'sm' | 'md' | 'lg'
  onClick?: () => void
  type?: 'button' | 'submit'
  className?: string
  disabled?: boolean
}) => {
  const base =
    'inline-flex items-center gap-1.5 font-medium rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed'
  const sizes = { sm: 'px-3 py-1.5 text-xs', md: 'px-4 py-2 text-sm', lg: 'px-5 py-2.5 text-sm' }
  const variants = {
    primary: 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 shadow-sm shadow-blue-100',
    secondary: 'bg-gray-100 text-gray-700 hover:bg-gray-200 focus:ring-gray-300',
    outline: 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-gray-300',
    ghost: 'text-gray-600 hover:bg-gray-100 focus:ring-gray-300',
    danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
  }
  return (
    <button
      type={type}
      onClick={onClick}
      disabled={disabled}
      className={`${base} ${sizes[size]} ${variants[variant]} ${className}`}
    >
      {children}
    </button>
  )
}

export const Input = ({
  label,
  type = 'text',
  placeholder,
  value,
  onChange,
  error,
  suffix,
}: {
  label?: string
  type?: string
  placeholder?: string
  value: string
  onChange: (v: string) => void
  error?: string
  suffix?: ReactNode
}) => (
  <div>
    {label && <label className="block text-sm font-medium text-gray-700 mb-1.5">{label}</label>}
    <div className="relative">
      <input
        type={type}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        className={`w-full px-3.5 py-2.5 text-sm bg-white border rounded-lg outline-none transition-colors ${
          error
            ? 'border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-100'
            : 'border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100'
        } ${suffix ? 'pr-10' : ''}`}
      />
      {suffix && <div className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">{suffix}</div>}
    </div>
    {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
  </div>
)

export const Select = ({
  label,
  value,
  onChange,
  options,
}: {
  label?: string
  value: string
  onChange: (v: string) => void
  options: { value: string; label: string }[]
}) => (
  <div>
    {label && <label className="block text-sm font-medium text-gray-700 mb-1.5">{label}</label>}
    <select
      value={value}
      onChange={(e) => onChange(e.target.value)}
      className="w-full px-3.5 py-2.5 text-sm bg-white border border-gray-300 rounded-lg outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-colors"
    >
      {options.map((o) => (
        <option key={o.value} value={o.value}>
          {o.label}
        </option>
      ))}
    </select>
  </div>
)

export const Badge = ({
  children,
  color = 'gray',
}: {
  children: ReactNode
  color?: 'gray' | 'blue' | 'green' | 'red' | 'yellow' | 'purple' | 'indigo'
}) => {
  const colors = {
    gray: 'bg-gray-100 text-gray-600',
    blue: 'bg-blue-50 text-blue-700',
    green: 'bg-emerald-50 text-emerald-700',
    red: 'bg-red-50 text-red-700',
    yellow: 'bg-amber-50 text-amber-700',
    purple: 'bg-purple-50 text-purple-700',
    indigo: 'bg-indigo-50 text-indigo-700',
  }
  return (
    <span className={`inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium ${colors[color]}`}>
      {children}
    </span>
  )
}

export const Card = ({
  children,
  className = '',
  onClick,
}: {
  children: ReactNode
  className?: string
  onClick?: () => void
}) => (
  <div
    onClick={onClick}
    className={`bg-white border border-gray-200 rounded-xl shadow-sm ${onClick ? 'cursor-pointer hover:border-blue-200 hover:shadow-md transition-all' : ''} ${className}`}
  >
    {children}
  </div>
)

export const ScoreCircle = ({ score, size = 80 }: { score: number; size?: number }) => {
  const r = 30
  const circ = 2 * Math.PI * r
  const offset = circ - (score / 100) * circ
  const color = score >= 80 ? '#10B981' : score >= 60 ? '#F59E0B' : '#EF4444'
  return (
    <svg width={size} height={size} viewBox="0 0 70 70">
      <circle cx="35" cy="35" r={r} fill="none" stroke="#F3F4F6" strokeWidth="6" />
      <circle
        cx="35"
        cy="35"
        r={r}
        fill="none"
        stroke={color}
        strokeWidth="6"
        strokeDasharray={circ}
        strokeDashoffset={offset}
        strokeLinecap="round"
        transform="rotate(-90 35 35)"
      />
      <text x="35" y="39" textAnchor="middle" fontSize="13" fontWeight="700" fill="#111827" fontFamily="Inter, sans-serif">
        {Math.round(score)}
      </text>
    </svg>
  )
}

export const StatusBadge = ({ status }: { status: string }) => {
  const map: Record<string, { color: string; dot: string }> = {
    active: { color: 'text-emerald-700 bg-emerald-50', dot: 'bg-emerald-500' },
    Active: { color: 'text-emerald-700 bg-emerald-50', dot: 'bg-emerald-500' },
    paused: { color: 'text-amber-700 bg-amber-50', dot: 'bg-amber-500' },
    Paused: { color: 'text-amber-700 bg-amber-50', dot: 'bg-amber-500' },
    draft: { color: 'text-gray-600 bg-gray-100', dot: 'bg-gray-400' },
    Draft: { color: 'text-gray-600 bg-gray-100', dot: 'bg-gray-400' },
  }
  const s = map[status] ?? map['Draft']
  return (
    <span className={`inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-medium ${s.color}`}>
      <span className={`w-1.5 h-1.5 rounded-full ${s.dot}`} />
      {status || 'Draft'}
    </span>
  )
}

export const Spinner = ({ size = 20 }: { size?: number }) => (
  <svg className="animate-spin" width={size} height={size} viewBox="0 0 24 24" fill="none">
    <circle className="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" />
    <path d="M22 12a10 10 0 00-10-10" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
  </svg>
)

export const PageLoader = () => (
  <div className="flex items-center justify-center h-64 text-blue-600">
    <Spinner size={28} />
  </div>
)

export const EmptyState = ({
  title,
  description,
  action,
  icon,
}: {
  title: string
  description: string
  action?: ReactNode
  icon?: ReactNode
}) => (
  <div className="flex flex-col items-center justify-center text-center py-16 px-6">
    <div className="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-4 text-gray-400">
      {icon ?? <IcoAlert />}
    </div>
    <h3 className="text-sm font-semibold text-gray-900">{title}</h3>
    <p className="text-xs text-gray-500 mt-1 max-w-xs">{description}</p>
    {action && <div className="mt-4">{action}</div>}
  </div>
)

export const ErrorBanner = ({ message }: { message: string }) => (
  <div className="bg-red-50 border border-red-200 text-red-700 text-xs px-3 py-2.5 rounded-lg flex items-center gap-2">
    <IcoAlert />
    {message}
  </div>
)
