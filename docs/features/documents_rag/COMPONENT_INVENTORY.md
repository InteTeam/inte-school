# Documents & RAG — Component Inventory

## Pages

| Component | Path | Reuses |
|---|---|---|
| Admin/Documents/Index | Pages/Admin/Documents/Index.tsx | SchoolLayout, Card, Badge, Button |
| Admin/Documents/Upload | Pages/Admin/Documents/Upload.tsx | SchoolLayout, Card, Button, Input, Label |
| Parent/Ask/Index | Pages/Parent/Ask/Index.tsx | ParentLayout, Card, Button, Input, Textarea |
| Student/Ask/Index | Pages/Student/Ask/Index.tsx | SchoolLayout, Card, Button, Input, Textarea |

## Shared Components Used

| Component | Source | Usage |
|---|---|---|
| Badge | Components/ui/badge | Processing status badges (pending, processing, indexed, failed) |
| Card | Components/ui/card | Document cards, answer display cards |
| Button | Components/ui/button | Upload, delete, submit question |
| Input | Components/ui/input | Question input field |
| Textarea | Components/ui/textarea | Extended question input |
| Label | Components/ui/label | Form labels |

## Layouts

| Layout | Used By |
|---|---|
| SchoolLayout | Admin document pages, Student ask page |
| ParentLayout | Parent ask page (PWA-optimised) |
