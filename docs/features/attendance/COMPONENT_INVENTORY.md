# Attendance — Component Inventory

## Pages
| Component | Path | Reuses |
|---|---|---|
| Teacher/Attendance/Register | Pages/Teacher/Attendance/Register.tsx | SchoolLayout, Card, Badge, Button |
| Parent/Attendance/History | Pages/Parent/Attendance/History.tsx | ParentLayout, Card, Badge |

## Shared Components Used
| Component | Source | Usage |
|---|---|---|
| Badge | Components/ui/badge | Status badges (present=green, absent=red, late=amber) |
| Card | Components/ui/card | Student cards, stats summary card |
| Button | Components/ui/button | Mark present/absent/late buttons |

## Layouts
| Layout | Used By |
|---|---|
| SchoolLayout | Teacher register page |
| ParentLayout | Parent history page (PWA-optimised) |

## Constants
- `STATUS_COLOURS` — maps present→green, absent→red, late→amber (defined in both Register.tsx and History.tsx)
