import { IcoAlert } from '../lib/icons'

export function NotConnectedBanner({ feature, note }: { feature: string; note?: string }) {
  return (
    <div className="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3.5">
      <span className="text-amber-500 mt-0.5">
        <IcoAlert />
      </span>
      <div>
        <p className="text-sm font-medium text-amber-800">{feature} isn&apos;t connected yet</p>
        <p className="text-xs text-amber-700 mt-0.5">
          {note ?? 'This screen is ready — it just needs a live data source wired up on the backend.'}
        </p>
      </div>
    </div>
  )
}
