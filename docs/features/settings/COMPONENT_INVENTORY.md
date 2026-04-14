# Settings — Component Inventory

## Pages

| Component | Path | Reuses |
|---|---|---|
| Admin/Settings/General | Pages/Admin/Settings/General.tsx | SchoolLayout, Card, Button, Input, Label, Switch |
| Admin/Settings/Notifications | Pages/Admin/Settings/Notifications.tsx | SchoolLayout, Card, Button, Switch, Label |
| Admin/Settings/Security | Pages/Admin/Settings/Security.tsx | SchoolLayout, Card, Button, Input, Switch, Badge, Label |
| Admin/Settings/Legal | Pages/Admin/Settings/Legal.tsx | SchoolLayout, Card, Badge, Button |

## Shared Components Used

| Component | Source | Usage |
|---|---|---|
| Card | Components/ui/card | Settings section cards |
| Button | Components/ui/button | Save buttons |
| Input | Components/ui/input | Name, colour hex, session timeout |
| Switch | Components/ui/switch | Dark mode, SMS fallback, 2FA toggles |
| Badge | Components/ui/badge | Plan badge, version badge, published/draft status |
| Label | Components/ui/label | Form labels |

## Layouts

| Layout | Used By |
|---|---|
| SchoolLayout | All settings pages |
