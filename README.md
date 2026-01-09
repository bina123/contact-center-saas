# Multi-Tenant Contact Center SaaS Platform

A production-ready, scalable contact center platform built with Laravel (backend) and React (frontend).

## 🎯 Features

- **Multi-Tenant Architecture**: Single database with tenant isolation
- **Real-Time Communication**: WebSocket-based live updates
- **Call Management**: Inbound/outbound calling with SIP integration
- **Live Chat & Ticketing**: Customer support features
- **Campaign Management**: Automated campaigns and lead management
- **Analytics Dashboard**: Real-time metrics and reporting
- **Billing & Subscriptions**: Automated billing with Stripe/Razorpay
- **Role-Based Access**: Admin, Supervisor, Agent roles
- **REST APIs**: CRM integration capabilities

## 🏗️ Architecture

```
┌──────────────────────────────┐
│  React + TypeScript Frontend │
│  (Real-time Dashboard)       │
└───────────────┬──────────────┘
                │
        API Gateway / Nginx
                │
┌───────────────▼────────────────┐
│     Laravel 10 Backend         │
│  Multi-tenant | Auth | APIs    │
└───────────────┬────────────────┘
                │
   ┌────────────┼──────────────────────┐
   ▼            ▼                      ▼
MySQL       Redis Cache           WebSocket Server
(Tenant DB)  & Queues           (Laravel Reverb)
```

## 🛠️ Tech Stack

### Backend
- **Laravel 10** with PHP 8.2
- **MySQL/MariaDB** for data persistence
- **Redis** for caching and queues
- **Laravel Reverb** for WebSockets
- **Laravel Sanctum** for API authentication

### Frontend
- **React 18** with TypeScript
- **Vite** for build tooling
- **TanStack Query** for server state
- **Zustand** for client state
- **Recharts** for analytics
- **Tailwind CSS** for styling
- **Shadcn/ui** for components

### DevOps
- **Docker** & Docker Compose
- **Nginx** as reverse proxy
- **GitHub Actions** for CI/CD

## 📦 Project Structure

```
contact-center-saas/
├── backend/                 # Laravel API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   └── Middleware/
│   │   ├── Models/
│   │   ├── Services/       # Business logic layer
│   │   ├── Repositories/   # Data access layer
│   │   └── Events/         # Domain events
│   ├── database/
│   │   └── migrations/
│   ├── routes/
│   └── tests/
├── frontend/               # React SPA
│   ├── src/
│   │   ├── components/
│   │   ├── features/       # Feature-based modules
│   │   ├── hooks/
│   │   ├── services/       # API calls
│   │   └── stores/         # State management
│   └── public/
├── docker/
│   ├── nginx/
│   ├── php/
│   └── mysql/
├── docker-compose.yml
└── README.md
```

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose
- Node.js 18+
- PHP 8.2+
- Composer

### Installation

1. **Clone and setup**
```bash
git clone <repository>
cd contact-center-saas
```

2. **Backend Setup**
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan reverb:start
```

3. **Frontend Setup**
```bash
cd frontend
npm install
npm run dev
```

4. **Docker Setup (Production)**
```bash
docker-compose up -d
```

## 🔐 Environment Variables

### Backend (.env)
```env
APP_NAME="Contact Center SaaS"
APP_ENV=local
APP_KEY=
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=contact_center
DB_USERNAME=root
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379

REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=

STRIPE_KEY=
STRIPE_SECRET=
```

### Frontend (.env)
```env
VITE_API_URL=http://localhost:8000/api
VITE_WS_URL=ws://localhost:6001
```

## 📊 Database Schema

### Core Tables
- `tenants` - Organization accounts
- `users` - User accounts with tenant_id
- `roles` - Role definitions (Admin, Supervisor, Agent)
- `calls` - Call records with tenant_id
- `tickets` - Support tickets
- `campaigns` - Marketing campaigns
- `subscriptions` - Billing subscriptions
- `invoices` - Payment records

## 🔄 Real-Time Events

Events broadcasted via WebSocket:
- `CallStarted`
- `CallEnded`
- `AgentStatusChanged`
- `TicketCreated`
- `TicketAssigned`
- `DashboardMetricsUpdated`

## 🧪 Testing

### Backend
```bash
cd backend
php artisan test
```

### Frontend
```bash
cd frontend
npm run test
```

## 🚢 Deployment

### Using Docker
```bash
docker-compose -f docker-compose.prod.yml up -d
```

### Manual Deployment
1. Build frontend: `npm run build`
2. Deploy Laravel to server
3. Setup Nginx reverse proxy
4. Configure SSL with Let's Encrypt
5. Setup queue workers
6. Setup scheduled tasks

## 🔒 Security Features

- Tenant data isolation
- JWT authentication
- API rate limiting
- CORS configuration
- SQL injection prevention
- XSS protection
- CSRF tokens
- Encrypted environment variables

## 📈 Scaling Strategy

- **Horizontal Scaling**: Multiple API servers behind load balancer
- **Database**: Read replicas for query performance
- **Queue Workers**: Auto-scaling based on queue depth
- **Caching**: Redis for frequently accessed data
- **CDN**: Static assets served via CDN

## 🔧 Development Tools

- **Laravel Telescope**: Debugging and monitoring
- **Laravel Pint**: Code style formatting
- **PHPStan**: Static analysis
- **ESLint + Prettier**: Frontend code quality

## 📝 API Documentation

API documentation available at `/api/documentation` using Scribe.

## 🤝 Contributing

1. Fork the repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

## 📄 License

MIT License

## 💬 Support

For support, email support@yourcompany.com or join our Slack channel.

---

Built with ❤️ for HoduSoft interview preparation
