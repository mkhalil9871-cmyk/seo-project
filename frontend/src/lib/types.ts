export interface User {
  id: number
  name: string
  email: string
  role: string | null
  permissions: string[]
}

export interface Project {
  id: number
  name: string
  domain: string
  industry: string | null
  country: string | null
  language: string | null
  status: string | null
  max_pages: number | null
  max_depth: number | null
  created_at: string
}

export type AuditStatus = 'queued' | 'crawling' | 'scoring' | 'completed' | 'failed'

export interface Audit {
  id: number
  status: AuditStatus
  pages_crawled: number
  pages_queued: number
  pages_failed: number
  sitemap_urls_found: number
  overall_score: number | null
  technical_score: number | null
  content_score: number | null
  comparison: {
    has_previous: boolean
    previous_audit_id?: number
    previous_score?: number
    score_delta?: number
    total_issues_now?: number
    total_issues_before?: number
    new_issue_types?: { type: string; count: number }[]
    fixed_issue_types?: { type: string; count: number }[]
    worsened_issue_types?: { type: string; from: number; to: number }[]
    improved_issue_types?: { type: string; from: number; to: number }[]
  } | null
  error_message: string | null
  started_at: string | null
  finished_at: string | null
}

export type IssueSeverity = 'critical' | 'high' | 'medium' | 'low'

export interface Issue {
  id: number
  audit_id: number
  crawled_page_id: number | null
  type: string
  severity: IssueSeverity
  category: string
  detail: string | null
  created_at: string
  page: { id: number; url: string; title: string | null } | null
}

export interface IssueSummaryRow {
  type: string
  severity: IssueSeverity
  category: string
  count: number
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  total: number
  per_page: number
}
