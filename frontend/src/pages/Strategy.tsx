import { useSelectedProject, ProjectPicker } from '../components/ProjectPicker'
import { NotConnectedBanner } from '../components/NotConnectedBanner'
import { Card, EmptyState } from '../lib/ui'
import { IcoStrategy } from '../lib/icons'

export default function StrategyPage() {
  const { projects, projectId, setProjectId } = useSelectedProject()

  return (
    <div className="max-w-5xl space-y-5">
      <div className="flex items-center justify-between flex-wrap gap-3">
        <ProjectPicker projects={projects} value={projectId} onChange={setProjectId} />
      </div>

      <NotConnectedBanner
        feature="AI Strategy"
        note="This is the quickest one to make real once you're ready — it just needs a backend endpoint that sends this project's audit issues to an AI API (OpenAI, Claude, etc.) and returns prioritized recommendations. No paid SEO data provider required."
      />

      <Card>
        <div className="px-5 py-4 border-b border-gray-100">
          <h3 className="text-sm font-semibold text-gray-900">Recommendations</h3>
        </div>
        <EmptyState
          icon={<IcoStrategy />}
          title="No strategy generated yet"
          description="Once connected, this will turn your audit's issues into a prioritized action plan — what to fix first and why."
        />
      </Card>
    </div>
  )
}
