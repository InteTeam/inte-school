# User Management

## Overview

Staff invitation, student enrolment, guardian linking, and class management. Supports email-based staff invites, bulk CSV student import, 8-char guardian invite codes, and flexible family structures (divorced parents, siblings).

## User Stories

- As an **admin**, I can invite staff (admin/teacher/support) via email with a 7-day token
- As an **admin**, I can enrol students individually or via bulk CSV import
- As an **admin**, I can generate guardian invite codes and link guardians to students
- As an **admin**, I can create classes, assign teachers, and manage student enrollment

## Key Flows

### Staff Invite
1. Admin enters name, email, role, optional department
2. `UserManagementService::inviteStaff()` creates/attaches user, generates 7-day invitation token
3. Staff member clicks link → `AcceptInvitation` page → sets name + password
4. Token consumed, `accepted_at` set on pivot

### Student Enrolment (Single)
1. Admin enters name, email, optional class
2. `UserManagementService::enrolStudent()` creates student user, attaches to school, optionally adds to class

### Student Bulk CSV Import
1. Admin uploads CSV file (name, email, year_group, class_name columns)
2. `ProcessStudentCsvImportJob` dispatched (default queue)
3. Job processes in chunks of 50, calls `enrolStudent()` per row
4. Per-row error handling — failures logged, don't stop batch

### Guardian Linking
1. Admin generates 8-char uppercase invite code for a student → 14-day expiry
2. Guardian receives code, enters on `AcceptInvitation` page
3. Guardian linked to student with `is_primary` flag

### Family Structures
- **Divorced parents:** multiple guardians per student, each with `is_primary` flag
- **Siblings:** one guardian linked to multiple students
- Unique constraint: `(school_id, guardian_id, student_id)` prevents duplicates

## CSV Security

- Injection prevention: cells starting with `=`, `-`, `+`, `@`, `\t`, `\r` are prefixed
- Export template provided: `GET /admin/students/export-template`

## Database Tables

- `school_user` — pivot with role, invitation token, acceptance tracking
- `classes` — school classes with optional teacher assignment
- `class_students` — enrollment with `enrolled_at` / `left_at` timestamps
- `guardian_student` — many-to-many with `is_primary` flag

## Routes (middleware: auth, not_disabled, school, legal, role:admin)

### Staff
- `GET /admin/staff` — list
- `POST /admin/staff/invite` — invite

### Students
- `GET /admin/students` — list
- `POST /admin/students/enrol` — single enrolment
- `POST /admin/students/import` — CSV upload
- `GET /admin/students/export-template` — CSV template download

### Guardians
- `GET /admin/guardians` — list
- `POST /admin/guardians/generate-code` — generate invite code (JSON)
- `POST /admin/guardians/link` — link guardian to student (JSON)

### Classes
- `GET /admin/classes` — list
- `POST /admin/classes` — create
- `GET /admin/classes/{class}` — detail view
- `PUT|POST /admin/classes/{class}` — update
- `DELETE /admin/classes/{class}` — soft delete
- `POST /admin/classes/{class}/students/{student}` — add student
- `DELETE /admin/classes/{class}/students/{student}` — remove student
