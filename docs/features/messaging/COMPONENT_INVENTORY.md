# Messaging — Component Inventory

## Pages
| Component | Path | Reuses |
|---|---|---|
| Admin/Messages/Index | Pages/Admin/Messages/Index.tsx | SchoolLayout, Card, Badge, Button |
| Admin/Messages/Compose | Pages/Admin/Messages/Compose.tsx | SchoolLayout, Card, Button, Input, Textarea, Select, Label |
| Teacher/Messages/Compose | Pages/Teacher/Messages/Compose.tsx | SchoolLayout, Card, Button, Textarea, Select, Label |
| Parent/Messages/Index | Pages/Parent/Messages/Index.tsx | ParentLayout, Card, Badge |
| Parent/Messages/Thread | Pages/Parent/Messages/Thread.tsx | ParentLayout, Card, Badge, Button |

## Shared Components Used
| Component | Source | Usage |
|---|---|---|
| Badge | Components/ui/badge | Message type badges (announcement=outline, attendance_alert=destructive, trip_permission=default, quick_reply=secondary) |
| Card | Components/ui/card | Message cards in inbox/outbox |
| Button | Components/ui/button | Send, quick reply, compose actions |
| Select | Components/ui/select | Type selector, class selector |
| Input | Components/ui/input | Recipient search |
| Textarea | Components/ui/textarea | Message body (6 rows, max 5000) |
| Label | Components/ui/label | Form labels |

## Layouts
| Layout | Used By |
|---|---|
| SchoolLayout | Admin + Teacher message pages |
| ParentLayout | Parent message pages (PWA-optimised) |
