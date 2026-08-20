import { Card, Spinner } from '../lib/ui'
import type { Audit } from '../lib/types'

const STATUS_COPY: Record<string, string> = {
  queued: 'Queued — will start on the next scheduled tick (within a minute).',
  crawling: 'Crawling pages…',
  scoring: 'Crawl finished — calculating scores and detecting issues…',
}

export function AuditProgress({ audit }: { audit: Audit }) {
  if (audit.status === 'failed') {
    return (
      <Card className="p-5 border-red-200 bg-red-50">
        <p className="text-sm font-semibold text-red-700">Audit failed</p>
        <p className="text-xs text-red-600 mt-1">
          {audit.error_message || 'An unexpected error occurred while crawling this site.'}
        </p>
      </Card>
    )
  }

  return (
    <Card className="p-5">
      <div className="flex items-center gap-3">
        <span className="text-blue-600">
          <Spinner size={20} />
        </span>
        <div>
          <p className="text-sm font-medium text-gray-900">{STATUS_COPY[audit.status] ?? audit.status}</p>
          <p className="text-xs text-gray-500 mt-0.5">
            {audit.pages_crawled} pages crawled
            {audit.pages_queued > 0 ? ` · ${audit.pages_queued} queued` : ''}
            {audit.pages_failed > 0 ? ` · ${audit.pages_failed} failed` : ''}
          </p>
        </div>
      </div>
      <div className="h-1.5 bg-gray-100 rounded-full overflow-hidden mt-4">
        <div
          className="h-full bg-blue-500 rounded-full transition-all duration-500"
          style={{
            width:
              audit.pages_crawled + audit.pages_queued > 0
                ? `${Math.min(100, (audit.pages_crawled / (audit.pages_crawled + audit.pages_queued)) * 100)}%`
                : '8%',
          }}
        />
      </div>
    </Card>
  )
}
