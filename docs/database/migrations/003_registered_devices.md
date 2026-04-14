# Migration 003 — Create Registered Devices Table

**File:** `2025_01_01_000003_create_registered_devices_table.php`
**Depends on:** users table

## Purpose

Stores browser/device registrations for push notifications and device trust.
Each record links a user to a specific device fingerprint with optional VAPID
push subscription data for the service worker notification flow.

## Schema

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | ULID | PK | Standard ULID primary key |
| school_id | VARCHAR(26) | nullable | FK added in migration 006 (schools table doesn't exist yet) |
| user_id | VARCHAR(26) | not null | FK to users |
| device_name | VARCHAR | nullable | User-friendly label (e.g. "Chrome on Pixel 7") |
| device_fingerprint | VARCHAR | not null | Browser fingerprint for device identification |
| push_subscription | JSONB | nullable | Browser push endpoint + p256dh + auth keys |
| last_seen_at | TIMESTAMP | not null | Last activity timestamp |
| trusted_at | TIMESTAMP | nullable | When device was marked trusted (null = untrusted) |
| created_at | TIMESTAMP | — | Laravel timestamp |
| updated_at | TIMESTAMP | — | Laravel timestamp |

## Indexes

| Name | Columns | Notes |
|---|---|---|
| idx_registered_devices_user_device | user_id, school_id | Fast lookup of devices per user per school |
| idx_registered_devices_school_created | school_id, created_at | Tenant-scoped listing with default sort order |

## Foreign Keys

| Column | References | On Delete |
|---|---|---|
| user_id | users.id | CASCADE |

## Notes

- `school_id` FK is deferred to migration 006 because the schools table doesn't
  exist yet at this point in the migration sequence.
- `push_subscription` stores the browser push endpoint + keys as JSONB, matching
  the structure returned by `PushManager.subscribe()`. Cast as `array` in the model.
- `trusted_at` being nullable distinguishes trusted vs untrusted devices — a null
  value means the device has not been explicitly trusted by the user.
