import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { useProjectsWithAudits } from '../hooks/useProjectsWithAudits'
import { Btn, Card, EmptyState, PageLoader, ScoreCircle, StatusBadge } from '../lib/ui'
import { IcoArrow, IcoProjects } from '../lib/icons'

function greeting() {
  const h = new Date().getHours()
  if (h < 12) return 'Good morning'
  if (h < 18) return 'Good afternoon'
  return 'Good evening'
}

export default function DashboardPage() {
  const { user } = useAuth()
  const navigate = useNavigate()
  const { data, isLoading } = useProjectsWithAudits()

  if (isLoading) return <PageLoader />

  const totalProjects = data.length
  const runningAudits = data.filter((d) => d.audit && ['queued', 'crawling', 'scoring'].includes(d.audit.status)).length
  const completedAudits = data.filter((d) => d.audit?.status === 'completed')
  const avgScore = completedAudits.length
    ? Math.round(completedAudits.reduce((sum, d) => sum + (d.audit!.overall_score ?? 0), 0) / completedAudits.length)
    : null
  const totalOpenPages = data.reduce((sum, d) => sum + (d.audit?.pages_failed ?? 0), 0)

  const firstName = user?.name?.split(' ')[0] ?? 'there'

  return (
    <div className="max-w-6xl space-y-6">
      <div>
        <h2 className="text-xl font-bold text-gray-900">
          {greeting()}, {firstName} 👋
        </h2>
        <p className="text-sm text-gray-500 mt-0.5">Here&apos;s what&apos;s happening across your projects today.</p>
      </div>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <Card className="p-5">
          <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Projects</p>
          <p className="text-2xl font-bold text-gray-900 mt-1">{totalProjects}</p>
          <p className="text-xs mt-1 font-medium text-gray-400">tracked sites</p>
        </Card>
        <Card className="p-5">
          <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">Audits Running</p>
          <p className="text-2xl font-bold text-gray-900 mt-1">{runningAudits}</p>
          <p className="text-xs mt-1 font-medium text-gray-400">in progress now</p>
        </Card>
        <Card className="p-5">
          <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">Average Score</p>
          <p className="text-2xl font-bold text-gray-900 mt-1">{avgScore ?? '—'}</p>
          <p className="text-xs mt-1 font-medium text-gray-400">
            {completedAudits.length ? `across ${completedAudits.length} completed audits` : 'no completed audits yet'}
          </p>
        </Card>
        <Card className="p-5">
          <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">Failed Pages</p>
          <p className="text-2xl font-bold text-gray-900 mt-1">{totalOpenPages}</p>
          <p className="text-xs mt-1 font-medium text-gray-400">across latest audits</p>
        </Card>
      </div>

      <Card>
        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100">
          <h3 className="text-sm font-semibold text-gray-900">Recent Projects</h3>
          <Link to="/projects">
            <Btn variant="ghost" size="sm">
              View all <IcoArrow dir="right" />
            </Btn>
          </Link>
        </div>

        {data.length === 0 ? (
          <EmptyState
            title="No projects yet"
            description="Add your first site to start running SEO audits."
            action={
              <Btn size="sm" onClick={() => navigate('/projects')}>
                Add a project
              </Btn>
            }
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-100">
                  <th className="text-left text-xs font-semibold text-gray-500 px-5 py-3">Project</th>
                  <th className="text-left text-xs font-semibold text-gray-500 px-3 py-3 hidden sm:table-cell">
                    Domain
                  </th>
                  <th className="text-left text-xs font-semibold text-gray-500 px-3 py-3">Status</th>
                  <th className="text-center text-xs font-semibold text-gray-500 px-3 py-3">Score</th>
                  <th className="text-right text-xs font-semibold text-gray-500 px-5 py-3 hidden md:table-cell">
                    Pages Crawled
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {data.slice(0, 5).map(({ project, audit }) => (
                  <tr
                    key={project.id}
                    onClick={() => navigate(`/projects/${project.id}`)}
                    className="hover:bg-gray-50/70 cursor-pointer transition-colors"
                  >
                    <td className="px-5 py-3.5">
                      <p className="font-medium text-gray-900 text-sm">{project.name}</p>
                    </td>
                    <td className="px-3 py-3.5 hidden sm:table-cell">
                      <span className="font-mono text-xs text-gray-500">{project.domain}</span>
                    </td>
                    <td className="px-3 py-3.5">
                      <StatusBadge status={audit?.status ?? project.status ?? 'Draft'} />
                    </td>
                    <td className="px-3 py-3.5 text-center">
                      {audit?.overall_score != null ? (
                        <ScoreCircle score={audit.overall_score} size={42} />
                      ) : (
                        <span className="text-xs text-gray-400">—</span>
                      )}
                    </td>
                    <td className="px-5 py-3.5 text-right hidden md:table-cell">
                      <span className="text-xs font-semibold text-gray-600">{audit?.pages_crawled ?? 0}</span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>

      {data.length === 0 && (
        <Card className="p-8 flex flex-col items-center text-center">
          <div className="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 mb-3">
            <IcoProjects />
          </div>
          <h3 className="text-sm font-semibold text-gray-900">Get started with your first audit</h3>
          <p className="text-xs text-gray-500 mt-1 max-w-sm">
            Add a website, then run a crawl to see technical issues, content problems, and a real SEO score.
          </p>
          <Link to="/projects" className="mt-4">
            <Btn size="sm">Add a project</Btn>
          </Link>
        </Card>
      )}
    </div>
  )
}
