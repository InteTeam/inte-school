# Migration 006 — Add School Foreign Keys

**File:** `2025_01_01_000006_add_school_foreign_keys.php`
**Depends on:** schools (005), registered_devices (003), action_tokens (004)

## Purpose

Adds deferred `school_id` foreign keys to the `registered_devices` and
`action_tokens` tables. These columns were created nullable in their original
migrations because the schools table didn't exist yet.

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| registered_devices.school_id | schools.id | CASCADE |
| action_tokens.school_id | schools.id | CASCADE |

## Notes

- This pattern (create column first, add FK later) avoids circular dependency
  between auth-related tables and the schools table.
- Both `school_id` columns were created as `VARCHAR(26) nullable` in migrations
  003 and 004 — this migration only adds the foreign key constraint, it does not
  alter the column type or nullability.
- The `CASCADE` delete rule means removing a school will automatically remove all
  associated registered devices and action tokens.
