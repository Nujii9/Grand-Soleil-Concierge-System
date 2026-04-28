# Grand Soleil Hotel — Concierge & Billing System

## Files Overview

```
hotel_system/
├── schema.sql      ← Run this first to create the database
├── config.php      ← DB credentials & hotel configuration
├── api.php         ← Full CRUD REST API (all backend logic)
└── index.html      ← Frontend UI (single-page app)
```

---

## Setup Instructions

### 1. Create the Database

Open phpMyAdmin or run in MySQL terminal:

```sql
source /path/to/schema.sql
```

Or paste the contents of `schema.sql` into phpMyAdmin's SQL tab.

### 2. Configure Database Credentials

Edit `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // your MySQL username
define('DB_PASS', '');          // your MySQL password
define('DB_NAME', 'hotel_db');
```

### 3. Deploy Files

Place all 3 PHP/HTML files in your web server's root, e.g.:

- **XAMPP**: `C:/xampp/htdocs/hotel/`
- **WAMP**: `C:/wamp64/www/hotel/`
- **Linux/Mac**: `/var/www/html/hotel/`

Then open: `http://localhost/hotel/index.html`

---

## API Reference (api.php)

All requests go to `api.php?resource=<name>` with optional `?id=<int>`.

| Resource | GET | POST | PUT | DELETE |
|---|---|---|---|---|
| `rooms` | List / get by id | Create room | Update room (`?id=`) | Delete (`?id=`) |
| `guests` | List / search / get by id | Create guest | Update guest (`?id=`) | Delete (`?id=`) |
| `reservations` | List / filter / get by id | Create reservation | Update / check-in / check-out / cancel | Delete (`?id=`) |
| `orders` | Get orders for reservation (`?id=res_id`) | Place order | Update status (`?id=`) | Delete (`?id=`) |
| `payments` | Get payments for reservation (`?id=res_id`) | Record payment | — | Delete (`?id=`) |
| `invoice` | Generate consolidated invoice (`?id=res_id`) | — | — | — |
| `services` | List active services | — | — | — |
| `dashboard` | Live stats & in-house guests | — | — | — |

### Check-In / Check-Out

```http
PUT api.php?resource=reservations&id=5
Body: { "action": "check_in" }   // or "check_out" or "cancel"
```

### Invoice SQL Join (summary)

The invoice endpoint uses 4 JOINed queries that consolidate:
- Reservation + Guest + Room details with `nights × rate` calculation
- Line items: `room_service_orders → services → service_categories`
- Category subtotals via `GROUP BY`
- Payments history
- Automatic VAT (12%) + Service Charge (10%) calculation

---

## Features

- **Dashboard** — Live occupancy stats, today's arrivals/departures, in-house guest list
- **Reservations** — Create, filter, and cancel bookings with availability validation
- **Check-In / Out** — One-click status transitions that update room availability
- **Room Service Orders** — Place, deliver, and track per-reservation orders by category
- **Billing & Invoice** — Consolidated invoice with room charges + service line items + tax + payment history + balance due
- **Guest Directory** — Full CRUD with VIP flagging and search
- **Room Inventory** — Visual room grid with status badges and rate display

---

## Requirements

- PHP 7.4+ with PDO and PDO_MySQL extension
- MySQL 5.7+ or MariaDB 10.3+
- A local web server (XAMPP / WAMP / Laragon / php -S localhost:8000)
