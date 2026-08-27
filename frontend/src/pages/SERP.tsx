import { useState } from 'react'
import { useSelectedProject, ProjectPicker } from '../components/ProjectPicker'
import { useKeywords, useRankTrackings, useCheckRankTracking } from '../hooks/useKeywords'
import { Badge, Btn, Card, EmptyState, ErrorBanner, PageLoader } from '../lib/ui'
import { IcoSERP, IcoRefresh } from '../lib/icons'
import { ApiError } from '../lib/api'
import type { Keyword } from '../lib/types'

export default function SERPPage() {
  const { projects, projectId, setProjectId } = useSelectedProject()
  const { data: keywords, isLoading } = useKeywords(projectId)
  const [selectedKeywordId, setSelectedKeywordId] = useState<number | undefined>(undefined)

  const activeKeyword = keywords?.find((k) => k.id === selectedKeywordId) ?? keywords?.[0]

  if (projects.length === 0) {
    return (
      <Card>
        <EmptyState title="No projects yet" description="Add a project first to track SERP rankings." />
      </Card>
    )
  }

  return (
    <div className="max-w-5xl space-y-5">
      <div className="flex items-center justify-between flex-wrap gap-3">
        <ProjectPicker projects={projects} value={projectId} onChange={setProjectId} />
      </div>

      {isLoading ? (
        <PageLoader />
      ) : !keywords || keywords.length === 0 ? (
        <Card>
          <EmptyState
            icon={<IcoSERP />}
            title="No keywords to track"
            description="Add keywords to this project under the Keywords tab first, then view SERP rank history here."
          />
        </Card>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
          {/* Keyword list selector */}
          <Card className="p-4 md:col-span-1 space-y-2">
            <h4 className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Select Keyword</h4>
            <div className="space-y-1">
              {keywords.map((kw) => {
                const isSelected = kw.id === (activeKeyword?.id)
                return (
                  <button
                    key={kw.id}
                    onClick={() => setSelectedKeywordId(kw.id)}
                    className={`w-full text-left px-3 py-2.5 rounded-lg text-sm transition-all flex items-center justify-between ${
                      isSelected
                        ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-200'
                        : 'text-gray-700 hover:bg-gray-50'
                    }`}
                  >
                    <span className="truncate">{kw.keyword}</span>
                    <Badge color={kw.serp_status === 'success' ? 'green' : 'yellow'}>{kw.serp_status}</Badge>
                  </button>
                )
              })}
            </div>
          </Card>

          {/* Keyword Rank Details */}
          <div className="md:col-span-2 space-y-4">
            {activeKeyword ? (
              <KeywordRankDetail projectId={projectId!} keyword={activeKeyword} />
            ) : null}
          </div>
        </div>
      )}
    </div>
  )
}

function KeywordRankDetail({ projectId, keyword }: { projectId: number; keyword: Keyword }) {
  const { data: trackings, isLoading } = useRankTrackings(projectId, keyword.id)
  const checkRank = useCheckRankTracking(projectId, keyword.id)
  const [error, setError] = useState('')

  const handleCheckNow = async () => {
    setError('')
    try {
      await checkRank.mutateAsync()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Failed to check rank position.')
    }
  }

  const latestPosition = trackings?.[0]?.position ?? null

  return (
    <Card className="p-5 space-y-4">
      <div className="flex items-start justify-between flex-wrap gap-3">
        <div>
          <div className="flex items-center gap-2">
            <h3 className="text-base font-bold text-gray-900">{keyword.keyword}</h3>
            <Badge color="blue">{keyword.cluster || 'General'}</Badge>
          </div>
          <p className="text-xs text-gray-500 mt-1">
            Current Rank:{' '}
            <strong className="text-sm text-gray-900">
              {latestPosition ? `#${latestPosition}` : 'Not ranked / pending'}
            </strong>
          </p>
        </div>
        <Btn size="sm" onClick={handleCheckNow} disabled={checkRank.isPending}>
          <IcoRefresh /> {checkRank.isPending ? 'Checking…' : 'Check Rank Now'}
        </Btn>
      </div>

      {error && <ErrorBanner message={error} />}

      <div className="border-t border-gray-100 pt-3">
        <h4 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Rank Tracking History</h4>
        {isLoading ? (
          <PageLoader />
        ) : !trackings || trackings.length === 0 ? (
          <p className="text-xs text-gray-400 py-6 text-center">
            No position snapshots recorded yet. Background cron or manual check will log rankings.
          </p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-xs">
              <thead>
                <tr className="border-b border-gray-100 text-left text-gray-500">
                  <th className="py-2 px-3">Date</th>
                  <th className="py-2 px-3">Position</th>
                  <th className="py-2 px-3">Engine</th>
                  <th className="py-2 px-3">Target Domain</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {trackings.map((t) => (
                  <tr key={t.id} className="hover:bg-gray-50">
                    <td className="py-2 px-3 text-gray-600">{t.checked_at}</td>
                    <td className="py-2 px-3 font-semibold text-gray-900">{t.position ? `#${t.position}` : 'N/A'}</td>
                    <td className="py-2 px-3 capitalize text-gray-500">{t.search_engine}</td>
                    <td className="py-2 px-3 font-mono text-gray-500">{t.domain}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </Card>
  )
}
