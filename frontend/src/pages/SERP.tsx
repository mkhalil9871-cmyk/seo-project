import { useSelectedProject, ProjectPicker } from '../components/ProjectPicker'
import { NotConnectedBanner } from '../components/NotConnectedBanner'
import { Card, EmptyState } from '../lib/ui'
import { IcoSERP } from '../lib/icons'

export default function SERPPage() {
  const { projects, projectId, setProjectId } = useSelectedProject()

  return (
    <div className="max-w-5xl space-y-5">
      <div className="flex items-center justify-between flex-wrap gap-3">
        <ProjectPicker projects={projects} value={projectId} onChange={setProjectId} />
      </div>

      <NotConnectedBanner
        feature="SERP tracking"
        note="Daily search-position snapshots require a SERP data provider (e.g. SerpApi, ValueSerp). Once that's wired up, position history and movement charts will appear here."
      />

      <Card>
        <div className="px-5 py-4 border-b border-gray-100">
          <h3 className="text-sm font-semibold text-gray-900">Ranking History</h3>
        </div>
        <EmptyState
          icon={<IcoSERP />}
          title="No ranking history yet"
          description="Once SERP tracking is connected, daily position changes for this project's keywords will show here as a trend."
        />
      </Card>
    </div>
  )
}
