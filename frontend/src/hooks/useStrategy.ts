import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { strategiesApi, contentApi } from '../lib/endpoints'

export function useStrategies(projectId: number | undefined) {
  return useQuery({
    queryKey: ['projects', projectId, 'strategies'],
    queryFn: () => strategiesApi.list(projectId!).then((r) => r.data),
    enabled: !!projectId,
  })
}

export function useGenerateStrategy(projectId: number | undefined) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: () => strategiesApi.generate(projectId!),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['projects', projectId, 'strategies'] })
    },
  })
}

export function useContentPieces(projectId: number | undefined) {
  return useQuery({
    queryKey: ['projects', projectId, 'content'],
    queryFn: () => contentApi.list(projectId!).then((r) => r.data),
    enabled: !!projectId,
  })
}

export function useGenerateContent(projectId: number | undefined) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (keywordId?: number | void) => contentApi.generate(projectId!, keywordId || undefined),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['projects', projectId, 'content'] })
    },
  })
}
