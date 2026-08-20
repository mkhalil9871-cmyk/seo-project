import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useCreateProject, useDeleteProject } from '../hooks/useProjects'
import { useProjectsWithAudits } from '../hooks/useProjectsWithAudits'
import { ApiError } from '../lib/api'
import { Btn, Card, EmptyState, ErrorBanner, Input, PageLoader, ScoreCircle, StatusBadge } from '../lib/ui'
import { IcoPlus, IcoProjects, IcoTrash, IcoX } from '../lib/icons'

export default function ProjectsPage() {
  const { data, isLoading } = useProjectsWithAudits()
  const [showCreate, setShowCreate] = useState(false)
  const navigate = useNavigate()

  return (
    <div className="max-w-6xl space-y-5">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-bold text-gray-900">Projects</h2>
          <p className="text-sm text-gray-500 mt-0.5">Every site you&apos;re tracking, in one place.</p>
        </div>
        <Btn onClick={() => setShowCreate(true)}>
          <IcoPlus /> Add project
        </Btn>
      </div>

      {isLoading ? (
        <PageLoader />
      ) : data.length === 0 ? (
        <Card>
          <EmptyState
            title="No projects yet"
            description="Add a website to start running SEO audits against it."
            action={<Btn onClick={() => setShowCreate(true)}>Add your first project</Btn>}
          />
        </Card>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {data.map(({ project, audit }) => (
            <Card key={project.id} className="p-5" onClick={() => navigate(`/projects/${project.id}`)}>
              <div className="flex items-start justify-between">
                <div className="min-w-0">
                  <p className="font-semibold text-gray-900 text-sm truncate">{project.name}</p>
                  <p className="font-mono text-xs text-gray-500 truncate mt-0.5">{project.domain}</p>
                </div>
                {audit?.overall_score != null && <ScoreCircle score={audit.overall_score} size={40} />}
              </div>
              <div className="flex items-center gap-2 mt-4">
                <StatusBadge status={audit?.status ?? project.status ?? 'Draft'} />
                {project.industry && (
                  <span className="text-xs text-gray-400 truncate">{project.industry}</span>
                )}
              </div>
              <div className="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 text-xs text-gray-500">
                <span>{audit ? `${audit.pages_crawled} pages crawled` : 'Not audited yet'}</span>
                <DeleteButton projectId={project.id} projectName={project.name} />
              </div>
            </Card>
          ))}
        </div>
      )}

      {showCreate && <CreateProjectModal onClose={() => setShowCreate(false)} />}
    </div>
  )
}

function DeleteButton({ projectId, projectName }: { projectId: number; projectName: string }) {
  const del = useDeleteProject()
  const [confirming, setConfirming] = useState(false)

  if (confirming) {
    return (
      <span className="flex items-center gap-1.5">
        <button
          className="text-red-600 font-medium hover:underline"
          onClick={(e) => {
            e.stopPropagation()
            del.mutate(projectId)
          }}
          disabled={del.isPending}
        >
          {del.isPending ? 'Deleting…' : 'Confirm delete'}
        </button>
        <button
          className="text-gray-400 hover:text-gray-600"
          onClick={(e) => {
            e.stopPropagation()
            setConfirming(false)
          }}
        >
          <IcoX />
        </button>
      </span>
    )
  }

  return (
    <button
      className="text-gray-400 hover:text-red-600 transition-colors"
      title={`Delete ${projectName}`}
      onClick={(e) => {
        e.stopPropagation()
        setConfirming(true)
      }}
    >
      <IcoTrash />
    </button>
  )
}

function CreateProjectModal({ onClose }: { onClose: () => void }) {
  const create = useCreateProject()
  const [name, setName] = useState('')
  const [domain, setDomain] = useState('')
  const [industry, setIndustry] = useState('')
  const [country, setCountry] = useState('')
  const [language, setLanguage] = useState('en')
  const [errors, setErrors] = useState<Record<string, string>>({})

  const submit = async (e: React.FormEvent) => {
    e.preventDefault()
    const errs: Record<string, string> = {}
    if (!name) errs.name = 'Project name is required.'
    if (!domain) errs.domain = 'Domain is required.'
    if (Object.keys(errs).length) {
      setErrors(errs)
      return
    }
    try {
      await create.mutateAsync({ name, domain, industry: industry || undefined, country: country || undefined, language })
      onClose()
    } catch (err) {
      if (err instanceof ApiError && err.errors) {
        const flat: Record<string, string> = {}
        Object.entries(err.errors).forEach(([field, messages]) => (flat[field] = messages[0]))
        setErrors(flat)
      } else {
        setErrors({ form: err instanceof ApiError ? err.message : 'Something went wrong.' })
      }
    }
  }

  return (
    <div className="fixed inset-0 bg-black/30 z-40 flex items-center justify-center p-4" onClick={onClose}>
      <div onClick={(e) => e.stopPropagation()} className="w-full max-w-md">
        <Card className="p-6">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                <IcoProjects />
              </div>
              <h3 className="text-sm font-semibold text-gray-900">Add a project</h3>
            </div>
            <button className="text-gray-400 hover:text-gray-600" onClick={onClose}>
              <IcoX />
            </button>
          </div>

          <form onSubmit={submit} className="space-y-3.5">
            {errors.form && <ErrorBanner message={errors.form} />}
            <Input label="Project name" placeholder="Acme Corp Blog" value={name} onChange={setName} error={errors.name} />
            <Input
              label="Domain"
              placeholder="example.com"
              value={domain}
              onChange={setDomain}
              error={errors.domain}
            />
            <div className="grid grid-cols-2 gap-3">
              <Input label="Industry (optional)" placeholder="Technology" value={industry} onChange={setIndustry} />
              <Input label="Country (optional)" placeholder="US" value={country} onChange={setCountry} />
            </div>
            <Input label="Language" placeholder="en" value={language} onChange={setLanguage} />

            <div className="flex items-center gap-2 pt-2">
              <Btn type="submit" className="flex-1 justify-center" disabled={create.isPending}>
                {create.isPending ? 'Creating…' : 'Create project'}
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
