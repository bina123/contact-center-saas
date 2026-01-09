# Project Structure Overview

```
contact-center-saas/
│
├── 📄 README.md                          # Main project documentation
├── 📄 IMPLEMENTATION_GUIDE.md            # Detailed implementation guide
├── 📄 DEPLOYMENT.md                      # Deployment instructions
├── 📄 INTERVIEW_CHEATSHEET.md           # Quick interview reference
├── 📄 docker-compose.yml                 # Docker orchestration
│
├── backend/                              # Laravel API Backend
│   ├── app/
│   │   ├── Http/
│   │   │   └── Controllers/
│   │   │       └── Api/
│   │   │           ├── CallController.php
│   │   │           ├── DashboardController.php
│   │   │           ├── TicketController.php
│   │   │           ├── CampaignController.php
│   │   │           ├── UserController.php
│   │   │           └── TenantController.php
│   │   ├── Models/
│   │   │   ├── Tenant.php               # Multi-tenant model
│   │   │   ├── User.php                 # User with tenant scope
│   │   │   ├── Call.php                 # Call management
│   │   │   ├── Campaign.php
│   │   │   ├── Ticket.php
│   │   │   └── [Other models]
│   │   ├── Services/
│   │   │   ├── CallService.php          # Call business logic
│   │   │   ├── TenantService.php        # Tenant management
│   │   │   ├── BillingService.php
│   │   │   └── [Other services]
│   │   ├── Events/
│   │   │   ├── CallStarted.php          # WebSocket events
│   │   │   ├── CallEnded.php
│   │   │   ├── CallAnswered.php
│   │   │   └── [Other events]
│   │   └── Middleware/
│   │       └── TenantScope.php          # Tenant isolation
│   ├── database/
│   │   └── migrations/
│   │       └── 2024_01_01_000000_create_contact_center_tables.php
│   ├── routes/
│   │   └── api.php                      # API routes
│   ├── .env.example                     # Environment template
│   └── composer.json                    # PHP dependencies
│
├── frontend/                            # React TypeScript Frontend
│   ├── src/
│   │   ├── components/
│   │   │   ├── Dashboard.tsx            # Real-time dashboard
│   │   │   ├── CallList.tsx
│   │   │   ├── CallDetails.tsx
│   │   │   ├── TicketList.tsx
│   │   │   ├── AgentList.tsx
│   │   │   └── [Other components]
│   │   ├── services/
│   │   │   ├── api.ts                   # API client
│   │   │   └── websocket.ts             # WebSocket service
│   │   ├── hooks/
│   │   │   ├── useCallUpdates.ts
│   │   │   ├── useDashboard.ts
│   │   │   └── [Other hooks]
│   │   ├── stores/
│   │   │   └── authStore.ts             # Zustand stores
│   │   └── App.tsx
│   ├── package.json                     # Node dependencies
│   ├── tsconfig.json                    # TypeScript config
│   ├── vite.config.ts                   # Vite config
│   └── tailwind.config.js               # Tailwind CSS
│
└── docker/                              # Docker configurations
    ├── php/
    │   ├── Dockerfile                   # PHP-FPM container
    │   └── php.ini
    ├── nginx/
    │   └── default.conf                 # Nginx config
    └── node/
        └── Dockerfile                   # Node.js container
```

## 🎯 Key Files Explained

### Backend (Laravel)

**Models** (`backend/app/Models/`)
- Define database structure
- Include tenant scoping
- Relationships between entities
- Business logic accessors

**Services** (`backend/app/Services/`)
- Business logic layer
- Reusable operations
- Called by controllers, commands, jobs
- Example: CallService handles call lifecycle

**Controllers** (`backend/app/Http/Controllers/Api/`)
- Thin layer handling HTTP requests
- Validation
- Call services
- Return JSON responses

**Events** (`backend/app/Events/`)
- Broadcast real-time updates
- Triggered by business logic
- Sent via Redis to WebSocket server

**Routes** (`backend/routes/api.php`)
- API endpoint definitions
- Middleware groups (auth, role)
- RESTful conventions

