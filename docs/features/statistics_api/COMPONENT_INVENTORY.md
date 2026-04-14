# Statistics & API — Component Inventory

## Pages

| Component | Path | Reuses |
|---|---|---|
| Admin/Statistics/Dashboard | Pages/Admin/Statistics/Dashboard.tsx | SchoolLayout, Card, Badge, Button, Select |
| Admin/Settings/ApiKeys | Pages/Admin/Settings/ApiKeys.tsx | SchoolLayout, Card, Button, Input, Label, Dialog, Badge |

## Shared Components Used

| Component | Source | Usage |
|---|---|---|
| Card | Components/ui/card | Stat cards, key list cards |
| Badge | Components/ui/badge | Period badges, permission badges |
| Button | Components/ui/button | Generate key, revoke, period selector |
| Select | Components/ui/select | Period selector (week/month/term) |
| Input | Components/ui/input | Key name, expiry date |
| Label | Components/ui/label | Form labels |
| Dialog | Components/ui/dialog | Revoke confirmation, key generation |

## Layouts

| Layout | Used By |
|---|---|
| SchoolLayout | Both statistics and API key pages |
