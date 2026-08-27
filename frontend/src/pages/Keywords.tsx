import { useState } from 'react'
import { useSelectedProject, ProjectPicker } from '../components/ProjectPicker'
import { useKeywords, useCreateKeyword, useDeleteKeyword, useRefreshKeyword } from '../hooks/useKeywords'
import { Badge, Btn, Card, EmptyState, ErrorBanner, Input, PageLoader, Select } from '../lib/ui'
import { IcoKeywords, IcoPlus, IcoRefresh, IcoTrash, IcoX } from '../lib/icons'
import { ApiError } from '../lib/api'
import type { Keyword } from '../lib/types'

export default function KeywordsPage() {
  const { projects, projectId, setProjectId } = useSelectedProject()
  const { data: keywords, isLoading } = useKeywords(projectId)
  const [showAdd, setShowAdd] = useState(false)

  if (projects.length === 0) {
    return (
      <Card>
        <EmptyState title="No projects yet" description="Add a project first, then you can manage keywords." />
      </Card>
    )
  }

  return (
    <div className="max-w-5xl space-y-5">
      <div className="flex items-center justify-between flex-wrap gap-3">
        <ProjectPicker projects={projects} value={projectId} onChange={setProjectId} />
        <Btn size="sm" onClick={() => setShowAdd(true)} disabled={!projectId}>
          <IcoPlus /> Add keyword
        </Btn>
      </div>

      <Card>
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="text-sm font-semibold text-gray-900">Tracked Keywords</h3>
          {keywords && (
            <span className="text-xs text-gray-500 font-medium">
              {keywords.length} {keywords.length === 1 ? 'keyword' : 'keywords'}
            </span>
          )}
        </div>

        {isLoading ? (
          <PageLoader />
        ) : !keywords || keywords.length === 0 ? (
          <EmptyState
            icon={<IcoKeywords />}
            title="No keywords added yet"
            description="Add target search queries to track rankings, analyze SERP positions, and generate targeted content."
            action={
              <Btn size="sm" onClick={() => setShowAdd(true)}>
                <IcoPlus /> Add your first keyword
              </Btn>
            }
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-100 text-xs font-semibold text-gray-500 text-left">
                  <th className="px-5 py-3">Keyword</th>
                  <th className="px-3 py-3">Cluster</th>
                  <th className="px-3 py-3">Intent</th>
                  <th className="px-3 py-3">SERP Status</th>
                  <th className="px-5 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {keywords.map((kw) => (
                  <KeywordRow key={kw.id} keyword={kw} projectId={projectId!} />
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>

      {showAdd && <AddKeywordModal projectId={projectId!} onClose={() => setShowAdd(false)} />}
    </div>
  )
}

function KeywordRow({ keyword, projectId }: { keyword: Keyword; projectId: number }) {
  const del = useDeleteKeyword(projectId)
  const refresh = useRefreshKeyword(projectId)
  const [confirmDelete, setConfirmDelete] = useState(false)

  const intentColor = {
    informational: 'blue',
    commercial: 'purple',
    transactional: 'green',
    navigational: 'yellow',
  } as const

  return (
    <tr className="hover:bg-gray-50/70 transition-colors">
      <td className="px-5 py-3.5 font-medium text-gray-900">{keyword.keyword}</td>
      <td className="px-3 py-3.5 text-gray-500 text-xs">{keyword.cluster || '—'}</td>
      <td className="px-3 py-3.5">
        {keyword.intent ? (
          <Badge color={intentColor[keyword.intent] ?? 'gray'}>{keyword.intent}</Badge>
        ) : (
          <span className="text-gray-400 text-xs">—</span>
        )}
      </td>
      <td className="px-3 py-3.5">
        <Badge color={keyword.serp_status === 'success' ? 'green' : keyword.serp_status === 'pending' ? 'yellow' : 'red'}>
          {keyword.serp_status}
        </Badge>
      </td>
      <td className="px-5 py-3.5 text-right space-x-2">
        <button
          onClick={() => refresh.mutate(keyword.id)}
          disabled={refresh.isPending}
          className="text-xs text-blue-600 hover:text-blue-800 disabled:opacity-50 inline-flex items-center gap-1"
          title="Queue SERP refresh"
        >
          <IcoRefresh /> Refresh
        </button>

        {confirmDelete ? (
          <span className="inline-flex items-center gap-1.5 ml-2">
            <button
              onClick={() => del.mutate(keyword.id)}
              disabled={del.isPending}
              className="text-xs text-red-600 hover:underline font-semibold"
            >
              {del.isPending ? 'Deleting…' : 'Confirm'}
            </button>
            <button onClick={() => setConfirmDelete(false)} className="text-gray-400 hover:text-gray-600">
              <IcoX />
            </button>
          </span>
        ) : (
          <button
            onClick={() => setConfirmDelete(true)}
            className="text-gray-400 hover:text-red-600 text-xs ml-2"
            title="Delete keyword"
          >
            <IcoTrash />
          </button>
        )}
      </td>
    </tr>
  )
}

function AddKeywordModal({ projectId, onClose }: { projectId: number; onClose: () => void }) {
  const create = useCreateKeyword(projectId)
  const [keyword, setKeyword] = useState('')
  const [cluster, setCluster] = useState('')
  const [intent, setIntent] = useState<string>('')
  const [error, setError] = useState('')

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!keyword.trim()) {
      setError('Keyword is required.')
      return
    }
    setError('')
    try {
      await create.mutateAsync({
        keyword: keyword.trim(),
        cluster: cluster.trim() || undefined,
        intent: (intent as any) || undefined,
      })
      onClose()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not add keyword.')
    }
  }

  return (
    <div className="fixed inset-0 bg-black/30 z-40 flex items-center justify-center p-4" onClick={onClose}>
      <div onClick={(e) => e.stopPropagation()} className="w-full max-w-md">
        <Card className="p-6">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                <IcoKeywords />
              </div>
              <h3 className="text-sm font-semibold text-gray-900">Add Keyword</h3>
            </div>
            <button className="text-gray-400 hover:text-gray-600" onClick={onClose}>
              <IcoX />
            </button>
          </div>

          <form onSubmit={handleSubmit} className="space-y-3.5">
            {error && <ErrorBanner message={error} />}
            <Input
              label="Keyword"
              placeholder="e.g. best seo tools 2026"
              value={keyword}
              onChange={setKeyword}
            />
            <Input
              label="Cluster / Group (optional)"
              placeholder="e.g. Tools, Commercial"
              value={cluster}
              onChange={setCluster}
            />
            <Select
              label="Search Intent (optional)"
              value={intent}
              onChange={setIntent}
              options={[
                { value: '', label: 'Select Intent...' },
                { value: 'informational', label: 'Informational' },
                { value: 'commercial', label: 'Commercial' },
                { value: 'transactional', label: 'Transactional' },
                { value: 'navigational', label: 'Navigational' },
              ]}
            />

            <div className="flex items-center gap-2 pt-2">
              <Btn type="submit" className="flex-1 justify-center" disabled={create.isPending}>
                {create.isPending ? 'Adding…' : 'Add keyword'}
              </Btn>
              <Btn type="button" variant="outline" onClick={onClose}>
                Cancel
              </Btn>
            </div>
          </form>
        </Card>
      </div>
    </div>
  )
}
