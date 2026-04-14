# User Management — Architecture

## Backend Layers

### Models

| Model | Traits | Key Relations |
|---|---|---|
| `SchoolClass` | HasUlids, HasSchoolScope, SoftDeletes | teacher (User), students (BelongsToMany via class_students with enrolled_at/left_at) |
| `ClassStudent` | — (pivot) | class, student |
| `GuardianStudent` | HasUlids, HasSchoolScope | guardian (User), student (User) |

### Service: `UserManagementService` (final)

| Method | Purpose |
|---|---|
| `inviteStaff()` | Create/attach user with role, generate 7-day token |
| `acceptInvitation()` | Accept by token, set name/password |
| `enrolStudent()` | Create student user, attach to school + optional class |
| `addStudentToClass()` | Set enrolled_at timestamp |
| `removeStudentFromClass()` | Set left_at timestamp (soft remove) |
| `generateGuardianInviteCode()` | 8-char uppercase code, 14-day expiry |
| `linkGuardianToStudent()` | Create guardian_student with is_primary |
| `sanitizeCsvRow()` | Strip CSV injection characters |
| `csvImportTemplate()` | Return header row array |
| `parseCsvImport()` | Parse CSV file into row dicts |

### Jobs

| Job | Queue | Purpose |
|---|---|---|
| `ProcessStudentCsvImportJob` | default | Bulk import, chunks by 50, per-row error handling |

### Form Requests

| Request | Key Rules |
|---|---|
| `InviteStaffRequest` | name, email, role (in:admin\|teacher\|support), department_label |
| `EnrolStudentRequest` | name, email, optional class_id |
| `StoreClassRequest` | name (max 100), year_group (max 50), optional teacher_id |
| `ImportStudentsRequest` | csv file (mimes:csv\|txt, max 10MB) |

### Controllers

| Controller | Methods |
|---|---|
| `Admin/StaffController` | index, invite |
| `Admin/StudentController` | index, enrol, import, exportTemplate |
| `Admin/GuardianController` | index, generateCode, link |
| `Admin/ClassController` | index, show, store, update, destroy, addStudent, removeStudent |

## Frontend Structure

### Pages

| Page | Layout | Purpose |
|---|---|---|
| `Admin/Staff/Index.tsx` | SchoolLayout | Staff table + invite dialog |
| `Admin/Students/Index.tsx` | SchoolLayout | Student table + import/export buttons |
| `Admin/Students/Import.tsx` | SchoolLayout | CSV upload form |
| `Admin/Guardians/Index.tsx` | SchoolLayout | Guardian table + code generator dialog |
| `Admin/Classes/Index.tsx` | SchoolLayout | Class table + create dialog |
| `Admin/Classes/Show.tsx` | SchoolLayout | Class detail with enrolled students |
