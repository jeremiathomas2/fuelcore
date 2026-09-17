# FUELCORE — Fuel Station Management System

A comprehensive fuel station management platform built with Laravel, designed for multi-station operations with POS, inventory, fleet management, and reporting.

![Laravel](https://img.shields.io/badge/Laravel-13-red)
![PHP](https://img.shields.io/badge/PHP-8.5-purple)
![License](https://img.shields.io/badge/License-MIT-green)

---

## Features

| Module | Capabilities |
|--------|-------------|
| **POS** | Real-time fuel dispensing, cashier sessions, payment processing |
| **Sales** | Transaction history, void handling, attendant tracking |
| **Pumps & Nozzles** | Multi-pump/nozzle management per station, status monitoring |
| **Tanks** | Level tracking, readings, leak detection, status alerts |
| **Customers** | Individual, corporate, fleet, and government accounts |
| **Fleet** | Company accounts with vehicle registration and fuel limits |
| **Deliveries** | Supplier scheduling, item-level tracking, receive workflow |
| **Suppliers** | Vendor management with delivery history |
| **Inventory** | Stock levels, movements, low-stock alerts, adjustments |
| **Expenses** | Category-based tracking, approval workflow |
| **Shifts** | Open/close/cancel cashier shifts |
| **Reconciliation** | End-of-day cash vs system reconciliation |
| **Reports** | 14 report types (sales, inventory, expenses, deliveries, performance...) |
| **Live Monitor** | Real-time pump status and transaction feed |
| **Users & Roles** | RBAC with 8 roles, granular permissions |
| **Audit Logs** | Full audit trail across all modules |
| **Settings** | Station-level and global configuration |
| **Integrations** | POS integration transactions with retry |
| **Notifications** | In-app notification center |

---

## Tech Stack

- **Backend:** Laravel 13, PHP 8.5, Eloquent ORM
- **Frontend:** Blade templates, Lucide icons, Vite, vanilla JS (no framework)
- **Database:** MySQL (dev), SQLite (tests)
- **Auth:** Session-based with role-based access control

---

## Quick Start

### Prerequisites

- PHP 8.5+ with required extensions
- Composer
- Node.js + npm
- MySQL (for development) or SQLite

### Installation

```bash
# Clone the repository
git clone <repo-url>
cd fuel-station-ms

# Install PHP dependencies
composer install

# Install JS dependencies and build assets
npm install
npm run build

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Database Setup

**MySQL:**

```bash
# Create database
mysql -u root -e "CREATE DATABASE fueldb"

# Update .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fueldb
DB_USERNAME=root
DB_PASSWORD=

# Run migrations and seed
php artisan migrate:fresh --seed
```

**SQLite (alternative):**

```bash
touch database/database.sqlite
php artisan migrate:fresh --seed
```

### Start the Server

```bash
php artisan serve
```

Visit [http://localhost:8000](http://localhost:8000)

---

## Demo Credentials

| Role | Email | Password |
|------|-------|----------|
| Super Admin | `super@fuelcore.test` | `DemoPass123` |
| Admin | `admin@fuelcore.test` | `DemoPass123` |
| Station Manager | `manager@fuelcore.test` | `DemoPass123` |
| Fuel Attendant | `attendant@fuelcore.test` | `DemoPass123` |

The super admin account has access to all modules. The attendant account is restricted to POS, sales, and view-only modules.

---

## Project Structure

```
app/
├── Console/              # Artisan commands
├── Exceptions/
├── Http/
│   ├── Controllers/      # All controllers (per module)
│   └── Middleware/        # Auth, permission checks
├── Models/               # Eloquent models
├── Services/             # Business logic (POS, Delivery, Inventory, Reports, etc.)
├── View/                 # Blade components
├── Support/              # Helpers (currency, options)
config/
├── permissions.php       # RBAC role + permission definitions
database/
├── migrations/           # 17 migrations
├── seeders/              # DatabaseSeeder (full demo data)
routes/
│   └── web.php           # Route definitions with permission middleware
resources/
│   └── views/            # Blade templates (25+ module folders)
tests/
│   ├── Feature/          # Auth, dashboard, access-control tests
│   └── Unit/             # Unit tests
```

---

## Modules & Permissions

The system uses a custom RBAC model with the following role hierarchy:

| Role | Access Level |
|------|-------------|
| `super_admin` | Full system access |
| `admin` | All operational modules |
| `station_manager` | Station-scoped operations |
| `inventory_manager` | Inventory, deliveries, suppliers |
| `cashier` | POS, payments, shifts |
| `fuel_attendant` | POS, basic sales |
| `accountant` | Reports, expenses, reconciliation |
| `auditor` | Read-only across all modules |

Permission slugs follow the pattern `module.action` (e.g., `station.manage`, `delivery.view`, `sale.void`).

---

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=AccessControlTest
php artisan test --filter=AuthenticationTest
php artisan test --filter=DashboardTest
```

Tests use an in-memory SQLite database with `DatabaseSeeder` for consistent test data.

---

## Static Prototype

A standalone HTML prototype (`filing-station-ms.html`) is included for reference. It demonstrates the UI design (navy theme, `.kpi-card`/`.data-table`/`.modal-overlay` patterns) that the Laravel implementation follows.

---

## License

MIT License.
