# SEO Engine — Frontend

React + TypeScript + Vite + Tailwind v4 dashboard, built from the Figma design and wired to the real Laravel backend.

## Setup

```powershell
npm install
npm run dev
```

Opens on `http://localhost:5173`. Backend must be running on `http://127.0.0.1:8000` (default — change via `.env`, `VITE_API_URL`).

```powershell
npm run build     # production build → dist/
npm run preview   # preview the production build locally
```

## What's real vs. not yet connected

| Page | Status |
|---|---|
| Login / Register | ✅ Real — `/api/login`, `/api/register` |
| Dashboard | ✅ Real — project count, average score, failed pages, recent projects (all computed from real audits) |
| Projects | ✅ Real — list, create, delete (`/api/projects`) |
| Project Detail | ✅ Real — project info, latest audit, quick links |
| Site Audit | ✅ Real — start audit, live progress polling, real issues table with severity filter, CSV exports |
| Keywords | ⏳ UI shell only — needs a keyword data provider (DataForSEO, SerpApi, Ahrefs API, or Google Keyword Planner) wired up server-side. Shows an honest "not connected" state, not fake numbers. |
| SERP Tracking | ⏳ UI shell only — needs a SERP data provider (SerpApi, ValueSerp, etc.). Same honest empty state. |
| AI Strategy | ⏳ UI shell only — this one's cheap to make real: a backend endpoint that sends a project's audit issues to an AI API (OpenAI/Claude/etc.) and returns prioritized recommendations. No paid SEO data provider needed. |

**Why Keywords/SERP aren't faked**: search volume and ranking position data can only come from a real data provider — there's no way to compute them from scratch. Showing made-up numbers there would look real but be completely wrong, which could lead to bad decisions. So those pages show a clear "not connected yet" banner instead, with the same polish as the rest of the app — ready to wire up the moment there's a data source.

## One backend addition needed

The frontend needs to know "what's this project's latest audit" after a page refresh (there was no endpoint for that — only starting a new audit, or fetching one by its ID). Add this to `AuditController.php`:

```php
public function latest(Project $project)
{
    $this->authorizeProjectOwner($project);

    $audit = $project->audits()->latest()->first();

    abort_if(! $audit, 404, 'This project has not been audited yet.');

    return response()->json($audit);
}
```

And this route in `routes/api.php`, right above the existing `audits` POST route:

```php
Route::get('/projects/{project}/audits/latest', [AuditController::class, 'latest']);
```

Everything else in the frontend talks to endpoints that already exist.

## Performance choices made

- **Route-based code splitting** (`React.lazy` per page) — visiting the Dashboard never downloads the Keywords/SERP/Strategy/Audit code. Confirmed in the production build: each page is its own ~1–5 KB chunk.
- **Vendor chunk splitting** — React, React Router, and React Query are split into their own cacheable chunks, separate from app code that changes more often.
- **React Query caching** (`staleTime: 30s`) — switching between Dashboard → Projects → back doesn't re-fetch data that's still fresh.
- **Smart audit polling** — the audit status endpoint is only polled every 4s, and only while the audit is actually running (`queued`/`crawling`/`scoring`); polling stops itself the instant the audit completes or fails.
- **`prod build`** uses Vite's default esbuild minification + tree-shaking (unused code, like `recharts` until a page actually imports it, is dropped entirely from the bundle — confirmed a near-empty chunk in the build output).

## Project structure

```
src/
  lib/         api.ts (fetch wrapper), endpoints.ts (typed API calls), types.ts, ui.tsx (design system), icons.tsx
  context/     AuthContext.tsx
  hooks/       useProjects, useAudit, useProjectsWithAudits (React Query)
  components/  Sidebar, Header, AppLayout, ProtectedRoute, ProjectPicker, AuditScoreSummary, AuditProgress, IssuesTable, NotConnectedBanner
  pages/       Login, Register, Dashboard, Projects, ProjectDetail, Audit, Keywords, SERP, Strategy
```
