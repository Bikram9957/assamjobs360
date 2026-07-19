# Brainstorm Plan Draft — Fix Full Project CSS Files

## Information Gathered
- Styling entry points:
  - Public pages load: `assets/aj360.css` (via `templates/layout.php`)
  - Admin pages load: `assets/admin.css` (via admin pages like `admin/index.php`, `admin/login.php`, etc.)
- `assets/aj360.css` currently contains CSS for:
  - Mock tests UI (`.test-runner`, `.question-palette`, etc.)
  - Public auth UI (`.user-auth-card`, `.btn-search`, etc.)
  - Some header mobile tweaks and `.admin-link`
- `assets/aj360.css` currently does **not** define many classes used by core public templates:
  - `templates/home.php` uses: `hero-section`, `hero-copy`, `hero-highlights`, `hero-stat-card`, `search-panel`, `search-panel-title`, `search-icon`, `quick-links`, `content-section`, `section-heading`, `category-grid`, `category-chip`, `latest-section`, `job-card`, `department-tag`, `deadline`, `job-meta`, `job-card-bottom`, `empty-jobs`, etc.
  - `templates/jobs_list.php` uses: `alert/Bootstrap` plus `card` patterns (but also relies on overall spacing/typography styles that are currently missing in `aj360.css`).
  - `templates/job_details.php` uses mainly Bootstrap cards/typography classes; some custom cards spacing may still be missing.
- `assets/aj360.css` references CSS variables like `var(--navy)`, `var(--blue)`, `var(--line)`, `var(--muted)`, `var(--orange)` but does **not** define them at the top of the file.
- `assets/admin.css` defines admin theme variables (`--admin-navy`, `--admin-blue`, etc.) and admin page specific styles.

## Root Cause Hypothesis
- Public theme CSS is incomplete: the site’s homepage and job listing/detail components rely on custom classes that are not present in `assets/aj360.css`.
- Missing theme CSS variables in `assets/aj360.css` can cause colors to become invalid in browsers (depending on what’s globally set elsewhere).

## Plan
### 1) Add base theme variables + global defaults to `assets/aj360.css`
- Add `:root { --navy: ..., --blue: ..., --orange: ..., --muted: ..., --line: ... }`.
- Add a small set of defaults for consistent look (e.g., body font fallbacks, link styles, utility chips).

### 2) Implement missing styles for public homepage UI in `assets/aj360.css`
Create CSS sections matching template class names:
- Hero:
  - `.hero-section`, `.hero-copy`, `.hero-highlights`, `.hero-stat-card`, `.hero-highlights span`, `.hero-copy .eyebrow`
- Search panel:
  - `.search-panel`, `.search-panel-title`, `.search-icon`
- Quick links:
  - `.quick-links`, `.quick-links a`, `.quick-links a span`
- Category section:
  - `.content-section`, `.section-heading`, `.category-grid`, `.category-chip` and chip arrow/icon placement
- Latest jobs list:
  - `.job-card`, `.job-card-top`, `.department-tag`, `.deadline`, `.job-meta`, `.job-card-bottom`, `.empty-jobs`

### 3) Ensure responsive behavior
- Add/adjust `@media(max-width:767.98px)` rules to match existing breakpoints already used in the file.
- Make sure hero padding/margins align with existing mobile rules (`.hero-section`, `.search-panel`, `.quick-links`, `.category-grid`, `.section-heading h2`).

### 4) Validate admin styles are independent
- Confirm no required public CSS classes are needed in admin.
- Keep `assets/admin.css` unchanged unless conflicts show up.

## Dependent Files to be edited
- `assets/aj360.css`

## Followup steps after editing
- Hard refresh in browser for public pages:
  - `/` (home)
  - `/?p=jobs` and filtered jobs
  - job details `/?p=job&job_slug=...`
  - mock tests pages (to ensure existing CSS still works)
- Validate admin pages render with `assets/admin.css`.

## Notes / Non-goals
- We will not refactor HTML templates.
- We will not add new CSS files unless required.

