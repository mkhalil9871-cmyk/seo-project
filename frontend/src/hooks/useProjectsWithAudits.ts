import { useQueries } from '@tanstack/react-query'
import { useProjects } from './useProjects'
import { auditsApi } from '../lib/endpoints'
import { ApiError } from '../lib/api'
import type { Audit, Project } from '../lib/types'

export interface ProjectWithAudit {
  project: Project
  audit: Audit | null
  auditLoading: boolean
}

/**
 * There's no bulk "all projects with their latest audit" endpoint, so this
 * fetches each project's latest audit in parallel (react-query dedupes and
 * caches these, so switching between Dashboard and Projects doesn't
 * re-fetch). Fine for the project counts a single account realistically
 * has; if that ever grows into the hundreds, this is the first place to
 * add a real bulk endpoint on the backend.
 */
export function useProjectsWithAudits() {
  const { data: projects, isLoading: projectsLoading, error } = useProjects()

  const auditQueries = useQueries({
    queries: (projects ?? []).map((p) => ({
      queryKey: ['audits', 'latest', p.id],
      queryFn: async () => {
        try {
          return await auditsApi.latest(p.id)
        } catch (e) {
          if (e instanceof ApiError && e.status === 404) return null
          throw e
        }
      },
      enabled: !!projects,
    })),
  })

  const combined: ProjectWithAudit[] = (projects ?? []).map((project, i) => ({
    project,
    audit: auditQueries[i]?.data ?? null,
    auditLoading: auditQueries[i]?.isLoading ?? false,
  }))

  return {
    data: combined,
    isLoading: projectsLoading,
    error,
  }
}
