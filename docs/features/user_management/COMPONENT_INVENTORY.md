# User Management — Component Inventory

## Pages

| Component | Path | Reuses |
|---|---|---|
| Admin/Staff/Index | Pages/Admin/Staff/Index.tsx | SchoolLayout, Card, Badge, Button, Input, Select, Dialog, Label |
| Admin/Students/Index | Pages/Admin/Students/Index.tsx | SchoolLayout, Card, Badge, Button |
| Admin/Students/Import | Pages/Admin/Students/Import.tsx | SchoolLayout, Card, Button, Input, Label |
| Admin/Guardians/Index | Pages/Admin/Guardians/Index.tsx | SchoolLayout, Card, Badge, Button, Dialog |
| Admin/Classes/Index | Pages/Admin/Classes/Index.tsx | SchoolLayout, Card, Button, Input, Dialog, Label |
| Admin/Classes/Show | Pages/Admin/Classes/Show.tsx | SchoolLayout, Card, Badge, Button |

## Shared Components Used

| Component | Source | Usage |
|---|---|---|
| Card | Components/ui/card | List cards, detail cards |
| Badge | Components/ui/badge | Role badges, status badges (accepted/pending) |
| Button | Components/ui/button | Invite, enrol, import, generate code, add/remove student |
| Input | Components/ui/input | Name, email, search fields |
| Select | Components/ui/select | Role dropdown (admin/teacher/support) |
| Dialog | Components/ui/dialog | Invite staff, generate guardian code, create class |
| Label | Components/ui/label | Form labels |

## Layouts

| Layout | Used By |
|---|---|
| SchoolLayout | All user management pages |
