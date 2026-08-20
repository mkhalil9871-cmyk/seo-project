import { useSelectedProject, ProjectPicker } from '../components/ProjectPicker'
import { NotConnectedBanner } from '../components/NotConnectedBanner'
import { Card, EmptyState } from '../lib/ui'
import { IcoKeywords } from '../lib/icons'

export default function KeywordsPage() {
  const { projects, projectId, setProjectId } = useSelectedProject()

  return (
    <div className="max-w-5xl space-y-5">
      <div className="flex items-center justify-between flex-wrap gap-3">
        <ProjectPicker projects={projects} value={projectId} onChange={setProjectId} />
      </div>

      <NotConnectedBanner
        feature="Keyword tracking"
        note="Search volume and rankings come from a paid data provider (e.g. DataForSEO, SerpApi, or Google Keyword Planner). Once that's wired up on the backend, this table will fill in automatically."
      />

      <Card>
        <div className="px-5 py-4 border-b border-gray-100">
          <h3 className="text-sm font-semibold text-gray-900">Tracked Keywords</h3>
        </div>
        <EmptyState
          icon={<IcoKeywords />}
          title="No keyword data yet"
          description="This project has no keywords being tracked. Once the keyword data source is connected, add keywords here to monitor their ranking."
        />
      </Card>
    </div>
  )
}
