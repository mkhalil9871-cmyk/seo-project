import { useState } from 'react'
import { useIssues } from '../hooks/useAudit'
import { Badge, Card, EmptyState, PageLoader, Select } from '../lib/ui'
import { IcoCheck } from '../lib/icons'
import type { IssueSeverity } from '../lib/types'

const SEVERITY_COLOR: Record<IssueSeverity, 'red' | 'yellow' | 'blue' | 'gray'> = {
  critical: 'red',
  high: 'red',
  medium: 'yellow',
  low: 'blue',
}

const SEVERITY_OPTIONS = [
  { value: '', label: 'All severities' },
  { value: 'critical', label: 'Critical' },
  { value: 'high', label: 'High' },
  { value: 'medium', label: 'Medium' },
  { value: 'low', label: 'Low' },
]

function humanizeType(type: string) {
  return type
    .split('_')
    .map((w) => w[0].toUpperCase() + w.slice(1))
    .join(' ')
}

export function IssuesTable({ auditId }: { auditId: number }) {
  const [severity, setSeverity] = useState('')
  const [page, setPage] = useState(1)
  const { data, isLoading } = useIssues(auditId, { severity: severity || undefined, page })

  return (
    <Card>
      <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <h3 className="text-sm font-semibold text-gray-900">Issues {data ? `(${data.total})` : ''}</h3>
        <div className="w-44">
          <Select
            value={severity}
            onChange={(v) => {
              setSeverity(v)
              setPage(1)
            }}
            options={SEVERITY_OPTIONS}
          />
        </div>
      </div>

      {isLoading ? (
        <PageLoader />
      ) : !data || data.data.length === 0 ? (
        <EmptyState
          title="No issues found"
          description={severity ? 'No issues at this severity level.' : 'This audit found no issues — nice and clean.'}
        />
      ) : (
        <>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-100">
                  <th className="text-left text-xs font-semibold text-gray-500 px-5 py-3">Severity</th>
                  <th className="text-left text-xs font-semibold text-gray-500 px-3 py-3">Issue</th>
                  <th className="text-left text-xs font-semibold text-gray-500 px-3 py-3">Page</th>
                  <th className="text-left text-xs font-semibold text-gray-500 px-5 py-3 hidden md:table-cell">
                    Detail
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {data.data.map((issue) => (
                  <tr key={issue.id} className="hover:bg-gray-50/70">
                    <td className="px-5 py-3">
                      <Badge color={SEVERITY_COLOR[issue.severity]}>{issue.severity}</Badge>
                    </td>
                    <td className="px-3 py-3">
                      <p className="text-sm font-medium text-gray-800">{humanizeType(issue.type)}</p>
                      <p className="text-xs text-gray-400">{issue.category}</p>
                    </td>
                    <td className="px-3 py-3 max-w-[220px]">
                      <a
                        href={issue.page?.url}
                        target="_blank"
                        rel="noreferrer"
                        className="text-xs font-mono text-blue-600 hover:underline truncate block"
                      >
                        {issue.page?.url ?? '—'}
                      </a>
                    </td>
                    <td className="px-5 py-3 hidden md:table-cell">
                      <span className="text-xs text-gray-500">{issue.detail ?? '—'}</span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {data.last_page > 1 && (
            <div className="flex items-center justify-between px-5 py-3 border-t border-gray-100">
              <button
                className="text-xs font-medium text-gray-600 hover:text-gray-900 disabled:opacity-40"
                disabled={page <= 1}
                onClick={() => setPage((p) => p - 1)}
              >
                Previous
              </button>
              <span className="text-xs text-gray-500">
                Page {data.current_page} of {data.last_page}
              </span>
              <button
                className="text-xs font-medium text-gray-600 hover:text-gray-900 disabled:opacity-40"
                disabled={page >= data.last_page}
                onClick={() => setPage((p) => p + 1)}
              >
                Next
              </button>
            </div>
          )}
        </>
      )}
    </Card>
  )
}

export function IssuesEmptyGood() {
  return (
    <div className="flex items-center gap-2 text-emerald-600 text-xs font-medium">
      <IcoCheck /> No issues found
    </div>
  )
}
