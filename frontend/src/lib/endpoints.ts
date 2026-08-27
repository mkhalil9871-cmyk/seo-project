import { api } from './api'
import type { Audit, Issue, IssueSummaryRow, PaginatedResponse, Project, User } from './types'

export const authApi = {
  login: (email: string, password: string) =>
    api.post<{ user: User; token: string }>('/login', { email, password }),
  register: (name: string, email: string, password: string, password_confirmation: string) =>
    api.post<{ user: User; token: string }>('/register', { name, email, password, password_confirmation }),
  logout: () => api.post<{ message: string }>('/logout'),
  me: () => api.get<User>('/user'),
}

// Fields the backend actually accepts on create/update (see StoreProjectRequest /
// UpdateProjectRequest). `status` is intentionally NOT sent — the backend doesn't
// validate or persist it yet, so a status picker here would silently do nothing.
export interface ProjectInput {
  name: string
  domain: string
  industry?: string
  country?: string
  language?: string
}

export const projectsApi = {
  list: () => api.get<{ data: Project[] }>('/projects'),
  get: (id: number) => api.get<{ data: Project }>(`/projects/${id}`),
  create: (input: ProjectInput) => api.post<{ data: Project }>('/projects', input),
  update: (id: number, input: Partial<ProjectInput>) => api.put<{ data: Project }>(`/projects/${id}`, input),
  remove: (id: number) => api.delete<{ message: string }>(`/projects/${id}`),
}

export const auditsApi = {
  start: (projectId: number) =>
    api.post<{ message: string; audit: Audit; seeded_urls: number; sitemap_urls_found: number }>(
      `/projects/${projectId}/audits`,
    ),
  latest: (projectId: number) => api.get<Audit>(`/projects/${projectId}/audits/latest`),
  status: (auditId: number) => api.get<Audit>(`/audits/${auditId}`),
  issues: (auditId: number, params: { severity?: string; type?: string; page?: number } = {}) => {
    const qs = new URLSearchParams()
    if (params.severity) qs.set('severity', params.severity)
    if (params.type) qs.set('type', params.type)
    if (params.page) qs.set('page', String(params.page))
    const suffix = qs.toString() ? `?${qs.toString()}` : ''
    return api.get<PaginatedResponse<Issue>>(`/audits/${auditId}/issues${suffix}`)
  },
  issuesSummary: (auditId: number) => api.get<IssueSummaryRow[]>(`/audits/${auditId}/issues/summary`),
}

export interface KeywordInput {
  keyword: string
  cluster?: string
  intent?: 'informational' | 'commercial' | 'transactional' | 'navigational'
}

export const keywordsApi = {
  list: (projectId: number) => api.get<{ data: import('./types').Keyword[] }>(`/projects/${projectId}/keywords`),
  create: (projectId: number, input: KeywordInput) =>
    api.post<{ data: import('./types').Keyword }>(`/projects/${projectId}/keywords`, input),
  delete: (projectId: number, keywordId: number) =>
    api.delete<{ message: string }>(`/projects/${projectId}/keywords/${keywordId}`),
  refresh: (projectId: number, keywordId: number) =>
    api.post<{ message: string }>(`/projects/${projectId}/keywords/${keywordId}/refresh`),
}

export const rankTrackingApi = {
  list: (projectId: number, keywordId: number) =>
    api.get<import('./types').RankTracking[]>(`/projects/${projectId}/keywords/${keywordId}/rank-trackings`),
  check: (projectId: number, keywordId: number, domain?: string) =>
    api.post<import('./types').RankTracking>(`/projects/${projectId}/keywords/${keywordId}/rank-trackings/check`, {
      domain,
    }),
}

export const strategiesApi = {
  list: (projectId: number) =>
    api.get<{ data: import('./types').Strategy[] }>(`/projects/${projectId}/strategies`),
  generate: (projectId: number) =>
    api.post<{ data: import('./types').Strategy }>(`/projects/${projectId}/strategies`),
  get: (projectId: number, strategyId: number) =>
    api.get<{ data: import('./types').Strategy }>(`/projects/${projectId}/strategies/${strategyId}`),
}

export const contentApi = {
  list: (projectId: number) =>
    api.get<{ data: import('./types').ContentPiece[] }>(`/projects/${projectId}/content`),
  generate: (projectId: number, keywordId?: number) =>
    api.post<{ data: import('./types').ContentPiece }>(`/projects/${projectId}/content`, {
      keyword_id: keywordId,
    }),
  get: (projectId: number, contentPieceId: number) =>
    api.get<{ data: import('./types').ContentPiece }>(`/projects/${projectId}/content/${contentPieceId}`),
}

