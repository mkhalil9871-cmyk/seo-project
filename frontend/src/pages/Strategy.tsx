import { useState } from 'react'
import { useSelectedProject, ProjectPicker } from '../components/ProjectPicker'
import { useStrategies, useGenerateStrategy, useContentPieces, useGenerateContent } from '../hooks/useStrategy'
import { useKeywords } from '../hooks/useKeywords'
import { Badge, Btn, Card, EmptyState, ErrorBanner, PageLoader, Select } from '../lib/ui'
import { IcoStrategy, IcoPlus, IcoRefresh } from '../lib/icons'
import { ApiError } from '../lib/api'

export default function StrategyPage() {
  const { projects, projectId, setProjectId } = useSelectedProject()
  const { data: strategies, isLoading: strategiesLoading } = useStrategies(projectId)
  const { data: contentPieces, isLoading: contentLoading } = useContentPieces(projectId)
  const { data: keywords } = useKeywords(projectId)
  
  const generateStrategy = useGenerateStrategy(projectId)
  const generateContent = useGenerateContent(projectId)

  const [selectedKeywordId, setSelectedKeywordId] = useState<string>('')
  const [strategyError, setStrategyError] = useState('')
  const [contentError, setContentError] = useState('')
  const [activeTab, setActiveTab] = useState<'strategy' | 'content'>('strategy')

  if (projects.length === 0) {
    return (
      <Card>
        <EmptyState title="No projects yet" description="Add a project first to generate AI SEO strategies." />
      </Card>
    )
  }

  const handleGenerateStrategy = async () => {
    setStrategyError('')
    try {
      await generateStrategy.mutateAsync()
    } catch (err) {
      setStrategyError(err instanceof ApiError ? err.message : 'Could not queue strategy generation.')
    }
  }

  const handleGenerateContent = async () => {
    setContentError('')
    try {
      await generateContent.mutateAsync(selectedKeywordId ? Number(selectedKeywordId) : undefined)
    } catch (err) {
      setContentError(err instanceof ApiError ? err.message : 'Could not queue content generation.')
    }
  }

  return (
    <div className="max-w-5xl space-y-5">
      <div className="flex items-center justify-between flex-wrap gap-3">
        <ProjectPicker projects={projects} value={projectId} onChange={setProjectId} />
        
        <div className="flex items-center gap-2">
          {activeTab === 'strategy' ? (
            <Btn size="sm" onClick={handleGenerateStrategy} disabled={generateStrategy.isPending}>
              <IcoStrategy /> {generateStrategy.isPending ? 'Queuing…' : 'Generate AI Strategy'}
            </Btn>
          ) : (
            <Btn size="sm" onClick={handleGenerateContent} disabled={generateContent.isPending}>
              <IcoPlus /> {generateContent.isPending ? 'Queuing…' : 'Generate Article'}
            </Btn>
          )}
        </div>
      </div>

      {/* Tabs */}
      <div className="flex border-b border-gray-200">
        <button
          onClick={() => setActiveTab('strategy')}
          className={`pb-2.5 px-4 text-sm font-semibold border-b-2 transition-colors ${
            activeTab === 'strategy'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700'
          }`}
        >
          AI SEO Strategies
        </button>
        <button
          onClick={() => setActiveTab('content')}
          className={`pb-2.5 px-4 text-sm font-semibold border-b-2 transition-colors ${
            activeTab === 'content'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700'
          }`}
        >
          AI Generated Content ({contentPieces?.length ?? 0})
        </button>
      </div>

      {activeTab === 'strategy' ? (
        <div className="space-y-4">
          {strategyError && <ErrorBanner message={strategyError} />}

          {strategiesLoading ? (
            <PageLoader />
          ) : !strategies || strategies.length === 0 ? (
            <Card>
              <EmptyState
                icon={<IcoStrategy />}
                title="No strategy generated yet"
                description="Click 'Generate AI Strategy' to analyze your project audit issues and create a prioritized action plan."
                action={
                  <Btn size="sm" onClick={handleGenerateStrategy} disabled={generateStrategy.isPending}>
                    <IcoStrategy /> Generate first strategy
                  </Btn>
                }
              />
            </Card>
          ) : (
            <div className="space-y-4">
              {strategies.map((strat) => (
                <Card key={strat.id} className="p-5 space-y-3">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <span className="text-sm font-bold text-gray-900">Strategy #{strat.id}</span>
                      <Badge color={strat.status === 'completed' ? 'green' : 'yellow'}>{strat.status}</Badge>
                    </div>
                    <span className="text-xs text-gray-400">
                      {strat.generated_at ? new Date(strat.generated_at).toLocaleString() : strat.created}
                    </span>
                  </div>

                  {strat.status === 'pending' ? (
                    <p className="text-xs text-amber-700 bg-amber-50 p-3 rounded-lg flex items-center gap-2">
                      <IcoRefresh /> Queued for OpenAI generation. Runs on scheduled tick (`php artisan strategy:generate`).
                    </p>
                  ) : strat.content?.text ? (
                    <div className="text-sm text-gray-700 whitespace-pre-wrap bg-gray-50 p-4 rounded-lg leading-relaxed border border-gray-100">
                      {strat.content.text}
                    </div>
                  ) : (
                    <p className="text-xs text-gray-400 italic">No content text available.</p>
                  )}
                </Card>
              ))}
            </div>
          )}
        </div>
      ) : (
        <div className="space-y-4">
          {contentError && <ErrorBanner message={contentError} />}

          {keywords && keywords.length > 0 && (
            <Card className="p-4 flex items-center gap-3">
              <div className="flex-1">
                <Select
                  label="Target Keyword for AI Content"
                  value={selectedKeywordId}
                  onChange={setSelectedKeywordId}
                  options={[
                    { value: '', label: 'Select a keyword (optional)...' },
                    ...keywords.map((k) => ({ value: String(k.id), label: k.keyword })),
                  ]}
                />
              </div>
              <div className="pt-6">
                <Btn onClick={handleGenerateContent} disabled={generateContent.isPending}>
                  <IcoPlus /> Generate Article
                </Btn>
              </div>
            </Card>
          )}

          {contentLoading ? (
            <PageLoader />
          ) : !contentPieces || contentPieces.length === 0 ? (
            <Card>
              <EmptyState
                icon={<IcoStrategy />}
                title="No articles generated yet"
                description="Use AI to generate SEO optimized blog posts and articles based on your targeted keywords."
                action={
                  <Btn size="sm" onClick={handleGenerateContent} disabled={generateContent.isPending}>
                    Generate first article
                  </Btn>
                }
              />
            </Card>
          ) : (
            <div className="space-y-4">
              {contentPieces.map((piece) => (
                <Card key={piece.id} className="p-5 space-y-3">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <h4 className="text-base font-bold text-gray-900">{piece.title || `Article #${piece.id}`}</h4>
                      <Badge color={piece.status === 'completed' ? 'green' : 'yellow'}>{piece.status}</Badge>
                    </div>
                    <span className="text-xs text-gray-400">
                      {piece.generated_at ? new Date(piece.generated_at).toLocaleString() : piece.created}
                    </span>
                  </div>

                  {piece.status === 'pending' ? (
                    <p className="text-xs text-amber-700 bg-amber-50 p-3 rounded-lg flex items-center gap-2">
                      <IcoRefresh /> Queued for generation. Runs on scheduled tick (`php artisan content:generate`).
                    </p>
                  ) : piece.body ? (
                    <div className="text-sm text-gray-700 whitespace-pre-wrap bg-gray-50 p-4 rounded-lg leading-relaxed border border-gray-100 font-sans">
                      {piece.body}
                    </div>
                  ) : (
                    <p className="text-xs text-gray-400 italic">No article body generated yet.</p>
                  )}
                </Card>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  )
}
