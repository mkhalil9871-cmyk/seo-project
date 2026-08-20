import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useProject } from '../hooks/useProjects'
import { useAudit, useLatestAudit, useStartAudit } from '../hooks/useAudit'
import { AuditScoreSummary } from '../components/AuditScoreSummary'
import { AuditProgress } from '../components/AuditProgress'
import { Badge, Btn, Card, PageLoader, StatusBadge } from '../lib/ui'
import { IcoArrow, IcoAudit, IcoGlobe, IcoKeywords, IcoSERP, IcoStrategy } from '../lib/icons'

const RUNNING = ['queued', 'crawling', 'scoring']

export default function ProjectDetailPage() {
  const { id } = useParams()
  const projectId = Number(id)
  const navigate = useNavigate()
  const { data: project, isLoading } = useProject(projectId)
  const { data: latestAudit } = useLatestAudit(projectId)
  const [activeAuditId, setActiveAuditId] = useState<number | undefined>(undefined)
  const { data: activeAudit } = useAudit(activeAuditId)
  const audit = activeAudit ?? latestAudit
  const startAudit = useStartAudit()

  if (isLoading) return <PageLoader />
  if (!project) return <p className="text-sm text-gray-500">Project not found.</p>

  const handleStart = async () => {
    const res = await startAudit.mutateAsync(projectId)
    setActiveAuditId(res.audit.id)
  }

  return (
    <div className="max-w-5xl space-y-5">
      <Card className="p-5">
        <div className="flex items-start justify-between flex-wrap gap-4">
          <div>
            <div className="flex items-center gap-2">
              <h2 className="text-lg font-bold text-gray-900">{project.name}</h2>
              <StatusBadge status={audit?.status ?? project.status ?? 'Draft'} />
            </div>
            <div className="flex items-center gap-1.5 text-sm text-gray-500 mt-1">
              <IcoGlobe />
              <a href={`https://${project.domain}`} target="_blank" rel="noreferrer" className="font-mono hover:underline">
                {project.domain}
              </a>
            </div>
            <div className="flex items-center gap-2 mt-3">
              {project.industry && <Badge>{project.industry}</Badge>}
              {project.country && <Badge color="blue">{project.country}</Badge>}
              {project.language && <Badge color="purple">{project.language}</Badge>}
            </div>
          </div>
          <Btn onClick={handleStart} disabled={startAudit.isPending || (audit != null && RUNNING.includes(audit.status))}>
            {audit && RUNNING.includes(audit.status) ? 'Audit running…' : 'Run new audit'}
          </Btn>
        </div>
      </Card>

      {audit ? (
        RUNNING.includes(audit.status) || audit.status === 'failed' ? (
          <AuditProgress audit={audit} />
        ) : (
          <AuditScoreSummary audit={audit} />
        )
      ) : (
        <Card className="p-5 text-center text-sm text-gray-500">
          No audit has run for this project yet. Click &quot;Run new audit&quot; to get started.
        </Card>
      )}

      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <QuickLink
          icon={<IcoAudit />}
          title="Site Audit"
          description="Technical issues, content problems, scores"
          onClick={() => navigate(`/audit?project=${projectId}`)}
        />
        <QuickLink
          icon={<IcoKeywords />}
          title="Keywords"
          description="Rankings and search volume"
          onClick={() => navigate(`/keywords?project=${projectId}`)}
        />
        <QuickLink
          icon={<IcoSERP />}
          title="SERP Tracking"
          description="Position history over time"
          onClick={() => navigate(`/serp?project=${projectId}`)}
        />
      </div>

      <QuickLink
        icon={<IcoStrategy />}
        title="AI Strategy"
        description="Prioritized recommendations based on this project's audit"
        onClick={() => navigate(`/strategy?project=${projectId}`)}
        wide
      />

      <Link to="/projects" className="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700">
        <span className="rotate-180 inline-block">
          <IcoArrow dir="right" />
        </span>
        Back to all projects
      </Link>
    </div>
  )
}

function QuickLink({
  icon,
  title,
  description,
  onClick,
  wide = false,
}: {
  icon: React.ReactNode
  title: string
  description: string
  onClick: () => void
  wide?: boolean
}) {
  return (
    <Card
      onClick={onClick}
      className={`p-4 flex items-center gap-3 ${wide ? '' : ''}`}
    >
      <div className="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
        {icon}
      </div>
      <div className="min-w-0 flex-1">
        <p className="text-sm font-semibold text-gray-900">{title}</p>
        <p className="text-xs text-gray-500 truncate">{description}</p>
      </div>
      <span className="text-gray-300">
        <IcoArrow dir="right" />
      </span>
    </Card>
  )
}
