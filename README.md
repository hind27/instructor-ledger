# Instructor Revenue Ledger

A Laravel-based financial ledger system that handles subscription revenue allocation and instructor payouts for an online learning platform.

## Tech Stack

- Laravel 12
- Filament v3
- Pest (testing)
- MySQL

## Setup Instructions

### 1. Clone and install dependencies
```bash
git clone <your-repo-url>
cd instructor-ledger
composer install
```

### 2. Environment setup
```bash
cp .env.example .env
php artisan key:generate
```

Update `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=instructor_ledger
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=sync
```

### 3. Database setup
```bash
php artisan migrate:fresh
php artisan db:seed --class=TestAllocationSeeder
```

### 4. Create Filament admin user
```bash
php artisan make:filament-user
```

### 5. Run the application
```bash
php artisan serve
```

Visit `http://127.0.0.1:8000/admin`

## How to Run Tests
```bash
./vendor/bin/pest
```

## How to Run Payouts
```bash
php artisan payouts:run
```

## Assumptions Made

- Platform takes a 30% cut of every subscription payment
- Instructor share is split proportionally by number of courses the student actually accessed
- Money is stored in piastres (integers) — never floats
- A student who pays upfront earns the instructor their share immediately on payment
- Refunds are handled by creating a negative ledger entry reversing the allocation
- The mock payment provider simulates 60% success, 20% failure, 20% timeout
