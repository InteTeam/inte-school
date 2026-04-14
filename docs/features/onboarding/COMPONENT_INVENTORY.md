# Onboarding — Component Inventory

## Pages

| Component | Path | Reuses |
|---|---|---|
| School/Onboarding/Step1 | Pages/School/Onboarding/Step1.tsx | WizardShell, Card, Button, Input, Label |
| School/Onboarding/Step2 | Pages/School/Onboarding/Step2.tsx | WizardShell, Card, Button, Input, Label |
| School/Onboarding/Step3 | Pages/School/Onboarding/Step3.tsx | WizardShell, Card, Button |
| School/Onboarding/Step4 | Pages/School/Onboarding/Step4.tsx | WizardShell, Card, Button |
| Legal/Accept | Pages/Legal/Accept.tsx | AuthLayout, Card, Button |
| Legal/Show | Pages/School/Legal/Show.tsx | SchoolLayout, Card |
| Legal/Edit | Pages/School/Legal/Edit.tsx | SchoolLayout, Card, Button, Textarea |

## Shared Components Used

| Component | Source | Usage |
|---|---|---|
| Card | Components/ui/card | Step content cards, document cards |
| Button | Components/ui/button | Next/Back navigation, Create School, Accept, Publish |
| Input | Components/ui/input | School name, slug, colour picker hex |
| Label | Components/ui/label | Form labels |
| Textarea | Components/ui/textarea | Legal document content editor |

## Custom Components

| Component | Source | Usage |
|---|---|---|
| WizardShell | Components/Organisms/WizardShell | 4-step progress indicator wrapping each onboarding step |

## Layouts

| Layout | Used By |
|---|---|
| WizardShell (organism) | Onboarding steps 1-4 |
| AuthLayout | Legal acceptance page |
| SchoolLayout | Legal show/edit pages |
