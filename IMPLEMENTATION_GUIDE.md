# Multi-Tenant Contact Center SaaS - Implementation Guide

## 🎯 For HoduSoft Interview Preparation

This document provides a comprehensive guide to implementing and explaining this contact center platform.

---

## 📋 Table of Contents

1. [System Architecture](#system-architecture)
2. [Multi-Tenancy Implementation](#multi-tenancy-implementation)
3. [Real-Time Features](#real-time-features)
4. [Database Design](#database-design)
5. [API Design](#api-design)
6. [Security Implementation](#security-implementation)
7. [Scaling Strategy](#scaling-strategy)
8. [Interview Talking Points](#interview-talking-points)

---

## 1. System Architecture

### High-Level Overview

```
┌─────────────────────────────────────────────┐
│           Load Balancer (Nginx)             │
└──────────────────┬──────────────────────────┘
                   │
    ┌──────────────┼──────────────────┐
    ▼              ▼                  ▼
┌─────────┐  ┌─────────┐        ┌─────────┐
│ Web App │  │ Web App │   ...  │ Web App │
│ Server  │  │ Server  │        │ Server  │
└────┬────┘  └────┬────┘        └────┬────┘
     │            │                   │
     └────────────┼───────────────────┘
                  │
     ┌────────────┼────────────────┐
     ▼            ▼                ▼
┌─────────┐  ┌─────────┐     ┌─────────┐
│  MySQL  │  │  Redis  │     │  Reverb │
│ Primary │  │  Cache  │     │   WS    │
└────┬────┘  └─────────┘     └─────────┘
     │
     ▼
┌─────────┐
│  MySQL  │
│ Replica │
└─────────┘
```

### Component Breakdown

**Frontend (React + TypeScript)**
- Modern SPA with TypeScript for type safety
- TanStack Query for efficient server state management
- Zustand for lightweight client state
- WebSocket integration for real-time updates
- Responsive design with Tailwind CSS

**Backend (Laravel 10)**
- RESTful API architecture
- Service layer for business logic
- Repository pattern for data access
- Event-driven architecture for real-time features
- Queue system for async operations

**Database (MySQL)**
- Single database multi-tenancy
- Tenant isolation via `tenant_id` column
- Indexed for performance
- Migration-based schema management

**Caching (Redis)**
- Session storage
- Queue management
- Cache frequently accessed data
- Pub/sub for real-time events

**WebSocket (Laravel Reverb)**
- Real-time call updates
- Agent presence tracking
- Dashboard metrics broadcasting
- Ticket notifications

---

## 2. Multi-Tenancy Implementation

### Strategy: Single Database with Tenant ID

**Why This Approach?**
1. **Cost-Effective**: Single database instance
2. **Easy Maintenance**: One schema to manage
3. **Simple Backups**: Single backup process
4. **Cross-Tenant Reporting**: Easier analytics
5. **Lower Complexity**: Simpler DevOps

### Implementation Details

**1. Database Level**

Every tenant-specific table includes `tenant_id`:

```sql
CREATE TABLE calls (
    id BIGINT PRIMARY KEY,
    tenant_id BIGINT NOT NULL,
    agent_id BIGINT,
    -- other columns
    INDEX idx_tenant_calls (tenant_id, created_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

**2. Model Level (Laravel)**

Global scopes automatically filter by tenant:

```php
protected static function booted()
{
    static::addGlobalScope('tenant', function ($builder) {
        if ($tenantId = auth()->user()?->tenant_id) {
            $builder->where('tenant_id', $tenantId);
        }
    });
}
```

**3. Middleware Level**

Verify tenant access on every request:

```php
public function handle($request, Closure $next)
{
    $user = $request->user();
    
    if (!$user || !$user->tenant_id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    
    // Set tenant context
    app()->instance('tenant_id', $user->tenant_id);
    
    return $next($request);
}
```

**4. Data Isolation**

- All queries automatically scoped to tenant
- Foreign keys enforce referential integrity
- Row-level security prevents cross-tenant access
- API responses filtered by tenant

---

## 3. Real-Time Features

### WebSocket Architecture

**Broadcasting Flow:**

```
User Action → Laravel Event → Redis Pub/Sub → Reverb → WebSocket Clients
```

### Implementation

**1. Laravel Events**

```php
class CallStarted implements ShouldBroadcast
{
    public function broadcastOn(): array
    {
        return [
            new Channel('tenant.' . $this->call->tenant_id . '.calls')
        ];
    }
    
    public function broadcastAs(): string
    {
        return 'call.started';
    }
}
```

**2. Frontend Subscription**

```typescript
wsService.subscribeToCallEvents({
    onCallStarted: (call) => {
        // Update UI with new call
        queryClient.invalidateQueries(['calls']);
    },
    onCallEnded: (call) => {
        // Update call list
    }
});
```

### Real-Time Features Implemented

1. **Active Calls Dashboard**: Live count of ongoing calls
2. **Agent Presence**: See which agents are online
3. **Call Notifications**: Instant alerts for new calls
4. **Ticket Updates**: Real-time ticket status changes
5. **Metrics Updates**: Dashboard metrics refresh automatically

---

## 4. Database Design

### Key Design Decisions

**1. Tenant Isolation**
- Every table with user data has `tenant_id`
- Composite indexes: `(tenant_id, created_at)`
- Foreign keys enforce data integrity

**2. Soft Deletes**
- Most tables use soft deletes for audit trail
- Allows data recovery
- Maintains referential integrity

**3. JSON Columns**
- `settings`, `metadata`, `custom_fields`
- Flexible schema for tenant customization
- Indexed with generated columns when needed

**4. Status Enums**
- Predefined status values
- Better performance than lookup tables
- Type-safe in application code

### Important Relationships

```
Tenant (1) ──────────< (N) Users
                      < (N) Calls
                      < (N) Tickets
                      < (N) Campaigns

User (1) ────────────< (N) Calls (as agent)
                     < (N) Tickets (assigned)
                     
Campaign (1) ────────< (N) Calls
                     < (N) Leads
```

---

## 5. API Design

### RESTful Conventions

**Endpoint Structure:**
```
GET    /api/calls              - List calls
GET    /api/calls/{id}         - Get single call
POST   /api/calls              - Create call (webhook)
POST   /api/calls/{id}/answer  - Answer call
POST   /api/calls/{id}/end     - End call
PATCH  /api/calls/{id}/notes   - Update notes
```

### Response Format

**Success:**
```json
{
    "data": { ... },
    "message": "Operation successful"
}
```

**Error:**
```json
{
    "error": "Error message",
    "code": "ERROR_CODE",
    "details": { ... }
}
```

### Pagination

All list endpoints support pagination:
```
GET /api/calls?page=1&per_page=15
```

Response includes:
- `data`: Array of items
- `current_page`, `last_page`
- `total`, `per_page`
- `next_page_url`, `prev_page_url`

---

## 6. Security Implementation

### Authentication

**Laravel Sanctum (Token-based)**
- Stateless authentication
- Token stored in localStorage
- Sent in `Authorization: Bearer {token}` header
- Automatic token revocation on logout

### Authorization

**Role-Based Access Control (RBAC)**

Three main roles:
1. **Admin**: Full tenant access
2. **Supervisor**: Manage agents, view reports
3. **Agent**: Handle calls, manage tickets

```php
// Middleware check
Route::middleware('role:admin')->group(function () {
    // Admin-only routes
});
```

### Data Protection

1. **Tenant Isolation**: Global scopes prevent cross-tenant access
2. **SQL Injection**: Prepared statements (Eloquent ORM)
3. **XSS Protection**: Input sanitization, output escaping
4. **CSRF Tokens**: For state-changing requests
5. **Rate Limiting**: API throttling (60 requests/minute)
6. **HTTPS Only**: SSL/TLS encryption in production

### Sensitive Data

- Passwords: `bcrypt` hashing
- API Keys: Encrypted storage
- PII: Encrypted at rest
- Logs: Sanitized sensitive data

---

## 7. Scaling Strategy

### Horizontal Scaling

**Application Servers**
```
Load Balancer
    │
    ├── App Server 1
    ├── App Server 2
    ├── App Server 3
    └── App Server N
```

- Stateless application design
- Session stored in Redis (shared)
- Load balanced with Nginx/HAProxy
- Auto-scaling based on CPU/memory

### Database Scaling

**Read Replicas**
```
Primary (Write)
    │
    ├── Replica 1 (Read)
    ├── Replica 2 (Read)
    └── Replica 3 (Read)
```

- Primary for writes
- Replicas for read-heavy queries (reports, analytics)
- Laravel's read/write connections
- Automatic failover

**Query Optimization**
- Proper indexing strategy
- Query caching with Redis
- Avoid N+1 queries (eager loading)
- Database query monitoring (Telescope)

### Queue Workers

**Auto-Scaling Workers**
- Monitor queue depth
- Scale workers based on load
- Separate queues by priority
- Retry failed jobs with exponential backoff

```bash
# Multiple workers for different queues
php artisan queue:work --queue=high,default,low
```

### Caching Strategy

**Multi-Level Caching**

1. **Application Cache** (Redis)
   - Frequently accessed data
   - API responses
   - User sessions

2. **Query Cache**
   - Database query results
   - Aggregated metrics
   - Report data

3. **CDN Cache**
   - Static assets
   - Frontend build files
   - Public resources

**Cache Invalidation**
```php
// Invalidate on updates
Cache::tags(['tenant:' . $tenantId, 'calls'])->flush();
```

---

## 8. Interview Talking Points

### How to Present This Project

**Opening Statement:**
> "I built a multi-tenant contact center SaaS platform using Laravel for the backend API and React with TypeScript for the frontend. The system handles real-time call management, agent routing, and analytics across multiple client organizations while maintaining complete data isolation."

### Key Technical Highlights

**1. Multi-Tenancy Architecture**
> "We implemented single-database multi-tenancy with tenant_id column isolation. This approach balances cost-effectiveness with security. Every database query is automatically scoped to the authenticated user's tenant using Laravel's global scopes, preventing any cross-tenant data leakage."

**2. Real-Time Features**
> "For real-time updates, we use Laravel Reverb as our WebSocket server. Events like call start/end, agent status changes, and dashboard metrics are broadcast via Redis pub/sub and pushed to connected clients. This gives supervisors instant visibility into call center operations."

**3. Service Layer Architecture**
> "The backend uses a clean architecture with service classes handling business logic, repositories for data access, and controllers as thin entry points. For example, CallService handles all call-related operations like routing to available agents, calculating metrics, and triggering events."

**4. Scalability Design**
> "The application is designed to scale horizontally. We use stateless API servers behind a load balancer, Redis for shared sessions and queues, and MySQL read replicas for report queries. Queue workers can auto-scale based on load."

**5. Security Measures**
> "Security is multi-layered: tenant isolation at the database level, JWT token authentication, role-based access control, API rate limiting, and all sensitive data encrypted. We also sanitize all user input and use prepared statements to prevent SQL injection."

### Sample Interview Questions & Answers

**Q: How do you handle data isolation between tenants?**

**A:** "We use a single-database approach with a tenant_id column in all relevant tables. At the model level, Laravel's global scopes automatically filter all queries by the authenticated user's tenant_id. This is enforced at multiple levels:

1. Database indexes on (tenant_id, created_at) for performance
2. Model global scopes that apply automatically
3. Middleware that verifies tenant access
4. Foreign key constraints preventing cross-tenant references

This approach is more cost-effective than separate databases while maintaining strong isolation."

---

**Q: How does the real-time call notification system work?**

**A:** "When a call comes in via webhook from our telephony provider:

1. The webhook hits our API which creates a Call record
2. A CallStarted event is fired and queued
3. Laravel's queue worker processes the event
4. The event broadcasts to a tenant-specific channel via Redis
5. Laravel Reverb pushes to all connected WebSocket clients
6. Frontend subscribers receive the event and update the UI

The entire flow takes under 100ms, so supervisors see new calls almost instantly."

---

**Q: How would you scale this to handle 10,000 concurrent calls?**

**A:** "Several strategies:

1. **Horizontal API scaling**: Run multiple stateless API servers behind a load balancer
2. **Database read replicas**: Direct read-heavy queries (analytics, reports) to replicas
3. **Redis clustering**: Distribute cache and queue load
4. **Queue worker scaling**: Auto-scale workers based on queue depth
5. **CDN for assets**: Offload static content delivery
6. **Optimize database queries**: Proper indexes, query optimization, avoiding N+1

For telephony specifically, we'd integrate with a scalable SIP provider like Twilio or our own Kamailio cluster."

---

**Q: How do you ensure call recordings are secure and compliant?**

**A:** "Multi-level security:

1. **Storage**: Recordings stored on S3 with server-side encryption
2. **Access Control**: Pre-signed URLs with 1-hour expiration
3. **Audit Logs**: Every recording access is logged
4. **Retention**: Automated deletion based on compliance rules
5. **Privacy**: PCI/HIPAA compliant storage if needed
6. **Backup**: Encrypted backups with separate access controls

We also implement role-based permissions - only supervisors and admins can access recordings."

---

### Closing Statement

> "This project demonstrates production-ready SaaS architecture with real-time features, proper multi-tenancy, and scalability considerations. The combination of Laravel's robust ecosystem and React's component model creates a maintainable, performant system that can grow with business needs."

---

## 🎯 Practice Exercise

Before your interview, practice explaining:

1. Draw the architecture diagram from memory
2. Explain the call flow from webhook to UI update
3. Walk through how a database query is automatically scoped to a tenant
4. Describe how you'd debug a cross-tenant data leak
5. Explain your caching strategy and invalidation rules

---

## 📚 Additional Resources

- Laravel Multi-Tenancy: https://laravel.com/docs/10.x
- Laravel Reverb Docs: https://reverb.laravel.com
- React Query: https://tanstack.com/query/latest
- Database Indexing: https://use-the-index-luke.com

---

**Good luck with your HoduSoft interview! 🚀**
