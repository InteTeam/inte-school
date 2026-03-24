# Inte-School Multi-Tenant Blueprint — PRD v1.1

## Overview

Inte-School is a multi-tenant school communication platform hosted on self-managed infrastructure. The goal is to disrupt legacy Scottish public-sector vendors by offering a cost-effective, privacy-respecting, two-way communication system between schools and parents.

---

## 1. Core Infrastructure

### Deployment Stages

| Stage | Hardware | Purpose |
|---|---|---|
| Staging / Testing | Dell R550 (Xeon Silver 4309Y, 192GB RAM) | Each school gets a sandboxed test environment before any commitment |
| Production | UK-based dedicated server | Live tenant stacks once a school commits |
| Warm Backup | Dell R550 (post-production) | Fallback copy of production; Dell becomes staging after handover |

### Provisioning & Routing
- **Virtualization:** Proxmox manages the Inte-School VM on Dell R550
- **Provisioning:** UPanel engine (Laravel 12 / Inertia / React) spins up new school tenants via Docker Compose
- **Traffic Routing:** Nginx Proxy Manager (NPM) routes `schoolname.inte.school` to the correct container
- **Deployment:** Automated "Git Push to Live" workflow via UPanel over SSH

### Tenancy Model
- Each school ("stall") is an isolated Docker Compose stack
- Stacks are portable — migration from Dell to dedicated UK server is a first-class requirement
- Resource ceiling on Dell (~30–40 schools at 4–6GB RAM per stack) is acceptable as Dell is staging only

---

## 2. Data Architecture

### Database: PostgreSQL

**Rationale:**
- Strict data types and superior relational integrity in multi-tenant environments
- PGVector extension available natively
- Better suited for complex relationship handling at scale

### Data Residency & GDPR
- All data resides within the **UK**, or **EU** where UK law permits
- Any third-party data processor (e.g., GCS for media/backup) must have a signed **Data Processing Agreement (DPA)**
- Google Cloud Storage (GCS) is approved for backup and media storage on production, subject to DPA confirmation
- Child data is treated as sensitive — access is scoped strictly to verified guardians and authorised school staff
- Platform must support: right to erasure, data portability, audit trail export
- ICO registration required before go-live

### PGVector — Semantic Search & RAG Pipeline

**Purpose:** Semantic search over school documents (handbooks, policies, FAQs)

**Use Case Example:**
> Parent asks: "What is the policy on nut allergies?"
> The system retrieves relevant chunks from the school's handbook via vector similarity and generates a contextual answer — no human required.

**Two-step pipeline:**
1. **Embedding model** — converts documents and queries into vectors
2. **Generation model** — answers using retrieved chunks (RAG)

#### Model Strategy by Stage

| Stage | Embedding | Generation | GDPR Position |
|---|---|---|---|
| Staging (Dell) | Ollama (`nomic-embed-text`) | Ollama (`mistral` or `llama3`) | Clean — data never leaves server |
| Production (UK server) | Ollama (same stack) | Ollama or OpenAI/Vertex with DPA | Clean if local; DPA required if API |

- Default to **Ollama on self-hosted** for both stages — no API keys, no external data transfer, no per-token cost
- OpenAI or Google Vertex AI are permitted on production only if a DPA is signed and EU region is used
- GCS for media/backup storage on production is approved subject to DPA

---

## 3. Two-Way Messaging: Supply/Demand Model

### Concept
Moving from a **broadcast** model to a **marketplace** model of information exchange.

### Supply (School → Parent)
- Announcements
- Attendance alerts
- Trip permission requests

### Demand (Parent → School)
- Instant replies
- Absence notifications
- Query / support tickets

### Transaction ID Logic
- Every outbound "Supply" message generates a unique **Transaction ID**
- Parent replies are mapped back to that Transaction ID
- Ensures all responses are contextual threads, not unstructured message piles
- Transaction ID is the **deduplication key** across all delivery channels (Reverb, push, SMS)
- Full audit trail stored in PostgreSQL

### Multi-Guardian Rule
- Each child may have multiple registered guardians
- Attendance alerts and permission requests are sent to the **primary guardian** by default
- If the primary guardian acknowledges, **no further notifications are sent** to secondary guardians
- If no acknowledgment within the fallback window, the secondary guardian is contacted next before SMS

---

## 4. Authentication & Access Control

### Parent Authentication
- **Required:** Email + password + 2FA (TOTP or email OTP)
- Access is tied to **registered devices** — replies and confirmations are only accepted from a device that has been verified during onboarding
- No anonymous or unauthenticated interactions

### Quick Reply Flow
- "Two tap" reply is achievable because the parent already has an **active authenticated session** on their registered device
- Flow: push notification arrives → tap to open PWA → tap to confirm (e.g., absence acknowledgment) — session is live, no re-login required
- Registered device requirement prevents link-forwarding attacks

