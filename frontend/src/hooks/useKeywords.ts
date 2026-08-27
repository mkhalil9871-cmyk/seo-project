import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { keywordsApi, rankTrackingApi, type KeywordInput } from '../lib/endpoints'

export function useKeywords(projectId: number | undefined) {
  return useQuery({
    queryKey: ['projects', projectId, 'keywords'],
    queryFn: () => keywordsApi.list(projectId!).then((r) => r.data),
    enabled: !!projectId,
  })
}

export function useCreateKeyword(projectId: number | undefined) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: KeywordInput) => keywordsApi.create(projectId!, input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['projects', projectId, 'keywords'] })
    },
  })
}

export function useDeleteKeyword(projectId: number | undefined) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (keywordId: number) => keywordsApi.delete(projectId!, keywordId),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['projects', projectId, 'keywords'] })
    },
  })
}

export function useRefreshKeyword(projectId: number | undefined) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (keywordId: number) => keywordsApi.refresh(projectId!, keywordId),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['projects', projectId, 'keywords'] })
    },
  })
}

export function useRankTrackings(projectId: number | undefined, keywordId: number | undefined) {
  return useQuery({
    queryKey: ['projects', projectId, 'keywords', keywordId, 'rank-trackings'],
    queryFn: () => rankTrackingApi.list(projectId!, keywordId!),
    enabled: !!projectId && !!keywordId,
  })
}

export function useCheckRankTracking(projectId: number | undefined, keywordId: number | undefined) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (domain?: string | void) => rankTrackingApi.check(projectId!, keywordId!, domain || undefined),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['projects', projectId, 'keywords', keywordId, 'rank-trackings'] })
      qc.invalidateQueries({ queryKey: ['projects', projectId, 'keywords'] })
    },
  })
}
