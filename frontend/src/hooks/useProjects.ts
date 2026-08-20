import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { projectsApi, type ProjectInput } from '../lib/endpoints'

export function useProjects() {
  return useQuery({
    queryKey: ['projects'],
    queryFn: () => projectsApi.list().then((r) => r.data),
  })
}

export function useProject(id: number | undefined) {
  return useQuery({
    queryKey: ['projects', id],
    queryFn: () => projectsApi.get(id!).then((r) => r.data),
    enabled: !!id,
  })
}

export function useCreateProject() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: ProjectInput) => projectsApi.create(input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['projects'] }),
  })
}

export function useDeleteProject() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => projectsApi.remove(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['projects'] }),
  })
}
