# Root Admin — Component Inventory

## Pages

| Component | Path | Reuses |
|---|---|---|
| RootAdmin/Dashboard | Pages/RootAdmin/Dashboard.tsx | RootAdminLayout, Card |
| RootAdmin/Schools/Index | Pages/RootAdmin/Schools/Index.tsx | RootAdminLayout, Card, Badge |
| RootAdmin/FeatureRequests/Index | Pages/RootAdmin/FeatureRequests/Index.tsx | RootAdminLayout, Card, Badge, Select |

## Shared Components Used

| Component | Source | Usage |
|---|---|---|
| Card | Components/ui/card | Stat cards, school cards, request cards |
| Badge | Components/ui/badge | Plan badges, status badges (active/inactive/deleted, open/planned/done) |
| Select | Components/ui/select | Inline status dropdown on feature requests |

## Layouts

| Layout | Used By |
|---|---|
| RootAdminLayout | All root admin pages (no school context) |
