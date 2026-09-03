---
paths:
  - 'database/migrations/**'
---

# Migrations

## Edit create migrations in place — never add alter migrations
Pre-launch decision (owner, 2026-08-16): when a table's schema changes, edit the existing `create_*_table` migration and run `php artisan migrate:fresh --seed`. Do not generate `add_x_to_y_table` migrations. Migration order matters: orders is created before camps/camp_registrations because camp_registrations FKs orders.
