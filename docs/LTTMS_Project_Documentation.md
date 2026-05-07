# Local Tourism and Travel Management System (LTTMS)

## Comprehensive Project Documentation

**Project Title:** Local Tourism and Travel Management System (LTTMS)
**Technology Stack:** PHP 8.2, MySQL 8.0, HTML5/CSS3/JavaScript, Docker
**Architecture:** Three-Tier (Presentation, Business Logic, Data)
**Version:** 1.0
**Date:** April 2026

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Problem Statement and Objectives](#2-problem-statement-and-objectives)
3. [System Architecture](#3-system-architecture)
4. [Technology Stack](#4-technology-stack)
5. [Database Design](#5-database-design)
6. [User Roles and Access Control](#6-user-roles-and-access-control)
7. [Feature Breakdown by Role](#7-feature-breakdown-by-role)
8. [File Structure and Organization](#8-file-structure-and-organization)
9. [Security Implementation](#9-security-implementation)
10. [Internationalization (i18n)](#10-internationalization-i18n)
11. [Payment System](#11-payment-system)
12. [Docker Deployment](#12-docker-deployment)
13. [Key Code Patterns and Techniques](#13-key-code-patterns-and-techniques)
14. [Testing and Quality Assurance](#14-testing-and-quality-assurance)
15. [Project Statistics](#15-project-statistics)
16. [Screenshots and UI Description](#16-screenshots-and-ui-description)
17. [Future Enhancements](#17-future-enhancements)

---

## 1. Project Overview

The **Local Tourism and Travel Management System (LTTMS)** is a full-stack web application designed to digitize and streamline the tourism industry in Myanmar. It connects three key stakeholders:

- **Customers** who want to discover, compare, and book local travel packages
- **Travel Agents** who create and manage tour packages, hotels, and transportation
- **Administrators** who oversee the entire platform, approve users, and monitor analytics

The system showcases Myanmar's major tourist destinations including Bagan, Inle Lake, Ngapali Beach, Mandalay, Golden Rock, and Hpa-An, with real-world pricing in Myanmar Kyat (MMK) and authentic hotel/transport options.

---

## 2. Problem Statement and Objectives

### Problem Statement

Myanmar's local tourism industry relies heavily on manual, paper-based processes for package management, booking, and payment verification. Travel agents have limited digital tools to reach customers, and tourists lack a centralized platform to compare and book local travel packages.

### Objectives

1. **Digitize tour package management** - Allow agents to create, update, and manage travel packages with linked hotels and transportation
2. **Enable online booking** - Provide customers a seamless booking experience with KBZ Pay mobile payment integration
3. **Implement role-based access** - Secure the system with three distinct user roles (customer, agent, admin) each with appropriate permissions
4. **Provide admin oversight** - Give administrators dashboards with analytics, user management, and booking reports
5. **Support bilingual content** - Full English and Burmese (Myanmar) language support
6. **Ensure security** - Implement industry-standard protections against common web vulnerabilities

---

## 3. System Architecture

### Three-Tier Architecture

```
+--------------------------------------------------+
|              PRESENTATION TIER                    |
|                                                   |
|  HTML5 + CSS3 + JavaScript                       |
|  - Responsive "Golden Heritage Luxe" theme       |
|  - Cormorant Garamond + Outfit fonts             |
|  - Gold/Navy/Cream color system                  |
|  - Mobile-first responsive design                |
+--------------------------------------------------+
                      |
                      v
+--------------------------------------------------+
|             BUSINESS LOGIC TIER                   |
|                                                   |
|  PHP 8.2 (Apache mod_php)                        |
|  - Authentication & Session Management           |
|  - CSRF Token Verification                       |
|  - Input Validation & Sanitization               |
|  - Role-Based Access Control                     |
|  - File Upload Handling                          |
|  - i18n Translation Engine                       |
+--------------------------------------------------+
                      |
                      v
+--------------------------------------------------+
|               DATA TIER                           |
|                                                   |
|  MySQL 8.0                                       |
|  - 7 InnoDB tables with foreign keys             |
|  - utf8mb4 character set (emoji support)         |
|  - PDO with prepared statements                  |
|  - Indexes on frequently queried columns         |
+--------------------------------------------------+
```

### Request Flow

```
Browser Request
      |
      v
Apache (mod_rewrite + mod_headers)
      |
      v
PHP Script (e.g., customer/booking.php)
      |
      +---> includes/auth.php     (session start, security headers, auth functions)
      +---> includes/lang.php     (load translations for current language)
      +---> includes/functions.php (utility functions, DB queries)
      +---> config/database.php   (PDO singleton connection)
      |
      v
Process POST (if form submission)
      |  - Verify CSRF token
      |  - Validate inputs
      |  - Execute SQL via prepared statements
      |  - Set flash message
      |  - Redirect (POST-Redirect-GET pattern)
      |
      v
Render HTML
      |  - includes/header.php (navbar, flash messages)
      |  - Page-specific content
      |  - includes/footer.php (scripts, closing tags)
      |
      v
Browser Response
```

---

## 4. Technology Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Backend** | PHP 8.2 | Server-side logic, form processing, authentication |
| **Database** | MySQL 8.0 | Relational data storage with InnoDB engine |
| **Web Server** | Apache 2.4 | HTTP server with mod_rewrite and mod_headers |
| **Frontend** | HTML5 / CSS3 / JavaScript | User interface and client-side interactions |
| **Containerization** | Docker + Docker Compose | Development and deployment environment |
| **Fonts** | Google Fonts | Cormorant Garamond (display) + Outfit (body) |
| **Payment** | KBZ Pay | Manual mobile payment with reference verification |

### Why These Technologies?

- **PHP** - Widely taught in Myanmar universities, extensive documentation, mature ecosystem for web applications
- **MySQL** - Industry-standard relational database, excellent PHP integration via PDO
- **Docker** - Ensures consistent development/deployment environments, eliminates "works on my machine" problems
- **No frameworks** - Pure PHP demonstrates understanding of core web concepts (routing, sessions, CSRF, SQL) without framework abstraction

---

## 5. Database Design

### Entity-Relationship Overview

```
users (1) -----> (N) hotels
users (1) -----> (N) transportation
users (1) -----> (N) packages
users (1) -----> (N) bookings

packages (1) ----> (N) bookings
packages (N) ----> (1) hotels         [optional]
packages (N) ----> (1) transportation  [optional]

bookings (1) ----> (N) feedback
packages (1) ----> (N) feedback
users (1) -------> (N) feedback

destinations (standalone, admin-managed)
```

### Table Definitions

#### 1. `users` Table
Stores all system users across three roles.

| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary key |
| username | VARCHAR(50) UNIQUE | Login identifier |
| email | VARCHAR(100) UNIQUE | Email address |
| password | VARCHAR(255) | bcrypt hash (PASSWORD_DEFAULT) |
| full_name | VARCHAR(100) | Display name |
| phone | VARCHAR(20) | Contact phone |
| kbz_phone | VARCHAR(20) | Agent's KBZ Pay phone (for receiving payments) |
| kbz_name | VARCHAR(100) | Agent's KBZ Pay account name |
| nrc_number | VARCHAR(50) | National Registration Card number |
| nrc_front_photo | VARCHAR(255) | Filename of NRC front image |
| nrc_back_photo | VARCHAR(255) | Filename of NRC back image |
| role | ENUM('customer','agent','admin') | Access level |
| agent_location | VARCHAR(100) | Agent's operating region in Myanmar |
| status | ENUM('pending','approved','suspended') | Account status |
| created_at | TIMESTAMP | Registration date |
| updated_at | TIMESTAMP | Last update (auto) |

#### 2. `hotels` Table
Hotels managed by travel agents.

| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary key |
| agent_id | INT (FK -> users) | Owning agent |
| name | VARCHAR(150) | Hotel name |
| location | VARCHAR(200) | Physical location |
| description | TEXT | Detailed description |
| rating | DECIMAL(2,1) | Hotel rating (0.0-5.0) |
| price_per_night | DECIMAL(10,2) | Price in MMK |
| image_url | VARCHAR(255) | Photo filename |
| created_at | TIMESTAMP | Date added |

#### 3. `transportation` Table
Transport options managed by agents.

| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary key |
| agent_id | INT (FK -> users) | Owning agent |
| type | VARCHAR(50) | VIP Bus, Flight, Private Car, etc. |
| company_name | VARCHAR(100) | Transport company |
| description | TEXT | Route details |
| price | DECIMAL(10,2) | Fare in MMK |
| created_at | TIMESTAMP | Date added |

#### 4. `packages` Table
The core entity - travel packages combining destination, hotel, and transport.

| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary key |
| agent_id | INT (FK -> users) | Creating agent |
| title | VARCHAR(200) | Package name |
| destination | VARCHAR(200) | Travel destination |
| description | TEXT | Full description |
| duration_days | INT | Trip length |
| price_per_person | DECIMAL(10,2) | Cost per person in MMK |
| max_slots | INT | Maximum capacity |
| remaining_slots | INT | Available spaces |
| hotel_id | INT (FK -> hotels, nullable) | Linked hotel |
| transportation_id | INT (FK -> transportation, nullable) | Linked transport |
| image_url | VARCHAR(255) | Package cover photo |
| rating_avg | DECIMAL(2,1) | Average customer rating |
| status | ENUM('active','inactive') | Visibility status |
| created_at | TIMESTAMP | Creation date |
| updated_at | TIMESTAMP | Last update (auto) |

#### 5. `bookings` Table
Customer booking records with payment tracking.

| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary key |
| customer_id | INT (FK -> users) | Booking customer |
| package_id | INT (FK -> packages) | Booked package |
| num_people | INT | Number of travelers |
| total_price | DECIMAL(10,2) | Calculated total (price x people) |
| booking_date | DATE | When booking was made |
| travel_date | DATE | Planned travel date |
| status | ENUM('pending','confirmed','cancelled') | Booking status |
| payment_method | VARCHAR(50) | Always 'KBZ Pay' |
| payment_reference | VARCHAR(100) | KBZ transaction ID |
| created_at | TIMESTAMP | Record creation |
| updated_at | TIMESTAMP | Last update (auto) |

#### 6. `feedback` Table
Customer reviews with agent reply capability.

| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary key |
| customer_id | INT (FK -> users) | Reviewing customer |
| package_id | INT (FK -> packages) | Reviewed package |
| booking_id | INT (FK -> bookings) | Associated booking |
| rating | INT (CHECK 1-5) | Star rating |
| comment | TEXT | Customer review text |
| agent_reply | TEXT | Agent's response |
| created_at | TIMESTAMP | Review date |
| replied_at | TIMESTAMP | Reply date |

#### 7. `destinations` Table
Admin-managed showcase destinations for the homepage.

| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary key |
| name | VARCHAR(100) | Destination name |
| tagline | VARCHAR(200) | Short description |
| image | VARCHAR(255) | Background photo filename |
| sort_order | INT | Display ordering |
| is_active | TINYINT(1) | Show/hide toggle |
| created_at | TIMESTAMP | Date added |

### Performance Indexes

```sql
CREATE INDEX idx_packages_destination ON packages(destination);
CREATE INDEX idx_packages_price ON packages(price_per_person);
CREATE INDEX idx_packages_status ON packages(status);
CREATE INDEX idx_bookings_customer ON bookings(customer_id);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_feedback_package ON feedback(package_id);
```

### Foreign Key Cascade Rules

- `ON DELETE CASCADE` on agent-owned resources (hotels, transportation, packages) - if an agent account is deleted, all their resources are removed
- `ON DELETE SET NULL` on optional links (package -> hotel, package -> transportation) - deleting a hotel/transport doesn't delete packages
- `ON DELETE CASCADE` on bookings and feedback - removing a package removes associated bookings/reviews

---

## 6. User Roles and Access Control

### Role Hierarchy

```
+------------------+     +------------------+     +------------------+
|    CUSTOMER      |     |     AGENT        |     |     ADMIN        |
+------------------+     +------------------+     +------------------+
| - Browse pkgs    |     | - All of Customer|     | - All of Agent   |
| - View details   |     | - Manage packages|     | - Manage ALL pkgs|
| - Book packages  |     | - Manage hotels  |     | - Manage users   |
| - View bookings  |     | - Manage transport|    | - Approve/suspend|
| - Leave feedback |     | - View bookings  |     | - View all data  |
|                  |     | - Reply feedback |     | - Manage dests   |
|                  |     | - KBZ settings   |     | - Analytics      |
+------------------+     +------------------+     +------------------+
```

### Registration and Approval Flow

```
New User Registration
        |
        v
[Provides: username, email, password, NRC number, NRC photos]
        |
        v
[If Agent: also selects operating region]
        |
        v
Account created with status = 'pending'
        |
        v
Admin reviews in Users Management page
        |
   +----+----+
   |         |
   v         v
Approve   Suspend
   |         |
   v         v
Can login   Cannot login
```

### Access Control Implementation

Three PHP functions enforce role-based access at the top of every protected page:

```php
requireLogin()    // Redirects to login page if not authenticated
requireRole($r)   // Returns 403 if user's role doesn't match
requireApproved() // Redirects if account status is not 'approved'
```

Example usage in `agent/packages.php`:
```php
requireRole('agent');      // Only agents can access
requireApproved();         // Must be admin-approved
```

---

## 7. Feature Breakdown by Role

### 7.1 Public Features (No Login Required)

| Feature | Endpoint | Description |
|---------|----------|-------------|
| Homepage | `index.php` | Hero section, feature highlights, top 4 packages, popular destinations |
| Browse Packages | `customer/packages.php` | Filter by destination, price range, rating; search functionality |
| Package Details | `customer/package-detail.php` | Full details with hotel, transport, ratings, rating distribution bars |
| Login | `login.php` | Username/email + password authentication |
| Register | `register.php` | Customer or agent registration with NRC verification |
| Language Toggle | `set-language.php` | Switch between English and Burmese |

### 7.2 Customer Features

| Feature | Endpoint | Description |
|---------|----------|-------------|
| Book Package | `customer/booking.php` | Select travel date, number of people, enter KBZ Pay reference |
| My Bookings | `customer/my-bookings.php` | View all bookings with status tracking (pending/confirmed/cancelled) |
| Leave Feedback | `customer/feedback.php` | Rate (1-5 stars) and review completed bookings |

### 7.3 Agent Features

| Feature | Endpoint | Description |
|---------|----------|-------------|
| Dashboard | `agent/dashboard.php` | Stats: total packages, bookings, pending count, revenue, unreplied feedback |
| My Packages | `agent/packages.php` | List all created packages with status filter |
| Add Package | `agent/add-package.php` | Create new package with photo, hotel link, transport link |
| Edit Package | `agent/edit-package.php` | Update package details, change photo |
| Manage Hotels | `agent/hotels.php` | CRUD for hotel listings |
| Manage Transport | `agent/transportation.php` | CRUD for transportation options |
| View Bookings | `agent/bookings.php` | See all bookings for own packages, confirm/cancel them |
| Customer Feedback | `agent/feedback.php` | Read reviews, reply to customer feedback |
| KBZ Pay Settings | `agent/settings.php` | Configure KBZ Pay phone number and account name |

### 7.4 Admin Features

| Feature | Endpoint | Description |
|---------|----------|-------------|
| Dashboard | `admin/dashboard.php` | Platform stats, revenue, popular destinations, monthly trends, recent bookings |
| Manage Users | `admin/users.php` | View all users, filter by role/status, approve/suspend/delete accounts |
| User Detail | `admin/user-detail.php` | View user profile and NRC photo verification |
| View NRC | `admin/view-nrc.php` | Display uploaded NRC document photos |
| All Packages | `admin/packages.php` | View all packages across all agents |
| Add Package | `admin/add-package.php` | Create package assigned to any agent |
| Edit Package | `admin/edit-package.php` | Edit any package, reassign to different agent |
| Destinations | `admin/destinations.php` | CRUD for homepage featured destinations with photo upload |
| Reports | `admin/reports.php` | Revenue reports, booking analytics |

---

## 8. File Structure and Organization

```
lttms/
|
|-- config/
|   +-- database.php           # PDO singleton connection, DB constants
|
|-- includes/
|   +-- auth.php               # Session start, security headers, auth functions, CSRF
|   +-- functions.php          # Utility functions (sanitize, format, upload handlers, queries)
|   +-- header.php             # HTML head, navbar, flash messages
|   +-- footer.php             # Closing HTML, scripts
|   +-- lang.php               # i18n engine (t() and tStatus() functions)
|
|-- lang/
|   +-- en.php                 # English translations (~200 keys)
|   +-- mm.php                 # Burmese translations (~200 keys)
|
|-- admin/
|   +-- dashboard.php          # Admin analytics dashboard
|   +-- users.php              # User management with filters
|   +-- user-detail.php        # Individual user details
|   +-- view-nrc.php           # NRC photo viewer
|   +-- packages.php           # All packages overview
|   +-- add-package.php        # Create package (any agent)
|   +-- edit-package.php       # Edit any package
|   +-- destinations.php       # Manage featured destinations
|   +-- reports.php            # Revenue/booking reports
|
|-- agent/
|   +-- dashboard.php          # Agent dashboard with stats
|   +-- packages.php           # Own packages list
|   +-- add-package.php        # Create new package
|   +-- edit-package.php       # Edit own package
|   +-- hotels.php             # Hotel CRUD
|   +-- transportation.php     # Transport CRUD
|   +-- bookings.php           # Manage booking confirmations
|   +-- feedback.php           # View/reply to reviews
|   +-- settings.php           # KBZ Pay configuration
|
|-- customer/
|   +-- packages.php           # Browse and filter packages
|   +-- package-detail.php     # Full package view with ratings
|   +-- booking.php            # Booking form with payment
|   +-- my-bookings.php        # Booking history
|   +-- feedback.php           # Submit review
|
|-- assets/
|   +-- css/style.css          # Complete stylesheet (1,044 lines)
|   +-- js/main.js             # Client-side interactions (54 lines)
|
|-- uploads/
|   +-- .htaccess              # Deny all (security baseline)
|   +-- nrc/.htaccess          # Allow images, deny PHP execution
|   +-- packages/.htaccess     # Allow images, deny PHP execution
|   +-- destinations/.htaccess # Allow images, deny PHP execution
|
|-- sql/
|   +-- schema.sql             # Full database schema + seed data
|   +-- migrate_nrc.sql        # Migration for NRC columns
|
|-- index.php                  # Homepage
|-- login.php                  # Login page
|-- register.php               # Registration page
|-- logout.php                 # Session destruction
|-- set-language.php           # Language switcher
|-- setup.php                  # Initial setup script
|-- Dockerfile                 # PHP 8.2 Apache image
|-- docker-compose.yml         # Multi-container setup
|-- .htaccess                  # URL rewriting rules
```

---

## 9. Security Implementation

### 9.1 SQL Injection Prevention

All database queries use **PDO prepared statements** with parameter binding:

```php
// SECURE: Parameter binding prevents SQL injection
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $username]);
```

The PDO connection is configured with `ATTR_EMULATE_PREPARES = false`, ensuring genuine server-side prepared statements where the SQL structure and parameters are sent separately to MySQL.

### 9.2 Cross-Site Scripting (XSS) Prevention

All user-generated output is escaped through the `sanitize()` function:

```php
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
```

- `ENT_QUOTES` escapes both single and double quotes
- `UTF-8` encoding prevents multi-byte character exploits
- Numeric values use `(int)` casting: `value="<?= (int)$price ?>"`

### 9.3 Cross-Site Request Forgery (CSRF) Protection

Every form includes a CSRF token validated on submission:

```php
// Token generation (per-session, 32 bytes of cryptographic randomness)
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Timing-safe comparison prevents timing attacks
function verifyCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
```

### 9.4 Session Security

Session hardening is applied before `session_start()`:

```php
ini_set('session.cookie_httponly', 1);    // Blocks JavaScript access to cookies
ini_set('session.cookie_samesite', 'Lax'); // Prevents cross-site cookie sending
ini_set('session.use_strict_mode', 1);    // Rejects uninitialized session IDs
```

Session fixation prevention on login:
```php
session_regenerate_id(true);  // New session ID after authentication
```

### 9.5 Security Headers

Three headers are set on every authenticated page:

```php
header('X-Frame-Options: DENY');                          // Prevents clickjacking
header('X-Content-Type-Options: nosniff');                // Prevents MIME sniffing
header('Referrer-Policy: strict-origin-when-cross-origin'); // Controls referrer leakage
```

### 9.6 Password Hashing

Passwords are hashed using PHP's built-in bcrypt implementation:

```php
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);  // bcrypt
password_verify($password, $user['password']);                  // Constant-time verify
```

### 9.7 File Upload Security

Three-layer protection for image uploads:

1. **MIME validation** - Uses `getimagesize()` to verify actual file content (not just extension):
   ```php
   $info = @getimagesize($file['tmp_name']);
   if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'])) {
       return null;
   }
   ```

2. **Random filenames** - Prevents path traversal and overwrites:
   ```php
   $filename = bin2hex(random_bytes(16)) . '.' . $ext;
   ```

3. **Apache protection** - Upload directories disable PHP execution:
   ```apache
   Allow from all
   php_flag engine off
   ```

### 9.8 Open Redirect Prevention

The language switcher extracts only the path from the referer URL:

```php
$parsed = parse_url($referer);
$path = $parsed['path'] ?? '/';
header('Location: ' . $path . $query);  // Only path, never scheme://host
```

### 9.9 Error Handling

Production error display is disabled:
```php
ini_set('display_errors', 0);   // Never show errors to users
ini_set('log_errors', 1);       // Log errors to server log instead
```

---

## 10. Internationalization (i18n)

### Architecture

The i18n system uses PHP arrays for translation storage with two helper functions:

```php
// lang.php - Translation engine
$translations = require __DIR__ . '/../lang/' . $currentLang . '.php';

function t(string $key, string $default = ''): string {
    global $translations;
    return $translations[$key] ?? ($default ?: $key);
}

function tStatus(string $status): string {
    $map = ['pending' => 'status_pending', 'confirmed' => 'status_confirmed', ...];
    return t($map[$status] ?? '', ucfirst($status));
}
```

### Language Files

**English** (`lang/en.php`):
```php
return [
    'hero_title' => 'Discover Myanmar\'s Hidden Treasures',
    'hero_subtitle' => 'Curated local travel packages from verified agents',
    'nav_home' => 'Home',
    'nav_packages' => 'Packages',
    // ~200 translation keys
];
```

**Burmese** (`lang/mm.php`):
```php
return [
    'hero_title' => 'မြန်မာ့ လျှို့ဝှက်ရတနာများကို ရှာဖွေလိုက်ပါ',
    'hero_subtitle' => 'အတည်ပြုထားသော အေးဂျင့်များထံမှ ခရီးစဉ်များ',
    'nav_home' => 'ပင်မ',
    'nav_packages' => 'ခရီးစဉ်များ',
    // ~200 translation keys
];
```

### Language Switching

Users click the language toggle in the navbar, which calls `set-language.php`:

```php
$supported = ['en', 'mm'];
$lang = $_GET['lang'] ?? 'en';
if (in_array($lang, $supported)) {
    $_SESSION['lang'] = $lang;
}
```

The HTML `lang` attribute adapts dynamically:
```html
<html lang="<?= $currentLang === 'mm' ? 'my' : 'en' ?>">
```

---

## 11. Payment System

### KBZ Pay Integration

The system uses a **manual verification** approach for KBZ Pay (Myanmar's leading mobile payment platform):

```
Customer Booking Flow:
                                          
1. Customer selects package    -->  Booking form shows:
                                     - Agent's KBZ Pay phone number
                                     - Agent's KBZ Pay account name
                                     - Total price calculation

2. Customer transfers via      -->  Opens KBZ Pay app on phone
   KBZ Pay app                      Sends money to agent's number

3. Customer enters transaction -->  Booking form requires:
   reference number                  payment_reference field

4. Booking saved as 'pending'  -->  Agent sees in bookings list

5. Agent verifies payment      -->  Checks KBZ Pay transaction
   and confirms booking             Changes status to 'confirmed'
```

### Agent KBZ Pay Setup

Agents configure their payment details through `agent/settings.php`:
- KBZ Pay phone number (displayed to customers during booking)
- KBZ Pay account holder name (for customer verification)

---

## 12. Docker Deployment

### Container Architecture

```
+------------------------+     +------------------------+
|     web container      |     |      db container      |
|                        |     |                        |
|  PHP 8.2 + Apache 2.4 |---->|  MySQL 8.0             |
|                        |     |                        |
|  Extensions:           |     |  Database: lttms_db    |
|  - pdo_mysql           |     |  User: lttms_user      |
|  - gd (image proc)     |     |  Charset: utf8mb4      |
|                        |     |                        |
|  Port: 8888 -> 80      |     |  Port: 3307 -> 3306    |
+------------------------+     +------------------------+
         |                              |
         v                              v
  ./  (bind mount)            lttms_mysql_data (volume)
```

### Dockerfile

```dockerfile
FROM php:8.2-apache

# Install PHP extensions for MySQL and image processing
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mysqli gd

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure document root and permissions
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html
```

### Docker Compose

```yaml
services:
  web:
    build: .
    ports: ["0.0.0.0:8888:80"]
    depends_on:
      db:
        condition: service_healthy     # Wait for MySQL to be ready
    environment:
      - DB_HOST=db
      - DB_NAME=lttms_db
      - DB_USER=lttms_user
      - DB_PASS=lttms_password
    volumes:
      - .:/var/www/html               # Live code reload

  db:
    image: mysql:8.0
    volumes:
      - lttms_mysql_data:/var/lib/mysql
      - ./sql/schema.sql:/docker-entrypoint-initdb.d/01-schema.sql  # Auto-initialize
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 5s
      retries: 10
```

### Running the Application

```bash
# Start the application
docker compose up -d --build

# Access at http://localhost:8888

# Demo accounts:
#   Admin:  admin  / password
#   Agent:  agent1 / password

# Stop the application
docker compose down

# Reset database (destroy volume)
docker compose down -v
```

---

## 13. Key Code Patterns and Techniques

### 13.1 PDO Singleton Pattern

The database connection uses the singleton pattern to ensure only one connection per request:

```php
function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}
```

### 13.2 POST-Redirect-GET Pattern

All form submissions follow the PRG pattern to prevent duplicate submissions:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form...
    setFlash('Success message!');
    redirect(BASE_URL . '/agent/packages.php');  // GET redirect
}
```

### 13.3 Flash Messages

One-time messages are stored in the session and cleared on display:

```php
function setFlash(string $message): void {
    $_SESSION['flash'] = $message;
}

function getFlashMessage(): ?string {
    if (isset($_SESSION['flash'])) {
        $msg = $_SESSION['flash'];
        unset($_SESSION['flash']);  // Shown once, then deleted
        return $msg;
    }
    return null;
}
```

### 13.4 Star Rating Rendering

Ratings display as visual stars using Unicode characters:

```php
function renderStars(float $rating): string {
    $full = (int)floor($rating);
    $half = ($rating - $full) >= 0.25 && ($rating - $full) < 0.75 ? 1 : 0;
    $extra = ($rating - $full) >= 0.75 ? 1 : 0;
    $full += $extra;
    $empty = 5 - $full - $half;
    // Renders: ★★★★☆ (4.0)
}
```

### 13.5 Dynamic Price Calculation

Booking total updates in real-time via JavaScript:

```javascript
// Price updates as customer changes number of people
numPeople.addEventListener('input', function() {
    var total = this.value * parseFloat(this.dataset.price);
    document.getElementById('bookingTotal').textContent =
        new Intl.NumberFormat().format(total) + ' MMK';
});
```

### 13.6 Atomic Booking Transaction

Bookings use database transactions to ensure data consistency:

```php
$pdo->beginTransaction();

// Insert booking record
$stmt = $pdo->prepare("INSERT INTO bookings (...) VALUES (...)");
$stmt->execute([...]);

// Decrement remaining slots atomically
$stmt = $pdo->prepare(
    "UPDATE packages SET remaining_slots = remaining_slots - ?
     WHERE id = ? AND remaining_slots >= ?"
);
$stmt->execute([$num_people, $package_id, $num_people]);

$pdo->commit();
```

---

## 14. Testing and Quality Assurance

### 14.1 Automated QA Results

**76 out of 76 tests passed** across the following categories:

| Category | Tests | Status |
|----------|-------|--------|
| Public endpoints (homepage, packages, login, register) | 8 | PASS |
| Customer booking lifecycle (browse -> book -> feedback) | 10 | PASS |
| Agent CRUD operations (packages, hotels, transport) | 14 | PASS |
| Admin user management (approve, suspend, delete) | 8 | PASS |
| Admin package management (add, edit, view) | 6 | PASS |
| CSRF rejection (POST without valid token) | 6 | PASS |
| Role isolation (customer can't access agent pages) | 6 | PASS |
| Input validation (empty fields, invalid prices) | 8 | PASS |
| i18n (Burmese language switch) | 4 | PASS |
| Security headers verification | 4 | PASS |
| Logout and session destruction | 2 | PASS |

### 14.2 Security Scan Results (Snyk)

**Static Application Security Testing (SAST):**
- Critical: 0
- High: 0
- Medium: 2 (false positives - Snyk taint tracker limitation)
- Low: 0

**Container Security Scan:**
- Application layer: 0 vulnerabilities
- Base image (Debian): 2 medium severity (systemd, libxml2 in OS packages)

### 14.3 Vulnerabilities Fixed During Development

| Finding | Severity | Fix Applied |
|---------|----------|-------------|
| XSS in edit-package form fields (admin + agent) | HIGH | Added (int) cast on numeric values |
| XSS in transportation price field | HIGH | Added (int) cast |
| XSS in filter tab href attributes (users, packages) | HIGH | Wrapped in sanitize() |
| Open redirect in set-language.php | MEDIUM | Extract path-only from HTTP_REFERER |
| Path traversal in destination image delete | MEDIUM | Added basename() wrapper |
| Session fixation on login | HIGH | Added session_regenerate_id(true) |
| Missing security headers | MEDIUM | Added X-Frame-Options, X-Content-Type-Options, Referrer-Policy |

---

## 15. Project Statistics

| Metric | Value |
|--------|-------|
| Total source files | 41 |
| PHP files | 37 |
| Total PHP lines | ~4,900 |
| CSS lines | 1,044 |
| JavaScript lines | 54 |
| SQL lines (schema + seed) | 210 |
| Database tables | 7 |
| API endpoints | 29 |
| User roles | 3 (customer, agent, admin) |
| Languages supported | 2 (English, Burmese) |
| Translation keys | ~200 per language |
| QA tests passed | 76/76 (100%) |
| Snyk HIGH vulnerabilities | 0 |
| Seed hotels | 10 (real Myanmar hotels) |
| Seed packages | 8 (real Myanmar destinations) |
| Seed transport options | 8 (real Myanmar companies) |

---

## 16. Screenshots and UI Description

### Visual Design: "Golden Heritage Luxe" Theme

The interface uses a sophisticated gold-and-navy color palette inspired by Myanmar's golden pagodas:

- **Primary Font:** Cormorant Garamond (serif) - for headings, evoking heritage and elegance
- **Body Font:** Outfit (sans-serif) - for readability in body text and forms
- **Color Palette:**
  - Gold: `#c8a951` (primary accent, buttons, highlights)
  - Navy: `#14213d` (navbar, dark backgrounds, text)
  - Cream: `#faf8f0` (page backgrounds)
  - Midnight: `#0a1128` (footer, dark sections)
  - Ivory: `#f5f0e8` (card backgrounds)

### Key Pages

1. **Homepage** - Full-width hero section with call-to-action, feature cards grid, top 4 package cards with photos and star ratings, destination showcase with background images and gradient overlays

2. **Package Listing** - Filterable grid with search by destination, price range slider, minimum rating filter. Each card shows photo, duration badge, star rating, remaining slots, and price

3. **Package Detail** - Full package information with linked hotel details, transport details, rating distribution bars (5-star breakdown), customer reviews with agent replies, and booking CTA

4. **Booking Page** - Package summary, traveler count with dynamic price calculation, KBZ Pay payment instructions showing agent's phone and name, transaction reference input

5. **Agent Dashboard** - Statistics cards (packages, bookings, pending, revenue, unreplied feedback), quick action buttons, recent bookings table

6. **Admin Dashboard** - Platform-wide statistics grid, popular destinations table, monthly revenue trends, recent bookings with status badges

7. **User Management** - Filterable table by role (customer/agent/admin) and status (pending/approved/suspended), inline approve/suspend/delete actions, NRC verification links

---

## 17. Future Enhancements

1. **Email Notifications** - Send booking confirmations, status updates, and agent replies via email
2. **Real-time Payment API** - Integrate KBZ Pay API for automatic payment verification instead of manual reference checking
3. **Image Gallery** - Multiple photos per package and hotel instead of single cover image
4. **Advanced Search** - Full-text search, date-based filtering, sorting options
5. **Review Moderation** - Admin ability to moderate inappropriate reviews
6. **Agent Verification** - More robust agent verification process with business license upload
7. **Mobile App** - Progressive Web App (PWA) or native mobile application
8. **Booking Calendar** - Visual calendar showing available dates and slot counts
9. **Multi-Currency** - Support for USD alongside MMK for international tourists
10. **Analytics Dashboard** - Charts and graphs for revenue trends, booking patterns, and user growth

---

## Appendix A: Demo Accounts

| Role | Username | Password | Status |
|------|----------|----------|--------|
| Admin | admin | password | Approved |
| Agent | agent1 | password | Approved |
| Customer | (register new) | (user-chosen) | Pending approval |

**Access URL:** `http://localhost:8888`

## Appendix B: Seed Data

The system comes pre-loaded with realistic Myanmar tourism data:

- **10 Hotels** across Inle Lake, Bagan, Ngapali, Mandalay, Kyaiktiyo, and Hpa-An
- **8 Transportation options** including JJ Express VIP buses, Mandalar Minn, Air KBZ flights, and Myanmar National Airlines
- **8 Travel Packages** ranging from budget (250,000 MMK) to premium (2,100,000 MMK)
- **6 Featured Destinations** with taglines (Bagan, Inle Lake, Ngapali Beach, Mandalay, Golden Rock, Hpa-An)

---

*This document was prepared for the LTTMS project seminar presentation, April 2026.*
