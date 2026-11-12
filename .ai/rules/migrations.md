---
paths:
  - 'database/migrations/**'
---

# Migrations

## Production is live — add new migrations, never edit deployed ones
Owner decision (2026-11-12): production holds real orders, settings and Stripe keys, so schema changes get a new migration (`php artisan make:migration add_x_to_y_table`) that runs with `php artisan migrate`. Never edit a migration that has already run in production and never run `migrate:fresh` there. Backfill existing rows in the same deploy when a change needs it (see `backfill_camp_schedules`). This replaces the pre-launch rule (2026-08-16) of editing create migrations in place.

Migration order still matters: orders is created before camps/camp_registrations because camp_registrations FKs orders, and the camp schedule tables (camp_age_groups → camp_sessions → camp_session_days → camp_day_times → camp_registration_days) must be created in that order.
