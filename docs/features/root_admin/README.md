# Root Admin

## Overview

Platform-level administration for the root admin user (`users.is_root_admin = true`). Provides cross-school visibility for school management, platform stats, and feature request lifecycle management.

## User Stories

- As a **root admin**, I can view all schools across the platform (including soft-deleted)
- As a **root admin**, I can see platform-wide stats (school count, user count)
- As a **root admin**, I can manage feature requests from all schools with status lifecycle

## Features

### Platform Dashboard
- Total schools, active schools, total users across all schools
- Entry point to school management and feature request feed

### School Management
- List all schools with name, slug, plan, active/inactive/deleted status
- Ordered by `created_at desc`
- Includes soft-deleted schools for recovery

### Feature Request Management
- Cross-school feed of all feature requests
- Status lifecycle: `open → under_review → planned → done | declined`
- Inline status dropdown for quick updates
- School name badge on each request for context

### Legal Template Management
- Manage platform-level legal document templates
- Templates pre-fill school legal documents during onboarding

## Middleware

- `root_admin` (CheckRootAdmin) — verifies `User::is_root_admin = true`
- Applied to all `/root-admin/*` routes
- No school context required — root admin operates cross-school

## Routes (middleware: auth, not_disabled, root_admin)

- `GET /root-admin` — dashboard
- `GET /root-admin/schools` — school list
- `GET /root-admin/feature-requests` — cross-school feed
- `PATCH /root-admin/feature-requests/{id}/status` — update status