### Frontend (React)

**Components** (`frontend/src/components/`)
- Reusable UI components
- Dashboard with real-time metrics
- Call management interfaces
- Ticket handling

**Services** (`frontend/src/services/`)
- `api.ts`: All backend API calls
- `websocket.ts`: Real-time WebSocket management
- Centralized communication layer

**Hooks** (`frontend/src/hooks/`)
- Custom React hooks
- Encapsulate logic
- Reusable across components

### Docker

**docker-compose.yml**
- Orchestrates all services
- MySQL, Redis, Backend, Frontend, Nginx
- Network configuration
- Volume mounts

**Dockerfiles**
- PHP: Laravel backend container
- Node: React frontend container
- Nginx: Web server and reverse proxy

## 📊 Data Flow

### API Request Flow
```
Client → Nginx → Backend (PHP-FPM) → MySQL/Redis → Response
```

### Real-Time Update Flow
```
Event → Queue → Worker → Broadcast → Redis → Reverb → Client
```

### Authentication Flow
```
Login → API → JWT Token → Stored locally → Sent with each request
```

## 🔧 Configuration Files

- `backend/.env` - Backend environment variables
- `frontend/.env` - Frontend environment variables  
- `docker-compose.yml` - Service orchestration
- `backend/config/database.php` - Database configuration
- `backend/config/broadcasting.php` - WebSocket configuration

## 🚀 Quick Commands

```bash
# Start everything
docker-compose up -d

# View logs
docker-compose logs -f backend

# Run migrations
docker-compose exec backend php artisan migrate

# Install frontend dependencies
docker-compose exec frontend npm install

# Build frontend
docker-compose exec frontend npm run build
```

## 📦 Dependencies Summary

### Backend (PHP)
- Laravel 10 - Framework
- Laravel Sanctum - Authentication
- Laravel Reverb - WebSockets
- Spatie Permission - RBAC
- Stripe PHP - Billing

### Frontend (JavaScript)
- React 18 - UI library
- TypeScript - Type safety
- TanStack Query - Server state
- Zustand - Client state
- Recharts - Charts
- Tailwind CSS - Styling

### Infrastructure
- MySQL 8.0 - Database
- Redis 7 - Cache/Queue
- Nginx - Web server
- Docker - Containerization

## 🎓 Learning Path

1. Start with README.md for overview
2. Read IMPLEMENTATION_GUIDE.md for architecture details
3. Study INTERVIEW_CHEATSHEET.md before interviews
4. Refer to DEPLOYMENT.md for deployment
5. Examine code in this order:
   - Models (understand data structure)
   - Services (understand business logic)
   - Controllers (understand API endpoints)
   - Events (understand real-time)
   - Frontend components (understand UI)

## ✅ What's Included

✅ Multi-tenant database schema
✅ Laravel models with tenant scoping
✅ Service layer with business logic
✅ RESTful API controllers
✅ Real-time event broadcasting
✅ React TypeScript frontend
✅ WebSocket integration
✅ Dashboard with live metrics
✅ Docker configuration
✅ Nginx configuration
✅ Comprehensive documentation
✅ Interview preparation materials

## 🎯 What to Build Next

To complete this MVP, you would add:

1. **Authentication UI** - Login/Register pages
2. **More Controllers** - Ticket, Campaign, User controllers
3. **More Models** - Campaign, Lead, Ticket models
4. **Frontend Routes** - React Router setup
5. **More Components** - Ticket list, Campaign manager
6. **Tests** - Unit and feature tests
7. **CI/CD** - GitHub Actions workflow
8. **Monitoring** - Laravel Telescope, error tracking

## 💡 Tips for Interview

1. **Know the flow**: Explain how a call goes from webhook to dashboard
2. **Understand trade-offs**: Why single DB vs separate DBs
3. **Security first**: Always mention tenant isolation
4. **Real-time is key**: Emphasize WebSocket implementation
5. **Scalability**: Talk about load balancing, caching, queues

---

**This structure provides a solid foundation for a production-ready contact center SaaS platform! 🚀**