### School Staff Roles
- Roles TBD (v1.2 planning item): at minimum `admin` and `teacher` with scoped permissions
- Only authorised staff can send Supply messages

---

## 5. Notification Delivery: Three-Tier Cascade

### Push Notification Stack (Self-Hosted, No Firebase)
- Uses **Web Push Protocol + VAPID keys** — no Firebase project, no Google SDK
- Laravel server holds the VAPID key pair (generated once at setup)
- PWA subscribes using the browser's native Push API
- Server sends pushes via `minishlink/web-push` PHP library
- Chrome on Android routes through Google's push infrastructure at the network layer, but there is **zero dependency on Firebase as a service**

### Delivery Cascade

```
School sends message
        │
        ▼
Message stored in DB — Transaction ID assigned
        │
        ▼
Horizon checks: is parent's Reverb WebSocket active?
   ├── YES → deliver via Reverb channel
   │          mark "delivered-realtime"
   │          skip push notification
   └── NO  → send VAPID Web Push to registered device
                    │
                    ▼
             Service Worker intercepts:
             ├── App open/focused → post to app UI, suppress OS banner
             └── App closed/backgrounded → show OS push notification
                    │
                    ▼
             Parent taps → PWA opens → Transaction ID matched → marked "read"
                    │
                    ▼
             Horizon 15-min timer: no "read" event recorded?
             └── Promote to SMS Gateway (emergency fallback)
```

### Deduplication
- The Transaction ID prevents duplicate delivery — regardless of which channel delivers the message, the app checks for the ID before rendering
- If Reverb delivers while a push is in flight, the push is silently discarded by the Service Worker

### SMS Fallback
- Triggered by Horizon after **15 minutes** with no "read" acknowledgment
- SMS provider TBD (Twilio, Vonage, or Scottish-local) — selection deferred to v1.1 planning
- SMS is the emergency backup only; target is $0 marginal cost on primary path

### Real-Time Chat (Reverb)
- Laravel Reverb handles WebSocket connections for the in-app messaging experience
- When app is open: Reverb is the primary channel
- When app is closed/backgrounded: VAPID push takes over
- On app re-open after a push: Reverb reconnects and syncs any missed messages by Transaction ID

---

## 6. Intecracy Filtering: Socio-Economic Strategy

### Integrity Layer
- Every interaction (send, deliver, read, reply, acknowledge) is logged in PostgreSQL
- Eliminates "I never got that message" disputes
- Full audit trail accessible to school admin and exportable for compliance

### Cost Disruption
- Self-hosted on R550 (staging) and UK dedicated server (production) removes cloud middlemen
- Primary notification channel (VAPID Web Push) has $0 marginal cost
- SMS is cost-incurring but triggered only after 15-min non-response
- Pricing target: significantly undercut incumbent Scottish public-contract vendors

---

## 7. Implementation Summary

| Feature | Tech Stack | Logic |
|---|---|---|
| Multi-Tenancy | Laravel 12 + Docker | One isolated stack per school; portable between servers |
| Real-time Chat | Laravel Reverb + Redis | Primary when app is open |
| Push Notifications | VAPID Web Push (self-hosted) | Primary when app is closed; no Firebase dependency |
| SMS Fallback | SMS Gateway via Horizon | Triggered after 15-min no-read; provider TBD |
| Search / AI (RAG) | PostgreSQL + PGVector + Ollama | Semantic search; fully local; no external API required |
| Media & Backup | GCS (production, with DPA) | Backup and media storage; UK/EU region only |
| Authentication | Email + Password + 2FA + Device | Registered device required for reply actions |
| Deployment | UPanel via SSH | Automated Git Push to Live; stacks portable across servers |

---

## 8. Key Design Principles

1. **Integrity first** — audit trail on every interaction, GDPR-compliant by design
2. **Two-way by default** — every school message expects a structured, threaded parent response
3. **Zero friction for parents** — two-tap reply on registered device with active session
4. **Self-hosted sovereignty** — no cloud dependency for core functionality; GCS permitted for storage with DPA
5. **Cost disruption** — beat incumbent vendors on price by eliminating intermediaries
6. **Portability** — tenant stacks must migrate cleanly from Dell staging to UK production server

---

## 9. Open Decisions

- [ ] SMS Gateway provider selection (Twilio, Vonage, or Scottish-local)
- [ ] Confirm GCS DPA terms for UK/EU data residency compliance
- [ ] Define school staff roles and permission levels (admin vs. teacher)
- [ ] Define PDF ingestion pipeline for PGVector (upload → chunk → embed → store)
- [ ] Define PWA heartbeat protocol detail (Service Worker POST vs. WebSocket ping)
- [ ] Pricing model draft vs. incumbent vendor benchmarks
- [ ] ICO registration process and timeline
- [ ] Confirm OpenAI/Vertex DPA terms for production AI (if Ollama is insufficient)
- [ ] Secondary guardian contact order and escalation logic detail
