import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { auditsApi } from '../lib/endpoints'
import { ApiError } from '../lib/api'
import type { Audit } from '../lib/types'

const RUNNING_STATUSES: Audit['status'][] = ['queued', 'crawling', 'scoring']

/**
 * Finds the most recent audit for a project (or null if it's never been
 * audited). A 404 here is an expected, normal state — not an error — so it's
 * caught and turned into `data: null` instead of surfacing as query.isError.
 */
export function useLatestAudit(projectId: number | undefined) {
  return useQuery({
    queryKey: ['audits', 'latest', projectId],
    queryFn: async () => {
      try {
        return await auditsApi.latest(projectId!)
      } catch (e) {
        if (e instanceof ApiError && e.status === 404) return null
        throw e
      }
    },
    enabled: !!projectId,
    refetchInterval: (query) => {
      const status = query.state.data?.status
      return status && RUNNING_STATUSES.includes(status) ? 4000 : false
    },
  })
}

/**
 * Polls GET /audits/{id} every 4s while the audit is still running, and stops
 * polling the moment it lands on 'completed' or 'failed'. This is exactly the
 * "wait a minute, then check" loop we did by hand in Postman — just automatic.
 */
export function useAudit(auditId: number | undefined) {
  return useQuery({
    queryKey: ['audits', auditId],
    queryFn: () => auditsApi.status(auditId!),
    enabled: !!auditId,
    refetchInterval: (query) => {
      const status = query.state.data?.status
      return status && RUNNING_STATUSES.includes(status) ? 4000 : false
    },
  })
}

export function useStartAudit() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (projectId: number) => auditsApi.start(projectId),
    onSuccess: (_, projectId) => {
      qc.invalidateQueries({ queryKey: ['audits', 'latest', projectId] })
    },
  })
}

export function useIssues(
  auditId: number | undefined,
  params: { severity?: string; type?: string; page?: number } = {},
) {
  return useQuery({
    queryKey: ['audits', auditId, 'issues', params],
    queryFn: () => auditsApi.issues(auditId!, params),
    enabled: !!auditId,
  })
}

export function useIssuesSummary(auditId: number | undefined) {
  return useQuery({
    queryKey: ['audits', auditId, 'issues-summary'],
    queryFn: () => auditsApi.issuesSummary(auditId!),
    enabled: !!auditId,
  })
}
