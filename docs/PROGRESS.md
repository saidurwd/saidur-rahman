# Progress

| Phase | Status |
| --- | --- |
| 0 · Foundation | Done (awaiting review and tag) |
| 1 · Data model | Pending |
| 2 · Admin shell | Pending |
| 3 · Design system | Pending |
| 4 · Admin modules | Pending |
| 5 · Public pages | Pending |
| 6 · Blog | Pending |
| 7 · Contact & CV | Pending |
| 8 · Hardening | Pending |
| 9 · Launch | Pending |

## Phase 0 notes
- Laravel 13, PHP 8.4 locally (8.3+ required), Pest 5, Larastan level 6.
- Two Vite entries: `site` (Tailwind 4 + Alpine) and `admin` (plain placeholder until Phase 2).
- Local DB: MySQL `saidur_rahman` (credentials in git-ignored `.env`). Tests run on in-memory SQLite per `phpunit.xml`.
