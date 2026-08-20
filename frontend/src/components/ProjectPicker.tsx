import { useEffect } from 'react'
import { useSearchParams } from 'react-router-dom'
import { useProjects } from '../hooks/useProjects'
import { Select } from '../lib/ui'

/** Reads/writes the selected project as a `?project=` URL param so the choice
 * survives a refresh and can be shared as a link. */
export function useSelectedProject() {
  const { data: projects } = useProjects()
  const [params, setParams] = useSearchParams()
  const projectId = params.get('project') ? Number(params.get('project')) : undefined

  // Auto-select the first project once the list loads, if nothing is chosen yet.
  useEffect(() => {
    if (!projectId && projects && projects.length > 0) {
      setParams({ project: String(projects[0].id) }, { replace: true })
    }
  }, [projectId, projects, setParams])

  const setProjectId = (id: number) => setParams({ project: String(id) })

  return { projects: projects ?? [], projectId, setProjectId }
}

export function ProjectPicker({
  projects,
  value,
  onChange,
}: {
  projects: { id: number; name: string; domain: string }[]
  value: number | undefined
  onChange: (id: number) => void
}) {
  if (projects.length === 0) return null

  return (
    <div className="w-64">
      <Select
        value={value ? String(value) : ''}
        onChange={(v) => onChange(Number(v))}
        options={projects.map((p) => ({ value: String(p.id), label: `${p.name} — ${p.domain}` }))}
      />
    </div>
  )
}
