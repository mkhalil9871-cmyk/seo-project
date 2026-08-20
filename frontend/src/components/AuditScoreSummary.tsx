import { Card, ScoreCircle } from '../lib/ui'
import { IcoArrow } from '../lib/icons'
import type { Audit } from '../lib/types'

export function AuditScoreSummary({ audit }: { audit: Audit }) {
  const delta = audit.comparison?.score_delta

  return (
    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <Card className="p-5 flex items-center gap-4">
        <ScoreCircle score={audit.overall_score ?? 0} size={64} />
        <div>
          <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">Overall Score</p>
          {delta != null && delta !== 0 && (
            <p className={`text-xs font-medium mt-1 flex items-center gap-1 ${delta > 0 ? 'text-emerald-600' : 'text-red-500'}`}>
              <IcoArrow dir={delta > 0 ? 'up' : 'down'} />
              {Math.abs(delta).toFixed(1)} vs last audit
            </p>
          )}
        </div>
      </Card>
      <Card className="p-5 flex items-center gap-4">
        <ScoreCircle score={audit.technical_score ?? 0} size={64} />
        <div>
          <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">Technical Score</p>
          <p className="text-xs text-gray-400 mt-1">crawlability, structure, links</p>
        </div>
      </Card>
      <Card className="p-5 flex items-center gap-4">
        <ScoreCircle score={audit.content_score ?? 0} size={64} />
        <div>
          <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">Content Score</p>
          <p className="text-xs text-gray-400 mt-1">titles, descriptions, duplication</p>
        </div>
      </Card>
    </div>
  )
}
