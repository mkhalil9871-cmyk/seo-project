// Base URL is configurable via .env (VITE_API_URL) so the same build can point
// at localhost during development and a real domain in production without
// code changes.
const API_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'

export class ApiError extends Error {
  status: number
  errors?: Record<string, string[]>
  constructor(message: string, status: number, errors?: Record<string, string[]>) {
    super(message)
    this.status = status
    this.errors = errors
  }
}

function getToken(): string | null {
  return localStorage.getItem('seo_engine_token')
}

export function setToken(token: string | null) {
  if (token) localStorage.setItem('seo_engine_token', token)
  else localStorage.removeItem('seo_engine_token')
}

/**
 * Core request function. Every API call in the app goes through this so
 * auth headers, JSON parsing, and error shapes are handled in exactly one
 * place instead of being copy-pasted into every page.
 */
async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const token = getToken()

  let res: Response
  try {
    res = await fetch(`${API_URL}${path}`, {
      ...options,
      headers: {
        Accept: 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...options.headers,
      },
    })
  } catch {
    // fetch() throws (not a normal error response) when the request never
    // reached the server at all — backend not running, wrong VITE_API_URL,
    // or blocked by CORS. This is different from a 401/422/500, which DO
    // get a response back, so it needs its own clear message.
    throw new ApiError(
      `Could not reach the server at ${API_URL}. Check that the backend is running and VITE_API_URL is correct.`,
      0,
    )
  }

  if (res.status === 204) return undefined as T

  const isJson = res.headers.get('content-type')?.includes('application/json')
  const body = isJson ? await res.json() : await res.text()

  if (!res.ok) {
    if (res.status === 401) {
      // Token missing/expired — clear it so the app falls back to the login
      // screen instead of silently re-sending a dead token on every request.
      setToken(null)
    }
    const message = isJson && body?.message ? body.message : `Request failed (${res.status})`
    throw new ApiError(message, res.status, isJson ? body?.errors : undefined)
  }

  return body as T
}

export const api = {
  get: <T>(path: string) => request<T>(path),
  post: <T>(path: string, data?: unknown) =>
    request<T>(path, { method: 'POST', body: data ? JSON.stringify(data) : undefined }),
  put: <T>(path: string, data?: unknown) =>
    request<T>(path, { method: 'PUT', body: data ? JSON.stringify(data) : undefined }),
  delete: <T>(path: string) => request<T>(path, { method: 'DELETE' }),
}

/** For CSV downloads: needs the raw Blob + auth header, not JSON parsing. */
export async function downloadFile(path: string, filename: string) {
  const token = getToken()
  const res = await fetch(`${API_URL}${path}`, {
    headers: { ...(token ? { Authorization: `Bearer ${token}` } : {}) },
  })
  if (!res.ok) throw new ApiError('Download failed', res.status)
  const blob = await res.blob()
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  a.remove()
  URL.revokeObjectURL(url)
}
