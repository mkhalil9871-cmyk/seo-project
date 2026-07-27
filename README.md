# SEO Project
 
A full-stack web application with a Laravel backend (API) and a React (Vite) frontend.
 
## Tech Stack
 
- **Backend:** Laravel (PHP), Sanctum for API authentication
- **Frontend:** React + TypeScript, built with Vite
- **Database:** SQLite (dev) — configurable in `backend/.env`
## Project Structure
 
```
seo-project/
├── backend/     # Laravel API
└── frontend/    # React (Vite) app
```
 
## Prerequisites
 
Before you start, install these on your machine:
 
- **PHP + Composer** — easiest via [Laravel Herd](https://herd.laravel.com) (Windows/Mac). This bundles PHP and Composer together.
- **Node.js** (includes npm) — [nodejs.org](https://nodejs.org)
- **Git**
Verify everything is installed:
```bash
php --version
composer --version
node --version
npm --version
```
 
## Getting Started
 
### 1. Clone the repo
```bash
git clone https://github.com/mkhalil9871-cmyk/seo-project.git
cd seo-project
```
 
### 2. Backend setup (Laravel)
```bash
cd backend
composer install
copy .env.example .env      # Windows (PowerShell/CMD)
# cp .env.example .env      # Mac/Linux
 
php artisan key:generate
php artisan migrate
```
 
Start the backend server:
```bash
php artisan serve
```
Runs at `http://127.0.0.1:8000`
 
### 3. Frontend setup (React + Vite)
 
Open a **new terminal** (keep the backend running in the first one):
```bash
cd frontend
npm install
npm run dev
```
Runs at `http://localhost:5173`
 
### 4. Confirm it's working
 
Open `http://localhost:5173` in your browser — the React app should load and successfully communicate with the Laravel API.
 
## Development Notes
 
- **CORS**: configured in `backend/config/cors.php`. If the frontend runs on a different port than `5173`, update `allowed_origins` there.
- **API routes**: defined in `backend/routes/api.php`, all prefixed with `/api/...` automatically.
- **Environment files**: `backend/.env` and any frontend `.env` files are gitignored — never commit these. Use `.env.example` as the template for required variables.
- **Dependencies** (`vendor/`, `node_modules/`) are **not** committed to the repo — always run `composer install` and `npm install` after pulling changes that touch `composer.json` or `package.json`.
## Branching Workflow
 
- `main` — stable, working code only
- Create a feature branch for new work: `git checkout -b feature/your-feature-name`
- Open a Pull Request into `main` when ready for review
- Pull latest changes before starting new work: `git pull origin main`
## Common Commands
 
| Task | Command |
|---|---|
| Run backend | `cd backend && php artisan serve` |
| Run frontend | `cd frontend && npm run dev` |
| Run backend migrations | `cd backend && php artisan migrate` |
| Install backend deps | `cd backend && composer install` |
| Install frontend deps | `cd frontend && npm install` |
 