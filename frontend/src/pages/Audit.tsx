import { useEffect, useState } from 'react'
import { useSelectedProject, ProjectPicker } from '../components/ProjectPicker'
import { useAudit, useLatestAudit, useStartAudit } from '../hooks/useAudit'
import { downloadFile } from '../lib/api'
import { AuditScoreSummary } from '../components/AuditScoreSummary'
import { AuditProgress } from '../components/AuditProgress'
import { IssuesTable } from '../components/IssuesTable'
import { Btn, Card, EmptyState, ErrorBanner, PageLoader } from '../lib/ui'
import { IcoAudit, IcoDownload, IcoRefresh } from '../lib/icons'
import { ApiError } from '../lib/api'

const RUNNING = ['queued', 'crawling', 'scoring']

export default function AuditPage() {
  const { projects, projectId, setProjectId } = useSelectedProject()

  // Two sources for "which audit are we showing": the last-known one for this
  // project (survives a page refresh), and — once we've started a NEW one in
  // this session — its exact ID from the start() response. The latter always
  // wins once set, and is polled via /audits/{id}, which doesn't depend on
  // the separate "latest" lookup at all.
  const { data: latestAudit, isLoading: latestLoading } = useLatestAudit(projectId)
  const [activeAuditId, setActiveAuditId] = useState<number | undefined>(undefined)
  const { data: activeAudit } = useAudit(activeAuditId)

  useEffect(() => {
    setActiveAuditId(undefined) // reset when switching projects
  }, [projectId])

  const audit = activeAudit ?? latestAudit
  const isLoading = latestLoading && !activeAuditId

  const startAudit = useStartAudit()
  const [startError, setStartError] = useState('')

  const handleStart = async () => {
    if (!projectId) return
    setStartError('')
    try {
      const res = await startAudit.mutateAsync(projectId)
      setActiveAuditId(res.audit.id)
    } catch (err) {
      setStartError(err instanceof ApiError ? err.message : 'Could not start the audit.')
    }
  }

  if (projects.length === 0) {
    return (
      <Card>
        <EmptyState title="No projects yet" description="Add a project first, then you can run an audit against it." />
      </Card>
    )
  }

  return (
    <div className="max-w-5xl space-y-5">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <ProjectPicker projects={projects} value={projectId} onChange={setProjectId} />
        <div className="flex items-center gap-2">
          {audit && !RUNNING.includes(audit.status) && (
            <>
              <Btn
                variant="outline"
                size="sm"
                onClick={() => projectId && downloadFile(`/audits/${audit.id}/export.csv`, `audit-${audit.id}-issues.csv`)}
              >
                <IcoDownload /> Issues CSV
              </Btn>
              <Btn
                variant="outline"
                size="sm"
                onClick={() =>
                  projectId && downloadFile(`/audits/${audit.id}/export-pages.csv`, `audit-${audit.id}-pages.csv`)
                }
              >
                <IcoDownload /> Pages CSV
              </Btn>
            </>
          )}
          <Btn size="sm" onClick={handleStart} disabled={startAudit.isPending || (audit != null && RUNNING.includes(audit.status))}>
            <IcoRefresh /> {audit && RUNNING.includes(audit.status) ? 'Audit running…' : 'Run new audit'}
          </Btn>
        </div>
      </div>

      {startError && <ErrorBanner message={startError} />}

      {isLoading ? (
        <PageLoader />
      ) : !audit ? (
        <Card>
          <EmptyState
            title="No audit yet"
            description="Run your first crawl to see technical issues, content problems, and a real SEO score for this site."
            action={
              <Btn onClick={handleStart} disabled={startAudit.isPending}>
                <IcoAudit /> {startAudit.isPending ? 'Starting…' : 'Run first audit'}
              </Btn>
            }
          />
        </Card>
      ) : (
        <>
          {RUNNING.includes(audit.status) || audit.status === 'failed' ? (
            <AuditProgress audit={audit} />
          ) : (
            <AuditScoreSummary audit={audit} />
          )}

          {audit.status === 'completed' && <IssuesTable auditId={audit.id} />}
        </>
      )}
    </div>
  )
}
